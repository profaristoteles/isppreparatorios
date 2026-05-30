<?php
require_once 'db_config.php';

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    header("Location: /apostilas.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM apostilas WHERE slug = ? AND active = 1 AND is_internal = 1 LIMIT 1");
$stmt->execute([$slug]);
$apostila_row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$apostila_row) {
    header("HTTP/1.0 404 Not Found");
    echo "<div style='text-align:center; padding: 50px; font-family: sans-serif;'><h1>404</h1><p>Apostila não encontrada.</p></div>";
    exit;
}

$_GET['id'] = $apostila_row['id'];
require 'template-apostila.php';
