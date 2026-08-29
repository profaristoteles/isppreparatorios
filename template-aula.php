<?php
/**
 * Template Público da Página Individual da Videoaula (/aulas-gratuitas/{canal_slug}/{video_slug})
 */
require_once 'db_config.php';
require_once 'includes/aulas_gratuitas_utils.php';
require_once 'admin/includes/admin_security.php';

// Buscar Videoaula publicada
$sqlVideo = "SELECT v.*, c.name as channel_name, c.slug as channel_slug, 
             d.name as discipline_name, d.slug as discipline_slug,
             t.name as teacher_name, t.slug as teacher_slug, t.photo as teacher_photo, t.bio as teacher_bio,
             b.name as board_name, b.slug as board_slug,
             ct.name as contest_name, ct.slug as contest_slug, ct.exam_date
             FROM free_videos v 
             JOIN free_channels c ON v.channel_id = c.id 
             LEFT JOIN free_disciplines d ON v.discipline_id = d.id 
             LEFT JOIN free_teachers t ON v.teacher_id = t.id 
             LEFT JOIN free_boards b ON v.board_id = b.id 
             LEFT JOIN free_contests ct ON v.contest_id = ct.id 
             WHERE c.slug = ? AND v.slug = ? AND v.status = 'publicado' AND v.active = 1 AND v.deleted_at IS NULL 
             LIMIT 1";

$stmtV = $pdo->prepare($sqlVideo);
$stmtV->execute([$canal_slug, $video_slug]);
$v = $stmtV->fetch();

if (!$v) {
    http_response_code(404);
    $dynamic_title = "Videoaula Não Encontrada";
    require_once 'includes/header.php';
    echo '<div class="container section-padding" style="padding: 5rem 5%; text-align: center;">';
    echo '<h1 style="font-size: 2rem; color: #fff; margin-bottom: 1rem;">404 - Videoaula Não Encontrada</h1>';
    echo '<p style="color: var(--text-secondary); margin-bottom: 2rem;">A aula solicitada não está disponível, está em rascunho ou foi desativada.</p>';
    echo '<a href="/aulas-gratuitas" class="btn" style="padding: 0.8rem 1.5rem; background: var(--brand-orange); color: #fff; text-decoration: none; border-radius: 4px;">Voltar para Aulas Gratuitas</a>';
    echo '</div>';
    require_once 'includes/footer.php';
    exit;
}

// 1. Registrar Evento Analítico de Visualização (video_accessed) com Throttling (60 min)
log_free_video_event($pdo, $v['id'], 'video_accessed');

// 2. Carregar Materiais em PDF Ativos vinculados
$stmtMat = $pdo->prepare("SELECT * FROM free_materials WHERE video_id = ? AND active = 1 AND deleted_at IS NULL ORDER BY id ASC");
$stmtMat->execute([$v['id']]);
$materiaisList = $stmtMat->fetchAll();

// 3. Carregar Aulas Relacionadas (mesma disciplina, banca ou canal, excluindo a atual)
$sqlRel = "SELECT v.*, c.slug as channel_slug, d.name as discipline_name 
           FROM free_videos v 
           JOIN free_channels c ON v.channel_id = c.id 
           LEFT JOIN free_disciplines d ON v.discipline_id = d.id 
           WHERE (v.discipline_id = ? OR v.board_id = ? OR v.channel_id = ?) 
             AND v.id != ? AND v.status = 'publicado' AND v.active = 1 AND v.deleted_at IS NULL 
           ORDER BY v.id DESC LIMIT 4";
$stmtRel = $pdo->prepare($sqlRel);
$stmtRel->execute([(int)$v['discipline_id'], (int)$v['board_id'], (int)$v['channel_id'], (int)$v['id']]);
$relatedVideos = $stmtRel->fetchAll();

// SEO e Metatags
$pageTitle = !empty($v['seo_title']) ? $v['seo_title'] : $v['title'] . " | ISP Preparatórios";
$pageDesc = !empty($v['seo_description']) ? $v['seo_description'] : (!empty($v['short_description']) ? $v['short_description'] : $v['title']);

$dynamic_title = $pageTitle;
$dynamic_desc = mb_strimwidth(strip_tags($pageDesc), 0, 160, '...');

