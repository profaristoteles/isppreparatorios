<?php
require_once 'auth.php';
require_once '../db_config.php';

if (!is_dir('../uploads')) { mkdir('../uploads', 0777, true); }

$defaultSec3Text = 'A resolução focada de questões e o estudo direcionado através de mapas e resumos otimizados são fundamentais para garantir sua fixação de conteúdo na reta final.';
$defaultSec3Bullets = "Questões selecionadas e comentadas por especialistas.\nResumos objetivos para leitura rápida e revisão.\nFormato amigável e direto ao ponto que as bancas cobram.";

if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $pdo->query("DELETE FROM apostilas WHERE id = $id");
    $_SESSION['msg'] = "Apostila removida.";
    header("Location: gerenciar-apostilas.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $payment_link = $_POST['payment_link'];
    $active = isset($_POST['active']) ? 1 : 0;
    
    $price = $_POST['price'] ?: 0;
    $subtitle = $_POST['subtitle'] ?: '';
    $is_internal = isset($_POST['is_internal']) ? 1 : 0;
    $topics = $_POST['topics'] ?: '';
    
    $hero_btn_text = $_POST['hero_btn_text'] ?: 'Garantir Meu Material';
    $sec1_title = $_POST['sec1_title'] ?: 'O que você vai encontrar no material?';
    $sec1_btn_text = $_POST['sec1_btn_text'] ?: 'Quero Ter Acesso Agora';
    $sec2_title = $_POST['sec2_title'] ?: 'Veja o Material por Dentro';
    $sec3_title = $_POST['sec3_title'] ?: 'Acelerando sua Aprovação';
    $sec3_text = $_POST['sec3_text'] ?: $defaultSec3Text;
    $sec3_bullets = $_POST['sec3_bullets'] ?: $defaultSec3Bullets;
    $sec3_btn_text = $_POST['sec3_btn_text'] ?: 'Comprar Agora';
    $video_title = $_POST['video_title'] ?: '';
    $video_url = $_POST['video_url'] ?: '';
    $image_alt = $_POST['image_alt'] ?: '';
    $extra_sections = $_POST['extra_sections'] ?: '[]';

    // SEO
    $slug = $_POST['slug'] ?? '';
    $meta_title = $_POST['meta_title'] ?? '';
    $meta_description = $_POST['meta_description'] ?? '';

    $cover = '';
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $cover = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['cover_image']['tmp_name'], '../uploads/' . $cover);
        }
    }

    $preview_str = '';
    if (isset($_FILES['preview_images']) && !empty($_FILES['preview_images']['name'][0])) {
        $arr = [];
        foreach($_FILES['preview_images']['tmp_name'] as $key => $tmp_name) {
            if($_FILES['preview_images']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['preview_images']['name'][$key], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $fname = uniqid() . '_prev.' . $ext;
                    move_uploaded_file($tmp_name, '../uploads/' . $fname);
                    $arr[] = $fname;
                }
            }
        }
        if(!empty($arr)) {
            $preview_str = implode(',', $arr);
        }
    }

    if (!empty($_POST['id'])) {
        $id = $_POST['id'];
        
        $sql = "UPDATE apostilas SET title=?, payment_link=?, active=?, price=?, subtitle=?, is_internal=?, topics=?, hero_btn_text=?, sec1_title=?, sec1_btn_text=?, sec2_title=?, sec3_title=?, sec3_text=?, sec3_bullets=?, sec3_btn_text=?, video_title=?, video_url=?, image_alt=?, extra_sections=?, slug=?, meta_title=?, meta_description=?";
        $params = [$title, $payment_link, $active, $price, $subtitle, $is_internal, $topics, $hero_btn_text, $sec1_title, $sec1_btn_text, $sec2_title, $sec3_title, $sec3_text, $sec3_bullets, $sec3_btn_text, $video_title, $video_url, $image_alt, $extra_sections, $slug, $meta_title, $meta_description];
        
        if ($cover) {
            $sql .= ", cover_image=?";
            $params[] = $cover;
        }
        if ($preview_str) {
            $sql .= ", preview_images=?";
            $params[] = $preview_str;
        }
        
        $sql .= " WHERE id=?";
        $params[] = $id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare("INSERT INTO apostilas (title, payment_link, cover_image, active, price, subtitle, is_internal, topics, preview_images, hero_btn_text, sec1_title, sec1_btn_text, sec2_title, sec3_title, sec3_text, sec3_bullets, sec3_btn_text, video_title, video_url, image_alt, extra_sections, slug, meta_title, meta_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $payment_link, $cover, $active, $price, $subtitle, $is_internal, $topics, $preview_str, $hero_btn_text, $sec1_title, $sec1_btn_text, $sec2_title, $sec3_title, $sec3_text, $sec3_bullets, $sec3_btn_text, $video_title, $video_url, $image_alt, $extra_sections, $slug, $meta_title, $meta_description]);
    }
    $_SESSION['msg'] = "Apostila salva com sucesso.";
    header("Location: gerenciar-apostilas.php");
    exit;
}

