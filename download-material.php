<?php
/**
 * Rota Controlada de Download Seguro de Material PDF
 * 
 * Valida a assinatura HMAC do token, registra o histórico do download
 * e realiza o streaming binário do PDF armazenado na pasta protegida.
 */

require_once __DIR__ . '/includes/aulas_gratuitas_utils.php';

$token = $_GET['token'] ?? '';
$payload = verify_download_token($token);

if (!$payload) {
    http_response_code(403);
    die("<h1>403 - Acesso Negado ou Token Expirado</h1><p>O link de download fornecido é inválido ou expirou. Por favor, solicite o material novamente na página da aula.</p>");
}

$subject_type = $payload['subject_type'];
$subject_id = $payload['subject_id'];
$material_id = $payload['material_id'];
$video_id = $payload['video_id'];

// Validação estrita do subject_type (Permitidos somente 'lead' ou 'conflict')
if (!in_array($subject_type, ['lead', 'conflict'], true)) {
    http_response_code(403);
    die("<h1>403 - Acesso Negado</h1><p>Identificador de sujeito inválido.</p>");
}

// Buscar e validar registro do material e da videoaula publicada no banco de dados
$stmtMat = $pdo->prepare("SELECT m.* 
                         FROM free_materials m 
                         JOIN free_videos v ON m.video_id = v.id 
                         WHERE m.id = ? AND m.video_id = ? AND m.active = 1 AND m.deleted_at IS NULL AND v.status = 'publicado' AND v.active = 1 AND v.deleted_at IS NULL 
                         LIMIT 1");
$stmtMat->execute([(int)$material_id, (int)$video_id]);
$material = $stmtMat->fetch();

if (!$material) {
    http_response_code(404);
    die("<h1>404 - Material Não Encontrado</h1><p>O arquivo solicitado não está disponível ou a aula não foi publicada.</p>");
}

// Caminho absoluto para a pasta protegida e prevenção contra Path Traversal
$protected_dir = __DIR__ . '/uploads/materiais_protegidos/';
$file_path = $protected_dir . basename($material['file_path']);

if (!file_exists($file_path) || !is_file($file_path)) {
    // Fallback: Tentar localização padrão uploads se não estiver em materiais_protegidos
    $fallback_path = __DIR__ . '/uploads/' . basename($material['file_path']);
    if (file_exists($fallback_path) && is_file($fallback_path)) {
        $file_path = $fallback_path;
    } else {
        http_response_code(404);
        die("<h1>404 - Arquivo Ausente</h1><p>O arquivo PDF não foi localizado no servidor físico.</p>");
    }
}

// Validação de segurança do realpath (Confinamento de diretório)
$realPath = realpath($file_path);
$realProtectedDir = realpath($protected_dir);
$realUploadsDir = realpath(__DIR__ . '/uploads');

if (!$realPath || ($realProtectedDir && strpos($realPath, $realProtectedDir) !== 0 && strpos($realPath, $realUploadsDir) !== 0)) {
    http_response_code(403);
    die("<h1>403 - Acesso Negado</h1><p>Caminho de arquivo inválido.</p>");
}

// 1. Registrar o download no histórico de downloads (lead_downloads)
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

$stmtDL = $pdo->prepare("INSERT INTO lead_downloads (subject_type, subject_id, material_id, video_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
$stmtDL->execute([$subject_type, $subject_id, $material_id, $video_id, $ip, $ua]);

// 2. Registrar evento analítico 'material_downloaded' em free_video_events
// Se for conflict, lead_id é gravado como NULL (não atribui arbitrariamente a Lead A nem B)
$lead_id_event = ($subject_type === 'lead') ? $subject_id : null;
log_free_video_event($pdo, $video_id, 'material_downloaded', $lead_id_event, [
    'material_id' => $material_id,
    'subject_type' => $subject_type,
    'subject_id' => $subject_id
]);

// Configurar cabeçalhos para download seguro e binário do PDF
$mime = !empty($material['mime_type']) ? $material['mime_type'] : 'application/pdf';
$filename = !empty($material['original_filename']) ? $material['original_filename'] : basename($file_path);

// Limpar buffers de saída prévios
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($file_path));

readfile($file_path);
exit;
