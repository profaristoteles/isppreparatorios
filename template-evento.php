<?php
// Note: Este arquivo é incluído pelo evento-redirect.php
// A variável $evento deve estar populada com os dados do banco de dados.

if (!isset($evento)) {
    header("Location: /eventos.php");
    exit;
}

// Meta SEO dinâmica (Usada no header.php)
$dynamic_title = $evento['meta_title'] ?: ($evento['title'] . " - ISP Preparatórios");
$dynamic_desc = $evento['meta_description'] ?: strip_tags(substr($evento['description'], 0, 160));

require_once 'includes/header.php';
$is_future = strtotime($evento['event_date']) >= time();
?>

<div style="background: linear-gradient(to bottom, #02023a, #03045e); padding: 4rem 5%; border-bottom: 1px solid var(--glass-border);">
    <div class="container">
        <div class="row align-items-center" style="display: flex; flex-wrap: wrap; gap: 2rem;">
            
            <!-- Detalhes do Evento -->
            <div class="col-md-6 reveal" style="flex: 1; min-width: 300px;">
                <span class="hero-pre-title" style="margin-bottom: 1rem; display: inline-block;">
                    <?php if($is_future): ?>
                        🔥 PRÓXIMO EVENTO ISP
                    <?php else: ?>
                        ⏪ EVENTO REALIZADO
                    <?php endif; ?>
                </span>
                <h1 style="font-size: clamp(2rem, 5vw, 3.5rem); margin-bottom: 1.5rem; line-height: 1.1;"><?= htmlspecialchars($evento['title']) ?></h1>
                
                <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
                    <div style="background: rgba(255,165,0,0.1); border: 1px solid var(--brand-orange); padding: 0.8rem 1.2rem; border-radius: 8px; display: inline-block;">
                        <div style="font-family: var(--font-mono); font-size: 0.8rem; color: var(--brand-orange); text-transform: uppercase; margin-bottom: 0.3rem;">Data</div>
                        <div style="font-weight: 700; font-size: 1.2rem;"><?= date('d/m/Y', strtotime($evento['event_date'])) ?></div>
                    </div>
                    <div style="background: rgba(255,165,0,0.1); border: 1px solid var(--brand-orange); padding: 0.8rem 1.2rem; border-radius: 8px; display: inline-block;">
                        <div style="font-family: var(--font-mono); font-size: 0.8rem; color: var(--brand-orange); text-transform: uppercase; margin-bottom: 0.3rem;">Horário</div>
                        <div style="font-weight: 700; font-size: 1.2rem;"><?= date('H:i', strtotime($evento['event_date'])) ?></div>
                    </div>
                </div>

                <div class="evento-description" style="font-size: 1.1rem; line-height: 1.6; color: var(--text-secondary); margin-bottom: 2rem;">
                    <?= $evento['description'] ?>
                </div>
            </div>
            
            <!-- Formulário ou Botão (Captação) -->
            <div class="col-md-5 reveal" style="flex: 1; min-width: 300px;">
                <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 12px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.4);">
                    
                    <?php if($evento['thumbnail']): ?>
                        <img src="/uploads/<?= $evento['thumbnail'] ?>" alt="<?= htmlspecialchars($evento['title']) ?>" style="width: 100%; aspect-ratio: 16/9; object-fit: cover; display: block; border-bottom: 1px solid var(--glass-border);">
                    <?php endif; ?>
                    
                    <div style="padding: 2rem;">
                        <h3 style="text-align: center; margin-bottom: 1.5rem; color: #fff;">
                            <?php if($is_future): ?>
                                Inscreva-se Gratuitamente
                            <?php else: ?>
                                Acessar Material do Evento
                            <?php endif; ?>
                        </h3>
                        
                        <?php if($evento['form_type'] === 'embed' && !empty($evento['form_embed'])): ?>
                            <!-- Código Embutido do CRM -->
                            <div class="crm-form-embed">
                                <?= $evento['form_embed'] ?>
                            </div>
                            <style>
                                .crm-form-embed iframe { width: 100% !important; border: none !important; min-height: 400px; border-radius: 8px; }
                                .crm-form-embed form { color: #fff; }
                                .crm-form-embed input, .crm-form-embed select { width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 4px; border: 1px solid #444; background: #222; color: #fff; }
                                .crm-form-embed button, .crm-form-embed input[type="submit"] { background: var(--brand-orange); color: #fff; border: none; padding: 12px 20px; border-radius: 4px; width: 100%; cursor: pointer; font-weight: bold; }
                            </style>
                        <?php elseif($evento['form_type'] === 'link' && !empty($evento['form_link'])): ?>
                            <!-- Botão Externo -->
                            <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2rem;">
                                Clique no botão abaixo para garantir sua vaga e receber todas as instruções pelo e-mail/WhatsApp.
                            </p>
                            <a href="<?= htmlspecialchars($evento['form_link']) ?>" class="btn" style="display: block; text-align: center; font-size: 1.2rem; padding: 1rem; width: 100%;" target="_blank" rel="noopener noreferrer">
                                Quero Participar!
                            </a>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--text-secondary);">Inscrições em breve.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
