<?php
require 'db_config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $stmt = $pdo->prepare("SELECT slug FROM posts WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post && !empty($post['slug'])) {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: /blog/" . $post['slug']);
        exit;
    }
}

// Fallback caso não tenha ID ou não tenha slug (embora todos devam ter agora)
header("HTTP/1.0 404 Not Found");
echo "<div style='text-align:center; padding: 50px; font-family: sans-serif;'><h1>404</h1><p>Post não encontrado.</p></div>";
exit;
