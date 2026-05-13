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

$dynamic_title = $apostila['title'];
$dynamic_desc = $apostila['subtitle'] ?: 'Apostila digital ISP Preparatórios com conteúdo focado para concursos.';

require_once 'includes/header.php';
?>

<style>
    .apostila-page {
        background: #f7f9fc;
        color: #172033;
    }
    .apostila-page * {
        letter-spacing: 0;
    }
    .apostila-page .lp-section {
        padding: 4.5rem 5%;
    }
    .apostila-page .lp-container {
        width: 90%;
        max-width: 1120px;
        margin: 0 auto;
    }
    .apostila-hero {
        padding-top: 9rem;
        background: linear-gradient(135deg, #ffffff 0%, #eef4ff 55%, #fff6ec 100%);
        border-bottom: 1px solid #d9e2ef;
    }
    .apostila-hero-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(280px, 0.85fr);
        gap: 3rem;
        align-items: center;
    }
    .apostila-kicker {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        color: #03045e;
        background: #e8efff;
        border: 1px solid #cbd8ff;
        padding: .45rem .8rem;
        border-radius: 999px;
        font-weight: 700;
        font-size: .86rem;
        margin-bottom: 1rem;
    }
    .apostila-page h1 {
        color: #07123d;
        font-size: clamp(2.25rem, 5vw, 4.4rem);
        line-height: 1.05;
        margin-bottom: 1rem;
    }
    .apostila-subtitle {
        color: #344057;
        font-size: 1.15rem;
        max-width: 680px;
        margin-bottom: 1.75rem;
    }
    .apostila-price {
        display: inline-block;
        color: #064e2c;
        background: #dcfce7;
        border: 1px solid #86efac;
        border-radius: 8px;
        padding: .8rem 1rem;
        font-size: 1.4rem;
        font-weight: 800;
        margin-bottom: 1.5rem;
    }
    .apostila-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: center;
    }
    .apostila-btn {
        background: #e50914;
        color: #fff !important;
        border-radius: 8px;
        padding: 1rem 1.5rem;
        text-decoration: none;
        text-transform: uppercase;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 52px;
        border: 2px solid #e50914;
        box-shadow: 0 10px 20px rgba(229, 9, 20, .18);
    }
    .apostila-btn:hover,
    .apostila-btn:focus {
        background: #b80710;
        border-color: #b80710;
        transform: none;
        box-shadow: 0 12px 22px rgba(229, 9, 20, .24);
    }
    .apostila-btn.secondary {
        background: #12823b;
        border-color: #12823b;
        box-shadow: 0 10px 20px rgba(18, 130, 59, .18);
    }
    .apostila-cover {
        background: #fff;
        border: 1px solid #d9e2ef;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 24px 50px rgba(15, 23, 42, .14);
    }
    .apostila-cover img {
        width: 100%;
        display: block;
        border-radius: 8px;
    }
    .apostila-empty-cover {
        min-height: 420px;
        display: grid;
        place-items: center;
        color: #5b6578;
        background: #edf2f7;
        border-radius: 8px;
        font-weight: 600;
    }
    .apostila-section-title {
        color: #07123d;
        text-align: center;
        font-size: clamp(1.8rem, 3vw, 2.5rem);
        margin-bottom: 1rem;
    }
    .apostila-section-lead {
        color: #4a5568;
        text-align: center;
        max-width: 760px;
        margin: 0 auto 2rem;
        font-size: 1.05rem;
    }
    .topics-box,
    .final-box {
        background: #fff;
        border: 1px solid #d9e2ef;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 18px 40px rgba(15, 23, 42, .08);
    }
    .topics-list,
    .final-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        gap: 1rem;
    }
    .topics-list li,
    .final-list li {
        display: grid;
        grid-template-columns: 28px 1fr;
        gap: .75rem;
        color: #263247;
        font-size: 1rem;
    }
    .checkmark {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: inline-grid;
        place-items: center;
        background: #dcfce7;
        color: #087a35;
        font-weight: 900;
        line-height: 1;
    }
    .video-wrap {
        background: #07123d;
        border-radius: 12px;
        padding: .75rem;
        box-shadow: 0 18px 40px rgba(15, 23, 42, .16);
    }
    .video-frame {
        position: relative;
        padding-bottom: 56.25%;
        height: 0;
        overflow: hidden;
        border-radius: 8px;
        background: #000;
    }
    .video-frame iframe {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        border: 0;
    }
    .preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.25rem;
    }
    .preview-card {
        background: #fff;
        border: 1px solid #d9e2ef;
        border-radius: 10px;
        padding: .75rem;
        box-shadow: 0 14px 30px rgba(15, 23, 42, .08);
    }
    .preview-card img {
        width: 100%;
        display: block;
        border-radius: 6px;
    }
    .final-section {
        background: #ffffff;
        border-top: 1px solid #d9e2ef;
    }
    .final-box {
        max-width: 820px;
        margin: 0 auto;
    }
    .final-cta-wrap {
        text-align: center;
        margin-top: 2rem;
    }
    @media (max-width: 820px) {
        .apostila-page .lp-section {
            padding: 3rem 0;
        }
        .apostila-hero {
            padding-top: 7rem;
        }
        .apostila-hero-grid {
            grid-template-columns: 1fr;
        }
        .apostila-actions .apostila-btn {
            width: 100%;
        }
        .topics-box,
        .final-box {
            padding: 1.25rem;
        }
    }
