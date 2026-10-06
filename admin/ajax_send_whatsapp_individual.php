<?php
/**
 * Endpoint AJAX para Envio Individual de Mensagem WhatsApp via Gateway/Evolution API
 * ISP Preparatórios
 */

require_once 'auth.php';
require_once '../db_config.php';
require_once '../includes/notification_service.php';
require_once '../includes/evocrm_service.php';
require_once 'includes/admin_security.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido.']);
    exit;
}

// Validação de CSRF Token
$token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$sessionToken = $_SESSION['admin_csrf_token'] ?? '';
if (empty($token) || empty($sessionToken) || !hash_equals($sessionToken, $token)) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada ou token de segurança inválido. Recarregue a página e tente novamente.']);
    exit;
}

$leadId      = (int)($_POST['lead_id'] ?? 0);
$name        = trim($_POST['name'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$email       = trim($_POST['email'] ?? '');
$messageRaw  = trim($_POST['message'] ?? '');
$contextType = trim($_POST['context_type'] ?? 'individual_lead');

if (empty($messageRaw)) {
    echo json_encode(['success' => false, 'message' => 'O texto da mensagem não pode estar vazio.']);
    exit;
}

// Se o telefone ou nome não foram enviados, tenta buscar do lead
if ($leadId > 0 && (empty($phone) || empty($name))) {
    try {
        $stmtLead = $pdo->prepare("SELECT * FROM leads WHERE id = ? LIMIT 1");
        $stmtLead->execute([$leadId]);
        $lead = $stmtLead->fetch();
        if ($lead) {
            if (empty($name)) $name = $lead['name'];
            if (empty($phone)) $phone = $lead['phone_normalized'] ?: $lead['phone_original'];
            if (empty($email)) $email = $lead['email'];
        }
    } catch (\Exception $e) {}
}

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Número de WhatsApp do lead não informado.']);
    exit;
}

// Normaliza o telefone
$cleanPhone = preg_replace('/[^0-9]/', '', $phone);
if (strlen($cleanPhone) === 10 || strlen($cleanPhone) === 11) {
    $cleanPhone = '55' . $cleanPhone;
}

if (strlen($cleanPhone) < 12) {
    echo json_encode(['success' => false, 'message' => 'Número de telefone inválido para envio via WhatsApp (' . htmlspecialchars($phone) . ').']);
    exit;
}

// Substituição de variáveis dinâmicas no template da mensagem
$firstName = explode(' ', trim($name))[0] ?? $name;
$baseUrl = NotificationService::getBaseUrl();

$finalMessage = str_replace([
    '{nome}',
    '{primeiro_nome}',
    '{email}',
    '{telefone}',
    '{link_site}'
], [
    $name ?: 'Aluno',
    $firstName ?: 'Aluno',
    $email ?: '',
    $phone,
    $baseUrl
], $messageRaw);

$config = get_config($pdo);

// Dispara usando NotificationService com flag force_api (não faz redirect silencioso para wa.me)
$result = NotificationService::sendWhatsApp($config, $cleanPhone, $finalMessage, [
    'force_api'   => true,
    'type'        => $contextType,
    'title'       => 'Disparo Individual - ' . ($name ?: $cleanPhone),
    'success_msg' => 'Mensagem disparada com sucesso no WhatsApp de ' . ($name ?: $cleanPhone) . '!'
], $pdo);

if ($result['success']) {
    echo json_encode([
        'success'       => true,
        'message'       => $result['message'],
        'phone'         => $cleanPhone,
        'recipient'     => $name,
        'sent_at'       => date('d/m/Y H:i:s'),
        'preview'       => mb_substr($finalMessage, 0, 150) . (mb_strlen($finalMessage) > 150 ? '...' : '')
    ]);
} else {
    // Retorna mensagem com link wa.me auxiliar para caso queira fallback manual
    $waLink = NotificationService::generateWaMeLink($cleanPhone, $finalMessage);
    echo json_encode([
        'success'   => false,
        'message'   => $result['message'],
        'status'    => $result['status'] ?? 'error',
        'wa_link'   => $waLink
    ]);
}
