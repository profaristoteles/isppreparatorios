<?php
/**
 * Endpoint AJAX de Captura de Lead para Aulas Gratuitas
 * 
 * Processa o formulário modal de captura, valida consentimento LGPD,
 * deduplica o lead com tratamento de conflitos, gera token HMAC e enfileira no EvoCRM.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/includes/aulas_gratuitas_utils.php';
require_once __DIR__ . '/admin/includes/admin_security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

// Obter dados do POST (JSON ou Form Payload)
$rawInput = file_get_contents('php://input') ?: '';
$postData = $_POST;
if (empty($postData) && !empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) $postData = $decoded;
}

// Honeypot Anti-Spam (Campo invisível preenchido por robôs)
if (!empty($postData['website_url_check']) || !empty($postData['website_hp'])) {
    echo json_encode(['success' => false, 'message' => 'Solicitação inválida.']);
    exit;
}

$video_id = (int)($postData['video_id'] ?? 0);
$material_id = (int)($postData['material_id'] ?? 0);

if (!$video_id || !$material_id) {
    echo json_encode(['success' => false, 'message' => 'Videoaula ou material não especificado.']);
    exit;
}

// Tratar disparo isolado do evento material_requested (Etapa 4)
$event_type = trim($postData['event_type'] ?? '');
if (!empty($postData['event_only']) || $event_type === 'material_requested') {
    log_free_video_event($pdo, $video_id, 'material_requested', null, ['material_id' => $material_id]);
    echo json_encode(['success' => true, 'message' => 'Evento material_requested registrado com sucesso.']);
    exit;
}

// Revalidar material e videoaula no backend
$stmtMat = $pdo->prepare("SELECT m.*, v.campaign_code, v.slug as video_slug, c.slug as channel_slug, b.slug as board_slug, d.slug as discipline_slug, t.slug as teacher_slug, ct.slug as contest_slug 
                         FROM free_materials m 
                         JOIN free_videos v ON m.video_id = v.id 
                         JOIN free_channels c ON v.channel_id = c.id 
                         LEFT JOIN free_boards b ON v.board_id = b.id 
                         LEFT JOIN free_disciplines d ON v.discipline_id = d.id 
                         LEFT JOIN free_teachers t ON v.teacher_id = t.id 
                         LEFT JOIN free_contests ct ON v.contest_id = ct.id 
                         WHERE m.id = ? AND m.video_id = ? AND m.active = 1 AND m.deleted_at IS NULL AND v.status = 'publicado' AND v.active = 1 AND v.deleted_at IS NULL 
                         LIMIT 1");
$stmtMat->execute([$material_id, $video_id]);
$matInfo = $stmtMat->fetch();

if (!$matInfo) {
    echo json_encode(['success' => false, 'message' => 'O material solicitado não está disponível ou a videoaula não foi publicada.']);
    exit;
}

// Validação dos Consentimentos LGPD
$consent_privacy = !empty($postData['consent_privacy']); // Obrigatório (Ciência da Política e Tratamento)
$consent_marketing = !empty($postData['consent_marketing']); // Opcional (Comunicações de Marketing)

if (!$consent_privacy) {
    echo json_encode(['success' => false, 'message' => 'Por favor, confirme a ciência da Política de Privacidade para receber o material.']);
    exit;
}

// Gerar Tags Automáticas de comportamento no sistema
$autoTags = ['origem:aulas-gratuitas', 'interesse:material-gratuito'];
if (!empty($matInfo['channel_slug']))    $autoTags[] = 'canal:' . $matInfo['channel_slug'];
if (!empty($matInfo['discipline_slug'])) $autoTags[] = 'disciplina:' . $matInfo['discipline_slug'];
if (!empty($matInfo['teacher_slug']))    $autoTags[] = 'professor:' . $matInfo['teacher_slug'];
if (!empty($matInfo['board_slug']))      $autoTags[] = 'banca:' . $matInfo['board_slug'];
if (!empty($matInfo['contest_slug']))    $autoTags[] = 'concurso:' . $matInfo['contest_slug'];
if (!empty($matInfo['video_slug']))      $autoTags[] = 'video:' . $matInfo['video_slug'];
if (!empty($matInfo['campaign_code'])) $autoTags[] = 'campanha:' . strtolower($matInfo['campaign_code']);

$leadPayload = [
    'name' => trim($postData['name'] ?? ''),
    'email' => strtolower(trim($postData['email'] ?? '')),
    'phone' => trim($postData['phone'] ?? ''),
    'source' => 'aulas-gratuitas',
    'utm_source' => trim($postData['utm_source'] ?? ''),
    'utm_medium' => trim($postData['utm_medium'] ?? ''),
    'utm_campaign' => trim($postData['utm_campaign'] ?? ''),
    'utm_content' => trim($postData['utm_content'] ?? ''),
    'utm_term' => trim($postData['utm_term'] ?? ''),
    'tags' => $autoTags,
    'campaign_code' => $matInfo['campaign_code'] ?? '',
    'consent_privacy' => true,
    'consent_marketing' => $consent_marketing,
    'consent_text_version' => 'v1.0',
    'privacy_policy_version' => 'v1.0'
];

$result = process_lead_capture($pdo, $leadPayload);

if ($result['status'] === 'error') {
    echo json_encode(['success' => false, 'message' => $result['message']]);
    exit;
}

// Se for sucesso ou se for conflito, gerar o token HMAC apropriado
if ($result['status'] === 'success') {
    $subject_type = 'lead';
    $subject_id = $result['lead_id'];
    
    // Registrar evento da jornada de captura
    log_free_video_event($pdo, $video_id, 'lead_captured', $subject_id);
} else {
    // Caso 'conflict': Não associar ao Lead A nem B, gerar autorização temporária baseada no conflito
    $subject_type = 'conflict';
    $subject_id = $result['conflict_id'];
    
    log_free_video_event($pdo, $video_id, 'lead_captured', null, ['conflict_id' => $subject_id]);
}

// Gerar Token HMAC assinado para download do PDF
$token = generate_download_token($subject_type, $subject_id, $material_id, $video_id);
$download_url = "/download-material.php?token=" . urlencode($token);

// Buscar link configurado do Grupo de WhatsApp VIP
$config = get_config($pdo);
$whatsapp_group_url = !empty($config['whatsapp_group_url']) ? $config['whatsapp_group_url'] : '';

echo json_encode([
    'success' => true,
    'message' => 'Material liberado com sucesso!',
    'download_url' => $download_url,
    'whatsapp_group_url' => $whatsapp_group_url,
    'status_code' => $result['status']
]);
