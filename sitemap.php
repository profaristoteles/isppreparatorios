<?php
header("Content-type: text/xml");
require_once 'db_config.php';

$base_url = "https://isppreparatorios.com.br/";

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Páginas Estáticas
$static_pages = ['', 'aulas-gratuitas', 'cursos.php', 'apostilas.php', 'blog.php', 'contato.php', 'sobre.php'];
foreach ($static_pages as $page) {
    echo '<url>';
    echo '<loc>' . $base_url . $page . '</loc>';
    echo '<priority>0.8</priority>';
    echo '</url>';
}

// Canais de Aulas Gratuitas
try {
    $canais = $pdo->query("SELECT slug FROM free_channels WHERE active=1 AND deleted_at IS NULL")->fetchAll();
    foreach ($canais as $ch) {
        echo '<url>';
        echo '<loc>' . $base_url . 'aulas-gratuitas/' . $ch['slug'] . '</loc>';
        echo '<priority>0.8</priority>';
        echo '</url>';
    }
} catch (Exception $e) {}

// Bancas Organizadoras
try {
    $bancas = $pdo->query("SELECT slug FROM free_boards WHERE active=1 AND deleted_at IS NULL")->fetchAll();
    foreach ($bancas as $b) {
        echo '<url>';
        echo '<loc>' . $base_url . 'aulas-gratuitas/banca/' . $b['slug'] . '</loc>';
        echo '<priority>0.7</priority>';
        echo '</url>';
    }
} catch (Exception $e) {}

// Videoaulas Publicadas
try {
    $videos = $pdo->query("SELECT v.slug as video_slug, c.slug as channel_slug FROM free_videos v JOIN free_channels c ON v.channel_id = c.id WHERE v.status = 'publicado' AND v.active = 1 AND v.deleted_at IS NULL")->fetchAll();
    foreach ($videos as $v) {
        echo '<url>';
        echo '<loc>' . $base_url . 'aulas-gratuitas/' . $v['channel_slug'] . '/' . $v['video_slug'] . '</loc>';
        echo '<priority>0.8</priority>';
        echo '</url>';
    }
} catch (Exception $e) {}

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

// Eventos
$eventos = $pdo->query("SELECT id, slug FROM eventos WHERE active=1")->fetchAll();
foreach ($eventos as $e) {
    echo '<url>';
    $urlPath = !empty($e['slug']) ? 'evento/' . $e['slug'] : 'evento-detalhes.php?id=' . $e['id'];
    echo '<loc>' . $base_url . $urlPath . '</loc>';
    echo '<priority>0.8</priority>';
    echo '</url>';
}

// Campanhas de Reserva / Novas Turmas
try {
    $campanhas = $pdo->query("SELECT slug FROM reservation_campaigns WHERE active=1 AND status IN ('reservas_abertas', 'matriculas_abertas', 'turma_confirmada')")->fetchAll();
    foreach ($campanhas as $camp) {
        echo '<url>';
        echo '<loc>' . $base_url . 'reserva/' . $camp['slug'] . '</loc>';
        echo '<priority>0.8</priority>';
        echo '</url>';
    }
} catch (Exception $e) {}

echo '</urlset>';
