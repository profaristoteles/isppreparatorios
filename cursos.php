<?php
require_once 'db_config.php';
require_once 'includes/header.php';

$stmt = $pdo->query("SELECT * FROM cursos WHERE active=1 ORDER BY id DESC");
$cursos = $stmt->fetchAll();
?>

<div class="container section-padding">
    <div class="section-header reveal">
        <span class="hero-pre-title">PREPARAÇÃO FOCADA</span>
        <h2>Cursos Disponíveis</h2>
    </div>
    
    <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));">
        <?php if(count($cursos) == 0): ?>
            <p class="reveal" style="font-family: var(--font-mono); color: var(--brand-orange);">Nenhum curso encontrado no momento.</p>
        <?php endif; ?>

        <?php foreach($cursos as $c): ?>
        <div class="feature-card reveal" style="display: flex; flex-direction: column;">
            <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; flex-wrap: wrap;">
                <span style="background: rgba(255, 128, 0, 0.1); color: var(--brand-orange); font-size: 0.75rem; font-family: var(--font-mono); padding: 0.3rem 0.8rem; border-radius: 20px; border: 1px solid rgba(255, 128, 0, 0.3); font-weight: 500;">⏱ <?= htmlspecialchars($c['duration']) ?></span>
                <span style="background: rgba(255, 255, 255, 0.05); color: #fff; font-size: 0.75rem; font-family: var(--font-mono); padding: 0.3rem 0.8rem; border-radius: 20px; border: 1px solid var(--glass-border); font-weight: 500;">📍 <?= htmlspecialchars($c['modality'] ?: 'Presencial e Online') ?></span>
            </div>
            
            <?php if($c['thumbnail']): ?>
                <img src="uploads/<?= $c['thumbnail'] ?>" alt="<?= htmlspecialchars($c['title']) ?>" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid var(--glass-border);">
            <?php else: ?>
                <div style="width: 100%; height: 200px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; border-radius: 8px; margin-bottom: 1.5rem;">
                    <span style="color: var(--text-secondary);">Sem Imagem</span>
                </div>
            <?php endif; ?>
            
            <h3 style="font-size: 1.4rem; margin-bottom: 1rem; flex-grow: 1;"><?= htmlspecialchars($c['title']) ?></h3>
            <p style="color: var(--text-secondary); margin-bottom: 2rem;"><?= htmlspecialchars(substr($c['description'], 0, 150)) ?>...</p>
            
            <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--glass-border); padding-top: 1.5rem;">
                <span style="font-family: var(--font-mono); color: var(--text-primary); font-size: 1.3rem; font-weight: 700;">R$ <?= number_format($c['price'], 2, ',', '.') ?></span>
                <a href="curso-detalhes.php?id=<?= $c['id'] ?>" class="btn" style="padding: 0.8rem 1.5rem; font-size: 0.8rem;">Saiba Mais</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
