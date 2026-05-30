<?php
require 'db_config.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $stmt = $pdo->prepare("SELECT slug FROM cursos WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($curso && !empty($curso['slug'])) {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: /curso/" . $curso['slug']);
        exit;
    }
}

header("HTTP/1.0 404 Not Found");
echo "<div style='text-align:center; padding: 50px; font-family: sans-serif;'><h1>404</h1><p>Curso não encontrado.</p></div>";
exit;
