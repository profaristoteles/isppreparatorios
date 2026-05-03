<?php
require_once 'db_config.php';

echo "<h3>Ajustando limites de caracteres da tabela cursos...</h3>";

try {
    // Aumentar o tamanho dos campos que podem receber textos longos acidentalmente
    $pdo->exec("ALTER TABLE cursos MODIFY COLUMN title VARCHAR(500) NOT NULL");
    $pdo->exec("ALTER TABLE cursos MODIFY COLUMN duration VARCHAR(255)");
    $pdo->exec("ALTER TABLE cursos MODIFY COLUMN modality VARCHAR(255) DEFAULT 'Presencial e Online'");
    
    echo "<p style='color: green;'>✅ Tabela <b>cursos</b> alterada com sucesso! Agora os campos Título, Duração e Modalidade aceitam textos maiores.</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erro ao alterar tabela: " . $e->getMessage() . "</p>";
}

echo "<br><a href='admin/gerenciar-cursos.php'>Voltar para Gerenciar Cursos</a>";
?>
