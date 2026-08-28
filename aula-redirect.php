<?php
/**
 * Redirector da Rota de Videoaula (/aulas-gratuitas/{canal_slug}/{video_slug})
 */
require_once 'db_config.php';

$canal_slug = trim($_GET['canal'] ?? '');
$video_slug = trim($_GET['video'] ?? '');

if (empty($canal_slug) || empty($video_slug)) {
    header("Location: /aulas-gratuitas");
    exit;
}

// Redirect 301 para o slug de canal oficial atualizado
if ($canal_slug === 'isp-resolve' && !empty($video_slug)) {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: /aulas-gratuitas/isp-preparatorios/" . urlencode($video_slug));
    exit;
}

require_once 'template-aula.php';
