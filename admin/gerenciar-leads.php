<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';

// Filtros
$search = trim($_GET['q'] ?? '');
$channel_id = !empty($_GET['channel_id']) ? (int)$_GET['channel_id'] : 0;
$video_id = !empty($_GET['video_id']) ? (int)$_GET['video_id'] : 0;
$teacher_id = !empty($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;
$discipline_id = !empty($_GET['discipline_id']) ? (int)$_GET['discipline_id'] : 0;
$board_id = !empty($_GET['board_id']) ? (int)$_GET['board_id'] : 0;
$contest_id = !empty($_GET['contest_id']) ? (int)$_GET['contest_id'] : 0;
$tag_filter = trim($_GET['tag'] ?? '');
$date_start = !empty($_GET['date_start']) ? $_GET['date_start'] : '';
$date_end = !empty($_GET['date_end']) ? $_GET['date_end'] : '';

// Paginação
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = " WHERE 1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (l.name LIKE ? OR l.email LIKE ? OR l.phone_original LIKE ? OR l.phone_normalized LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($date_start)) {
    $where .= " AND l.created_at >= ?";
    $params[] = $date_start . " 00:00:00";
}

if (!empty($date_end)) {
    $where .= " AND l.created_at <= ?";
    $params[] = $date_end . " 23:59:59";
}

if (!empty($tag_filter)) {
    $where .= " AND l.tags_cache LIKE ?";
    $params[] = "%$tag_filter%";
}

if ($channel_id || $video_id || $teacher_id || $discipline_id || $board_id || $contest_id) {
    $where .= " AND l.id IN (SELECT DISTINCT e.lead_id FROM free_video_events e JOIN free_videos v ON e.video_id = v.id WHERE e.lead_id IS NOT NULL";
    if ($video_id) { $where .= " AND v.id = ?"; $params[] = $video_id; }
    if ($channel_id) { $where .= " AND v.channel_id = ?"; $params[] = $channel_id; }
    if ($teacher_id) { $where .= " AND v.teacher_id = ?"; $params[] = $teacher_id; }
    if ($discipline_id) { $where .= " AND v.discipline_id = ?"; $params[] = $discipline_id; }
    if ($board_id) { $where .= " AND v.board_id = ?"; $params[] = $board_id; }
    if ($contest_id) { $where .= " AND v.contest_id = ?"; $params[] = $contest_id; }
    $where .= ")";
}

// Contagem total para paginação
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM leads l $where");
$stmtCount->execute($params);
$totalLeads = $stmtCount->fetchColumn();
$totalPages = ceil($totalLeads / $limit);

// Consulta paginada
$sql = "SELECT l.*, 
        (SELECT COUNT(*) FROM lead_tags lt WHERE lt.lead_id = l.id) as tag_count,
        (SELECT COUNT(*) FROM lead_downloads ld WHERE ld.subject_type = 'lead' AND ld.subject_id = l.id) as dl_count,
        (SELECT COUNT(*) FROM reservations r WHERE r.lead_id = l.id) as res_count
        FROM leads l $where ORDER BY l.id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leadsList = $stmt->fetchAll();

