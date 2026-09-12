<?php
/**
 * Exportador de Reservas para CSV (UTF-8 com BOM)
 * ISP Preparatórios
 */

require_once 'auth.php';
require_once '../db_config.php';
require_once '../includes/reservation_service.php';

$statusLabels = ReservationService::getReservationStatusLabels();
$modalityLabels = ReservationService::getModalityLabels();

// Filtros
$search = trim($_GET['q'] ?? '');
$campaign_id = !empty($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0;
$modality_filter = trim($_GET['modality'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$crm_status_filter = trim($_GET['crm_status'] ?? '');
$date_start = trim($_GET['date_start'] ?? '');
$date_end = trim($_GET['date_end'] ?? '');
$city_filter = trim($_GET['city'] ?? '');
$waiting_filter = trim($_GET['is_waiting_list'] ?? '');
$follow_up_filter = trim($_GET['follow_up_filter'] ?? '');

$where = " WHERE 1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (l.name LIKE ? OR l.email LIKE ? OR l.phone_original LIKE ? OR l.phone_normalized LIKE ? OR r.city LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($campaign_id > 0) {
    $where .= " AND r.campaign_id = ?";
    $params[] = $campaign_id;
}

if (!empty($modality_filter)) {
    $where .= " AND r.preferred_modality = ?";
    $params[] = $modality_filter;
}

if (!empty($status_filter)) {
    $where .= " AND r.status = ?";
    $params[] = $status_filter;
}

if ($waiting_filter !== '') {
    $where .= " AND r.is_waiting_list = ?";
    $params[] = (int)$waiting_filter;
}

if (!empty($city_filter)) {
    $where .= " AND r.city LIKE ?";
    $params[] = "%$city_filter%";
}

if (!empty($date_start)) {
    $where .= " AND r.created_at >= ?";
    $params[] = $date_start . " 00:00:00";
}

if (!empty($date_end)) {
    $where .= " AND r.created_at <= ?";
    $params[] = $date_end . " 23:59:59";
}

if (!empty($follow_up_filter)) {
    if ($follow_up_filter === 'vencido') {
        $where .= " AND r.next_follow_up_at IS NOT NULL AND r.next_follow_up_at < NOW() AND r.status NOT IN ('matriculado', 'cancelado', 'sem_interesse')";
    } elseif ($follow_up_filter === 'hoje') {
        $where .= " AND DATE(r.next_follow_up_at) = CURDATE()";
    } elseif ($follow_up_filter === '7dias') {
        $where .= " AND r.next_follow_up_at >= NOW() AND r.next_follow_up_at <= NOW() + INTERVAL 7 DAY";
    } elseif ($follow_up_filter === 'sem_acompanhamento') {
        $where .= " AND r.next_follow_up_at IS NULL";
    }
}

if (!empty($crm_status_filter)) {
    if ($crm_status_filter === 'none') {
        $where .= " AND NOT EXISTS (SELECT 1 FROM integration_queue q WHERE q.entity_type = 'reservation' AND q.entity_id = r.id)";
    } else {
        $where .= " AND EXISTS (SELECT 1 FROM integration_queue q WHERE q.entity_type = 'reservation' AND q.entity_id = r.id AND q.status = ?)";
        $params[] = $crm_status_filter;
    }
}

$sql = "SELECT r.*, l.name as lead_name, l.email as lead_email, l.phone_original as lead_phone, l.phone_normalized as lead_phone_normalized,
        c.title as campaign_title,
        (SELECT MAX(h.created_at) FROM reservation_history h WHERE h.reservation_id = r.id AND h.action_type = 'contato_comercial') as last_contact_at,
        (SELECT q.status FROM integration_queue q WHERE q.entity_type = 'reservation' AND q.entity_id = r.id ORDER BY q.id DESC LIMIT 1) as crm_status
        FROM reservations r 
        JOIN leads l ON r.lead_id = l.id 
        JOIN reservation_campaigns c ON r.campaign_id = c.id 
        $where 
        ORDER BY r.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filename = 'reservas_isp_' . date('Ymd_His') . '.csv';

// Limpar buffer de saída
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// Adicionar BOM UTF-8 para correta interpretação de acentos no Excel
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

// Cabeçalhos das colunas
fputcsv($out, [
    'ID Reserva',
    'Nome Completo',
    'WhatsApp / Telefone',
    'Telefone Normalizado',
    'E-mail',
    'Cidade',
    'Estado',
    'Campanha',
    'Modalidade Preferida',
    'Status Comercial',
    'Último Contato',
    'Próximo Acompanhamento',
    'Lista de Espera',
    'Data da Reserva',
    'Origem',
    'UTM Source',
    'UTM Medium',
    'UTM Campaign',
    'UTM Content',
    'UTM Term',
    'Status EvoCRM'
], ';');

/**
 * Sanitiza valores contra CSV / Formula Injection (DDE)
 * Se iniciar com =, +, -, @, tabulação (\t) ou carriage return (\r),
 * prefixa com apóstrofo (') para evitar execução arbitrária de fórmulas no Excel/Calc.
 */
function sanitize_csv_field($val) {
    if ($val === null || $val === '') {
        return '';
    }
    $str = (string)$val;
    $firstChar = $str[0];
    if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $str;
    }
    return $str;
}

foreach ($rows as $row) {
    $lineData = [
        $row['id'],
        $row['lead_name'],
        $row['lead_phone'],
        $row['lead_phone_normalized'],
        $row['lead_email'],
        $row['city'],
        $row['state'],
        $row['campaign_title'],
        $modalityLabels[$row['preferred_modality']] ?? $row['preferred_modality'],
        $statusLabels[$row['status']] ?? $row['status'],
        ReservationService::formatContactDate($row['last_contact_at']),
        !empty($row['next_follow_up_at']) ? date('d/m/Y H:i', strtotime($row['next_follow_up_at'])) : '-',
        $row['is_waiting_list'] ? 'Sim' : 'Não',
        date('d/m/Y H:i:s', strtotime($row['created_at'])),
        $row['source'],
        $row['utm_source'],
        $row['utm_medium'],
        $row['utm_campaign'],
        $row['utm_content'],
        $row['utm_term'],
        $row['crm_status'] ?: 'não enfileirado'
    ];

    fputcsv($out, array_map('sanitize_csv_field', $lineData), ';');
}

fclose($out);
exit;
