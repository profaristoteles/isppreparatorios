<?php
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

    $config = get_config($pdo);
    $openai_key = $config['ai_openai_key'] ?? '';
    $image_url = '';

    if (!empty($openai_key)) {
        // Usar OpenAI DALL-E 3
        $url = 'https://api.openai.com/v1/images/generations';
        $payload = json_encode([
            "model" => "dall-e-3",
            "prompt" => $prompt,
            "n" => 1,
            "size" => "1024x1024"
        ]);
        
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nAuthorization: Bearer $openai_key\r\n",
                'content' => $payload,
                'timeout' => 60,
                'ignore_errors' => true
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
        ];
        
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception('Falha na conexão com a API da OpenAI.');
        }
        
        $result = json_decode($response, true);
        if (isset($result['error'])) {
            throw new Exception($result['error']['message'] ?? 'Erro desconhecido da API OpenAI.');
        }
        
        $image_url = $result['data'][0]['url'] ?? '';
    } else {
        // Fallback para Pollinations AI (Gratuito, sem chave)
        $encoded_prompt = urlencode($prompt . ', high quality, photorealistic, professional blog cover');
        $image_url = "https://image.pollinations.ai/prompt/{$encoded_prompt}?width=1024&height=1024&nologo=true";
    }

    if (empty($image_url)) {
        throw new Exception('Não foi possível gerar a imagem.');
    }

    // Baixar a imagem gerada
    $image_data = @file_get_contents($image_url);
    if ($image_data === false) {
        throw new Exception('Não foi possível baixar a imagem gerada.');
    }

    // Salvar localmente
    $filename = uniqid('ai_img_') . '.jpg';
    $filepath = '../uploads/' . $filename;
    
    if (file_put_contents($filepath, $image_data) !== false) {
        echo json_encode(['success' => true, 'filename' => $filename, 'url' => '../uploads/' . $filename]);
    } else {
        throw new Exception('Erro ao salvar a imagem no servidor.');
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['error' => 'Erro fatal no PHP: ' . $e->getMessage()]);
}
