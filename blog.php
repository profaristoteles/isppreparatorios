<?php
require_once 'db_config.php';
require_once 'includes/header.php';

// Get categories for sidebar
$stmt_cats = $pdo->query("SELECT category, COUNT(*) as total FROM posts WHERE active=1 AND created_at <= NOW() GROUP BY category ORDER BY total DESC");
$categories = $stmt_cats->fetchAll();

// Get all posts
$stmt_posts = $pdo->query("SELECT * FROM posts WHERE active=1 AND created_at <= NOW() ORDER BY id DESC");
$all_posts = $stmt_posts->fetchAll();

$hero_post = count($all_posts) > 0 ? $all_posts[0] : null;
$recent_posts = count($all_posts) > 1 ? array_slice($all_posts, 1) : [];
?>

<div class="container section-padding" style="margin-top: 5rem;">
    <div style="text-align: center; margin-bottom: 4rem;">
        <h1 style="font-size: 3rem; color: var(--text-primary); letter-spacing: -1px; margin-bottom: 0.5rem;">ISP News</h1>
        <p style="color: var(--text-secondary); font-size: 1.2rem;">Notícias, Editais, Dicas e Artigos para a sua aprovação.</p>
    </div>

    <?php if(!$hero_post): ?>
        <p style="text-align: center; color: var(--brand-orange);">Nenhum artigo publicado ainda.</p>
    <?php else: ?>
        <div style="display: flex; flex-wrap: wrap; gap: 3rem; align-items: flex-start;">
            
            <!-- Main Content Area (70%) -->
            <div style="flex: 1; min-width: 300px; flex-basis: 65%;">
                
                <!-- Hero Post -->
                <a href="post.php?id=<?= $hero_post['id'] ?>" style="text-decoration: none; display: block; margin-bottom: 4rem; position: relative; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.5); group;">
                    <div style="position: relative; width: 100%; padding-bottom: 50%; background: #111;">
                        <?php if($hero_post['cover_image']): ?>
                            <img src="uploads/<?= $hero_post['cover_image'] ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;" alt="<?= htmlspecialchars($hero_post['image_alt'] ?: $hero_post['title']) ?>">
                        <?php endif; ?>
                        <!-- Overlay gradient for text readability -->
                        <div style="position: absolute; bottom: 0; left: 0; width: 100%; height: 80%; background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);"></div>
                    </div>
                    
                    <div style="position: absolute; bottom: 0; left: 0; width: 100%; padding: 2rem;">
                        <span style="background: #E50914; color: #fff; padding: 4px 12px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; margin-bottom: 1rem; display: inline-block;">
                            <?= htmlspecialchars($hero_post['category']) ?>
                        </span>
                        <h2 style="color: #fff; font-size: clamp(1.8rem, 4vw, 2.5rem); margin-bottom: 0.5rem; line-height: 1.2;">
                            <?= htmlspecialchars($hero_post['title']) ?>
                        </h2>
                        <div style="color: #aaa; font-size: 0.9rem; font-family: var(--font-mono);">
                            <i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($hero_post['created_at'])) ?> &nbsp;|&nbsp; Equipe ISP
                        </div>
                    </div>
                </a>

                <!-- Grid of Recent Posts -->
                <div style="display: flex; flex-direction: column; gap: 2rem;">
                    <?php foreach($recent_posts as $p): ?>
                        <a href="post.php?id=<?= $p['id'] ?>" style="text-decoration: none; display: flex; gap: 1.5rem; background: rgba(255,255,255,0.02); border: 1px solid var(--glass-border); border-radius: 12px; overflow: hidden; transition: transform 0.3s, border-color 0.3s; align-items: stretch;">
                            
                            <div style="width: 35%; flex-shrink: 0; background: #111; position: relative;">
                                <?php if($p['cover_image']): ?>
                                    <img src="uploads/<?= $p['cover_image'] ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;" alt="<?= htmlspecialchars($p['image_alt'] ?: $p['title']) ?>">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-secondary);">
                                        Sem imagem
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="padding: 1.5rem 1.5rem 1.5rem 0; flex: 1; display: flex; flex-direction: column; justify-content: center;">
                                <span style="color: var(--brand-orange); font-size: 0.8rem; font-weight: bold; text-transform: uppercase; margin-bottom: 0.5rem; display: block;">
                                    <?= htmlspecialchars($p['category']) ?>
                                </span>
                                <h3 style="color: var(--text-primary); font-size: 1.3rem; margin-bottom: 0.5rem; line-height: 1.3;">
                                    <?= htmlspecialchars($p['title']) ?>
                                </h3>
                                <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1rem; line-height: 1.5;">
                                    <?= htmlspecialchars(mb_substr(strip_tags($p['content']), 0, 120)) ?>...
                                </p>
                                <div style="color: #666; font-size: 0.8rem; font-family: var(--font-mono); margin-top: auto;">
                                    <?= date('d/m/Y', strtotime($p['created_at'])) ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

            </div>

            <!-- Sidebar (30%) -->
            <div style="flex: 1; min-width: 280px; flex-basis: 30%;">
                
                <!-- Editorias Widget -->
                <div style="background: rgba(3, 4, 94, 0.4); border: 1px solid var(--prism-cyan); border-radius: 12px; padding: 2rem; margin-bottom: 2rem;">
                    <h3 style="color: #fff; font-size: 1.2rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">Editorias</h3>
                    <ul style="list-style: none; margin: 0; padding: 0;">
                        <?php foreach($categories as $cat): ?>
                            <li style="margin-bottom: 0.8rem;">
                                <a href="#" style="color: var(--text-secondary); text-decoration: none; display: flex; justify-content: space-between; align-items: center; transition: color 0.3s;" onmouseover="this.style.color='var(--brand-orange)'" onmouseout="this.style.color='var(--text-secondary)'">
                                    <span><?= htmlspecialchars($cat['category']) ?></span>
                                    <span style="background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; color: #fff;">
                                        <?= $cat['total'] ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Banner/Ad Widget -->
                <div style="border-radius: 12px; overflow: hidden; border: 1px solid var(--glass-border); box-shadow: 0 10px 20px rgba(0,0,0,0.5);">
                    <div style="padding: 2rem; background: linear-gradient(135deg, #E50914, #800000); text-align: center;">
                        <h4 style="color: #fff; font-size: 1.5rem; margin-bottom: 1rem; line-height: 1.2;">Prepare-se Conosco</h4>
                        <p style="color: #ffcccc; font-size: 0.9rem; margin-bottom: 1.5rem;">Cursos para todas as carreiras da educação.</p>
                        <a href="cursos.php" class="btn" style="background: #fff; color: #E50914; padding: 0.8rem 1.5rem; font-size: 0.9rem; border-radius: 50px;">Ver Cursos Disponíveis</a>
                    </div>
                </div>

            </div>

        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
