<?php
require_once 'auth.php';
require_once '../db_config.php';

// Criar pasta de uploads se não existir
if (!is_dir('../uploads')) { mkdir('../uploads', 0777, true); }

// Deleção
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $pdo->query("DELETE FROM eventos WHERE id = $id");
    $pdo->prepare("DELETE FROM lotes WHERE item_type = 'evento' AND item_id = ?")->execute([$id]);
    $_SESSION['msg'] = "Evento removido.";
    header("Location: gerenciar-eventos.php");
    exit;
}

// Inserção / Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $schedule = $_POST['schedule'] ?? '';
    $event_date = str_replace('T', ' ', $_POST['event_date']) . ':00'; // Formata datetime-local para MySQL DATETIME
    
    // Tratamento da data de término (opcional)
    $event_end_date = !empty($_POST['event_end_date']) ? str_replace('T', ' ', $_POST['event_end_date']) . ':00' : null;
    
    $form_type = $_POST['form_type'];
    $form_link = $_POST['form_link'] ?? '';
    $form_embed = $_POST['form_embed'] ?? '';
    $active = isset($_POST['active']) ? 1 : 0;
    
    // Valores e Links
    $price = !empty($_POST['price']) ? $_POST['price'] : null;
    $payment_link = $_POST['payment_link'] ?? '';
    $price_presencial = !empty($_POST['price_presencial']) ? $_POST['price_presencial'] : null;
    $link_presencial = $_POST['link_presencial'] ?? '';
    $price_online = !empty($_POST['price_online']) ? $_POST['price_online'] : null;
    $link_online = $_POST['link_online'] ?? '';

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
            $evento_id = (int)$_POST['id'];
            // Update
            if ($thumbnail) {
                $stmt = $pdo->prepare("UPDATE eventos SET title=?, description=?, schedule=?, event_date=?, event_end_date=?, form_type=?, form_link=?, form_embed=?, price=?, payment_link=?, price_presencial=?, link_presencial=?, price_online=?, link_online=?, active=?, thumbnail=?, slug=?, meta_title=?, meta_description=?, video_embed=? WHERE id=?");
                $stmt->execute([$title, $description, $schedule, $event_date, $event_end_date, $form_type, $form_link, $form_embed, $price, $payment_link, $price_presencial, $link_presencial, $price_online, $link_online, $active, $thumbnail, $slug, $meta_title, $meta_description, $video_embed, $evento_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE eventos SET title=?, description=?, schedule=?, event_date=?, event_end_date=?, form_type=?, form_link=?, form_embed=?, price=?, payment_link=?, price_presencial=?, link_presencial=?, price_online=?, link_online=?, active=?, slug=?, meta_title=?, meta_description=?, video_embed=? WHERE id=?");
                $stmt->execute([$title, $description, $schedule, $event_date, $event_end_date, $form_type, $form_link, $form_embed, $price, $payment_link, $price_presencial, $link_presencial, $price_online, $link_online, $active, $slug, $meta_title, $meta_description, $video_embed, $evento_id]);
            }
            if(!isset($_SESSION['erro'])) $_SESSION['msg'] = "Evento atualizado com sucesso.";
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO eventos (title, description, schedule, event_date, event_end_date, form_type, form_link, form_embed, price, payment_link, price_presencial, link_presencial, price_online, link_online, active, thumbnail, slug, meta_title, meta_description, video_embed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $schedule, $event_date, $event_end_date, $form_type, $form_link, $form_embed, $price, $payment_link, $price_presencial, $link_presencial, $price_online, $link_online, $active, $thumbnail, $slug, $meta_title, $meta_description, $video_embed]);
            $evento_id = $pdo->lastInsertId();
            if(!isset($_SESSION['erro'])) $_SESSION['msg'] = "Evento adicionado com sucesso.";
        }

        // Salvar Lotes do Evento
        $pdo->prepare("DELETE FROM lotes WHERE item_type = 'evento' AND item_id = ?")->execute([$evento_id]);
        if (!empty($_POST['lote_name']) && is_array($_POST['lote_name'])) {
            $stmtLote = $pdo->prepare("INSERT INTO lotes (item_type, item_id, lote_name, data_virada, price_presencial, link_presencial, price_online, link_online, price_geral, link_geral, order_index) VALUES ('evento', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($_POST['lote_name'] as $idx => $lname) {
                $lname = trim($lname);
                if (empty($lname)) continue;
                $dvirada = !empty($_POST['lote_data_virada'][$idx]) ? str_replace('T', ' ', $_POST['lote_data_virada'][$idx]) . ':00' : null;
                $ppres = !empty($_POST['lote_price_presencial'][$idx]) ? $_POST['lote_price_presencial'][$idx] : null;
                $lpres = $_POST['lote_link_presencial'][$idx] ?? '';
                $ponl = !empty($_POST['lote_price_online'][$idx]) ? $_POST['lote_price_online'][$idx] : null;
                $lonl = $_POST['lote_link_online'][$idx] ?? '';
                $pgeral = !empty($_POST['lote_price_geral'][$idx]) ? $_POST['lote_price_geral'][$idx] : null;
                $lgeral = $_POST['lote_link_geral'][$idx] ?? '';
                $stmtLote->execute([$evento_id, $lname, $dvirada, $ppres, $lpres, $ponl, $lonl, $pgeral, $lgeral, $idx]);
            }
        }
    } catch (\PDOException $e) {
        $_SESSION['erro'] = "Erro ao salvar no banco de dados: " . $e->getMessage();
    }
    header("Location: gerenciar-eventos.php");
    exit;
}