$apostilas = $pdo->query("SELECT * FROM apostilas ORDER BY id DESC")->fetchAll();
require_once 'includes/header.php';
?>

<style>
    .apostila-admin-note {
        background: #eef4ff;
        border: 1px solid #cbd8ff;
        border-left: 5px solid #03045e;
        border-radius: 6px;
        color: #172033;
        margin: 1rem 0 1.5rem;
        padding: 1rem 1.25rem;
    }
    .apostila-admin-note strong {
        display: block;
        margin-bottom: .35rem;
    }
    .apostila-section-heading {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        color: #03045e;
        font-size: 1rem;
        margin: 2rem 0 1rem;
        padding: .8rem 1rem;
    }
    .apostila-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    @media (max-width: 900px) {
        .apostila-row {
            display: block;
        }
    }
</style>

<div class="card">
    <h2>Gerenciar Apostilas</h2>
    <div class="apostila-admin-note">
        <strong>Fluxo recomendado</strong>
        Cadastre título, preço e link de compra. Marque "Apostila Própria (ISP)" para criar uma página de venda editável com tópicos, vídeo, imagens e chamada final.
    </div>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" id="apo_id">
        
        <h3 class="apostila-section-heading">1. Informações principais</h3>
        <div class="apostila-row">
            <div class="form-group" style="flex:2;">
                <label>Título da Apostila</label>
                <input type="text" name="title" id="apo_title" class="form-control" required>
            </div>
            <div class="form-group" style="flex:1;">
                <label>Preço</label>
                <input type="number" step="0.01" name="price" id="apo_price" class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label>Link de Compra / Pagamento / Link Afiliado</label>
            <input type="url" name="payment_link" id="apo_payment_link" class="form-control" required>
        </div>

        <div class="form-group" style="background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 8px; border-left: 4px solid var(--brand-orange); margin: 2rem 0;">
            <label style="font-size: 1.1rem; color: var(--brand-orange); cursor: pointer;">
                <input type="checkbox" name="is_internal" id="apo_is_internal" value="1" onchange="toggleInternalFields()" style="transform: scale(1.5); margin-right: 10px;"> 
                <strong>Esta é uma Apostila Própria (ISP)?</strong>
            </label>
            <p style="color: var(--text-secondary); margin-top: 0.5rem; font-size: 0.9rem;">Se marcado, uma Landing Page será criada e o cliente será direcionado para ela.</p>
            
            <div id="internal_fields" style="display: none; margin-top: 1.5rem; border-top: 1px solid var(--glass-border); padding-top: 1.5rem;">
                <h3 class="apostila-section-heading">2. Conteúdo da página de venda</h3>
                <div class="form-group">
                    <label>Subtítulo (Ex: Caderno focado nas provas...)</label>
                    <input type="text" name="subtitle" id="apo_subtitle" class="form-control">
                </div>
                <div class="form-group">
                    <label>Tópicos do Caderno (Um por linha)</label>
                    <textarea name="topics" id="apo_topics" class="form-control" rows="5" placeholder="Questões gabaritadas&#10;Mapas mentais&#10;Resumos esquematizados"></textarea>
                </div>
                <div class="form-group">
                    <label>Imagens de Demonstração (Múltiplas páginas por dentro)</label>
                    <input type="file" name="preview_images[]" class="form-control" accept="image/*" multiple>
                    <small style="color: var(--text-secondary);">Você pode selecionar várias imagens de uma vez segurando CTRL.</small>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: 2rem 0;">
                <h3 class="apostila-section-heading">3. Textos editáveis da landing page</h3>
                
                <div class="form-group">
                    <label>Texto do Botão Principal (Topo)</label>
                    <input type="text" name="hero_btn_text" id="apo_hero_btn_text" class="form-control" placeholder="Garantir Meu Material">
                </div>
                <div class="form-group">
                    <label>Título da Seção de Tópicos</label>
                    <input type="text" name="sec1_title" id="apo_sec1_title" class="form-control" placeholder="O que você vai encontrar no material?">
                </div>
                <div class="form-group">
                    <label>Texto do Botão da Seção de Tópicos</label>
                    <input type="text" name="sec1_btn_text" id="apo_sec1_btn_text" class="form-control" placeholder="Quero Ter Acesso Agora">
                </div>
                <div class="form-group">
                    <label>Título da Seção de Imagens</label>
                    <input type="text" name="sec2_title" id="apo_sec2_title" class="form-control" placeholder="Veja o Material por Dentro">
                </div>
                <div class="form-group">
                    <label>Título da Seção Final</label>
                    <input type="text" name="sec3_title" id="apo_sec3_title" class="form-control" placeholder="Acelerando sua Aprovação">
                </div>
                <div class="form-group">
                    <label>Texto da Seção Final</label>
                    <textarea name="sec3_text" id="apo_sec3_text" class="form-control" rows="4" placeholder="<?= htmlspecialchars($defaultSec3Text) ?>"></textarea>
                    <small style="color: var(--text-secondary);">Este texto aparece antes do último botão de compra.</small>
                </div>
                <div class="form-group">
                    <label>Diferenciais da Seção Final (Um por linha)</label>
                    <textarea name="sec3_bullets" id="apo_sec3_bullets" class="form-control" rows="4" placeholder="Questões selecionadas e comentadas&#10;Resumos objetivos para revisão&#10;Formato direto ao ponto"></textarea>
                </div>
                <div class="form-group">
                    <label>Texto do Botão Final</label>
                    <input type="text" name="sec3_btn_text" id="apo_sec3_btn_text" class="form-control" placeholder="Comprar Agora">
                </div>

                <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: 2rem 0;">
                <h3 class="apostila-section-heading">4. Vídeo do YouTube</h3>
                <div class="form-group">
                    <label>Título da Seção de Vídeo (Opcional)</label>
                    <input type="text" name="video_title" id="apo_video_title" class="form-control" placeholder="Entenda como funciona nossa apostila">
                    <small style="color: var(--text-secondary);">Se ficar vazio, o site usa um título padrão e ainda exibe o vídeo.</small>
                </div>
                <div class="form-group">
                    <label>URL do Vídeo do YouTube (Opcional)</label>
                    <input type="url" name="video_url" id="apo_video_url" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
                </div>

                <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: 2rem 0;">
                <h3 class="apostila-section-heading">5. Seções Extras Dinâmicas</h3>
                <p style="color: var(--text-secondary); margin-bottom: 1rem;">Crie seções personalizadas com título e conteúdo livre.</p>
                
                <div id="extra_sections_container"></div>
                <button type="button" class="btn btn-secondary" onclick="addExtraSection()" style="margin-bottom: 1.5rem;">+ Adicionar Nova Seção</button>
                <input type="hidden" name="extra_sections" id="apo_extra_sections">
            </div>
        </div>

        <h3 class="apostila-section-heading">6. Imagens e publicação</h3>
        <div class="apostila-row">
            <div class="form-group" style="flex:1;">
                <label>Imagem da Capa (Vitrine e Topo da Landing Page)</label>
                <input type="file" name="cover_image" class="form-control" accept="image/*">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Texto Alternativo da Imagem (SEO)</label>
                <input type="text" name="image_alt" id="apo_image_alt" class="form-control" placeholder="Ex: Capa da apostila de Português para Caxias">
            </div>
        </div>

        <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; margin-top: 1rem; margin-bottom: 1.5rem;">
            <h3 style="margin-top: 0; color: var(--brand-orange); font-size: 1.1rem; margin-bottom: 1rem;">Configurações de SEO</h3>
            <div class="form-group">
                <label>Slug (URL Amigável) <small style="color: #999;">- Deixe em branco para gerar automaticamente baseado no título</small></label>
                <input type="text" name="slug" id="apo_slug" class="form-control" placeholder="exemplo-de-apostila">
            </div>
            <div class="form-group">
                <label>Meta Title <small style="color: #999;">- Título para o Google e Aba do Navegador (Opcional)</small></label>
                <input type="text" name="meta_title" id="apo_meta_title" class="form-control" placeholder="Ex: Apostila de Educação Especial - ISP Preparatórios">
            </div>
            <div class="form-group">
                <label>Meta Description <small style="color: #999;">- Resumo que aparece no Google (Opcional, max 160 caracteres)</small></label>
                <textarea name="meta_description" id="apo_meta_description" class="form-control" rows="2" placeholder="Resumo atrativo para os resultados de busca..."></textarea>
            </div>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="active" id="apo_active" value="1" checked> Ativo (Visível na loja)
            </label>
        </div>
        
        <button type="submit" class="btn">Salvar Apostila</button>
        <button type="button" class="btn btn-warning" onclick="resetForm()">Nova Apostila</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <tr>
            <th>Capa</th>
            <th>Título</th>
            <th>Tipo</th>
            <th>Preço</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
        <?php foreach($apostilas as $a): ?>
        <tr>
            <td>
                <?php if($a['cover_image']): ?>
                    <img src="../uploads/<?= $a['cover_image'] ?>" height="50" style="border-radius: 4px;">
                <?php else: ?>
                    Sem capa
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($a['title']) ?></td>
            <td>
                <?php if($a['is_internal']): ?>
                    <span style="background: var(--prism-cyan); color: #000; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">Própria</span>
                <?php else: ?>
                    <span style="background: var(--glass-border); color: #fff; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem;">Externa</span>
                <?php endif; ?>
            </td>
            <td>R$ <?= number_format($a['price'], 2, ',', '.') ?></td>
            <td><?= $a['active'] ? 'Ativa' : 'Inativa' ?></td>
            <td>
                <button class="btn" onclick='editarApostila(<?= json_encode($a) ?>)'>Editar</button>
                <a href="?del=<?= $a['id'] ?>" class="btn btn-danger" onclick="return confirm('Excluir esta apostila?')">Excluir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
