<?php
require '../db_config.php';
try {
    $sql = "ALTER TABLE apostilas 
            ADD COLUMN price DECIMAL(10,2) DEFAULT 0,
            ADD COLUMN is_internal BOOLEAN DEFAULT FALSE,
            ADD COLUMN subtitle VARCHAR(255) DEFAULT '',
            ADD COLUMN topics TEXT,
            ADD COLUMN preview_images TEXT";
    $pdo->exec($sql);
    echo "Columns added to apostilas successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