$eventos = $pdo->query("SELECT * FROM eventos ORDER BY event_date DESC")->fetchAll();

// Carregar lotes de todos os eventos
$lotes_by_event = [];
try {
    $stmtL = $pdo->query("SELECT * FROM lotes WHERE item_type = 'evento' ORDER BY order_index ASC, id ASC");
    foreach ($stmtL->fetchAll() as $l) {
        $lotes_by_event[$l['item_id']][] = $l;
    }
} catch (Exception $e) {}

require_once 'includes/header.php';
?>

<!-- TinyMCE CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js" referrerpolicy="origin"></script>

<div class="card">
    <h2>Adicionar / Editar Evento / Aulão</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" id="evento_id">
        
        <div class="form-group">
            <label>Título do Evento / Aulão</label>
            <input type="text" name="title" id="evento_title" class="form-control" required placeholder="Ex: Aulão de Véspera - PM & Polícia Civil">
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

        <!-- Seção: Modalidades Diferenciadas de Aulão (Presencial e Online) -->
        <div style="background: rgba(3, 4, 94, 0.4); padding: 1.5rem; border: 1px solid rgba(255,128,0,0.3); border-radius: 8px; margin-top: 1rem; margin-bottom: 1.5rem;">
            <h3 style="margin-top: 0; color: var(--brand-orange); font-size: 1.1rem; margin-bottom: 1rem;">📍 Valoração &amp; Links por Modalidade (Presencial vs Online)</h3>
            <p style="color: #ccc; font-size: 0.9rem; margin-bottom: 1rem;">
                Caso o Aulão/Evento seja vendido separadamente em <strong>Presencial</strong> e <strong>Online</strong>, preencha os valores e links abaixo:
            </p>
            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px; background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1);">
                    <h4 style="color: #fff; margin: 0 0 0.8rem 0; font-size: 0.95rem;">🏫 Presencial</h4>
                    <div class="form-group">
                        <label>Preço Presencial (R$)</label>
                        <input type="number" step="0.01" name="price_presencial" id="evento_price_presencial" class="form-control" placeholder="Ex: 80.00">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Link de Inscrição Presencial</label>
                        <input type="url" name="link_presencial" id="evento_link_presencial" class="form-control" placeholder="https://...">
                    </div>
                </div>
                <div style="flex: 1; min-width: 250px; background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1);">
                    <h4 style="color: #fff; margin: 0 0 0.8rem 0; font-size: 0.95rem;">💻 Online</h4>
                    <div class="form-group">
                        <label>Preço Online (R$)</label>
                        <input type="number" step="0.01" name="price_online" id="evento_price_online" class="form-control" placeholder="Ex: 49.90">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Link de Inscrição Online</label>
                        <input type="url" name="link_online" id="evento_link_online" class="form-control" placeholder="https://...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção: Lotes de Ingressos & Virada Automática de Data para Aulões/Eventos -->
        <div style="background: rgba(255, 128, 0, 0.08); padding: 1.5rem; border: 2px solid #ff8000; border-radius: 8px; margin-top: 1rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 style="margin: 0; color: #ff8000; font-size: 1.15rem; font-weight: 700;">🏷️ Lotes de Venda do Aulão &amp; Virada Automática por Data</h3>
                    <small style="color: #555; font-size: 0.85rem; display: block; margin-top: 2px;">Configure 1º Lote, 2º Lote e 3º Lote com data e hora limite para a virada automática de lote!</small>
                </div>
                <button type="button" onclick="addLoteRow()" style="background: #ff8000 !important; color: #ffffff !important; font-weight: bold; border: none; padding: 10px 18px; border-radius: 6px; font-size: 0.95rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(255,128,0,0.4);">
                    ➕ Adicionar Lote
                </button>
            </div>
            
            <div id="lotes_container">
                <!-- Lotes injetados via JavaScript -->
            </div>
        </div>

        <div class="form-group">
            <label>Descrição do Evento (Conteúdo para convencer a inscrição)</label>
            <textarea name="description" id="evento_desc" class="form-control" rows="3"></textarea>
        </div>
        
        <div class="form-group">
            <label>Programação do Evento <small style="color:#999;">(Opcional, preencha para exibir na página)</small></label>
            <textarea name="schedule" id="evento_schedule" class="form-control" rows="3"></textarea>
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
const lotesByEvent = <?= json_encode($lotes_by_event) ?>;
let loteCounter = 0;

