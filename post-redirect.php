<?php
require 'db_config.php';

$slug = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$slug) {
    header("HTTP/1.0 400 Bad Request");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header("HTTP/1.0 404 Not Found");
    echo "<div style='text-align:center; padding: 50px; font-family: sans-serif;'><h1>404</h1><p>Post não encontrado.</p></div>";
    exit;
}

// Renderiza o artigo normalmente
include 'template-post.php';
