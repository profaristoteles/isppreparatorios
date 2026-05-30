<?php
require_once 'db_config.php';

try {
    $pdo->exec("ALTER TABLE eventos ADD COLUMN video_embed TEXT NULL AFTER thumbnail");
    echo "Coluna video_embed adicionada com sucesso!";
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
