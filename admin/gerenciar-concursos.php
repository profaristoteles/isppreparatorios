<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';

// Tratar Soft Delete
if (isset($_GET['del'])) {
    verify_csrf_token();
    $id = (int)$_GET['del'];
    $stmt = $pdo->prepare("UPDATE free_contests SET active = 0, deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "Concurso arquivado com sucesso.";
    header("Location: gerenciar-concursos.php");
    exit;
}

// Tratar Restauração
if (isset($_GET['restore'])) {
    verify_csrf_token();
    $id = (int)$_GET['restore'];
    $stmt = $pdo->prepare("UPDATE free_contests SET active = 1, deleted_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "Concurso restaurado com sucesso.";
    header("Location: gerenciar-concursos.php");
    exit;
}

// Salvar / Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $board_id = !empty($_POST['board_id']) ? (int)$_POST['board_id'] : null;
    $exam_date = !empty($_POST['exam_date']) ? $_POST['exam_date'] : null;
    $active = isset($_POST['active']) ? 1 : 0;
    
    if (empty($name)) {
        $_SESSION['erro'] = "Informe o nome do concurso.";
        header("Location: gerenciar-concursos.php");
        exit;
    }
    
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', $name));
    } else {
        $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', $slug));
    }
    
    $stmtSlug = $pdo->prepare("SELECT id FROM free_contests WHERE slug = ? AND id != ? LIMIT 1");
    $stmtSlug->execute([$slug, $id]);
    if ($stmtSlug->fetch()) {
        $_SESSION['erro'] = "O slug '$slug' já pertence a outro concurso.";
        header("Location: gerenciar-concursos.php" . ($id ? "?edit=$id" : ""));
        exit;
    }
    
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE free_contests SET name=?, slug=?, description=?, board_id=?, exam_date=?, active=? WHERE id=?");
        $stmt->execute([$name, $slug, $description, $board_id, $exam_date, $active, $id]);
        $_SESSION['msg'] = "Concurso atualizado com sucesso.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO free_contests (name, slug, description, board_id, exam_date, active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $description, $board_id, $exam_date, $active]);
        $_SESSION['msg'] = "Concurso cadastrado com sucesso.";
    }
    
    header("Location: gerenciar-concursos.php");
    exit;
}

// Carregar Bancas Ativas para SELECT
$boardsSelect = $pdo->query("SELECT id, name FROM free_boards WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();

// Edição
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT * FROM free_contests WHERE id = ? LIMIT 1");
    $stmtEdit->execute([$editId]);
    $editData = $stmtEdit->fetch();
}

$search = trim($_GET['q'] ?? '');
$showArchived = isset($_GET['archived']);

$query = "SELECT c.*, b.name as board_name FROM free_contests c LEFT JOIN free_boards b ON c.board_id = b.id WHERE 1=1";
$params = [];

if ($showArchived) {
    $query .= " AND c.deleted_at IS NOT NULL";
} else {
    $query .= " AND c.deleted_at IS NULL";
}

if (!empty($search)) {
    $query .= " AND (c.name LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY c.name ASC";
$contests = $pdo->prepare($query);
$contests->execute($params);
$contestsList = $contests->fetchAll();

require_once 'includes/header.php';
?>

<h2><i class="fas fa-award"></i> Gerenciamento de Concursos</h2>

<div class="card">
    <h3><?= $editData ? 'Editar Concurso: ' . htmlspecialchars($editData['name']) : 'Cadastrar Novo Concurso' ?></h3>
    <form method="POST">
        <?= csrf_field() ?>
        <?php if ($editData): ?>
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 2fr 2fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Nome do Concurso *</label>
                <input type="text" name="name" class="form-control" placeholder="Ex: Prefeitura de Altos - PI" required value="<?= htmlspecialchars($editData['name'] ?? '') ?>" oninput="generateSlug(this.value)">
            </div>
            <div class="form-group">
                <label>Slug (URL Amigável)</label>
                <input type="text" name="slug" id="slugInput" class="form-control" value="<?= htmlspecialchars($editData['slug'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Data da Prova (Opcional)</label>
                <input type="date" name="exam_date" class="form-control" value="<?= htmlspecialchars($editData['exam_date'] ?? '') ?>">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Banca Organizadora</label>
                <select name="board_id" class="form-control">
                    <option value="">-- Nenhuma banca associada --</option>
                    <?php foreach ($boardsSelect as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($editData['board_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-top: 1.5rem;">
                <label style="font-weight: normal; cursor: pointer;">
                    <input type="checkbox" name="active" value="1" <?= ($editData['active'] ?? 1) ? 'checked' : '' ?>> Concurso Ativo
                </label>
            </div>
        </div>
        
        <div class="form-group">
            <label>Descrição / Informações do Concurso</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editData['description'] ?? '') ?></textarea>
        </div>
        
        <div style="margin-top: 1rem;">
            <button type="submit" class="btn"><i class="fas fa-save"></i> <?= $editData ? 'Atualizar Concurso' : 'Cadastrar Concurso' ?></button>
            <?php if ($editData): ?>
                <a href="gerenciar-concursos.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <form method="GET" style="display: flex; gap: 0.5rem; flex: 1; max-width: 500px;">
            <input type="text" name="q" class="form-control" placeholder="Buscar concurso..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn"><i class="fas fa-search"></i></button>
        </form>
        <div>
            <?php if ($showArchived): ?>
                <a href="gerenciar-concursos.php" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i> Ver Ativos</a>
            <?php else: ?>
                <a href="gerenciar-concursos.php?archived=1" class="btn btn-secondary btn-sm"><i class="fas fa-archive"></i> Ver Arquivados</a>
            <?php endif; ?>
        </div>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>Nome / Slug</th>
                <th>Banca</th>
                <th>Data Prova</th>
                <th>Status</th>
                <th style="width: 150px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($contestsList)): ?>
                <tr><td colspan="5" style="text-align: center; color: #888;">Nenhum concurso cadastrado.</td></tr>
            <?php else: foreach ($contestsList as $c): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($c['name']) ?></strong><br>
                        <small style="color: #666;"><?= htmlspecialchars($c['slug']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($c['board_name'] ?? 'Sem banca') ?></td>
                    <td><?= $c['exam_date'] ? date('d/m/Y', strtotime($c['exam_date'])) : '-' ?></td>
                    <td>
                        <?php if ($c['deleted_at']): ?>
                            <span class="badge badge-danger">Arquivado</span>
                        <?php elseif ($c['active']): ?>
                            <span class="badge badge-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($c['deleted_at']): ?>
                            <a href="gerenciar-concursos.php?restore=<?= $c['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-success btn-sm" onclick="return confirm('Deseja restaurar este concurso?')"><i class="fas fa-trash-restore"></i></a>
                        <?php else: ?>
                            <a href="gerenciar-concursos.php?edit=<?= $c['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="gerenciar-concursos.php?del=<?= $c['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja arquivar este concurso?')"><i class="fas fa-archive"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<script>
function generateSlug(text) {
    const slugInput = document.getElementById('slugInput');
    if (slugInput && !slugInput.dataset.manual) {
        slugInput.value = text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9 -]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-');
    }
}
document.getElementById('slugInput').addEventListener('input', function() {
    this.dataset.manual = 'true';
});
</script>

<?php require_once 'includes/footer.php'; ?>
