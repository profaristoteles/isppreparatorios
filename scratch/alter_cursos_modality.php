<?php
require '../db_config.php';
try {
    $sql = "ALTER TABLE cursos ADD COLUMN modality VARCHAR(100) DEFAULT '100% Online / Presencial'";
    $pdo->exec($sql);
    echo "Column modality added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
