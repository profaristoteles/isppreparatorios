<?php
require_once 'auth.php';
require_once '../db_config.php';

if (!is_dir('../uploads')) { mkdir('../uploads', 0777, true); }

if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $pdo->query("DELETE FROM posts WHERE id = $id");
    $_SESSION['msg'] = "Post removido.";
    header("Location: gerenciar-posts.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $category = $_POST['category'] ?: 'Geral';
    // TinyMCE sends HTML content
    $content = $_POST['content'];
    $image_alt = $_POST['image_alt'] ?: '';
    $created_at = !empty($_POST['created_at']) ? date('Y-m-d H:i:s', strtotime($_POST['created_at'])) : date('Y-m-d H:i:s');

    $cover = '';
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $cover = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['cover_image']['tmp_name'], '../uploads/' . $cover);
        }
    }

    if (!empty($_POST['id'])) {
        if ($cover) {
            $stmt = $pdo->prepare("UPDATE posts SET title=?, category=?, content=?, cover_image=?, image_alt=?, created_at=? WHERE id=?");
            $stmt->execute([$title, $category, $content, $cover, $image_alt, $created_at, $_POST['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE posts SET title=?, category=?, content=?, image_alt=?, created_at=? WHERE id=?");
            $stmt->execute([$title, $category, $content, $image_alt, $created_at, $_POST['id']]);
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO posts (title, category, content, cover_image, image_alt, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $category, $content, $cover, $image_alt, $created_at]);
    }
    $_SESSION['msg'] = "Post salvo com sucesso.";
    header("Location: gerenciar-posts.php");
    exit;
}

$posts = $pdo->query("SELECT * FROM posts ORDER BY id DESC")->fetchAll();
$config = get_config($pdo);
require_once 'includes/header.php';
?>

<!-- TinyMCE CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>

<div class="card">
    <h2>Gerenciar Blog (Artigos)</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" id="post_id">
        
        <div style="display:flex; gap:1rem;">
            <div class="form-group" style="flex:2;">
                <label>Título do Post</label>
                <input type="text" name="title" id="post_title" class="form-control" required>
            </div>
            <div class="form-group" style="flex:1;">
                <label>Categoria (Ex: Notícias, Dicas, Edital)</label>
                <input type="text" name="category" id="post_category" class="form-control" placeholder="Geral">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Data de Publicação</label>
                <input type="datetime-local" name="created_at" id="post_created_at" class="form-control">
            </div>
        </div>

        <div class="form-group" style="background: rgba(3, 4, 94, 0.4); border: 1px solid var(--prism-cyan); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="color: var(--prism-cyan); margin: 0;"><i class="fas fa-robot"></i> Assistente IA ISP</h4>
                <?php 
                    $providerLabels = ['gemini' => '🔵 Google Gemini', 'groq' => '🟢 Groq', 'openai' => '⚪ OpenAI', 'openrouter' => '🟠 OpenRouter'];
                    $activeProvider = $providerLabels[$config['ai_provider'] ?? 'gemini'] ?? 'Não configurado';
                ?>
                <span style="font-size: 0.8rem; color: var(--text-secondary);">Powered by <?= $activeProvider ?></span>
            </div>
            <p style="color: #ccc; font-size: 0.9rem; margin-bottom: 1rem;">Descreva o tema do artigo e a IA irá gerar um rascunho completo formatado no editor abaixo.</p>
            <div style="display: flex; gap: 1rem; align-items: flex-start;">
                <textarea id="ai_prompt" class="form-control" rows="2" placeholder="Ex: Escreva um artigo sobre Dicas de Estudos para o concurso de Professor em São Paulo, focando em LDB." style="flex: 1; resize: none;"></textarea>
                <button type="button" onclick="gerarArtigoIA()" id="btn_ia" class="btn" style="background: var(--brand-orange); color: #fff; white-space: nowrap; padding: 0.8rem 1.5rem;">Gerar Artigo</button>
            </div>
            <div id="ai_loading" style="display: none; color: var(--brand-orange); font-size: 0.9rem; margin-top: 1rem; font-family: var(--font-mono);">
                <i class="fas fa-spinner fa-spin"></i> A Inteligência Artificial está escrevendo... (Isso pode levar alguns segundos)
            </div>
        </div>

        <div class="form-group">
            <label>Conteúdo (Use o editor para formatar, adicionar vídeos, botões e imagens)</label>
            <textarea name="content" id="post_content" class="form-control" rows="15"></textarea>
        </div>
        <div style="display:flex; gap:1rem;">
            <div class="form-group" style="flex:1;">
                <label>Imagem da Capa</label>
                <input type="file" name="cover_image" class="form-control" accept="image/*">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Texto Alternativo da Imagem (SEO)</label>
                <input type="text" name="image_alt" id="post_image_alt" class="form-control" placeholder="Ex: Professor dando aula de educação especial">
            </div>
        </div>
        <button type="submit" class="btn">Salvar Post</button>
        <button type="button" class="btn btn-warning" onclick="resetForm()">Novo Post</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <tr>
            <th>Capa</th>
            <th>Título</th>
            <th>Categoria</th>
            <th>Data</th>
            <th>Ações</th>
        </tr>
        <?php foreach($posts as $p): ?>
        <tr>
            <td>
                <?php if($p['cover_image']): ?>
                    <img src="../uploads/<?= $p['cover_image'] ?>" height="50" style="border-radius: 4px;">
                <?php else: ?>
                    Sem capa
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['title']) ?></td>
            <td><span style="background: var(--prism-cyan); color: #000; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;"><?= htmlspecialchars($p['category']) ?></span></td>
            <td>
                <?php 
                $is_scheduled = strtotime($p['created_at']) > time();
                echo date('d/m/Y H:i', strtotime($p['created_at']));
                if($is_scheduled) {
                    echo '<br><span style="background:var(--brand-orange); color:#fff; font-size:0.7rem; padding:2px 5px; border-radius:4px; margin-top:4px; display:inline-block;">⏳ Agendado</span>';
                }
                ?>
            </td>
            <td>
                <!-- Pass content securely -->
                <button class="btn" onclick="editarPost(<?= htmlspecialchars(json_encode([
                    'id' => $p['id'],
                    'title' => $p['title'],
                    'category' => $p['category'],
                    'content' => $p['content'],
                    'image_alt' => $p['image_alt'],
                    'created_at' => $p['created_at']
                ])) ?>)">Editar</button>
                <a href="?del=<?= $p['id'] ?>" class="btn btn-danger" onclick="return confirm('Excluir este post?')">Excluir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
