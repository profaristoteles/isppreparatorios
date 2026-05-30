<?php
require_once 'db_config.php';

try {
    $pdo->exec("ALTER TABLE eventos ADD COLUMN schedule TEXT NULL AFTER description");
    echo "Coluna schedule adicionada com sucesso!";
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
