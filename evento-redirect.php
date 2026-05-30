<?php
require_once 'db_config.php';

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    header("Location: /eventos.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM eventos WHERE slug = ? AND active = 1");
$stmt->execute([$slug]);
$evento = $stmt->fetch();

if (!$evento) {
    header("HTTP/1.0 404 Not Found");
    echo "<h1>Evento não encontrado</h1><a href='/eventos.php'>Voltar para a lista</a>";
    exit;
}

require 'template-evento.php';