let currentSections = [];

function toggleInternalFields() {
    var isInternal = document.getElementById('apo_is_internal').checked;
    document.getElementById('internal_fields').style.display = isInternal ? 'block' : 'none';
}

function editarApostila(a) {
    document.getElementById('apo_id').value = a.id;
    document.getElementById('apo_title').value = a.title;
    document.getElementById('apo_price').value = a.price;
    document.getElementById('apo_payment_link').value = a.payment_link;
    document.getElementById('apo_active').checked = a.active == 1;
    
    document.getElementById('apo_is_internal').checked = a.is_internal == 1;
    document.getElementById('apo_subtitle').value = a.subtitle || '';
    document.getElementById('apo_topics').value = a.topics || '';
    
    document.getElementById('apo_hero_btn_text').value = a.hero_btn_text || '';
    document.getElementById('apo_sec1_title').value = a.sec1_title || '';
    document.getElementById('apo_sec1_btn_text').value = a.sec1_btn_text || '';
    document.getElementById('apo_sec2_title').value = a.sec2_title || '';
    document.getElementById('apo_sec3_title').value = a.sec3_title || '';
    document.getElementById('apo_sec3_text').value = a.sec3_text || '';
    document.getElementById('apo_sec3_bullets').value = a.sec3_bullets || '';
    document.getElementById('apo_sec3_btn_text').value = a.sec3_btn_text || '';
    
    document.getElementById('apo_video_title').value = a.video_title || '';
    document.getElementById('apo_video_url').value = a.video_url || '';
    document.getElementById('apo_image_alt').value = a.image_alt || '';
    
    document.getElementById('apo_slug').value = a.slug || '';
    document.getElementById('apo_meta_title').value = a.meta_title || '';
    document.getElementById('apo_meta_description').value = a.meta_description || '';

    // Carregar Seções Extras
    currentSections = [];
    try {
        currentSections = JSON.parse(a.extra_sections || '[]');
    } catch(e) { currentSections = []; }
    renderSections();

    toggleInternalFields();
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
    const titleInput = document.getElementById('apo_title');
    const slugInput = document.getElementById('apo_slug');
    if (!slugInput.value.trim() && titleInput.value.trim()) {
        slugInput.value = generateSlug(titleInput.value);
    }
});

