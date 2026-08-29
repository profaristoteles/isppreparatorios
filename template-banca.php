<?php
/**
 * Template Público de Exibição de Banca Organizadora (/aulas-gratuitas/banca/{slug})
 */
require_once 'db_config.php';
require_once 'includes/aulas_gratuitas_utils.php';

$stmtBoard = $pdo->prepare("SELECT * FROM free_boards WHERE slug = ? AND active = 1 AND deleted_at IS NULL LIMIT 1");
$stmtBoard->execute([$banca_slug]);
$banca = $stmtBoard->fetch();

if (!$banca) {
    http_response_code(404);
    $dynamic_title = "Banca Não Encontrada";
    require_once 'includes/header.php';
    echo '<div class="container section-padding" style="padding: 5rem 5%; text-align: center;">';
    echo '<h1 style="font-size: 2rem; color: #fff; margin-bottom: 1rem;">404 - Banca Não Encontrada</h1>';
    echo '<p style="color: var(--text-secondary); margin-bottom: 2rem;">A banca examinadora solicitada não foi localizada no sistema.</p>';
    echo '<a href="/aulas-gratuitas" class="btn" style="padding: 0.8rem 1.5rem; background: var(--brand-orange); color: #fff; text-decoration: none; border-radius: 4px;">Voltar para Aulas Gratuitas</a>';
    echo '</div>';
    require_once 'includes/footer.php';
    exit;
}

// SEO
$dynamic_title = "Aulas e Questões da Banca " . htmlspecialchars($banca['name']) . " | ISP Preparatórios";
$dynamic_desc = !empty($banca['description']) ? mb_strimwidth(strip_tags($banca['description']), 0, 160, '...') : "Resolução de questões e videoaulas focadas no perfil da banca " . htmlspecialchars($banca['name']) . " no ISP Preparatórios.";

require_once 'includes/header.php';

// Videoaulas desta Banca
$sqlVideos = "SELECT v.*, c.name as channel_name, c.slug as channel_slug, d.name as discipline_name, t.name as teacher_name,
              (SELECT COUNT(*) FROM free_materials m WHERE m.video_id = v.id AND m.active = 1 AND m.deleted_at IS NULL) as total_materiais
              FROM free_videos v 
              JOIN free_channels c ON v.channel_id = c.id 
              LEFT JOIN free_disciplines d ON v.discipline_id = d.id 
              LEFT JOIN free_teachers t ON v.teacher_id = t.id 
              WHERE v.board_id = ? AND v.status = 'publicado' AND v.active = 1 AND v.deleted_at IS NULL 
              ORDER BY v.published_at DESC";
$stmtVideos = $pdo->prepare($sqlVideos);
$stmtVideos->execute([$banca['id']]);
$videosList = $stmtVideos->fetchAll();

// Concursos Associados a esta Banca
$stmtContests = $pdo->prepare("SELECT * FROM free_contests WHERE board_id = ? AND active = 1 AND deleted_at IS NULL ORDER BY name ASC");
$stmtContests->execute([$banca['id']]);
$contestsList = $stmtContests->fetchAll();

// Disciplinas Relacionadas às aulas desta Banca
$stmtDisciplines = $pdo->prepare("SELECT DISTINCT d.name, d.slug FROM free_videos v JOIN free_disciplines d ON v.discipline_id = d.id WHERE v.board_id = ? AND v.status = 'publicado' AND v.active = 1 AND v.deleted_at IS NULL ORDER BY d.name ASC");
$stmtDisciplines->execute([$banca['id']]);
$disciplinesList = $stmtDisciplines->fetchAll();
?>

