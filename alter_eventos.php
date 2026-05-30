<?php
require_once 'db_config.php';

try {
    $pdo->exec("ALTER TABLE eventos ADD COLUMN event_end_date DATETIME NULL AFTER event_date");
    echo "Coluna event_end_date adicionada com sucesso!";
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
