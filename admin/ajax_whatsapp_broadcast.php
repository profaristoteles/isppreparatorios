<?php
/**
 * Endpoint AJAX para Disparo em Massa via Evolution API - ISP Preparatórios
 */

require_once 'auth.php';
require_once '../db_config.php';
require_once '../includes/evocrm_service.php';
require_once '../includes/notification_service.php';
require_once 'includes/admin_security.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

$action = trim($_POST['action'] ?? '');

// 1. Obter lista de destinatários filtrados
if ($action === 'fetch_recipients') {
    $targetGroup = trim($_POST['target_group'] ?? 'all_leads');
    $filterId    = (int)($_POST['filter_id'] ?? 0);

    $recipients = [];

    try {
        if ($targetGroup === 'all_leads') {
            // Apenas leads legítimos com vínculo a Aulas Gratuitas
            $stmt = $pdo->query("SELECT DISTINCT l.id, l.name, l.email, l.phone_original, l.phone_normalized 
                                 FROM leads l 
                                 WHERE ((l.phone_normalized IS NOT NULL AND l.phone_normalized != '') OR (l.phone_original IS NOT NULL AND l.phone_original != ''))
                                   AND (
                                       l.source LIKE 'aulas-gratuitas%' 
                                       OR EXISTS (SELECT 1 FROM free_video_events e WHERE e.lead_id = l.id) 
                                       OR EXISTS (SELECT 1 FROM lead_downloads ld WHERE ld.subject_type = 'lead' AND ld.subject_id = l.id) 
                                       OR EXISTS (SELECT 1 FROM lead_consents lc WHERE lc.lead_id = l.id AND lc.source LIKE 'aulas-gratuitas%')
                                   )
                                 ORDER BY l.id DESC");
            $recipients = $stmt->fetchAll();
        } elseif ($targetGroup === 'leads_channel' && $filterId > 0) {
            $stmt = $pdo->prepare("SELECT DISTINCT l.id, l.name, l.email, l.phone_original, l.phone_normalized 
                                   FROM leads l 
                                   JOIN free_video_events e ON e.lead_id = l.id 
                                   JOIN free_videos v ON e.video_id = v.id 
                                   WHERE v.channel_id = ? 
                                     AND ((l.phone_normalized IS NOT NULL AND l.phone_normalized != '') OR (l.phone_original IS NOT NULL AND l.phone_original != ''))
                                   ORDER BY l.id DESC");
            $stmt->execute([$filterId]);
            $recipients = $stmt->fetchAll();
        } elseif ($targetGroup === 'all_reservations') {
            // Alunos que possuem reserva de turma registrada
            $stmt = $pdo->query("SELECT DISTINCT l.id, l.name, l.email, l.phone_original, l.phone_normalized 
                                 FROM reservations r 
                                 JOIN leads l ON r.lead_id = l.id 
                                 WHERE (l.phone_normalized IS NOT NULL AND l.phone_normalized != '') 
                                    OR (l.phone_original IS NOT NULL AND l.phone_original != '') 
                                 ORDER BY l.id DESC");
            $recipients = $stmt->fetchAll();
        } elseif ($targetGroup === 'reservations_campaign' && $filterId > 0) {
            // Alunos de uma campanha de reserva específica
            $stmt = $pdo->prepare("SELECT DISTINCT l.id, l.name, l.email, l.phone_original, l.phone_normalized 
                                   FROM reservations r 
                                   JOIN leads l ON r.lead_id = l.id 
                                   WHERE r.campaign_id = ? 
                                     AND ((l.phone_normalized IS NOT NULL AND l.phone_normalized != '') OR (l.phone_original IS NOT NULL AND l.phone_original != ''))
                                   ORDER BY l.id DESC");
            $stmt->execute([$filterId]);
            $recipients = $stmt->fetchAll();
        } elseif ($targetGroup === 'all_events') {
            $stmt = $pdo->query("SELECT id, name, email, phone as phone_original, phone_normalized 
                                 FROM event_registrations 
                                 WHERE (phone_normalized IS NOT NULL AND phone_normalized != '') 
                                    OR (phone IS NOT NULL AND phone != '') 
                                 ORDER BY id DESC");
            $recipients = $stmt->fetchAll();
        } elseif ($targetGroup === 'all_contacts') {
            // Todos os contatos da base unificada
            $stmt = $pdo->query("SELECT id, name, email, phone_original, phone_normalized 
                                 FROM leads 
                                 WHERE (phone_normalized IS NOT NULL AND phone_normalized != '') 
                                    OR (phone_original IS NOT NULL AND phone_original != '') 
                                 ORDER BY id DESC");
            $recipients = $stmt->fetchAll();
        }
    } catch (\Exception $eQuery) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao consultar contatos: ' . $eQuery->getMessage(),
            'count' => 0,
            'recipients' => []
        ]);
        exit;
    }

    // Normaliza os telefones e remove duplicatas por telefone
    $uniqueRecipients = [];
    $seenPhones = [];

    foreach ($recipients as $r) {
        $phone = preg_replace('/[^0-9]/', '', $r['phone_normalized'] ?: $r['phone_original']);
        if (strlen($phone) === 10 || strlen($phone) === 11) {
            $phone = '55' . $phone;
        }

        if (strlen($phone) >= 12 && !isset($seenPhones[$phone])) {
            $seenPhones[$phone] = true;
            $uniqueRecipients[] = [
                'id'    => $r['id'],
                'name'  => trim($r['name'] ?: 'Aluno'),
                'email' => trim($r['email'] ?: ''),
                'phone' => $phone
            ];
        }
    }

    echo json_encode([
        'success'    => true,
        'count'      => count($uniqueRecipients),
        'recipients' => $uniqueRecipients
    ]);
    exit;
}

// 2. Disparar mensagem individual no loop do cliente
if ($action === 'send_single_message') {
    $phone       = trim($_POST['phone'] ?? '');
    $name        = trim($_POST['name'] ?? 'Aluno');
    $email       = trim($_POST['email'] ?? '');
    $messageTpl  = trim($_POST['message'] ?? '');

    if (empty($phone) || empty($messageTpl)) {
        echo json_encode(['success' => false, 'message' => 'Telefone ou mensagem ausente.']);
        exit;
    }

    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($cleanPhone) === 10 || strlen($cleanPhone) === 11) {
        $cleanPhone = '55' . $cleanPhone;
    }

    // Substituir tags dinâmicas
    $firstName = explode(' ', trim($name))[0] ?? $name;
    $msgBody = str_replace([
        '{nome}',
        '{primeiro_nome}',
        '{email}',
        '{telefone}',
        '{link_site}'
    ], [
        $name,
        $firstName,
        $email,
        $phone,
        'https://isppreparatorios.com.br'
    ], $messageTpl);

    $config = get_config($pdo);

    // Se o provedor for Evolution API no CRM ou nas Notificações
    $cfgCrm = EvoCRMService::getConfig($pdo);
    $apiUrl = $cfgCrm['apiUrl'] ?: ($config['whatsapp_api_url'] ?? '');
    $apiKey = $cfgCrm['apiKey'] ?: ($config['whatsapp_api_key'] ?? '');
    $instance = $cfgCrm['instance'] ?: ($config['whatsapp_api_instance'] ?? 'isp');

    if (empty($apiUrl) || empty($apiKey)) {
        echo json_encode(['success' => false, 'message' => 'Evolution API não configurada. Configure a URL e a API Key em Configurações.']);
        exit;
    }

    $endpoint = rtrim($apiUrl, '/') . '/message/sendText/' . urlencode($instance);
    $body = [
        'number' => $cleanPhone,
        'text' => $msgBody,
        'options' => [
            'delay' => 1200,
            'presence' => 'composing'
        ]
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($body),
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'apikey: ' . $apiKey,
            'Accept: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        echo json_encode(['success' => false, 'message' => 'Erro de conexão: ' . $curlErr]);
        exit;
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        // Registra log opcional no histórico
        try {
            NotificationService::logNotification($pdo, 'whatsapp', 'disparo_massa', $cleanPhone, 'Disparo em Massa', $msgBody, 'sent', "Instância: {$instance}");
        } catch (\Exception $eLog) {}

        echo json_encode(['success' => true, 'message' => 'Mensagem enviada com sucesso!']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => "Evolution API HTTP {$httpCode}: " . substr($response, 0, 150)]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