// Selects para filtros
$channelsSelect = $pdo->query("SELECT id, name FROM free_channels WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$videosSelect = $pdo->query("SELECT id, title FROM free_videos WHERE deleted_at IS NULL ORDER BY title ASC")->fetchAll();
$teachersSelect = $pdo->query("SELECT id, name FROM free_teachers WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$disciplinesSelect = $pdo->query("SELECT id, name FROM free_disciplines WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$boardsSelect = $pdo->query("SELECT id, name FROM free_boards WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$contestsSelect = $pdo->query("SELECT id, name FROM free_contests WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();

// Montar query string para exportação e paginação
$queryString = http_build_query($_GET);

require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h2><i class="fas fa-users"></i> Gestão de Leads das Aulas Gratuitas</h2>
    <a href="export_leads_csv.php?<?= $queryString ?>" class="btn btn-success"><i class="fas fa-file-excel"></i> Exportar CSV para Mautic</a>
</div>

<!-- Filtros Avançados -->
<div class="card">
    <form method="GET" style="display: flex; flex-direction: column; gap: 1rem;">
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 1rem;">
            <div class="form-group" style="margin: 0;">
                <label>Busca por Nome, E-mail ou Telefone</label>
                <input type="text" name="q" class="form-control" placeholder="Digite para buscar..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Tag Contém</label>
                <input type="text" name="tag" class="form-control" placeholder="Ex: banca:instituto-jk" value="<?= htmlspecialchars($tag_filter) ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Data Inicial</label>
                <input type="date" name="date_start" class="form-control" value="<?= htmlspecialchars($date_start) ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Data Final</label>
                <input type="date" name="date_end" class="form-control" value="<?= htmlspecialchars($date_end) ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.5rem;">
            <select name="channel_id" class="form-control">
                <option value="">Todos os canais</option>
                <?php foreach ($channelsSelect as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $channel_id == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="video_id" class="form-control">
                <option value="">Todas as videoaulas</option>
                <?php foreach ($videosSelect as $v): ?>
                    <option value="<?= $v['id'] ?>" <?= $video_id == $v['id'] ? 'selected' : '' ?>><?= htmlspecialchars($v['title']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="board_id" class="form-control">
                <option value="">Todas as bancas</option>
                <?php foreach ($boardsSelect as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $board_id == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="discipline_id" class="form-control">
                <option value="">Todas as disciplinas</option>
                <?php foreach ($disciplinesSelect as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $discipline_id == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="teacher_id" class="form-control">
                <option value="">Todos os professores</option>
                <?php foreach ($teachersSelect as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $teacher_id == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="contest_id" class="form-control">
                <option value="">Todos os concursos</option>
                <?php foreach ($contestsSelect as $ct): ?>
                    <option value="<?= $ct['id'] ?>" <?= $contest_id == $ct['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ct['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
            <a href="gerenciar-leads.php" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i> Limpar Filtros</a>
            <button type="submit" class="btn btn-sm"><i class="fas fa-filter"></i> Aplicar Filtros</button>
        </div>
    </form>
</div>

<!-- Tabela de Leads -->
<div class="card">
    <div style="margin-bottom: 0.8rem; color: #666; font-size: 0.9rem;">
        Total de <strong><?= number_format($totalLeads) ?></strong> lead(s) encontrado(s).
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome / E-mail</th>
                <th>WhatsApp / Telefone</th>
                <th>Origem / UTMs</th>
                <th>Tags</th>
                <th>Downloads</th>
                <th>Data Cadastro</th>
                <th style="width: 80px;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($leadsList)): ?>
                <tr><td colspan="8" style="text-align: center; color: #888;">Nenhum lead encontrado com os filtros aplicados.</td></tr>
            <?php else: foreach ($leadsList as $lead): ?>
                <tr>
                    <td>#<?= $lead['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($lead['name']) ?></strong><br>
                        <small style="color: #666;"><?= htmlspecialchars($lead['email']) ?></small>
                        <?php if (!empty($lead['res_count'])): ?>
                            <div style="margin-top: 3px;">
                                <span class="badge" style="background: #fff3cd; color: #856404; border: 1px solid #ffeeba; font-size: 0.72rem; padding: 0.2rem 0.5rem;">
                                    <i class="fas fa-bookmark"></i> <?= $lead['res_count'] ?> reserva(s)
                                </span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <i class="fab fa-whatsapp text-success"></i> <?= htmlspecialchars($lead['phone_original']) ?><br>
                        <small style="color: #888;"><?= htmlspecialchars($lead['phone_normalized'] ?: '-') ?></small>
                    </td>
                    <td>
                        <span class="badge badge-info"><?= htmlspecialchars($lead['source']) ?></span><br>
                        <?php if ($lead['utm_source']): ?>
                            <small style="color: #666;"><?= htmlspecialchars($lead['utm_source']) ?> / <?= htmlspecialchars($lead['utm_medium']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-secondary"><?= $lead['tag_count'] ?> tag(s)</span>
                    </td>
                    <td>
                        <span class="badge badge-success"><?= $lead['dl_count'] ?> PDF(s)</span>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($lead['created_at'])) ?></td>
                    <td>
                        <a href="lead-detalhes.php?id=<?= $lead['id'] ?>" class="btn btn-sm" title="Ver Detalhes"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- Paginação -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php
                    $paramsGET = $_GET;
                    $paramsGET['page'] = $i;
                    $url = 'gerenciar-leads.php?' . http_build_query($paramsGET);
                ?>
                <a href="<?= $url ?>" class="<?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
