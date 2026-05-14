<?php
require_once 'db_config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND active = 1 AND (status='publicado' OR status IS NULL) AND created_at <= NOW()");
$stmt->execute([$id]);
$post = $stmt->fetch();

if(!$post) {
    require_once 'includes/header.php';
    echo "<div class='container section-padding' style='margin-top: 5rem;'><h1 style='color: var(--brand-orange); font-family: var(--font-mono); text-align: center;'>// ERRO: POST NÃO ENCONTRADO</h1></div>";
    require_once 'includes/footer.php';
    exit;
}

$dynamic_title = $post['title'];
$content_clean = preg_replace(['/<style\b[^>]*>(.*?)<\/style>/is', '/<script\b[^>]*>(.*?)<\/script>/is'], '', $post['content']);
$dynamic_desc = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($content_clean))), 0, 150) . '...';
$dynamic_keywords = !empty($post['seo_keywords']) ? htmlspecialchars($post['seo_keywords']) : '';
require_once 'includes/header.php';
?>

<!-- Estilos Globais para Todos os Posts do Blog -->
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Sora:wght@100..800&display=swap');

.blog-post-card {
    background: #ffffff;
    color: #1a1a2e;
    font-family: 'DM Sans', sans-serif;
    max-width: 900px;
    margin: 0 auto;
    border-radius: 24px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.3);
    padding: 4rem 4rem;
    animation: slideUp 0.8s ease;
    overflow-x: hidden;
}

@media (max-width: 768px) {
    .blog-post-card {
        padding: 2rem 1.5rem;
        border-radius: 0; /* Largura total no mobile */
        box-shadow: none;
    }
}

