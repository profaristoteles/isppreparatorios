<?php
require_once 'db_config.php';
require_once 'includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND active = 1");
$stmt->execute([$id]);
$post = $stmt->fetch();

if(!$post) {
    echo "<div class='container section-padding' style='margin-top: 5rem;'><h1 style='color: var(--brand-orange); font-family: var(--font-mono); text-align: center;'>// ERRO: ARQUIVO NÃO ENCONTRADO</h1></div>";
    require_once 'includes/footer.php';
    exit;
}
?>

<!-- TinyMCE Default Styles for Frontend Rendering (optional but helpful for tables, lists, etc) -->
<style>
.post-content h2, .post-content h3, .post-content h4 {
    color: var(--text-primary);
    margin-top: 2rem;
    margin-bottom: 1rem;
}
.post-content p {
    margin-bottom: 1.5rem;
}
.post-content a {
    color: var(--brand-orange);
    text-decoration: underline;
}
.post-content a:hover {
    color: var(--prism-cyan);
}
.post-content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1.5rem 0;
}
.post-content iframe {
    max-width: 100%;
    margin: 1.5rem 0;
    border-radius: 8px;
}
.post-content ul, .post-content ol {
    margin-bottom: 1.5rem;
    padding-left: 2rem;
}
.post-content table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5rem;
}
.post-content table td, .post-content table th {
    border: 1px solid var(--glass-border);
    padding: 0.8rem;
}
.post-content blockquote {
    border-left: 4px solid var(--brand-orange);
    padding-left: 1rem;
    margin-left: 0;
    font-style: italic;
    color: var(--text-secondary);
}
</style>

<div class="container section-padding" style="margin-top: 5rem;">
    <div class="content-block reveal" style="max-width: 800px; margin: 0 auto;">
        
        <!-- Post Header -->
        <div style="text-align: center; margin-bottom: 3rem;">
            <span style="background: var(--prism-cyan); color: #000; padding: 4px 12px; border-radius: 4px; font-size: 0.9rem; font-weight: bold; text-transform: uppercase; margin-bottom: 1.5rem; display: inline-block;">
                <?= htmlspecialchars($post['category']) ?>
            </span>
            
            <h1 style="color: var(--text-primary); margin-bottom: 1.5rem; font-size: clamp(2rem, 5vw, 3.5rem); letter-spacing: -1px; line-height: 1.2;">
                <?= htmlspecialchars($post['title']) ?>
            </h1>
            
            <div style="color: var(--text-secondary); font-size: 1rem; font-family: var(--font-mono); display: flex; justify-content: center; align-items: center; gap: 1rem;">
                <span><i class="far fa-calendar-alt"></i> Publicado em <?= date('d/m/Y', strtotime($post['created_at'])) ?></span>
                <span>•</span>
                <span>Por Equipe ISP</span>
            </div>
        </div>
        
        <!-- Cover Image -->
        <?php if($post['cover_image']): ?>
            <div style="margin-bottom: 3rem; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                <img src="uploads/<?= $post['cover_image'] ?>" alt="<?= htmlspecialchars($post['title']) ?>" style="width: 100%; object-fit: cover; border: 1px solid var(--glass-border); display: block;">
            </div>
        <?php endif; ?>
        
        <!-- Post Content (Rich HTML) -->
        <div class="post-content" style="line-height: 1.8; font-size: 1.15rem; color: #ddd;">
            <?= $post['content'] ?>
        </div>
        
        <!-- Author Box -->
        <div style="margin-top: 4rem; padding: 2rem; background: rgba(3, 4, 94, 0.4); border: 1px solid var(--prism-cyan); border-radius: 12px; display: flex; gap: 1.5rem; align-items: center;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; padding: 10px;">
                <img src="uploads/logo.png" alt="ISP" style="max-width: 100%; mix-blend-mode: multiply;" onerror="this.style.display='none'">
            </div>
            <div>
                <h4 style="color: #fff; font-size: 1.2rem; margin-bottom: 0.5rem;">ISP Preparatórios</h4>
                <p style="color: var(--text-secondary); font-size: 0.95rem; line-height: 1.5;">
                    Especialistas em aprovação nas carreiras da educação. Nossa missão é entregar o conteúdo mais focado e atualizado para acelerar a sua nomeação.
                </p>
            </div>
        </div>
        
        <div class="prism-divider" style="margin: 3rem 0;"></div>
        
        <div style="text-align: center;">
            <a href="blog.php" class="btn btn-outline" style="font-size: 0.9rem;">← VOLTAR PARA O BLOG</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