<div class="container section-padding" style="margin-top: 90px; padding: 2rem 5%;">

    <!-- Breadcrumb Semântico -->
    <nav aria-label="Breadcrumb" style="margin-bottom: 2rem; font-size: 0.85rem; color: var(--text-secondary);">
        <a href="/index.php" style="color: var(--text-secondary); text-decoration: none;">Início</a>
        <span style="margin: 0 0.4rem;">&gt;</span>
        <a href="/aulas-gratuitas" style="color: var(--text-secondary); text-decoration: none;">Aulas Gratuitas</a>
        <span style="margin: 0 0.4rem;">&gt;</span>
        <span style="color: var(--text-secondary);">Banca</span>
        <span style="margin: 0 0.4rem;">&gt;</span>
        <span style="color: var(--brand-orange); font-weight: 600;"><?= htmlspecialchars($banca['name']) ?></span>
    </nav>

    <!-- Header da Banca -->
    <section class="card" style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--glass-border); border-radius: 8px; padding: 2rem; margin-bottom: 3rem;">
        <div class="banca-header-box" style="display: flex; gap: 2rem; align-items: center; flex-wrap: wrap;">
            <?php if (!empty($banca['logo'])): ?>
                <div style="background: #fff; padding: 1rem; border-radius: 8px; display: flex; align-items: center; justify-content: center; width: 120px; height: 80px;">
                    <img src="/uploads/<?= htmlspecialchars($banca['logo']) ?>" alt="Logo <?= htmlspecialchars($banca['name']) ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                </div>
            <?php else: ?>
                <div style="background: rgba(3, 4, 94, 0.6); border: 1px solid var(--glass-border); border-radius: 8px; display: flex; align-items: center; justify-content: center; width: 80px; height: 80px;">
                    <i class="fas fa-building fa-2x" style="color: var(--brand-orange);" aria-hidden="true"></i>
                </div>
            <?php endif; ?>

            <div style="flex: 1; min-width: 250px;">
                <span style="color: var(--brand-orange); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">BANCA ORGANIZADORA</span>
                <h1 style="font-size: 2.2rem; color: #fff; font-weight: 800; margin: 0.2rem 0 0.6rem 0;"><?= htmlspecialchars($banca['name']) ?></h1>
                
                <?php if (!empty($banca['description'])): ?>
                    <p style="font-size: 0.95rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">
                        <?= htmlspecialchars($banca['description']) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tags de Concursos e Disciplinas Relacionadas -->
        <?php if (!empty($contestsList) || !empty($disciplinesList)): ?>
        <div style="margin-top: 1.5rem; pt-1rem; border-top: 1px solid var(--glass-border); display: flex; gap: 1.5rem; flex-wrap: wrap;">
            <?php if (!empty($contestsList)): ?>
                <div>
                    <span style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 600; display: block; margin-bottom: 0.4rem;">Concursos Associados:</span>
                    <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                        <?php foreach ($contestsList as $ct): ?>
                            <span style="background: rgba(255,255,255,0.05); color: #fff; font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 12px; border: 1px solid var(--glass-border);">
                                <i class="fas fa-award" style="color: var(--brand-orange);" aria-hidden="true"></i> <?= htmlspecialchars($ct['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($disciplinesList)): ?>
                <div>
                    <span style="font-size: 0.8rem; color: var(--text-secondary); font-weight: 600; display: block; margin-bottom: 0.4rem;">Disciplinas Disponíveis:</span>
                    <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                        <?php foreach ($disciplinesList as $d): ?>
                            <span style="background: rgba(3, 4, 94, 0.4); color: #fff; font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
                                <i class="fas fa-book" aria-hidden="true"></i> <?= htmlspecialchars($d['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- Grade de Aulas da Banca -->
    <section>
        <div style="margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.6rem; color: #fff;">Aulas da Banca <?= htmlspecialchars($banca['name']) ?> (<?= count($videosList) ?>)</h2>
        </div>

        <?php if (empty($videosList)): ?>
            <div style="background: rgba(255,255,255,0.02); border: 1px dashed var(--glass-border); padding: 3rem 1.5rem; text-align: center; border-radius: 8px;">
                <i class="fas fa-folder-open fa-3x" style="color: var(--brand-orange); margin-bottom: 1rem; display: block;" aria-hidden="true"></i>
                <h3 style="font-size: 1.2rem; color: #fff; margin-bottom: 0.5rem;">Nenhuma aula cadastrada para esta banca no momento.</h3>
                <p style="color: var(--text-secondary);">Fique atento às nossas atualizações diárias.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr)); gap: 1.5rem;">
                <?php foreach ($videosList as $v): ?>
                    <article style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 8px; overflow: hidden; display: flex; flex-direction: column;">
                        <div style="position: relative; aspect-ratio: 16/9; background: #000; overflow: hidden;">
                            <?php if (!empty($v['thumbnail'])): ?>
                                <img src="/uploads/<?= htmlspecialchars($v['thumbnail']) ?>" alt="Thumbnail da aula: <?= htmlspecialchars($v['title']) ?>" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php elseif (!empty($v['youtube_id'])): ?>
                                <img src="https://img.youtube.com/vi/<?= htmlspecialchars($v['youtube_id']) ?>/hqdefault.jpg" alt="Thumbnail da aula: <?= htmlspecialchars($v['title']) ?>" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #03045e;">
                                    <i class="fas fa-video fa-2x" style="color: rgba(255,255,255,0.3);" aria-hidden="true"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div style="position: absolute; top: 10px; left: 10px; right: 10px; display: flex; justify-content: space-between; gap: 0.5rem; pointer-events: none;">
                                <?php if (!empty($v['discipline_name'])): ?>
                                    <span style="background: rgba(3, 4, 94, 0.85); backdrop-filter: blur(4px); color: #fff; font-size: 0.7rem; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 600;">
                                        <?= htmlspecialchars($v['discipline_name']) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($v['total_materiais'] > 0): ?>
                                    <span style="background: #ff8000; color: #fff; font-size: 0.7rem; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700;">
                                        📘 MATERIAL GRATUITO
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="padding: 1.2rem; display: flex; flex-direction: column; flex-grow: 1;">
                            <div style="font-size: 0.75rem; color: var(--brand-orange); font-weight: 600; margin-bottom: 0.4rem; text-transform: uppercase;">
                                <?= htmlspecialchars($v['channel_name']) ?>
                            </div>

                            <h3 style="font-size: 1.05rem; color: #fff; font-weight: 700; line-height: 1.4; margin-bottom: 0.8rem; flex-grow: 1;">
                                <a href="/aulas-gratuitas/<?= htmlspecialchars($v['channel_slug']) ?>/<?= htmlspecialchars($v['slug']) ?>" style="color: inherit; text-decoration: none;">
                                    <?= htmlspecialchars($v['title']) ?>
                                </a>
                            </h3>

                            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 1.2rem;">
                                <?php if (!empty($v['teacher_name'])): ?>
                                    <div><i class="fas fa-user-circle" aria-hidden="true"></i> Profª: <strong><?= htmlspecialchars($v['teacher_name']) ?></strong></div>
                                <?php endif; ?>
                            </div>

                            <a href="/aulas-gratuitas/<?= htmlspecialchars($v['channel_slug']) ?>/<?= htmlspecialchars($v['slug']) ?>" aria-label="Assistir aula: <?= htmlspecialchars($v['title']) ?>" style="display: block; text-align: center; padding: 0.7rem 1rem; background: var(--brand-blue); color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.85rem; font-weight: 700; border: 1px solid rgba(255,255,255,0.1);">
                                <i class="fas fa-play" aria-hidden="true"></i> ASSISTIR AULA
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</div>

<?php require_once 'includes/footer.php'; ?>
