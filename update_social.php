<?php
require_once 'db_config.php';

try {
    $pdo->exec("ALTER TABLE configuracoes ADD COLUMN youtube VARCHAR(255) DEFAULT NULL, ADD COLUMN tiktok VARCHAR(255) DEFAULT NULL");
    echo "Colunas youtube e tiktok adicionadas com sucesso!";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "As colunas já existem.";
    } else {
        echo "Erro: " . $e->getMessage();
    }
}
?>
