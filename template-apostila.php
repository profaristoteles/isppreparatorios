<?php
require_once 'db_config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM apostilas WHERE id = ? AND active = 1 AND is_internal = 1");
$stmt->execute([$id]);
$apostila = $stmt->fetch();

if (!$apostila) {
    header("Location: apostilas.php");
    exit;
}

function getYouTubeEmbedUrl($url) {
    $url = trim((string)$url);
    if ($url === '') {
        return '';
    }

    $parsed = parse_url($url);
    if (!$parsed || empty($parsed['host'])) {
        return '';
    }

    $host = strtolower(str_replace('www.', '', $parsed['host']));
    $path = trim($parsed['path'] ?? '', '/');
    $videoId = '';

    if (in_array($host, ['youtube.com', 'm.youtube.com'], true)) {
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $query);
            $videoId = $query['v'] ?? '';
        }

        if ($videoId === '' && preg_match('~^(embed|shorts)/([^/?#]+)~', $path, $matches)) {
            $videoId = $matches[2];
        }
    }

    if ($host === 'youtu.be') {
        $videoId = explode('/', $path)[0] ?? '';
    }

    return $videoId !== '' ? 'https://www.youtube.com/embed/' . rawurlencode($videoId) : '';
}

$topics = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $apostila['topics'] ?? '')));
$previews = !empty($apostila['preview_images']) ? array_filter(array_map('trim', explode(',', $apostila['preview_images']))) : [];
$finalText = trim($apostila['sec3_text'] ?? '') ?: 'A resolução focada de questões e o estudo direcionado através de mapas e resumos otimizados são fundamentais para garantir sua fixação de conteúdo na reta final.';
$finalBullets = trim($apostila['sec3_bullets'] ?? '') ?: "Questões selecionadas e comentadas por especialistas.\nResumos objetivos para leitura rápida e revisão.\nFormato amigável e direto ao ponto que as bancas cobram.";
$finalBullets = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $finalBullets)));
$videoEmbed = getYouTubeEmbedUrl($apostila['video_url'] ?? '');
$videoTitle = trim($apostila['video_title'] ?? '') ?: 'Conheça a apostila por dentro';
$coverAlt = trim($apostila['image_alt'] ?? '') ?: 'Capa da apostila ' . $apostila['title'];

$extraSections = [];
try {
    $extraSections = JSON_decode($apostila['extra_sections'] ?: '[]', true);
} catch(Exception $e) { $extraSections = []; }

$dynamic_title = !empty($apostila['meta_title']) ? $apostila['meta_title'] : $apostila['title'];
$dynamic_desc = !empty($apostila['meta_description']) ? $apostila['meta_description'] : ($apostila['subtitle'] ?: 'Apostila digital ISP Preparatórios com conteúdo focado para concursos.');

require_once 'includes/header.php';
?>

