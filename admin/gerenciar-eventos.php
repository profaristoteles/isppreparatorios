<?php
require_once 'auth.php';
require_once '../db_config.php';

// Criar pasta de uploads se não existir
if (!is_dir('../uploads')) { mkdir('../uploads', 0777, true); }

// Deleção
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $pdo->query("DELETE FROM eventos WHERE id = $id");
    $_SESSION['msg'] = "Evento removido.";
    header("Location: gerenciar-eventos.php");
    exit;
}

// Inserção / Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_date = str_replace('T', ' ', $_POST['event_date']) . ':00'; // Formata datetime-local para MySQL DATETIME
    
    // Tratamento da data de término (opcional)
    $event_end_date = !empty($_POST['event_end_date']) ? str_replace('T', ' ', $_POST['event_end_date']) . ':00' : null;
    
    $form_type = $_POST['form_type'];
    $form_link = $_POST['form_link'] ?? '';
    $form_embed = $_POST['form_embed'] ?? '';
    $active = isset($_POST['active']) ? 1 : 0;
    
    // SEO
    $slug = $_POST['slug'] ?? '';
    $meta_title = $_POST['meta_title'] ?? '';
    $meta_description = $_POST['meta_description'] ?? '';
    
    // Upload de Imagem
    $thumbnail = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $thumbnail = uniqid() . '.' . $ext;
            if (!move_uploaded_file($_FILES['thumbnail']['tmp_name'], '../uploads/' . $thumbnail)) {
                $_SESSION['erro'] = "Falha ao salvar a imagem na pasta uploads/. Verifique permissões.";
                $thumbnail = '';
            }
        } else {
            $_SESSION['erro'] = "Formato de imagem inválido.";
        }
    } elseif (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] !== UPLOAD_ERR_NO_FILE) {
        $_SESSION['erro'] = "Erro no upload da imagem: Código " . $_FILES['thumbnail']['error'] . " (Pode ser que a imagem seja muito pesada).";
    }
    
    $video_embed = $_POST['video_embed'] ?? '';

    try {
        if (!empty($_POST['id'])) {
            // Update
            if ($thumbnail) {
        if (!empty($_POST['id'])) {
            // Update
            if ($thumbnail) {
                $stmt = $pdo->prepare("UPDATE eventos SET title=?, description=?, event_date=?, event_end_date=?, form_type=?, form_link=?, form_embed=?, active=?, thumbnail=?, slug=?, meta_title=?, meta_description=?, video_embed=? WHERE id=?");
                $stmt->execute([$title, $description, $event_date, $event_end_date, $form_type, $form_link, $form_embed, $active, $thumbnail, $slug, $meta_title, $meta_description, $video_embed, $_POST['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE eventos SET title=?, description=?, event_date=?, event_end_date=?, form_type=?, form_link=?, form_embed=?, active=?, slug=?, meta_title=?, meta_description=?, video_embed=? WHERE id=?");
                $stmt->execute([$title, $description, $event_date, $event_end_date, $form_type, $form_link, $form_embed, $active, $slug, $meta_title, $meta_description, $video_embed, $_POST['id']]);
            }
            if(!isset($_SESSION['erro'])) $_SESSION['msg'] = "Evento atualizado com sucesso.";
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO eventos (title, description, event_date, event_end_date, form_type, form_link, form_embed, active, thumbnail, slug, meta_title, meta_description, video_embed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $event_date, $event_end_date, $form_type, $form_link, $form_embed, $active, $thumbnail, $slug, $meta_title, $meta_description, $video_embed]);
            if(!isset($_SESSION['erro'])) $_SESSION['msg'] = "Evento adicionado com sucesso.";
        }
    } catch (\PDOException $e) {
        $_SESSION['erro'] = "Erro ao salvar no banco de dados: " . $e->getMessage();
    }
    header("Location: gerenciar-eventos.php");
    exit;
}

$eventos = $pdo->query("SELECT * FROM eventos ORDER BY event_date DESC")->fetchAll();

require_once 'includes/header.php';
?>

<!-- TinyMCE CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>

<div class="card">
    <h2>Adicionar / Editar Evento</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" id="evento_id">
        
        <div class="form-group">
            <label>Título do Evento</label>
            <input type="text" name="title" id="evento_title" class="form-control" required placeholder="Ex: Aula Inaugural - Educação Especial">
        </div>
        
        <div style="display:flex; gap:1rem; flex-wrap: wrap;">
            <div class="form-group" style="flex:1; min-width: 200px;">
                <label>Início do Evento <span style="color:red;">*</span></label>
                <input type="datetime-local" name="event_date" id="evento_date" class="form-control" required>
            </div>
            <div class="form-group" style="flex:1; min-width: 200px;">
                <label>Término do Evento <small style="color:#999;">(Opcional)</small></label>
                <input type="datetime-local" name="event_end_date" id="evento_end_date" class="form-control">
            </div>
            <div class="form-group" style="flex:1; min-width: 200px;">
                <label>Imagem de Capa (Thumbnail)</label>
                <input type="file" name="thumbnail" class="form-control" accept="image/*">
                <small style="color:#999;">Recomendado: Formato quadrado (1:1) ou banner vertical.</small>
            </div>
        </div>

        <div class="form-group">
            <label>Descrição do Evento (Conteúdo para convencer a inscrição)</label>
            <textarea name="description" id="evento_desc" class="form-control" rows="3"></textarea>
        </div>

        <div style="background: rgba(255,165,0,0.05); padding: 1.5rem; border: 1px solid var(--brand-orange); border-radius: 8px; margin-top: 1rem; margin-bottom: 1.5rem;">
            <h3 style="margin-top: 0; color: var(--brand-orange); font-size: 1.1rem; margin-bottom: 1rem;">Captação de Leads (CRM)</h3>
            
            <div class="form-group">
                <label>Tipo de Formulário</label>
                <select name="form_type" id="evento_form_type" class="form-control" onchange="toggleFormFields()">
                    <option value="link">Botão com Link Externo (Redireciona para o CRM)</option>
                    <option value="embed">Código Embutido (Iframe do CRM direto na página)</option>
                </select>
            </div>
            
            <div class="form-group" id="field_form_link">
                <label>Link Externo do CRM <small style="color: #999;">- O aluno será direcionado para cá ao clicar no botão</small></label>
                <input type="url" name="form_link" id="evento_form_link" class="form-control" placeholder="https://...">
            </div>
            
            <div class="form-group" id="field_form_embed" style="display: none;">
                <label>Código Embed (Iframe HTML) <small style="color: #999;">- Cole o código gerado pelo seu CRM</small></label>
                <textarea name="form_embed" id="evento_form_embed" class="form-control" rows="4" placeholder='<iframe src="..."></iframe>' style="font-family: monospace;"></textarea>
            </div>
        </div>

        <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; margin-top: 1rem; margin-bottom: 1.5rem;">
            <h3 style="margin-top: 0; color: var(--brand-orange); font-size: 1.1rem; margin-bottom: 1rem;">Vídeo (Opcional)</h3>
            <div class="form-group">
                <label>Embed do Vídeo (Iframe do YouTube/Vimeo) <small style="color: #999;">- Será exibido na página do evento</small></label>
                <textarea name="video_embed" id="evento_video_embed" class="form-control" rows="3" placeholder='<iframe src="https://www.youtube.com/embed/..." ...></iframe>' style="font-family: monospace;"></textarea>
            </div>
        </div>

        <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; margin-top: 1rem; margin-bottom: 1.5rem;">
            <h3 style="margin-top: 0; color: var(--brand-orange); font-size: 1.1rem; margin-bottom: 1rem;">Configurações de SEO</h3>
            <div class="form-group">
                <label>Slug (URL Amigável) <small style="color: #999;">- Deixe em branco para gerar automaticamente baseado no título</small></label>
                <input type="text" name="slug" id="evento_slug" class="form-control" placeholder="aula-inaugural">
            </div>
            <div class="form-group">
                <label>Meta Title <small style="color: #999;">- Título para o Google e Aba do Navegador (Opcional)</small></label>
                <input type="text" name="meta_title" id="evento_meta_title" class="form-control" placeholder="Ex: Aula Inaugural Gratuita - ISP Preparatórios">
            </div>
            <div class="form-group">
                <label>Meta Description <small style="color: #999;">- Resumo que aparece no Google (Opcional, max 160 caracteres)</small></label>
                <textarea name="meta_description" id="evento_meta_description" class="form-control" rows="2" placeholder="Resumo atrativo para os resultados de busca..."></textarea>
            </div>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="active" id="evento_active" value="1" checked style="transform: scale(1.2); margin-right: 8px;"> 
                <strong>Evento Ativo (Visível no site)</strong> - Desmarque para deixar como rascunho ou ocultar evento passado
            </label>
        </div>

        <button type="submit" class="btn">Salvar Evento</button>
        <button type="button" class="btn btn-warning" onclick="resetForm()">Novo Evento</button>
    </form>
</div>

<div class="card">
    <h2>Lista de Eventos</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Imagem</th>
                <th>Título</th>
                <th>Data do Evento</th>
                <th>Visibilidade</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($eventos as $e): ?>
            <tr>
                <td>
                    <?php if($e['thumbnail']): ?>
                        <img src="../uploads/<?= $e['thumbnail'] ?>" width="50" height="50" style="object-fit:cover; border-radius: 4px;">
                    <?php else: ?>
                        <div style="width: 50px; height: 50px; background: #eee; border-radius: 4px; display: grid; place-items: center; font-size: 0.7em; color: #999;">Sem IMG</div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($e['title']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($e['event_date'])) ?></td>
                <td>
                    <?php if($e['active'] == 0): ?>
                        <span style="background: #dc3545; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold;">Inativo</span>
                    <?php else: ?>
                        <span style="background: #17a2b8; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold;">Publicado</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn" onclick="editarEvento(<?= htmlspecialchars(json_encode($e), ENT_QUOTES, 'UTF-8') ?>)">Editar</button>
                    <a href="?del=<?= $e['id'] ?>" class="btn btn-danger" onclick="return confirm('Tem certeza?')">Excluir</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
// Toggle dos campos de CRM
function toggleFormFields() {
    const type = document.getElementById('evento_form_type').value;
    if(type === 'embed') {
        document.getElementById('field_form_link').style.display = 'none';
        document.getElementById('field_form_embed').style.display = 'block';
    } else {
        document.getElementById('field_form_link').style.display = 'block';
        document.getElementById('field_form_embed').style.display = 'none';
    }
}

// Inicializa o TinyMCE
tinymce.init({
    selector: '#evento_desc',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline | link image media | align lineheight | numlist bullist | emoticons charmap | removeformat',
    height: 300,
    language: 'pt_BR',
    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px; background: #f4f4f4; color: #333; }',
    skin: "oxide-dark",
    content_css: "dark",
});

function editarEvento(evento) {
    document.getElementById('evento_id').value = evento.id;
    document.getElementById('evento_title').value = evento.title;
    
    // Formatar data para datetime-local (Y-m-dTH:i)
    if(evento.event_date) {
        const dateStr = evento.event_date.replace(' ', 'T').substring(0, 16);
        document.getElementById('evento_date').value = dateStr;
    }
    
    if(evento.event_end_date) {
        const endDateStr = evento.event_end_date.replace(' ', 'T').substring(0, 16);
        document.getElementById('evento_end_date').value = endDateStr;
    } else {
        document.getElementById('evento_end_date').value = '';
    }
    
    document.getElementById('evento_form_type').value = evento.form_type || 'link';
    document.getElementById('evento_form_link').value = evento.form_link || '';
    document.getElementById('evento_form_embed').value = evento.form_embed || '';
    document.getElementById('evento_active').checked = evento.active != 0;
    
    document.getElementById('evento_slug').value = evento.slug || '';
    document.getElementById('evento_meta_title').value = evento.meta_title || '';
    document.getElementById('evento_meta_description').value = evento.meta_description || '';
    document.getElementById('evento_video_embed').value = evento.video_embed || '';
    
    if (tinymce.get('evento_desc')) {
        tinymce.get('evento_desc').setContent(evento.description || '');
    } else {
        document.getElementById('evento_desc').value = evento.description || '';
    }
    
    toggleFormFields();
    window.scrollTo(0,0);
}

function generateSlug(text) {
    return text.toString().toLowerCase().trim()
        .replace(/[áàãâä]/g, 'a')
        .replace(/[éèêë]/g, 'e')
        .replace(/[íìîï]/g, 'i')
        .replace(/[óòõôö]/g, 'o')
        .replace(/[úùûü]/g, 'u')
        .replace(/[ç]/g, 'c')
        .replace(/[ñ]/g, 'n')
        .replace(/[\s\W-]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

document.querySelector('form').addEventListener('submit', function(e) {
    const titleInput = document.getElementById('evento_title');
    const slugInput = document.getElementById('evento_slug');
    if (!slugInput.value.trim() && titleInput.value.trim()) {
        slugInput.value = generateSlug(titleInput.value);
    }
});

function resetForm() {
    document.getElementById('evento_id').value = '';
    document.getElementById('evento_title').value = '';
    document.getElementById('evento_date').value = '';
    document.getElementById('evento_end_date').value = '';
    document.getElementById('evento_form_type').value = 'link';
    document.getElementById('evento_form_link').value = '';
    document.getElementById('evento_form_embed').value = '';
    document.getElementById('evento_active').checked = true;
    
    document.getElementById('evento_slug').value = '';
    document.getElementById('evento_meta_title').value = '';
    document.getElementById('evento_meta_description').value = '';
    document.getElementById('evento_video_embed').value = '';
    
    if (tinymce.get('evento_desc')) {
        tinymce.get('evento_desc').setContent('');
    } else {
        document.getElementById('evento_desc').value = '';
    }
    
    toggleFormFields();
}
</script>

<?php require_once 'includes/footer.php'; ?>