// Imagem da Thumbnail para OpenGraph
$thumbUrl = "https://isppreparatorios.com.br/uploads/logo.png";
if (!empty($v['thumbnail'])) {
    $thumbUrl = "https://isppreparatorios.com.br/uploads/" . $v['thumbnail'];
} elseif (!empty($v['youtube_id'])) {
    $thumbUrl = "https://img.youtube.com/vi/" . $v['youtube_id'] . "/hqdefault.jpg";
}

// Configuração do WhatsApp Group URL
$config = get_config($pdo);
$whatsapp_group_url = !empty($config['whatsapp_group_url']) ? $config['whatsapp_group_url'] : '';

require_once 'includes/header.php';
?>

<!-- Schema.org JSON-LD VideoObject -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "VideoObject",
  "name": <?= json_encode($v['title']) ?>,
  "description": <?= json_encode(strip_tags($pageDesc)) ?>,
  "thumbnailUrl": [<?= json_encode($thumbUrl) ?>],
  "uploadDate": <?= json_encode(date('c', strtotime($v['published_at']))) ?>,
  "embedUrl": <?= json_encode("https://www.youtube-nocookie.com/embed/" . $v['youtube_id']) ?>
}
</script>

<div class="container section-padding" style="margin-top: 90px; padding: 2rem 5%;">

    <!-- Breadcrumb Semântico -->
    <nav aria-label="Breadcrumb" style="margin-bottom: 1.5rem; font-size: 0.85rem; color: var(--text-secondary);">
        <a href="/index.php" style="color: var(--text-secondary); text-decoration: none;">Início</a>
        <span style="margin: 0 0.4rem;">&gt;</span>
        <a href="/aulas-gratuitas" style="color: var(--text-secondary); text-decoration: none;">Aulas Gratuitas</a>
        <span style="margin: 0 0.4rem;">&gt;</span>
        <a href="/aulas-gratuitas/<?= htmlspecialchars($v['channel_slug']) ?>" style="color: var(--text-secondary); text-decoration: none;"><?= htmlspecialchars($v['channel_name']) ?></a>
        <?php if (!empty($v['discipline_name'])): ?>
            <span style="margin: 0 0.4rem;">&gt;</span>
            <span style="color: var(--text-secondary);"><?= htmlspecialchars($v['discipline_name']) ?></span>
        <?php endif; ?>
    </nav>

    <!-- Cabeçalho da Videoaula -->
    <header style="margin-bottom: 2rem;">
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.5rem;">
            <span style="background: var(--brand-orange); color: #fff; font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 700; text-transform: uppercase;">
                <?= htmlspecialchars($v['channel_name']) ?>
            </span>
            <?php if (!empty($v['discipline_name'])): ?>
                <span style="background: rgba(3, 4, 94, 0.8); color: #fff; font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 4px; font-weight: 600; border: 1px solid rgba(255,255,255,0.15);">
                    <?= htmlspecialchars($v['discipline_name']) ?>
                </span>
            <?php endif; ?>
        </div>

        <h1 style="font-size: clamp(1.6rem, 3vw, 2.5rem); font-weight: 800; color: #fff; line-height: 1.2; margin-bottom: 1rem;">
            <?= htmlspecialchars($v['title']) ?>
        </h1>

        <!-- Metadados da Aula -->
        <div class="aula-header-meta">
            <?php if (!empty($v['teacher_name'])): ?>
                <div><i class="fas fa-user-circle" style="color: var(--brand-orange);" aria-hidden="true"></i> Professor(a): <strong><?= htmlspecialchars($v['teacher_name']) ?></strong></div>
            <?php endif; ?>

            <?php if (!empty($v['board_name'])): ?>
                <div>
                    <i class="fas fa-building" style="color: var(--brand-orange);" aria-hidden="true"></i> Banca: 
                    <a href="/aulas-gratuitas/banca/<?= htmlspecialchars($v['board_slug']) ?>" style="color: #fff; text-decoration: underline;">
                        <strong><?= htmlspecialchars($v['board_name']) ?></strong>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (!empty($v['contest_name'])): ?>
                <div><i class="fas fa-award" style="color: var(--brand-orange);" aria-hidden="true"></i> Concurso: <strong><?= htmlspecialchars($v['contest_name']) ?></strong></div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Player Responsivo 16:9 (On-Demand para Performance e Privacidade, SEM AUTOPLAY) -->
    <div style="margin-bottom: 2.5rem;">
        <div id="player-container" style="position: relative; width: 100%; aspect-ratio: 16/9; background: #000; border-radius: 8px; overflow: hidden; border: 1px solid var(--glass-border); box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <?php if (!empty($v['youtube_id'])): ?>
                <!-- Estado Inicial: Thumbnail com Botão de Reprodução Acessível -->
                <div id="player-preview" style="position: absolute; top:0; left:0; width:100%; height:100%; cursor: pointer;" onclick="loadYoutubePlayer('<?= htmlspecialchars($v['youtube_id']) ?>')">
                    <img src="<?= htmlspecialchars($thumbUrl) ?>" alt="Capa da videoaula: <?= htmlspecialchars($v['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.35); display: flex; align-items: center; justify-content: center; transition: background 0.3s ease;">
                        <button aria-label="Reproduzir videoaula: <?= htmlspecialchars($v['title']) ?>" style="width: 80px; height: 80px; background: var(--brand-orange); border: none; border-radius: 50%; color: #fff; font-size: 2.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 20px rgba(255,128,0,0.6); transition: transform 0.2s ease;">
                            <i class="fas fa-play" style="margin-left: 5px;" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-secondary);">
                    <span>Vídeo em configuração pelo administrador.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Seção de Ações: Material Gratuito / Grupo VIP / Compartilhamento -->
    <div class="aula-grid-layout" style="margin-bottom: 3rem;">
        
        <!-- Coluna Esquerda: Descrição e Material -->
        <div>
            <!-- Box do Material Gratuito (CTA Principal) -->
            <?php if (!empty($materiaisList)): ?>
                <section class="card" aria-label="Material Gratuito" style="background: linear-gradient(135deg, rgba(255,128,0,0.1), rgba(3,4,94,0.4)); border: 2px solid var(--brand-orange); padding: 1.8rem; border-radius: 8px; margin-bottom: 2.5rem;">
                    <div style="display: flex; gap: 1rem; align-items: flex-start; flex-wrap: wrap;">
                        <div style="background: var(--brand-orange); color: #fff; width: 50px; height: 50px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                            <i class="fas fa-book-open" aria-hidden="true"></i>
                        </div>
                        <div style="flex: 1; min-width: 240px;">
                            <span style="color: var(--brand-orange); font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">MATERIAL COMPLEMENTAR GRATUITO</span>
                            <h2 style="font-size: 1.4rem; color: #fff; font-weight: 800; margin: 0.2rem 0 0.5rem 0;">Baixe o Caderno desta Aula</h2>
                            <p style="font-size: 0.95rem; color: rgba(255,255,255,0.9); line-height: 1.5; margin-bottom: 1.2rem;">
                                Baixe gratuitamente o material utilizado pelo professor nesta videoaula para acompanhar as questões e fixar o conteúdo.
                            </p>
                            
                            <?php foreach ($materiaisList as $mat): ?>
                                <div class="aula-material-item">
                                    <div>
                                        <strong style="color: #fff; font-size: 0.95rem; display: block;"><?= htmlspecialchars($mat['title']) ?></strong>
                                        <?php if (!empty($mat['description'])): ?>
                                            <small style="color: var(--text-secondary);"><?= htmlspecialchars($mat['description']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <button class="btn-material-trigger" onclick="requestMaterial(<?= $v['id'] ?>, <?= $mat['id'] ?>, '<?= htmlspecialchars(addslashes($mat['title'])) ?>', this)" style="padding: 0.7rem 1.4rem; background: var(--brand-orange); color: #fff; border: none; border-radius: 4px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;">
                                        <i class="fas fa-download" aria-hidden="true"></i> BAIXAR MATERIAL GRATUITO
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Descrição da Aula -->
            <section class="card" style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); padding: 1.8rem; border-radius: 8px;">
                <h2 style="font-size: 1.3rem; color: #fff; font-weight: 700; margin-bottom: 1rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 0.5rem;">
                    Sobre esta Aula
                </h2>
                
                <?php if (!empty($v['short_description'])): ?>
                    <p style="font-size: 1rem; color: #fff; font-weight: 600; line-height: 1.6; margin-bottom: 1rem;">
                        <?= htmlspecialchars($v['short_description']) ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($v['full_description'])): ?>
                    <div style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.7;">
                        <?= nl2br(htmlspecialchars($v['full_description'])) ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Coluna Direita: Inscrição no YouTube, Grupo VIP e Compartilhamento -->
        <div>
            <!-- CTA Inscrição no YouTube -->
            <div class="card" style="background: linear-gradient(135deg, rgba(255,0,0,0.15), rgba(3,4,94,0.6)); border: 1px solid #FF0000; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                <i class="fab fa-youtube fa-3x" style="color: #FF0000; margin-bottom: 0.8rem; display: block;" aria-hidden="true"></i>
                <h3 style="font-size: 1.1rem; color: #fff; font-weight: 700; margin-bottom: 0.5rem;">Canal do ISP no YouTube</h3>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.2rem; line-height: 1.4;">
                    Inscreva-se no nosso canal oficial para acompanhar aulas ao vivo e novas resoluções de questões.
                </p>
                <a href="https://www.youtube.com/@ISPPreparat%C3%B3rios?sub_confirmation=1" target="_blank" rel="noopener noreferrer" style="display: block; padding: 0.7rem 1rem; background: #FF0000; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 700; font-size: 0.85rem; box-shadow: 0 4px 12px rgba(255,0,0,0.4);">
                    <i class="fab fa-youtube" aria-hidden="true"></i> INSCREVER-SE NO CANAL
                </a>
            </div>

            <!-- CTA Grupo VIP WhatsApp -->
            <?php if (!empty($whatsapp_group_url)): ?>
                <div class="card" style="background: rgba(37, 211, 102, 0.1); border: 1px solid rgba(37, 211, 102, 0.3); padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                    <i class="fab fa-whatsapp fa-3x" style="color: #25D366; margin-bottom: 0.8rem; display: block;" aria-hidden="true"></i>
                    <h3 style="font-size: 1.1rem; color: #fff; font-weight: 700; margin-bottom: 0.5rem;">Grupo de Estudos no WhatsApp</h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.2rem; line-height: 1.4;">
                        Receba notificações de novas aulas, editais e materiais gratuitos no grupo oficial.
                    </p>
                    <a href="<?= htmlspecialchars($whatsapp_group_url) ?>" target="_blank" rel="noopener noreferrer" style="display: block; padding: 0.7rem 1rem; background: #25D366; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 700; font-size: 0.85rem;">
                        <i class="fab fa-whatsapp" aria-hidden="true"></i> ENTRAR NO GRUPO VIP
                    </a>
                </div>
            <?php endif; ?>

            <!-- Botões de Compartilhamento -->
            <div class="card" style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); padding: 1.5rem; border-radius: 8px;">
                <h3 style="font-size: 1rem; color: #fff; font-weight: 700; margin-bottom: 1rem; text-align: center;">Compartilhar Aula</h3>
                
                <?php
                    $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                    $shareText = rawurlencode("Assista à aula '" . $v['title'] . "' no ISP Preparatórios: " . $currentUrl);
                ?>
                
                <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                    <a href="https://api.whatsapp.com/send?text=<?= $shareText ?>" target="_blank" rel="noopener noreferrer" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.6rem; background: #25D366; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">
                        <i class="fab fa-whatsapp" aria-hidden="true"></i> WhatsApp
                    </a>
                    
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($currentUrl) ?>" target="_blank" rel="noopener noreferrer" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.6rem; background: #1877F2; color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.85rem; font-weight: 600;">
                        <i class="fab fa-facebook-f" aria-hidden="true"></i> Facebook
                    </a>

                    <button onclick="copyCurrentUrl()" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.6rem; background: rgba(255,255,255,0.1); border: 1px solid var(--glass-border); color: #fff; border-radius: 4px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-link" aria-hidden="true"></i> <span id="copy-btn-text">Copiar Link</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Videoaulas Relacionadas -->
    <?php if (!empty($relatedVideos)): ?>
        <section style="border-top: 1px solid var(--glass-border); padding-top: 3rem;">
            <h2 style="font-size: 1.6rem; color: #fff; font-weight: 700; margin-bottom: 1.5rem;">
                Videoaulas Relacionadas
            </h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem;">
                <?php foreach ($relatedVideos as $rel): ?>
                    <article style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 8px; overflow: hidden; display: flex; flex-direction: column;">
                        <div style="position: relative; aspect-ratio: 16/9; background: #000;">
                            <?php if (!empty($rel['thumbnail'])): ?>
                                <img src="/uploads/<?= htmlspecialchars($rel['thumbnail']) ?>" alt="<?= htmlspecialchars($rel['title']) ?>" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php elseif (!empty($rel['youtube_id'])): ?>
                                <img src="https://img.youtube.com/vi/<?= htmlspecialchars($rel['youtube_id']) ?>/hqdefault.jpg" alt="<?= htmlspecialchars($rel['title']) ?>" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #03045e;">
                                    <i class="fas fa-video fa-2x" style="color: rgba(255,255,255,0.3);" aria-hidden="true"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="padding: 1rem; display: flex; flex-direction: column; flex-grow: 1;">
                            <h3 style="font-size: 0.95rem; color: #fff; font-weight: 700; line-height: 1.4; margin-bottom: 0.8rem; flex-grow: 1;">
                                <a href="/aulas-gratuitas/<?= htmlspecialchars($rel['channel_slug']) ?>/<?= htmlspecialchars($rel['slug']) ?>" style="color: inherit; text-decoration: none;">
                                    <?= htmlspecialchars($rel['title']) ?>
                                </a>
                            </h3>

                            <a href="/aulas-gratuitas/<?= htmlspecialchars($rel['channel_slug']) ?>/<?= htmlspecialchars($rel['slug']) ?>" style="display: block; text-align: center; padding: 0.5rem 0.8rem; background: var(--brand-blue); color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.8rem; font-weight: 700;">
                                ASSISTIR AULA
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</div>

