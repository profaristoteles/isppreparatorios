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
    $category = $_POST['category'] ?? '';
    $price = $_POST['price'] ?: 0;
    $duration = $_POST['duration'];
    $modality = $_POST['modality'] ?: 'Presencial e Online';
    $active = isset($_POST['active']) ? 1 : 0;
    $description = $_POST['description'];
    $features = $_POST['features'] ?? '';
    $payment_link = $_POST['payment_link'];
    $payment_methods = $_POST['payment_methods'] ?? '';
    $info_extra = $_POST['info_extra'] ?? '';
    $disciplinas = $_POST['disciplinas'] ?? '';
    $conteudo = $_POST['conteudo'] ?? '';
    $status = $_POST['status'] ?: 'Disponível';
    $image_alt = $_POST['image_alt'];
    
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
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], '../uploads/' . $thumbnail);
        }
    }

    try {
        if (!empty($_POST['id'])) {
            // Update
            if ($thumbnail) {
                $stmt = $pdo->prepare("UPDATE cursos SET title=?, category=?, price=?, duration=?, modality=?, active=?, description=?, features=?, payment_link=?, payment_methods=?, info_extra=?, disciplinas=?, conteudo=?, status=?, thumbnail=?, image_alt=?, slug=?, meta_title=?, meta_description=? WHERE id=?");
                $stmt->execute([$title, $category, $price, $duration, $modality, $active, $description, $features, $payment_link, $payment_methods, $info_extra, $disciplinas, $conteudo, $status, $thumbnail, $image_alt, $slug, $meta_title, $meta_description, $_POST['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE cursos SET title=?, category=?, price=?, duration=?, modality=?, active=?, description=?, features=?, payment_link=?, payment_methods=?, info_extra=?, disciplinas=?, conteudo=?, status=?, image_alt=?, slug=?, meta_title=?, meta_description=? WHERE id=?");
                $stmt->execute([$title, $category, $price, $duration, $modality, $active, $description, $features, $payment_link, $payment_methods, $info_extra, $disciplinas, $conteudo, $status, $image_alt, $slug, $meta_title, $meta_description, $_POST['id']]);
            }
            $_SESSION['msg'] = "Curso atualizado.";
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO cursos (title, category, price, duration, modality, active, description, features, payment_link, payment_methods, info_extra, disciplinas, conteudo, status, thumbnail, image_alt, slug, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $category, $price, $duration, $modality, $active, $description, $features, $payment_link, $payment_methods, $info_extra, $disciplinas, $conteudo, $status, $thumbnail, $image_alt, $slug, $meta_title, $meta_description]);
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
        <div style="display:flex; gap:1rem;">
            <div class="form-group" style="flex:2;">
                <label>Título do Curso</label>
                <input type="text" name="title" id="curso_title" class="form-control" required>
            </div>
            <div class="form-group" style="flex:1;">
                <label>Subtítulo / Categoria <small style="color: #999;">(Opcional)</small></label>
                <input type="text" name="category" id="curso_category" class="form-control" placeholder="Ex: Preparatório ENEM, Pós-Graduação">
            </div>
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

        <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; margin-top: 1rem; margin-bottom: 1.5rem;">
            <h3 style="margin-top: 0; color: var(--brand-orange); font-size: 1.1rem; margin-bottom: 1rem;">O que Inclui &amp; Formas de Pagamento (Caixa de Venda)</h3>
            <div class="form-group">
                <label>O que inclui no Curso (Recursos na caixa de investimento) <small style="color: #999;">- Digite um item por linha</small></label>
                <textarea name="features" id="curso_features" class="form-control" rows="4" placeholder="Acesso Imediato&#10;Material em PDF&#10;Simulados&#10;Suporte"></textarea>
            </div>
            <div class="form-group">
                <label>Formas de Pagamento Aceitas <small style="color: #999;">- Digite separando por vírgulas ou marque os atalhos abaixo</small></label>
                <div style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom: 10px;">
                    <label style="font-weight:normal; cursor:pointer; background: rgba(255,255,255,0.05); padding: 6px 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1);"><input type="checkbox" class="pm-chk" value="Cartão de Crédito" onchange="togglePaymentMethod('Cartão de Crédito', this.checked)"> 💳 Cartão de Crédito</label>
                    <label style="font-weight:normal; cursor:pointer; background: rgba(255,255,255,0.05); padding: 6px 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1);"><input type="checkbox" class="pm-chk" value="Pix" onchange="togglePaymentMethod('Pix', this.checked)"> ⚡ Pix</label>
                    <label style="font-weight:normal; cursor:pointer; background: rgba(255,255,255,0.05); padding: 6px 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1);"><input type="checkbox" class="pm-chk" value="Boleto Bancário" onchange="togglePaymentMethod('Boleto Bancário', this.checked)"> 📄 Boleto Bancário</label>
                    <label style="font-weight:normal; cursor:pointer; background: rgba(255,255,255,0.05); padding: 6px 12px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1);"><input type="checkbox" class="pm-chk" value="Assinatura Recorrente" onchange="togglePaymentMethod('Assinatura Recorrente', this.checked)"> 🔄 Assinatura Recorrente</label>
                </div>
                <input type="text" name="payment_methods" id="curso_payment_methods" class="form-control" placeholder="Ex: Cartão de Crédito, Pix, Boleto Bancário, Assinatura Recorrente" oninput="syncCheckboxesFromInput()">
            </div>
        </div>

        <div style="display:flex; gap:1rem;">
            <div class="form-group" style="flex:1;">
                <label>Imagem (Thumbnail)</label>
                <input type="file" name="thumbnail" class="form-control" accept="image/*">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Texto Alternativo da Imagem (SEO)</label>
                <input type="text" name="image_alt" id="curso_image_alt" class="form-control" placeholder="Ex: Capa do curso">
            </div>
        </div>

        <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; margin-top: 1rem; margin-bottom: 1.5rem;">
            <h3 style="margin-top: 0; color: var(--brand-orange); font-size: 1.1rem; margin-bottom: 1rem;">Configurações de SEO</h3>
            <div class="form-group">
                <label>Slug (URL Amigável) <small style="color: #999;">- Deixe em branco para gerar automaticamente baseado no título</small></label>
                <input type="text" name="slug" id="curso_slug" class="form-control" placeholder="exemplo-de-curso">
            </div>
            <div class="form-group">
                <label>Meta Title <small style="color: #999;">- Título para o Google e Aba do Navegador (Opcional)</small></label>
                <input type="text" name="meta_title" id="curso_meta_title" class="form-control" placeholder="Ex: Curso Completo - ISP Preparatórios">
            </div>
            <div class="form-group">
                <label>Meta Description <small style="color: #999;">- Resumo que aparece no Google (Opcional, max 160 caracteres)</small></label>
                <textarea name="meta_description" id="curso_meta_description" class="form-control" rows="2" placeholder="Resumo atrativo para os resultados de busca..."></textarea>
            </div>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="active" id="curso_active" value="1" checked style="transform: scale(1.2); margin-right: 8px;"> 
                <strong>Curso Ativo (Visível no site)</strong> - Desmarque para deixar como rascunho
            </label>
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
                <th>Status (Vagas)</th>
                <th>Visibilidade</th>
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
                <td>
                    <?php if(isset($c['active']) && $c['active'] == 0): ?>
                        <span style="background: #dc3545; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold;">Inativo (Rascunho)</span>
                    <?php else: ?>
                        <span style="background: #17a2b8; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold;">Publicado</span>
                    <?php endif; ?>
                </td>
                <td>R$ <?= number_format($c['price'], 2, ',', '.') ?></td>
                <td>
                    <button class="btn" onclick="editarCurso(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>)">Editar</button>
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
    selector: '#curso_desc',
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

function togglePaymentMethod(method, checked) {
    let input = document.getElementById('curso_payment_methods');
    let current = input.value.split(',').map(s => s.trim()).filter(Boolean);
    if (checked) {
        if (!current.includes(method)) current.push(method);
    } else {
        current = current.filter(m => m !== method);
    }
    input.value = current.join(', ');
}

function syncCheckboxesFromInput() {
    let input = document.getElementById('curso_payment_methods').value.toLowerCase();
    document.querySelectorAll('.pm-chk').forEach(chk => {
        chk.checked = input.includes(chk.value.toLowerCase());
    });
}

function editarCurso(curso) {
    document.getElementById('curso_id').value = curso.id;
    document.getElementById('curso_title').value = curso.title;
    document.getElementById('curso_category').value = curso.category || '';
    document.getElementById('curso_price').value = curso.price;
    document.getElementById('curso_duration').value = curso.duration;
    document.getElementById('curso_modality').value = curso.modality || 'Presencial e Online';
    document.getElementById('curso_active').checked = curso.active != 0;
    document.getElementById('curso_features').value = curso.features || '';
    document.getElementById('curso_payment_link').value = curso.payment_link || '';
    document.getElementById('curso_payment_methods').value = curso.payment_methods || '';
    syncCheckboxesFromInput();

    document.getElementById('curso_status').value = curso.status || 'Disponível';
    document.getElementById('curso_image_alt').value = curso.image_alt || '';
    
    document.getElementById('curso_slug').value = curso.slug || '';
    document.getElementById('curso_meta_title').value = curso.meta_title || '';
    document.getElementById('curso_meta_description').value = curso.meta_description || '';
    
    const fields = [
        { id: 'curso_desc', content: curso.description }
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
    const titleInput = document.getElementById('curso_title');
    const slugInput = document.getElementById('curso_slug');
    if (!slugInput.value.trim() && titleInput.value.trim()) {
        slugInput.value = generateSlug(titleInput.value);
    }
});

function resetForm() {
    document.getElementById('curso_id').value = '';
    document.getElementById('curso_title').value = '';
    document.getElementById('curso_category').value = '';
    document.getElementById('curso_price').value = '';
    document.getElementById('curso_duration').value = '';
    document.getElementById('curso_modality').value = 'Presencial e Online';
    document.getElementById('curso_active').checked = true;
    document.getElementById('curso_features').value = '';
    document.getElementById('curso_payment_link').value = '';
    document.getElementById('curso_payment_methods').value = '';
    syncCheckboxesFromInput();
    document.getElementById('curso_status').value = 'Disponível';
    document.getElementById('curso_image_alt').value = '';
    
    document.getElementById('curso_slug').value = '';
    document.getElementById('curso_meta_title').value = '';
    document.getElementById('curso_meta_description').value = '';
    
    const fields = ['curso_desc'];
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
