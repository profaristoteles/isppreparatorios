<?php
require_once 'db_config.php';
require_once 'includes/header.php';

// Busca eventos futuros e passados separadamente
$stmt_futuros = $pdo->query("SELECT * FROM eventos WHERE active=1 AND event_date >= NOW() ORDER BY event_date ASC");
$eventos_futuros = $stmt_futuros->fetchAll();

$stmt_passados = $pdo->query("SELECT * FROM eventos WHERE active=1 AND event_date < NOW() ORDER BY event_date DESC");
$eventos_passados = $stmt_passados->fetchAll();
?>

<div class="container section-padding">
    <div class="section-header reveal" style="margin-bottom: 3rem; text-align: center;">
        <span class="hero-pre-title">CONTEÚDO GRATUITO</span>
        <h2 style="text-align: center;">Eventos, Aulas e Lives</h2>
        <p style="color: var(--text-secondary); max-width: 600px; margin: 1rem auto 0; text-align: center;">Inscreva-se em nossos eventos ao vivo ou assista às gravações de nossas aulas especiais focadas em concursos da Educação Especial.</p>
    </div>
    
    <?php if(count($eventos_futuros) > 0): ?>
        <h3 class="reveal" style="font-size: 1.8rem; margin-bottom: 1.5rem; color: var(--brand-orange); border-bottom: 1px solid rgba(255,165,0,0.3); padding-bottom: 0.5rem;">Próximos Eventos</h3>
        
        <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(min(100%, 350px), 1fr)); margin-bottom: 4rem;">
            <?php foreach($eventos_futuros as $e): ?>
            <div class="feature-card reveal" style="display: flex; flex-direction: column; padding: 1.5rem; background: rgba(255, 255, 255, 0.03); border: 1px solid var(--glass-border); border-radius: 12px; height: 100%;">
                <div style="position: relative; margin-bottom: 1.5rem; border-radius: 8px; overflow: hidden;">
                    <?php if($e['thumbnail']): ?>
                        <img src="uploads/<?= $e['thumbnail'] ?>" alt="<?= htmlspecialchars($e['title']) ?>" style="width: 100%; aspect-ratio: 1/1; object-fit: contain; background: #111; display: block; border-radius: 8px;">
                    <?php else: ?>
                        <div style="width: 100%; aspect-ratio: 16/9; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                            <span style="color: var(--text-secondary);">Sem Capa</span>
                        </div>
                    <?php endif; ?>
                    
                    <div style="position: absolute; top: 10px; left: 10px; display: flex; gap: 0.4rem;">
                        <span style="background: var(--brand-orange); color: #fff; font-size: 0.8rem; font-family: var(--font-mono); padding: 0.4rem 0.8rem; border-radius: 20px; font-weight: 700; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                            📅 <?= date('d/m/Y \à\s H:i', strtotime($e['event_date'])) ?>
                        </span>
                    </div>
                </div>
                
                <h3 style="font-size: 1.4rem; margin-bottom: 1rem; flex-grow: 1; line-height: 1.4; color: #fff;"><?= htmlspecialchars($e['title']) ?></h3>
                
                <div style="margin-top: auto; padding-top: 1rem;">
                    <a href="<?= !empty($e['slug']) ? '/evento/'.$e['slug'] : 'evento-detalhes.php?id='.$e['id'] ?>" class="btn" style="width: 100%; text-align: center; padding: 0.8rem; font-size: 1rem;">Garantir minha vaga</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if(count($eventos_passados) > 0): ?>
        <h3 class="reveal" style="font-size: 1.5rem; margin-bottom: 1.5rem; color: var(--text-primary); border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">Eventos Passados</h3>
        
        <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));">
            <?php foreach($eventos_passados as $e): ?>
            <div class="feature-card reveal" style="display: flex; flex-direction: column; padding: 1.5rem; background: rgba(255, 255, 255, 0.03); border: 1px solid var(--glass-border); border-radius: 12px; height: 100%;">
                <div style="position: relative; margin-bottom: 1.5rem; border-radius: 8px; overflow: hidden; opacity: 0.7;">
                    <?php if($e['thumbnail']): ?>
                        <img src="uploads/<?= $e['thumbnail'] ?>" alt="<?= htmlspecialchars($e['title']) ?>" style="width: 100%; aspect-ratio: 1/1; object-fit: contain; background: #111; display: block; border-radius: 8px; filter: grayscale(50%);">
                    <?php else: ?>
                        <div style="width: 100%; aspect-ratio: 16/9; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                            <span style="color: var(--text-secondary);">Sem Capa</span>
                        </div>
                    <?php endif; ?>
                    <div style="position: absolute; top: 10px; left: 10px; display: flex; gap: 0.4rem;">
                        <span style="background: rgba(0,0,0,0.8); color: #fff; font-size: 0.8rem; font-family: var(--font-mono); padding: 0.4rem 0.8rem; border-radius: 20px;">
                            Realizado em <?= date('d/m/Y', strtotime($e['event_date'])) ?>
                        </span>
                    </div>
                </div>
                
                <h4 style="font-size: 1.2rem; margin-bottom: 1rem; flex-grow: 1; line-height: 1.4; color: var(--text-secondary);"><?= htmlspecialchars($e['title']) ?></h4>
                
                <div style="margin-top: auto; padding-top: 1rem;">
                    <a href="<?= !empty($e['slug']) ? '/evento/'.$e['slug'] : 'evento-detalhes.php?id='.$e['id'] ?>" class="btn btn-outline" style="width: 100%; text-align: center; padding: 0.8rem; font-size: 0.9rem;">Acessar Material</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if(count($eventos_futuros) == 0 && count($eventos_passados) == 0): ?>
        <p class="text-center" style="color: var(--text-secondary); margin-top: 2rem;">Nenhum evento disponível no momento. Volte em breve!</p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
