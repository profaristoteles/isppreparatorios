<?php
require_once 'db_config.php';
$apostilas = $pdo->query("SELECT * FROM apostilas WHERE active=1 ORDER BY id DESC")->fetchAll();
require_once 'includes/header.php';
?>

<main>
    <section class="page-header reveal" style="padding: 8rem 5% 4rem; text-align: center; background: radial-gradient(circle at center, #06088a 0%, #03045e 100%);">
        <h1>Material Didático</h1>
        <p style="color: var(--text-secondary); max-width: 600px; margin: 1rem auto 0; font-size: 1.2rem;">Apostilas completas, atualizadas e focadas nas bancas para acelerar sua aprovação.</p>
    </section>

    <section class="section-padding" style="min-height: 50vh;">
        <div class="container">
            <?php if(empty($apostilas)): ?>
                <div class="reveal" style="text-align: center; padding: 4rem; background: rgba(255,255,255,0.02); border-radius: 12px; border: 1px solid var(--glass-border);">
                    <h3 style="color: var(--text-secondary);">Nenhuma apostila disponível no momento.</h3>
                    <p style="margin-top: 1rem;">Estamos preparando o melhor material para você. Volte em breve!</p>
                </div>
            <?php else: ?>
                <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
                    <?php foreach($apostilas as $apo): ?>
                    <div class="feature-card reveal" style="display: flex; flex-direction: column;">
                        <?php if($apo['cover_image']): ?>
                            <img src="uploads/<?= $apo['cover_image'] ?>" alt="<?= htmlspecialchars($apo['title']) ?>" style="width: 100%; aspect-ratio: 1/1; object-fit: cover; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid var(--glass-border);">
                        <?php else: ?>
                            <div style="width: 100%; aspect-ratio: 1/1; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; border-radius: 8px; margin-bottom: 1.5rem;">
                                <span style="color: var(--text-secondary);">Sem Capa</span>
                            </div>
                        <?php endif; ?>
                        
                        <h3 style="font-size: 1.3rem; margin-bottom: 1rem; flex-grow: 1;"><?= htmlspecialchars($apo['title']) ?></h3>
                        
                        <div style="margin-top: auto; padding-top: 1.5rem; border-top: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                            <span style="font-family: var(--font-mono); color: var(--text-primary); font-size: 1.4rem; font-weight: 700; white-space: nowrap;">
                                R$ <?= number_format($apo['price'], 2, ',', '.') ?>
                            </span>
                            <?php if($apo['is_internal']): ?>
                                <a href="apostila-detalhes.php?id=<?= $apo['id'] ?>" class="btn" style="flex: 1; text-align: center; padding: 0.8rem 1rem; font-size: 0.8rem;">Saiba Mais</a>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($apo['payment_link']) ?>" class="btn" style="flex: 1; text-align: center; padding: 0.8rem 1rem; font-size: 0.8rem;" target="_blank">Comprar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
