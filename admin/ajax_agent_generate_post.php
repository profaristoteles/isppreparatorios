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
    // 1. Tentar JSON direto
    $json = json_decode($rawText, true);
    if (is_array($json) && !empty($json['html_content'])) {
        return $json;
    }

    // 2. Tentar bloco JSON ```json ... ```
    if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/i', $rawText, $m)) {
        $json = json_decode($m[1], true);
        if (is_array($json) && !empty($json['html_content'])) {
            return $json;
        }
    }

    // 3. Tentar JSON regex do primeiro { até o último }
    if (preg_match('/\{[\s\S]*\}/', $rawText, $m)) {
        $json = json_decode($m[0], true);
        if (is_array($json) && !empty($json['html_content'])) {
            return $json;
        }
    }

    // 4. Fallback Robusto: Se a IA retornou HTML direto
    $cleanHtml = preg_replace('/^```(?:html)?\s*|\s*```$/i', '', trim($rawText));
    if (strlen($cleanHtml) > 100) {
        $title = $fallbackTopic;
        if (preg_match('/<h[12][^>]*>(.*?)<\/h[12]>/i', $cleanHtml, $mTitle)) {
            $title = strip_tags($mTitle[1]);
        }

        $resumo = safe_substr(trim(preg_replace('/\s+/', ' ', strip_tags($cleanHtml))), 0, 155) . '...';

        return [
            'title' => $title,
            'category' => 'Concursos',
            'meta_title' => $title,
            'meta_description' => $resumo,
            'seo_keywords' => 'concurso, edital, professores, maranhão',
            'image_prompt' => 'education contest study books classroom',
            'html_content' => $cleanHtml
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

    // 1. Coletar dados base conforme o modo
    $contestInfo = "";
    $articleTopic = "";

    if ($mode === 'pci_contest') {
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
        } else {
            $articleTopic = "Guia Prático de Estudos para Concursos da Educação em $uf";
        }
    } elseif ($mode === 'education_topic') {
        $topicsList = [
            "Como Gabaritar a LDB (Lei de Diretrizes e Bases) em Concursos da Educação",
            "5 Conteúdo Pedagógicos que Mais Caem nas Provas de Professores",
            "Português para Concursos Públicos: Principais Pegadinhas da Banca",
            "Planejamento de Aula e Avaliação Escolar: Guia de Revisão Rápida"
        ];
        $articleTopic = $topicsList[array_rand($topicsList)];
    } else {
        $articleTopic = !empty($customPrompt) ? $customPrompt : "Dicas de Estudo e Preparação para Concursos Públicos";
    }

    // 2. Engenharia de Prompt Especializada com Diagramação Profissional HTML
    $systemInstruction = "Você é o Redator Oficial do ISP Preparatórios. Escreva um artigo de blog sobre o concurso indicado, otimizado para SEO e com diagramação HTML profissional.

Retorne ESTRITAMENTE um JSON com as chaves:
1. \"title\": Título do Post
2. \"category\": Concursos
3. \"meta_title\": Meta Title SEO
4. \"meta_description\": Resumo de 150 caracteres
5. \"seo_keywords\": Palavras-chave
6. \"image_prompt\": Prompt em inglês para foto da capa
7. \"html_content\": Conteúdo HTML completo do artigo com div.sec-title, div.subsec, div.box.tip, div.box.info, div.table-wrap com table e div.post-cta-box";

    $userMessage = "Tema do Artigo: $articleTopic\n" . ($contestInfo ? "\n$contestInfo\n" : "") . ($customPrompt ? "\nInstruções extras do Admin: $customPrompt" : "");

    // 3. Executar chamada de API conforme o Provedor de IA
    $jsonRaw = '';

    if ($provider === 'gemini') {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;
        $payload = json_encode([
            "contents" => [
                ["parts" => [["text" => $systemInstruction . "\n\n" . $userMessage]]]
            ]
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $res = curl_exec($ch);

        $parsed = json_decode($res, true);
        $jsonRaw = $parsed['candidates'][0]['content']['parts'][0]['text'] ?? '';
    } else {
        // Groq / OpenAI / OpenRouter
        $endpoints = [
            'groq' => ['url' => 'https://api.groq.com/openai/v1/chat/completions', 'model' => 'llama-3.3-70b-versatile'],
            'openai' => ['url' => 'https://api.openai.com/v1/chat/completions', 'model' => 'gpt-4o-mini'],
            'openrouter' => ['url' => 'https://openrouter.ai/api/v1/chat/completions', 'model' => 'meta-llama/llama-3.3-70b-instruct']
        ];
        $ep = $endpoints[$provider];

        $payload = json_encode([
            "model" => $ep['model'],
            "messages" => [
                ["role" => "system", "content" => $systemInstruction],
                ["role" => "user", "content" => $userMessage]
            ],
            "temperature" => 0.5
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
            CURLOPT_TIMEOUT => 45,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $res = curl_exec($ch);

        $parsed = json_decode($res, true);
        $jsonRaw = $parsed['choices'][0]['message']['content'] ?? '';
    }

    // 4. Extrair e validar dados do JSON ou HTML retornado pela IA
    $aiData = extract_article_payload($jsonRaw, $articleTopic);

    if (empty($aiData) || empty($aiData['html_content'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Falha ao processar a resposta da IA. Resposta bruta: ' . substr($jsonRaw, 0, 300)
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

    $imgUrl = "https://image.pollinations.ai/prompt/" . urlencode($imagePrompt . " high quality education study classroom portrait") . "?width=800&height=500&nologo=true";
    
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
