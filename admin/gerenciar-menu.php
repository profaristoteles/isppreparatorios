<?php
require_once 'auth.php';
require_once '../db_config.php';

// Deleção
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $pdo->query("DELETE FROM menu_items WHERE id = $id");
    $_SESSION['msg'] = "Item removido com sucesso.";
    header("Location: gerenciar-menu.php");
    exit;
}

// Inserção / Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $label = $_POST['label'];
    $url = $_POST['url'];
    $order_index = (int)$_POST['order_index'];
    $is_button = isset($_POST['is_button']) ? 1 : 0;
    $target_blank = isset($_POST['target_blank']) ? 1 : 0;
    
    // Validar limite de 2 botões
    if ($is_button == 1) {
        $id_check = !empty($_POST['id']) ? $_POST['id'] : 0;
        $countButtons = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE is_button = 1 AND id != ?");
        $countButtons->execute([$id_check]);
        $total_buttons = $countButtons->fetchColumn();
        
        if ($total_buttons >= 2) {
            $_SESSION['erro'] = "Limite de botões atingido! Você só pode ter no máximo 2 itens como botão no menu.";
            header("Location: gerenciar-menu.php");
            exit;
        }
    }

    try {
        if (!empty($_POST['id'])) {
            // Update
            $stmt = $pdo->prepare("UPDATE menu_items SET label=?, url=?, order_index=?, is_button=?, target_blank=? WHERE id=?");
            $stmt->execute([$label, $url, $order_index, $is_button, $target_blank, $_POST['id']]);
            $_SESSION['msg'] = "Item do menu atualizado.";
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO menu_items (label, url, order_index, is_button, target_blank) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$label, $url, $order_index, $is_button, $target_blank]);
            $_SESSION['msg'] = "Item do menu adicionado.";
        }
    } catch (\PDOException $e) {
        $_SESSION['erro'] = "Erro ao salvar no banco de dados: " . $e->getMessage();
    }
    header("Location: gerenciar-menu.php");
    exit;
}

$itens = $pdo->query("SELECT * FROM menu_items ORDER BY order_index ASC")->fetchAll();

require_once 'includes/header.php';
?>

<div class="card">
    <h2>Adicionar / Editar Item de Menu</h2>
    <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Crie ou edite os links que aparecem na barra de navegação principal (ao lado do logo).</p>
    
    <form method="POST">
        <input type="hidden" name="id" id="menu_id">
        
        <div style="display:flex; gap:1rem; flex-wrap: wrap;">
            <div class="form-group" style="flex:2; min-width: 250px;">
                <label>Texto do Menu (Label)</label>
                <input type="text" name="label" id="menu_label" class="form-control" required placeholder="Ex: Cursos">
            </div>
            <div class="form-group" style="flex:3; min-width: 300px;">
                <label>URL (Link de destino)</label>
                <input type="text" name="url" id="menu_url" class="form-control" required placeholder="Ex: /cursos.php ou https://...">
            </div>
            <div class="form-group" style="flex:1; min-width: 100px;">
                <label>Ordem (Número)</label>
                <input type="number" name="order_index" id="menu_order_index" class="form-control" required value="0">
            </div>
        </div>

        <div style="background: #f8f9fa; padding: 1.5rem; border: 1px solid #ddd; border-radius: 8px; margin-top: 1rem; margin-bottom: 1.5rem; color: #333;">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="cursor: pointer; display: flex; align-items: center; font-weight: normal;">
                    <input type="checkbox" name="is_button" id="menu_is_button" value="1" style="transform: scale(1.2); margin-right: 10px;"> 
                    <div>
                        <strong>Transformar em Botão</strong><br>
                        <small style="color: #666;">O item ficará destacado visualmente. Máximo de 2 botões no menu.</small>
                    </div>
                </label>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label style="cursor: pointer; display: flex; align-items: center; font-weight: normal;">
                    <input type="checkbox" name="target_blank" id="menu_target_blank" value="1" style="transform: scale(1.2); margin-right: 10px;"> 
                    <div>
                        <strong>Abrir em nova aba</strong><br>
                        <small style="color: #666;">Marque se o link leva para fora do site (Ex: Área do Aluno, WhatsApp).</small>
                    </div>
                </label>
            </div>
        </div>

        <button type="submit" class="btn">Salvar Item</button>
        <button type="button" class="btn btn-warning" onclick="resetForm()">Novo Item</button>
    </form>
</div>

<div class="card">
    <h2>Menu Atual</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Ordem</th>
                <th>Texto (Label)</th>
                <th>URL</th>
                <th>Aparência</th>
                <th>Alvo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($itens as $i): ?>
            <tr>
                <td><strong><?= $i['order_index'] ?></strong></td>
                <td><?= htmlspecialchars($i['label']) ?></td>
                <td><code style="background: #f4f4f4; padding: 2px 5px; color: #c7254e;"><?= htmlspecialchars($i['url']) ?></code></td>
                <td>
                    <?php if($i['is_button']): ?>
                        <span style="background: #ff8000; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold;">Botão</span>
                    <?php else: ?>
                        <span style="color: #666; font-size: 0.9em;">Link Simples</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($i['target_blank']): ?>
                        <span style="color: #03045e; font-size: 0.9em;">🔗 Nova Aba</span>
                    <?php else: ?>
                        <span style="color: #666; font-size: 0.9em;">Mesma Página</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn" onclick="editarItem(<?= htmlspecialchars(json_encode($i), ENT_QUOTES, 'UTF-8') ?>)">Editar</button>
                    <a href="?del=<?= $i['id'] ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function editarItem(item) {
    document.getElementById('menu_id').value = item.id;
    document.getElementById('menu_label').value = item.label;
    document.getElementById('menu_url').value = item.url;
    document.getElementById('menu_order_index').value = item.order_index;
    document.getElementById('menu_is_button').checked = item.is_button == 1;
    document.getElementById('menu_target_blank').checked = item.target_blank == 1;
    window.scrollTo(0,0);
}

function resetForm() {
    document.getElementById('menu_id').value = '';
    document.getElementById('menu_label').value = '';
    document.getElementById('menu_url').value = '';
    document.getElementById('menu_order_index').value = '0';
    document.getElementById('menu_is_button').checked = false;
    document.getElementById('menu_target_blank').checked = false;
}
</script>

<?php require_once 'includes/footer.php'; ?>
