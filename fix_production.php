<?php
require_once 'db_config.php';

echo "<h3>Corrigindo problemas em Produção...</h3>";

// 1. Aumentar o tamanho do image_alt para TEXT nas tabelas
$tables = ['cursos', 'apostilas', 'posts', 'banners'];

foreach ($tables as $table) {
    try {
        $pdo->exec("ALTER TABLE $table MODIFY COLUMN image_alt TEXT");
        echo "<p style='color: green;'>✅ Coluna <b>image_alt</b> modificada para TEXT na tabela <b>$table</b>!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erro ao modificar a tabela <b>$table</b>: " . $e->getMessage() . "</p>";
    }
}

// 2. Corrigir permissões da pasta uploads
$uploads_dir = __DIR__ . '/uploads';
if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
    echo "<p style='color: green;'>✅ Pasta <b>uploads</b> criada.</p>";
}

if (chmod($uploads_dir, 0777)) {
    echo "<p style='color: green;'>✅ Permissões de gravação (0777) aplicadas na pasta <b>uploads</b>.</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Não foi possível alterar as permissões de <b>uploads</b> via PHP. Pode ser necessário ajustar via terminal no servidor (ex: chmod -R 777 uploads/).</p>";
}

echo "<h4>Correções concluídas! Você já pode deletar este arquivo.</h4>";
?>