<style>
    /* Premium Obsidian Design System */
    :root {
        --obsidian-bg: #020617;
        --obsidian-card: #0f172a;
        --obsidian-border: rgba(255, 255, 255, 0.1);
        --obsidian-accent: #ff8000; /* Laranja */
        --obsidian-success: #ff8000; /* Botões Laranja */
        --obsidian-text: #f8fafc;
        --obsidian-text-dim: #94a3b8;
    }

    .apostila-page {
        background: var(--obsidian-bg);
        color: var(--obsidian-text);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        overflow-x: hidden;
    }

    .apostila-page .lp-section {
        padding: 6rem 5%;
        position: relative;
    }

    .apostila-page .lp-container {
        width: 90%;
        max-width: 1200px;
        margin: 0 auto;
    }

    /* Fix header overlap */
    .apostila-hero {
        padding-top: 10rem !important;
        background: radial-gradient(circle at top right, rgba(251, 191, 36, 0.05), transparent),
                    radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.05), transparent);
    }

    .apostila-hero-grid {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 4rem;
        align-items: center;
    }

    .apostila-kicker {
        color: var(--obsidian-accent);
        text-transform: uppercase;
        font-weight: 800;
        letter-spacing: 2px;
        font-size: 0.9rem;
        margin-bottom: 1rem;
        display: block;
    }

    .apostila-page h1 {
        font-size: clamp(2.5rem, 6vw, 4.5rem);
        line-height: 1.1;
        font-weight: 900;
        margin-bottom: 1.5rem;
        background: linear-gradient(to bottom right, #fff, #94a3b8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .apostila-subtitle {
        color: var(--obsidian-text-dim);
        font-size: 1.25rem;
        line-height: 1.6;
        margin-bottom: 2.5rem;
        max-width: 600px;
    }

    .apostila-price-tag {
        font-size: 2.5rem;
        font-weight: 900;
        color: var(--obsidian-accent);
        margin-bottom: 2rem;
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
    }

    .apostila-price-tag small {
        font-size: 1rem;
        color: var(--obsidian-text-dim);
        text-decoration: line-through;
    }

    .apostila-btn {
        background: var(--obsidian-success);
        color: #03045e !important;
        padding: 1.25rem 2.5rem;
        border-radius: 99px;
        font-weight: 800;
        font-size: 1.1rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
        box-shadow: 0 10px 25px rgba(255, 128, 0, 0.3);
        border: none;
        text-align: center;
    }

    .apostila-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(255, 128, 0, 0.4);
        background: #e67300;
    }

    .apostila-cover-wrap {
        position: relative;
    }

    .apostila-cover-wrap::before {
        content: '';
        position: absolute;
        inset: -20px;
        background: var(--obsidian-accent);
        filter: blur(60px);
        opacity: 0.15;
        border-radius: 50%;
        z-index: 0;
    }

    .apostila-cover {
        background: var(--obsidian-card);
        border: 1px solid var(--obsidian-border);
        padding: 1rem;
        border-radius: 20px;
        position: relative;
        z-index: 1;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .apostila-cover img {
        width: 100%;
        border-radius: 12px;
        display: block;
    }

    /* Section Styles */
    .apostila-section-title {
        font-size: clamp(2rem, 4vw, 3rem);
        font-weight: 800;
        text-align: center;
        margin-bottom: 3rem;
        color: #fff;
    }

    .glass-card {
        background: var(--obsidian-card);
        border: 1px solid var(--obsidian-border);
        border-radius: 24px;
        padding: 3rem;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }

    .topics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
    }

    .topic-item {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
        color: var(--obsidian-text-dim);
        font-size: 1.1rem;
    }

    .topic-icon {
        color: var(--obsidian-accent);
        flex-shrink: 0;
        margin-top: 3px;
    }

    /* Video Section */
    .video-section {
        background: #000;
        overflow: hidden;
    }

    .video-container {
        max-width: 900px;
        margin: 0 auto;
        border-radius: 20px;
        border: 1px solid var(--obsidian-border);
        overflow: hidden;
        box-shadow: 0 0 50px rgba(251, 191, 36, 0.1);
    }

    .video-frame {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
    }

    .video-frame iframe {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        border: 0;
    }

    /* Previews */
    .preview-scroll {
        display: flex;
        gap: 1.5rem;
        overflow-x: auto;
        padding-bottom: 2rem;
        scroll-snap-type: x mandatory;
    }

    .preview-item {
        flex: 0 0 300px;
        scroll-snap-align: start;
        border-radius: 15px;
        overflow: hidden;
        border: 1px solid var(--obsidian-border);
        transition: transform 0.3s ease;
    }

    .preview-item:hover {
        transform: scale(1.05);
    }

    .preview-item img {
        width: 100%;
        display: block;
    }

    /* Dynamic Sections */
    .extra-section {
        border-top: 1px solid var(--obsidian-border);
    }

    .extra-section-content {
        font-size: 1.2rem;
        line-height: 1.7;
        color: var(--obsidian-text-dim);
    }

    .extra-section-content h2, .extra-section-content h3 {
        color: #fff;
        margin-bottom: 1.5rem;
    }

    /* Final Section */
    .final-section {
        background: linear-gradient(to bottom, var(--obsidian-bg), #000);
        text-align: center;
    }

    .final-lead {
        font-size: 1.4rem;
        color: var(--obsidian-text-dim);
        max-width: 800px;
        margin: 0 auto 3rem;
    }

    @media (max-width: 900px) {
        .apostila-hero-grid {
            grid-template-columns: 1fr;
            text-align: center;
        }
        .apostila-subtitle {
            margin: 0 auto 2.5rem;
        }
        .apostila-price-tag {
            justify-content: center;
        }
        .lp-section {
            padding: 4rem 5%;
        }
    }
</style>

<div class="apostila-page">
    <!-- HERO SECTION -->
    <section class="lp-section apostila-hero">
        <div class="lp-container apostila-hero-grid">
            <div class="hero-content">
                <span class="apostila-kicker">Material Exclusivo ISP</span>
                <h1><?= htmlspecialchars($apostila['title']) ?></h1>
                
                <?php if (!empty($apostila['subtitle'])): ?>
                    <p class="apostila-subtitle"><?= htmlspecialchars($apostila['subtitle']) ?></p>
                <?php endif; ?>

                <div class="apostila-price-tag">
                    <small>R$ <?= number_format((float)$apostila['price'] * 1.5, 2, ',', '.') ?></small>
                    R$ <?= number_format((float)$apostila['price'], 2, ',', '.') ?>
                </div>

                <a href="<?= htmlspecialchars($apostila['payment_link']) ?>" class="apostila-btn">
                    <?= htmlspecialchars($apostila['hero_btn_text'] ?: 'Garantir meu material') ?>
                </a>
            </div>

            <div class="apostila-cover-wrap">
                <div class="apostila-cover">
                    <?php if (!empty($apostila['cover_image'])): ?>
                        <img src="uploads/<?= htmlspecialchars($apostila['cover_image']) ?>" alt="<?= htmlspecialchars($coverAlt) ?>">
                    <?php else: ?>
                        <div style="aspect-ratio: 3/4; display: grid; place-items: center; color: var(--obsidian-text-dim);">Capa em breve</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- TOPICS SECTION -->
    <?php if (!empty($topics)): ?>
    <section class="lp-section">
        <div class="lp-container">
            <h2 class="apostila-section-title"><?= htmlspecialchars($apostila['sec1_title'] ?: 'O que você vai encontrar?') ?></h2>
            <div class="glass-card">
                <div class="topics-grid">
                    <?php foreach ($topics as $topic): ?>
                        <div class="topic-item">
                            <span class="topic-icon">
                                <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            </span>
                            <span><?= htmlspecialchars($topic) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="text-align: center; margin-top: 3rem;">
                    <a href="<?= htmlspecialchars($apostila['payment_link']) ?>" class="apostila-btn" style="background: transparent; border: 2px solid var(--obsidian-accent); color: var(--obsidian-accent) !important; box-shadow: none;">
                        <?= htmlspecialchars($apostila['sec1_btn_text'] ?: 'Quero ter acesso agora') ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- DYNAMIC EXTRA SECTIONS -->
    <?php foreach ($extraSections as $sec): ?>
    <section class="lp-section extra-section">
        <div class="lp-container">
            <h2 class="apostila-section-title"><?= htmlspecialchars($sec['title']) ?></h2>
            <div class="extra-section-content">
                <?= $sec['content'] ?>
            </div>
        </div>
    </section>
    <?php endforeach; ?>

    <!-- VIDEO SECTION -->
    <?php if ($videoEmbed !== ''): ?>
    <section class="lp-section video-section">
        <div class="lp-container">
            <h2 class="apostila-section-title"><?= htmlspecialchars($videoTitle) ?></h2>
            <div class="video-container">
                <div class="video-frame">
                    <iframe src="<?= htmlspecialchars($videoEmbed) ?>" title="<?= htmlspecialchars($videoTitle) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- PREVIEWS SECTION -->
    <?php if (!empty($previews)): ?>
    <section class="lp-section">
        <div class="lp-container">
            <h2 class="apostila-section-title"><?= htmlspecialchars($apostila['sec2_title'] ?: 'Veja por dentro') ?></h2>
            <div class="preview-scroll">
                <?php foreach ($previews as $img): ?>
                    <div class="preview-item">
                        <img src="uploads/<?= htmlspecialchars($img) ?>" alt="Prévia do material">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- FINAL CTA SECTION -->
    <section class="lp-section final-section">
        <div class="lp-container">
            <h2 class="apostila-section-title"><?= htmlspecialchars($apostila['sec3_title'] ?: 'Pronto para sua aprovação?') ?></h2>
            <p class="final-lead"><?= htmlspecialchars($finalText) ?></p>

            <?php if (!empty($finalBullets)): ?>
                <div class="glass-card" style="max-width: 600px; margin: 0 auto 3rem; text-align: left;">
                    <ul style="list-style: none; padding: 0; margin: 0; display: grid; gap: 1rem;">
                        <?php foreach ($finalBullets as $item): ?>
                            <li style="display: flex; gap: 1rem; color: var(--obsidian-text);">
                                <span style="color: var(--obsidian-success);">✓</span>
                                <span><?= htmlspecialchars($item) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <a href="<?= htmlspecialchars($apostila['payment_link']) ?>" class="apostila-btn">
                <?= htmlspecialchars($apostila['sec3_btn_text'] ?: 'Comprar agora') ?>
            </a>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
