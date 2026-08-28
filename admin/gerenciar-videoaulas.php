<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';

// Tratar Ação de Duplicação
if (isset($_GET['duplicate'])) {
    verify_csrf_token();
    $dupId = (int)$_GET['duplicate'];
    $stmtFind = $pdo->prepare("SELECT * FROM free_videos WHERE id = ? LIMIT 1");
    $stmtFind->execute([$dupId]);
    $orig = $stmtFind->fetch();
    
    if ($orig) {
        $newTitle = "Cópia de " . $orig['title'];
        $newSlug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', $orig['slug'])) . '-copia-' . substr(uniqid(), 0, 4);
        
        $stmtDup = $pdo->prepare("INSERT INTO free_videos (channel_id, discipline_id, teacher_id, board_id, contest_id, title, slug, short_description, full_description, thumbnail, youtube_url, youtube_id, category, campaign_code, status, is_featured, order_index, seo_title, seo_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'rascunho', 0, ?, ?, ?)");
        $stmtDup->execute([
            $orig['channel_id'],
            $orig['discipline_id'],
            $orig['teacher_id'],
            $orig['board_id'],
            $orig['contest_id'],
            $newTitle,
            $newSlug,
            $orig['short_description'],
            $orig['full_description'],
            $orig['thumbnail'],
            $orig['youtube_url'],
            $orig['youtube_id'],
            $orig['category'],
            $orig['campaign_code'],
            $orig['order_index'],
            $orig['seo_title'],
            $orig['seo_description']
        ]);
        
        $_SESSION['msg'] = "Videoaula duplicada com sucesso como Rascunho.";
    }
    header("Location: gerenciar-videoaulas.php");
    exit;
}

