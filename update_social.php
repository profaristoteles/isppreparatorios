<?php
require_once 'db_config.php';

$columns_to_add = [
    'ai_provider' => "VARCHAR(50) DEFAULT 'gemini'",
    'ai_api_key' => "VARCHAR(255) DEFAULT NULL",
    'ai_groq_key' => "VARCHAR(255) DEFAULT NULL",
    'ai_openai_key' => "VARCHAR(255) DEFAULT NULL",
    'ai_openrouter_key' => "VARCHAR(255) DEFAULT NULL",
    'youtube' => "VARCHAR(255) DEFAULT NULL",
    'tiktok' => "VARCHAR(255) DEFAULT NULL"
];

echo "<h3>Iniciando reparo do Banco de Dados...</h3>";

foreach ($columns_to_add as $column => $definition) {
    try {
        $pdo->exec("ALTER TABLE configuracoes ADD COLUMN $column $definition");
        echo "<p style='color: green;'>✅ Coluna <b>$column</b> adicionada com sucesso!</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: blue;'>ℹ️ Coluna <b>$column</b> já existe.</p>";
        } else {
            echo "<p style='color: red;'>❌ Erro ao adicionar <b>$column</b>: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<h4>Reparo concluído! Tente acessar as configurações agora.</h4>";
?>
