<?php
/**
 * Template Público de Exibição do Canal (/aulas-gratuitas/{canal_slug})
 */
require_once 'db_config.php';
require_once 'includes/aulas_gratuitas_utils.php';

$stmtChan = $pdo->prepare("SELECT * FROM free_channels WHERE slug = ? AND active = 1 AND deleted_at IS NULL LIMIT 1");
$stmtChan->execute([$canal_slug]);
$canal = $stmtChan->fetch();

if (!$canal) {
    http_response_code(404);
    $dynamic_title = "Canal Não Encontrado";
    require_once 'includes/header.php';
    echo '<div class="container section-padding" style="margin-top: 90px; padding: 5rem 5%; text-align: center;">';
    echo '<h1 style="font-size: 2rem; color: #fff; margin-bottom: 1rem;">404 - Canal Não Encontrado</h1>';
    echo '<p style="color: var(--text-secondary); margin-bottom: 2rem;">O canal solicitado não existe ou foi removido.</p>';
    echo '<a href="/aulas-gratuitas" class="btn" style="padding: 0.8rem 1.5rem; background: var(--brand-orange); color: #fff; text-decoration: none; border-radius: 6px;">Voltar para Aulas Gratuitas</a>';
    echo '</div>';
    require_once 'includes/footer.php';
    exit;
}

// SEO
$dynamic_title = htmlspecialchars($canal['name']) . " | Aulas Gratuitas";
$dynamic_desc = !empty($canal['description']) ? mb_strimwidth(strip_tags($canal['description']), 0, 160, '...') : "Videoaulas gratuitas do canal " . htmlspecialchars($canal['name']) . " no ISP Preparatórios.";

// URL oficial do YouTube
$youtube_channel_url = "https://www.youtube.com/@ISPPreparat%C3%B3rios?sub_confirmation=1";

require_once 'includes/header.php';

// Filtros GET para este canal
$search = trim($_GET['q'] ?? '');
$filterDiscipline = !empty($_GET['disciplina']) ? trim($_GET['disciplina']) : '';
$filterBoard = !empty($_GET['banca']) ? trim($_GET['banca']) : '';

// Paginação
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$where = " WHERE v.channel_id = ? AND v.status = 'publicado' AND v.active = 1 AND v.deleted_at IS NULL";
$params = [$canal['id']];

if (!empty($search)) {
    $where .= " AND (v.title LIKE ? OR v.short_description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filterDiscipline)) {
    $where .= " AND d.slug = ?";
    $params[] = $filterDiscipline;
}

if (!empty($filterBoard)) {
    $where .= " AND b.slug = ?";
    $params[] = $filterBoard;
}

// Contagem total
$countSql = "SELECT COUNT(*) FROM free_videos v 
             LEFT JOIN free_disciplines d ON v.discipline_id = d.id 
             LEFT JOIN free_boards b ON v.board_id = b.id 
             $where";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalVideos = $stmtCount->fetchColumn();
$totalPages = ceil($totalVideos / $limit);

// Consulta SQL das Aulas do Canal
$sqlVideos = "SELECT v.*, d.name as discipline_name, t.name as teacher_name, b.name as board_name, ct.name as contest_name,
              (SELECT COUNT(*) FROM free_materials m WHERE m.video_id = v.id AND m.active = 1 AND m.deleted_at IS NULL) as total_materiais
              FROM free_videos v 
              LEFT JOIN free_disciplines d ON v.discipline_id = d.id 
              LEFT JOIN free_teachers t ON v.teacher_id = t.id 
              LEFT JOIN free_boards b ON v.board_id = b.id 
              LEFT JOIN free_contests ct ON v.contest_id = ct.id 
              $where 
              ORDER BY v.order_index ASC, v.published_at DESC 
              LIMIT $limit OFFSET $offset";
$stmtVideos = $pdo->prepare($sqlVideos);
$stmtVideos->execute($params);
$videosList = $stmtVideos->fetchAll();

// Carregar Disciplinas e Bancas do Canal para Filtros
$selectDisciplines = $pdo->prepare("SELECT DISTINCT d.slug, d.name FROM free_videos v JOIN free_disciplines d ON v.discipline_id = d.id WHERE v.channel_id = ? AND v.status = 'publicado' AND v.active = 1 AND v.deleted_at IS NULL ORDER BY d.name ASC");
$selectDisciplines->execute([$canal['id']]);
$selectDisciplinesList = $selectDisciplines->fetchAll();

$selectBoards = $pdo->prepare("SELECT DISTINCT b.slug, b.name FROM free_videos v JOIN free_boards b ON v.board_id = b.id WHERE v.channel_id = ? AND v.status = 'publicado' AND v.active = 1 AND v.deleted_at IS NULL ORDER BY b.name ASC");
$selectBoards->execute([$canal['id']]);
$selectBoardsList = $selectBoards->fetchAll();
?>

