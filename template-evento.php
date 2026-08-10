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
                            <?php 
                            $lote_info = get_active_lote_info($pdo, 'evento', $evento['id'], $evento);
                            $has_modalities = (!empty($lote_info['price_presencial']) || !empty($lote_info['price_online']));
                            ?>

                            <?php if (!empty($lote_info['lote_name'])): ?>
                                <!-- Badge do Lote Ativo do Aulão -->
                                <div style="background: rgba(255,128,0,0.15); border: 1px solid var(--brand-orange); border-radius: 8px; padding: 0.8rem 1rem; margin-bottom: 1.5rem; text-align: center;">
                                    <span style="color: var(--brand-orange); font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; display: block;">
                                        🏷️ <?= htmlspecialchars($lote_info['lote_name']) ?> VIGENTE
                                    </span>
                                    <?php if (!empty($lote_info['data_virada'])): ?>
                                        <small style="color: rgba(255,255,255,0.8); font-size: 0.8rem; display: block; margin-top: 4px;">
                                            ⏳ Virada de Lote em: <strong><?= date('d/m/Y \à\s H:i', strtotime($lote_info['data_virada'])) ?></strong>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <h3 style="text-align: center; margin-bottom: 1.5rem; color: #fff; font-size: 1.6rem; font-weight: 700; letter-spacing: -0.5px;">
                                <?php if($is_future): ?>
                                    Garanta sua Vaga no Aulão
                                <?php else: ?>
                                    Acessar Conteúdo do Evento
                                <?php endif; ?>
                            </h3>
                            
                            <?php if ($has_modalities): ?>
                                <!-- Opções por Modalidade (Presencial e Online) no Evento/Aulão -->
                                <div style="display: flex; flex-direction: column; gap: 1.2rem; margin-bottom: 2rem;">
                                    <?php if (!empty($lote_info['price_presencial'])): ?>
                                        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,128,0,0.4); border-radius: 10px; padding: 1.2rem;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                                <span style="font-weight: 700; color: #fff; font-size: 0.95rem;">🏫 Presencial</span>
                                                <span style="font-size: 1.4rem; font-weight: 700; color: var(--brand-orange); font-family: var(--font-mono);">
                                                    R$ <?= number_format($lote_info['price_presencial'], 2, ',', '.') ?>
                                                </span>
                                            </div>
                                            <?php 
                                            $btn_link_pres = !empty($lote_info['link_presencial']) ? $lote_info['link_presencial'] : ($evento['form_link'] ?? '#');
                                            ?>
                                            <a href="<?= htmlspecialchars($btn_link_pres) ?>" target="_blank" class="btn" style="width: 100%; font-size: 0.95rem; padding: 0.8rem; text-align: center; display: block; box-sizing: border-box; background: var(--brand-orange); border-color: var(--brand-orange); margin-top: 0.5rem; text-decoration: none;">
                                                Garantir Vaga Presencial
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($lote_info['price_online'])): ?>
                                        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(0,204,255,0.4); border-radius: 10px; padding: 1.2rem;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                                <span style="font-weight: 700; color: #fff; font-size: 0.95rem;">💻 Online</span>
                                                <span style="font-size: 1.4rem; font-weight: 700; color: #00ccff; font-family: var(--font-mono);">
                                                    R$ <?= number_format($lote_info['price_online'], 2, ',', '.') ?>
                                                </span>
                                            </div>
                                            <?php 
                                            $btn_link_onl = !empty($lote_info['link_online']) ? $lote_info['link_online'] : ($evento['form_link'] ?? '#');
                                            ?>
                                            <a href="<?= htmlspecialchars($btn_link_onl) ?>" target="_blank" class="btn" style="width: 100%; font-size: 0.95rem; padding: 0.8rem; text-align: center; display: block; box-sizing: border-box; background: transparent; border-color: #00ccff; color: #00ccff; margin-top: 0.5rem; text-decoration: none;">
                                                Garantir Vaga Online
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php elseif($evento['form_type'] === 'embed' && !empty($evento['form_embed'])): ?>
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
                            <?php elseif($evento['form_type'] === 'link' && (!empty($evento['form_link']) || !empty($lote_info['link_geral']))): ?>
                                <!-- Botão Externo -->
                                <?php $evt_link = !empty($lote_info['link_geral']) ? $lote_info['link_geral'] : $evento['form_link']; ?>
                                <p style="text-align: center; color: rgba(255,255,255,0.7); margin-bottom: 2rem; line-height: 1.6;">
                                    Clique no botão abaixo para concluir sua inscrição e receber todas as instruções de acesso.
                                </p>
                                <a href="<?= htmlspecialchars($evt_link) ?>" class="btn" style="display: block; width: 100%; text-align: center; font-size: 1.1rem; padding: 1.2rem; border-radius: 8px;" target="_blank" rel="noopener noreferrer">
                                    Quero Participar!
                                </a>
                            <?php else: ?>
                                <p style="text-align: center; color: var(--text-secondary); padding: 2rem 0;">Inscrições em breve.</p>
                            <?php endif; ?>

                            <!-- Botões de Compartilhamento -->
                            <?php
                            $share_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                            $share_host = $_SERVER['HTTP_HOST'];
                            if(empty($share_host) || $share_host === 'localhost') $share_host = 'isppreparatorios.com.br';
                            $share_url = $share_protocol . $share_host . (!empty($evento['slug']) ? '/evento/'.$evento['slug'] : '/evento-detalhes.php?id='.$evento['id']);
                            $share_title = urlencode($evento['title']);
                            $whatsapp_text = urlencode("Confira este evento imperdível do ISP Preparatórios:\n*" . $evento['title'] . "*\n\nAcesse aqui: " . $share_url);
                            ?>
                            <div style="margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.1); text-align: center;">
                                <h4 style="color: rgba(255,255,255,0.7); margin-bottom: 1rem; font-size: 0.95rem; font-weight: normal;">Compartilhe este evento</h4>
                                <div style="display: flex; justify-content: center; gap: 8px; flex-wrap: wrap;">
                                    <a href="https://api.whatsapp.com/send?text=<?= $whatsapp_text ?>" target="_blank" style="background: rgba(37, 211, 102, 0.15); color: #25D366; border: 1px solid rgba(37, 211, 102, 0.3); padding: 10px 15px; border-radius: 8px; font-weight: bold; text-decoration: none; display: flex; align-items: center; gap: 6px; font-size: 0.9rem; transition: all 0.2s;" onmouseover="this.style.background='#25D366'; this.style.color='#fff';" onmouseout="this.style.background='rgba(37, 211, 102, 0.15)'; this.style.color='#25D366';">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 0C5.385 0 0 5.385 0 12.031c0 2.124.551 4.195 1.597 6.015L.032 24l6.103-1.603a11.96 11.96 0 0 0 5.896 1.542h.005c6.645 0 12.03-5.385 12.03-12.031S18.676 0 12.031 0zm0 21.939h-.004a9.98 9.98 0 0 1-5.086-1.39l-.365-.216-3.784.993.999-3.69-.237-.377A9.957 9.957 0 0 1 2.052 12.03C2.052 6.525 6.526 2.05 12.03 2.05s9.978 4.474 9.978 9.98-4.473 9.98-9.978 9.98zm5.474-7.469c-.3-.15-1.774-.876-2.048-.976-.275-.101-.476-.15-.675.151-.201.3-.775.976-.95 1.176-.176.202-.351.226-.651.076-.301-.15-1.267-.467-2.414-1.493-.893-.799-1.496-1.787-1.672-2.088-.176-.301-.019-.464.131-.614.135-.135.301-.35.45-.526.151-.175.201-.3.301-.5.101-.2.051-.376-.025-.526-.075-.15-.675-1.626-.924-2.227-.243-.585-.488-.506-.674-.515-.176-.009-.376-.009-.576-.009s-.526.075-.801.376c-.275.301-1.05 1.026-1.05 2.5s1.076 2.88 1.226 3.08c.15.202 2.1 3.206 5.087 4.496.711.309 1.266.493 1.698.632.713.228 1.363.195 1.878.118.577-.086 1.774-.726 2.024-1.427.251-.7.251-1.301.176-1.427-.076-.126-.276-.201-.576-.35z"/></svg> WhatsApp
                                    </a>
                                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $share_url ?>" target="_blank" style="background: rgba(24, 119, 242, 0.15); color: #1877F2; border: 1px solid rgba(24, 119, 242, 0.3); padding: 10px 15px; border-radius: 8px; font-weight: bold; text-decoration: none; display: flex; align-items: center; gap: 6px; font-size: 0.9rem; transition: all 0.2s;" onmouseover="this.style.background='#1877F2'; this.style.color='#fff';" onmouseout="this.style.background='rgba(24, 119, 242, 0.15)'; this.style.color='#1877F2';">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg> Facebook
                                    </a>
                                    <a href="https://twitter.com/intent/tweet?url=<?= $share_url ?>&text=<?= $share_title ?>" target="_blank" style="background: rgba(255, 255, 255, 0.05); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); padding: 10px 15px; border-radius: 8px; font-weight: bold; text-decoration: none; display: flex; align-items: center; gap: 6px; font-size: 0.9rem; transition: all 0.2s;" onmouseover="this.style.background='#fff'; this.style.color='#000';" onmouseout="this.style.background='rgba(255, 255, 255, 0.05)'; this.style.color='#fff';">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg> X
                                    </a>
                                    <button onclick="navigator.clipboard.writeText('<?= $share_url ?>').then(() => { let o=this.innerHTML; this.innerHTML='<svg width=\'18\' height=\'18\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><polyline points=\'20 6 9 17 4 12\'></polyline></svg> Copiado!'; setTimeout(()=>this.innerHTML=o,2000); })" style="background: rgba(255, 255, 255, 0.05); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); padding: 10px 15px; border-radius: 8px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 0.9rem; font-family: var(--font-main); transition: all 0.2s;" onmouseover="this.style.background='rgba(255, 255, 255, 0.15)';" onmouseout="this.style.background='rgba(255, 255, 255, 0.05)';">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg> Link
                                    </button>
                                </div>
                            </div>
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
