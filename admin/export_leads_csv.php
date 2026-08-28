<?php
/**
 * Gerador de Exportação CSV UTF-8 para Mautic
 */
require_once 'auth.php';
require_once '../db_config.php';

// Filtros
$search = trim($_GET['q'] ?? '');
$channel_id = !empty($_GET['channel_id']) ? (int)$_GET['channel_id'] : 0;
$video_id = !empty($_GET['video_id']) ? (int)$_GET['video_id'] : 0;
$teacher_id = !empty($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
$discipline_id = !empty($_GET['discipline_id']) ? (int)$_GET['discipline_id'] : 0;
$board_id = !empty($_GET['board_id']) ? (int)$_GET['board_id'] : 0;
$contest_id = !empty($_GET['contest_id']) ? (int)$_GET['contest_id'] : 0;
$tag_filter = trim($_GET['tag'] ?? '');
$date_start = !empty($_GET['date_start']) ? $_GET['date_start'] : '';
$date_end = !empty($_GET['date_end']) ? $_GET['date_end'] : '';

$where = " WHERE 1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (l.name LIKE ? OR l.email LIKE ? OR l.phone_original LIKE ? OR l.phone_normalized LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($date_start)) {
    $where .= " AND l.created_at >= ?";
    $params[] = $date_start . " 00:00:00";
}

if (!empty($date_end)) {
    $where .= " AND l.created_at <= ?";
    $params[] = $date_end . " 23:59:59";
}

if (!empty($tag_filter)) {
    $where .= " AND l.tags_cache LIKE ?";
    $params[] = "%$tag_filter%";
}

// Filtros baseados em eventos/aulas
if ($channel_id || $video_id || $teacher_id || $discipline_id || $board_id || $contest_id) {
    $where .= " AND l.id IN (SELECT DISTINCT e.lead_id FROM free_video_events e JOIN free_videos v ON e.video_id = v.id WHERE e.lead_id IS NOT NULL";
    if ($video_id) { $where .= " AND v.id = ?"; $params[] = $video_id; }
    if ($channel_id) { $where .= " AND v.channel_id = ?"; $params[] = $channel_id; }
    if ($teacher_id) { $where .= " AND v.teacher_id = ?"; $params[] = $teacher_id; }
    if ($discipline_id) { $where .= " AND v.discipline_id = ?"; $params[] = $discipline_id; }
    if ($board_id) { $where .= " AND v.board_id = ?"; $params[] = $board_id; }
    if ($contest_id) { $where .= " AND v.contest_id = ?"; $params[] = $contest_id; }
    $where .= ")";
}

$sql = "SELECT l.* FROM leads l $where ORDER BY l.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

$filename = 'export_leads_mautic_' . date('Ymd_His') . '.csv';

// Limpar buffer de saída prévio
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Grava o BOM UTF-8 (\xEF\xBB\xBF) para garantir abertura correta no Excel e Mautic
fputs($output, "\xEF\xBB\xBF");

// Cabeçalhos CSV no padrão Mautic
fputcsv($output, [
    'firstname',
    'lastname',
    'full_name',
    'email',
    'mobile',
    'phone',
    'source',
    'tags',
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_content',
    'utm_term',
    'video',
    'channel',
    'discipline',
    'teacher',
    'exam_board',
    'contest',
    'created_at'
]);

foreach ($leads as $lead) {
    // Divisão do nome em firstname / lastname
    $nameParts = explode(' ', trim($lead['name']), 2);
    $firstname = $nameParts[0] ?? '';
    $lastname = $nameParts[1] ?? '';
    
    // Obter contexto da última aula acessada pelo lead
    $stmtLastV = $pdo->prepare("SELECT v.title as video_title, c.name as channel_name, d.name as discipline_name, t.name as teacher_name, b.name as board_name, ct.name as contest_name FROM free_video_events e JOIN free_videos v ON e.video_id = v.id JOIN free_channels c ON v.channel_id = c.id LEFT JOIN free_disciplines d ON v.discipline_id = d.id LEFT JOIN free_teachers t ON v.teacher_id = t.id LEFT JOIN free_boards b ON v.board_id = b.id LEFT JOIN free_contests ct ON v.contest_id = ct.id WHERE e.lead_id = ? ORDER BY e.id DESC LIMIT 1");
    $stmtLastV->execute([$lead['id']]);
    $ctx = $stmtLastV->fetch() ?: [];

    fputcsv($output, [
        $firstname,
        $lastname,
        $lead['name'],
        $lead['email'],
        $lead['phone_normalized'] ?: $lead['phone_original'],
        $lead['phone_original'],
        $lead['source'],
        $lead['tags_cache'],
        $lead['utm_source'],
        $lead['utm_medium'],
        $lead['utm_campaign'],
        $lead['utm_content'],
        $lead['utm_term'],
        $ctx['video_title'] ?? '',
        $ctx['channel_name'] ?? '',
        $ctx['discipline_name'] ?? '',
        $ctx['teacher_name'] ?? '',
        $ctx['board_name'] ?? '',
        $ctx['contest_name'] ?? '',
        $lead['created_at']
    ]);
}

fclose($output);
exit;