function addExtraSection() {
    currentSections.push({ title: '', content: '' });
    renderSections();
}

function removeSection(index) {
    currentSections.splice(index, 1);
    renderSections();
}

function updateSection(index, field, value) {
    currentSections[index][field] = value;
    document.getElementById('apo_extra_sections').value = JSON.stringify(currentSections);
}

function renderSections() {
    const container = document.getElementById('extra_sections_container');
    container.innerHTML = '';
    
    currentSections.forEach((sec, index) => {
        const div = document.createElement('div');
        div.style = "background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; position: relative;";
        div.innerHTML = `
            <button type="button" onclick="removeSection(${index})" style="position: absolute; top: 10px; right: 10px; background: #ff4444; color: white; border: none; border-radius: 4px; padding: 2px 8px; cursor: pointer;">Remover</button>
            <div class="form-group">
                <label>Título da Seção</label>
                <input type="text" class="form-control" value="${sec.title}" oninput="updateSection(${index}, 'title', this.value)" placeholder="Ex: Por que escolher nosso material?">
            </div>
            <div class="form-group">
                <label>Conteúdo da Seção (HTML permitido)</label>
                <textarea class="form-control" rows="3" oninput="updateSection(${index}, 'content', this.value)" placeholder="Descreva aqui os detalhes...">${sec.content}</textarea>
            </div>
        `;
        container.appendChild(div);
    });
    document.getElementById('apo_extra_sections').value = JSON.stringify(currentSections);
}

