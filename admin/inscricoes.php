<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/header.php';

$inscricoes = $pdo->query("SELECT i.*, c.title as curso FROM inscricoes i LEFT JOIN cursos c ON i.course_id = c.id ORDER BY i.id DESC")->fetchAll();
?>

<div class="card">
    <h2>Leads / Inscrições</h2>
    <!-- Aqui poderia ter um botão para exportar CSV futuramente -->
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Telefone</th>
                <th>Curso de Interesse</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($inscricoes as $i): ?>
            <tr>
                <td><?= $i['id'] ?></td>
                <td><?= htmlspecialchars($i['name']) ?></td>
                <td><?= htmlspecialchars($i['email']) ?></td>
                <td><?= htmlspecialchars($i['phone']) ?></td>
                <td><?= htmlspecialchars($i['curso'] ?? 'Contato Geral') ?></td>
                <td><?= date('d/m/Y H:i', strtotime($i['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
