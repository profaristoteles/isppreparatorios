<?php
/**
 * Biblioteca Pública de Aulas Gratuitas - ISP Preparatórios
 */
require_once 'db_config.php';
require_once 'includes/aulas_gratuitas_utils.php';

// SEO e Metatags
$dynamic_title = "Aulas Gratuitas";
$dynamic_desc = "Estude gratuitamente com os professores do ISP Preparatórios por meio de videoaulas, resolução de questões e materiais complementares para concursos públicos.";

require_once 'includes/header.php';

// Filtros GET
$search = trim($_GET['q'] ?? '');
$filterChannel = !empty($_GET['canal']) ? trim($_GET['canal']) : '';
$filterDiscipline = !empty($_GET['disciplina']) ? trim($_GET['disciplina']) : '';
$filterTeacher = !empty($_GET['professor']) ? trim($_GET['professor']) : '';
$filterBoard = !empty($_GET['banca']) ? trim($_GET['banca']) : '';
$filterContest = !empty($_GET['concurso']) ? trim($_GET['concurso']) : '';
$filterCategory = !empty($_GET['categoria']) ? trim($_GET['categoria']) : '';

// Paginação
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

// URL do Canal Oficial do YouTube para inscrição
$youtube_channel_url = "https://www.youtube.com/@ISPPreparat%C3%B3rios?sub_confirmation=1";

// 1. Carregar Canais Ativos para a Seção de Canais em Destaque
$stmtCanais = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM free_videos v WHERE v.channel_id = c.id AND v.status = 'publicado' AND v.active = 1 AND v.deleted_at IS NULL) as total_aulas FROM free_channels c WHERE c.active = 1 AND c.deleted_at IS NULL ORDER BY c.order_index ASC, c.id DESC");
$canaisDestaque = $stmtCanais->fetchAll();

