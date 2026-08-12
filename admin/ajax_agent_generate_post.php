<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'auth.php';
require_once '../db_config.php';
require_once '../includes/pci_mcp_client.php';

header('Content-Type: application/json; charset=utf-8');

function safe_substr($str, $start, $length) {
    if (function_exists('mb_substr')) {
        return mb_substr($str, $start, $length, 'UTF-8');
    }
    return substr($str, $start, $length);
}

function extract_article_payload($rawText, $fallbackTopic) {
    if (empty(trim($rawText))) return null;

    $text = trim($rawText);
    
    // Remove delimitadores de código markdown ```json ou ```html se houver
    $cleanedText = preg_replace('/^```(?:json|html)?\s*|\s*```$/i', '', $text);
    $cleanedText = trim($cleanedText);

    // 1. Tentar json_decode normal
    $json = json_decode($cleanedText, true);

    // 2. Se falhar, tentar sanitizar caracteres de controle (quebras de linha não escapadas dentro de strings JSON)
    if (!is_array($json)) {
        $sanitized = preg_replace_callback('/"([^"\\\\]*|\\\\.)*"/s', function($m) {
            return str_replace(["\r\n", "\r", "\n", "\t"], ["\\n", "\\n", "\\n", "\\t"], $m[0]);
        }, $cleanedText);
        $json = json_decode($sanitized, true);
    }

    // 3. Se json_decode funcionou e encontrou html_content
    if (is_array($json) && !empty($json['html_content'])) {
        $html = $json['html_content'];
        // Limpar qualquer prefixo ou sufixo JSON acidental dentro de html_content
        $html = preg_replace('/^\s*\{?\s*"html_content"\s*:\s*"/i', '', $html);
        $html = preg_replace('/"\s*\}\s*$/', '', $html);
        $json['html_content'] = trim($html);
        return $json;
    }

    // 4. Fallback por Regex: Extrair os campos diretamente do texto sem depender de json_decode
    $extractedTitle = $fallbackTopic;
    if (preg_match('/"title"\s*:\s*"([^"]+)"/i', $cleanedText, $mTitle)) {
        $extractedTitle = stripslashes($mTitle[1]);
    }

    $htmlContent = '';
    if (preg_match('/"html_content"\s*:\s*"(.*?)"\s*\}\s*$/s', $cleanedText, $mHtml)) {
        $htmlContent = stripslashes($mHtml[1]);
    } elseif (preg_match('/"html_content"\s*:\s*"(.*)/s', $cleanedText, $mHtml)) {
        $htmlContent = preg_replace('/"\s*\}\s*$/', '', $mHtml[1]);
        $htmlContent = stripslashes($htmlContent);
    }

    // Se ainda assim o htmlContent contiver JSON inicial ou rótulos json {
    if (empty($htmlContent) && (strpos($cleanedText, '<') !== false)) {
        $htmlContent = preg_replace('/^(?:json)?\s*\{[\s\S]*?"html_content"\s*:\s*"/i', '', $cleanedText);
        $htmlContent = preg_replace('/"\s*\}\s*$/', '', $htmlContent);
    }

    // Limpeza rigorosa final anti-vazamento de estrutura JSON
    $htmlContent = preg_replace('/^\s*json\s*\{[\s\S]*?"html_content"\s*:\s*"/i', '', $htmlContent);
    $htmlContent = preg_replace('/^\s*\{[\s\S]*?"html_content"\s*:\s*"/i', '', $htmlContent);
    $htmlContent = trim($htmlContent);

    if (!empty($htmlContent) && strlen($htmlContent) > 50) {
        $resumo = safe_substr(trim(preg_replace('/\s+/', ' ', strip_tags($htmlContent))), 0, 155) . '...';
        return [
            'title' => $extractedTitle,
            'category' => 'Concursos',
            'meta_title' => safe_substr($extractedTitle, 0, 70),
            'meta_description' => $resumo,
            'seo_keywords' => 'concurso, edital, professores, maranhão',
            'image_prompt' => 'edital verticalizado mapa de estudos concurso publico',
            'html_content' => $htmlContent
        ];
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método de requisição inválido.']);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?: $_POST;

    $mode = $data['mode'] ?? 'pci_contest';
    $customPrompt = trim($data['prompt'] ?? '');
    $status = in_array($data['status'] ?? '', ['publicado', 'rascunho']) ? $data['status'] : 'rascunho';
    $uf = strtoupper(trim($data['uf'] ?? 'MA'));

    // Configurações do Banco
    $config = get_config($pdo);
    $provider = $config['ai_provider'] ?? 'gemini';

    $apiKeys = [
        'gemini'     => $config['ai_api_key'] ?? '',
        'groq'       => $config['ai_groq_key'] ?? '',
        'openai'     => $config['ai_openai_key'] ?? '',
        'openrouter' => $config['ai_openrouter_key'] ?? '',
    ];

    $apiKey = $apiKeys[$provider] ?? '';

    if (empty($apiKey)) {
        echo json_encode([
            'success' => false,
            'error' => "A chave de API do provedor '$provider' não foi configurada no painel. Acesse Configurações."
        ]);
        exit;
    }

    // 1. Coletar dados base conforme o modo e o prompt do usuário
    $contestInfo = "";
    $articleTopic = "";
    $defaultCategory = "Concursos";

    // Verificar se o tema/prompt é claramente um concurso público
    $isExplicitContest = false;
    if (!empty($customPrompt)) {
        if (preg_match('/\b(concurso|edital|prefeitura|selecao|seleção|processo seletivo|banca|gabarito|vagas)\b/i', $customPrompt)) {
            $isExplicitContest = true;
        }
    }

    if (!empty($customPrompt) && strlen($customPrompt) > 2) {
        $items = [];
        if ($isExplicitContest || $mode === 'pci_contest') {
            // Tentar pesquisar edital no PCI Concursos se for um tema de concurso
            $mcpRes = PciMcpClient::pesquisarConcursos($customPrompt, $uf);
            $items = $mcpRes['data'] ?? [];

            if (empty($items)) {
                $cidadeLimpa = trim(preg_replace('/(concurso|prefeitura|pública|da|de|do|ma|sp|rj|pi|ce|ba|se|al|pe|pb|rn)/i', '', $customPrompt));
                if (!empty($cidadeLimpa) && strlen($cidadeLimpa) >= 3) {
                    $mcpResCidade = PciMcpClient::buscarPorCidade($cidadeLimpa);
                    $items = $mcpResCidade['data'] ?? [];
                }
            }
        }

        if (!empty($items)) {
            // Encontrou edital cadastrado no PCI para o termo solicitado
            $featured = $items[0];
            $tituloConcurso = $featured['titulo'] ?? $customPrompt;
            $vagasSalario = $featured['vagas_salario'] ?? 'Vagas a definir no edital';
            $cargos = !empty($featured['cargos']) ? implode(', ', $featured['cargos']) : ($featured['cargos_resumo'] ?? 'Educação e Administração');
            $escolaridade = $featured['formacao'] ?? 'Fundamental / Médio / Superior';
            $linkEdital = $featured['noticia']['link'] ?? 'https://www.pciconcursos.com.br';
            $dias = $featured['datas']['dias_restantes'] ?? 'Em breve / Previsto';

            $articleTopic = "Análise e Guia de Estudos do $tituloConcurso ($uf)";
            $contestInfo = "Dados Oficiais do Edital no PCI Concursos:\n- Órgão: $tituloConcurso ($uf)\n- Vagas e Remuneração: $vagasSalario\n- Cargos: $cargos\n- Escolaridade: $escolaridade\n- Prazo de Inscrição: $dias\n- Link do Edital Oficial: $linkEdital";
            $defaultCategory = "Concursos";
        } elseif ($isExplicitContest) {
            // É um concurso solicitado pelo usuário, mas não listado no PCI no momento
            $articleTopic = "Guia de Estudos e Análise do Concurso Público: " . ucwords(mb_strtolower($customPrompt, 'UTF-8'));
            $contestInfo = "O artigo DEVE ser ESTRITAMENTE sobre o concurso indicado: " . $customPrompt . " ($uf).\nFoque na preparação completa (disciplinas essenciais, dicas de estudos e bancas organizadoras).";
            $defaultCategory = "Concursos";
        } else {
            // NÃO É CONCURSO ou É TEMA GERAL / LEGISLAÇÃO / NOTÍCIA / MEC / PEDAGOGIA!
            $articleTopic = $customPrompt;
            $contestInfo = "Tema Solicitado pelo Usuário: $customPrompt.\nATENÇÃO: Este artigo NÃO é sobre um concurso público fictício! É um artigo temático, educativo ou informativo sobre o assunto especificado ($customPrompt). Apresente dados precisos, contexto pedagógico/normativo, análise detalhada e orientações práticas para educadores e estudantes. NÃO invente tabelas de vagas de concursos nem edital fictício.";
            
            if (preg_match('/\b(portaria|mec|lei|ldb|diretriz|resolucao|resolução|decreto|bncc|legisla)/i', $customPrompt)) {
                $defaultCategory = "Legislação / MEC";
            } elseif (preg_match('/\b(dica|estudo|metodologia|planejamento|aula|pedagog)/i', $customPrompt)) {
                $defaultCategory = "Pedagogia / Dicas";
            } else {
                $defaultCategory = "Notícias";
            }
        }
    } elseif ($mode === 'pci_contest') {
        // Se nenhum prompt específico foi digitado, buscar edital geral de destaque na PCI para o estado
        $mcpRes = PciMcpClient::buscarPorCargo('professor', $uf);
        if (empty($mcpRes['data'])) {
            $mcpRes = PciMcpClient::pesquisarConcursos('concurso', $uf);
        }

        $items = $mcpRes['data'] ?? [];
        if (!empty($items)) {
            $featured = $items[0];
            $tituloConcurso = $featured['titulo'] ?? 'Concurso Público';
            $vagasSalario = $featured['vagas_salario'] ?? 'Diversas vagas';
            $cargos = !empty($featured['cargos']) ? implode(', ', $featured['cargos']) : ($featured['cargos_resumo'] ?? 'Educação');
            $escolaridade = $featured['formacao'] ?? 'Médio / Superior';
            $linkEdital = $featured['noticia']['link'] ?? 'https://www.pciconcursos.com.br';
            $dias = $featured['datas']['dias_restantes'] ?? 'Abertas';

            $articleTopic = "Análise e Guia de Estudos do $tituloConcurso ($uf)";
            $contestInfo = "Dados do Edital:\n- Órgão: $tituloConcurso ($uf)\n- Vagas e Remuneração: $vagasSalario\n- Cargos: $cargos\n- Escolaridade: $escolaridade\n- Prazo de Inscrição: $dias dias restantes\n- Link do Edital Oficial: $linkEdital";
            $defaultCategory = "Concursos";
        } else {
            $articleTopic = "Guia Prático de Estudos para Concursos da Educação em $uf";
            $defaultCategory = "Concursos";
        }
    } elseif ($mode === 'education_topic') {
        $topicsList = [
            "Como Gabaritar a LDB (Lei de Diretrizes e Bases) em Concursos da Educação",
            "5 Conteúdos Pedagógicos que Mais Caem nas Provas de Professores",
            "Português para Concursos Públicos: Principais Pegadinhas da Banca",
            "Planejamento de Aula e Avaliação Escolar: Guia de Revisão Rápida"
        ];
        $articleTopic = $topicsList[array_rand($topicsList)];
        $defaultCategory = "Pedagogia / Dicas";
    } else {
        $articleTopic = !empty($customPrompt) ? $customPrompt : "Dicas de Estudo e Preparação para Concursos Públicos";
        $defaultCategory = "Geral";
    }

    // 2. Engenharia de Prompt Especializada com Diagramação Profissional HTML
    $systemInstruction = "Você é o Redator Oficial do ISP Preparatórios. Escreva um artigo de blog completo e aprofundado sobre o tema solicitado ('$articleTopic'), otimizado para SEO e com diagramação HTML profissional impecável.

DIRETRIZES DE FORMATAÇÃO E DIAGRAMAÇÃO HTML:
1. No 'html_content', escreva APENAS o código HTML limpo do corpo do artigo. É ESTRITAMENTE PROIBIDO incluir a palavra 'json', chaves {}, ou aspas do formato JSON dentro do 'html_content'.
2. PROIBIÇÃO DE TEXTO VERTICAL: Escreva todos os textos, títulos e tabelas de forma horizontal normal. NUNCA crie células de tabela ou blocos com letras empilhadas verticalmente.
3. ESTRUTURA VISUAL OBRIGATÓRIA NO HTML:
   - Títulos de Seção: <div class=\"sec-title\"><span class=\"sec-num\">1</span> <h2>1. Visão Geral e Contexto</h2></div>
   - Subtítulos: <div class=\"subsec\">Subtítulo da Seção</div>
   - Dica do Professor/Especialista: <div class=\"box tip\"><strong>💡 Dica de Estudos:</strong> Conteúdo da dica...</div>
   - Informações Relevantes / Alertas: <div class=\"box info\"><strong>ℹ️ Informações Importantes:</strong> Conteúdo fluido...</div>
   - Tabelas (quando aplicável): <div class=\"table-wrap\"><table class=\"table\"><thead><tr><th>Tópico</th><th>Detalhe</th><th>Impacto</th></tr></thead><tbody><tr><td class=\"b\">Item</td><td class=\"c\">Descrição</td><td>Aplicação</td></tr></tbody></table></div>
   - Caixa de Chamada (CTA) ao final: <div class=\"post-cta-box\"><h3>Quer se preparar melhor para concursos e seleções da educação?</h3><p>Estude com o ISP Preparatórios!</p><a href=\"/cursos.php\" class=\"btn\">Conhecer Nossos Cursos</a></div>

DIRETRIZ DE TEMÁTICA:
- Se a solicitação for sobre um Concurso Público específico, apresente análise de edital, vagas, disciplinas e dicas de estudos.
- Se a solicitação for sobre uma Portaria do MEC, Lei, LDB, Resolução, Notícia ou Tema Pedagógico, escreva um artigo educacional/informativo detalhando os impactos, a aplicação prática na educação e como o assunto pode ser cobrado. NÃO invente edital fictício de concurso público se o tema não for sobre concurso!

Retorne ESTRITAMENTE um objeto JSON válido com as seguintes chaves:
{
  \"title\": \"Título Oficial e Atrativo do Post\",
  \"category\": \"$defaultCategory\",
  \"meta_title\": \"Meta Title SEO (máx 70 caracteres)\",
  \"meta_description\": \"Resumo SEO de 150 caracteres\",
  \"seo_keywords\": \"Palavras-chave separadas por vírgula\",
  \"image_prompt\": \"conceito visual ilustração 3d moderna sobre o tema do artigo\",
  \"html_content\": \"<div class=\\\"sec-title\\\">...</div>...\"
}";

    $userMessage = "Tema Solicitado: $articleTopic\n" . ($contestInfo ? "\n$contestInfo\n" : "");

    // 3. Executar chamada de API conforme o Provedor de IA
    $jsonRaw = '';
    $debugInfo = '';

    if ($provider === 'gemini') {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;
        $payload = json_encode([
            "contents" => [
                ["parts" => [["text" => $systemInstruction . "\n\n" . $userMessage]]]
            ],
            "generationConfig" => [
                "responseMimeType" => "application/json",
                "temperature" => 0.7,
                "maxOutputTokens" => 8192
            ]
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $res = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($res === false || !empty($curlError)) {
            echo json_encode([
                'success' => false,
                'error' => "Erro de conexão com a API Gemini: $curlError"
            ]);
            exit;
        }

        $parsed = json_decode($res, true);

        // Verificar erros da API
        if (isset($parsed['error'])) {
            $errMsg = $parsed['error']['message'] ?? json_encode($parsed['error']);
            echo json_encode([
                'success' => false,
                'error' => "Erro da API Gemini (HTTP $httpCode): $errMsg"
            ]);
            exit;
        }

        // Verificar bloqueio por segurança
        $finishReason = $parsed['candidates'][0]['finishReason'] ?? '';
        if ($finishReason === 'SAFETY') {
            echo json_encode([
                'success' => false,
                'error' => 'A IA bloqueou a resposta por filtro de segurança. Tente reformular o tema.'
            ]);
            exit;
        }

        // Extrair texto — suporte a modelos com thinking (múltiplas parts)
        $parts = $parsed['candidates'][0]['content']['parts'] ?? [];
        $jsonRaw = '';
        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $jsonRaw .= $part['text'];
            }
        }

        $debugInfo = "Provider: gemini | HTTP: $httpCode | FinishReason: $finishReason | Parts: " . count($parts);

    } else {
        // Groq / OpenAI / OpenRouter
        $openrouterModel = !empty($config['ai_openrouter_model']) ? $config['ai_openrouter_model'] : 'meta-llama/llama-3.3-70b-instruct';
        $endpoints = [
            'groq' => ['url' => 'https://api.groq.com/openai/v1/chat/completions', 'model' => 'llama-3.3-70b-versatile'],
            'openai' => ['url' => 'https://api.openai.com/v1/chat/completions', 'model' => 'gpt-4o-mini'],
            'openrouter' => ['url' => 'https://openrouter.ai/api/v1/chat/completions', 'model' => $openrouterModel]
        ];
        $ep = $endpoints[$provider] ?? null;

        if (!$ep) {
            echo json_encode(['success' => false, 'error' => "Provedor '$provider' não reconhecido."]);
            exit;
        }

        $payload = json_encode([
            "model" => $ep['model'],
            "messages" => [
                ["role" => "system", "content" => $systemInstruction],
                ["role" => "user", "content" => $userMessage]
            ],
            "temperature" => 0.5,
            "max_tokens" => 8192
        ]);

        $ch = curl_init($ep['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "Authorization: Bearer $apiKey"
            ],
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $res = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($res === false || !empty($curlError)) {
            echo json_encode([
                'success' => false,
                'error' => "Erro de conexão com a API ($provider): $curlError"
            ]);
            exit;
        }

        $parsed = json_decode($res, true);

        // Verificar erros da API
        if (isset($parsed['error'])) {
            $errMsg = is_array($parsed['error']) ? ($parsed['error']['message'] ?? json_encode($parsed['error'])) : $parsed['error'];
            echo json_encode([
                'success' => false,
                'error' => "Erro da API $provider (HTTP $httpCode): $errMsg"
            ]);
            exit;
        }

        $jsonRaw = $parsed['choices'][0]['message']['content'] ?? '';
        $debugInfo = "Provider: $provider | HTTP: $httpCode | Model: " . $ep['model'];
    }

    // 4. Extrair e validar dados do JSON ou HTML retornado pela IA
    $aiData = extract_article_payload($jsonRaw, $articleTopic);

    if (empty($aiData) || empty($aiData['html_content'])) {
        $rawPreview = mb_substr($jsonRaw, 0, 500);
        echo json_encode([
            'success' => false,
            'error' => "Falha ao processar a resposta da IA. [$debugInfo] Resposta bruta: $rawPreview"
        ]);
        exit;
    }

    $title = $aiData['title'] ?? $articleTopic;
    $category = $aiData['category'] ?? 'Concursos';
    $metaTitle = $aiData['meta_title'] ?? $title;
    $metaDesc = $aiData['meta_description'] ?? 'Saiba tudo sobre concursos e oportunidades da educação com o ISP Preparatórios.';
    $keywords = $aiData['seo_keywords'] ?? 'concurso, edital, professores, maranhão';
    $htmlContent = $aiData['html_content'];
    $imagePrompt = $aiData['image_prompt'] ?? 'education contest study books student maranhao';

    // Truncar campos para respeitar limites do banco
    $title = safe_substr($title, 0, 250);
    $metaTitle = safe_substr($metaTitle, 0, 490);
    $metaDesc = safe_substr($metaDesc, 0, 490);
    $keywords = safe_substr($keywords, 0, 490);

    // Gerar Slug amigável
    $slugText = strtr(strtolower($title), [
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
        'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
        'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
        'ç'=>'c','ñ'=>'n'
    ]);
    $slug = preg_replace('/[\s-]+/', '-', trim(preg_replace('/[^a-z0-9\s-]/', '', $slugText)));

    // 5. Gerar e salvar a Imagem de Capa via IA (Pollinations AI)
    $coverFilename = 'agent_' . uniqid() . '.jpg';
    $uploadDir = __DIR__ . '/../uploads/';
    if (!file_exists($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $finalImgPrompt = "3d graphic illustration of public exam edital verticalizado study map, checklist notebook, pen, study strategy diagram, glowing blue and orange lighting, high quality 8k render, no human faces";
    $imgUrl = "https://image.pollinations.ai/prompt/" . urlencode($finalImgPrompt) . "?width=800&height=500&nologo=true";
    
    $chImg = curl_init($imgUrl);
    curl_setopt_array($chImg, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $imgData = curl_exec($chImg);

    if ($imgData && strlen($imgData) > 5000) {
        @file_put_contents($uploadDir . $coverFilename, $imgData);
    } else {
        $coverFilename = '';
    }

    // 6. Inserir no Banco de Dados
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO posts (title, category, content, cover_image, image_alt, status, seo_keywords, meta_title, meta_description, slug, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $title,
        $category,
        $htmlContent,
        $coverFilename,
        $title,
        $status,
        $keywords,
        $metaTitle,
        $metaDesc,
        $slug,
        $now
    ]);

    $newPostId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'post_id' => $newPostId,
        'title' => $title,
        'category' => $category,
        'cover_image' => $coverFilename,
        'slug' => $slug,
        'status' => $status,
        'message' => "Post '$title' gerado com diagramação profissional em HTML e salvo com sucesso!"
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno ao executar o Agente: ' . $e->getMessage()
    ]);
}
