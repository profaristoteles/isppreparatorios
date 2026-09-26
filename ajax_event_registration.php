<?php
/**
 * Endpoint AJAX de Inscrição em Eventos e Aulões - ISP Preparatórios
 * 
 * Processa a inscrição de alunos em eventos, cadastra o lead,
 * registra na tabela event_registrations e notifica o Administrador
 * via E-mail e WhatsApp cadastrados no painel.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/includes/notification_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

// 1. Obter dados do POST (JSON ou FormData)
$rawInput = file_get_contents('php://input') ?: '';
$postData = $_POST;
if (empty($postData) && !empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $postData = $decoded;
    }
}

// 2. Honeypot Anti-Spam
if (!empty($postData['website_url_check']) || !empty($postData['website_hp'])) {
    echo json_encode(['success' => false, 'message' => 'Solicitação inválida.']);
    exit;
}

// 3. Sanitização e Validação dos Campos
$eventId = (int)($postData['event_id'] ?? 0);
$eventSlug = trim($postData['event_slug'] ?? '');
$name = trim($postData['name'] ?? '');
$email = strtolower(trim($postData['email'] ?? ''));
$phone = trim($postData['phone'] ?? '');
$modality = strtolower(trim($postData['modality'] ?? 'geral'));
if (!in_array($modality, ['presencial', 'online', 'geral'])) {
    $modality = 'geral';
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, informe seu nome completo.']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, informe um endereço de e-mail válido.']);
    exit;
}

$cleanPhone = preg_replace('/[^0-9]/', '', $phone);
if (strlen($cleanPhone) < 10) {
    echo json_encode(['success' => false, 'message' => 'Por favor, informe um WhatsApp válido com DDD (Ex: 99 99999-9999).']);
    exit;
}
if (strlen($cleanPhone) === 10 || strlen($cleanPhone) === 11) {
    $phoneNormalized = '55' . $cleanPhone;
} else {
    $phoneNormalized = $cleanPhone;
}

// 4. Buscar Evento no Banco de Dados
if ($eventId > 0) {
    $stmtEvt = $pdo->prepare("SELECT * FROM eventos WHERE id = ? LIMIT 1");
    $stmtEvt->execute([$eventId]);
} else {
    $stmtEvt = $pdo->prepare("SELECT * FROM eventos WHERE slug = ? LIMIT 1");
    $stmtEvt->execute([$eventSlug]);
}
$evento = $stmtEvt->fetch();

if (!$evento) {
    echo json_encode(['success' => false, 'message' => 'Evento não encontrado.']);
    exit;
}

$eventId = (int)$evento['id'];

try {
    $pdo->beginTransaction();

    // 5. Vincular ou Criar Lead Mestre
    $leadId = null;
    $stmtFindLead = $pdo->prepare("SELECT id FROM leads WHERE email = ? OR phone_normalized = ? LIMIT 1");
    $stmtFindLead->execute([$email, $phoneNormalized]);
    $leadId = $stmtFindLead->fetchColumn();

    $utmSource = trim($postData['utm_source'] ?? 'site-evento');
    $utmMedium = trim($postData['utm_medium'] ?? '');
    $utmCampaign = trim($postData['utm_campaign'] ?? ($evento['slug'] ?? ''));

    if ($leadId) {
        $stmtUpLead = $pdo->prepare("UPDATE leads SET name = ?, phone_original = ?, phone_normalized = ?, last_conversion = NOW() WHERE id = ?");
        $stmtUpLead->execute([$name, $phone, $phoneNormalized, $leadId]);
    } else {
        $stmtInsLead = $pdo->prepare("INSERT INTO leads (name, email, phone_original, phone_normalized, source, utm_source, utm_medium, utm_campaign, first_conversion, last_conversion) VALUES (?, ?, ?, ?, 'site-evento', ?, ?, ?, NOW(), NOW())");
        $stmtInsLead->execute([$name, $email, $phone, $phoneNormalized, $utmSource, $utmMedium, $utmCampaign]);
        $leadId = $pdo->lastInsertId();
    }

    // 6. Verificar se já existe inscrição deste aluno neste evento
    $stmtCheckReg = $pdo->prepare("SELECT id, created_at, modality FROM event_registrations WHERE event_id = ? AND (email = ? OR phone_normalized = ?) LIMIT 1");
    $stmtCheckReg->execute([$eventId, $email, $phoneNormalized]);
    $existingReg = $stmtCheckReg->fetch();

    $siteConfig = get_config($pdo);
    $ispPhone = !empty($siteConfig['phone']) ? $siteConfig['phone'] : '99999999999';
    $msgAlunoZap = "Olá, coordenação do ISP Preparatórios! Me inscrevi no evento *" . $evento['title'] . "* e gostaria de mais informações.";
    $whatsappContactUrl = "https://wa.me/" . preg_replace('/[^0-9]/', '', $ispPhone) . "?text=" . urlencode($msgAlunoZap);

    // Lotes / Link de pagamento se aplicável
    $loteInfo = function_exists('get_active_lote_info') ? get_active_lote_info($pdo, 'evento', $eventId, $evento) : [];
    $paymentLink = '';
    if ($modality === 'presencial' && !empty($loteInfo['link_presencial'])) {
        $paymentLink = $loteInfo['link_presencial'];
    } elseif ($modality === 'online' && !empty($loteInfo['link_online'])) {
        $paymentLink = $loteInfo['link_online'];
    } elseif (!empty($loteInfo['link_geral'])) {
        $paymentLink = $loteInfo['link_geral'];
    } elseif (!empty($evento['payment_link'])) {
        $paymentLink = $evento['payment_link'];
    }

    if ($existingReg) {
        $pdo->commit();
        $regId = (int)$existingReg['id'];
        $protocol = 'EVT-' . str_pad($regId, 6, '0', STR_PAD_LEFT);

        echo json_encode([
            'success' => true,
            'already_registered' => true,
            'registration_id' => $regId,
            'protocol' => $protocol,
            'event_title' => $evento['title'],
            'event_date' => $evento['event_date'],
            'modality' => $existingReg['modality'],
            'payment_link' => $paymentLink,
            'whatsapp_contact_url' => $whatsappContactUrl,
            'message' => 'Você já possui uma inscrição confirmada para este evento!'
        ]);
        exit;
    }

    // 7. Inserir Nova Inscrição
    $stmtInsReg = $pdo->prepare("INSERT INTO event_registrations (event_id, lead_id, name, email, phone, phone_normalized, modality, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'confirmada')");
    $stmtInsReg->execute([$eventId, $leadId, $name, $email, $phone, $phoneNormalized, $modality]);
    $registrationId = (int)$pdo->lastInsertId();

    // 8. Inserir na tabela geral de inscrições / leads do site
    try {
        $stmtInscGeral = $pdo->prepare("INSERT INTO inscricoes (name, email, phone, course_id, message) VALUES (?, ?, ?, NULL, ?)");
        $stmtInscGeral->execute([$name, $email, $phone, "Inscrição no Evento: {$evento['title']} (Modalidade: {$modality})"]);
    } catch (Exception $eInsc) { /* tabela pode ter chave ou regras legadas */ }

    $pdo->commit();

    $protocol = 'EVT-' . str_pad($registrationId, 6, '0', STR_PAD_LEFT);

    // 9. Disparo de Notificação Automática (E-mail & WhatsApp) para o Administrador
    try {
        NotificationService::notifyAdminNewEventRegistration($pdo, [
            'registration_id' => $registrationId,
            'protocol' => $protocol,
            'event_id' => $eventId,
            'event_title' => $evento['title'],
            'event_date' => $evento['event_date'],
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'modality' => $modality,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $eNotif) {
        error_log("Aviso: Falha ao disparar notificação de evento: " . $eNotif->getMessage());
    }

    echo json_encode([
        'success' => true,
        'already_registered' => false,
        'registration_id' => $registrationId,
        'protocol' => $protocol,
        'event_title' => $evento['title'],
        'event_date' => $evento['event_date'],
        'modality' => $modality,
        'payment_link' => $paymentLink,
        'whatsapp_contact_url' => $whatsappContactUrl,
        'message' => 'Sua inscrição no evento foi confirmada com sucesso!'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro na inscrição de evento: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ocorreu um erro ao processar sua inscrição. Por favor, tente novamente.'
    ]);
}
