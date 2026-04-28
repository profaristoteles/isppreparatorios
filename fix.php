<?php
require_once 'db_config.php';

try {
    $pdo->query("ALTER TABLE configuracoes ADD COLUMN ai_api_key VARCHAR(255) DEFAULT ''");
    echo "Coluna adicionada com sucesso!";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
