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
    $status = $_POST['status'] ?? 'publicado';
    $seo_keywords = $_POST['seo_keywords'] ?? '';
    $meta_title = $_POST['meta_title'] ?? '';
    $meta_description = $_POST['meta_description'] ?? '';
    $slug = $_POST['slug'] ?? '';
    $created_at = !empty($_POST['created_at']) ? date('Y-m-d H:i:s', strtotime($_POST['created_at'])) : date('Y-m-d H:i:s');

    if (!$slug) {
        $slug_text = strtr(strtolower($title), [
            'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
            'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
            'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
            'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
            'ç'=>'c','ñ'=>'n'
        ]);
        $slug = preg_replace('/[\s-]+/', '-', trim(preg_replace('/[^a-z0-9\s-]/', '', $slug_text)));
    }

    $cover = '';
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $cover = uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['cover_image']['tmp_name'], '../uploads/' . $cover);
        }
    } elseif (!empty($_POST['ai_generated_image'])) {
        $cover = $_POST['ai_generated_image'];
    }

    if (!empty($_POST['id'])) {
        if ($cover) {
            $stmt = $pdo->prepare("UPDATE posts SET title=?, category=?, content=?, cover_image=?, image_alt=?, status=?, seo_keywords=?, meta_title=?, meta_description=?, slug=?, created_at=? WHERE id=?");
            $stmt->execute([$title, $category, $content, $cover, $image_alt, $status, $seo_keywords, $meta_title, $meta_description, $slug, $created_at, $_POST['id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE posts SET title=?, category=?, content=?, image_alt=?, status=?, seo_keywords=?, meta_title=?, meta_description=?, slug=?, created_at=? WHERE id=?");
            $stmt->execute([$title, $category, $content, $image_alt, $status, $seo_keywords, $meta_title, $meta_description, $slug, $created_at, $_POST['id']]);
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO posts (title, category, content, cover_image, image_alt, status, seo_keywords, meta_title, meta_description, slug, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $category, $content, $cover, $image_alt, $status, $seo_keywords, $meta_title, $meta_description, $slug, $created_at]);
    }
    $msgLabel = $status === 'rascunho' ? 'Rascunho salvo com sucesso.' : 'Post publicado com sucesso.';
    $_SESSION['msg'] = $msgLabel;
    header("Location: gerenciar-posts.php");
    exit;
}

$posts = $pdo->query("SELECT * FROM posts ORDER BY id DESC")->fetchAll();
$config = get_config($pdo);
require_once 'includes/header.php';
?>

<!-- TinyMCE CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>

<!-- CARD DO AGENTE IA DE BLOG -->
<div class="card" style="background: linear-gradient(135deg, #03045e, #0b1340); color: #fff; margin-bottom: 2rem; border: 1px solid rgba(0, 210, 255, 0.4); box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
        <h3 style="margin: 0; color: #ff8000; display: flex; align-items: center; gap: 0.6rem; font-size: 1.3rem;">
            🤖 Agente Redator IA — Blog Autônomo
        </h3>
        <span style="background: rgba(0,210,255,0.2); color: #00d2ff; padding: 0.35rem 0.9rem; border-radius: 20px; font-size: 0.82rem; font-weight: 600;">
            Diagramação Profissional HTML
        </span>
    </div>
    
    <p style="color: #ccc; font-size: 0.92rem; margin-bottom: 1.2rem; line-height: 1.5;">
        Gere artigos de blog completos em tempo real baseados nos últimos editais da <strong>PCI Concursos (MCP)</strong> ou em temas educacionais, com tabelas de vagas, caixas de alerta, badges e capas por IA.
    </p>

    <div style="display: grid; grid-template-columns: 1.5fr 1fr 120px auto; gap: 1rem; align-items: flex-end;">
        <div>
            <label style="color: #aaa; font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.3rem;">MODO DE GERAÇÃO</label>
            <select id="agent-mode" class="form-control" style="background: #02041b; color: #fff; border-color: rgba(0,210,255,0.3);">
                <option value="pci_contest">📌 Análise de Edital PCI Concursos (Maranhão / Professores)</option>
                <option value="education_topic">📚 Artigo Educacional (LDB, Português, Pedagogia)</option>
                <option value="custom_prompt">✍️ Tema Personalizado</option>
            </select>
        </div>

        <div>
            <label style="color: #aaa; font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.3rem;">ESTADO (UF) / TEMA LIVRE</label>
            <input type="text" id="agent-prompt" class="form-control" placeholder="Ex: MA ou digite o tema desejado..." style="background: #02041b; color: #fff; border-color: rgba(0,210,255,0.3);">
        </div>

        <div>
            <label style="color: #aaa; font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 0.3rem;">STATUS</label>
            <select id="agent-status" class="form-control" style="background: #02041b; color: #fff; border-color: rgba(0,210,255,0.3);">
                <option value="rascunho">Rascunho</option>
                <option value="publicado">Publicado</option>
            </select>
        </div>

        <div>
            <button type="button" onclick="runAiBlogAgent()" class="btn" style="padding: 0.75rem 1.4rem; white-space: nowrap; background: linear-gradient(135deg, #ff8000, #ff5500); border: none; font-weight: 700;">
                ⚡ GERAR POST AGORA
            </button>
        </div>
    </div>

    <!-- Feedback / Progress Container -->
    <div id="agent-progress-box" style="display: none; margin-top: 1.2rem; background: rgba(0,0,0,0.4); padding: 1rem 1.25rem; border-radius: 8px; border: 1px solid var(--glass-border);">
        <div style="display: flex; align-items: center; gap: 0.85rem;">
            <div id="agent-spinner" style="width: 24px; height: 24px; border: 3px solid rgba(255,128,0,0.3); border-top-color: #ff8000; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
            <span id="agent-progress-msg" style="color: #fff; font-size: 0.92rem; font-weight: 500;">Iniciando o Agente IA...</span>
        </div>
    </div>
</div>

<script>
function runAiBlogAgent() {
    const mode = document.getElementById('agent-mode').value;
    const promptInput = document.getElementById('agent-prompt').value.trim();
    const status = document.getElementById('agent-status').value;
    
    const progressBox = document.getElementById('agent-progress-box');
    const progressMsg = document.getElementById('agent-progress-msg');
    
    progressBox.style.display = 'block';
    progressMsg.innerHTML = '⚡ <strong>Agente IA Ativo:</strong> Pesquisando edital e redigindo artigo com diagramação HTML... (Aguarde alguns segundos)';

    let uf = 'MA';
    if (promptInput.length === 2) {
        uf = promptInput.toUpperCase();
    } else {
        const ufMatch = promptInput.match(/\b(AC|AL|AP|AM|BA|CE|DF|ES|GO|MA|MT|MS|MG|PA|PB|PR|PE|PI|RJ|RN|RS|RO|RR|SC|SP|SE|TO)\b/i);
        if (ufMatch) {
            uf = ufMatch[1].toUpperCase();
        }
    }

    fetch('ajax_agent_generate_post.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            mode: mode,
            prompt: promptInput,
            status: status,
            uf: uf
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            progressMsg.innerHTML = `✅ <strong>Sucesso!</strong> ${data.message} Recarregando a página...`;
            setTimeout(() => {
                window.location.reload();
            }, 1800);
        } else {
            progressMsg.innerHTML = `❌ <strong>Erro:</strong> ${data.error || 'Falha ao gerar o artigo.'}`;
        }
    })
    .catch(err => {
        progressMsg.innerHTML = `❌ <strong>Erro de conexão:</strong> Não foi possível se comunicar com o Agente IA.`;
    });
}
</script>

