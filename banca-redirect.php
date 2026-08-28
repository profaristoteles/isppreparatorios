<?php
/**
 * Redirector da Rota de Banca (/aulas-gratuitas/banca/{slug})
 */
require_once 'db_config.php';

$banca_slug = trim($_GET['slug'] ?? '');
if (empty($banca_slug)) {
    header("Location: /aulas-gratuitas");
    exit;
}

require_once 'template-banca.php';
