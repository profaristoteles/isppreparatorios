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
    echo '<div class="container section-padding" style="padding: 5rem 5%; text-align: center;">';
    echo '<h1 style="font-size: 2rem; color: #fff; margin-bottom: 1rem;">404 - Canal Não Encontrado</h1>';
    echo '<p style="color: var(--text-secondary); margin-bottom: 2rem;">O canal solicitado não existe ou foi removido.</p>';
    echo '<a href="/aulas-gratuitas" class="btn" style="padding: 0.8rem 1.5rem; background: var(--brand-orange); color: #fff; text-decoration: none; border-radius: 4px;">Voltar para Aulas Gratuitas</a>';
    echo '</div>';
    require_once 'includes/footer.php';
    exit;
}

// SEO
$dynamic_title = htmlspecialchars($canal['name']) . " | Aulas Gratuitas";
$dynamic_desc = !empty($canal['description']) ? mb_strimwidth(strip_tags($canal['description']), 0, 160, '...') : "Videoaulas gratuitas do canal " . htmlspecialchars($canal['name']) . " no ISP Preparatórios.";

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

<div class="container section-padding" style="padding: 2rem 5%;">

    <!-- Breadcrumb Semântico -->
    <nav aria-label="Breadcrumb" style="margin-bottom: 2rem; font-size: 0.85rem; color: var(--text-secondary);">
        <a href="/index.php" style="color: var(--text-secondary); text-decoration: none;">Início</a>
        <span style="margin: 0 0.4rem;">&gt;</span>
        <a href="/aulas-gratuitas" style="color: var(--text-secondary); text-decoration: none;">Aulas Gratuitas</a>
        <span style="margin: 0 0.4rem;">&gt;</span>
        <span style="color: var(--brand-orange); font-weight: 600;"><?= htmlspecialchars($canal['name']) ?></span>
    </nav>

    <!-- Header do Canal -->
    <section class="card" style="background: rgba(255, 255, 255, 0.03); border: 1px solid var(--glass-border); border-radius: 8px; overflow: hidden; margin-bottom: 3rem;">
        <?php if (!empty($canal['cover_image'])): ?>
            <div style="width: 100%; height: 200px; overflow: hidden;">
                <img src="/uploads/<?= htmlspecialchars($canal['cover_image']) ?>" alt="Capa do canal <?= htmlspecialchars($canal['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        <?php endif; ?>
        
        <div style="padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <span style="color: var(--brand-orange); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">CANAL DE VIDEOAULAS</span>
                    <h1 style="font-size: 2.2rem; color: #fff; font-weight: 800; margin: 0.2rem 0 0.8rem 0;"><?= htmlspecialchars($canal['name']) ?></h1>
                </div>
                <span style="background: rgba(255,128,0,0.15); color: var(--brand-orange); font-size: 0.9rem; padding: 0.4rem 0.9rem; border-radius: 20px; font-weight: 700; border: 1px solid rgba(255,128,0,0.3);">
                    <?= $totalVideos ?> videoaula(s) publicada(s)
                </span>
            </div>
            
            <?php if (!empty($canal['description'])): ?>
                <p style="font-size: 1rem; color: var(--text-secondary); line-height: 1.6; max-width: 900px; margin-top: 0.5rem;">
                    <?= htmlspecialchars($canal['description']) ?>
                </p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Barra de Busca e Filtros do Canal -->
    <section style="margin-bottom: 2.5rem;">
        <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <div style="flex: 2; min-width: 240px;">
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Buscar aulas neste canal..." style="width: 100%; padding: 0.7rem 1rem; background: rgba(0,0,0,0.4); border: 1px solid var(--glass-border); border-radius: 4px; color: #fff; font-size: 0.9rem;">
            </div>
            
            <?php if (!empty($selectDisciplinesList)): ?>
            <div style="flex: 1; min-width: 160px;">
                <select name="disciplina" style="width: 100%; padding: 0.7rem; background: rgba(0,0,0,0.5); border: 1px solid var(--glass-border); border-radius: 4px; color: #fff; font-size: 0.85rem;">
                    <option value="">Todas as disciplinas</option>
                    <?php foreach ($selectDisciplinesList as $sd): ?>
                        <option value="<?= htmlspecialchars($sd['slug']) ?>" <?= $filterDiscipline === $sd['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($sd['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (!empty($selectBoardsList)): ?>
            <div style="flex: 1; min-width: 160px;">
                <select name="banca" style="width: 100%; padding: 0.7rem; background: rgba(0,0,0,0.5); border: 1px solid var(--glass-border); border-radius: 4px; color: #fff; font-size: 0.85rem;">
                    <option value="">Todas as bancas</option>
                    <?php foreach ($selectBoardsList as $sb): ?>
                        <option value="<?= htmlspecialchars($sb['slug']) ?>" <?= $filterBoard === $sb['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($sb['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <button type="submit" style="padding: 0.7rem 1.5rem; background: var(--brand-orange); color: #fff; border: none; border-radius: 4px; font-weight: 700; cursor: pointer; font-size: 0.85rem;"><i class="fas fa-search"></i> BUSCAR</button>
        </form>
    </section>

    <!-- Grade de Aulas do Canal -->
    <section>
        <?php if (empty($videosList)): ?>
            <div style="background: rgba(255,255,255,0.02); border: 1px dashed var(--glass-border); padding: 3rem 1.5rem; text-align: center; border-radius: 8px;">
                <i class="fas fa-video-slash fa-3x" style="color: var(--brand-orange); margin-bottom: 1rem; display: block;" aria-hidden="true"></i>
                <h3 style="font-size: 1.2rem; color: #fff; margin-bottom: 0.5rem;">Nenhuma aula publicada neste canal no momento.</h3>
                <p style="color: var(--text-secondary);">Novas videoaulas serão adicionadas em breve pelos nossos professores.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
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
                            <h3 style="font-size: 1.05rem; color: #fff; font-weight: 700; line-height: 1.4; margin-bottom: 0.8rem; flex-grow: 1;">
                                <a href="/aulas-gratuitas/<?= htmlspecialchars($canal['slug']) ?>/<?= htmlspecialchars($v['slug']) ?>" style="color: inherit; text-decoration: none;">
                                    <?= htmlspecialchars($v['title']) ?>
                                </a>
                            </h3>

                            <div style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 1.2rem; display: flex; flex-direction: column; gap: 0.3rem;">
                                <?php if (!empty($v['teacher_name'])): ?>
                                    <div><i class="fas fa-user-circle" aria-hidden="true"></i> Profª: <strong><?= htmlspecialchars($v['teacher_name']) ?></strong></div>
                                <?php endif; ?>
                                
                                <?php if (!empty($v['board_name'])): ?>
                                    <div><i class="fas fa-building" aria-hidden="true"></i> Banca: <strong><?= htmlspecialchars($v['board_name']) ?></strong></div>
                                <?php endif; ?>
                            </div>

                            <a href="/aulas-gratuitas/<?= htmlspecialchars($canal['slug']) ?>/<?= htmlspecialchars($v['slug']) ?>" aria-label="Assistir aula: <?= htmlspecialchars($v['title']) ?>" style="display: block; text-align: center; padding: 0.7rem 1rem; background: var(--brand-blue); color: #fff; text-decoration: none; border-radius: 4px; font-size: 0.85rem; font-weight: 700; border: 1px solid rgba(255,255,255,0.1);">
                                <i class="fas fa-play" aria-hidden="true"></i> ASSISTIR AULA
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Paginação -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Paginação do canal" style="display: flex; justify-content: center; gap: 0.4rem; margin-top: 3rem;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="/aulas-gratuitas/<?= htmlspecialchars($canal['slug']) ?>?page=<?= $i ?>" style="padding: 0.5rem 0.9rem; border-radius: 4px; font-size: 0.9rem; font-weight: 600; text-decoration: none; <?= $page == $i ? 'background: var(--brand-orange); color: #fff;' : 'background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--glass-border);' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

</div>

<?php require_once 'includes/footer.php'; ?>
