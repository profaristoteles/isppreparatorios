<?php
require_once 'includes/pci_mcp_client.php';

header('Content-Type: application/json; charset=utf-8');

$uf = isset($_GET['uf']) ? strtoupper(trim($_GET['uf'])) : 'MA';
$termo = isset($_GET['termo']) ? trim($_GET['termo']) : (isset($_GET['q']) ? trim($_GET['q']) : '');
$cidade = isset($_GET['cidade']) ? trim($_GET['cidade']) : '';
$professoresOnly = !empty($_GET['professores']);

$response = [
    'success' => true,
    'uf' => $uf,
    'termo' => $termo,
    'cidade' => $cidade,
    'total' => 0,
    'data' => []
];

try {
    if (!empty($cidade)) {
        $result = PciMcpClient::buscarPorCidade($cidade);
    } elseif (!empty($termo)) {
        $result = PciMcpClient::pesquisarConcursos($termo, $uf);
    } elseif ($professoresOnly) {
        $result = PciMcpClient::buscarPorCargo('professor', $uf);
    } else {
        $result = PciMcpClient::buscarPorCargo('professor', $uf);
        if (empty($result['data'])) {
            $result = PciMcpClient::pesquisarConcursos('concurso', $uf);
        }
    }

    $response['total'] = $result['meta']['total'] ?? count($result['data'] ?? []);
    $response['data'] = $result['data'] ?? [];
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
