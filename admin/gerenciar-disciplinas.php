<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';

// Tratar Soft Delete
if (isset($_GET['del'])) {
    verify_csrf_token();
    $id = (int)$_GET['del'];
    $stmt = $pdo->prepare("UPDATE free_disciplines SET active = 0, deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "Disciplina arquivada com sucesso.";
    header("Location: gerenciar-disciplinas.php");
    exit;
}

// Tratar Restauração
if (isset($_GET['restore'])) {
    verify_csrf_token();
    $id = (int)$_GET['restore'];
    $stmt = $pdo->prepare("UPDATE free_disciplines SET active = 1, deleted_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "Disciplina restaurada com sucesso.";
    header("Location: gerenciar-disciplinas.php");
    exit;
}

// Salvar / Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    
    if (empty($name)) {
        $_SESSION['erro'] = "Informe o nome da disciplina.";
        header("Location: gerenciar-disciplinas.php");
        exit;
    }
    
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', $name));
    } else {
        $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', $slug));
    }
    
    $stmtSlug = $pdo->prepare("SELECT id FROM free_disciplines WHERE slug = ? AND id != ? LIMIT 1");
    $stmtSlug->execute([$slug, $id]);
    if ($stmtSlug->fetch()) {
        $_SESSION['erro'] = "O slug '$slug' já pertence a outra disciplina.";
        header("Location: gerenciar-disciplinas.php" . ($id ? "?edit=$id" : ""));
        exit;
    }
    
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE free_disciplines SET name=?, slug=?, description=?, active=? WHERE id=?");
        $stmt->execute([$name, $slug, $description, $active, $id]);
        $_SESSION['msg'] = "Disciplina atualizada com sucesso.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO free_disciplines (name, slug, description, active) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $description, $active]);
        $_SESSION['msg'] = "Disciplina cadastrada com sucesso.";
    }
    
    header("Location: gerenciar-disciplinas.php");
    exit;
}

// Edição
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT * FROM free_disciplines WHERE id = ? LIMIT 1");
    $stmtEdit->execute([$editId]);
    $editData = $stmtEdit->fetch();
}

$search = trim($_GET['q'] ?? '');
$showArchived = isset($_GET['archived']);

$query = "SELECT * FROM free_disciplines WHERE 1=1";
$params = [];

if ($showArchived) {
    $query .= " AND deleted_at IS NOT NULL";
} else {
    $query .= " AND deleted_at IS NULL";
}

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY name ASC";
$disciplines = $pdo->prepare($query);
$disciplines->execute($params);
$disciplinesList = $disciplines->fetchAll();

require_once 'includes/header.php';
?>

<h2><i class="fas fa-book"></i> Gerenciamento de Disciplinas</h2>

<div class="card">
    <h3><?= $editData ? 'Editar Disciplina: ' . htmlspecialchars($editData['name']) : 'Cadastrar Nova Disciplina' ?></h3>
    <form method="POST">
        <?= csrf_field() ?>
        <?php if ($editData): ?>
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Nome da Disciplina *</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editData['name'] ?? '') ?>" oninput="generateSlug(this.value)">
            </div>
            <div class="form-group">
                <label>Slug (URL Amigável)</label>
                <input type="text" name="slug" id="slugInput" class="form-control" value="<?= htmlspecialchars($editData['slug'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Descrição da Disciplina</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editData['description'] ?? '') ?></textarea>
        </div>
        
        <div class="form-group">
            <label style="font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="active" value="1" <?= ($editData['active'] ?? 1) ? 'checked' : '' ?>> Disciplina Ativa
            </label>
        </div>
        
        <div style="margin-top: 1rem;">
            <button type="submit" class="btn"><i class="fas fa-save"></i> <?= $editData ? 'Atualizar Disciplina' : 'Cadastrar Disciplina' ?></button>
            <?php if ($editData): ?>
                <a href="gerenciar-disciplinas.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <form method="GET" style="display: flex; gap: 0.5rem; flex: 1; max-width: 500px;">
            <input type="text" name="q" class="form-control" placeholder="Buscar por nome..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn"><i class="fas fa-search"></i></button>
        </form>
        <div>
            <?php if ($showArchived): ?>
                <a href="gerenciar-disciplinas.php" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i> Ver Ativas</a>
            <?php else: ?>
                <a href="gerenciar-disciplinas.php?archived=1" class="btn btn-secondary btn-sm"><i class="fas fa-archive"></i> Ver Arquivadas</a>
            <?php endif; ?>
        </div>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>Nome / Slug</th>
                <th>Descrição</th>
                <th>Status</th>
                <th style="width: 150px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($disciplinesList)): ?>
                <tr><td colspan="4" style="text-align: center; color: #888;">Nenhuma disciplina cadastrada.</td></tr>
            <?php else: foreach ($disciplinesList as $d): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($d['name']) ?></strong><br>
                        <small style="color: #666;"><?= htmlspecialchars($d['slug']) ?></small>
                    </td>
                    <td><?= htmlspecialchars(mb_strimwidth($d['description'] ?? '', 0, 100, '...')) ?></td>
                    <td>
                        <?php if ($d['deleted_at']): ?>
                            <span class="badge badge-danger">Arquivada</span>
                        <?php elseif ($d['active']): ?>
                            <span class="badge badge-success">Ativa</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Inativa</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($d['deleted_at']): ?>
                            <a href="gerenciar-disciplinas.php?restore=<?= $d['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-success btn-sm" onclick="return confirm('Deseja restaurar esta disciplina?')"><i class="fas fa-trash-restore"></i></a>
                        <?php else: ?>
                            <a href="gerenciar-disciplinas.php?edit=<?= $d['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="gerenciar-disciplinas.php?del=<?= $d['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja arquivar esta disciplina?')"><i class="fas fa-archive"></i></a>
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