<!-- Modal Acessível de Captura de Lead para Liberar o PDF -->
<div id="lead-modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div id="lead-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modal-title" style="background: #0d1117; border: 1px solid var(--glass-border); border-radius: 8px; width: 100%; max-width: 500px; padding: 2rem; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.8);">
        
        <button onclick="closeLeadModal()" aria-label="Fechar modal" style="position: absolute; top: 1rem; right: 1rem; background: transparent; border: none; color: var(--text-secondary); font-size: 1.5rem; cursor: pointer; padding: 0.3rem; line-height: 1;">
            &times;
        </button>

        <div id="modal-form-content">
            <span style="color: var(--brand-orange); font-weight: 700; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">LIBERAÇÃO DE CONTEÚDO</span>
            <h2 id="modal-title" style="font-size: 1.4rem; color: #fff; font-weight: 800; margin: 0.2rem 0 0.5rem 0;">Acessar Material Gratuito</h2>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1.5rem;" id="modal-material-name">
                Preencha seus dados abaixo para liberar o download em PDF.
            </p>

            <div id="modal-error-box" aria-live="polite" style="display: none; background: rgba(220,53,69,0.2); border: 1px solid #dc3545; color: #ff8080; padding: 0.8rem; border-radius: 4px; font-size: 0.85rem; margin-bottom: 1rem;"></div>

            <form id="lead-capture-form" onsubmit="submitLeadCapture(event)">
                <input type="hidden" id="modal-video-id" name="video_id" value="<?= $v['id'] ?>">
                <input type="hidden" id="modal-material-id" name="material_id" value="">
                <?= csrf_field() ?>
                
                <!-- Honeypot Anti-Spam (Campo invisível para robôs) -->
                <div style="position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; opacity: 0; pointer-events: none;" aria-hidden="true" tabindex="-1">
                    <input type="text" name="website_url_check" autocomplete="off" tabindex="-1">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label for="lead-name" style="display: block; font-size: 0.85rem; color: #fff; font-weight: 600; margin-bottom: 0.3rem;">Nome Completo *</label>
                    <input type="text" id="lead-name" name="name" autocomplete="name" required placeholder="Digite seu nome completo..." style="width: 100%; padding: 0.7rem; background: rgba(0,0,0,0.5); border: 1px solid var(--glass-border); border-radius: 4px; color: #fff; font-size: 0.9rem;">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label for="lead-email" style="display: block; font-size: 0.85rem; color: #fff; font-weight: 600; margin-bottom: 0.3rem;">E-mail *</label>
                    <input type="email" id="lead-email" name="email" autocomplete="email" required placeholder="seuemail@exemplo.com" style="width: 100%; padding: 0.7rem; background: rgba(0,0,0,0.5); border: 1px solid var(--glass-border); border-radius: 4px; color: #fff; font-size: 0.9rem;">
                </div>

                <div style="margin-bottom: 1.2rem;">
                    <label for="lead-phone" style="display: block; font-size: 0.85rem; color: #fff; font-weight: 600; margin-bottom: 0.3rem;">WhatsApp com DDD *</label>
                    <input type="tel" id="lead-phone" name="phone" autocomplete="tel" required placeholder="(11) 99999-9999" style="width: 100%; padding: 0.7rem; background: rgba(0,0,0,0.5); border: 1px solid var(--glass-border); border-radius: 4px; color: #fff; font-size: 0.9rem;">
                </div>

                <!-- Consentimentos LGPD Separados -->
                <div style="margin-bottom: 1rem; font-size: 0.8rem; color: var(--text-secondary); display: flex; gap: 0.6rem; align-items: flex-start;">
                    <input type="checkbox" id="consent-privacy" name="consent_privacy" required style="margin-top: 0.2rem; cursor: pointer;">
                    <label for="consent-privacy" style="cursor: pointer; line-height: 1.4;">
                        Li e estou ciente da <a href="/sobre.php" target="_blank" style="color: var(--brand-orange); text-decoration: underline;">Política de Privacidade</a> e do tratamento dos meus dados necessário para disponibilização do material solicitado. *
                    </label>
                </div>

                <div style="margin-bottom: 1.5rem; font-size: 0.8rem; color: var(--text-secondary); display: flex; gap: 0.6rem; align-items: flex-start;">
                    <input type="checkbox" id="consent-marketing" name="consent_marketing" style="margin-top: 0.2rem; cursor: pointer;">
                    <label for="consent-marketing" style="cursor: pointer; line-height: 1.4;">
                        Quero receber novidades, conteúdos gratuitos, informações sobre concursos, cursos e ofertas do ISP Preparatórios por e-mail e/ou WhatsApp. (Opcional)
                    </label>
                </div>

                <button type="submit" id="btn-submit-lead" style="width: 100%; padding: 0.8rem; background: var(--brand-orange); color: #fff; border: none; border-radius: 4px; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: background 0.3s ease;">
                    <i class="fas fa-download" aria-hidden="true"></i> ACESSAR MATERIAL
                </button>
            </form>
        </div>

        <!-- Conteúdo de Sucesso (Pós-Captura) -->
        <div id="modal-success-content" style="display: none; text-align: center; padding: 1rem 0;">
            <i class="fas fa-check-circle fa-4x" style="color: #28a745; margin-bottom: 1rem;" aria-hidden="true"></i>
            <h3 style="font-size: 1.5rem; color: #fff; font-weight: 800; margin-bottom: 0.5rem;">Material Liberado!</h3>
            <p style="font-size: 0.95rem; color: var(--text-secondary); margin-bottom: 1.5rem; line-height: 1.5;">
                O seu download em PDF foi iniciado. Se o arquivo não abrir automaticamente, clique no botão abaixo:
            </p>

            <a id="success-download-link" href="#" class="btn" style="display: inline-block; padding: 0.8rem 1.6rem; background: var(--brand-orange); color: #fff; text-decoration: none; border-radius: 4px; font-weight: 700; font-size: 0.9rem; margin-bottom: 1.5rem;">
                <i class="fas fa-file-pdf" aria-hidden="true"></i> BAIXAR MATERIAL AGORA
            </a>

            <div id="success-whatsapp-box" style="display: none; background: rgba(37, 211, 102, 0.1); border: 1px solid rgba(37, 211, 102, 0.3); padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                <p style="font-size: 0.85rem; color: #fff; margin-bottom: 0.8rem;">
                    Participe também do nosso Grupo VIP no WhatsApp para receber avisos de novas aulas:
                </p>
                <a id="success-whatsapp-link" href="#" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 0.6rem 1.2rem; background: #25D366; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 700; font-size: 0.85rem;">
                    <i class="fab fa-whatsapp" aria-hidden="true"></i> ENTRAR NO GRUPO VIP
                </a>
            </div>
        </div>

    </div>
