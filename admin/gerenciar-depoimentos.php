<?php
require_once 'auth.php';
require_once '../db_config.php';

if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $pdo->query("DELETE FROM depoimentos WHERE id = $id");
    $_SESSION['msg'] = "Depoimento removido.";
    header("Location: gerenciar-depoimentos.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $role = $_POST['role'];
    $content = $_POST['content'];
    $active = isset($_POST['active']) ? 1 : 0;

    if (!empty($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE depoimentos SET name=?, role=?, content=?, active=? WHERE id=?");
        $stmt->execute([$name, $role, $content, $active, $_POST['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO depoimentos (name, role, content, active) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $role, $content, $active]);
    }
    $_SESSION['msg'] = "Depoimento salvo com sucesso.";
    header("Location: gerenciar-depoimentos.php");
    exit;
}

$depoimentos = $pdo->query("SELECT * FROM depoimentos ORDER BY id DESC")->fetchAll();
require_once 'includes/header.php';
?>

<div class="card">
    <h2>Gerenciar Depoimentos</h2>
    <form method="POST">
        <input type="hidden" name="id" id="depo_id">
        <div class="form-group">
            <label>Nome do Aluno</label>
            <input type="text" name="name" id="depo_name" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Cargo / Contexto (Ex: Aprovado no Concurso X)</label>
            <input type="text" name="role" id="depo_role" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Texto do Depoimento</label>
            <textarea name="content" id="depo_content" class="form-control" rows="5" required></textarea>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="active" id="depo_active" value="1" checked> Ativo
            </label>
        </div>
        <button type="submit" class="btn">Salvar Depoimento</button>
        <button type="button" class="btn btn-warning" onclick="resetForm()">Novo</button>
    </form>
</div>

<div class="card">
    <table class="table">
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>Cargo/Contexto</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
        <?php foreach($depoimentos as $d): ?>
        <tr>
            <td><?= $d['id'] ?></td>
            <td><?= htmlspecialchars($d['name']) ?></td>
            <td><?= htmlspecialchars($d['role']) ?></td>
            <td><?= $d['active'] ? 'Ativo' : 'Inativo' ?></td>
            <td>
                <button class="btn" onclick='editarDepoimento(<?= json_encode($d) ?>)'>Editar</button>
                <a href="?del=<?= $d['id'] ?>" class="btn btn-danger" onclick="return confirm('Excluir?')">Excluir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
function editarDepoimento(d) {
    document.getElementById('depo_id').value = d.id;
    document.getElementById('depo_name').value = d.name;
    document.getElementById('depo_role').value = d.role;
    document.getElementById('depo_content').value = d.content;
    document.getElementById('depo_active').checked = d.active == 1;
    window.scrollTo(0,0);
}
function resetForm() {
    document.getElementById('depo_id').value = '';
    document.getElementById('depo_name').value = '';
    document.getElementById('depo_role').value = '';
    document.getElementById('depo_content').value = '';
    document.getElementById('depo_active').checked = true;
}
</script>
<?php require_once 'includes/footer.php'; ?>
