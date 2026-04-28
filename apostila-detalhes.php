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
    $parsed = parse_url($url);
    if (isset($parsed['query'])) {
        parse_str($parsed['query'], $query);
        if (isset($query['v'])) return 'https://www.youtube.com/embed/' . $query['v'];
    }
    if (isset($parsed['host']) && $parsed['host'] == 'youtu.be') {
        return 'https://www.youtube.com/embed' . $parsed['path'];
    }
    if (strpos($url, 'embed') !== false) {
        return $url;
    }
    return $url;
}

$topics = array_filter(array_map('trim', explode("\n", $apostila['topics'])));
$previews = $apostila['preview_images'] ? explode(',', $apostila['preview_images']) : [];

require_once 'includes/header.php';
?>

<main style="background: var(--obsidian-deep); min-height: 100vh;">
    <!-- Hero Section -->
    <section style="padding: 10rem 5% 4rem; background: radial-gradient(circle at top right, var(--prism-cyan) 0%, transparent 40%), radial-gradient(circle at bottom left, var(--prism-amber) 0%, transparent 40%); border-bottom: 1px solid var(--glass-border);">
        <div class="container" style="display: flex; flex-wrap: wrap; gap: 4rem; align-items: center; justify-content: space-between;">
            <div style="flex: 1; min-width: 300px; text-align: left;">
                <h1 style="font-size: clamp(2.5rem, 5vw, 4rem); line-height: 1.1; margin-bottom: 1rem; color: #fff;">
                    <?= htmlspecialchars($apostila['title']) ?>
                </h1>
                
                <?php if($apostila['subtitle']): ?>
                    <p style="color: var(--brand-orange); font-size: 1.2rem; margin-bottom: 2rem; font-weight: 500;">
                        <?= htmlspecialchars($apostila['subtitle']) ?>
                    </p>
                <?php endif; ?>
                
                <p style="color: #25D366; font-family: var(--font-mono); font-size: 1.8rem; font-weight: 700; margin-bottom: 2rem; background: rgba(37, 211, 102, 0.1); padding: 0.5rem 1rem; border-radius: 8px; display: inline-block; border: 1px solid rgba(37, 211, 102, 0.3);">
                    Por apenas R$ <?= number_format($apostila['price'], 2, ',', '.') ?>
                </p>
                
                <a href="<?= htmlspecialchars($apostila['payment_link']) ?>" class="btn" style="padding: 1.2rem 3rem; font-size: 1.1rem; border-radius: 50px; background: #E50914; color: white; text-transform: uppercase; font-weight: bold; letter-spacing: 1px; display: inline-block; margin-top: 1rem;" target="_blank">
                    <?= htmlspecialchars($apostila['hero_btn_text'] ?: 'Garantir Meu Material') ?>
                </a>
            </div>
            
            <div style="flex: 1; min-width: 300px; display: flex; justify-content: center;">
                <?php if($apostila['cover_image']): ?>
                    <img src="uploads/<?= $apostila['cover_image'] ?>" alt="Capa da Apostila" style="width: 100%; max-width: 450px; border-radius: 12px; box-shadow: 0 30px 60px rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.1);">
                <?php else: ?>
                    <div style="width: 100%; max-width: 400px; aspect-ratio: 3/4; background: rgba(255,255,255,0.05); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                        <span style="color: var(--text-secondary);">Sem Imagem</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Topics Section -->
    <?php if(!empty($topics)): ?>
    <section class="section-padding" style="background: rgba(2, 2, 58, 0.4);">
        <div class="container" style="max-width: 800px; margin: 0 auto;">
            <h2 style="text-align: center; margin-bottom: 3rem; color: #fff; font-size: 2.2rem;">
                <?= htmlspecialchars($apostila['sec1_title'] ?: 'O que você vai encontrar no material?') ?>
            </h2>
            
            <div style="background: rgba(255,255,255,0.02); padding: 3rem; border-radius: 16px; border: 1px solid var(--glass-border);">
                <ul style="list-style: none; margin: 0; padding: 0;">
                    <?php foreach($topics as $topic): ?>
                        <li style="display: flex; align-items: flex-start; gap: 15px; margin-bottom: 1.5rem; font-size: 1.1rem; color: var(--text-secondary);">
                            <span style="color: #25D366; font-size: 1.2rem; flex-shrink: 0; margin-top: 2px;">✔</span>
                            <span><?= htmlspecialchars($topic) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <div style="text-align: center; margin-top: 3rem;">
                    <a href="<?= htmlspecialchars($apostila['payment_link']) ?>" class="btn" style="padding: 1rem 2.5rem; background: #E50914; font-weight: bold; letter-spacing: 1px;" target="_blank">
                        <?= htmlspecialchars($apostila['sec1_btn_text'] ?: 'Quero Ter Acesso Agora') ?>
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Video Section -->
    <?php if(!empty($apostila['video_url']) && !empty($apostila['video_title'])): ?>
    <section class="section-padding" style="background: rgba(3, 4, 94, 0.4); border-top: 1px solid var(--glass-border);">
        <div class="container" style="max-width: 800px; margin: 0 auto;">
            <h2 style="text-align: center; margin-bottom: 3rem; color: #fff; font-size: 2.2rem;">
                <?= htmlspecialchars($apostila['video_title']) ?>
            </h2>
            <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; border-radius: 12px; border: 1px solid var(--prism-cyan); box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                <iframe src="<?= htmlspecialchars(getYouTubeEmbedUrl($apostila['video_url'])) ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Previews Section -->
    <?php if(!empty($previews)): ?>
    <section class="section-padding">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 4rem; color: var(--brand-orange); font-size: 2.2rem;">
                <?= htmlspecialchars($apostila['sec2_title'] ?: 'Veja o Material por Dentro') ?>
            </h2>
            
            <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <?php foreach($previews as $img): ?>
                    <div style="border-radius: 8px; overflow: hidden; border: 1px solid var(--glass-border); box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                        <img src="uploads/<?= htmlspecialchars($img) ?>" style="width: 100%; display: block; object-fit: cover;" alt="Página do Material">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Final CTA Section -->
    <section class="section-padding" style="background: rgba(0, 0, 0, 0.3); border-top: 1px solid var(--glass-border);">
        <div class="container" style="max-width: 800px; margin: 0 auto; text-align: center;">
            <h2 style="margin-bottom: 2rem; color: #fff;">
                <?= htmlspecialchars($apostila['sec3_title'] ?: 'Acelerando sua Aprovação') ?>
            </h2>
            <p style="color: var(--text-secondary); font-size: 1.2rem; margin-bottom: 2rem;">
                A resolução focada de questões e o estudo direcionado através de mapas e resumos otimizados são fundamentais para garantir sua fixação de conteúdo na reta final.
            </p>
            
            <div style="margin: 3rem 0; padding: 2rem; background: rgba(3, 4, 94, 0.3); border-radius: 12px; border: 1px solid var(--prism-cyan);">
                <h3 style="color: var(--prism-cyan); margin-bottom: 1.5rem;">Por que esse material é diferente?</h3>
                <ul style="list-style: none; text-align: left; margin: 0 auto; max-width: 600px;">
                    <li style="margin-bottom: 1rem; color: #fff;">👉 Questões selecionadas e comentadas por especialistas.</li>
                    <li style="margin-bottom: 1rem; color: #fff;">👉 Resumos objetivos para leitura rápida e revisão.</li>
                    <li style="margin-bottom: 1rem; color: #fff;">👉 Formato amigável e direto ao ponto que as bancas cobram.</li>
                </ul>
            </div>
            
            <a href="<?= htmlspecialchars($apostila['payment_link']) ?>" class="btn" style="padding: 1.2rem 3rem; font-size: 1.2rem; border-radius: 50px; background: #25D366; color: white; text-transform: uppercase; font-weight: bold; letter-spacing: 1px; width: 100%; max-width: 400px; margin: 0 auto;" target="_blank">
                <?= htmlspecialchars($apostila['sec3_btn_text'] ?: 'Comprar Agora') ?>
            </a>
        </div>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
