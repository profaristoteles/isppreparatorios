<?php
require_once 'auth.php';
require_once '../db_config.php';

// Criar pasta de uploads se não existir
if (!is_dir('../uploads')) { mkdir('../uploads', 0777, true); }

// Deleção
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $pdo->query("DELETE FROM cursos WHERE id = $id");
    $_SESSION['msg'] = "Curso removido.";
    header("Location: gerenciar-cursos.php");
    exit;
}

// Inserção / Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $price = $_POST['price'] ?: 0;
    $duration = $_POST['duration'];
    $modality = $_POST['modality'] ?: 'Presencial e Online';
    $description = $_POST['description'];
    $payment_link = $_POST['payment_link'];
    $info_extra = $_POST['info_extra'];
    $disciplinas = $_POST['disciplinas'];
    $conteudo = $_POST['conteudo'];
    
    // Upload de Imagem
    $thumbnail = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $thumbnail = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], '../uploads/' . $thumbnail);
        }
    }

    if (!empty($_POST['id'])) {
        // Update
        if ($thumbnail) {
            $stmt = $pdo->prepare("UPDATE cursos SET title=?, price=?, duration=?, modality=?, description=?, payment_link=?, info_extra=?, disciplinas=?, conteudo=?, thumbnail=? WHERE id=?");
            $stmt->execute([$title, $price, $duration, $modality, $description, $payment_link, $info_extra, $disciplinas, $conteudo, $thumbnail, $_POST['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE cursos SET title=?, price=?, duration=?, modality=?, description=?, payment_link=?, info_extra=?, disciplinas=?, conteudo=? WHERE id=?");
            $stmt->execute([$title, $price, $duration, $modality, $description, $payment_link, $info_extra, $disciplinas, $conteudo, $_POST['id']]);
        }
        $_SESSION['msg'] = "Curso atualizado.";
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO cursos (title, price, duration, modality, description, payment_link, info_extra, disciplinas, conteudo, thumbnail) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $price, $duration, $modality, $description, $payment_link, $info_extra, $disciplinas, $conteudo, $thumbnail]);
        $_SESSION['msg'] = "Curso adicionado.";
    }
    header("Location: gerenciar-cursos.php");
    exit;
}

$cursos = $pdo->query("SELECT * FROM cursos ORDER BY id DESC")->fetchAll();

require_once 'includes/header.php';
?>

<div class="card">
    <h2>Adicionar / Editar Curso</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" id="curso_id">
        <div class="form-group">
            <label>Título</label>
            <input type="text" name="title" id="curso_title" class="form-control" required>
        </div>
        <div style="display:flex; gap:1rem;">
            <div class="form-group" style="flex:1;">
                <label>Preço (Opcional)</label>
                <input type="number" step="0.01" name="price" id="curso_price" class="form-control">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Duração (Ex: 120h)</label>
                <input type="text" name="duration" id="curso_duration" class="form-control">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Modalidade</label>
                <select name="modality" id="curso_modality" class="form-control">
                    <option value="Presencial e Online">Presencial e Online</option>
                    <option value="100% Online">100% Online</option>
                    <option value="Presencial">Presencial</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Link de Pagamento (Hotmart, Kiwify, etc)</label>
            <input type="url" name="payment_link" id="curso_payment_link" class="form-control" placeholder="https://...">
        </div>
        <div class="form-group">
            <label>Descrição (Sobre o Curso)</label>
            <textarea name="description" id="curso_desc" class="form-control" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label>Informações</label>
            <textarea name="info_extra" id="curso_info_extra" class="form-control" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label>Disciplinas</label>
            <textarea name="disciplinas" id="curso_disciplinas" class="form-control" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label>Conteúdo Programático</label>
            <textarea name="conteudo" id="curso_conteudo" class="form-control" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label>Imagem (Thumbnail)</label>
            <input type="file" name="thumbnail" class="form-control" accept="image/*">
        </div>
        <button type="submit" class="btn">Salvar Curso</button>
        <button type="button" class="btn btn-warning" onclick="resetForm()">Novo</button>
    </form>
</div>

<div class="card">
    <h2>Lista de Cursos</h2>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Imagem</th>
                <th>Título</th>
                <th>Preço</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($cursos as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td>
                    <?php if($c['thumbnail']): ?>
                        <img src="../uploads/<?= $c['thumbnail'] ?>" width="50" height="50" style="object-fit:cover;">
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['title']) ?></td>
                <td>R$ <?= number_format($c['price'], 2, ',', '.') ?></td>
                <td>
                    <button class="btn" onclick='editarCurso(<?= json_encode($c) ?>)'>Editar</button>
                    <a href="?del=<?= $c['id'] ?>" class="btn btn-danger" onclick="return confirm('Tem certeza?')">Excluir</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function editarCurso(curso) {
    document.getElementById('curso_id').value = curso.id;
    document.getElementById('curso_title').value = curso.title;
    document.getElementById('curso_price').value = curso.price;
    document.getElementById('curso_duration').value = curso.duration;
    document.getElementById('curso_modality').value = curso.modality || 'Presencial e Online';
    document.getElementById('curso_desc').value = curso.description || '';
    document.getElementById('curso_payment_link').value = curso.payment_link || '';
    document.getElementById('curso_info_extra').value = curso.info_extra || '';
    document.getElementById('curso_disciplinas').value = curso.disciplinas || '';
    document.getElementById('curso_conteudo').value = curso.conteudo || '';
    window.scrollTo(0,0);
}
function resetForm() {
    document.getElementById('curso_id').value = '';
    document.getElementById('curso_title').value = '';
    document.getElementById('curso_price').value = '';
    document.getElementById('curso_duration').value = '';
    document.getElementById('curso_modality').value = 'Presencial e Online';
    document.getElementById('curso_desc').value = '';
    document.getElementById('curso_payment_link').value = '';
    document.getElementById('curso_info_extra').value = '';
    document.getElementById('curso_disciplinas').value = '';
    document.getElementById('curso_conteudo').value = '';
}
</script>

<?php require_once 'includes/footer.php'; ?>
