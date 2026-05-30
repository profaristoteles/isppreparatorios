<?php
require_once 'db_config.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS eventos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE,
        description TEXT,
        event_date DATETIME,
        thumbnail VARCHAR(255),
        form_type VARCHAR(50) DEFAULT 'link',
        form_link VARCHAR(255),
        form_embed TEXT,
        active TINYINT DEFAULT 1,
        meta_title VARCHAR(255),
        meta_description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Migração de Eventos concluída com sucesso! Tabela 'eventos' criada ou já existia.";
} catch (PDOException $e) {
    echo "Erro na migração: " . $e->getMessage();
}
