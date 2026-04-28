<?php
require_once 'db_config.php';

// Verifica o usuário atual
$stmt = $pdo->query("SELECT * FROM admin_usuarios");
$users = $stmt->fetchAll();

echo "Usuários encontrados:\n";
foreach ($users as $u) {
    echo "ID: " . $u['id'] . " | E-mail: " . $u['email'] . "\n";
}

// Reseta a senha para admin123
$new_password = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE admin_usuarios SET password = ? WHERE email = ?");
$email = 'admin@isppreparatorios.com.br';
$stmt->execute([$new_password, $email]);

if ($stmt->rowCount() > 0) {
    echo "\nSenha do usuário '$email' resetada com sucesso para: admin123\n";
} else {
    // Tenta inserir se não existir
    $stmt = $pdo->prepare("INSERT INTO admin_usuarios (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute(['Administrador', $email, $new_password]);
    echo "\nUsuário '$email' criado com a senha: admin123\n";
}
?>
