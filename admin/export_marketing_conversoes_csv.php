<?php
/**
 * Exportação de Log de Conversões de Marketing para CSV
 * ISP Preparatórios
 */

require_once 'auth.php';
require_once '../db_config.php';

// Filtros
$filterEvent = trim($_GET['event'] ?? '');
$filterCampaign = trim($_GET['campaign'] ?? '');
$filterDate = trim($_GET['date'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($filterEvent)) {
    $where[] = "c.event_name = ?";
    $params[] = $filterEvent;
}
if (!empty($filterCampaign)) {
    $where[] = "(c.campaign_slug = ? OR m.slug = ?)";
    $params[] = $filterCampaign;
    $params[] = $filterCampaign;
}
if (!empty($filterDate)) {
    $where[] = "DATE(c.created_at) = ?";
    $params[] = $filterDate;
}
if (!empty($filterStatus)) {
    $where[] = "c.status = ?";
    $params[] = $filterStatus;
}

$whereSql = implode(" AND ", $where);

$query = "SELECT c.*, m.name AS campaign_name 
    FROM marketing_conversions c 
    LEFT JOIN marketing_campaigns m ON c.campaign_id = m.id 
    WHERE {$whereSql} 
    ORDER BY c.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);

$filename = "conversoes_marketing_isp_" . date('Y-m-d_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// UTF-8 BOM para correta abertura no Microsoft Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Cabeçalhos do CSV
fputcsv($output, [
    'ID',
    'Data/Hora',
    'Evento Meta',
    'Campanha',
    'Slug Campanha',
    'Tipo Entidade',
    'ID Entidade (Reserva/Lead)',
    'URL da Página',
    'Referrer',
    'UTM Source',
    'UTM Medium',
    'UTM Campaign',
    'UTM Content',
    'UTM Term',
    'Status',
    'Event ID (Deduplicação CAPI)'
], ';');

while ($row = $stmt->fetch()) {
    fputcsv($output, [
        $row['id'],
        $row['created_at'],
        $row['event_name'],
        $row['campaign_name'] ?: $row['campaign_slug'],
        $row['campaign_slug'],
        $row['entity_type'],
        $row['entity_id'],
        $row['page_url'],
        $row['referrer'],
        $row['utm_source'],
        $row['utm_medium'],
        $row['utm_campaign'],
        $row['utm_content'],
        $row['utm_term'],
        $row['status'],
        $row['event_id']
    ], ';');
}

fclose($output);
exit;
