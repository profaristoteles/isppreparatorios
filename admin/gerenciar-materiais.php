<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';

// Tratar Soft Delete
if (isset($_GET['del'])) {
    verify_csrf_token();
    $id = (int)$_GET['del'];
    $stmt = $pdo->prepare("UPDATE free_materials SET active = 0, deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "Material PDF arquivado com sucesso.";
    header("Location: gerenciar-materiais.php");
    exit;
}

// Tratar Restauração
if (isset($_GET['restore'])) {
    verify_csrf_token();
    $id = (int)$_GET['restore'];
    $stmt = $pdo->prepare("UPDATE free_materials SET active = 1, deleted_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "Material PDF restaurado com sucesso.";
    header("Location: gerenciar-materiais.php");
    exit;
}

// Salvar / Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    $id = (int)($_POST['id'] ?? 0);
    $video_id = (int)($_POST['video_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    
    if (!$video_id || empty($title)) {
        $_SESSION['erro'] = "Selecione a videoaula e informe o título do material.";
        header("Location: gerenciar-materiais.php");
        exit;
    }
    
    $file_path = '';
    $original_filename = '';
    $file_size = 0;
    $mime_type = 'application/pdf';
    
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
        $detected_mime = mime_content_type($_FILES['pdf_file']['tmp_name']);
        
        if ($ext !== 'pdf' || (strpos($detected_mime, 'pdf') === false && $detected_mime !== 'application/octet-stream')) {
            $_SESSION['erro'] = "Apenas arquivos no formato PDF são aceitos.";
            header("Location: gerenciar-materiais.php" . ($id ? "?edit=$id" : ""));
            exit;
        }
        
        $original_filename = $_FILES['pdf_file']['name'];
        $file_size = $_FILES['pdf_file']['size'];
        $file_path = 'pdf_' . bin2hex(random_bytes(16)) . '.pdf';
        
        $target_dir = __DIR__ . '/../uploads/materiais_protegidos/';
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        if (!move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target_dir . $file_path)) {
            $_SESSION['erro'] = "Falha ao mover o arquivo para a pasta protegida.";
            header("Location: gerenciar-materiais.php" . ($id ? "?edit=$id" : ""));
            exit;
        }
    } elseif ($id === 0) {
        $_SESSION['erro'] = "Selecione um arquivo PDF para upload.";
        header("Location: gerenciar-materiais.php");
        exit;
    }
    
    if ($id > 0) {
        if ($file_path) {
            $stmt = $pdo->prepare("UPDATE free_materials SET video_id=?, title=?, description=?, file_path=?, original_filename=?, mime_type=?, file_size=?, active=? WHERE id=?");
            $stmt->execute([$video_id, $title, $description, $file_path, $original_filename, $mime_type, $file_size, $active, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE free_materials SET video_id=?, title=?, description=?, active=? WHERE id=?");
            $stmt->execute([$video_id, $title, $description, $active, $id]);
        }
        
        // Marca na videoaula que possui material
        $pdo->prepare("UPDATE free_videos SET has_material = 1 WHERE id = ?")->execute([$video_id]);
        
        $_SESSION['msg'] = "Material PDF atualizado com sucesso.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO free_materials (video_id, title, description, file_path, original_filename, mime_type, file_size, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$video_id, $title, $description, $file_path, $original_filename, $mime_type, $file_size, $active]);
        
        // Marca na videoaula que possui material
        $pdo->prepare("UPDATE free_videos SET has_material = 1 WHERE id = ?")->execute([$video_id]);
        
        $_SESSION['msg'] = "Material PDF cadastrado com sucesso.";
    }
    
    header("Location: gerenciar-materiais.php");
    exit;
}

// Carregar Videoaulas Ativas para SELECT
$videosSelect = $pdo->query("SELECT id, title FROM free_videos WHERE deleted_at IS NULL ORDER BY title ASC")->fetchAll();

// Edição
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT * FROM free_materials WHERE id = ? LIMIT 1");
    $stmtEdit->execute([$editId]);
    $editData = $stmtEdit->fetch();
}

$search = trim($_GET['q'] ?? '');
$filterVideo = !empty($_GET['video_id']) ? (int)$_GET['video_id'] : 0;
$showArchived = isset($_GET['archived']);

$query = "SELECT m.*, v.title as video_title FROM free_materials m JOIN free_videos v ON m.video_id = v.id WHERE 1=1";
$params = [];

if ($showArchived) {
    $query .= " AND m.deleted_at IS NOT NULL";
} else {
    $query .= " AND m.deleted_at IS NULL";
}

if ($filterVideo > 0) {
    $query .= " AND m.video_id = ?";
    $params[] = $filterVideo;
}

if (!empty($search)) {
    $query .= " AND (m.title LIKE ? OR m.original_filename LIKE ? OR v.title LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY m.id DESC";
$materials = $pdo->prepare($query);
$materials->execute($params);
$materialsList = $materials->fetchAll();

require_once 'includes/header.php';
?>

<h2><i class="fas fa-file-pdf"></i> Gerenciamento de Materiais Gratuitos em PDF</h2>

<div class="card">
    <h3><?= $editData ? 'Editar Material: ' . htmlspecialchars($editData['title']) : 'Cadastrar Novo Material PDF' ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($editData): ?>
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 2fr 2fr; gap: 1rem;">
            <div class="form-group">
                <label>Videoaula Associada *</label>
                <select name="video_id" class="form-control" required>
                    <option value="">-- Selecionar Videoaula --</option>
                    <?php foreach ($videosSelect as $v): ?>
                        <option value="<?= $v['id'] ?>" <?= ($editData['video_id'] ?? $filterVideo) == $v['id'] ? 'selected' : '' ?>><?= htmlspecialchars($v['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Título do Material *</label>
                <input type="text" name="title" class="form-control" placeholder="Ex: Caderno de Questões Comentadas" required value="<?= htmlspecialchars($editData['title'] ?? '') ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Descrição do Material</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Ex: Baixe gratuitamente o caderno de questões utilizado nesta aula."><?= htmlspecialchars($editData['description'] ?? '') ?></textarea>
        </div>
        
        <div style="display: flex; gap: 1.5rem; align-items: center;">
            <div class="form-group" style="flex: 1;">
                <label>Arquivo PDF (Pasta Protegida) <?= $editData ? '(Deixe em branco para manter)' : '*' ?></label>
                <input type="file" name="pdf_file" class="form-control" accept="application/pdf,.pdf" <?= $editData ? '' : 'required' ?>>
                <?php if (!empty($editData['original_filename'])): ?>
                    <small style="display: block; margin-top: 0.3rem;">Atual: <strong><?= htmlspecialchars($editData['original_filename']) ?></strong> (<?= round($editData['file_size'] / 1024, 1) ?> KB)</small>
                <?php endif; ?>
            </div>
            <div class="form-group" style="margin-top: 1.5rem;">
                <label style="font-weight: normal; cursor: pointer;">
                    <input type="checkbox" name="active" value="1" <?= ($editData['active'] ?? 1) ? 'checked' : '' ?>> Material Ativo
                </label>
            </div>
        </div>
        
        <div style="margin-top: 1rem;">
            <button type="submit" class="btn"><i class="fas fa-save"></i> <?= $editData ? 'Atualizar Material' : 'Cadastrar Material' ?></button>
            <?php if ($editData): ?>
                <a href="gerenciar-materiais.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <form method="GET" style="display: flex; gap: 0.5rem; flex: 1; max-width: 600px;">
            <input type="text" name="q" class="form-control" placeholder="Buscar por título ou arquivo..." value="<?= htmlspecialchars($search) ?>">
            <select name="video_id" class="form-control" style="width: 200px;">
                <option value="">Todas as videoaulas</option>
                <?php foreach ($videosSelect as $v): ?>
                    <option value="<?= $v['id'] ?>" <?= $filterVideo == $v['id'] ? 'selected' : '' ?>><?= htmlspecialchars($v['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn"><i class="fas fa-filter"></i></button>
        </form>
        <div>
            <?php if ($showArchived): ?>
                <a href="gerenciar-materiais.php" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i> Ver Ativos</a>
            <?php else: ?>
                <a href="gerenciar-materiais.php?archived=1" class="btn btn-secondary btn-sm"><i class="fas fa-archive"></i> Ver Arquivados</a>
            <?php endif; ?>
        </div>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>Título / Arquivo</th>
                <th>Videoaula Associada</th>
                <th>Tamanho</th>
                <th>Status</th>
                <th style="width: 150px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($materialsList)): ?>
                <tr><td colspan="5" style="text-align: center; color: #888;">Nenhum material PDF cadastrado.</td></tr>
            <?php else: foreach ($materialsList as $m): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($m['title']) ?></strong><br>
                        <small style="color: #666;"><i class="fas fa-file-pdf text-danger"></i> <?= htmlspecialchars($m['original_filename']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($m['video_title']) ?></td>
                    <td><?= round($m['file_size'] / 1024, 1) ?> KB</td>
                    <td>
                        <?php if ($m['deleted_at']): ?>
                            <span class="badge badge-danger">Arquivado</span>
                        <?php elseif ($m['active']): ?>
                            <span class="badge badge-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($m['deleted_at']): ?>
                            <a href="gerenciar-materiais.php?restore=<?= $m['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-success btn-sm" onclick="return confirm('Deseja restaurar este material?')"><i class="fas fa-trash-restore"></i></a>
                        <?php else: ?>
                            <a href="gerenciar-materiais.php?edit=<?= $m['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="gerenciar-materiais.php?del=<?= $m['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-danger btn-sm" onclick="return confirm('Deseja arquivar este material?')"><i class="fas fa-archive"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
