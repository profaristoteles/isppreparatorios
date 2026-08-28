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
    $_SESSION['msg'] = "Material arquivado com sucesso.";
    header("Location: gerenciar-materiais.php");
    exit;
}

// Tratar Restauração
if (isset($_GET['restore'])) {
    verify_csrf_token();
    $id = (int)$_GET['restore'];
    $stmt = $pdo->prepare("UPDATE free_materials SET active = 1, deleted_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "Material restaurado com sucesso.";
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
    $material_type = trim($_POST['material_type'] ?? 'file');
    $external_url = trim($_POST['external_url'] ?? '');
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
    
    if ($material_type === 'external_url') {
        if (empty($external_url) || !filter_var($external_url, FILTER_VALIDATE_URL)) {
            $_SESSION['erro'] = "Informe uma URL válida do Google Drive ou link externo.";
            header("Location: gerenciar-materiais.php" . ($id ? "?edit=$id" : ""));
            exit;
        }
        $original_filename = 'Link do Google Drive';
    } else {
        // Tipo 'file' (Upload local no servidor)
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
            $_SESSION['erro'] = "Selecione um arquivo PDF para upload ou informe o link do Google Drive.";
            header("Location: gerenciar-materiais.php");
            exit;
        }
    }
    
    if ($id > 0) {
        if ($material_type === 'external_url') {
            $stmt = $pdo->prepare("UPDATE free_materials SET video_id=?, title=?, description=?, external_url=?, material_type=?, original_filename=?, active=? WHERE id=?");
            $stmt->execute([$video_id, $title, $description, $external_url, 'external_url', $original_filename, $active, $id]);
        } elseif ($file_path) {
            $stmt = $pdo->prepare("UPDATE free_materials SET video_id=?, title=?, description=?, file_path=?, material_type=?, original_filename=?, mime_type=?, file_size=?, active=? WHERE id=?");
            $stmt->execute([$video_id, $title, $description, $file_path, 'file', $original_filename, $mime_type, $file_size, $active, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE free_materials SET video_id=?, title=?, description=?, material_type=?, active=? WHERE id=?");
            $stmt->execute([$video_id, $title, $description, $material_type, $active, $id]);
        }
        
        $_SESSION['msg'] = "Material atualizado com sucesso.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO free_materials (video_id, title, description, file_path, external_url, material_type, original_filename, mime_type, file_size, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$video_id, $title, $description, $file_path, $external_url, $material_type, $original_filename, $mime_type, $file_size, $active]);
        
        $_SESSION['msg'] = "Material cadastrado com sucesso.";
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
    $query .= " AND (m.title LIKE ? OR m.original_filename LIKE ? OR m.external_url LIKE ? OR v.title LIKE ?)";
    $params[] = "%$search%";
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

<h2><i class="fas fa-file-pdf"></i> Gerenciamento de Materiais Gratuitos (PDF / Google Drive)</h2>

<div class="card">
    <h3><?= $editData ? 'Editar Material: ' . htmlspecialchars($editData['title']) : 'Cadastrar Novo Material (PDF ou Google Drive)' ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($editData): ?>
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 2fr 2fr; gap: 1rem; margin-bottom: 1rem;">
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

        <!-- Opção de Tipo de Origem do Material (Upload VPS vs Google Drive URL) -->
        <div class="form-group" style="background: rgba(0,0,0,0.03); padding: 1rem; border-radius: 6px; border: 1px solid #eee; margin-bottom: 1rem;">
            <label style="font-weight: bold; margin-bottom: 0.5rem; display: block;">Origem do Arquivo:</label>
            <div style="display: flex; gap: 2rem; align-items: center;">
                <label style="cursor: pointer;">
                    <input type="radio" name="material_type" value="file" id="type-file" <?= ($editData['material_type'] ?? 'file') === 'file' ? 'checked' : '' ?> onchange="toggleMaterialSource()"> 📁 Upload PDF no Servidor (VPS)
                </label>
                <label style="cursor: pointer;">
                    <input type="radio" name="material_type" value="external_url" id="type-url" <?= ($editData['material_type'] ?? '') === 'external_url' ? 'checked' : '' ?> onchange="toggleMaterialSource()"> 🔗 Link do Google Drive / Nuvem
                </label>
            </div>
        </div>

        <div id="source-file-box" class="form-group" style="display: <?= ($editData['material_type'] ?? 'file') === 'file' ? 'block' : 'none' ?>;">
            <label>Arquivo PDF Local <?= $editData ? '(Deixe em branco para manter o arquivo atual)' : '' ?></label>
            <input type="file" name="pdf_file" class="form-control" accept="application/pdf,.pdf">
            <?php if (!empty($editData['original_filename']) && $editData['material_type'] === 'file'): ?>
                <small style="display: block; margin-top: 0.3rem;">Arquivo Atual: <strong><?= htmlspecialchars($editData['original_filename']) ?></strong> (<?= round($editData['file_size'] / 1024, 1) ?> KB)</small>
            <?php endif; ?>
        </div>

        <div id="source-url-box" class="form-group" style="display: <?= ($editData['material_type'] ?? '') === 'external_url' ? 'block' : 'none' ?>;">
            <label>Link / URL do Google Drive ou Nuvem *</label>
            <input type="url" name="external_url" class="form-control" placeholder="Ex: https://drive.google.com/file/d/1A2B3C.../view" value="<?= htmlspecialchars($editData['external_url'] ?? '') ?>">
            <small style="color: #666; display: block; margin-top: 0.3rem;">
                Insira o link de compartilhamento do Google Drive, Dropbox ou OneDrive. O sistema capturará o lead normalmente e redirecionará com segurança para o link.
            </small>
        </div>

        <div class="form-group" style="margin-top: 1rem;">
            <label style="font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="active" value="1" <?= ($editData['active'] ?? 1) ? 'checked' : '' ?>> Material Ativo
            </label>
        </div>
        
        <div style="margin-top: 1rem;">
            <button type="submit" class="btn"><i class="fas fa-save"></i> <?= $editData ? 'Atualizar Material' : 'Cadastrar Material' ?></button>
            <?php if ($editData): ?>
                <a href="gerenciar-materiais.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
function toggleMaterialSource() {
    const isUrl = document.getElementById('type-url').checked;
    document.getElementById('source-file-box').style.display = isUrl ? 'none' : 'block';
    document.getElementById('source-url-box').style.display = isUrl ? 'block' : 'none';
}
</script>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <form method="GET" style="display: flex; gap: 0.5rem; flex: 1; max-width: 600px;">
            <input type="text" name="q" class="form-control" placeholder="Buscar por título ou link..." value="<?= htmlspecialchars($search) ?>">
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
                <th>Título / Origem</th>
                <th>Videoaula Associada</th>
                <th>Tipo</th>
                <th>Status</th>
                <th style="width: 150px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($materialsList)): ?>
                <tr><td colspan="5" style="text-align: center; color: #888;">Nenhum material cadastrado.</td></tr>
            <?php else: foreach ($materialsList as $m): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($m['title']) ?></strong><br>
                        <?php if ($m['material_type'] === 'external_url' || !empty($m['external_url'])): ?>
                            <small><a href="<?= htmlspecialchars($m['external_url']) ?>" target="_blank" style="color: #28a745;"><i class="fab fa-google-drive"></i> <?= htmlspecialchars(mb_strimwidth($m['external_url'], 0, 45, '...')) ?></a></small>
                        <?php else: ?>
                            <small style="color: #666;"><i class="fas fa-file-pdf text-danger"></i> <?= htmlspecialchars($m['original_filename']) ?> (<?= round($m['file_size'] / 1024, 1) ?> KB)</small>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($m['video_title']) ?></td>
                    <td>
                        <?php if ($m['material_type'] === 'external_url' || !empty($m['external_url'])): ?>
                            <span class="badge badge-info"><i class="fab fa-google-drive"></i> Google Drive</span>
                        <?php else: ?>
                            <span class="badge badge-primary"><i class="fas fa-file-pdf"></i> Arquivo VPS</span>
                        <?php endif; ?>
                    </td>
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
