<?php
require_once 'db_config.php';

$tables = ['cursos', 'apostilas', 'posts', 'banners'];

echo "<h3>Iniciando adição de colunas image_alt...</h3>";

foreach ($tables as $table) {
    try {
        $pdo->exec("ALTER TABLE $table ADD COLUMN image_alt VARCHAR(255) DEFAULT NULL");
        echo "<p style='color: green;'>✅ Coluna <b>image_alt</b> adicionada à tabela <b>$table</b>!</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: blue;'>ℹ️ Coluna <b>image_alt</b> já existe em <b>$table</b>.</p>";
        } else {
            echo "<p style='color: red;'>❌ Erro em <b>$table</b>: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<h4>Operação concluída!</h4>";
?>