.post-content {
    overflow-wrap: break-word;
    word-wrap: break-word;
    word-break: break-word;
}
.post-content h2, .post-content h3, .post-content h4 {
    font-family: 'Sora', sans-serif;
    color: #03045e;
    margin-top: 2.5rem;
    margin-bottom: 1rem;
    font-weight: 700;
}
.post-content p {
    margin-bottom: 1.5rem;
    font-size: 1.05rem;
    line-height: 1.7;
    color: #333;
}
.post-content a {
    color: #ff8000;
    text-decoration: underline;
    font-weight: 500;
}
.post-content a:hover {
    color: #0077b6;
}
.post-content img, .post-content iframe {
    max-width: 100%;
    height: auto;
    border-radius: 12px;
    margin: 2rem 0;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.post-content ul, .post-content ol {
    margin-bottom: 1.5rem;
    padding-left: 2rem;
    font-size: 1.05rem;
    color: #333;
}
.post-content blockquote {
    border-left: 4px solid #ff8000;
    padding-left: 1.5rem;
    margin: 2rem 0;
    font-style: italic;
    color: #555;
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0 8px 8px 0;
}

/* Componentes Premium Nativos (Tabelas, Badges, Cronogramas) */
.table-wrap { overflow-x: auto; max-width: 100%; width: 100%; border-radius: 8px; border: 1px solid #dee2e6; margin: 1.5rem 0; -webkit-overflow-scrolling: touch; }
.post-content table { width: 100%; border-collapse: collapse; min-width: 480px; margin: 0; font-size: 0.9rem; }
.post-content thead tr { background: #03045e; color: #fff; }
.post-content thead th { padding: 12px 15px; font-family: 'Sora', sans-serif; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase; text-align: left; border: none; }
.post-content tbody tr:nth-child(even) { background: #f8f9fa; }
.post-content tbody tr:hover { background: #e8f0fe; transition: background 0.2s; }
.post-content tbody td { padding: 12px 15px; border-bottom: 1px solid #dee2e6; vertical-align: top; }
.post-content tbody td.b { font-weight: 600; color: #03045e; }
.post-content tbody td.c, .post-content thead th.c { text-align: center; }
.total-row td { background: #e8f0fe !important; font-weight: 700; font-family: 'Sora', sans-serif; color: #03045e; }

.badge-ret { display: flex; gap: 12px; align-items: flex-start; background: #fff8ee; border: 1px solid #fcd5a0; border-left: 4px solid #ff8000; border-radius: 8px; padding: 16px; font-size: 0.9rem; color: #7a3f00; margin-bottom: 2rem; }
.badge-ret strong { font-family: 'Sora', sans-serif; color: #ff8000; display: block; margin-bottom: 4px; }
.sec-title { font-family: 'Sora', sans-serif; font-size: 1.25rem; font-weight: 700; color: #03045e; padding-bottom: 8px; border-bottom: 2px solid #ff8000; margin: 2.5rem 0 1.5rem; display: flex; align-items: center; gap: 10px; }
.sec-num { background: #ff8000; color: #fff; font-size: 0.8rem; width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
.subsec { font-family: 'Sora', sans-serif; font-size: 0.9rem; font-weight: 700; color: #03045e; background: #e8f0fe; border-left: 3px solid #0077b6; padding: 8px 14px; border-radius: 0 6px 6px 0; margin: 1.5rem 0 1rem; }
.box { border-radius: 8px; padding: 16px; margin: 1.5rem 0; font-size: 0.9rem; display: flex; gap: 12px; align-items: flex-start; }
.box.info { background: #e8f0fe; border-left: 4px solid #0077b6; color: #03045e; }
.box.tip { background: #eafaf1; border-left: 4px solid #1a7a4a; color: #145a32; }
.box.warn { background: #fff8e1; border-left: 4px solid #f59e0b; color: #78350f; }

.etapas-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin: 1.5rem 0; }
.etapa-card { background: #f8f9fa; border: 1px solid #dee2e6; border-top: 3px solid #ff8000; border-radius: 8px; padding: 16px; }
.tag { display: inline-block; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; padding: 3px 8px; border-radius: 4px; margin-right: 5px; margin-bottom: 8px; }
.tag.elim { background: #fde8e8; color: #c0392b; }
.tag.class { background: #eafaf1; color: #1a7a4a; }
.crono-wrap { border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; margin: 1.5rem 0; }
.crono-item { display: flex; gap: 15px; padding: 12px 16px; font-size: 0.9rem; border-bottom: 1px solid #dee2e6; background: #fff; }
.crono-item:nth-child(even) { background: #f8f9fa; }
.crono-item.dest { background: #03045e; color: #fff; font-weight: 600; font-family: 'Sora', sans-serif; }
.crono-date { flex-shrink: 0; width: 130px; font-weight: 600; color: #0077b6; }
.crono-item.dest .crono-date { color: #ff8000; }
.cota-chip { display: flex; gap: 10px; padding: 10px 15px; border-radius: 8px; font-size: 0.85rem; flex: 1; }
.cota-chip.pcd { background: #e3f0ff; color: #0047b3; border: 1px solid #aecbfa; }
.cota-chip.ppi { background: #fff0e0; color: #8a4500; border: 1px solid #ffc980; }

@media (max-width: 580px) {
    .post-content table { min-width: 400px; }
    .crono-item { flex-direction: column; gap: 4px; }
    .crono-date { width: 100%; }
    .cota-chip { width: 100%; flex-direction: column; }
    .etapas-grid { grid-template-columns: 1fr; }
    .badge-ret { flex-direction: column; }
}
</style>

<div class="container section-padding" style="margin-top: 5rem; padding-left: 0; padding-right: 0;">
    <article class="blog-post-card">
        
        <!-- Post Header -->
        <div style="text-align: center; margin-bottom: 3rem;">
            <span style="background: #ff8000; color: #fff; padding: 6px 16px; border-radius: 50px; font-size: 0.85rem; font-weight: bold; font-family: 'Sora', sans-serif; text-transform: uppercase; margin-bottom: 1.5rem; display: inline-block; letter-spacing: 1px;">
                <?= htmlspecialchars($post['category']) ?>
            </span>
            
            <h1 style="color: #03045e; font-family: 'Sora', sans-serif; margin-bottom: 1.5rem; font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 800; letter-spacing: -1px; line-height: 1.2;">
                <?= htmlspecialchars($post['title']) ?>
            </h1>
            
            <div style="color: #6c757d; font-size: 0.95rem; font-family: 'DM Sans', sans-serif; display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 10px 1.5rem;">
                <span><i class="far fa-calendar-alt"></i> Publicado em <?= date('d/m/Y', strtotime($post['created_at'])) ?></span>
                <span style="opacity: 0.5;">|</span>
                <span>✍️ Por Equipe ISP</span>
            </div>
        </div>
        
        <!-- Cover Image -->
        <?php if($post['cover_image']): ?>
            <div style="margin-bottom: 3.5rem; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <img src="uploads/<?= $post['cover_image'] ?>" alt="<?= htmlspecialchars($post['image_alt'] ?: $post['title']) ?>" style="width: 100%; object-fit: cover; display: block;">
            </div>
        <?php endif; ?>
        
        <!-- Post Content (Rich HTML) -->
        <div class="post-content">
            <?= $post['content'] ?>
        </div>
        
        <!-- Author Box -->
        <div style="margin-top: 5rem; padding: 2rem; background: #f8f9fa; border: 1px solid #dee2e6; border-left: 4px solid #03045e; border-radius: 12px; display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
            <div style="width: 80px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                <img src="uploads/logo.png" alt="ISP" style="max-width: 100%; height: auto;" onerror="this.style.display='none'">
            </div>
            <div style="flex: 1; min-width: 250px;">
                <h4 style="color: #03045e; font-family: 'Sora', sans-serif; font-size: 1.2rem; margin-bottom: 0.5rem; font-weight: 700;">ISP Preparatórios</h4>
                <p style="color: #555; font-size: 0.95rem; line-height: 1.6; margin-bottom: 0;">
                    Especialistas em aprovação nas carreiras da educação. Nossa missão é entregar o conteúdo mais focado e atualizado para acelerar a sua nomeação.
                </p>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 4rem; padding-top: 3rem; border-top: 1px solid #dee2e6; display: flex; flex-direction: column; gap: 2rem; align-items: center;">
            <div>
                <h3 style="color: #03045e; font-family: 'Sora', sans-serif; font-size: 1.4rem; margin-bottom: 1.2rem; font-weight: 700;">Dê o próximo passo rumo à sua aprovação!</h3>
                <a href="cursos.php" class="btn" style="background: #ff8000; color: #fff; border: none; font-weight: 800; font-size: 1.05rem; padding: 1.2rem 2.5rem; border-radius: 8px; box-shadow: 0 8px 20px rgba(255,128,0,0.3); text-transform: uppercase; letter-spacing: 1px;">CONHECER NOSSOS CURSOS</a>
            </div>
            
            <a href="blog.php" style="color: #6c757d; font-size: 0.95rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: color 0.3s;" onmouseover="this.style.color='#03045e'" onmouseout="this.style.color='#6c757d'">
                <span style="font-size: 1.2rem;">←</span> Voltar para os Artigos
            </a>
        </div>
    </article>
</div>

<script>
// Script para garantir que TODAS as tabelas coladas no editor sejam responsivas
document.addEventListener("DOMContentLoaded", function() {
    var tables = document.querySelectorAll('.post-content table');
    tables.forEach(function(table) {
        if (!table.parentElement.classList.contains('table-wrap')) {
            var wrapper = document.createElement('div');
            wrapper.classList.add('table-wrap');
            wrapper.style.overflowX = 'auto';
            wrapper.style.maxWidth = '100%';
            wrapper.style.webkitOverflowScrolling = 'touch';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
