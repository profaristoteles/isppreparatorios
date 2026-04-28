<?php
require '../db_config.php';
try {
    $sql = "ALTER TABLE configuracoes ADD COLUMN ai_api_key VARCHAR(255) DEFAULT ''";
    $pdo->exec($sql);
    echo "Column ai_api_key added.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
