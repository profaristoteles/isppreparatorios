<?php
/**
 * Endpoint AJAX para Sincronizar Fila de Integrações sob Demanda - ISP Preparatórios
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

$worker_id = 'admin_' . ($_SESSION['admin_id'] ?? 1) . '_' . substr(md5(uniqid()), 0, 4);

try {
    $pdo->beginTransaction();

    // Seleciona até 50 jobs pendentes ou com erro prontos para sincronização
    $sqlSelect = "SELECT id, integration, entity_type, entity_id, action, payload, attempts, max_attempts 
                  FROM integration_queue 
                  WHERE (
                      (status IN ('pending', 'error') AND (next_retry_at IS NULL OR next_retry_at <= NOW()))
                      OR (status = 'processing' AND locked_at < NOW() - INTERVAL 10 MINUTE)
                  )
                  ORDER BY id ASC
                  LIMIT 50 
                  FOR UPDATE SKIP LOCKED";

    $stmt = $pdo->query($sqlSelect);
    $jobs = $stmt->fetchAll();

    if (empty($jobs)) {
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'processed' => 0,
            'message' => 'Nenhuma tarefa pendente na fila para sincronização.'
        ]);
        exit;
    }

    $jobIds = array_column($jobs, 'id');
    $inClause = implode(',', array_map('intval', $jobIds));

    $sqlUpdateLock = "UPDATE integration_queue 
                      SET status = 'processing', locked_at = NOW(), worker_id = ? 
                      WHERE id IN ($inClause)";
    $stmtLock = $pdo->prepare($sqlUpdateLock);
    $stmtLock->execute([$worker_id]);

    $pdo->commit();

    $syncedCount = 0;
    $errorCount  = 0;
    $errors = [];

    foreach ($jobs as $job) {
        $jobId       = (int)$job['id'];
        $integration = $job['integration'];
        $entityType  = $job['entity_type'] ?? '';
        $action      = $job['action'] ?? '';
        $payload     = json_decode($job['payload'], true) ?: [];
        $attempts    = (int)$job['attempts'] + 1;
        $maxAttempts = (int)$job['max_attempts'];

        $result = ['success' => false, 'message' => 'Integração desconhecida'];

        if ($integration === 'evocrm') {
            if ($action === 'upsert_contact') {
                $result = EvoCRMService::upsertContact($payload, $pdo);
            } else {
                $result = ['success' => false, 'message' => "Ação não suportada: {$action}"];
            }
        } else {
            $result = ['success' => true, 'message' => 'Simulado'];
        }

        if ($result['success']) {
            $stmtDone = $pdo->prepare("UPDATE integration_queue SET status = 'synced', attempts = ?, locked_at = NULL, worker_id = NULL, last_error = NULL WHERE id = ?");
            $stmtDone->execute([$attempts, $jobId]);
            $syncedCount++;

            if ($entityType === 'reservation') {
                try {
                    $stmtHist = $pdo->prepare("INSERT INTO reservation_history (reservation_id, admin_id, action_type, old_status, new_status, description) VALUES (?, ?, 'envio_crm', NULL, NULL, 'Sincronizado com CRM / Evolution API via Painel Admin.')");
                    $stmtHist->execute([(int)$job['entity_id'], $_SESSION['admin_id'] ?? null]);
                } catch (\Exception $eHist) {}
            }
        } else {
            $cleanError = preg_replace('/(Bearer|Key|Token|Password)\s+[A-Za-z0-9._-]+/i', '$1 [REDACTED]', $result['message']);
            $stmtErr = $pdo->prepare("UPDATE integration_queue SET status = 'error', attempts = ?, locked_at = NULL, worker_id = NULL, last_error = ?, next_retry_at = NOW() + INTERVAL 5 MINUTE WHERE id = ?");
            $stmtErr->execute([$attempts, $cleanError, $jobId]);
            $errorCount++;
            $errors[] = "#{$jobId}: " . substr($cleanError, 0, 80);
        }
    }

    echo json_encode([
        'success'   => true,
        'processed' => count($jobs),
        'synced'    => $syncedCount,
        'errors'    => $errorCount,
        'details'   => $errors,
        'message'   => "Processamento concluído: {$syncedCount} sincronizado(s) com sucesso" . ($errorCount > 0 ? ", {$errorCount} falha(s)." : ".")
    ]);
    exit;

} catch (\Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Erro ao processar fila: ' . $e->getMessage()]);
    exit;
}
