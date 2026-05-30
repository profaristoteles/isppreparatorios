<?php
header("Content-type: text/xml");
require_once 'db_config.php';

$base_url = "https://isppreparatorios.com.br/";

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Páginas Estáticas
$static_pages = ['', 'cursos.php', 'apostilas.php', 'blog.php', 'contato.php', 'sobre.php'];
foreach ($static_pages as $page) {
    echo '<url>';
    echo '<loc>' . $base_url . $page . '</loc>';
    echo '<priority>0.8</priority>';
    echo '</url>';
}

// Cursos
$cursos = $pdo->query("SELECT id, slug FROM cursos WHERE active=1")->fetchAll();
foreach ($cursos as $c) {
    echo '<url>';
    $urlPath = !empty($c['slug']) ? 'curso/' . $c['slug'] : 'curso-detalhes.php?id=' . $c['id'];
    echo '<loc>' . $base_url . $urlPath . '</loc>';
    echo '<priority>0.7</priority>';
    echo '</url>';
}

// Apostilas Internas
$apostilas = $pdo->query("SELECT id, slug FROM apostilas WHERE active=1 AND is_internal=1")->fetchAll();
foreach ($apostilas as $a) {
    echo '<url>';
    $urlPath = !empty($a['slug']) ? 'apostila/' . $a['slug'] : 'apostila-detalhes.php?id=' . $a['id'];
    echo '<loc>' . $base_url . $urlPath . '</loc>';
    echo '<priority>0.7</priority>';
    echo '</url>';
}

// Posts do Blog
$posts = $pdo->query("SELECT id, slug FROM posts WHERE active=1 AND created_at <= NOW()")->fetchAll();
foreach ($posts as $p) {
    echo '<url>';
    $urlPath = !empty($p['slug']) ? 'blog/' . $p['slug'] : 'post.php?id=' . $p['id'];
    echo '<loc>' . $base_url . $urlPath . '</loc>';
    echo '<priority>0.6</priority>';
    echo '</url>';
}

echo '</urlset>';
?>
