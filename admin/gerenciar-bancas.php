<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';

// Tratar Soft Delete
if (isset($_GET['del'])) {
    verify_csrf_token();
    $id = (int)$_GET['del'];
    $stmt = $pdo->prepare("UPDATE free_boards SET active = 0, deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "Banca arquivada com sucesso.";
    header("Location: gerenciar-bancas.php");
    exit;
}

// Tratar Restauração
if (isset($_GET['restore'])) {
    verify_csrf_token();
    $id = (int)$_GET['restore'];
    $stmt = $pdo->prepare("UPDATE free_boards SET active = 1, deleted_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "Banca restaurada com sucesso.";
    header("Location: gerenciar-bancas.php");
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
        $_SESSION['erro'] = "Informe o nome da banca organizadora.";
        header("Location: gerenciar-bancas.php");
        exit;
    }
    
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', $name));
    } else {
        $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', $slug));
    }
    
    // Verificar duplicidade de slug
    $stmtSlug = $pdo->prepare("SELECT id FROM free_boards WHERE slug = ? AND id != ? LIMIT 1");
    $stmtSlug->execute([$slug, $id]);
    if ($stmtSlug->fetch()) {
        $_SESSION['erro'] = "O slug '$slug' já pertence a outra banca.";
        header("Location: gerenciar-bancas.php" . ($id ? "?edit=$id" : ""));
        exit;
    }
    
    // Upload de Logo
    $logo = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg'])) {
            $logo = 'board_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['logo']['tmp_name'], '../uploads/' . $logo);
        }
    }
    
    if ($id > 0) {
        if ($logo) {
            $stmt = $pdo->prepare("UPDATE free_boards SET name=?, slug=?, description=?, logo=?, active=? WHERE id=?");
            $stmt->execute([$name, $slug, $description, $logo, $active, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE free_boards SET name=?, slug=?, description=?, active=? WHERE id=?");
            $stmt->execute([$name, $slug, $description, $active, $id]);
        }
        $_SESSION['msg'] = "Banca atualizada com sucesso.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO free_boards (name, slug, description, logo, active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $description, $logo, $active]);
        $_SESSION['msg'] = "Banca cadastrada com sucesso.";
    }
    
    header("Location: gerenciar-bancas.php");
    exit;
}

// Edição
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT * FROM free_boards WHERE id = ? LIMIT 1");
    $stmtEdit->execute([$editId]);
    $editData = $stmtEdit->fetch();
}

// Filtro e Listagem
$search = trim($_GET['q'] ?? '');
$showArchived = isset($_GET['archived']);

$query = "SELECT * FROM free_boards WHERE 1=1";
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
$boards = $pdo->prepare($query);
$boards->execute($params);
$boardsList = $boards->fetchAll();

require_once 'includes/header.php';
?>

<h2><i class="fas fa-building"></i> Gerenciamento de Bancas Organizadoras</h2>

<!-- Formulário -->
<div class="card">
    <h3><?= $editData ? 'Editar Banca: ' . htmlspecialchars($editData['name']) : 'Cadastrar Nova Banca' ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($editData): ?>
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Nome da Banca *</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($editData['name'] ?? '') ?>" oninput="generateSlug(this.value)">
            </div>
            <div class="form-group">
                <label>Slug (URL Amigável)</label>
                <input type="text" name="slug" id="slugInput" class="form-control" value="<?= htmlspecialchars($editData['slug'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Descrição / Informações da Banca</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editData['description'] ?? '') ?></textarea>
        </div>
        
        <div style="display: flex; gap: 1.5rem; align-items: center;">
            <div class="form-group" style="flex: 1;">
                <label>Logo da Banca</label>
                <input type="file" name="logo" class="form-control" accept="image/*">
                <?php if (!empty($editData['logo'])): ?>
                    <small style="display: block; margin-top: 0.3rem;">Logo Atual: <?= htmlspecialchars($editData['logo']) ?></small>
                <?php endif; ?>
            </div>
            <div class="form-group" style="margin-top: 1.5rem;">
                <label style="font-weight: normal; cursor: pointer;">
                    <input type="checkbox" name="active" value="1" <?= ($editData['active'] ?? 1) ? 'checked' : '' ?>> Banca Ativa
                </label>
            </div>
        </div>
        
        <div style="margin-top: 1rem;">
            <button type="submit" class="btn"><i class="fas fa-save"></i> <?= $editData ? 'Atualizar Banca' : 'Cadastrar Banca' ?></button>
            <?php if ($editData): ?>
                <a href="gerenciar-bancas.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Listagem -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <form method="GET" style="display: flex; gap: 0.5rem; flex: 1; max-width: 500px;">
            <input type="text" name="q" class="form-control" placeholder="Buscar banca por nome..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn"><i class="fas fa-search"></i></button>
        </form>
        <div>
            <?php if ($showArchived): ?>
                <a href="gerenciar-bancas.php" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i> Ver Bancas Ativas</a>
            <?php else: ?>
                <a href="gerenciar-bancas.php?archived=1" class="btn btn-secondary btn-sm"><i class="fas fa-archive"></i> Ver Arquivadas</a>
            <?php endif; ?>
        </div>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>Logo</th>
                <th>Nome / Slug</th>
                <th>Descrição</th>
                <th>Status</th>
                <th style="width: 150px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($boardsList)): ?>
                <tr><td colspan="5" style="text-align: center; color: #888;">Nenhuma banca cadastrada.</td></tr>
            <?php else: foreach ($boardsList as $b): ?>
                <tr>
                    <td>
                        <?php if (!empty($b['logo'])): ?>
                            <img src="/uploads/<?= htmlspecialchars($b['logo']) ?>" style="max-width: 60px; max-height: 40px; object-fit: contain;">
                        <?php else: ?>
                            <span style="color: #ccc;"><i class="fas fa-building fa-2x"></i></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($b['name']) ?></strong><br>
                        <small style="color: #666;">/aulas-gratuitas/banca/<?= htmlspecialchars($b['slug']) ?></small>
                    </td>
                    <td><?= htmlspecialchars(mb_strimwidth($b['description'] ?? '', 0, 80, '...')) ?></td>
                    <td>
                        <?php if ($b['deleted_at']): ?>
                            <span class="badge badge-danger">Arquivada</span>
                        <?php elseif ($b['active']): ?>
                            <span class="badge badge-success">Ativa</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Inativa</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($b['deleted_at']): ?>
                            <a href="gerenciar-bancas.php?restore=<?= $b['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-success btn-sm" onclick="return confirm('Deseja restaurar esta banca?')"><i class="fas fa-trash-restore"></i></a>
                        <?php else: ?>
                            <a href="gerenciar-bancas.php?edit=<?= $b['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="gerenciar-bancas.php?del=<?= $b['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja arquivar esta banca?')"><i class="fas fa-archive"></i></a>
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
