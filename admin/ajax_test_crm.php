<?php
/**
 * Endpoint AJAX para Teste de Conexão com CRM / Evolution API - ISP Preparatórios
 */

require_once 'auth.php';
require_once '../db_config.php';
require_once '../includes/evocrm_service.php';
require_once 'includes/admin_security.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

$provider = trim($_POST['provider'] ?? 'evolution');
$apiUrl   = trim($_POST['api_url'] ?? '');
$apiKey   = trim($_POST['api_key'] ?? '');
$instance = trim($_POST['instance'] ?? 'isp');

// Se os campos vierem vazios, usa a configuração salva no banco
if (empty($apiUrl) || empty($apiKey)) {
    $cfg = EvoCRMService::getConfig($pdo);
    if (empty($apiUrl)) $apiUrl = $cfg['apiUrl'];
    if (empty($apiKey)) $apiKey = $cfg['apiKey'];
    if (empty($instance)) $instance = $cfg['instance'];
}

$customConfig = [
    'provider' => $provider,
    'apiUrl'   => $apiUrl,
    'apiKey'   => $apiKey,
    'instance' => $instance
];

$res = EvoCRMService::testConnection($customConfig, $pdo);
echo json_encode($res);
exit;