function resetForm() {
    document.getElementById('apo_id').value = '';
    document.getElementById('apo_title').value = '';
    document.getElementById('apo_price').value = '';
    document.getElementById('apo_payment_link').value = '';
    document.getElementById('apo_active').checked = true;
    
    document.getElementById('apo_is_internal').checked = false;
    document.getElementById('apo_subtitle').value = '';
    document.getElementById('apo_topics').value = '';
    
    document.getElementById('apo_hero_btn_text').value = '';
    document.getElementById('apo_sec1_title').value = '';
    document.getElementById('apo_sec1_btn_text').value = '';
    document.getElementById('apo_sec2_title').value = '';
    document.getElementById('apo_sec3_title').value = '';
    document.getElementById('apo_sec3_text').value = '';
    document.getElementById('apo_sec3_bullets').value = '';
    document.getElementById('apo_sec3_btn_text').value = '';
    
    document.getElementById('apo_video_title').value = '';
    document.getElementById('apo_video_url').value = '';
    document.getElementById('apo_image_alt').value = '';
    
    document.getElementById('apo_slug').value = '';
    document.getElementById('apo_meta_title').value = '';
    document.getElementById('apo_meta_description').value = '';
    
    currentSections = [];
    renderSections();

    toggleInternalFields();
}
</script>
<?php require_once 'includes/footer.php'; ?>
