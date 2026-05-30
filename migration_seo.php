<?php
require_once 'db_config.php';

echo "Iniciando migração de SEO...\n";

// 1. Adicionar colunas
$colunas = [
    'slug' => 'VARCHAR(255) UNIQUE',
    'meta_title' => 'VARCHAR(70)',
    'meta_description' => 'VARCHAR(160)'
];

foreach ($colunas as $coluna => $tipo) {
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN $coluna $tipo");
        echo "Coluna '$coluna' adicionada com sucesso.\n";
    } catch (PDOException $e) {
        // Ignora o erro se a coluna já existir (código de erro 1060 do MySQL)
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "Coluna '$coluna' já existe.\n";
        } else {
            echo "Erro ao adicionar '$coluna': " . $e->getMessage() . "\n";
        }
    }
}

// 2. Função para gerar slug
function gerar_slug(string $texto): string {
    $mapa = [
        'Á'=>'a','À'=>'a','Ã'=>'a','Â'=>'a','Ä'=>'a',
        'É'=>'e','È'=>'e','Ê'=>'e','Ë'=>'e',
        'Í'=>'i','Ì'=>'i','Î'=>'i','Ï'=>'i',
        'Ó'=>'o','Ò'=>'o','Õ'=>'o','Ô'=>'o','Ö'=>'o',
        'Ú'=>'u','Ù'=>'u','Û'=>'u','Ü'=>'u',
        'Ç'=>'c','Ñ'=>'n',
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
        'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
        'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
        'ç'=>'c','ñ'=>'n'
    ];
    $texto = strtr($texto, $mapa);
    $texto = strtolower($texto);
    $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);
    $texto = preg_replace('/[\s-]+/', '-', trim($texto));
    return $texto;
}

// 3. Atualizar slugs de posts existentes
$stmt = $pdo->query("SELECT id, title, slug FROM posts WHERE slug IS NULL OR slug = ''");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$atualizados = 0;
foreach ($posts as $post) {
    $slugBase = gerar_slug($post['title']);
    $slug = $slugBase;
    $contador = 1;

    // Verificar se o slug já existe para outro post (em caso de títulos iguais)
    while (true) {
        $check = $pdo->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
        $check->execute([$slug, $post['id']]);
        if (!$check->fetch()) {
            break;
        }
        $slug = $slugBase . '-' . $contador;
        $contador++;
    }

    $update = $pdo->prepare("UPDATE posts SET slug = ? WHERE id = ?");
    $update->execute([$slug, $post['id']]);
    $atualizados++;
}

echo "Migração concluída! $atualizados posts atualizados com slugs gerados.\n";
