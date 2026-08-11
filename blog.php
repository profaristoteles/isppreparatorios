<?php
require_once 'db_config.php';
require_once 'includes/header.php';

// Get categories for sidebar
$stmt_cats = $pdo->query("SELECT category, COUNT(*) as total FROM posts WHERE active=1 AND (status='publicado' OR status IS NULL) AND created_at <= NOW() GROUP BY category ORDER BY total DESC");
$categories = $stmt_cats->fetchAll();

// Get all posts
$stmt_posts = $pdo->query("SELECT * FROM posts WHERE active=1 AND (status='publicado' OR status IS NULL) AND created_at <= NOW() ORDER BY id DESC");
$all_posts = $stmt_posts->fetchAll();

$hero_post = count($all_posts) > 0 ? $all_posts[0] : null;
$recent_posts = count($all_posts) > 1 ? array_slice($all_posts, 1) : [];

function clean_excerpt($html, $length = 120) {
    // Remove scripts and styles
    $clean = preg_replace(['/<style\b[^>]*>(.*?)<\/style>/is', '/<script\b[^>]*>(.*?)<\/script>/is'], '', $html);
    // Strip tags and decode entities (&nbsp;, &eacute;, etc)
    $clean = html_entity_decode(strip_tags($clean), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Remove extra whitespace
    $clean = trim(preg_replace('/\s+/', ' ', $clean));
    
    $len = function_exists('mb_strlen') ? mb_strlen($clean, 'UTF-8') : strlen($clean);
    if ($len > $length) {
        return (function_exists('mb_substr') ? mb_substr($clean, 0, $length, 'UTF-8') : substr($clean, 0, $length)) . '...';
    }
    return $clean;
}
?>

<style>
    :root {
        --blog-bg: #f8fafc;
        --blog-card: #ffffff;
        --blog-border: #e2e8f0;
        --blog-text: #334155;
        --blog-heading: #0f172a;
        --blog-primary: #03045e;
        --blog-accent: #ff8000;
    }

    body {
        background-color: var(--blog-bg);
    }

    .blog-header {
        text-align: center;
        margin-bottom: 4rem;
    }

    .blog-header h1 {
        font-size: clamp(2.5rem, 5vw, 4rem);
        color: var(--blog-primary);
        font-weight: 800;
        letter-spacing: -1px;
        margin-bottom: 1rem;
    }

    .blog-header p {
        color: var(--blog-text);
        font-size: 1.25rem;
        max-width: 600px;
        margin: 0 auto;
    }

    .blog-layout {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 3rem;
        align-items: start;
    }

    /* Cards gerais */
    .blog-card {
        background: var(--blog-card);
        border: 1px solid var(--blog-border);
        border-radius: 16px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        flex-direction: column;
        text-decoration: none;
    }

    .blog-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.08);
    }

    .blog-card-img-wrap {
        position: relative;
        overflow: hidden;
        background: #e2e8f0;
    }

    .blog-card-img-wrap img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .blog-card:hover .blog-card-img-wrap img {
        transform: scale(1.05);
    }

    .blog-badge {
        background: var(--blog-accent);
        color: #fff;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: inline-block;
        margin-bottom: 1rem;
    }

    .blog-meta {
        color: #64748b;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: auto;
    }

    /* Hero Post */
    .hero-post .blog-card-img-wrap {
        aspect-ratio: 16/9;
    }

    .hero-post .blog-card-content {
        padding: 2.5rem;
    }

    .hero-post h2 {
        font-size: clamp(1.5rem, 3vw, 2.2rem);
        color: var(--blog-heading);
        margin-bottom: 1rem;
        line-height: 1.3;
        font-weight: 800;
    }

    .hero-post p {
        font-size: 1.1rem;
        color: var(--blog-text);
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }

    /* Grid de Posts Recentes */
    .recent-posts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
        margin-top: 3rem;
    }

    .recent-post .blog-card-img-wrap {
        aspect-ratio: 16/10;
    }

    .recent-post .blog-card-content {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    .recent-post h3 {
        font-size: 1.25rem;
        color: var(--blog-heading);
        margin-bottom: 0.75rem;
        line-height: 1.4;
        font-weight: 700;
    }

    .recent-post p {
        font-size: 0.95rem;
        color: var(--blog-text);
        line-height: 1.5;
        margin-bottom: 1.5rem;
    }

    /* Sidebar */
    .sidebar-widget {
        background: var(--blog-card);
        border: 1px solid var(--blog-border);
        border-radius: 16px;
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .sidebar-title {
        font-size: 1.25rem;
        color: var(--blog-heading);
        font-weight: 800;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--blog-accent);
        display: inline-block;
    }

    .category-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .category-item a {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        color: var(--blog-text);
        text-decoration: none;
        border-bottom: 1px solid #f1f5f9;
        transition: color 0.2s;
    }

    .category-item a:hover {
        color: var(--blog-accent);
    }

    .category-count {
        background: #f1f5f9;
        color: #64748b;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .promo-widget {
        background: linear-gradient(135deg, var(--blog-primary), #0077b6);
        color: #fff;
        text-align: center;
        padding: 2.5rem 2rem;
    }

    .promo-widget h4 {
        font-size: 1.5rem;
        margin-bottom: 1rem;
        font-weight: 800;
    }

    .promo-widget p {
        color: #e0f2fe;
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
    }

    .promo-btn {
        background: var(--blog-accent);
        color: #fff;
        padding: 0.8rem 1.5rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 700;
        display: inline-block;
        transition: transform 0.2s;
    }

    .promo-btn:hover {
        transform: scale(1.05);
    }

    @media (max-width: 992px) {
        .blog-layout {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container section-padding" style="margin-top: 5rem;">
    <div class="blog-header">
        <h1>ISP News</h1>
        <p>Notícias, Editais, Dicas e Artigos para garantir a sua aprovação nos concursos educacionais.</p>
    </div>

    <?php if(!$hero_post): ?>
        <div style="text-align: center; padding: 4rem; background: var(--blog-card); border-radius: 16px; border: 1px dashed var(--blog-border);">
            <p style="color: var(--blog-text); font-size: 1.2rem;">Nenhum artigo publicado ainda.</p>
        </div>
    <?php else: ?>
        <div class="blog-layout">
            
            <!-- Main Content Area -->
            <div>
                <!-- Hero Post -->
                <a href="/blog/<?= !empty($hero_post['slug']) ? $hero_post['slug'] : $hero_post['id'] ?>" class="blog-card hero-post">
                    <div class="blog-card-img-wrap">
                        <?php if($hero_post['cover_image']): ?>
                            <img src="uploads/<?= $hero_post['cover_image'] ?>" alt="<?= htmlspecialchars($hero_post['image_alt'] ?: $hero_post['title']) ?>">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #94a3b8;">Imagem não disponível</div>
                        <?php endif; ?>
                    </div>
                    <div class="blog-card-content">
                        <span class="blog-badge"><?= htmlspecialchars($hero_post['category']) ?></span>
                        <h2><?= htmlspecialchars($hero_post['title']) ?></h2>
                        <p><?= htmlspecialchars(clean_excerpt($hero_post['content'], 180)) ?></p>
                        <div class="blog-meta">
                            <i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($hero_post['created_at'])) ?>
                            &bull; Por Equipe ISP
                        </div>
                    </div>
                </a>

                <!-- Grid of Recent Posts -->
                <div class="recent-posts-grid">
                    <?php foreach($recent_posts as $p): ?>
                        <a href="/blog/<?= !empty($p['slug']) ? $p['slug'] : $p['id'] ?>" class="blog-card recent-post">
                            <div class="blog-card-img-wrap">
                                <?php if($p['cover_image']): ?>
                                    <img src="uploads/<?= $p['cover_image'] ?>" alt="<?= htmlspecialchars($p['image_alt'] ?: $p['title']) ?>">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #94a3b8; background: #f1f5f9;">Sem Imagem</div>
                                <?php endif; ?>
                            </div>
                            <div class="blog-card-content">
                                <div>
                                    <span class="blog-badge" style="background: #e2e8f0; color: var(--blog-primary);"><?= htmlspecialchars($p['category']) ?></span>
                                    <h3><?= htmlspecialchars($p['title']) ?></h3>
                                    <p><?= htmlspecialchars(clean_excerpt($p['content'], 100)) ?></p>
                                </div>
                                <div class="blog-meta">
                                    <i class="far fa-clock"></i> <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <aside>
                <!-- Editorias Widget -->
                <div class="sidebar-widget">
                    <h3 class="sidebar-title">Editorias</h3>
                    <ul class="category-list">
                        <?php foreach($categories as $cat): ?>
                            <li class="category-item">
                                <a href="#">
                                    <span><?= htmlspecialchars($cat['category']) ?></span>
                                    <span class="category-count"><?= $cat['total'] ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Banner/Ad Widget -->
                <div class="sidebar-widget promo-widget" style="padding: 0; overflow: hidden; border: none;">
                    <div style="padding: 2.5rem 2rem;">
                        <h4>Acelere sua Aprovação</h4>
                        <p>Descubra nossos cursos focados nas carreiras da educação.</p>
                        <a href="cursos.php" class="promo-btn">Ver Cursos</a>
                    </div>
                </div>
            </aside>

        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