// 2. Carregar Opções de Filtro (Taxonomias Ativas)
$selectChannels = $pdo->query("SELECT slug, name FROM free_channels WHERE active = 1 AND deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$selectDisciplines = $pdo->query("SELECT slug, name FROM free_disciplines WHERE active = 1 AND deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$selectTeachers = $pdo->query("SELECT slug, name FROM free_teachers WHERE active = 1 AND deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$selectBoards = $pdo->query("SELECT slug, name FROM free_boards WHERE active = 1 AND deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$selectContests = $pdo->query("SELECT slug, name FROM free_contests WHERE active = 1 AND deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$selectCategories = $pdo->query("SELECT DISTINCT category FROM free_videos WHERE category != '' AND status = 'publicado' AND active = 1 AND deleted_at IS NULL ORDER BY category ASC")->fetchAll();

// 3. Montar Query de Videoaulas Publicadas
$where = " WHERE v.status = 'publicado' AND v.active = 1 AND v.deleted_at IS NULL";
$params = [];

if (!empty($search)) {
    $where .= " AND (v.title LIKE ? OR v.short_description LIKE ? OR d.name LIKE ? OR t.name LIKE ? OR b.name LIKE ? OR ct.name LIKE ? OR c.name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

if (!empty($filterChannel)) {
    $where .= " AND c.slug = ?";
    $params[] = $filterChannel;
}

if (!empty($filterDiscipline)) {
    $where .= " AND d.slug = ?";
    $params[] = $filterDiscipline;
}

if (!empty($filterTeacher)) {
    $where .= " AND t.slug = ?";
    $params[] = $filterTeacher;
}

if (!empty($filterBoard)) {
    $where .= " AND b.slug = ?";
    $params[] = $filterBoard;
}

if (!empty($filterContest)) {
    $where .= " AND ct.slug = ?";
    $params[] = $filterContest;
}

if (!empty($filterCategory)) {
    $where .= " AND v.category = ?";
    $params[] = $filterCategory;
}

// Contagem total para paginação
$countSql = "SELECT COUNT(*) FROM free_videos v 
             JOIN free_channels c ON v.channel_id = c.id 
             LEFT JOIN free_disciplines d ON v.discipline_id = d.id 
             LEFT JOIN free_teachers t ON v.teacher_id = t.id 
             LEFT JOIN free_boards b ON v.board_id = b.id 
             LEFT JOIN free_contests ct ON v.contest_id = ct.id 
             $where";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalVideos = $stmtCount->fetchColumn();
$totalPages = ceil($totalVideos / $limit);

// Consulta SQL das Videoaulas
$sqlVideos = "SELECT v.*, c.name as channel_name, c.slug as channel_slug, 
              d.name as discipline_name, t.name as teacher_name, b.name as board_name, ct.name as contest_name,
              (SELECT COUNT(*) FROM free_materials m WHERE m.video_id = v.id AND m.active = 1 AND m.deleted_at IS NULL) as total_materiais
              FROM free_videos v 
              JOIN free_channels c ON v.channel_id = c.id 
              LEFT JOIN free_disciplines d ON v.discipline_id = d.id 
              LEFT JOIN free_teachers t ON v.teacher_id = t.id 
              LEFT JOIN free_boards b ON v.board_id = b.id 
              LEFT JOIN free_contests ct ON v.contest_id = ct.id 
              $where 
              ORDER BY v.is_featured DESC, v.order_index ASC, v.published_at DESC 
              LIMIT $limit OFFSET $offset";

$stmtVideos = $pdo->prepare($sqlVideos);
$stmtVideos->execute($params);
$videosList = $stmtVideos->fetchAll();
?>

<!-- Hero Section (Com espaçamento superior para não ficar oculto pelo menu fixo) -->
<section class="hero-section" style="margin-top: 90px; padding: 3.5rem 5% 3rem 5%; background: radial-gradient(circle at 50% 30%, rgba(3, 4, 94, 0.95), var(--obsidian-deep)); text-align: center; border-bottom: 1px solid var(--glass-border);">
    <div style="max-width: 900px; margin: 0 auto;">
        <span style="color: var(--brand-orange); font-weight: 800; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem; display: block; margin-bottom: 0.5rem;">CONTEÚDO GRATUITO DE ELITE</span>
        <h1 style="font-size: clamp(2rem, 4vw, 3.2rem); font-weight: 800; color: #fff; margin-bottom: 1rem; line-height: 1.2;">Aulas Gratuitas</h1>
        <p style="font-size: 1.05rem; color: rgba(255,255,255,0.9); line-height: 1.6; margin-bottom: 2rem; max-width: 800px; margin-left: auto; margin-right: auto;">
            Estude com a equipe de professores do ISP Preparatórios. Videoaulas, resolução de questões e cadernos de estudo em PDF totalmente gratuitos.
        </p>
        
        <div class="hero-actions" style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?= htmlspecialchars($youtube_channel_url) ?>" target="_blank" rel="noopener noreferrer" class="btn" style="padding: 0.9rem 1.8rem; font-size: 0.95rem; background: #FF0000; color: #fff; border-color: #FF0000; font-weight: 800; text-decoration: none; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.5rem; box-shadow: 0 4px 15px rgba(255,0,0,0.4);">
                <i class="fab fa-youtube" style="font-size: 1.2rem;" aria-hidden="true"></i> INSCREVER-SE NO YOUTUBE
            </a>
            <a href="#aulas-grid" class="btn-alt" style="padding: 0.9rem 1.8rem; font-size: 0.95rem; border: 1px solid rgba(255,255,255,0.3); color: #fff; text-decoration: none; border-radius: 6px; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-play-circle" aria-hidden="true"></i> EXPLORAR AULAS NO SITE
            </a>
        </div>
    </div>
</section>

<div class="container section-padding" style="padding: 2.5rem 5%;">

    <!-- Banner de Destaque: Inscrição Direta no Canal do YouTube -->
    <div class="yt-banner-cta">
        <div style="flex: 1; min-width: 280px;">
            <span style="background: #FF0000; color: #fff; font-size: 0.75rem; padding: 0.25rem 0.7rem; border-radius: 4px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; display: inline-flex; align-items: center; gap: 0.4rem; margin-bottom: 0.6rem;">
                <i class="fab fa-youtube" style="font-size: 1rem;"></i> CANAL OFICIAL NO YOUTUBE
            </span>
            <h2 style="font-size: 1.5rem; color: #fff; font-weight: 800; margin: 0 0 0.4rem 0;">Quer aprovação? Inscreva-se no nosso YouTube!</h2>
            <p style="font-size: 0.95rem; color: rgba(255,255,255,0.9); line-height: 1.5; margin: 0;">
                Acompanhe nossas transmissões ao vivo, dicas de reta final e resolução de questões diretamente no canal do ISP Preparatórios.
            </p>
        </div>
        <a href="<?= htmlspecialchars($youtube_channel_url) ?>" target="_blank" rel="noopener noreferrer" style="padding: 0.9rem 1.8rem; background: #FF0000; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 800; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 0.6rem; box-shadow: 0 4px 15px rgba(255,0,0,0.5); transition: transform 0.2s ease;">
            <i class="fab fa-youtube" style="font-size: 1.3rem;"></i> INSCREVER-SE AGORA
        </a>
    </div>

    <!-- Barra de Pesquisa e Filtros (Com Alto Contraste para Tema Escuro) -->
    <section class="card" aria-label="Filtros de Aulas" style="background: rgba(10, 15, 50, 0.85); backdrop-filter: blur(12px); border: 1px solid var(--glass-border); padding: 1.8rem; border-radius: 10px; margin-bottom: 3rem; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
        <form method="GET" action="/aulas-gratuitas">
            <div style="display: flex; gap: 1rem; margin-bottom: 1.2rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 250px;">
                    <label for="search-input" style="display: block; font-size: 0.85rem; color: #ffffff; margin-bottom: 0.4rem; font-weight: 700;">Pesquisar Conteúdo</label>
                    <div style="position: relative;">
                        <input type="text" id="search-input" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Pesquisar por título, disciplina, professor ou banca..." style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.5rem; background: #06092b; border: 1px solid rgba(255,255,255,0.25); border-radius: 6px; color: #ffffff; font-size: 0.95rem; font-weight: 500;">
                        <i class="fas fa-search" style="position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); color: var(--brand-orange);" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            <!-- Filtros em Selects -->
            <div class="filters-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 0.8rem; margin-bottom: 1.4rem;">
                <div>
                    <label for="filter-canal" style="display: block; font-size: 0.8rem; color: #ffffff; margin-bottom: 0.3rem; font-weight: 700;">Canal</label>
                    <select id="filter-canal" name="canal" style="width: 100%; padding: 0.65rem; background: #06092b; border: 1px solid rgba(255,255,255,0.25); border-radius: 6px; color: #ffffff; font-size: 0.85rem; font-weight: 500;">
                        <option value="" style="background: #06092b; color: #fff;">Todos os canais</option>
                        <?php foreach ($selectChannels as $c): ?>
                            <option value="<?= htmlspecialchars($c['slug']) ?>" <?= $filterChannel === $c['slug'] ? 'selected' : '' ?> style="background: #06092b; color: #fff;"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="filter-disciplina" style="display: block; font-size: 0.8rem; color: #ffffff; margin-bottom: 0.3rem; font-weight: 700;">Disciplina</label>
                    <select id="filter-disciplina" name="disciplina" style="width: 100%; padding: 0.65rem; background: #06092b; border: 1px solid rgba(255,255,255,0.25); border-radius: 6px; color: #ffffff; font-size: 0.85rem; font-weight: 500;">
                        <option value="" style="background: #06092b; color: #fff;">Todas as disciplinas</option>
                        <?php foreach ($selectDisciplines as $d): ?>
                            <option value="<?= htmlspecialchars($d['slug']) ?>" <?= $filterDiscipline === $d['slug'] ? 'selected' : '' ?> style="background: #06092b; color: #fff;"><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="filter-professor" style="display: block; font-size: 0.8rem; color: #ffffff; margin-bottom: 0.3rem; font-weight: 700;">Professor(a)</label>
                    <select id="filter-professor" name="professor" style="width: 100%; padding: 0.65rem; background: #06092b; border: 1px solid rgba(255,255,255,0.25); border-radius: 6px; color: #ffffff; font-size: 0.85rem; font-weight: 500;">
                        <option value="" style="background: #06092b; color: #fff;">Todos os professores</option>
                        <?php foreach ($selectTeachers as $t): ?>
                            <option value="<?= htmlspecialchars($t['slug']) ?>" <?= $filterTeacher === $t['slug'] ? 'selected' : '' ?> style="background: #06092b; color: #fff;"><?= htmlspecialchars($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="filter-banca" style="display: block; font-size: 0.8rem; color: #ffffff; margin-bottom: 0.3rem; font-weight: 700;">Banca</label>
                    <select id="filter-banca" name="banca" style="width: 100%; padding: 0.65rem; background: #06092b; border: 1px solid rgba(255,255,255,0.25); border-radius: 6px; color: #ffffff; font-size: 0.85rem; font-weight: 500;">
                        <option value="" style="background: #06092b; color: #fff;">Todas as bancas</option>
                        <?php foreach ($selectBoards as $b): ?>
                            <option value="<?= htmlspecialchars($b['slug']) ?>" <?= $filterBoard === $b['slug'] ? 'selected' : '' ?> style="background: #06092b; color: #fff;"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="filter-concurso" style="display: block; font-size: 0.8rem; color: #ffffff; margin-bottom: 0.3rem; font-weight: 700;">Concurso</label>
                    <select id="filter-concurso" name="concurso" style="width: 100%; padding: 0.65rem; background: #06092b; border: 1px solid rgba(255,255,255,0.25); border-radius: 6px; color: #ffffff; font-size: 0.85rem; font-weight: 500;">
                        <option value="" style="background: #06092b; color: #fff;">Todos os concursos</option>
                        <?php foreach ($selectContests as $ct): ?>
                            <option value="<?= htmlspecialchars($ct['slug']) ?>" <?= $filterContest === $ct['slug'] ? 'selected' : '' ?> style="background: #06092b; color: #fff;"><?= htmlspecialchars($ct['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (!empty($selectCategories)): ?>
                <div>
                    <label for="filter-categoria" style="display: block; font-size: 0.8rem; color: #ffffff; margin-bottom: 0.3rem; font-weight: 700;">Categoria</label>
                    <select id="filter-categoria" name="categoria" style="width: 100%; padding: 0.65rem; background: #06092b; border: 1px solid rgba(255,255,255,0.25); border-radius: 6px; color: #ffffff; font-size: 0.85rem; font-weight: 500;">
                        <option value="" style="background: #06092b; color: #fff;">Todas as categorias</option>
                        <?php foreach ($selectCategories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $filterCategory === $cat['category'] ? 'selected' : '' ?> style="background: #06092b; color: #fff;"><?= htmlspecialchars($cat['category']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <div class="filter-actions" style="display: flex; gap: 0.8rem; justify-content: flex-end;">
                <a href="/aulas-gratuitas" style="padding: 0.6rem 1.2rem; background: transparent; border: 1px solid rgba(255,255,255,0.3); color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600;">LIMPAR FILTROS</a>
                <button type="submit" style="padding: 0.6rem 1.6rem; background: var(--brand-orange); color: #fff; border: none; border-radius: 6px; font-weight: 800; cursor: pointer; font-size: 0.85rem;"><i class="fas fa-filter"></i> FILTRAR AULAS</button>
            </div>
        </form>
    </section>

    <!-- Seção de Canais de Aulas -->
    <?php if (!empty($canaisDestaque) && empty($search) && empty($filterChannel) && empty($filterDiscipline) && empty($filterBoard)): ?>
    <section id="canais-grid" style="margin-bottom: 4rem;">
        <div style="margin-bottom: 1.5rem;">
            <span style="color: var(--brand-orange); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">ORGANIZAÇÃO DE CONTEÚDO</span>
            <h2 style="font-size: 1.8rem; color: #fff; font-weight: 800;">Canais de Aulas</h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr)); gap: 1.5rem;">
            <?php foreach ($canaisDestaque as $canal): ?>
            <div style="background: rgba(255,255,255,0.04); border: 1px solid var(--glass-border); border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.3s ease, border-color 0.3s ease; box-shadow: 0 8px 25px rgba(0,0,0,0.3);">
                <?php if (!empty($canal['cover_image'])): ?>
                    <img src="/uploads/<?= htmlspecialchars($canal['cover_image']) ?>" alt="Capa do canal <?= htmlspecialchars($canal['name']) ?>" loading="lazy" style="width: 100%; height: 140px; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 100%; height: 120px; background: linear-gradient(135deg, #03045e, #06088a); display: flex; align-items: center; justify-content: center;">
                        <i class="fab fa-youtube fa-3x" style="color: #FF0000;" aria-hidden="true"></i>
                    </div>
                <?php endif; ?>
                
                <div style="padding: 1.4rem; display: flex; flex-direction: column; flex-grow: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.6rem;">
                        <h3 style="font-size: 1.2rem; color: #fff; font-weight: 800; margin: 0;"><?= htmlspecialchars($canal['name']) ?></h3>
                        <span style="background: rgba(255,128,0,0.2); color: var(--brand-orange); font-size: 0.75rem; padding: 0.25rem 0.6rem; border-radius: 12px; font-weight: 700; flex-shrink: 0; border: 1px solid rgba(255,128,0,0.3);"><?= $canal['total_aulas'] ?> aula(s)</span>
                    </div>
                    
                    <p style="font-size: 0.88rem; color: rgba(255,255,255,0.8); line-height: 1.5; margin-bottom: 1.4rem; flex-grow: 1;">
                        <?= htmlspecialchars(mb_strimwidth($canal['description'] ?? '', 0, 120, '...')) ?>
                    </p>
                    
                    <div style="display: flex; gap: 0.6rem; flex-direction: column;">
                        <a href="<?= htmlspecialchars($youtube_channel_url) ?>" target="_blank" rel="noopener noreferrer" style="display: block; text-align: center; padding: 0.7rem 1rem; background: #FF0000; color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.85rem; font-weight: 800; box-shadow: 0 4px 12px rgba(255,0,0,0.4);">
                            <i class="fab fa-youtube"></i> INSCREVER-SE NO YOUTUBE
                        </a>
                        <a href="/aulas-gratuitas/<?= htmlspecialchars($canal['slug']) ?>" style="display: block; text-align: center; padding: 0.6rem 1rem; border: 1px solid rgba(255,255,255,0.25); color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600;">
                            VER AULAS NO SITE <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Grade de Videoaulas -->
    <section id="aulas-grid">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span style="color: var(--brand-orange); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">BIBLIOTECA DE VIDEOAULAS</span>
                <h2 style="font-size: 1.8rem; color: #fff; font-weight: 800;">Todas as Aulas Publicadas</h2>
            </div>
            <div style="font-size: 0.9rem; color: rgba(255,255,255,0.8);">
                Exibindo <strong><?= count($videosList) ?></strong> de <strong><?= $totalVideos ?></strong> aula(s)
            </div>
        </div>

        <?php if (empty($videosList)): ?>
            <div style="background: rgba(255,255,255,0.03); border: 1px dashed var(--glass-border); padding: 3.5rem 1.5rem; text-align: center; border-radius: 10px;">
                <i class="fab fa-youtube fa-4x" style="color: #FF0000; margin-bottom: 1rem; display: block;" aria-hidden="true"></i>
                <h3 style="font-size: 1.4rem; color: #fff; font-weight: 800; margin-bottom: 0.5rem;">Nenhuma aula encontrada nesta busca</h3>
                <p style="color: rgba(255,255,255,0.8); max-width: 550px; margin: 0 auto 1.8rem auto; line-height: 1.6;">
                    Não encontramos nenhuma videoaula com os filtros selecionados. Inscreva-se no nosso canal oficial do YouTube para não perder nenhuma novidade ou limpe os filtros.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="<?= htmlspecialchars($youtube_channel_url) ?>" target="_blank" rel="noopener noreferrer" style="padding: 0.75rem 1.5rem; background: #FF0000; color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.9rem; font-weight: 800; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fab fa-youtube"></i> INSCREVER-SE NO YOUTUBE
                    </a>
                    <a href="/aulas-gratuitas" class="btn" style="padding: 0.75rem 1.5rem; background: var(--brand-orange); color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.9rem; font-weight: 700;">
                        VER TODAS AS AULAS
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr)); gap: 1.5rem;">
                <?php foreach ($videosList as $v): ?>
                    <article style="background: rgba(255,255,255,0.04); border: 1px solid var(--glass-border); border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.3s ease, box-shadow 0.3s ease; box-shadow: 0 8px 25px rgba(0,0,0,0.3);">
                        <!-- Thumbnail Container -->
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
                            
                            <!-- Badges na Thumbnail -->
                            <div style="position: absolute; top: 10px; left: 10px; right: 10px; display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; pointer-events: none;">
                                <?php if (!empty($v['discipline_name'])): ?>
                                    <span style="background: rgba(3, 4, 94, 0.9); backdrop-filter: blur(4px); color: #fff; font-size: 0.7rem; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 700; border: 1px solid rgba(255,255,255,0.2);">
                                        <?= htmlspecialchars($v['discipline_name']) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($v['total_materiais'] > 0): ?>
                                    <span style="background: #ff8000; color: #fff; font-size: 0.7rem; padding: 0.25rem 0.6rem; border-radius: 4px; font-weight: 800; box-shadow: 0 2px 6px rgba(0,0,0,0.4);">
                                        📘 CADERNO EM PDF
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Conteúdo do Card -->
                        <div style="padding: 1.3rem; display: flex; flex-direction: column; flex-grow: 1;">
                            <div style="font-size: 0.75rem; color: var(--brand-orange); font-weight: 700; margin-bottom: 0.4rem; text-transform: uppercase;">
                                <?= htmlspecialchars($v['channel_name']) ?>
                            </div>
                            
                            <h3 style="font-size: 1.1rem; color: #fff; font-weight: 800; line-height: 1.4; margin-bottom: 0.8rem; flex-grow: 1;">
                                <a href="/aulas-gratuitas/<?= htmlspecialchars($v['channel_slug']) ?>/<?= htmlspecialchars($v['slug']) ?>" style="color: inherit; text-decoration: none;">
                                    <?= htmlspecialchars($v['title']) ?>
                                </a>
                            </h3>

                            <!-- Taxonomias (Professor / Banca / Concurso) -->
                            <div style="font-size: 0.82rem; color: rgba(255,255,255,0.75); margin-bottom: 1.3rem; display: flex; flex-direction: column; gap: 0.35rem;">
                                <?php if (!empty($v['teacher_name'])): ?>
                                    <div><i class="fas fa-user-circle" style="color: var(--brand-orange);" aria-hidden="true"></i> Profª: <strong style="color: #fff;"><?= htmlspecialchars($v['teacher_name']) ?></strong></div>
                                <?php endif; ?>
                                
                                <?php if (!empty($v['board_name'])): ?>
                                    <div><i class="fas fa-building" style="color: var(--brand-orange);" aria-hidden="true"></i> Banca: <strong style="color: #fff;"><?= htmlspecialchars($v['board_name']) ?></strong></div>
                                <?php endif; ?>

                                <?php if (!empty($v['contest_name'])): ?>
                                    <div><i class="fas fa-award" style="color: var(--brand-orange);" aria-hidden="true"></i> Concurso: <strong style="color: #fff;"><?= htmlspecialchars($v['contest_name']) ?></strong></div>
                                <?php endif; ?>
                            </div>

                            <!-- Botões de Ação -->
                            <div style="display: flex; gap: 0.5rem; flex-direction: column;">
                                <a href="/aulas-gratuitas/<?= htmlspecialchars($v['channel_slug']) ?>/<?= htmlspecialchars($v['slug']) ?>" aria-label="Assistir aula: <?= htmlspecialchars($v['title']) ?>" style="display: block; text-align: center; padding: 0.75rem 1rem; background: var(--brand-orange); color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.85rem; font-weight: 800; transition: background 0.3s ease;">
                                    <i class="fas fa-play" aria-hidden="true"></i> ASSISTIR AULA AGORA
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Paginação -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Paginação da biblioteca" style="display: flex; justify-content: center; gap: 0.4rem; margin-top: 3rem;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php
                            $getParams = $_GET;
                            $getParams['page'] = $i;
                            $pageUrl = '/aulas-gratuitas?' . http_build_query($getParams);
                        ?>
                        <a href="<?= $pageUrl ?>" style="padding: 0.6rem 1rem; border-radius: 6px; font-size: 0.9rem; font-weight: 700; text-decoration: none; <?= $page == $i ? 'background: var(--brand-orange); color: #fff;' : 'background: rgba(255,255,255,0.05); color: #fff; border: 1px solid var(--glass-border);' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

</div>

<?php require_once 'includes/footer.php'; ?>
