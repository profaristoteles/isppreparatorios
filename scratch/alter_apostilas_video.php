<?php
require '../db_config.php';
try {
    $sql = "ALTER TABLE apostilas 
            ADD COLUMN video_title VARCHAR(255) DEFAULT '',
            ADD COLUMN video_url VARCHAR(255) DEFAULT ''";
    $pdo->exec($sql);
    echo "Video columns added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
