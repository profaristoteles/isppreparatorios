<?php
/**
 * Worker CLI da Fila de Integrações - ISP Preparatórios
 * 
 * Executado via CRON (ex: * * * * * php /caminho/scripts/process_integration_queue.php).
 * Possui concorrência transacional (FOR UPDATE SKIP LOCKED) e recuperação automática 
 * de tarefas travadas em 'processing' por mais de 10 minutos (Staleness Recovery).
 */

if (php_sapi_name() !== 'cli' && empty($_SERVER['SHELL'])) {
    // Trava de segurança: impede invocação via navegador se não for acionado manualmente com autenticação admin
    require_once __DIR__ . '/../admin/auth.php';
}

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../includes/evocrm_service.php';

$worker_id = 'worker_' . getmypid() . '_' . substr(md5(uniqid()), 0, 6);
echo "[" . date('Y-m-d H:i:s') . "] [$worker_id] Iniciando processamento da fila de integrações...\n";

try {
    // 1. Inicia transação para reservar lote de registros sem colisão entre workers
    $pdo->beginTransaction();

    // Seleciona tarefas pendentes/erro prontas para envio OU tarefas travadas em 'processing' há mais de 10 minutos
    $sqlSelect = "SELECT id, integration, entity_type, entity_id, action, payload, attempts, max_attempts 
                  FROM integration_queue 
                  WHERE (
                      (status IN ('pending', 'error') AND (next_retry_at IS NULL OR next_retry_at <= NOW()) AND attempts < max_attempts)
                      OR (status = 'processing' AND locked_at < NOW() - INTERVAL 10 MINUTE)
                  )
                  LIMIT 10 
                  FOR UPDATE SKIP LOCKED";

    $stmt = $pdo->query($sqlSelect);
    $jobs = $stmt->fetchAll();

    if (empty($jobs)) {
        $pdo->commit();
        echo "[" . date('Y-m-d H:i:s') . "] [$worker_id] Nenhuma tarefa pendente na fila.\n";
        exit(0);
    }

    $jobIds = array_column($jobs, 'id');
    $inClause = implode(',', array_map('intval', $jobIds));

    // Trava os jobs selecionados atualizando para processing e registrando o timestamp e ID do worker
    $sqlUpdateLock = "UPDATE integration_queue 
                      SET status = 'processing', locked_at = NOW(), worker_id = ? 
                      WHERE id IN ($inClause)";
    $stmtLock = $pdo->prepare($sqlUpdateLock);
    $stmtLock->execute([$worker_id]);

    $pdo->commit(); // Libera o bloqueio de tabela/linha mantendo o status 'processing'

    echo "[" . date('Y-m-d H:i:s') . "] [$worker_id] " . count($jobs) . " tarefa(s) reservada(s) para processamento.\n";

    // 2. Processa cada job reservado
    foreach ($jobs as $job) {
        $jobId = (int)$job['id'];
        $integration = $job['integration'];
        $payload = json_decode($job['payload'], true) ?: [];
        $attempts = (int)$job['attempts'] + 1;
        $maxAttempts = (int)$job['max_attempts'];

        echo "[" . date('Y-m-d H:i:s') . "] [$worker_id] Processando Job #$jobId (Integração: $integration, Tentativa: $attempts/$maxAttempts)...\n";

        $result = ['success' => false, 'message' => 'Integração desconhecida'];

        if ($integration === 'evocrm') {
            $result = EvoCRMService::upsertContact($payload);
        } else {
            $result = ['success' => true, 'message' => 'Driver mantido como stub para integrações futuras.'];
        }

        if ($result['success']) {
            // Sincronizado com sucesso
            $stmtDone = $pdo->prepare("UPDATE integration_queue SET status = 'synced', attempts = ?, locked_at = NULL, worker_id = NULL, last_error = NULL WHERE id = ?");
            $stmtDone->execute([$attempts, $jobId]);
            echo "[" . date('Y-m-d H:i:s') . "] [$worker_id] Job #$jobId sincronizado com sucesso!\n";
        } else {
            // Falha: calcular backoff exponencial para próxima tentativa
            $retryMinutes = 5;
            if ($attempts === 2) $retryMinutes = 30;
            if ($attempts === 3) $retryMinutes = 120;
            if ($attempts >= 4) $retryMinutes = 1440; // 24 horas

            $cleanError = preg_replace('/(Bearer|Key|Token|Password)\s+[A-Za-z0-9._-]+/i', '$1 [REDACTED]', $result['message']);
            
            $stmtErr = $pdo->prepare("UPDATE integration_queue SET status = 'error', attempts = ?, locked_at = NULL, worker_id = NULL, last_error = ?, next_retry_at = NOW() + INTERVAL ? MINUTE WHERE id = ?");
            $stmtErr->execute([$attempts, $cleanError, $retryMinutes, $jobId]);

            echo "[" . date('Y-m-d H:i:s') . "] [$worker_id] Job #$jobId falhou: $cleanError (Próxima tentativa em $retryMinutes min).\n";
        }
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "[" . date('Y-m-d H:i:s') . "] [$worker_id] Erro fatal no worker: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] [$worker_id] Processamento concluído.\n";
exit(0);
