<?php
require '../db_config.php';
try {
    $sql = "ALTER TABLE apostilas 
            ADD COLUMN hero_btn_text VARCHAR(100) DEFAULT 'Garantir Meu Material',
            ADD COLUMN sec1_title VARCHAR(255) DEFAULT 'O que você vai encontrar no material?',
            ADD COLUMN sec1_btn_text VARCHAR(100) DEFAULT 'Quero Ter Acesso Agora',
            ADD COLUMN sec2_title VARCHAR(255) DEFAULT 'Veja o Material por Dentro',
            ADD COLUMN sec3_title VARCHAR(255) DEFAULT 'Acelerando sua Aprovação',
            ADD COLUMN sec3_btn_text VARCHAR(100) DEFAULT 'Comprar Agora'";
    $pdo->exec($sql);
    echo "Columns added to apostilas successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
