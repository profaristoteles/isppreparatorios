<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';

$conflict_id = (int)($_GET['id'] ?? 0);
if (!$conflict_id) {
    header("Location: conflitos-leads.php");
    exit;
}

// Carregar Registro de Conflito
$stmtC = $pdo->prepare("SELECT * FROM lead_conflicts WHERE id = ? LIMIT 1");
$stmtC->execute([$conflict_id]);
$conflict = $stmtC->fetch();

if (!$conflict) {
    $_SESSION['erro'] = "Registro de conflito não localizado.";
    header("Location: conflitos-leads.php");
    exit;
}

// Carregar Lead A (Identificado por E-mail)
$leadEmail = null;
if ($conflict['existing_lead_id_email']) {
    $stmtE = $pdo->prepare("SELECT * FROM leads WHERE id = ? LIMIT 1");
    $stmtE->execute([$conflict['existing_lead_id_email']]);
    $leadEmail = $stmtE->fetch();
}

// Carregar Lead B (Identificado por Telefone)
$leadPhone = null;
if ($conflict['existing_lead_id_phone']) {
    $stmtP = $pdo->prepare("SELECT * FROM leads WHERE id = ? LIMIT 1");
    $stmtP->execute([$conflict['existing_lead_id_phone']]);
    $leadPhone = $stmtP->fetch();
}

// Resolução de Conflito
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    $action = $_POST['resolution_action'] ?? '';
    $admin_name = $_SESSION['admin_name'] ?? 'Administrador';
    
    try {
        $pdo->beginTransaction();
        
        if ($action === 'associate_email' && $leadEmail) {
            $targetLeadId = $leadEmail['id'];
            
            // Reatribui downloads temporários do conflito para o Lead A
            $pdo->prepare("UPDATE lead_downloads SET subject_type = 'lead', subject_id = ? WHERE subject_type = 'conflict' AND subject_id = ?")->execute([$targetLeadId, $conflict_id]);
            
            // Atualiza status do conflito
            $stmtRes = $pdo->prepare("UPDATE lead_conflicts SET status = 'resolved', resolved_at = NOW(), resolved_by = ?, resolution_type = 'associate_email', resolved_lead_id = ? WHERE id = ?");
            $stmtRes->execute([$admin_name, $targetLeadId, $conflict_id]);
            
            $pdo->commit();
            $_SESSION['msg'] = "Conflito resolvido: Conversão associada com sucesso ao Lead do E-mail (#$targetLeadId).";
            header("Location: conflitos-leads.php");
            exit;
            
        } elseif ($action === 'associate_phone' && $leadPhone) {
            $targetLeadId = $leadPhone['id'];
            
            // Reatribui downloads temporários do conflito para o Lead B
            $pdo->prepare("UPDATE lead_downloads SET subject_type = 'lead', subject_id = ? WHERE subject_type = 'conflict' AND subject_id = ?")->execute([$targetLeadId, $conflict_id]);
            
            // Atualiza status do conflito
            $stmtRes = $pdo->prepare("UPDATE lead_conflicts SET status = 'resolved', resolved_at = NOW(), resolved_by = ?, resolution_type = 'associate_phone', resolved_lead_id = ? WHERE id = ?");
            $stmtRes->execute([$admin_name, $targetLeadId, $conflict_id]);
            
            $pdo->commit();
            $_SESSION['msg'] = "Conflito resolvido: Conversão associada com sucesso ao Lead do Telefone (#$targetLeadId).";
            header("Location: conflitos-leads.php");
            exit;
            
        } elseif ($action === 'ignore') {
            $stmtRes = $pdo->prepare("UPDATE lead_conflicts SET status = 'ignored', resolved_at = NOW(), resolved_by = ?, resolution_type = 'ignored' WHERE id = ?");
            $stmtRes->execute([$admin_name, $conflict_id]);
            
            $pdo->commit();
            $_SESSION['msg'] = "Conflito marcado como Ignorado.";
            header("Location: conflitos-leads.php");
            exit;
        } else {
            $pdo->rollBack();
            $_SESSION['erro'] = "Ação de resolução inválida ou incompatível.";
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['erro'] = "Erro ao resolver conflito: " . $e->getMessage();
    }
}

$payloadData = json_decode($conflict['payload'], true) ?: [];

require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h2><i class="fas fa-search"></i> Análise de Conflito de Identidade #<?= $conflict['id'] ?></h2>
    <a href="conflitos-leads.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar para Lista</a>
</div>

