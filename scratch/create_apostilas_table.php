<?php
require '../db_config.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS apostilas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        cover_image VARCHAR(255) NOT NULL,
        payment_link VARCHAR(255) NOT NULL,
        active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'apostilas' created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
