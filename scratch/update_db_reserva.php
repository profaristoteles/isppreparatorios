<?php
require_once __DIR__ . '/../db_config.php';

try {
    $pdo->exec("ALTER TABLE cursos ADD COLUMN status VARCHAR(50) DEFAULT 'Disponível'");
    echo "Coluna 'status' adicionada com sucesso na tabela 'cursos'.\n";
} catch (PDOException $e) {
    echo "Erro ao adicionar coluna (provavelmente já existe): " . $e->getMessage() . "\n";
}