// Tratar Soft Delete
if (isset($_GET['del'])) {
    verify_csrf_token();
    $id = (int)$_GET['del'];
    $stmt = $pdo->prepare("UPDATE free_videos SET active = 0, deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "Videoaula arquivada com sucesso.";
    header("Location: gerenciar-videoaulas.php");
    exit;
}

// Tratar Restauração
if (isset($_GET['restore'])) {
    verify_csrf_token();
    $id = (int)$_GET['restore'];
    $stmt = $pdo->prepare("UPDATE free_videos SET active = 1, deleted_at = NULL WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['msg'] = "Videoaula restaurada com sucesso.";
    header("Location: gerenciar-videoaulas.php");
    exit;
}

// Salvar / Editar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $channel_id = (int)($_POST['channel_id'] ?? 0);
    $discipline_id = !empty($_POST['discipline_id']) ? (int)$_POST['discipline_id'] : null;
    $teacher_id = !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : null;
    $board_id = !empty($_POST['board_id']) ? (int)$_POST['board_id'] : null;
    $contest_id = !empty($_POST['contest_id']) ? (int)$_POST['contest_id'] : null;
    $category = trim($_POST['category'] ?? '');
    $short_description = trim($_POST['short_description'] ?? '');
    $full_description = trim($_POST['full_description'] ?? '');
    $youtube_url = trim($_POST['youtube_url'] ?? '');
    $youtube_id = extract_youtube_id($youtube_url);
    $campaign_code = trim($_POST['campaign_code'] ?? '');
    $published_at = !empty($_POST['published_at']) ? $_POST['published_at'] : date('Y-m-d H:i:s');
    $status = ($_POST['status'] ?? 'rascunho') === 'publicado' ? 'publicado' : 'rascunho';
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $order_index = (int)($_POST['order_index'] ?? 0);
    $seo_title = trim($_POST['seo_title'] ?? '');
    $seo_description = trim($_POST['seo_description'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;
    
    if (empty($title) || !$channel_id) {
        $_SESSION['erro'] = "Título e Canal são campos obrigatórios.";
        header("Location: gerenciar-videoaulas.php");
        exit;
    }
    
    if (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', $title));
    } else {
        $slug = strtolower(preg_replace('/[^a-z0-9-]+/', '-', $slug));
    }
    
    $stmtSlug = $pdo->prepare("SELECT id FROM free_videos WHERE slug = ? AND id != ? LIMIT 1");
    $stmtSlug->execute([$slug, $id]);
    if ($stmtSlug->fetch()) {
        $_SESSION['erro'] = "O slug '$slug' já pertence a outra videoaula.";
        header("Location: gerenciar-videoaulas.php" . ($id ? "?edit=$id" : ""));
        exit;
    }
    
    // Upload de Thumbnail personalizada
    $thumbnail = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $thumbnail = 'thumb_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], '../uploads/' . $thumbnail);
        }
    }
    
    if ($id > 0) {
        if ($thumbnail) {
            $stmt = $pdo->prepare("UPDATE free_videos SET channel_id=?, discipline_id=?, teacher_id=?, board_id=?, contest_id=?, title=?, slug=?, short_description=?, full_description=?, thumbnail=?, youtube_url=?, youtube_id=?, category=?, campaign_code=?, published_at=?, status=?, is_featured=?, order_index=?, seo_title=?, seo_description=?, active=? WHERE id=?");
            $stmt->execute([$channel_id, $discipline_id, $teacher_id, $board_id, $contest_id, $title, $slug, $short_description, $full_description, $thumbnail, $youtube_url, $youtube_id, $category, $campaign_code, $published_at, $status, $is_featured, $order_index, $seo_title, $seo_description, $active, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE free_videos SET channel_id=?, discipline_id=?, teacher_id=?, board_id=?, contest_id=?, title=?, slug=?, short_description=?, full_description=?, youtube_url=?, youtube_id=?, category=?, campaign_code=?, published_at=?, status=?, is_featured=?, order_index=?, seo_title=?, seo_description=?, active=? WHERE id=?");
            $stmt->execute([$channel_id, $discipline_id, $teacher_id, $board_id, $contest_id, $title, $slug, $short_description, $full_description, $youtube_url, $youtube_id, $category, $campaign_code, $published_at, $status, $is_featured, $order_index, $seo_title, $seo_description, $active, $id]);
        }
        $_SESSION['msg'] = "Videoaula atualizada com sucesso.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO free_videos (channel_id, discipline_id, teacher_id, board_id, contest_id, title, slug, short_description, full_description, thumbnail, youtube_url, youtube_id, category, campaign_code, published_at, status, is_featured, order_index, seo_title, seo_description, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$channel_id, $discipline_id, $teacher_id, $board_id, $contest_id, $title, $slug, $short_description, $full_description, $thumbnail, $youtube_url, $youtube_id, $category, $campaign_code, $published_at, $status, $is_featured, $order_index, $seo_title, $seo_description, $active]);
        $_SESSION['msg'] = "Videoaula cadastrada com sucesso.";
    }
    
    header("Location: gerenciar-videoaulas.php");
    exit;
}

// Carregar Selects
$channelsSelect = $pdo->query("SELECT id, name FROM free_channels WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$disciplinesSelect = $pdo->query("SELECT id, name FROM free_disciplines WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$teachersSelect = $pdo->query("SELECT id, name FROM free_teachers WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$boardsSelect = $pdo->query("SELECT id, name FROM free_boards WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$contestsSelect = $pdo->query("SELECT id, name FROM free_contests WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();

// Edição
$editData = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT * FROM free_videos WHERE id = ? LIMIT 1");
    $stmtEdit->execute([$editId]);
    $editData = $stmtEdit->fetch();
}

// Listagem e Filtros
$search = trim($_GET['q'] ?? '');
$filterChannel = !empty($_GET['channel_id']) ? (int)$_GET['channel_id'] : 0;
$filterStatus = $_GET['status'] ?? '';
$showArchived = isset($_GET['archived']);

$query = "SELECT v.*, c.name as channel_name, b.name as board_name, d.name as discipline_name, t.name as teacher_name 
          FROM free_videos v 
          JOIN free_channels c ON v.channel_id = c.id 
          LEFT JOIN free_boards b ON v.board_id = b.id 
          LEFT JOIN free_disciplines d ON v.discipline_id = d.id 
          LEFT JOIN free_teachers t ON v.teacher_id = t.id 
          WHERE 1=1";
$params = [];

if ($showArchived) {
    $query .= " AND v.deleted_at IS NOT NULL";
} else {
    $query .= " AND v.deleted_at IS NULL";
}

if ($filterChannel > 0) {
    $query .= " AND v.channel_id = ?";
    $params[] = $filterChannel;
}

if (!empty($filterStatus)) {
    $query .= " AND v.status = ?";
    $params[] = $filterStatus;
}

if (!empty($search)) {
    $query .= " AND (v.title LIKE ? OR v.short_description LIKE ? OR v.campaign_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY v.order_index ASC, v.id DESC";
$videos = $pdo->prepare($query);
$videos->execute($params);
$videosList = $videos->fetchAll();

require_once 'includes/header.php';
?>

<h2><i class="fas fa-video"></i> Gerenciamento de Videoaulas</h2>

<div class="card">
    <h3><?= $editData ? 'Editar Videoaula: ' . htmlspecialchars($editData['title']) : 'Cadastrar Nova Videoaula' ?></h3>
    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($editData): ?>
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 2fr 2fr; gap: 1rem;">
            <div class="form-group">
                <label>Título da Videoaula *</label>
                <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($editData['title'] ?? '') ?>" oninput="generateSlug(this.value)">
            </div>
            <div class="form-group">
                <label>Slug (URL Amigável)</label>
                <input type="text" name="slug" id="slugInput" class="form-control" value="<?= htmlspecialchars($editData['slug'] ?? '') ?>">
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem;">
            <div class="form-group">
                <label>Canal *</label>
                <select name="channel_id" class="form-control" required>
                    <option value="">-- Selecionar --</option>
                    <?php foreach ($channelsSelect as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($editData['channel_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Disciplina</label>
                <select name="discipline_id" class="form-control">
                    <option value="">-- Nenhuma --</option>
                    <?php foreach ($disciplinesSelect as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($editData['discipline_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Professor(a)</label>
                <select name="teacher_id" class="form-control">
                    <option value="">-- Nenhum(a) --</option>
                    <?php foreach ($teachersSelect as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= ($editData['teacher_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Banca</label>
                <select name="board_id" class="form-control">
                    <option value="">-- Nenhuma --</option>
                    <?php foreach ($boardsSelect as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= ($editData['board_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Concurso</label>
                <select name="contest_id" class="form-control">
                    <option value="">-- Nenhum --</option>
                    <?php foreach ($contestsSelect as $ct): ?>
                        <option value="<?= $ct['id'] ?>" <?= ($editData['contest_id'] ?? '') == $ct['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ct['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>URL do YouTube</label>
                <input type="url" name="youtube_url" id="ytUrlInput" class="form-control" placeholder="https://www.youtube.com/watch?v=..." value="<?= htmlspecialchars($editData['youtube_url'] ?? '') ?>" onchange="updateYtId(this.value)">
                <small style="color: #666;">ID Extraído: <span id="ytIdSpan" style="font-weight: bold; color: #03045e;"><?= htmlspecialchars($editData['youtube_id'] ?? 'Nenhum') ?></span></small>
            </div>
            <div class="form-group">
                <label>Código da Campanha</label>
                <input type="text" name="campaign_code" class="form-control" placeholder="Ex: ISP-YT-JK-PORT-001" value="<?= htmlspecialchars($editData['campaign_code'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Categoria</label>
                <input type="text" name="category" class="form-control" placeholder="Ex: Questões Comentadas" value="<?= htmlspecialchars($editData['category'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Descrição Curta (Card de Listagem)</label>
            <textarea name="short_description" class="form-control" rows="2"><?= htmlspecialchars($editData['short_description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Descrição Completa (Página Individual da Aula)</label>
            <textarea name="full_description" class="form-control" rows="5"><?= htmlspecialchars($editData['full_description'] ?? '') ?></textarea>
        </div>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem; align-items: center;">
            <div class="form-group">
                <label>Thumbnail Personalizada (Opcional - Usará YouTube por padrão)</label>
                <input type="file" name="thumbnail" class="form-control" accept="image/*">
                <?php if (!empty($editData['thumbnail'])): ?>
                    <small style="display: block;">Personalizada: <?= htmlspecialchars($editData['thumbnail']) ?></small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label>Status da Publicação</label>
                <select name="status" class="form-control">
                    <option value="rascunho" <?= ($editData['status'] ?? 'rascunho') === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
                    <option value="publicado" <?= ($editData['status'] ?? '') === 'publicado' ? 'selected' : '' ?>>Publicado</option>
                </select>
            </div>
            <div class="form-group" style="margin-top: 1.5rem;">
                <label style="font-weight: normal; cursor: pointer; margin-right: 1rem;">
                    <input type="checkbox" name="is_featured" value="1" <?= ($editData['is_featured'] ?? 0) ? 'checked' : '' ?>> Destaque
                </label>
                <label style="font-weight: normal; cursor: pointer;">
                    <input type="checkbox" name="active" value="1" <?= ($editData['active'] ?? 1) ? 'checked' : '' ?>> Ativo
                </label>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-top: 1rem;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>SEO Title</label>
                <input type="text" name="seo_title" class="form-control" placeholder="Título para o Google e Redes Sociais" value="<?= htmlspecialchars($editData['seo_title'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>SEO Description</label>
                <input type="text" name="seo_description" class="form-control" placeholder="Descrição resumida para mecanismos de busca" value="<?= htmlspecialchars($editData['seo_description'] ?? '') ?>">
            </div>
        </div>
        
        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn"><i class="fas fa-save"></i> <?= $editData ? 'Atualizar Videoaula' : 'Cadastrar Videoaula' ?></button>
            <?php if ($editData): ?>
                <a href="gerenciar-videoaulas.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Listagem -->
<div class="card">
    <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
        <form method="GET" style="display: flex; gap: 0.5rem; flex: 1; min-width: 300px;">
            <input type="text" name="q" class="form-control" placeholder="Buscar videoaula..." value="<?= htmlspecialchars($search) ?>">
            <select name="channel_id" class="form-control" style="width: 180px;">
                <option value="">Todos os canais</option>
                <?php foreach ($channelsSelect as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filterChannel == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-control" style="width: 140px;">
                <option value="">Todos status</option>
                <option value="publicado" <?= $filterStatus === 'publicado' ? 'selected' : '' ?>>Publicado</option>
                <option value="rascunho" <?= $filterStatus === 'rascunho' ? 'selected' : '' ?>>Rascunho</option>
            </select>
            <button type="submit" class="btn"><i class="fas fa-filter"></i> Filtrar</button>
        </form>
        <div>
            <?php if ($showArchived): ?>
                <a href="gerenciar-videoaulas.php" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i> Ver Ativas</a>
            <?php else: ?>
                <a href="gerenciar-videoaulas.php?archived=1" class="btn btn-secondary btn-sm"><i class="fas fa-archive"></i> Ver Arquivadas</a>
            <?php endif; ?>
        </div>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th>Thumb</th>
                <th>Título / Slug</th>
                <th>Canal / Taxonomias</th>
                <th>Campanha</th>
                <th>Status</th>
                <th style="width: 180px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($videosList)): ?>
                <tr><td colspan="6" style="text-align: center; color: #888;">Nenhuma videoaula encontrada.</td></tr>
            <?php else: foreach ($videosList as $v): ?>
                <tr>
                    <td>
                        <?php if (!empty($v['thumbnail'])): ?>
                            <img src="/uploads/<?= htmlspecialchars($v['thumbnail']) ?>" style="width: 60px; height: 35px; object-fit: cover; border-radius: 4px;">
                        <?php elseif (!empty($v['youtube_id'])): ?>
                            <img src="https://img.youtube.com/vi/<?= htmlspecialchars($v['youtube_id']) ?>/hqdefault.jpg" style="width: 60px; height: 35px; object-fit: cover; border-radius: 4px;">
                        <?php else: ?>
                            <span style="color: #ccc;"><i class="fas fa-video fa-2x"></i></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($v['title']) ?></strong>
                        <?php if ($v['is_featured']): ?>
                            <span class="badge badge-warning">Destaque</span>
                        <?php endif; ?><br>
                        <small style="color: #666;"><?= htmlspecialchars($v['slug']) ?></small>
                    </td>
                    <td>
                        <span class="badge badge-info"><?= htmlspecialchars($v['channel_name']) ?></span><br>
                        <small style="color: #666;">
                            <?= $v['discipline_name'] ? htmlspecialchars($v['discipline_name']) . ' | ' : '' ?>
                            <?= $v['board_name'] ? htmlspecialchars($v['board_name']) : '' ?>
                        </small>
                    </td>
                    <td><code><?= htmlspecialchars($v['campaign_code'] ?: '-') ?></code></td>
                    <td>
                        <?php if ($v['deleted_at']): ?>
                            <span class="badge badge-danger">Arquivada</span>
                        <?php elseif ($v['status'] === 'publicado'): ?>
                            <span class="badge badge-success">Publicado</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Rascunho</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($v['deleted_at']): ?>
                            <a href="gerenciar-videoaulas.php?restore=<?= $v['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-success btn-sm" onclick="return confirm('Deseja restaurar esta aula?')"><i class="fas fa-trash-restore"></i></a>
                        <?php else: ?>
                            <a href="gerenciar-videoaulas.php?edit=<?= $v['id'] ?>" class="btn btn-warning btn-sm" title="Editar"><i class="fas fa-edit"></i></a>
                            <a href="gerenciar-videoaulas.php?duplicate=<?= $v['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-secondary btn-sm" title="Duplicar" onclick="return confirm('Deseja duplicar esta aula como rascunho?')"><i class="fas fa-copy"></i></a>
                            <a href="gerenciar-videoaulas.php?del=<?= $v['id'] ?>&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-danger btn-sm" title="Arquivar" onclick="return confirm('Deseja arquivar esta aula?')"><i class="fas fa-archive"></i></a>
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

function updateYtId(url) {
    let id = '';
    const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|live\/|shorts\/)([^#\&\?]*).*/;
    const match = url.match(regExp);
    if (match && match[2].length === 11) {
        id = match[2];
    } else if (url.trim().length === 11) {
        id = url.trim();
    }
    document.getElementById('ytIdSpan').innerText = id ? id : 'Inválido / Não extraído';
}
</script>

<?php require_once 'includes/footer.php'; ?>