<div class="card">
    <h2>Gerenciar Blog (Artigos)</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" id="post_id">
        <input type="hidden" name="status" id="post_status" value="publicado">
        
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

        <div class="form-group">
            <label>Palavras-chave SEO (Separadas por vírgula)</label>
            <input type="text" name="seo_keywords" id="post_seo_keywords" class="form-control" placeholder="concurso, educação, professores, dicas de estudo">
        </div>

        <div style="display:flex; gap:1rem; margin-bottom: 1.5rem; background: #f8f9fa; padding: 1rem; border-radius: 8px; border: 1px solid #dee2e6;">
            <div style="flex: 1;">
                <h4 style="margin-top: 0; color: #03045e; font-size: 1rem;">Configurações de SEO</h4>
                <div class="form-group">
                    <label>Meta Title (Deixe vazio para usar o título do post)</label>
                    <input type="text" name="meta_title" id="post_meta_title" class="form-control" maxlength="70" placeholder="Ex: Dicas para Concurso | ISP">
                    <small style="color: #666;">Máximo de 70 caracteres</small>
                </div>
                <div class="form-group">
                    <label>Meta Description (Deixe vazio para usar o resumo)</label>
                    <textarea name="meta_description" id="post_meta_description" class="form-control" rows="2" maxlength="160" placeholder="Ex: Descubra as melhores dicas para passar no concurso de professores da rede pública..."></textarea>
                    <small style="color: #666;">Máximo de 160 caracteres</small>
                </div>
                <div class="form-group">
                    <label>Slug Personalizado (Deixe vazio para gerar automaticamente do título)</label>
                    <input type="text" name="slug" id="post_slug" class="form-control" placeholder="ex: dicas-para-concurso">
                </div>
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
                
                <div style="margin-top: 1rem; padding: 1rem; background: rgba(3, 4, 94, 0.2); border: 1px dashed var(--prism-cyan); border-radius: 8px;">
                    <label style="font-size: 0.9rem; color: var(--prism-cyan); margin-bottom: 0.5rem; display: block;"><i class="fas fa-magic"></i> Ou gerar capa com IA</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" id="ai_image_prompt" class="form-control" placeholder="Ex: Professor em sala de aula, estilo realista">
                        <button type="button" class="btn" onclick="gerarImagemIA()" id="btn_ia_image" style="background: var(--prism-cyan); color: #000; white-space: nowrap;">Gerar Imagem</button>
                    </div>
                    <div id="ai_img_loading" style="display:none; color: var(--prism-cyan); font-size: 0.8rem; margin-top: 0.5rem;"><i class="fas fa-spinner fa-spin"></i> Gerando imagem e baixando...</div>
                    
                    <div id="ai_img_preview_container" style="display:none; margin-top: 1rem;">
                        <img id="ai_img_preview" src="" style="width: 100%; height: 150px; object-fit: cover; border-radius: 4px;">
                        <input type="hidden" name="ai_generated_image" id="ai_generated_image">
                        <small style="color: #ccc; display: block; margin-top: 0.3rem;">Imagem gerada selecionada como capa. Salve o post para aplicar.</small>
                    </div>
                </div>
            </div>
            <div class="form-group" style="flex:1;">
                <label>Texto Alternativo da Imagem (SEO)</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" name="image_alt" id="post_image_alt" class="form-control" placeholder="Ex: Professor dando aula de educação especial" style="flex: 1;">
                    <button type="button" class="btn" onclick="gerarAltTextIA()" id="btn_ia_alt" style="background: var(--prism-cyan); color: #000; white-space: nowrap; font-size: 0.8rem; padding: 0.5rem 1rem;" title="Gerar texto alternativo com IA"><i class="fas fa-magic"></i> Gerar com IA</button>
                </div>
                <div id="ai_alt_loading" style="display:none; color: var(--prism-cyan); font-size: 0.8rem; margin-top: 0.5rem;"><i class="fas fa-spinner fa-spin"></i> Gerando texto alternativo...</div>
            </div>
        </div>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <button type="submit" class="btn" onclick="document.getElementById('post_status').value='publicado'" style="background: #ff8000; border: none; color: #fff; font-weight: 600; padding: 0.6rem 1.5rem;"><i class="fas fa-paper-plane"></i> Publicar</button>
            <button type="submit" class="btn" onclick="document.getElementById('post_status').value='rascunho'" style="background: #e9ecef; border: 1px solid #ccc; color: #333; font-weight: 600; padding: 0.6rem 1.5rem;"><i class="fas fa-save"></i> Salvar Rascunho</button>
            <button type="button" class="btn btn-warning" onclick="resetForm()">Novo Post</button>
            <span id="status_indicator" style="display:none; font-size: 0.8rem; padding: 0.3rem 0.8rem; border-radius: 20px; font-weight: 600;"></span>
        </div>
    </form>
