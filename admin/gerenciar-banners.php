<?php
require_once 'auth.php';
require_once '../db_config.php';

if (!is_dir('../uploads')) { mkdir('../uploads', 0777, true); }

if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $pdo->query("DELETE FROM banners WHERE id = $id");
    $_SESSION['msg'] = "Banner removido.";
    header("Location: gerenciar-banners.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'];
    $link = $_POST['link'];
    $order_index = (int)$_POST['order_index'];

    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $image = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $image);
        }
    }

    if (!empty($_POST['id'])) {
        if ($image) {
            $stmt = $pdo->prepare("UPDATE banners SET title=?, subtitle=?, link=?, order_index=?, image=? WHERE id=?");
            $stmt->execute([$title, $subtitle, $link, $order_index, $image, $_POST['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE banners SET title=?, subtitle=?, link=?, order_index=? WHERE id=?");
            $stmt->execute([$title, $subtitle, $link, $order_index, $_POST['id']]);
        }
    } else {
        if ($image) {
            $stmt = $pdo->prepare("INSERT INTO banners (title, subtitle, link, order_index, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $subtitle, $link, $order_index, $image]);
        } else {
            $_SESSION['erro'] = "A imagem é obrigatória para novos banners.";
            header("Location: gerenciar-banners.php");
            exit;
        }
    }
    $_SESSION['msg'] = "Banner salvo com sucesso.";
    header("Location: gerenciar-banners.php");
    exit;
}

$banners = $pdo->query("SELECT * FROM banners ORDER BY order_index ASC")->fetchAll();
require_once 'includes/header.php';
?>

<div class="card">
    <h2>Gerenciar Banners (Hero Section)</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" id="banner_id">
        <div class="form-group">
            <label>Título principal</label>
            <input type="text" name="title" id="banner_title" class="form-control">
        </div>
        <div class="form-group">
            <label>Subtítulo</label>
            <input type="text" name="subtitle" id="banner_subtitle" class="form-control">
        </div>
        <div style="display:flex; gap:1rem;">
            <div class="form-group" style="flex:1;">
                <label>Link do Botão</label>
                <input type="text" name="link" id="banner_link" class="form-control" placeholder="Ex: cursos.php">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Ordem</label>
                <input type="number" name="order_index" id="banner_order" class="form-control" value="0">
            </div>
        </div>
        <div class="form-group">
            <label>Imagem de Fundo (1920x600 recomendado)</label>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>
        <button type="submit" class="btn">Salvar Banner</button>
        <button type="button" class="btn btn-warning" onclick="resetForm()">Novo</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <tr>
            <th>Ordem</th>
            <th>Imagem</th>
            <th>Título</th>
            <th>Ações</th>
        </tr>
        <?php foreach($banners as $b): ?>
        <tr>
            <td><?= $b['order_index'] ?></td>
            <td><img src="../uploads/<?= $b['image'] ?>" height="50"></td>
            <td><?= htmlspecialchars($b['title']) ?></td>
            <td>
                <button class="btn" onclick='editarBanner(<?= json_encode($b) ?>)'>Editar</button>
                <a href="?del=<?= $b['id'] ?>" class="btn btn-danger" onclick="return confirm('Excluir?')">Excluir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
function editarBanner(b) {
    document.getElementById('banner_id').value = b.id;
    document.getElementById('banner_title').value = b.title;
    document.getElementById('banner_subtitle').value = b.subtitle;
    document.getElementById('banner_link').value = b.link;
    document.getElementById('banner_order').value = b.order_index;
    window.scrollTo(0,0);
}
function resetForm() {
    document.getElementById('banner_id').value = '';
    document.getElementById('banner_title').value = '';
    document.getElementById('banner_subtitle').value = '';
    document.getElementById('banner_link').value = '';
    document.getElementById('banner_order').value = '0';
}
</script>
<?php require_once 'includes/footer.php'; ?>
