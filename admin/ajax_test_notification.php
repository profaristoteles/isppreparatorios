<?php
/**
 * Endpoint AJAX para Teste de Envio de Notificações - ISP Preparatórios
 * 
 * Permite ao administrador disparar testes de e-mail e WhatsApp em tempo real.
 */

require_once 'auth.php';
require_once '../db_config.php';
require_once '../includes/notification_service.php';
require_once 'includes/admin_security.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

$action = trim($_POST['action'] ?? '');
$target = trim($_POST['target'] ?? '');

if ($action === 'test_email') {
    if (empty($target) || !filter_var($target, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Por favor, informe um endereço de e-mail válido para teste.']);
        exit;
    }

    $res = NotificationService::testEmailConnection($pdo, $target);
    echo json_encode($res);
    exit;
}

if ($action === 'test_whatsapp') {
    $cleanPhone = preg_replace('/[^0-9]/', '', $target);
    if (strlen($cleanPhone) < 10) {
        echo json_encode(['success' => false, 'message' => 'Por favor, informe um número de WhatsApp válido com DDD para teste.']);
        exit;
    }

    $res = NotificationService::testWhatsAppConnection($pdo, $cleanPhone);
    echo json_encode($res);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Ação de teste não reconhecida.']);