function checkLotesEmptyState() {
    const container = document.getElementById('lotes_container');
    const items = container.querySelectorAll('.lote-row-item');
    let emptyNotice = document.getElementById('lotes_empty_notice');
    
    if (items.length === 0) {
        if (!emptyNotice) {
            emptyNotice = document.createElement('div');
            emptyNotice.id = 'lotes_empty_notice';
            emptyNotice.style.cssText = 'background: rgba(255, 128, 0, 0.05); border: 2px dashed #ff8000; border-radius: 8px; padding: 1.5rem; text-align: center; margin-top: 0.5rem;';
            emptyNotice.innerHTML = `
                <p style="color: #444; margin: 0 0 1rem 0; font-size: 0.95rem; font-weight: 600;">
                    💡 <strong>Nenhum lote programado no momento.</strong><br>
                    Para ativar as viradas de lotes automáticas (ex: 1º Lote, 2º Lote...), clique no botão abaixo:
                </p>
                <button type="button" onclick="addLoteRow()" style="background: #ff8000 !important; color: #ffffff !important; font-weight: bold; border: none; padding: 10px 22px; border-radius: 6px; font-size: 1rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(255,128,0,0.3);">
                    ➕ Cadastrar 1º Lote Agora
                </button>
            `;
            container.appendChild(emptyNotice);
        }
    } else {
        if (emptyNotice) {
            emptyNotice.remove();
        }
    }
}

