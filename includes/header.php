<?php
require_once 'db_config.php';
$site_config = get_config($pdo);

// Sobrescrevendo cores via variáveis CSS inline, se configuradas no BD
$primary = $site_config['theme_color_primary'] ?? '#03045e';
$secondary = $site_config['theme_color_secondary'] ?? '#ff8000';

$tem_depoimentos = $pdo->query("SELECT count(*) FROM depoimentos WHERE active=1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-N66CW53H');</script>
    <!-- End Google Tag Manager -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
        $page_title = isset($dynamic_title) ? $dynamic_title . " | ISP Preparatórios" : "ISP Preparatórios | Elite - Concursos Públicos em Caxias, MA";
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    ?>
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="icon" type="image/png" href="/uploads/favicon.png">
    <link rel="canonical" href="<?= htmlspecialchars($current_url) ?>">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars($dynamic_desc ?? 'ISP Preparatórios — Cursos preparatórios para concursos públicos em Caxias, MA. Aprove-se com nossa metodologia de elite.') ?>">
    <meta name="keywords" content="<?= !empty($dynamic_keywords) ? htmlspecialchars($dynamic_keywords) : 'concurso público, caxias ma, preparatório, educação especial, inclusiva, curso online, curso presencial' ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($current_url) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($dynamic_desc ?? 'ISP Preparatórios — Cursos preparatórios para concursos públicos em Caxias, MA.') ?>">
    <meta property="og:image" content="https://isppreparatorios.com.br/uploads/logo.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= htmlspecialchars($current_url) ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="twitter:description" content="<?= htmlspecialchars($dynamic_desc ?? 'ISP Preparatórios — Cursos preparatórios para concursos públicos em Caxias, MA.') ?>">
    <meta property="twitter:image" content="https://isppreparatorios.com.br/uploads/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/css/accessibility.css?v=<?= time() ?>">
    <style>
        :root {
            --brand-blue: <?= htmlspecialchars($primary) ?>;
            --brand-orange: <?= htmlspecialchars($secondary) ?>;
            --prism-cyan: <?= htmlspecialchars($primary) ?>80; /* Com transparência */
            --prism-violet: <?= htmlspecialchars($primary) ?>;
            --prism-amber: <?= htmlspecialchars($secondary) ?>80; /* Com transparência */
        }
    </style>
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '854510411013557');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=854510411013557&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
</head>
<body>
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-N66CW53H"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    <!-- Skip Navigation (Acessibilidade) -->
    <a href="#main-content" class="skip-link">Pular para o conteúdo principal</a>

    <div class="prism-overlay" aria-hidden="true"></div>

    <nav role="navigation" aria-label="Menu principal">
        <a href="/index.php" style="text-decoration: none;" aria-label="ISP Preparatórios — Página inicial">
            <div class="logo">
                <?php if(file_exists(__DIR__ . '/../uploads/logo.png') || file_exists('uploads/logo.png')): ?>
                    <img src="/uploads/logo.png" alt="Logo ISP Preparatórios">
                <?php else: ?>
                    ISP.
                <?php endif; ?>
            </div>
        </a>
        <button class="mobile-menu-toggle" aria-label="Abrir menu de navegação" aria-expanded="false" style="display: none; cursor: pointer; color: var(--text-primary); background: none; border: none;">
            <svg viewBox="0 0 24 24" width="30" height="30" fill="currentColor" aria-hidden="true">
                <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
            </svg>
        </button>
        <div class="nav-links" role="menubar">
            <a href="/index.php" role="menuitem">Início</a>
            <a href="/index.php#diferenciais" role="menuitem">Diferenciais</a>
            <a href="/cursos.php" role="menuitem">Cursos</a>
            <a href="/editais.php" role="menuitem">Editais</a>
            <a href="/index.php#modalidades" role="menuitem">Modalidades</a>
            <?php if($tem_depoimentos > 0): ?>
                <a href="/index.php#depoimentos" role="menuitem">Depoimentos</a>
            <?php endif; ?>
            <a href="/blog.php" role="menuitem">Blog</a>
            <a href="/apostilas.php" role="menuitem">Apostilas</a>
            <a href="https://ispreparatorios.classbuild.com" target="_blank" rel="noopener noreferrer" class="nav-btn-alt" role="menuitem" aria-label="Área do Aluno - abre em nova aba">Já sou Aluno(a)</a>
        </div>
    </nav>

    <!-- Widget de Acessibilidade -->
    <div id="accessibility-widget" class="a11y-widget" role="complementary" aria-label="Opções de acessibilidade">
        <button id="a11y-toggle" class="a11y-toggle-btn" aria-label="Abrir opções de acessibilidade" aria-expanded="false" title="Acessibilidade">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor" aria-hidden="true">
                <path d="M12 2c1.1 0 2 .9 2 2s-.9 2-2 2-2-.9-2-2 .9-2 2-2zm9 7h-6v13h-2v-6h-2v6H9V9H3V7h18v2z"/>
            </svg>
        </button>
        <div id="a11y-panel" class="a11y-panel" role="dialog" aria-label="Painel de acessibilidade" aria-hidden="true">
            <h3>Acessibilidade</h3>
            <div class="a11y-option">
                <span>Tamanho do texto</span>
                <div class="a11y-controls">
                    <button onclick="changeFontSize(-1)" aria-label="Diminuir tamanho do texto" title="Diminuir texto">A-</button>
                    <button onclick="changeFontSize(0)" aria-label="Tamanho padrão do texto" title="Tamanho padrão">A</button>
                    <button onclick="changeFontSize(1)" aria-label="Aumentar tamanho do texto" title="Aumentar texto">A+</button>
                </div>
            </div>
            <div class="a11y-option">
                <span>Alto contraste</span>
                <button onclick="toggleHighContrast()" id="btn-contrast" aria-label="Ativar alto contraste" title="Alto contraste">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 2a10 10 0 0 1 0 20V2z"/></svg>
                </button>
            </div>
            <div class="a11y-option">
                <span>Destacar links</span>
                <button onclick="toggleLinkHighlight()" id="btn-links" aria-label="Destacar links na página" title="Destacar links">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
                </button>
            </div>
            <div class="a11y-option">
                <span>Fonte legível</span>
                <button onclick="toggleReadableFont()" id="btn-font" aria-label="Ativar fonte mais legível" title="Fonte legível">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M9.93 13.5h4.14L12 7.98 9.93 13.5zM20 2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-4.05 16.5-1.14-3H9.17l-1.12 3H5.96l5.11-13h1.86l5.11 13h-2.09z"/></svg>
                </button>
            </div>
        </div>
    </div>

    <main id="main-content" role="main">
