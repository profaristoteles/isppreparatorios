<?php
/**
 * Endpoint AJAX para Captura de Reservas / Lista de Interesse
 * ISP Preparatórios
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/reservation_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

// Obter dados do POST (JSON ou FormData)
$rawInput = file_get_contents('php://input') ?: '';
$postData = $_POST;
if (empty($postData) && !empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $postData = $decoded;
    }
}

// Honeypot Anti-Spam (Campo invisível que apenas bots preenchem)
if (!empty($postData['website_url_check']) || !empty($postData['website_hp'])) {
    echo json_encode(['success' => false, 'message' => 'Solicitação inválida.']);
    exit;
}

// Obter respostas customizadas se enviadas
$customAnswers = [];
if (!empty($postData['custom_answers']) && is_array($postData['custom_answers'])) {
    $customAnswers = $postData['custom_answers'];
} else {
    // Também verificar campos dinâmicos custom_ campo por campo
    foreach ($postData as $key => $val) {
        if (str_starts_with($key, 'custom_')) {
            $customAnswers[substr($key, 7)] = trim($val);
        }
    }
}

$input = [
    'campaign_id'        => (int)($postData['campaign_id'] ?? 0),
    'campaign_slug'      => trim($postData['campaign_slug'] ?? ''),
    'name'               => trim($postData['name'] ?? ''),
    'email'              => strtolower(trim($postData['email'] ?? '')),
    'phone'              => trim($postData['phone'] ?? ''),
    'city'               => trim($postData['city'] ?? ''),
    'state'              => strtoupper(trim($postData['state'] ?? '')),
    'preferred_modality' => trim($postData['preferred_modality'] ?? ''),
    'consent_privacy'    => !empty($postData['consent_privacy']),
    'custom_answers'     => $customAnswers,
    'utm_source'         => trim($postData['utm_source'] ?? ''),
    'utm_medium'         => trim($postData['utm_medium'] ?? ''),
    'utm_campaign'       => trim($postData['utm_campaign'] ?? ''),
    'utm_content'        => trim($postData['utm_content'] ?? ''),
    'utm_term'           => trim($postData['utm_term'] ?? '')
];

$result = ReservationService::createReservation($pdo, $input);

echo json_encode($result);
