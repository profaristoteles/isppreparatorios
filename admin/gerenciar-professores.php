<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';

// Tratar Soft Delete
if (isset($_GET['del'])) {
    verify_csrf_token();
    $id = (int)$_GET['del'];
    $stmt = $pdo->prepare("UPDATE free_teachers SET active = 0, deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "Professor(a) arquivado(a) com sucesso.";
    header("Location: gerenciar-professores.php");
    exit;
}

// Tratar Restauração
if (isset($_GET['restore'])) {
    verify_csrf_token();
    $id = (int)$_GET['restore'];
    $stmt = $pdo->prepare("UPDATE free_teachers SET active = 1, deleted_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "Professor(a) restaurado(a) com sucesso.";
    header("Location: gerenciar-professores.php");
    exit;
}

// Salvar / Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    
    if (empty($name)) {
        $_SESSION['erro'] = "Informe o nome do(a) professor(a).";
        header("Location: gerenciar-professores.php");
        exit;
    }
    
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', $name));
    } else {
        $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', $slug));
    }
    
    $stmtSlug = $pdo->prepare("SELECT id FROM free_teachers WHERE slug = ? AND id != ? LIMIT 1");
    $stmtSlug->execute([$slug, $id]);
    if ($stmtSlug->fetch()) {
        $_SESSION['erro'] = "O slug '$slug' já pertence a outro professor.";
        header("Location: gerenciar-professores.php" . ($id ? "?edit=$id" : ""));
        exit;
    }
    
    // Upload de Foto
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $photo = 'teacher_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], '../uploads/' . $photo);
        }
    }
    
    if ($id > 0) {
        if ($photo) {
            $stmt = $pdo->prepare("UPDATE free_teachers SET name=?, slug=?, bio=?, photo=?, active=? WHERE id=?");
            $stmt->execute([$name, $slug, $bio, $photo, $active, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE free_teachers SET name=?, slug=?, bio=?, active=? WHERE id=?");
            $stmt->execute([$name, $slug, $bio, $active, $id]);
        }
        $_SESSION['msg'] = "Professor(a) atualizado(a) com sucesso.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO free_teachers (name, slug, bio, photo, active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $bio, $photo, $active]);
        $_SESSION['msg'] = "Professor(a) cadastrado(a) com sucesso.";
    }
    
    header("Location: gerenciar-professores.php");
    exit;
}

// Edição
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT * FROM free_teachers WHERE id = ? LIMIT 1");
    $stmtEdit->execute([$editId]);
    $editData = $stmtEdit->fetch();
}

$search = trim($_GET['q'] ?? '');
$showArchived = isset($_GET['archived']);

$query = "SELECT * FROM free_teachers WHERE 1=1";
$params = [];

if ($showArchived) {
    $query .= " AND deleted_at IS NOT NULL";
} else {
    $query .= " AND deleted_at IS NULL";
}

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR bio LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY name ASC";
$teachers = $pdo->prepare($query);
$teachers->execute($params);
$teachersList = $teachers->fetchAll();

require_once 'includes/header.php';
?>

<h2><i class="fas fa-chalkboard-teacher"></i> Gerenciamento de Professores</h2>

<div class="card">
    <h3><?= $editData ? 'Editar Professor(a): ' . htmlspecialchars($editData['name']) : 'Cadastrar Novo(a) Professor(a)' ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($editData): ?>
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Nome do(a) Professor(a) *</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editData['name'] ?? '') ?>" oninput="generateSlug(this.value)">
            </div>
            <div class="form-group">
                <label>Slug (URL Amigável)</label>
                <input type="text" name="slug" id="slugInput" class="form-control" value="<?= htmlspecialchars($editData['slug'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Biografia / Resumo Profissional</label>
            <textarea name="bio" class="form-control" rows="3"><?= htmlspecialchars($editData['bio'] ?? '') ?></textarea>
        </div>
        
        <div style="display: flex; gap: 1.5rem; align-items: center;">
            <div class="form-group" style="flex: 1;">
                <label>Foto do(a) Professor(a)</label>
                <input type="file" name="photo" class="form-control" accept="image/*">
                <?php if (!empty($editData['photo'])): ?>
                    <small style="display: block; margin-top: 0.3rem;">Foto Atual: <?= htmlspecialchars($editData['photo']) ?></small>
                <?php endif; ?>
            </div>
            <div class="form-group" style="margin-top: 1.5rem;">
                <label style="font-weight: normal; cursor: pointer;">
                    <input type="checkbox" name="active" value="1" <?= ($editData['active'] ?? 1) ? 'checked' : '' ?>> Cadastro Ativo
                </label>
            </div>
        </div>
        
        <div style="margin-top: 1rem;">
            <button type="submit" class="btn"><i class="fas fa-save"></i> <?= $editData ? 'Atualizar Professor(a)' : 'Cadastrar Professor(a)' ?></button>
            <?php if ($editData): ?>
                <a href="gerenciar-professores.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
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
                <a href="gerenciar-professores.php" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i> Ver Ativos</a>
            <?php else: ?>
                <a href="gerenciar-professores.php?archived=1" class="btn btn-secondary btn-sm"><i class="fas fa-archive"></i> Ver Arquivados</a>
            <?php endif; ?>
        </div>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>Foto</th>
                <th>Nome / Slug</th>
                <th>Biografia</th>
                <th>Status</th>
                <th style="width: 150px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($teachersList)): ?>
                <tr><td colspan="5" style="text-align: center; color: #888;">Nenhum professor cadastrado.</td></tr>
            <?php else: foreach ($teachersList as $t): ?>
                <tr>
                    <td>
                        <?php if (!empty($t['photo'])): ?>
                            <img src="/uploads/<?= htmlspecialchars($t['photo']) ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <span style="color: #ccc;"><i class="fas fa-user-circle fa-2x"></i></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($t['name']) ?></strong><br>
                        <small style="color: #666;"><?= htmlspecialchars($t['slug']) ?></small>
                    </td>
                    <td><?= htmlspecialchars(mb_strimwidth($t['bio'] ?? '', 0, 80, '...')) ?></td>
                    <td>
                        <?php if ($t['deleted_at']): ?>
                            <span class="badge badge-danger">Arquivado</span>
                        <?php elseif ($t['active']): ?>
                            <span class="badge badge-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($t['deleted_at']): ?>
                            <a href="gerenciar-professores.php?restore=<?= $t['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-success btn-sm" onclick="return confirm('Deseja restaurar este cadastro?')"><i class="fas fa-trash-restore"></i></a>
                        <?php else: ?>
                            <a href="gerenciar-professores.php?edit=<?= $t['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="gerenciar-professores.php?del=<?= $t['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja arquivar este cadastro?')"><i class="fas fa-archive"></i></a>
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
