<?php
/**
 * cron_agent_blog.php - Agente Autônomo de Blog do ISP Preparatórios
 * Executa periodicamente via CLI / Cron Job para publicar artigos no Blog.
 */

if (php_sapi_name() !== 'cli' && empty($_GET['secret'])) {
    die("Acesso permitido apenas via CLI ou chave de segurança.");
}

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/includes/pci_mcp_client.php';

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

echo "[" . date('Y-m-d H:i:s') . "] === INICIANDO AGENTE AUTÔNOMO DE BLOG ===\n";

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
    echo "[" . date('Y-m-d H:i:s') . "] ERRO: Chave API do provedor '$provider' não configurada.\n";
    exit(1);
}

// 1. Buscar concurso destaque no Maranhão (ou Educação)
$uf = 'MA';
$mcpRes = PciMcpClient::buscarPorCargo('professor', $uf);
$items = $mcpRes['data'] ?? [];

if (!empty($items)) {
    $featured = $items[array_rand(array_slice($items, 0, 3))];
    $tituloConcurso = $featured['titulo'] ?? 'Concurso Público';
    $vagasSalario = $featured['vagas_salario'] ?? 'Diversas vagas';
    $cargos = !empty($featured['cargos']) ? implode(', ', $featured['cargos']) : ($featured['cargos_resumo'] ?? 'Educação');
    $escolaridade = $featured['formacao'] ?? 'Médio / Superior';
    $linkEdital = $featured['noticia']['link'] ?? 'https://www.pciconcursos.com.br';
    $dias = $featured['datas']['dias_restantes'] ?? 'Abertas';

    $articleTopic = "Análise e Guia de Estudos do $tituloConcurso ($uf)";
    $contestInfo = "Dados do Edital:\n- Órgão: $tituloConcurso ($uf)\n- Vagas e Remuneração: $vagasSalario\n- Cargos: $cargos\n- Escolaridade: $escolaridade\n- Prazo de Inscrição: $dias dias restantes\n- Link do Edital Oficial: $linkEdital";
} else {
    $articleTopic = "Dicas de Estudo e Preparação de Alto Rendimento para Concursos Públicos";
    $contestInfo = "";
}

echo "[" . date('Y-m-d H:i:s') . "] Tema selecionado: '$articleTopic'\n";

// 2. System Instruction para Diagramação Profissional HTML
$systemInstruction = "Você é o Redator Oficial do ISP Preparatórios. Escreva um artigo de blog sobre o concurso indicado, otimizado para SEO e com diagramação HTML profissional.

Retorne ESTRITAMENTE um JSON com as chaves: title, category, meta_title, meta_description, seo_keywords, image_prompt, html_content.

No 'html_content', use obrigatoriamente:
- <div class=\"sec-title\"><span class=\"sec-num\">1</span> <h2>Título da Seção</h2></div>
- <div class=\"subsec\">Subtítulo da Seção</div>
- <div class=\"box tip\"><strong>💡 Dica do Professor:</strong> Conteúdo...</div>
- <div class=\"box info\"><strong>ℹ️ Informação Importante:</strong> Conteúdo...</div>
- <div class=\"table-wrap\"><table class=\"table\"><thead><tr><th>Cargo</th><th>Vagas</th><th>Salário</th></tr></thead><tbody>...</tbody></table></div>
- <div class=\"post-cta-box\"><h3>Quer garantir a sua aprovação neste concurso?</h3><p>Estude com o ISP Preparatórios!</p><a href=\"/cursos.php\" class=\"btn\" style=\"padding: 0.8rem 2rem;\">Conhecer Cursos</a></div>";

$userMessage = "Tema: $articleTopic\n$contestInfo";

// 3. Invocação da API do Provedor de IA
$jsonRaw = '';

