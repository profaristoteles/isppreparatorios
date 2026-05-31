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

<main style="background: var(--obsidian-deep); min-height: 100vh;">
    <!-- Hero Section -->
    <section style="padding: 10rem 5% 5rem; position: relative; border-bottom: 1px solid var(--glass-border); overflow: hidden;">
        <!-- Fundo decorativo -->
        <div class="hero-bg-light" style="opacity: 0.3;"></div>
        
        <div class="container" style="display: flex; flex-wrap: wrap; gap: 4rem; align-items: flex-start; position: relative; z-index: 2;">
            
            <!-- Conteúdo Principal (Esquerda) -->
            <div class="reveal" style="flex: 1 1 500px;">
                <!-- Tags -->
                <div style="display: flex; gap: 10px; margin-bottom: 1.5rem;">
                    <?php if($is_future): ?>
                        <span style="background: rgba(255,165,0,0.15); color: var(--brand-orange); padding: 0.4rem 1rem; border-radius: 20px; font-weight: bold; font-size: 0.8rem; border: 1px solid var(--brand-orange);">🔥 EVENTO AO VIVO</span>
                    <?php else: ?>
                        <span style="background: rgba(255,255,255,0.1); color: #ccc; padding: 0.4rem 1rem; border-radius: 20px; font-weight: bold; font-size: 0.8rem; border: 1px solid var(--glass-border);">⏪ EVENTO ENCERRADO</span>
                    <?php endif; ?>
                </div>

                <h1 style="font-size: clamp(2.2rem, 5vw, 4rem); margin-bottom: 1.5rem; line-height: 1.15; font-weight: 800; letter-spacing: -1px; color: #fff;">
                    <?= htmlspecialchars($evento['title']) ?>
                </h1>

                <!-- Datas (Blocos Elegantes) -->
                <div style="display: flex; gap: 1.5rem; margin-bottom: 3rem; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 180px; background: rgba(0,0,0,0.2); border-left: 4px solid var(--brand-orange); padding: 1.2rem 1.5rem; border-radius: 0 8px 8px 0; box-shadow: inset 0 0 20px rgba(255,255,255,0.02);">
                        <span style="display: block; color: var(--text-secondary); font-family: var(--font-mono); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem;">Início do Evento</span>
                        <strong style="color: #fff; font-size: 1.3rem;"><?= date('d/m/Y \à\s H:i', strtotime($evento['event_date'])) ?></strong>
                    </div>
                    <?php if(!empty($evento['event_end_date'])): ?>
                    <div style="flex: 1; min-width: 180px; background: rgba(0,0,0,0.2); border-left: 4px solid rgba(255,255,255,0.3); padding: 1.2rem 1.5rem; border-radius: 0 8px 8px 0; box-shadow: inset 0 0 20px rgba(255,255,255,0.02);">
                        <span style="display: block; color: var(--text-secondary); font-family: var(--font-mono); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.5rem;">Término</span>
                        <strong style="color: #fff; font-size: 1.3rem;"><?= date('d/m/Y \à\s H:i', strtotime($evento['event_end_date'])) ?></strong>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="evento-description" style="font-size: 1.15rem; line-height: 1.8; color: rgba(255,255,255,0.85); margin-bottom: 2rem;">
                    <?= $evento['description'] ?>
                </div>
            </div>

            <!-- Coluna Direita: Formulário ou Capa Sticky -->
            <div class="reveal" style="flex: 1 1 350px; max-width: 500px; margin: 0 auto; width: 100%;">
                <div style="position: sticky; top: 120px; z-index: 10;">
                    <div style="background: var(--obsidian-surface); border: 1px solid var(--glass-border); border-radius: 16px; overflow: hidden; box-shadow: 0 30px 60px rgba(0,0,0,0.6);">
                        
                        <?php if($evento['thumbnail']): ?>
                            <div style="position: relative; width: 100%; aspect-ratio: 1/1; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; overflow: hidden; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 1.5rem;">
                                <img src="/uploads/<?= $evento['thumbnail'] ?>" alt="<?= htmlspecialchars($evento['title']) ?>" style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                            </div>
                        <?php endif; ?>

                        <div style="padding: 2.5rem 2rem;">
                            <h3 style="text-align: center; margin-bottom: 1.5rem; color: #fff; font-size: 1.6rem; font-weight: 700; letter-spacing: -0.5px;">
                                <?php if($is_future): ?>
                                    Garanta sua Vaga Agora
                                <?php else: ?>
                                    Acessar Conteúdo do Evento
                                <?php endif; ?>
                            </h3>
                            
                            <?php if($evento['form_type'] === 'embed' && !empty($evento['form_embed'])): ?>
                                <!-- Código Embutido do CRM com max-width para celular -->
                                <div class="crm-form-embed" style="width: 100%; overflow: hidden; position: relative;">
                                    <?= $evento['form_embed'] ?>
                                </div>
                                <style>
                                    /* Forçar o Iframe do CRM a nunca estourar a largura da caixa e do celular */
                                    .crm-form-embed iframe { 
                                        width: 100% !important; 
                                        max-width: 100% !important; 
                                        border: none !important; 
                                        min-height: 380px; 
                                        border-radius: 8px; 
                                        background: transparent;
                                    }
                                    .crm-form-embed form { color: #fff; max-width: 100%; }
                                    .crm-form-embed input, .crm-form-embed select { width: 100%; max-width: 100%; padding: 12px; margin-bottom: 15px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.3); color: #fff; font-family: var(--font-main); }
                                    .crm-form-embed button, .crm-form-embed input[type="submit"] { background: var(--brand-orange); color: #fff; border: none; padding: 14px 20px; border-radius: 6px; width: 100%; cursor: pointer; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s; }
                                    .crm-form-embed button:hover, .crm-form-embed input[type="submit"]:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(255,128,0,0.3); background: #ff9933; color: #000; }
                                </style>
                            <?php elseif($evento['form_type'] === 'link' && !empty($evento['form_link'])): ?>
                                <!-- Botão Externo -->
                                <p style="text-align: center; color: rgba(255,255,255,0.7); margin-bottom: 2rem; line-height: 1.6;">
                                    Clique no botão abaixo para concluir sua inscrição e receber todas as instruções de acesso.
                                </p>
                                <a href="<?= htmlspecialchars($evento['form_link']) ?>" class="btn" style="display: block; width: 100%; text-align: center; font-size: 1.1rem; padding: 1.2rem; border-radius: 8px;" target="_blank" rel="noopener noreferrer">
                                    Quero Participar!
                                </a>
                            <?php else: ?>
                                <p style="text-align: center; color: var(--text-secondary); padding: 2rem 0;">Inscrições em breve.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </section>

    <?php if(!empty($evento['schedule'])): ?>
    <!-- Seção de Programação -->
    <section style="background: rgba(255,255,255,0.02); padding: 6rem 5%;">
        <div class="container" style="max-width: 900px; margin: 0 auto;">
            <div class="reveal">
                <h2 style="color: #fff; font-size: clamp(2rem, 4vw, 2.8rem); margin-bottom: 3rem; text-align: center; display: flex; align-items: center; justify-content: center; gap: 20px;">
                    <span style="width: 50px; height: 2px; background: var(--brand-orange);"></span>
                    Programação do Evento
                    <span style="width: 50px; height: 2px; background: var(--brand-orange);"></span>
                </h2>
                <div class="schedule-content" style="background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 3.5rem; color: rgba(255,255,255,0.9); font-size: 1.1rem; line-height: 1.7; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <?= $evento['schedule'] ?>
                </div>
            </div>
        </div>
    </section>
    <style>
        /* Estilos base para conteúdo do TinyMCE na programação */
        .schedule-content h1, .schedule-content h2, .schedule-content h3, .schedule-content h4 { color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1.2rem; font-weight: 700; letter-spacing: -0.5px; }
        .schedule-content h1:first-child, .schedule-content h2:first-child, .schedule-content h3:first-child { margin-top: 0; }
        .schedule-content ul, .schedule-content ol { padding-left: 2rem; margin-bottom: 1.5rem; }
        .schedule-content li { margin-bottom: 0.8rem; }
        .schedule-content p { margin-bottom: 1.5rem; }
        @media (max-width: 768px) {
            .schedule-content { padding: 2rem 1.5rem !important; }
        }
    </style>
    <?php endif; ?>

    <?php if(!empty($evento['video_embed'])): ?>
    <!-- Seção de Vídeo -->
    <section style="background: var(--obsidian-deep); padding: 6rem 5%; border-top: 1px solid var(--glass-border);">
        <div class="container reveal" style="max-width: 1000px; margin: 0 auto; text-align: center;">
            <span style="color: var(--brand-orange); font-family: var(--font-mono); text-transform: uppercase; font-size: 0.9rem; letter-spacing: 2px; margin-bottom: 1rem; display: block;">Transmissão ao Vivo / Gravação</span>
            <h2 style="color: #fff; margin-bottom: 3.5rem; font-size: clamp(2rem, 4vw, 3rem);">Assista ao Evento</h2>
            
            <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 20px; box-shadow: 0 30px 60px rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.1); background: #000;">
                <?php 
                    // Assegurar que o iframe ocupa 100% do container
                    $video_code = preg_replace('/width=["\'][0-9]+["\']/', 'width="100%"', $evento['video_embed']);
                    $video_code = preg_replace('/height=["\'][0-9]+["\']/', 'height="100%"', $video_code);
                    echo str_replace('<iframe', '<iframe style="position:absolute; top:0; left:0; width:100%; height:100%; border:none; background: #000;"', $video_code);
                ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

</main>

<?php require_once 'includes/footer.php'; ?>
