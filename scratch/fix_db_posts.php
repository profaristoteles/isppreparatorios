<?php
require_once __DIR__ . '/../db_config.php';

try {
    $pdo->exec("ALTER TABLE posts MODIFY COLUMN image_alt VARCHAR(255)");
    echo "Coluna image_alt alterada com sucesso na tabela posts.\n";
} catch (PDOException $e) {
    echo "Erro ao alterar a tabela: " . $e->getMessage() . "\n";
}
