<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';

// Tratar Soft Delete
if (isset($_GET['del'])) {
    verify_csrf_token();
    $id = (int)$_GET['del'];
    $stmt = $pdo->prepare("UPDATE free_channels SET active = 0, deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "Canal arquivado com sucesso.";
    header("Location: gerenciar-canais.php");
    exit;
}

// Tratar Restauração
if (isset($_GET['restore'])) {
    verify_csrf_token();
    $id = (int)$_GET['restore'];
    $stmt = $pdo->prepare("UPDATE free_channels SET active = 1, deleted_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "Canal restaurado com sucesso.";
    header("Location: gerenciar-canais.php");
    exit;
}

// Salvar / Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $order_index = (int)($_POST['order_index'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;
    
    if (empty($name)) {
        $_SESSION['erro'] = "Informe o nome do canal.";
        header("Location: gerenciar-canais.php");
        exit;
    }
    
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', $name));
    } else {
        $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', $slug));
    }
    
    // Verificar duplicidade de slug
    $stmtSlug = $pdo->prepare("SELECT id FROM free_channels WHERE slug = ? AND id != ? LIMIT 1");
    $stmtSlug->execute([$slug, $id]);
    if ($stmtSlug->fetch()) {
        $_SESSION['erro'] = "O slug '$slug' já está em uso por outro canal.";
        header("Location: gerenciar-canais.php" . ($id ? "?edit=$id" : ""));
        exit;
    }
    
    // Upload de Imagem de Capa
    $cover_image = '';
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $cover_image = 'chan_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['cover_image']['tmp_name'], '../uploads/' . $cover_image);
        }
    }
    
    if ($id > 0) {
        if ($cover_image) {
            $stmt = $pdo->prepare("UPDATE free_channels SET name=?, slug=?, description=?, cover_image=?, order_index=?, active=? WHERE id=?");
            $stmt->execute([$name, $slug, $description, $cover_image, $order_index, $active, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE free_channels SET name=?, slug=?, description=?, order_index=?, active=? WHERE id=?");
            $stmt->execute([$name, $slug, $description, $order_index, $active, $id]);
        }
        $_SESSION['msg'] = "Canal atualizado com sucesso.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO free_channels (name, slug, description, cover_image, order_index, active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $description, $cover_image, $order_index, $active]);
        $_SESSION['msg'] = "Canal cadastrado com sucesso.";
    }
    
    header("Location: gerenciar-canais.php");
    exit;
}

// Edição
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT * FROM free_channels WHERE id = ? LIMIT 1");
    $stmtEdit->execute([$editId]);
    $editData = $stmtEdit->fetch();
}

// Filtro e Listagem
$search = trim($_GET['q'] ?? '');
$showArchived = isset($_GET['archived']);

$query = "SELECT * FROM free_channels WHERE 1=1";
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

$query .= " ORDER BY order_index ASC, id DESC";
$channels = $pdo->prepare($query);
$channels->execute($params);
$channelsList = $channels->fetchAll();

require_once 'includes/header.php';
?>

<h2><i class="fas fa-tv"></i> Gerenciamento de Canais</h2>

<!-- Formulário de Cadastro / Edição -->
<div class="card">
    <h3><?= $editData ? 'Editar Canal: ' . htmlspecialchars($editData['name']) : 'Cadastrar Novo Canal' ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($editData): ?>
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 2fr 2fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Nome do Canal *</label>
                <input type="text" name="name" id="nameInput" class="form-control" required value="<?= htmlspecialchars($editData['name'] ?? '') ?>" oninput="generateSlug(this.value)">
            </div>
            <div class="form-group">
                <label>Slug (URL Amigável)</label>
                <input type="text" name="slug" id="slugInput" class="form-control" value="<?= htmlspecialchars($editData['slug'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Ordem Exibição</label>
                <input type="number" name="order_index" class="form-control" value="<?= htmlspecialchars($editData['order_index'] ?? 0) ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Descrição do Canal</label>
            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editData['description'] ?? '') ?></textarea>
        </div>
        
        <div style="display: flex; gap: 1.5rem; align-items: center;">
            <div class="form-group" style="flex: 1;">
                <label>Capa/Imagem do Canal</label>
                <input type="file" name="cover_image" class="form-control" accept="image/*">
                <?php if (!empty($editData['cover_image'])): ?>
                    <small style="display: block; margin-top: 0.3rem;">Atual: <?= htmlspecialchars($editData['cover_image']) ?></small>
                <?php endif; ?>
            </div>
            <div class="form-group" style="margin-top: 1.5rem;">
                <label style="font-weight: normal; cursor: pointer;">
                    <input type="checkbox" name="active" value="1" <?= ($editData['active'] ?? 1) ? 'checked' : '' ?>> Canal Ativo
                </label>
            </div>
        </div>
        
        <div style="margin-top: 1rem;">
            <button type="submit" class="btn"><i class="fas fa-save"></i> <?= $editData ? 'Atualizar Canal' : 'Cadastrar Canal' ?></button>
            <?php if ($editData): ?>
                <a href="gerenciar-canais.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Filtros e Tabela de Listagem -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <form method="GET" style="display: flex; gap: 0.5rem; flex: 1; max-width: 500px;">
            <input type="text" name="q" class="form-control" placeholder="Buscar por nome ou descrição..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn"><i class="fas fa-search"></i></button>
        </form>
        <div>
            <?php if ($showArchived): ?>
                <a href="gerenciar-canais.php" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i> Ver Canais Ativos</a>
            <?php else: ?>
                <a href="gerenciar-canais.php?archived=1" class="btn btn-secondary btn-sm"><i class="fas fa-archive"></i> Ver Arquivados</a>
            <?php endif; ?>
        </div>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th style="width: 50px;">Ordem</th>
                <th>Capa</th>
                <th>Nome / Slug</th>
                <th>Descrição</th>
                <th>Status</th>
                <th style="width: 150px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($channelsList)): ?>
                <tr><td colspan="6" style="text-align: center; color: #888;">Nenhum canal encontrado.</td></tr>
            <?php else: foreach ($channelsList as $ch): ?>
                <tr>
                    <td><?= $ch['order_index'] ?></td>
                    <td>
                        <?php if (!empty($ch['cover_image'])): ?>
                            <img src="/uploads/<?= htmlspecialchars($ch['cover_image']) ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 4px;">
                        <?php else: ?>
                            <span style="color: #ccc;"><i class="fas fa-image fa-2x"></i></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($ch['name']) ?></strong><br>
                        <small style="color: #666;">/aulas-gratuitas/<?= htmlspecialchars($ch['slug']) ?></small>
                    </td>
                    <td><?= htmlspecialchars(mb_strimwidth($ch['description'], 0, 80, '...')) ?></td>
                    <td>
                        <?php if ($ch['deleted_at']): ?>
                            <span class="badge badge-danger">Arquivado</span>
                        <?php elseif ($ch['active']): ?>
                            <span class="badge badge-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($ch['deleted_at']): ?>
                            <a href="gerenciar-canais.php?restore=<?= $ch['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-success btn-sm" onclick="return confirm('Deseja restaurar este canal?')"><i class="fas fa-trash-restore"></i></a>
                        <?php else: ?>
                            <a href="gerenciar-canais.php?edit=<?= $ch['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="gerenciar-canais.php?del=<?= $ch['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja arquivar este canal?')"><i class="fas fa-archive"></i></a>
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