</div>

<div class="card">
    <table class="table">
        <tr>
            <th>Capa</th>
            <th>Título</th>
            <th>Categoria</th>
            <th>Status</th>
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
            <td><span style="background: #ff8000; color: #03045e; padding: 2px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;"><?= htmlspecialchars($p['category']) ?></span></td>
            <td>
                <?php $pStatus = $p['status'] ?? 'publicado'; ?>
                <?php if($pStatus === 'rascunho'): ?>
                    <span style="background: rgba(255,255,255,0.1); color: #aaa; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold; border: 1px solid var(--glass-border);">📝 Rascunho</span>
                <?php else: ?>
                    <span style="background: #1a7a4a; color: #fff; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: bold;">✅ Publicado</span>
                <?php endif; ?>
            </td>
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
                <button type="button" class="btn" onclick="editarPost(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)">Editar</button>
                <a href="?del=<?= $p['id'] ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
// Inicializa o TinyMCE
tinymce.init({
    selector: '#post_content',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code preview',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat | code preview',
    height: 500,
    language: 'pt_BR',
    content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px; background: #f4f4f4; color: #333; }',
    skin: "oxide-dark",
    content_css: "dark",
    valid_children: "+body[style],+div[style]",
    extended_valid_elements: "style[type|media|scoped]"
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
    document.getElementById('post_seo_keywords').value = p.seo_keywords || '';
    document.getElementById('post_meta_title').value = p.meta_title || '';
    document.getElementById('post_meta_description').value = p.meta_description || '';
    document.getElementById('post_slug').value = p.slug || '';
    
    // Set status
    document.getElementById('post_status').value = p.status || 'publicado';
    var indicator = document.getElementById('status_indicator');
    if (p.status === 'rascunho') {
        indicator.textContent = '📝 Editando Rascunho';
        indicator.style.display = 'inline-block';
        indicator.style.background = '#f0f0f0';
        indicator.style.color = '#666';
        indicator.style.border = '1px solid #ccc';
    } else {
        indicator.textContent = '✅ Publicado';
        indicator.style.display = 'inline-block';
        indicator.style.background = '#1a7a4a';
        indicator.style.color = '#fff';
        indicator.style.border = 'none';
    }
    
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
    document.getElementById('post_seo_keywords').value = '';
    document.getElementById('post_meta_title').value = '';
    document.getElementById('post_meta_description').value = '';
    document.getElementById('post_slug').value = '';
    document.getElementById('post_created_at').value = '';
    document.getElementById('ai_image_prompt').value = '';
    document.getElementById('ai_generated_image').value = '';
    document.getElementById('ai_img_preview_container').style.display = 'none';
    document.getElementById('post_status').value = 'publicado';
    document.getElementById('status_indicator').style.display = 'none';
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

async function gerarAltTextIA() {
    const title = document.getElementById('post_title').value.trim();
    if (!title) {
        alert("Preencha o título do post primeiro para que a IA gere um texto alternativo adequado.");
        return;
    }

    const btn = document.getElementById('btn_ia_alt');
    const loading = document.getElementById('ai_alt_loading');
    
    btn.disabled = true;
    loading.style.display = 'block';

    try {
        const response = await fetch('ajax_ai_generate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prompt: 'Gere APENAS um texto alternativo curto (máximo 15 palavras) para a imagem de capa de um artigo de blog sobre: "' + title + '". O texto deve ser descritivo, acessível e bom para SEO. Retorne SOMENTE o texto puro, sem aspas, sem HTML, sem explicação.' })
        });
        
        const data = await response.json();
        
        if (data.error) {
            alert(data.error);
        } else if (data.success && data.html) {
            let altText = data.html.replace(/<[^>]*>/g, '').trim();
            altText = altText.replace(/^["']|["']$/g, '').trim();
            document.getElementById('post_image_alt').value = altText;
        }
    } catch (e) {
        alert("Erro: " + e.message);
    } finally {
        btn.disabled = false;
        loading.style.display = 'none';
    }
}

async function gerarImagemIA() {
    const prompt = document.getElementById('ai_image_prompt').value.trim();
    if (!prompt) {
        alert("Por favor, digite um prompt para gerar a imagem.");
        return;
    }

    const btn = document.getElementById('btn_ia_image');
    const loading = document.getElementById('ai_img_loading');
    const previewContainer = document.getElementById('ai_img_preview_container');
    const previewImg = document.getElementById('ai_img_preview');
    const hiddenInput = document.getElementById('ai_generated_image');
    
    btn.disabled = true;
    loading.style.display = 'block';
    previewContainer.style.display = 'none';

    try {
        const response = await fetch('ajax_ai_generate_image.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prompt: prompt })
        });
        
        const data = await response.json();
        
        if (data.error) {
            alert(data.error);
        } else if (data.success) {
            previewImg.src = data.url;
            hiddenInput.value = data.filename;
            previewContainer.style.display = 'block';
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
