<?php
require 'db_config.php';

echo "<h1>Iniciando migração de SEO (Cursos, Apostilas e Configurações)...</h1>";

// 1. Tabela configuracoes
try {
    $pdo->exec("ALTER TABLE configuracoes ADD COLUMN site_description TEXT");
    echo "Coluna 'site_description' adicionada em 'configuracoes'.<br>";
} catch(PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Coluna 'site_description' já existe em 'configuracoes'.<br>";
    } else {
        echo "Erro na tabela configuracoes: " . $e->getMessage() . "<br>";
    }
}

// 2. Tabela cursos
try {
    $pdo->exec("ALTER TABLE cursos ADD COLUMN slug VARCHAR(255) UNIQUE");
    $pdo->exec("ALTER TABLE cursos ADD COLUMN meta_title VARCHAR(255)");
    $pdo->exec("ALTER TABLE cursos ADD COLUMN meta_description TEXT");
    echo "Colunas de SEO adicionadas em 'cursos'.<br>";
} catch(PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Colunas de SEO já existem em 'cursos'.<br>";
    } else {
        echo "Erro na tabela cursos: " . $e->getMessage() . "<br>";
    }
}

// 3. Tabela apostilas
try {
    $pdo->exec("ALTER TABLE apostilas ADD COLUMN slug VARCHAR(255) UNIQUE");
    $pdo->exec("ALTER TABLE apostilas ADD COLUMN meta_title VARCHAR(255)");
    $pdo->exec("ALTER TABLE apostilas ADD COLUMN meta_description TEXT");
    echo "Colunas de SEO adicionadas em 'apostilas'.<br>";
} catch(PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Colunas de SEO já existem em 'apostilas'.<br>";
    } else {
        echo "Erro na tabela apostilas: " . $e->getMessage() . "<br>";
    }
}

// Helper de gerar slug (substitui mb_strtolower por strtolower se mbstring não existir)
function generateSlug($string) {
    $string = trim($string);
    if (function_exists('mb_strtolower')) {
        $string = mb_strtolower($string, 'UTF-8');
    } else {
        $string = strtolower($string);
    }
    
    // Substituir acentos manualmente
    $chars = array(
        'a' => '/[áàâãä]/u',
        'e' => '/[éèêë]/u',
        'i' => '/[íìîï]/u',
        'o' => '/[óòôõö]/u',
        'u' => '/[úùûü]/u',
        'c' => '/[ç]/u',
        'n' => '/[ñ]/u'
    );
    foreach ($chars as $replacement => $pattern) {
        $string = preg_replace($pattern, $replacement, $string);
    }

    $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Popular Cursos
echo "<h3>Populando slugs de cursos...</h3>";
$cursos = $pdo->query("SELECT id, title FROM cursos WHERE slug IS NULL OR slug = ''")->fetchAll();
foreach ($cursos as $c) {
    $slug = generateSlug($c['title']);
    
    // Garantir unicidade
    $originalSlug = $slug;
    $counter = 1;
    while (true) {
        $stmtCheck = $pdo->prepare("SELECT id FROM cursos WHERE slug = ? AND id != ?");
        $stmtCheck->execute([$slug, $c['id']]);
        if (!$stmtCheck->fetch()) break;
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    $pdo->prepare("UPDATE cursos SET slug = ? WHERE id = ?")->execute([$slug, $c['id']]);
    echo "Curso #{$c['id']} -> {$slug}<br>";
}

// Popular Apostilas
echo "<h3>Populando slugs de apostilas...</h3>";
$apostilas = $pdo->query("SELECT id, title FROM apostilas WHERE slug IS NULL OR slug = ''")->fetchAll();
foreach ($apostilas as $a) {
    $slug = generateSlug($a['title']);
    
    // Garantir unicidade
    $originalSlug = $slug;
    $counter = 1;
    while (true) {
        $stmtCheck = $pdo->prepare("SELECT id FROM apostilas WHERE slug = ? AND id != ?");
        $stmtCheck->execute([$slug, $a['id']]);
        if (!$stmtCheck->fetch()) break;
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    $pdo->prepare("UPDATE apostilas SET slug = ? WHERE id = ?")->execute([$slug, $a['id']]);
    echo "Apostila #{$a['id']} -> {$slug}<br>";
}

echo "<h2>Migração concluída com sucesso!</h2>";
?>
