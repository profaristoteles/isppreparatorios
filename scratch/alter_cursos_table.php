<?php
require '../db_config.php';
try {
    $sql = "ALTER TABLE cursos 
            ADD COLUMN payment_link VARCHAR(255) DEFAULT '',
            ADD COLUMN info_extra TEXT,
            ADD COLUMN disciplinas TEXT,
            ADD COLUMN conteudo TEXT";
    $pdo->exec($sql);
    echo "Columns added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
