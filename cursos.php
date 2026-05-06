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
    
    <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));">
        <?php if(count($cursos) == 0): ?>
            <p class="reveal" style="font-family: var(--font-mono); color: var(--brand-orange);">Nenhum curso encontrado no momento.</p>
        <?php endif; ?>

        <?php foreach($cursos as $c): ?>
        <?php $is_reserva = ($c['status'] ?? 'Disponível') == 'Pegando Reserva'; ?>
        <div class="feature-card reveal" style="display: flex; flex-direction: column; padding: 1.2rem;">
            <div style="position: relative; margin-bottom: 1.2rem; border-radius: 8px; overflow: hidden; border: 1px solid var(--glass-border);">
                <?php if($c['thumbnail']): ?>
                    <img src="uploads/<?= $c['thumbnail'] ?>" alt="<?= htmlspecialchars($c['image_alt'] ?: $c['title']) ?>" style="width: 100%; aspect-ratio: 1/1; object-fit: cover; display: block; transition: transform 0.3s ease;">
                <?php else: ?>
                    <div style="width: 100%; aspect-ratio: 1/1; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center;">
                        <span style="color: var(--text-secondary);">Sem Imagem</span>
                    </div>
                <?php endif; ?>
                
                <div style="position: absolute; top: 10px; left: 10px; right: 10px; display: flex; gap: 0.4rem; flex-wrap: wrap;">
                    <?php if($is_reserva): ?>
                        <span style="background: var(--brand-orange); color: #fff; font-size: 0.7rem; font-family: var(--font-mono); padding: 0.3rem 0.6rem; border-radius: 20px; font-weight: 700; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">🚨 RESERVA</span>
                    <?php endif; ?>
                    <span style="background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(5px); color: #fff; font-size: 0.7rem; font-family: var(--font-mono); padding: 0.3rem 0.6rem; border-radius: 20px; font-weight: 500; border: 1px solid rgba(255,255,255,0.1);">⏱ <?= htmlspecialchars($c['duration']) ?></span>
                    <span style="background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(5px); color: #fff; font-size: 0.7rem; font-family: var(--font-mono); padding: 0.3rem 0.6rem; border-radius: 20px; font-weight: 500; border: 1px solid rgba(255,255,255,0.1);">📍 <?= htmlspecialchars($c['modality'] ?: 'Presencial e Online') ?></span>
                </div>
            </div>
            
            <h3 style="font-size: 1.2rem; margin-bottom: 1rem; flex-grow: 1; line-height: 1.4;"><?= htmlspecialchars($c['title']) ?></h3>
            
            <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--glass-border); padding-top: 1rem; gap: 0.5rem; flex-wrap: wrap;">
                <?php if($is_reserva): ?>
                    <span style="font-family: var(--font-mono); color: var(--brand-orange); font-size: 0.85rem; font-weight: 600;">Valores sob consulta</span>
                    <a href="curso-detalhes.php?id=<?= $c['id'] ?>" class="btn" style="flex: 1; min-width: 100px; text-align: center; padding: 0.7rem 1rem; font-size: 0.8rem; background: var(--brand-orange); color: #fff; border-color: var(--brand-orange);">Reservar</a>
                <?php else: ?>
                    <?php if($c['price'] > 0): ?>
                        <span style="font-family: var(--font-mono); color: var(--text-primary); font-size: 1.2rem; font-weight: 700;">R$ <?= number_format($c['price'], 2, ',', '.') ?></span>
                    <?php endif; ?>
                    <a href="curso-detalhes.php?id=<?= $c['id'] ?>" class="btn" style="flex: 1; min-width: 100px; text-align: center; padding: 0.7rem 1rem; font-size: 0.8rem;">Saiba Mais</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
