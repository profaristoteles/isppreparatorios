<?php
/**
 * Redirector da Rota de Canal (/aulas-gratuitas/{canal_slug})
 */
require_once 'db_config.php';

$canal_slug = trim($_GET['slug'] ?? '');
if (empty($canal_slug)) {
    header("Location: /aulas-gratuitas");
    exit;
}

// Redirect 301 para o slug oficial atualizado
if ($canal_slug === 'isp-resolve') {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: /aulas-gratuitas/isp-preparatorios");
    exit;
}

require_once 'template-canal.php';
