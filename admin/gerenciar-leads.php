<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';

// Ações em massa via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = $_POST['action'] ?? '';
    if ($action === 'bulk_resync_crm') {
        $selectedIds = $_POST['selected_leads'] ?? [];
        if (!empty($selectedIds) && is_array($selectedIds)) {
            $count = 0;
            $stmtInsert = $pdo->prepare("INSERT INTO integration_queue (integration, entity_type, entity_id, action, payload, status) VALUES ('evocrm', 'lead', ?, 'upsert_contact', ?, 'pending')");
            foreach ($selectedIds as $leadId) {
                $leadId = (int)$leadId;
                $stmtL = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
                $stmtL->execute([$leadId]);
                $lead = $stmtL->fetch();
                if ($lead) {
                    $tags = !empty($lead['tags_cache']) ? array_map('trim', explode(',', $lead['tags_cache'])) : ['lead:site'];
                    $payload = [
                        'lead_id' => $lead['id'],
                        'name' => $lead['name'],
                        'email' => $lead['email'],
                        'phone' => !empty($lead['phone_normalized']) ? $lead['phone_normalized'] : $lead['phone_original'],
                        'tags' => $tags,
                        'campaign_code' => $lead['campaign_code'] ?? 'aulas-gratuitas'
                    ];
                    $stmtInsert->execute([$lead['id'], json_encode($payload)]);
                    $count++;
                }
            }
            $_SESSION['msg'] = "{$count} lead(s) enfileirado(s) com sucesso para sincronização com o CRM / Evolution API!";
        } else {
            $_SESSION['erro'] = "Nenhum lead foi selecionado.";
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Filtros
$search = trim($_GET['q'] ?? '');
$origin = trim($_GET['origin'] ?? 'aulas_gratuitas');
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

if ($origin === 'aulas_gratuitas') {
    // Apenas leads legítimos vinculados a aulas gratuitas
    $where .= " AND (
        l.source LIKE 'aulas-gratuitas%' 
        OR EXISTS (SELECT 1 FROM free_video_events e WHERE e.lead_id = l.id) 
        OR EXISTS (SELECT 1 FROM lead_downloads ld WHERE ld.subject_type = 'lead' AND ld.subject_id = l.id) 
        OR EXISTS (SELECT 1 FROM lead_consents lc WHERE lc.lead_id = l.id AND lc.source LIKE 'aulas-gratuitas%')
    )";
} elseif ($origin === 'reservas') {
    // Apenas leads com reservas de turmas
    $where .= " AND (l.source LIKE 'reserva%' OR EXISTS (SELECT 1 FROM reservations r WHERE r.lead_id = l.id))";
}

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
        <div style="display: grid; grid-template-columns: 2fr 1.3fr 1fr 1fr 1fr; gap: 1rem;">
            <div class="form-group" style="margin: 0;">
                <label>Busca por Nome, E-mail ou Telefone</label>
                <input type="text" name="q" class="form-control" placeholder="Digite para buscar..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Segmento / Origem</label>
                <select name="origin" class="form-control">
                    <option value="aulas_gratuitas" <?= $origin === 'aulas_gratuitas' ? 'selected' : '' ?>>🎯 Aulas Gratuitas (Padrão)</option>
                    <option value="reservas" <?= $origin === 'reservas' ? 'selected' : '' ?>>📝 Leads com Reserva de Turmas</option>
                    <option value="todos" <?= $origin === 'todos' ? 'selected' : '' ?>>🌐 Todos os Leads (Base Unificada)</option>
                </select>
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

<!-- Tabela de Leads com Ações em Massa -->
<div class="card">
    <form method="POST" id="formBulkLeads">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="bulk_resync_crm">

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;">
            <div style="color: #666; font-size: 0.9rem;">
                Total de <strong><?= number_format($totalLeads) ?></strong> lead(s) encontrado(s).
            </div>

            <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-sm btn-success" style="background: #25d366; border-color: #25d366; color: white; font-weight: 600;" onclick="abrirWhatsAppParaSelecionado()">
                    <i class="fab fa-whatsapp"></i> Disparar WhatsApp ao Lead Selecionado
                </button>
                <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Deseja sincronizar os leads selecionados com o CRM / Evolution API?')">
                    <i class="fas fa-sync"></i> Reenviar Selecionados ao CRM / Evolution API
                </button>
                <a href="fila-integracoes.php?queue_all_leads=1&csrf_token=<?= generate_csrf_token() ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Deseja enfileirar todos os leads da base para envio ao CRM?')">
                    <i class="fas fa-users"></i> Enfileirar Todos da Base
                </a>
                <a href="disparo-massa-whatsapp.php" class="btn btn-sm btn-success" style="background: #128c7e; border-color: #128c7e; color: white;">
                    <i class="fab fa-whatsapp"></i> Disparo em Massa
                </a>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 35px; text-align: center;">
                        <input type="checkbox" id="selectAllLeads" onclick="toggleSelectAllLeads(this)" title="Selecionar todos">
                    </th>
                    <th>ID</th>
                    <th>Nome / E-mail</th>
                    <th>WhatsApp / Telefone</th>
                    <th>Origem / UTMs</th>
                    <th>Tags</th>
                    <th>Downloads</th>
                    <th>Data Cadastro</th>
                    <th style="width: 100px; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leadsList)): ?>
                    <tr><td colspan="9" style="text-align: center; color: #888;">Nenhum lead encontrado com os filtros aplicados.</td></tr>
                <?php else: foreach ($leadsList as $lead): 
                    $leadPayloadJson = htmlspecialchars(json_encode([
                        'id' => (int)$lead['id'],
                        'name' => $lead['name'],
                        'phone' => !empty($lead['phone_normalized']) ? $lead['phone_normalized'] : $lead['phone_original'],
                        'email' => $lead['email'],
                        'source' => $lead['source']
                    ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr>
                        <td style="text-align: center;">
                            <input type="checkbox" name="selected_leads[]" value="<?= $lead['id'] ?>" class="lead-checkbox" data-lead='<?= $leadPayloadJson ?>'>
                        </td>
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
                            <?php if (!empty($lead['phone_original']) || !empty($lead['phone_normalized'])): ?>
                                <a href="javascript:void(0)" onclick='abrirModalWhatsApp(<?= $leadPayloadJson ?>)' style="color: #128c7e; text-decoration: none; font-weight: 600;" title="Disparar mensagem no WhatsApp deste lead">
                                    <i class="fab fa-whatsapp" style="color: #25d366;"></i> <?= htmlspecialchars($lead['phone_original']) ?>
                                </a>
                            <?php else: ?>
                                <span style="color: #888;">-</span>
                            <?php endif; ?><br>
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
                        <td style="text-align: center; white-space: nowrap;">
                            <a href="lead-detalhes.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-secondary" title="Ver Detalhes do Lead" style="padding: 0.25rem 0.45rem;">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (!empty($lead['phone_original']) || !empty($lead['phone_normalized'])): ?>
                                <button type="button" class="btn btn-sm btn-success" style="background: #25d366; border-color: #25d366; color: white; padding: 0.25rem 0.5rem;" title="Disparar WhatsApp Direto" onclick='abrirModalWhatsApp(<?= $leadPayloadJson ?>)'>
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </form>
</div>

<script>
function toggleSelectAllLeads(master) {
    document.querySelectorAll('.lead-checkbox').forEach(cb => {
        cb.checked = master.checked;
    });
}

function abrirWhatsAppParaSelecionado() {
    const checked = Array.from(document.querySelectorAll('.lead-checkbox:checked'));
    if (checked.length === 0) {
        alert('Selecione pelo menos um lead na tabela para enviar mensagem no WhatsApp.');
        return;
    }
    try {
        const leadData = JSON.parse(checked[0].getAttribute('data-lead'));
        if (checked.length > 1) {
            if (confirm(`Você selecionou ${checked.length} leads. Deseja disparar mensagem individual para o primeiro selecionado (${leadData.name})?\n\n(Para disparar para todos de uma vez, utilize a opção "Disparo em Massa")`)) {
                abrirModalWhatsApp(leadData);
            }
        } else {
            abrirModalWhatsApp(leadData);
        }
    } catch(e) {
        alert('Erro ao carregar dados do lead selecionado.');
    }
}
</script>

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

<?php 
require_once 'includes/modal_whatsapp_individual.php';
require_once 'includes/footer.php'; 
?>
