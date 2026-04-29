<?php
require_once 'auth.php';
require_once '../db_config.php';

if (!is_dir('../uploads')) { mkdir('../uploads', 0777, true); }

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
    $sec3_btn_text = $_POST['sec3_btn_text'] ?: 'Comprar Agora';
    $video_title = $_POST['video_title'] ?: '';
    $video_url = $_POST['video_url'] ?: '';
    $image_alt = $_POST['image_alt'] ?: '';

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
        
        $sql = "UPDATE apostilas SET title=?, payment_link=?, active=?, price=?, subtitle=?, is_internal=?, topics=?, hero_btn_text=?, sec1_title=?, sec1_btn_text=?, sec2_title=?, sec3_title=?, sec3_btn_text=?, video_title=?, video_url=?, image_alt=?";
        $params = [$title, $payment_link, $active, $price, $subtitle, $is_internal, $topics, $hero_btn_text, $sec1_title, $sec1_btn_text, $sec2_title, $sec3_title, $sec3_btn_text, $video_title, $video_url, $image_alt];
        
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
        $stmt = $pdo->prepare("INSERT INTO apostilas (title, payment_link, cover_image, active, price, subtitle, is_internal, topics, preview_images, hero_btn_text, sec1_title, sec1_btn_text, sec2_title, sec3_title, sec3_btn_text, video_title, video_url, image_alt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $payment_link, $cover, $active, $price, $subtitle, $is_internal, $topics, $preview_str, $hero_btn_text, $sec1_title, $sec1_btn_text, $sec2_title, $sec3_title, $sec3_btn_text, $video_title, $video_url, $image_alt]);
    }
    $_SESSION['msg'] = "Apostila salva com sucesso.";
    header("Location: gerenciar-apostilas.php");
    exit;
}

$apostilas = $pdo->query("SELECT * FROM apostilas ORDER BY id DESC")->fetchAll();
require_once 'includes/header.php';
?>

<div class="card">
    <h2>Gerenciar Apostilas</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" id="apo_id">
        
        <div style="display:flex; gap:1rem; margin-bottom: 1rem;">
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
                <h4 style="margin-bottom: 1rem; color: var(--text-primary);">Textos Dinâmicos da Landing Page</h4>
                
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
                    <label>Texto do Botão Final</label>
                    <input type="text" name="sec3_btn_text" id="apo_sec3_btn_text" class="form-control" placeholder="Comprar Agora">
                </div>

                <hr style="border: 0; border-top: 1px solid var(--glass-border); margin: 2rem 0;">
                <h4 style="margin-bottom: 1rem; color: var(--text-primary);">Seção de Vídeo (YouTube)</h4>
                <div class="form-group">
                    <label>Título da Seção de Vídeo (Opcional)</label>
                    <input type="text" name="video_title" id="apo_video_title" class="form-control" placeholder="Entenda como funciona nossa apostila">
                </div>
                <div class="form-group">
                    <label>URL do Vídeo do YouTube (Opcional)</label>
                    <input type="url" name="video_url" id="apo_video_url" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
                </div>
            </div>
        </div>

        <div style="display:flex; gap:1rem;">
            <div class="form-group" style="flex:1;">
                <label>Imagem da Capa (Vitrine e Topo da Landing Page)</label>
                <input type="file" name="cover_image" class="form-control" accept="image/*">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Texto Alternativo da Imagem (SEO)</label>
                <input type="text" name="image_alt" id="apo_image_alt" class="form-control" placeholder="Ex: Capa da apostila de Português para Caxias">
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
    document.getElementById('apo_sec3_btn_text').value = a.sec3_btn_text || '';
    
    document.getElementById('apo_video_title').value = a.video_title || '';
    document.getElementById('apo_video_url').value = a.video_url || '';
    document.getElementById('apo_image_alt').value = a.image_alt || '';

    toggleInternalFields();
    window.scrollTo(0,0);
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
    document.getElementById('apo_sec3_btn_text').value = '';
    
    document.getElementById('apo_video_title').value = '';
    document.getElementById('apo_video_url').value = '';
    document.getElementById('apo_image_alt').value = '';

    toggleInternalFields();
}
</script>
<?php require_once 'includes/footer.php'; ?>