</div>

<script>
let lastTriggerBtn = null;

// Carregamento On-Demand do Player de YouTube SEM AUTOPLAY (youtube-nocookie.com)
function loadYoutubePlayer(youtubeId) {
    const container = document.getElementById('player-container');
    if (!container) return;
    
    container.innerHTML = `
        <iframe 
            src="https://www.youtube-nocookie.com/embed/${youtubeId}?rel=0&modestbranding=1" 
            title="<?= htmlspecialchars(addslashes($v['title'])) ?>" 
            style="width: 100%; height: 100%; border: none;" 
            allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
            allowfullscreen>
        </iframe>
    `;
}

// Disparo do Evento 'material_requested' e Abertura do Modal de Captura
function requestMaterial(videoId, materialId, materialTitle, triggerBtn) {
    lastTriggerBtn = triggerBtn;
    
    // Dispara log do evento 'material_requested'
    fetch('/ajax_capture_lead.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `video_id=${videoId}&material_id=${materialId}&event_type=material_requested`
    }).catch(e => console.log('Log material_requested failed'));
    
    // Configura o modal
    document.getElementById('modal-material-id').value = materialId;
    document.getElementById('modal-material-name').innerText = 'Material: ' + materialTitle;
    document.getElementById('modal-error-box').style.display = 'none';
    document.getElementById('modal-form-content').style.display = 'block';
    document.getElementById('modal-success-content').style.display = 'none';
    
    // Exibe modal com animação suave e define foco inicial
    const overlay = document.getElementById('lead-modal-overlay');
    overlay.style.display = 'flex';
    setTimeout(() => {
        document.getElementById('lead-name').focus();
    }, 100);
}

