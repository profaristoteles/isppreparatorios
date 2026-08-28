<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';

// Reenviar Agora (recoloca o job na fila para o worker CLI)
if (isset($_GET['retry'])) {
    verify_csrf_token();
    $jobId = (int)$_GET['retry'];
    $stmt = $pdo->prepare("UPDATE integration_queue SET status = 'pending', next_retry_at = NOW(), locked_at = NULL, worker_id = NULL WHERE id = ?");
    $stmt->execute([$jobId]);
    $_SESSION['msg'] = "Job #$jobId recolocado na fila com status PENDENTE para processamento pelo worker CLI.";
    header("Location: fila-integracoes.php");
    exit;
}

// Liberar Job Preso em 'processing'
if (isset($_GET['unlock'])) {
    verify_csrf_token();
    $jobId = (int)$_GET['unlock'];
    $stmt = $pdo->prepare("UPDATE integration_queue SET status = 'pending', locked_at = NULL, worker_id = NULL WHERE id = ?");
    $stmt->execute([$jobId]);
    $_SESSION['msg'] = "Trava do Job #$jobId liberada com sucesso.";
    header("Location: fila-integracoes.php");
    exit;
}

// Filtros e Paginação
$filterStatus = $_GET['status'] ?? '';
$filterIntegration = $_GET['integration'] ?? '';

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = " WHERE 1=1";
$params = [];

if (!empty($filterStatus)) {
    $where .= " AND status = ?";
    $params[] = $filterStatus;
}

if (!empty($filterIntegration)) {
    $where .= " AND integration = ?";
    $params[] = $filterIntegration;
}

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM integration_queue $where");
$stmtCount->execute($params);
$totalJobs = $stmtCount->fetchColumn();
$totalPages = ceil($totalJobs / $limit);

$sql = "SELECT * FROM integration_queue $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobsList = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h2><i class="fas fa-sync"></i> Fila Assíncrona de Integrações</h2>
</div>

<!-- Cards Sintéticos -->
<div class="grid-cards" style="margin-bottom: 1.5rem;">
    <?php
        $stPending = $pdo->query("SELECT COUNT(*) FROM integration_queue WHERE status='pending'")->fetchColumn();
        $stProc = $pdo->query("SELECT COUNT(*) FROM integration_queue WHERE status='processing'")->fetchColumn();
        $stSynced = $pdo->query("SELECT COUNT(*) FROM integration_queue WHERE status='synced'")->fetchColumn();
        $stError = $pdo->query("SELECT COUNT(*) FROM integration_queue WHERE status='error'")->fetchColumn();
    ?>
    <div class="stat-card" style="border-left-color: #ffc107;">
        <h3>Pendentes</h3>
        <p class="val"><?= number_format($stPending) ?></p>
    </div>
    <div class="stat-card" style="border-left-color: #17a2b8;">
        <h3>Processando</h3>
        <p class="val"><?= number_format($stProc) ?></p>
    </div>
    <div class="stat-card" style="border-left-color: #28a745;">
        <h3>Sincronizados</h3>
        <p class="val"><?= number_format($stSynced) ?></p>
    </div>
    <div class="stat-card" style="border-left-color: #dc3545;">
        <h3>Com Erro</h3>
        <p class="val"><?= number_format($stError) ?></p>
    </div>
</div>

<!-- Filtros -->
<div class="card">
    <form method="GET" style="display: flex; gap: 1rem; align-items: center;">
        <div style="display: flex; gap: 0.5rem; flex: 1;">
            <select name="status" class="form-control" style="width: 180px;">
                <option value="">Todos os status</option>
                <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pendente</option>
                <option value="processing" <?= $filterStatus === 'processing' ? 'selected' : '' ?>>Processando</option>
                <option value="synced" <?= $filterStatus === 'synced' ? 'selected' : '' ?>>Sincronizado</option>
                <option value="error" <?= $filterStatus === 'error' ? 'selected' : '' ?>>Erro</option>
            </select>

            <select name="integration" class="form-control" style="width: 180px;">
                <option value="">Todas integrações</option>
                <option value="evocrm" <?= $filterIntegration === 'evocrm' ? 'selected' : '' ?>>EvoCRM</option>
                <option value="mautic" <?= $filterIntegration === 'mautic' ? 'selected' : '' ?>>Mautic</option>
            </select>

            <button type="submit" class="btn btn-sm"><i class="fas fa-filter"></i> Filtrar</button>
            <a href="fila-integracoes.php" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i> Limpar</a>
        </div>
    </form>
</div>

<!-- Tabela de Jobs -->
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Integração</th>
                <th>Ação / Entidade</th>
                <th>Status</th>
                <th>Tentativas</th>
                <th>Próxima Tentativa</th>
                <th>Último Erro (Sanitizado)</th>
                <th>Atualizado em</th>
                <th style="width: 130px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($jobsList)): ?>
                <tr><td colspan="9" style="text-align: center; color: #888;">Nenhum job encontrado na fila.</td></tr>
            <?php else: foreach ($jobsList as $j): ?>
                <?php
                    $isStuck = ($j['status'] === 'processing' && !empty($j['locked_at']) && (time() - strtotime($j['locked_at'])) > 600);
                ?>
                <tr>
                    <td>#<?= $j['id'] ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars(strtoupper($j['integration'])) ?></span></td>
                    <td>
                        <strong><?= htmlspecialchars($j['action']) ?></strong><br>
                        <small style="color: #666;"><?= htmlspecialchars($j['entity_type']) ?> #<?= $j['entity_id'] ?></small>
                    </td>
                    <td>
                        <?php if ($j['status'] === 'synced'): ?>
                            <span class="badge badge-success">Sincronizado</span>
                        <?php elseif ($j['status'] === 'error'): ?>
                            <span class="badge badge-danger">Erro</span>
                        <?php elseif ($j['status'] === 'processing'): ?>
                            <span class="badge badge-info">Processando</span>
                            <?php if ($isStuck): ?>
                                <span class="badge badge-danger" title="Preso há mais de 10 min">Stuck!</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-warning">Pendente</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $j['attempts'] ?> / <?= $j['max_attempts'] ?></td>
                    <td><?= $j['next_retry_at'] ? date('d/m/Y H:i', strtotime($j['next_retry_at'])) : '-' ?></td>
                    <td>
                        <?php if ($j['last_error']): ?>
                            <small style="color: #dc3545; word-break: break-all;"><?= htmlspecialchars(mb_strimwidth($j['last_error'], 0, 100, '...')) ?></small>
                        <?php else: ?>
                            <span style="color: #ccc;">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($j['updated_at'])) ?></td>
                    <td>
                        <?php if ($j['status'] === 'error' || $j['status'] === 'pending'): ?>
                            <a href="fila-integracoes.php?retry=<?= $j['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-warning btn-sm" title="Reenviar Agora (Worker CLI)">
                                <i class="fas fa-redo"></i> Reenviar
                            </a>
                        <?php endif; ?>
                        <?php if ($isStuck): ?>
                            <a href="fila-integracoes.php?unlock=<?= $j['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-danger btn-sm" title="Liberar Job Preso" onclick="return confirm('Deseja liberar a trava deste job?')">
                                <i class="fas fa-unlock"></i> Liberar
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- Paginação -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php
                    $paramsGET = $_GET;
                    $paramsGET['page'] = $i;
                    $url = 'fila-integracoes.php?' . http_build_query($paramsGET);
                ?>
                <a href="<?= $url ?>" class="<?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
