<?php
require '../db_config.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS depoimentos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        role VARCHAR(100),
        content TEXT NOT NULL,
        active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'depoimentos' created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
