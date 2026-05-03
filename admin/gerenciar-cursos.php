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
    $status = $_POST['status'] ?: 'Disponível';
    $image_alt = $_POST['image_alt'];
    
    // Upload de Imagem
    $thumbnail = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $thumbnail = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], '../uploads/' . $thumbnail);
        }
    }

    try {
        if (!empty($_POST['id'])) {
            // Update
            if ($thumbnail) {
                $stmt = $pdo->prepare("UPDATE cursos SET title=?, price=?, duration=?, modality=?, description=?, payment_link=?, info_extra=?, disciplinas=?, conteudo=?, status=?, thumbnail=?, image_alt=? WHERE id=?");
                $stmt->execute([$title, $price, $duration, $modality, $description, $payment_link, $info_extra, $disciplinas, $conteudo, $status, $thumbnail, $image_alt, $_POST['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE cursos SET title=?, price=?, duration=?, modality=?, description=?, payment_link=?, info_extra=?, disciplinas=?, conteudo=?, status=?, image_alt=? WHERE id=?");
                $stmt->execute([$title, $price, $duration, $modality, $description, $payment_link, $info_extra, $disciplinas, $conteudo, $status, $image_alt, $_POST['id']]);
            }
            $_SESSION['msg'] = "Curso atualizado.";
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO cursos (title, price, duration, modality, description, payment_link, info_extra, disciplinas, conteudo, status, thumbnail, image_alt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $price, $duration, $modality, $description, $payment_link, $info_extra, $disciplinas, $conteudo, $status, $thumbnail, $image_alt]);
            $_SESSION['msg'] = "Curso adicionado.";
        }
    } catch (\PDOException $e) {
        if (strpos($e->getMessage(), 'Data too long') !== false) {
            $_SESSION['erro'] = "Erro ao salvar: O texto que você inseriu em um dos campos (ex: Título, Duração ou Modalidade) é muito longo e excedeu o limite máximo de caracteres.";
        } else {
            $_SESSION['erro'] = "Erro no banco de dados: " . $e->getMessage();
        }
    }
    header("Location: gerenciar-cursos.php");
    exit;
}

$cursos = $pdo->query("SELECT * FROM cursos ORDER BY id DESC")->fetchAll();

require_once 'includes/header.php';
?>

<!-- TinyMCE CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>

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
            <div class="form-group" style="flex:1;">
                <label>Status do Curso</label>
                <select name="status" id="curso_status" class="form-control">
                    <option value="Disponível">Disponível</option>
                    <option value="Pegando Reserva">Pegando Reserva (Lista de Espera)</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Link de Pagamento / Formulário de Reserva</label>
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
        <div style="display:flex; gap:1rem;">
            <div class="form-group" style="flex:1;">
                <label>Imagem (Thumbnail)</label>
                <input type="file" name="thumbnail" class="form-control" accept="image/*">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Texto Alternativo da Imagem (SEO)</label>
                <input type="text" name="image_alt" id="curso_image_alt" class="form-control" placeholder="Ex: Capa do curso de Educação Especial">
            </div>
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
                <th>Status</th>
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
                <td>
                    <?php if(($c['status'] ?? 'Disponível') == 'Pegando Reserva'): ?>
                        <span style="background: var(--brand-orange); color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold;">Reserva</span>
                    <?php else: ?>
                        <span style="background: #28a745; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold;">Disponível</span>
                    <?php endif; ?>
                </td>
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
// Inicializa o TinyMCE para os campos de texto do curso
tinymce.init({
    selector: '#curso_desc, #curso_info_extra, #curso_disciplinas, #curso_conteudo',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code preview',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat | code preview',
    height: 300,
    language: 'pt_BR',
    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px; background: #f4f4f4; color: #333; }',
    skin: "oxide-dark",
    content_css: "dark",
    valid_children: "+body[style],+div[style]",
    extended_valid_elements: "style[type|media|scoped]"
});

function editarCurso(curso) {
    document.getElementById('curso_id').value = curso.id;
    document.getElementById('curso_title').value = curso.title;
    document.getElementById('curso_price').value = curso.price;
    document.getElementById('curso_duration').value = curso.duration;
    document.getElementById('curso_modality').value = curso.modality || 'Presencial e Online';
    document.getElementById('curso_payment_link').value = curso.payment_link || '';
    document.getElementById('curso_status').value = curso.status || 'Disponível';
    document.getElementById('curso_image_alt').value = curso.image_alt || '';
    
    const fields = [
        { id: 'curso_desc', content: curso.description },
        { id: 'curso_info_extra', content: curso.info_extra },
        { id: 'curso_disciplinas', content: curso.disciplinas },
        { id: 'curso_conteudo', content: curso.conteudo }
    ];
    
    fields.forEach(f => {
        if (tinymce.get(f.id)) {
            tinymce.get(f.id).setContent(f.content || '');
        } else {
            document.getElementById(f.id).value = f.content || '';
        }
    });

    window.scrollTo(0,0);
}

function resetForm() {
    document.getElementById('curso_id').value = '';
    document.getElementById('curso_title').value = '';
    document.getElementById('curso_price').value = '';
    document.getElementById('curso_duration').value = '';
    document.getElementById('curso_modality').value = 'Presencial e Online';
    document.getElementById('curso_payment_link').value = '';
    document.getElementById('curso_status').value = 'Disponível';
    document.getElementById('curso_image_alt').value = '';
    
    const fields = ['curso_desc', 'curso_info_extra', 'curso_disciplinas', 'curso_conteudo'];
    fields.forEach(id => {
        if (tinymce.get(id)) {
            tinymce.get(id).setContent('');
        } else {
            document.getElementById(id).value = '';
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
