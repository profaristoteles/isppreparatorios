<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'auth.php';
require_once '../db_config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $prompt = $data['prompt'] ?? '';

    if (empty($prompt)) {
        echo json_encode(['error' => 'O prompt não pode estar vazio.']);
        exit;
    }

    // Obter configurações do banco de dados
    $config = get_config($pdo);
    $provider = $config['ai_provider'] ?? 'gemini';

    // Selecionar a API Key do provedor ativo
    $apiKeys = [
        'gemini'     => $config['ai_api_key'] ?? '',
        'groq'       => $config['ai_groq_key'] ?? '',
        'openai'     => $config['ai_openai_key'] ?? '',
        'openrouter' => $config['ai_openrouter_key'] ?? '',
    ];

    $apiKey = $apiKeys[$provider] ?? '';

    if (empty($apiKey)) {
        $providerNames = ['gemini' => 'Google Gemini', 'groq' => 'Groq', 'openai' => 'OpenAI', 'openrouter' => 'OpenRouter'];
        echo json_encode(['error' => 'Chave da API do ' . ($providerNames[$provider] ?? $provider) . ' não configurada. Vá em Configurações e insira a chave.']);
        exit;
    }

    // Instrução do sistema (igual para todos)
    $system_instruction = "Você é um redator especialista em concursos públicos da área da educação no Brasil. Escreva o artigo completo formatado em HTML nativo (use tags <h2>, <p>, <ul>, <strong>). Não use formatação markdown de código, retorne apenas o HTML limpo, pronto para ser inserido em um editor de texto rico.";
    $user_message = "Tema sugerido pelo administrador: " . $prompt;

    // Montar URL, payload conforme o provedor e executar via cURL
    $url = '';
    $payload = '';
    $authHeader = '';

    switch ($provider) {
        case 'gemini':
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;
            $payload = json_encode([
                "contents" => [
                    ["parts" => [["text" => $system_instruction . "\n\n" . $user_message]]]
                ]
            ]);
            break;

        case 'groq':
            $url = 'https://api.groq.com/openai/v1/chat/completions';
            $payload = json_encode([
                "model" => "llama-3.3-70b-versatile",
                "messages" => [
                    ["role" => "system", "content" => $system_instruction],
                    ["role" => "user", "content" => $user_message]
                ],
                "max_tokens" => 4096,
                "temperature" => 0.7
            ]);
            $authHeader = "Authorization: Bearer $apiKey";
            break;

        case 'openai':
            $url = 'https://api.openai.com/v1/chat/completions';
            $payload = json_encode([
                "model" => "gpt-4o-mini",
                "messages" => [
                    ["role" => "system", "content" => $system_instruction],
                    ["role" => "user", "content" => $user_message]
                ],
                "max_tokens" => 4096,
                "temperature" => 0.7
            ]);
            $authHeader = "Authorization: Bearer $apiKey";
            break;

        case 'openrouter':
            $url = 'https://openrouter.ai/api/v1/chat/completions';
            $payload = json_encode([
                "model" => "meta-llama/llama-3.3-70b-instruct",
                "messages" => [
                    ["role" => "system", "content" => $system_instruction],
                    ["role" => "user", "content" => $user_message]
                ],
                "max_tokens" => 4096,
                "temperature" => 0.7
            ]);
            $authHeader = "Authorization: Bearer $apiKey";
            break;

        default:
            echo json_encode(['error' => 'Provedor de IA desconhecido: ' . $provider]);
            exit;
    }

    // Executar requisição via cURL
    $headers = ['Content-Type: application/json'];
    if (!empty($authHeader)) {
        $headers[] = $authHeader;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || !empty($curlError)) {
        echo json_encode(['error' => "Erro de conexão com a API ($provider): $curlError"]);
        exit;
    }

    $result = json_decode($response, true);

    // Extrair o conteúdo HTML conforme o formato de resposta do provedor
    $generated_html = '';

    if ($provider === 'gemini') {
        // Verificar erros da API do Gemini
        if (isset($result['error'])) {
            echo json_encode(['error' => 'Erro da API Gemini: ' . ($result['error']['message'] ?? json_encode($result['error']))]);
            exit;
        }

        // Verificar bloqueio por segurança
        $finishReason = $result['candidates'][0]['finishReason'] ?? '';
        if ($finishReason === 'SAFETY') {
            echo json_encode(['error' => 'A IA bloqueou a resposta por filtro de segurança. Tente reformular o tema.']);
            exit;
        }

        // Suporte a modelos com thinking (múltiplas parts)
        $parts = $result['candidates'][0]['content']['parts'] ?? [];
        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $generated_html .= $part['text'];
            }
        }
    } else {
        // Formato OpenAI-compatible (Groq, OpenAI, OpenRouter)
        if (isset($result['error'])) {
            $errMsg = is_array($result['error']) ? ($result['error']['message'] ?? json_encode($result['error'])) : $result['error'];
            echo json_encode(['error' => 'Erro da API: ' . $errMsg]);
            exit;
        }
        $generated_html = $result['choices'][0]['message']['content'] ?? '';
    }

    if (empty($generated_html)) {
        echo json_encode(['error' => 'A IA não retornou um conteúdo válido. Tente reformular o prompt. (HTTP ' . $httpCode . ')']);
        exit;
    }

    // Limpar formatação markdown residual
    $generated_html = preg_replace('/^```html\s*/i', '', $generated_html);
    $generated_html = preg_replace('/\s*```$/', '', $generated_html);

    echo json_encode(['success' => true, 'html' => trim($generated_html)]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Erro interno no servidor: ' . $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['error' => 'Erro fatal no PHP: ' . $e->getMessage()]);
}