if ($provider === 'gemini') {
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;
    $payload = json_encode([
        "contents" => [["parts" => [["text" => $systemInstruction . "\n\n" . $userMessage]]]],
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
        echo "[" . date('Y-m-d H:i:s') . "] ERRO cURL Gemini: $curlError\n";
        exit(1);
    }

    $parsed = json_decode($res, true);

    if (isset($parsed['error'])) {
        echo "[" . date('Y-m-d H:i:s') . "] ERRO API Gemini (HTTP $httpCode): " . ($parsed['error']['message'] ?? json_encode($parsed['error'])) . "\n";
        exit(1);
    }

    $finishReason = $parsed['candidates'][0]['finishReason'] ?? '';
    if ($finishReason === 'SAFETY') {
        echo "[" . date('Y-m-d H:i:s') . "] ERRO: Resposta bloqueada por filtro de segurança.\n";
        exit(1);
    }

    // Suporte a modelos com thinking (múltiplas parts)
    $parts = $parsed['candidates'][0]['content']['parts'] ?? [];
    $jsonRaw = '';
    foreach ($parts as $part) {
        if (isset($part['text'])) {
            $jsonRaw .= $part['text'];
        }
    }
} else {
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
        "temperature" => 0.5,
        "max_tokens" => 8192
    ]);

    $ch = curl_init($ep['url']);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', "Authorization: Bearer $apiKey"],
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $res = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false || !empty($curlError)) {
        echo "[" . date('Y-m-d H:i:s') . "] ERRO cURL $provider: $curlError\n";
        exit(1);
    }

    $parsed = json_decode($res, true);

    if (isset($parsed['error'])) {
        $errMsg = is_array($parsed['error']) ? ($parsed['error']['message'] ?? json_encode($parsed['error'])) : $parsed['error'];
        echo "[" . date('Y-m-d H:i:s') . "] ERRO API $provider (HTTP $httpCode): $errMsg\n";
        exit(1);
    }

    $jsonRaw = $parsed['choices'][0]['message']['content'] ?? '';
}

$aiData = extract_article_payload($jsonRaw, $articleTopic);

if (empty($aiData) || empty($aiData['html_content'])) {
    echo "[" . date('Y-m-d H:i:s') . "] ERRO ao obter resposta da IA. Resposta: " . mb_substr($jsonRaw, 0, 500) . "\n";
    exit(1);
}

$title = $aiData['title'] ?? $articleTopic;
$category = $aiData['category'] ?? 'Concursos';
$metaTitle = $aiData['meta_title'] ?? $title;
$metaDesc = $aiData['meta_description'] ?? $title;
$keywords = $aiData['seo_keywords'] ?? 'concurso, maranhao, professores';
$htmlContent = $aiData['html_content'];
$imagePrompt = $aiData['image_prompt'] ?? 'education study books classroom';

// Truncar campos para respeitar limites do banco
$title = safe_substr($title, 0, 250);
$metaTitle = safe_substr($metaTitle, 0, 490);
$metaDesc = safe_substr($metaDesc, 0, 490);
$keywords = safe_substr($keywords, 0, 490);

// Slug
$slugText = strtr(strtolower($title), ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','õ'=>'o','ô'=>'o','ú'=>'u','ç'=>'c']);
$slug = preg_replace('/[\s-]+/', '-', trim(preg_replace('/[^a-z0-9\s-]/', '', $slugText)));

// Capa por IA
$coverFilename = 'agent_cron_' . uniqid() . '.jpg';
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) { @mkdir($uploadDir, 0777, true); }

$finalImgPrompt = "3d graphic illustration of public exam edital verticalizado study map, checklist notebook, pen, study strategy diagram, glowing blue and orange lighting, high quality 8k render, no human faces";
$imgUrl = "https://image.pollinations.ai/prompt/" . urlencode($finalImgPrompt) . "?width=800&height=500&nologo=true";
$chImg = curl_init($imgUrl);
curl_setopt_array($chImg, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => false]);
$imgData = curl_exec($chImg);

if ($imgData && strlen($imgData) > 5000) {
    file_put_contents($uploadDir . $coverFilename, $imgData);
} else {
    $coverFilename = '';
}

// Inserir no Banco
$now = date('Y-m-d H:i:s');
$stmt = $pdo->prepare("INSERT INTO posts (title, category, content, cover_image, image_alt, status, seo_keywords, meta_title, meta_description, slug, created_at) VALUES (?, ?, ?, ?, ?, 'publicado', ?, ?, ?, ?, ?)");
$stmt->execute([$title, $category, $htmlContent, $coverFilename, $title, $keywords, $metaTitle, $metaDesc, $slug, $now]);

echo "[" . date('Y-m-d H:i:s') . "] SUCESSO! Post publicado id: " . $pdo->lastInsertId() . " ('$title')\n";