// Inicializa o TinyMCE
tinymce.init({
    selector: '#post_content',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat | code',
    height: 500,
    language: 'pt_BR',
    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px; background: #f4f4f4; color: #333; }',
    skin: "oxide-dark",
    content_css: "dark"
});

function editarPost(p) {
    document.getElementById('post_id').value = p.id;
    document.getElementById('post_title').value = p.title;
    document.getElementById('post_category').value = p.category || 'Geral';
    
    if (tinymce.get('post_content')) {
        tinymce.get('post_content').setContent(p.content || '');
    } else {
        document.getElementById('post_content').value = p.content || '';
    }
    document.getElementById('post_image_alt').value = p.image_alt || '';
    
    if (p.created_at) {
        document.getElementById('post_created_at').value = p.created_at.substring(0, 16).replace(' ', 'T');
    } else {
        document.getElementById('post_created_at').value = '';
    }
    
    window.scrollTo(0,0);
}

function resetForm() {
    document.getElementById('post_id').value = '';
    document.getElementById('post_title').value = '';
    document.getElementById('post_category').value = '';
    
    if (tinymce.get('post_content')) {
        tinymce.get('post_content').setContent('');
    } else {
        document.getElementById('post_content').value = '';
    }
    document.getElementById('post_image_alt').value = '';
    document.getElementById('post_created_at').value = '';
}

async function gerarArtigoIA() {
    const prompt = document.getElementById('ai_prompt').value.trim();
    if (!prompt) {
        alert("Por favor, digite um tema ou comando para a IA.");
        return;
    }

    const btn = document.getElementById('btn_ia');
    const loading = document.getElementById('ai_loading');
    
    btn.disabled = true;
    loading.style.display = 'block';

    try {
        const response = await fetch('ajax_ai_generate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prompt: prompt })
        });
        
        const rawText = await response.text();
        let data;
        try {
            data = JSON.parse(rawText);
        } catch (e) {
            console.error("Raw response:", rawText);
            alert("Erro de formato na resposta do servidor. Verifique o console para detalhes.");
            return;
        }
        
        if (data.error) {
            alert(data.error);
        } else if (data.success && data.html) {
            if (tinymce.get('post_content')) {
                tinymce.get('post_content').setContent(data.html);
            } else {
                document.getElementById('post_content').value = data.html;
            }
            if(document.getElementById('post_title').value === '') {
                document.getElementById('post_title').value = "Artigo Gerado por IA";
            }
        }
    } catch (e) {
        alert("Erro na requisição: " + e.message);
        console.error(e);
    } finally {
        btn.disabled = false;
        loading.style.display = 'none';
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
