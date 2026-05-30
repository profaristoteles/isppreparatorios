<?php
require_once 'db_config.php';

$slug = filter_input(INPUT_GET, 'slug', FILTER_SANITIZE_STRING);

if (!$slug) {
    header("Location: /cursos.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM cursos WHERE slug = ? AND active = 1 LIMIT 1");
$stmt->execute([$slug]);
$curso_row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$curso_row) {
    header("HTTP/1.0 404 Not Found");
    echo "<div style='text-align:center; padding: 50px; font-family: sans-serif;'><h1>404</h1><p>Curso não encontrado.</p></div>";
    exit;
}

$_GET['id'] = $curso_row['id'];
require 'template-curso.php';