</style>

<div class="apostila-page">
    <section class="apostila-hero lp-section" aria-labelledby="apostila-title">
        <div class="lp-container apostila-hero-grid">
            <div>
                <span class="apostila-kicker">Material digital ISP</span>
                <h1 id="apostila-title"><?= htmlspecialchars($apostila['title']) ?></h1>

                <?php if (!empty($apostila['subtitle'])): ?>
                    <p class="apostila-subtitle"><?= htmlspecialchars($apostila['subtitle']) ?></p>
                <?php endif; ?>

                <p class="apostila-price">Por R$ <?= number_format((float)$apostila['price'], 2, ',', '.') ?></p>

                <div class="apostila-actions">
                    <a href="<?= htmlspecialchars($apostila['payment_link']) ?>" class="apostila-btn" target="_blank" rel="noopener noreferrer">
                        <?= htmlspecialchars($apostila['hero_btn_text'] ?: 'Garantir meu material') ?>
                    </a>
                </div>
            </div>

            <div class="apostila-cover">
                <?php if (!empty($apostila['cover_image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($apostila['cover_image']) ?>" alt="<?= htmlspecialchars($coverAlt) ?>">
                <?php else: ?>
                    <div class="apostila-empty-cover">Capa em breve</div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php if (!empty($topics)): ?>
    <section class="lp-section" aria-labelledby="topics-title">
        <div class="lp-container" style="max-width: 860px;">
            <h2 class="apostila-section-title" id="topics-title">
                <?= htmlspecialchars($apostila['sec1_title'] ?: 'O que você vai encontrar no material?') ?>
            </h2>
            <div class="topics-box">
                <ul class="topics-list">
                    <?php foreach ($topics as $topic): ?>
                        <li><span class="checkmark" aria-hidden="true">&check;</span><span><?= htmlspecialchars($topic) ?></span></li>
                    <?php endforeach; ?>
                </ul>

                <div class="final-cta-wrap">
                    <a href="<?= htmlspecialchars($apostila['payment_link']) ?>" class="apostila-btn" target="_blank" rel="noopener noreferrer">
                        <?= htmlspecialchars($apostila['sec1_btn_text'] ?: 'Quero ter acesso agora') ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($videoEmbed !== ''): ?>
    <section class="lp-section" aria-labelledby="video-title" style="background:#eef4ff;">
        <div class="lp-container" style="max-width: 860px;">
            <h2 class="apostila-section-title" id="video-title"><?= htmlspecialchars($videoTitle) ?></h2>
            <div class="video-wrap">
                <div class="video-frame">
                    <iframe src="<?= htmlspecialchars($videoEmbed) ?>" title="<?= htmlspecialchars($videoTitle) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($previews)): ?>
    <section class="lp-section" aria-labelledby="preview-title">
        <div class="lp-container">
            <h2 class="apostila-section-title" id="preview-title">
                <?= htmlspecialchars($apostila['sec2_title'] ?: 'Veja o material por dentro') ?>
            </h2>
            <div class="preview-grid">
                <?php foreach ($previews as $index => $img): ?>
                    <div class="preview-card">
                        <img src="uploads/<?= htmlspecialchars($img) ?>" alt="Prévia da apostila <?= $index + 1 ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="lp-section final-section" aria-labelledby="final-title">
        <div class="lp-container">
            <h2 class="apostila-section-title" id="final-title">
                <?= htmlspecialchars($apostila['sec3_title'] ?: 'Acelerando sua aprovação') ?>
            </h2>
            <p class="apostila-section-lead"><?= htmlspecialchars($finalText) ?></p>

            <?php if (!empty($finalBullets)): ?>
                <div class="final-box">
                    <ul class="final-list">
                        <?php foreach ($finalBullets as $item): ?>
                            <li><span class="checkmark" aria-hidden="true">&check;</span><span><?= htmlspecialchars($item) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="final-cta-wrap">
                <a href="<?= htmlspecialchars($apostila['payment_link']) ?>" class="apostila-btn secondary" target="_blank" rel="noopener noreferrer">
                    <?= htmlspecialchars($apostila['sec3_btn_text'] ?: 'Comprar agora') ?>
                </a>
            </div>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