<!-- Container principal com margem superior para o menu fixo -->
<div class="container section-padding" style="margin-top: 90px; padding: 2rem 5%;">

    <!-- Breadcrumb Semântico -->
    <nav aria-label="Breadcrumb" style="margin-bottom: 2rem; font-size: 0.85rem; color: rgba(255,255,255,0.75);">
        <a href="/index.php" style="color: rgba(255,255,255,0.75); text-decoration: none;">Início</a>
        <span style="margin: 0 0.4rem;">&gt;</span>
        <a href="/aulas-gratuitas" style="color: rgba(255,255,255,0.75); text-decoration: none;">Aulas Gratuitas</a>
        <span style="margin: 0 0.4rem;">&gt;</span>
        <span style="color: var(--brand-orange); font-weight: 700;"><?= htmlspecialchars($canal['name']) ?></span>
    </nav>

    <!-- Header do Canal -->
    <section class="card" style="background: rgba(10, 15, 50, 0.85); border: 1px solid var(--glass-border); border-radius: 12px; overflow: hidden; margin-bottom: 3rem; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
        <?php if (!empty($canal['cover_image'])): ?>
            <div style="width: 100%; height: 200px; overflow: hidden;">
                <img src="/uploads/<?= htmlspecialchars($canal['cover_image']) ?>" alt="Capa do canal <?= htmlspecialchars($canal['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        <?php else: ?>
            <div style="width: 100%; height: 140px; background: linear-gradient(135deg, rgba(255,0,0,0.2), #03045e); display: flex; align-items: center; justify-content: center;">
                <i class="fab fa-youtube fa-4x" style="color: #FF0000;" aria-hidden="true"></i>
            </div>
        <?php endif; ?>
        
        <div style="padding: 2rem;">
            <div class="canal-header-content" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 1rem;">
                <div>
                    <span style="color: var(--brand-orange); font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">CANAL OFICIAL</span>
                    <h1 style="font-size: 2.2rem; color: #fff; font-weight: 800; margin: 0.2rem 0 0.4rem 0;"><?= htmlspecialchars($canal['name']) ?></h1>
                    <span style="color: rgba(255,255,255,0.7); font-size: 0.9rem; font-weight: 600;">
                        <?= $totalVideos ?> videoaula(s) publicada(s) no site
                    </span>
                </div>
                
                <a href="<?= htmlspecialchars($youtube_channel_url) ?>" target="_blank" rel="noopener noreferrer" style="padding: 0.8rem 1.6rem; background: #FF0000; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 800; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 15px rgba(255,0,0,0.5);">
                    <i class="fab fa-youtube" style="font-size: 1.2rem;"></i> INSCREVER-SE NO YOUTUBE
                </a>
            </div>
            
            <?php if (!empty($canal['description'])): ?>
                <p style="font-size: 1rem; color: rgba(255,255,255,0.9); line-height: 1.6; max-width: 900px; margin: 0;">
                    <?= htmlspecialchars($canal['description']) ?>
                </p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Barra de Busca e Filtros do Canal (Alto Contraste) -->
    <section style="margin-bottom: 2.5rem; background: rgba(10, 15, 50, 0.85); padding: 1.5rem; border-radius: 10px; border: 1px solid var(--glass-border);">
        <form method="GET" class="canal-filter-form" style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div style="flex: 2; min-width: 240px;">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar aulas neste canal..." style="width: 100%; padding: 0.75rem 1rem; background: #06092b; border: 1px solid rgba(255,255,255,0.25); border-radius: 6px; color: #ffffff; font-size: 0.9rem; font-weight: 500;">
            </div>
            
            <?php if (!empty($selectDisciplinesList)): ?>
            <div style="flex: 1; min-width: 160px;">
                <select name="disciplina" style="width: 100%; padding: 0.75rem; background: #06092b; border: 1px solid rgba(255,255,255,0.25); border-radius: 6px; color: #ffffff; font-size: 0.85rem; font-weight: 500;">
                    <option value="" style="background: #06092b; color: #fff;">Todas as disciplinas</option>
                    <?php foreach ($selectDisciplinesList as $sd): ?>
                        <option value="<?= htmlspecialchars($sd['slug']) ?>" <?= $filterDiscipline === $sd['slug'] ? 'selected' : '' ?> style="background: #06092b; color: #fff;"><?= htmlspecialchars($sd['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (!empty($selectBoardsList)): ?>
            <div style="flex: 1; min-width: 160px;">
                <select name="banca" style="width: 100%; padding: 0.75rem; background: #06092b; border: 1px solid rgba(255,255,255,0.25); border-radius: 6px; color: #ffffff; font-size: 0.85rem; font-weight: 500;">
                    <option value="" style="background: #06092b; color: #fff;">Todas as bancas</option>
                    <?php foreach ($selectBoardsList as $sb): ?>
                        <option value="<?= htmlspecialchars($sb['slug']) ?>" <?= $filterBoard === $sb['slug'] ? 'selected' : '' ?> style="background: #06092b; color: #fff;"><?= htmlspecialchars($sb['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <button type="submit" style="padding: 0.75rem 1.6rem; background: var(--brand-orange); color: #fff; border: none; border-radius: 6px; font-weight: 800; cursor: pointer; font-size: 0.85rem;"><i class="fas fa-search"></i> BUSCAR</button>
        </form>
    </section>

    <!-- Grade de Aulas do Canal -->
    <section>
        <?php if (empty($videosList)): ?>
            <div style="background: rgba(255,255,255,0.03); border: 1px dashed var(--glass-border); padding: 3.5rem 1.5rem; text-align: center; border-radius: 10px;">
                <i class="fab fa-youtube fa-4x" style="color: #FF0000; margin-bottom: 1rem; display: block;" aria-hidden="true"></i>
                <h3 style="font-size: 1.3rem; color: #fff; font-weight: 800; margin-bottom: 0.5rem;">Nenhuma aula publicada neste canal no momento.</h3>
                <p style="color: rgba(255,255,255,0.8); max-width: 500px; margin: 0 auto 1.5rem auto;">
                    Novas videoaulas serão adicionadas em breve pelos nossos professores. Inscreva-se no canal do YouTube para receber avisos de novas publicações.
                </p>
                <a href="<?= htmlspecialchars($youtube_channel_url) ?>" target="_blank" rel="noopener noreferrer" style="padding: 0.75rem 1.5rem; background: #FF0000; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 800; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fab fa-youtube"></i> INSCREVER-SE NO YOUTUBE
                </a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr)); gap: 1.5rem;">
                <?php foreach ($videosList as $v): ?>
                    <article style="background: rgba(255,255,255,0.04); border: 1px solid var(--glass-border); border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 8px 25px rgba(0,0,0,0.3);">
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
                                    <span style="background: rgba(3, 4, 94, 0.9); backdrop-filter: blur(4px); color: #fff; font-size: 0.7rem; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700;">
                                        <?= htmlspecialchars($v['discipline_name']) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($v['total_materiais'] > 0): ?>
                                    <span style="background: #ff8000; color: #fff; font-size: 0.7rem; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 800;">
                                        📘 CADERNO EM PDF
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="padding: 1.3rem; display: flex; flex-direction: column; flex-grow: 1;">
                            <h3 style="font-size: 1.05rem; color: #fff; font-weight: 800; line-height: 1.4; margin-bottom: 0.8rem; flex-grow: 1;">
                                <a href="/aulas-gratuitas/<?= htmlspecialchars($canal['slug']) ?>/<?= htmlspecialchars($v['slug']) ?>" style="color: inherit; text-decoration: none;">
                                    <?= htmlspecialchars($v['title']) ?>
                                </a>
                            </h3>

                            <div style="font-size: 0.82rem; color: rgba(255,255,255,0.75); margin-bottom: 1.2rem; display: flex; flex-direction: column; gap: 0.35rem;">
                                <?php if (!empty($v['teacher_name'])): ?>
                                    <div><i class="fas fa-user-circle" style="color: var(--brand-orange);" aria-hidden="true"></i> Profª: <strong style="color: #fff;"><?= htmlspecialchars($v['teacher_name']) ?></strong></div>
                                <?php endif; ?>
                                
                                <?php if (!empty($v['board_name'])): ?>
                                    <div><i class="fas fa-building" style="color: var(--brand-orange);" aria-hidden="true"></i> Banca: <strong style="color: #fff;"><?= htmlspecialchars($v['board_name']) ?></strong></div>
                                <?php endif; ?>
                            </div>

                            <a href="/aulas-gratuitas/<?= htmlspecialchars($canal['slug']) ?>/<?= htmlspecialchars($v['slug']) ?>" aria-label="Assistir aula: <?= htmlspecialchars($v['title']) ?>" style="display: block; text-align: center; padding: 0.75rem 1rem; background: var(--brand-orange); color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.85rem; font-weight: 800;">
                                <i class="fas fa-play" aria-hidden="true"></i> ASSISTIR AULA AGORA
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Paginação -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Paginação do canal" style="display: flex; justify-content: center; gap: 0.4rem; margin-top: 3rem;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="/aulas-gratuitas/<?= htmlspecialchars($canal['slug']) ?>?page=<?= $i ?>" style="padding: 0.6rem 1rem; border-radius: 6px; font-size: 0.9rem; font-weight: 700; text-decoration: none; <?= $page == $i ? 'background: var(--brand-orange); color: #fff;' : 'background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--glass-border);' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

</div>

<?php require_once 'includes/footer.php'; ?>
