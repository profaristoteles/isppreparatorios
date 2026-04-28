<?php
require_once 'auth.php';
require_once '../db_config.php';

// Contagens para o Dashboard
$tot_cursos = $pdo->query("SELECT COUNT(*) FROM cursos")->fetchColumn();
$tot_inscricoes = $pdo->query("SELECT COUNT(*) FROM inscricoes")->fetchColumn();
$tot_posts = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();

require_once 'includes/header.php';
?>

<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem;">
    <div class="card">
        <h3>Total de Cursos</h3>
        <p style="font-size: 2rem; font-weight: bold; color: #ff8000;"><?= $tot_cursos ?></p>
    </div>
    <div class="card">
        <h3>Total de Inscrições (Leads)</h3>
        <p style="font-size: 2rem; font-weight: bold; color: #ff8000;"><?= $tot_inscricoes ?></p>
    </div>
    <div class="card">
        <h3>Posts no Blog</h3>
        <p style="font-size: 2rem; font-weight: bold; color: #ff8000;"><?= $tot_posts ?></p>
    </div>
</div>

<div class="card" style="margin-top: 2rem;">
    <h3>Últimas Inscrições</h3>
    <table class="table">
        <tr>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Data</th>
        </tr>
        <?php
        $ultimas = $pdo->query("SELECT name, email, created_at FROM inscricoes ORDER BY id DESC LIMIT 5")->fetchAll();
        foreach($ultimas as $i): ?>
        <tr>
            <td><?= htmlspecialchars($i['name']) ?></td>
            <td><?= htmlspecialchars($i['email']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($i['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(count($ultimas) == 0): ?>
            <tr><td colspan="3">Nenhuma inscrição recente.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