<!-- Comparativo em 3 Colunas -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
    <!-- 1. DADOS ENVIADOS NO FORMULÁRIO -->
    <div class="card" style="border-top: 4px solid #ffc107;">
        <h3 style="color: #856404;"><i class="fas fa-wpforms"></i> 1. Dados do Formulário</h3>
        <table class="table" style="margin-top: 0.8rem; font-size: 0.85rem;">
            <tr><th>Nome:</th><td><strong><?= htmlspecialchars($conflict['incoming_name']) ?></strong></td></tr>
            <tr><th>E-mail:</th><td><?= htmlspecialchars($conflict['incoming_email']) ?></td></tr>
            <tr><th>Telefone Norm.:</th><td><code><?= htmlspecialchars($conflict['incoming_phone_normalized']) ?></code></td></tr>
            <tr><th>Data Envio:</th><td><?= date('d/m/Y H:i:s', strtotime($conflict['created_at'])) ?></td></tr>
            <tr><th>Source:</th><td><?= htmlspecialchars($payloadData['source'] ?? 'aulas-gratuitas') ?></td></tr>
        </table>
    </div>

    <!-- 2. LEAD IDENTIFICADO PELO E-MAIL -->
    <div class="card" style="border-top: 4px solid #03045e;">
        <h3 style="color: #03045e;"><i class="fas fa-envelope"></i> 2. Lead por E-mail (Lead A)</h3>
        <?php if ($leadEmail): ?>
            <table class="table" style="margin-top: 0.8rem; font-size: 0.85rem;">
                <tr><th>ID Lead:</th><td><strong>#<?= $leadEmail['id'] ?></strong></td></tr>
                <tr><th>Nome:</th><td><?= htmlspecialchars($leadEmail['name']) ?></td></tr>
                <tr><th>E-mail:</th><td><?= htmlspecialchars($leadEmail['email']) ?></td></tr>
                <tr><th>Telefone:</th><td><?= htmlspecialchars($leadEmail['phone_original']) ?></td></tr>
                <tr><th>Criado em:</th><td><?= date('d/m/Y', strtotime($leadEmail['created_at'])) ?></td></tr>
            </table>
        <?php else: ?>
            <p style="color: #888; margin-top: 1rem;">Nenhum lead localizado por e-mail.</p>
        <?php endif; ?>
    </div>

    <!-- 3. LEAD IDENTIFICADO PELO TELEFONE -->
    <div class="card" style="border-top: 4px solid #ff8000;">
        <h3 style="color: #ff8000;"><i class="fab fa-whatsapp"></i> 3. Lead por Telefone (Lead B)</h3>
        <?php if ($leadPhone): ?>
            <table class="table" style="margin-top: 0.8rem; font-size: 0.85rem;">
                <tr><th>ID Lead:</th><td><strong>#<?= $leadPhone['id'] ?></strong></td></tr>
                <tr><th>Nome:</th><td><?= htmlspecialchars($leadPhone['name']) ?></td></tr>
                <tr><th>E-mail:</th><td><?= htmlspecialchars($leadPhone['email']) ?></td></tr>
                <tr><th>Telefone:</th><td><?= htmlspecialchars($leadPhone['phone_original']) ?></td></tr>
                <tr><th>Criado em:</th><td><?= date('d/m/Y', strtotime($leadPhone['created_at'])) ?></td></tr>
            </table>
        <?php else: ?>
            <p style="color: #888; margin-top: 1rem;">Nenhum lead localizado por telefone.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Ações de Resolução Administrativa -->
<div class="card">
    <h3><i class="fas fa-gavel"></i> Resolução Administrativa do Conflito</h3>
    <p style="color: #666; font-size: 0.9rem; margin-bottom: 1.5rem;">
        Selecione como este conflito deve ser tratado. Nenhum dado dos leads anteriores será apagado automaticamente.
    </p>

    <?php if ($conflict['status'] === 'resolved'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Conflito já resolvido em <strong><?= date('d/m/Y H:i', strtotime($conflict['resolved_at'])) ?></strong> por <strong><?= htmlspecialchars($conflict['resolved_by'] ?: 'Admin') ?></strong> (Tipo: <code><?= htmlspecialchars($conflict['resolution_type']) ?></code>).
        </div>
    <?php else: ?>
        <form method="POST">
            <?= csrf_field() ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php if ($leadEmail): ?>
                    <label style="background: #f8f9fa; padding: 1rem; border-radius: 6px; border: 1fr solid #ddd; cursor: pointer; display: block;">
                        <input type="radio" name="resolution_action" value="associate_email" checked>
                        <strong>Associar a conversão ao Lead do E-mail (#<?= $leadEmail['id'] ?> - <?= htmlspecialchars($leadEmail['name']) ?>)</strong>
                        <div style="font-size: 0.85rem; color: #666; margin-top: 0.3rem;">
                            O material baixado e a conversão serão vinculados a este lead. O Lead B permanece intacto.
                        </div>
                    </label>
                <?php endif; ?>

                <?php if ($leadPhone): ?>
                    <label style="background: #f8f9fa; padding: 1rem; border-radius: 6px; border: 1fr solid #ddd; cursor: pointer; display: block;">
                        <input type="radio" name="resolution_action" value="associate_phone" <?= !$leadEmail ? 'checked' : '' ?>>
                        <strong>Associar a conversão ao Lead do Telefone (#<?= $leadPhone['id'] ?> - <?= htmlspecialchars($leadPhone['name']) ?>)</strong>
                        <div style="font-size: 0.85rem; color: #666; margin-top: 0.3rem;">
                            O material baixado e a conversão serão vinculados a este lead. O Lead A permanece intacto.
                        </div>
                    </label>
                <?php endif; ?>

                <label style="background: #f8f9fa; padding: 1rem; border-radius: 6px; border: 1fr solid #ddd; cursor: pointer; display: block;">
                    <input type="radio" name="resolution_action" value="ignore">
                    <strong>Ignorar Conflito</strong>
                    <div style="font-size: 0.85rem; color: #666; margin-top: 0.3rem;">
                        Marca o registro como revisado/ignorado sem alterar nenhum dos dois leads.
                    </div>
                </label>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-success" onclick="return confirm('Confirma a resolução administrativa deste conflito?')">
                    <i class="fas fa-check"></i> Executar Resolução
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
