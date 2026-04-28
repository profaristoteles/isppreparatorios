<?php
require_once 'auth.php';
require_once '../db_config.php';

$admin_id = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin_usuarios SET name=?, email=?, password=? WHERE id=?");
        $result = $stmt->execute([$name, $email, $hashed_password, $admin_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE admin_usuarios SET name=?, email=? WHERE id=?");
        $result = $stmt->execute([$name, $email, $admin_id]);
    }

    if ($result) {
        $_SESSION['admin_name'] = $name;
        $_SESSION['msg'] = "Perfil atualizado com sucesso!";
    } else {
        $_SESSION['erro'] = "Erro ao atualizar perfil.";
    }
    header("Location: perfil.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM admin_usuarios WHERE id = ?");
$stmt->execute([$admin_id]);
$user = $stmt->fetch();

require_once 'includes/header.php';
?>

<div class="card">
    <h2>Meu Perfil</h2>
    <form method="POST">
        <div class="form-group">
            <label>Nome</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>

        <div class="form-group">
            <label>E-mail (Usuário)</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <div class="form-group">
            <label>Nova Senha (deixe em branco para manter a atual)</label>
            <input type="password" name="password" class="form-control">
        </div>

        <button type="submit" class="btn" style="margin-top: 1rem;">Atualizar Perfil</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
