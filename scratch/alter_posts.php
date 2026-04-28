<?php
require '../db_config.php';
try {
    $sql1 = "ALTER TABLE posts MODIFY content LONGTEXT NOT NULL";
    $pdo->exec($sql1);
    
    $sql2 = "ALTER TABLE posts ADD COLUMN category VARCHAR(100) DEFAULT 'Geral'";
    $pdo->exec($sql2);
    
    echo "Posts table altered successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
