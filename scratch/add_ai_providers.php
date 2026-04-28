<?php
require_once __DIR__ . '/../db_config.php';

$queries = [
    "ALTER TABLE configuracoes ADD COLUMN IF NOT EXISTS ai_provider VARCHAR(20) DEFAULT 'gemini'",
    "ALTER TABLE configuracoes ADD COLUMN IF NOT EXISTS ai_groq_key VARCHAR(255) DEFAULT ''",
    "ALTER TABLE configuracoes ADD COLUMN IF NOT EXISTS ai_openai_key VARCHAR(255) DEFAULT ''",
    "ALTER TABLE configuracoes ADD COLUMN IF NOT EXISTS ai_openrouter_key VARCHAR(255) DEFAULT ''",
];

foreach ($queries as $q) {
    try {
        $pdo->exec($q);
        echo "OK: $q\n";
    } catch (Exception $e) {
        echo "SKIP: " . $e->getMessage() . "\n";
    }
}
echo "\nColunas atualizadas com sucesso!";
