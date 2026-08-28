<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';

// Migration incremental não-destrutiva para colunas de auditoria de conflitos se ausentes
try {
    $cols = $pdo->query("SHOW COLUMNS FROM lead_conflicts LIKE 'resolved_by'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE lead_conflicts ADD COLUMN resolved_by VARCHAR(100) DEFAULT NULL AFTER status");
        $pdo->exec("ALTER TABLE lead_conflicts ADD COLUMN resolution_type VARCHAR(50) DEFAULT NULL AFTER resolved_by");
    }
} catch (Exception $e) { /* tabela ok */ }

$filterStatus = $_GET['status'] ?? 'pending';

$query = "SELECT c.*, 
          lEmail.name as email_lead_name, lEmail.email as email_lead_email, 
          lPhone.name as phone_lead_name, lPhone.phone_original as phone_lead_phone 
          FROM lead_conflicts c 
          LEFT JOIN leads lEmail ON c.existing_lead_id_email = lEmail.id 
          LEFT JOIN leads lPhone ON c.existing_lead_id_phone = lPhone.id 
          WHERE 1=1";
$params = [];

if ($filterStatus === 'pending') {
    $query .= " AND c.status = 'pending'";
} elseif ($filterStatus === 'resolved') {
    $query .= " AND c.status = 'resolved'";
} elseif ($filterStatus === 'ignored') {
    $query .= " AND c.status = 'ignored'";
}

$query .= " ORDER BY c.id DESC";
$conflicts = $pdo->prepare($query);
$conflicts->execute($params);
$conflictsList = $conflicts->fetchAll();

require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h2><i class="fas fa-exclamation-triangle text-warning"></i> Conflitos de Identidade de Leads</h2>
</div>

<!-- Filtros de Status -->
<div class="card">
    <div style="display: flex; gap: 0.5rem;">
        <a href="conflitos-leads.php?status=pending" class="btn <?= $filterStatus === 'pending' ? 'btn-warning' : 'btn-secondary' ?> btn-sm">
            <i class="fas fa-clock"></i> Pendentes
        </a>
        <a href="conflitos-leads.php?status=resolved" class="btn <?= $filterStatus === 'resolved' ? 'btn-success' : 'btn-secondary' ?> btn-sm">
            <i class="fas fa-check-circle"></i> Resolvidos
        </a>
        <a href="conflitos-leads.php?status=ignored" class="btn <?= $filterStatus === 'ignored' ? 'btn-secondary' : 'btn-secondary' ?> btn-sm">
            <i class="fas fa-ban"></i> Ignorados
        </a>
        <a href="conflitos-leads.php?status=all" class="btn <?= $filterStatus === 'all' ? 'btn-secondary' : 'btn-secondary' ?> btn-sm">
            <i class="fas fa-list"></i> Todos
        </a>
    </div>
</div>

<!-- Tabela de Conflitos -->
<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Dados Informados</th>
                <th>Lead Encontrado (E-mail)</th>
                <th>Lead Encontrado (Telefone)</th>
                <th>Status</th>
                <th style="width: 100px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($conflictsList)): ?>
                <tr><td colspan="7" style="text-align: center; color: #888;">Nenhum conflito registrado nesta categoria.</td></tr>
            <?php else: foreach ($conflictsList as $c): ?>
                <tr>
                    <td>#<?= $c['id'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($c['incoming_name']) ?></strong><br>
                        <small style="color: #666;"><?= htmlspecialchars($c['incoming_email']) ?></small><br>
                        <small style="color: #666;"><i class="fab fa-whatsapp text-success"></i> <?= htmlspecialchars($c['incoming_phone_normalized']) ?></small>
                    </td>
                    <td>
                        <?php if ($c['email_lead_name']): ?>
                            <strong><?= htmlspecialchars($c['email_lead_name']) ?></strong> (#<?= $c['existing_lead_id_email'] ?>)<br>
                            <small style="color: #666;"><?= htmlspecialchars($c['email_lead_email']) ?></small>
                        <?php else: ?>
                            <span style="color: #aaa;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($c['phone_lead_name']): ?>
                            <strong><?= htmlspecialchars($c['phone_lead_name']) ?></strong> (#<?= $c['existing_lead_id_phone'] ?>)<br>
                            <small style="color: #666;"><?= htmlspecialchars($c['phone_lead_phone']) ?></small>
                        <?php else: ?>
                            <span style="color: #aaa;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($c['status'] === 'pending'): ?>
                            <span class="badge badge-warning">Pendente</span>
                        <?php elseif ($c['status'] === 'resolved'): ?>
                            <span class="badge badge-success">Resolvido</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Ignorado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="conflito-detalhes.php?id=<?= $c['id'] ?>" class="btn btn-sm <?= $c['status'] === 'pending' ? 'btn-warning' : 'btn-secondary' ?>" title="Analisar Conflito">
                            <i class="fas fa-search"></i> Analisar
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