// Fechar Modal com retorno de foco
function closeLeadModal() {
    const overlay = document.getElementById('lead-modal-overlay');
    overlay.style.display = 'none';
    if (lastTriggerBtn) {
        lastTriggerBtn.focus();
    }
}

// Suporte à tecla ESC para fechar modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const overlay = document.getElementById('lead-modal-overlay');
        if (overlay && overlay.style.display === 'flex') {
            closeLeadModal();
        }
    }
});

// Submissão do Formulário de Captura via AJAX
function submitLeadCapture(e) {
    e.preventDefault();
    
    const form = document.getElementById('lead-capture-form');
    const submitBtn = document.getElementById('btn-submit-lead');
    const errorBox = document.getElementById('modal-error-box');
    
    errorBox.style.display = 'none';
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> PROCESSANDO...';
    
    const formData = new FormData(form);
    
    fetch('/ajax_capture_lead.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Sucesso na Captura
            document.getElementById('modal-form-content').style.display = 'none';
            document.getElementById('modal-success-content').style.display = 'block';
            document.getElementById('success-download-link').href = data.download_url;
            
            if (data.whatsapp_group_url) {
                document.getElementById('success-whatsapp-link').href = data.whatsapp_group_url;
                document.getElementById('success-whatsapp-box').style.display = 'block';
            }
            
            // Inicia o download automaticamente via streaming do token HMAC
            window.location.href = data.download_url;
        } else {
            // Erro de Validação ou Conflito
            errorBox.innerText = data.message || 'Ocorreu um erro ao processar sua solicitação.';
            errorBox.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-download"></i> ACESSAR MATERIAL';
        }
    })
    .catch(err => {
        errorBox.innerText = 'Falha de comunicação com o servidor. Tente novamente.';
        errorBox.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-download"></i> ACESSAR MATERIAL';
    });
}

// Copiar Link para a área de transferência
function copyCurrentUrl() {
    const url = window.location.href;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            document.getElementById('copy-btn-text').innerText = 'Link Copiado!';
            setTimeout(() => { document.getElementById('copy-btn-text').innerText = 'Copiar Link'; }, 3000);
        });
    } else {
        const dummy = document.createElement('input');
        document.body.appendChild(dummy);
        dummy.value = url;
        dummy.select();
        document.execCommand('copy');
        document.body.removeChild(dummy);
        document.getElementById('copy-btn-text').innerText = 'Link Copiado!';
        setTimeout(() => { document.getElementById('copy-btn-text').innerText = 'Copiar Link'; }, 3000);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
