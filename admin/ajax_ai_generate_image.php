<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once '../includes/nano_banana_generator.php';

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

    $filename = NanoBananaGenerator::generateImage($prompt, $pdo, 1024, 1024);

    if ($filename) {
        echo json_encode([
            'success' => true,
            'filename' => $filename,
            'url' => '../uploads/' . $filename
        ]);
    } else {
        throw new Exception('Não foi possível gerar a imagem no estilo Nano Banana 3D.');
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['error' => 'Erro fatal no PHP: ' . $e->getMessage()]);
}