function addLoteRow(lote = {}) {
    const emptyNotice = document.getElementById('lotes_empty_notice');
    if (emptyNotice) emptyNotice.remove();

    loteCounter++;
    const container = document.getElementById('lotes_container');
    const div = document.createElement('div');
    div.className = 'lote-row-item';
    div.style.cssText = 'background: #2b2b36; border: 1px solid rgba(255,128,0,0.4); border-radius: 8px; padding: 1.2rem; margin-bottom: 1rem; position: relative; color: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.15);';

    const dvirada = lote.data_virada ? lote.data_virada.replace(' ', 'T').substring(0, 16) : '';

    div.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem;">
            <strong style="color: #ff8000; font-size: 1rem;">🏷️ Lote #${loteCounter}</strong>
            <button type="button" onclick="this.closest('.lote-row-item').remove(); checkLotesEmptyState();" class="btn btn-sm btn-danger" style="padding: 4px 12px; font-size: 0.85rem; font-weight: bold;">✕ Remover Lote</button>
        </div>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div class="form-group" style="flex: 2; min-width: 200px;">
                <label style="font-size: 0.85rem; color: #eee;">Nome do Lote <span style="color:red;">*</span></label>
                <input type="text" name="lote_name[]" class="form-control" placeholder="Ex: 1º Lote - Ingressos Antecipados" value="${lote.lote_name || ''}" required style="background: #1f1f28; color: #fff; border: 1px solid #444;">
            </div>
            <div class="form-group" style="flex: 2; min-width: 220px;">
                <label style="font-size: 0.85rem; color: #eee;">Data/Hora Limite de Virada <small style="color:#aaa;">(Encerra este lote)</small></label>
                <input type="datetime-local" name="lote_data_virada[]" class="form-control" value="${dvirada}" style="background: #1f1f28; color: #fff; border: 1px solid #444;">
            </div>
        </div>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem; background: rgba(0,0,0,0.25); padding: 0.8rem; border-radius: 6px;">
            <div style="flex: 1; min-width: 200px;">
                <label style="font-size: 0.85rem; color: #ff9933; font-weight: bold;">🏫 Presencial (Preço & Link)</label>
                <input type="number" step="0.01" name="lote_price_presencial[]" class="form-control" placeholder="R$ Presencial" value="${lote.price_presencial || ''}" style="margin-bottom: 5px; background: #1f1f28; color: #fff; border: 1px solid #444;">
                <input type="url" name="lote_link_presencial[]" class="form-control" placeholder="Link Checkout Presencial" value="${lote.link_presencial || ''}" style="background: #1f1f28; color: #fff; border: 1px solid #444;">
            </div>
            <div style="flex: 1; min-width: 200px;">
                <label style="font-size: 0.85rem; color: #00ccff; font-weight: bold;">💻 Online (Preço & Link)</label>
                <input type="number" step="0.01" name="lote_price_online[]" class="form-control" placeholder="R$ Online" value="${lote.price_online || ''}" style="margin-bottom: 5px; background: #1f1f28; color: #fff; border: 1px solid #444;">
                <input type="url" name="lote_link_online[]" class="form-control" placeholder="Link Checkout Online" value="${lote.link_online || ''}" style="background: #1f1f28; color: #fff; border: 1px solid #444;">
            </div>
        </div>
    `;
    container.appendChild(div);
}

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
    selector: '#evento_desc, #evento_schedule',
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
    
    document.getElementById('evento_price_presencial').value = evento.price_presencial || '';
    document.getElementById('evento_link_presencial').value = evento.link_presencial || '';
    document.getElementById('evento_price_online').value = evento.price_online || '';
    document.getElementById('evento_link_online').value = evento.link_online || '';

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
    
    if (tinymce.get('evento_schedule')) {
        tinymce.get('evento_schedule').setContent(evento.schedule || '');
    } else {
        document.getElementById('evento_schedule').value = evento.schedule || '';
    }

    // Renderizar Lotes do Evento
    document.getElementById('lotes_container').innerHTML = '';
    loteCounter = 0;
    if (lotesByEvent[evento.id] && lotesByEvent[evento.id].length > 0) {
        lotesByEvent[evento.id].forEach(lote => {
            addLoteRow(lote);
        });
    } else {
        checkLotesEmptyState();
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
    document.getElementById('evento_price_presencial').value = '';
    document.getElementById('evento_link_presencial').value = '';
    document.getElementById('evento_price_online').value = '';
    document.getElementById('evento_link_online').value = '';
    document.getElementById('evento_form_type').value = 'link';
    document.getElementById('evento_form_link').value = '';
    document.getElementById('evento_form_embed').value = '';
    document.getElementById('evento_active').checked = true;
    
    document.getElementById('evento_slug').value = '';
    document.getElementById('evento_meta_title').value = '';
    document.getElementById('evento_meta_description').value = '';
    document.getElementById('evento_video_embed').value = '';

    document.getElementById('lotes_container').innerHTML = '';
    loteCounter = 0;
    checkLotesEmptyState();
    
    if (tinymce.get('evento_desc')) {
        tinymce.get('evento_desc').setContent('');
    } else {
        document.getElementById('evento_desc').value = '';
    }
    
    if (tinymce.get('evento_schedule')) {
        tinymce.get('evento_schedule').setContent('');
    } else {
        document.getElementById('evento_schedule').value = '';
    }
    
    toggleFormFields();
}

// Inicializa a verificação de lotes vazios no carregamento inicial da página
document.addEventListener('DOMContentLoaded', function() {
    checkLotesEmptyState();
});
checkLotesEmptyState();
</script>

<?php require_once 'includes/footer.php'; ?>
