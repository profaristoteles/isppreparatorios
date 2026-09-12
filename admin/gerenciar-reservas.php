<?php
/**
 * Gerenciamento e Listagem de Reservas / Lista de Interesse
 * ISP Preparatórios
 */

require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';
require_once '../includes/reservation_service.php';

$statusLabels = ReservationService::getReservationStatusLabels();
$modalityLabels = ReservationService::getModalityLabels();

// Processar Ações em Massa ou Ações Individuais via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $action = $_POST['action'] ?? '';
    $adminId = $_SESSION['admin_id'] ?? null;
    $adminName = $_SESSION['admin_name'] ?? 'Administrador';

    if ($action === 'update_single_status') {
        $reservationId = (int)($_POST['reservation_id'] ?? 0);
        $newStatus = $_POST['new_status'] ?? '';
        $res = ReservationService::updateReservationStatus($pdo, $reservationId, $newStatus, $adminId, $adminName);
        if ($res['success']) {
            $_SESSION['msg'] = $res['message'];
        } else {
            $_SESSION['erro'] = $res['message'];
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    if ($action === 'resync_crm') {
        $reservationId = (int)($_POST['reservation_id'] ?? 0);
        $res = ReservationService::reenqueueEvoCRM($pdo, $reservationId);
        if ($res['success']) {
            $_SESSION['msg'] = $res['message'];
        } else {
            $_SESSION['erro'] = $res['message'];
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    // Ações em massa
    if ($action === 'bulk_action') {
        $selectedIds = $_POST['selected_reservations'] ?? [];
        $bulkOperation = $_POST['bulk_operation'] ?? '';

        if (empty($selectedIds) || !is_array($selectedIds)) {
            $_SESSION['erro'] = "Nenhuma reserva foi selecionada.";
        } else {
            $count = 0;
            if (str_starts_with($bulkOperation, 'status_')) {
                $newStatus = substr($bulkOperation, 7);
                foreach ($selectedIds as $rId) {
                    ReservationService::updateReservationStatus($pdo, (int)$rId, $newStatus, $adminId, $adminName);
                    $count++;
                }
                $_SESSION['msg'] = "Status de {$count} reserva(s) atualizado para '" . ($statusLabels[$newStatus] ?? $newStatus) . "'.";
            } elseif ($bulkOperation === 'resync_crm') {
                foreach ($selectedIds as $rId) {
                    ReservationService::reenqueueEvoCRM($pdo, (int)$rId);
                    $count++;
                }
                $_SESSION['msg'] = "{$count} reserva(s) reenfileirada(s) com sucesso para o EvoCRM.";
            }
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Filtros
$search = trim($_GET['q'] ?? '');
$campaign_id = !empty($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0;
$modality_filter = trim($_GET['modality'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$crm_status_filter = trim($_GET['crm_status'] ?? '');
$date_start = trim($_GET['date_start'] ?? '');
$date_end = trim($_GET['date_end'] ?? '');
$city_filter = trim($_GET['city'] ?? '');
$waiting_filter = trim($_GET['is_waiting_list'] ?? '');
$follow_up_filter = trim($_GET['follow_up_filter'] ?? '');

// Paginação
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

$where = " WHERE 1=1";
$params = [];

if (!empty($search)) {
    $where .= " AND (l.name LIKE ? OR l.email LIKE ? OR l.phone_original LIKE ? OR l.phone_normalized LIKE ? OR r.city LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($campaign_id > 0) {
    $where .= " AND r.campaign_id = ?";
    $params[] = $campaign_id;
}

if (!empty($modality_filter)) {
    $where .= " AND r.preferred_modality = ?";
    $params[] = $modality_filter;
}

if (!empty($status_filter)) {
    $where .= " AND r.status = ?";
    $params[] = $status_filter;
}

if ($waiting_filter !== '') {
    $where .= " AND r.is_waiting_list = ?";
    $params[] = (int)$waiting_filter;
}

if (!empty($city_filter)) {
    $where .= " AND r.city LIKE ?";
    $params[] = "%$city_filter%";
}

if (!empty($date_start)) {
    $where .= " AND r.created_at >= ?";
    $params[] = $date_start . " 00:00:00";
}

if (!empty($date_end)) {
    $where .= " AND r.created_at <= ?";
    $params[] = $date_end . " 23:59:59";
}

// Filtro por próximo acompanhamento
if (!empty($follow_up_filter)) {
    if ($follow_up_filter === 'vencido') {
        $where .= " AND r.next_follow_up_at IS NOT NULL AND r.next_follow_up_at < NOW() AND r.status NOT IN ('matriculado', 'cancelado', 'sem_interesse')";
    } elseif ($follow_up_filter === 'hoje') {
        $where .= " AND DATE(r.next_follow_up_at) = CURDATE()";
    } elseif ($follow_up_filter === '7dias') {
        $where .= " AND r.next_follow_up_at >= NOW() AND r.next_follow_up_at <= NOW() + INTERVAL 7 DAY";
    } elseif ($follow_up_filter === 'sem_acompanhamento') {
        $where .= " AND r.next_follow_up_at IS NULL";
    }
}

// Filtro por status do CRM (subconsulta da integration_queue)
if (!empty($crm_status_filter)) {
    if ($crm_status_filter === 'none') {
        $where .= " AND NOT EXISTS (SELECT 1 FROM integration_queue q WHERE q.entity_type = 'reservation' AND q.entity_id = r.id)";
    } else {
        $where .= " AND EXISTS (SELECT 1 FROM integration_queue q WHERE q.entity_type = 'reservation' AND q.entity_id = r.id AND q.status = ?)";
        $params[] = $crm_status_filter;
    }
}

// Contagem total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM reservations r JOIN leads l ON r.lead_id = l.id JOIN reservation_campaigns c ON r.campaign_id = c.id $where");
$stmtCount->execute($params);
$totalReservas = (int)$stmtCount->fetchColumn();
$totalPages = ceil($totalReservas / $limit);

// Consulta paginada com status do CRM, notas e último contato
$sql = "SELECT r.*, l.name as lead_name, l.email as lead_email, l.phone_original as lead_phone, l.phone_normalized as lead_phone_normalized,
        c.title as campaign_title, c.slug as campaign_slug,
        (SELECT MAX(h.created_at) FROM reservation_history h WHERE h.reservation_id = r.id AND h.action_type = 'contato_comercial') as last_contact_at,
        (SELECT q.status FROM integration_queue q WHERE q.entity_type = 'reservation' AND q.entity_id = r.id ORDER BY q.id DESC LIMIT 1) as crm_status,
        (SELECT COUNT(*) FROM reservation_notes n WHERE n.reservation_id = r.id) as note_count
        FROM reservations r 
        JOIN leads l ON r.lead_id = l.id 
        JOIN reservation_campaigns c ON r.campaign_id = c.id 
        $where 
        ORDER BY r.id DESC 
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

// Campanhas para filtro
$campaignsSelect = $pdo->query("SELECT id, title FROM reservation_campaigns ORDER BY id DESC")->fetchAll();

$queryString = http_build_query($_GET);

require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2><i class="fas fa-clipboard-list"></i> Gestão de Reservas / Interessados (<?= $totalReservas ?>)</h2>
        <p style="color: #666; margin: 0; font-size: 0.9rem;">Acompanhe o funil de interessados, entre em contato via WhatsApp e gerencie conversões.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="export_reservas_csv.php?<?= $queryString ?>" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Exportar CSV
        </a>
        <a href="reservas-dashboard.php" class="btn btn-secondary">
            <i class="fas fa-chart-pie"></i> Ver Dashboard
        </a>
    </div>
</div>

<!-- Filtros Avançados -->
<div class="card" style="margin-bottom: 1.5rem;">
    <form method="GET" style="display: flex; flex-direction: column; gap: 1rem;">
        <div style="display: grid; grid-template-columns: 2fr 2fr 1fr 1fr; gap: 1rem;">
            <div class="form-group" style="margin: 0;">
                <label>Busca por Nome, E-mail ou WhatsApp</label>
                <input type="text" name="q" class="form-control" placeholder="Digite para buscar..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Campanha / Turma</label>
                <select name="campaign_id" class="form-control">
                    <option value="">Todas as Campanhas</option>
                    <?php foreach ($campaignsSelect as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $campaign_id === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Modalidade</label>
                <select name="modality" class="form-control">
                    <option value="">Todas</option>
                    <option value="presencial" <?= $modality_filter === 'presencial' ? 'selected' : '' ?>>Presencial</option>
                    <option value="online" <?= $modality_filter === 'online' ? 'selected' : '' ?>>Online</option>
                    <option value="ambas" <?= $modality_filter === 'ambas' ? 'selected' : '' ?>>Ambas</option>
                </select>
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Status Comercial</label>
                <select name="status" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($statusLabels as $sVal => $sLbl): ?>
                        <option value="<?= $sVal ?>" <?= $status_filter === $sVal ? 'selected' : '' ?>><?= $sLbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr auto; gap: 1rem; align-items: flex-end;">
            <div class="form-group" style="margin: 0;">
                <label>Data Início</label>
                <input type="date" name="date_start" class="form-control" value="<?= htmlspecialchars($date_start) ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Data Fim</label>
                <input type="date" name="date_end" class="form-control" value="<?= htmlspecialchars($date_end) ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Próximo Contato</label>
                <select name="follow_up_filter" class="form-control">
                    <option value="">Todos</option>
                    <option value="vencido" <?= $follow_up_filter === 'vencido' ? 'selected' : '' ?>>Contato vencido</option>
                    <option value="hoje" <?= $follow_up_filter === 'hoje' ? 'selected' : '' ?>>Hoje</option>
                    <option value="7dias" <?= $follow_up_filter === '7dias' ? 'selected' : '' ?>>Próximos 7 dias</option>
                    <option value="sem_acompanhamento" <?= $follow_up_filter === 'sem_acompanhamento' ? 'selected' : '' ?>>Sem acompanhamento</option>
                </select>
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Sincronização CRM</label>
                <select name="crm_status" class="form-control">
                    <option value="">Todos</option>
                    <option value="synced" <?= $crm_status_filter === 'synced' ? 'selected' : '' ?>>Sincronizado</option>
                    <option value="pending" <?= $crm_status_filter === 'pending' ? 'selected' : '' ?>>Pendente / Na Fila</option>
                    <option value="error" <?= $crm_status_filter === 'error' ? 'selected' : '' ?>>Erro</option>
                    <option value="none" <?= $crm_status_filter === 'none' ? 'selected' : '' ?>>Não Enfileirado</option>
                </select>
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Fila de Espera</label>
                <select name="is_waiting_list" class="form-control">
                    <option value="">Todas</option>
                    <option value="0" <?= $waiting_filter === '0' ? 'selected' : '' ?>>Vagas Regulares</option>
                    <option value="1" <?= $waiting_filter === '1' ? 'selected' : '' ?>>Lista de Espera</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="gerenciar-reservas.php" class="btn btn-secondary" title="Limpar Filtros"><i class="fas fa-undo"></i></a>
            </div>
        </div>
    </form>
</div>

<!-- Formulário para Ações em Massa e Tabela -->
<form method="POST" id="formBulkActions">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="bulk_action">

    <div style="background: #fff; padding: 0.8rem 1rem; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.8rem;">
        <div style="display: flex; align-items: center; gap: 0.8rem;">
            <span style="font-weight: 600; font-size: 0.9rem; color: #03045e;"><i class="fas fa-tasks"></i> Ações em Massa:</span>
            <select name="bulk_operation" class="form-control" style="width: auto; padding: 0.4rem 0.8rem;">
                <option value="">Selecione uma ação...</option>
                <optgroup label="Alterar Status Comercial">
                    <?php foreach ($statusLabels as $k => $lbl): ?>
                        <option value="status_<?= $k ?>">Mudar para: <?= $lbl ?></option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Integrações">
                    <option value="resync_crm">Reenviar Selecionadas ao EvoCRM</option>
                </optgroup>
            </select>
            <button type="submit" class="btn btn-sm" onclick="return confirm('Deseja aplicar esta ação nas reservas selecionadas?');">
                Aplicar às Selecionadas
            </button>
        </div>
        <div style="font-size: 0.85rem; color: #666;">
            Exibindo <strong><?= count($reservations) ?></strong> de <strong><?= $totalReservas ?></strong> registro(s)
        </div>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="overflow-x: auto;">
            <table class="table" style="margin: 0;">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)" style="cursor: pointer;">
                        </th>
                        <th style="width: 50px;">ID</th>
                        <th>Interessado (Lead)</th>
                        <th>Campanha</th>
                        <th>Modalidade</th>
                        <th>Cidade/UF</th>
                        <th>Data</th>
                        <th>Último Contato</th>
                        <th>Próximo Contato</th>
                        <th>Status Comercial</th>
                        <th>CRM</th>
                        <th style="text-align: right; width: 130px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations)): ?>
                        <tr><td colspan="12" style="text-align: center; color: #888; padding: 2.5rem;">Nenhuma reserva encontrada com os filtros selecionados.</td></tr>
                    <?php else: foreach ($reservations as $r): 
                        $whatsappLink = ReservationService::generateWhatsAppLink($r['lead_phone'], $r['lead_name'], $r['campaign_title'], $r['preferred_modality']);
                        
                        $modBadgeClass = 'badge-secondary';
                        if ($r['preferred_modality'] === 'presencial') $modBadgeClass = 'badge-info';
                        elseif ($r['preferred_modality'] === 'online') $modBadgeClass = 'badge-warning';
                        elseif ($r['preferred_modality'] === 'ambas') $modBadgeClass = 'badge-success';

                        $statusBadgeClass = 'badge-secondary';
                        if ($r['status'] === 'nova') $statusBadgeClass = 'badge-info';
                        elseif ($r['status'] === 'contatado' || $r['status'] === 'interessado') $statusBadgeClass = 'badge-warning';
                        elseif ($r['status'] === 'matriculado') $statusBadgeClass = 'badge-success';
                        elseif ($r['status'] === 'aguardando_matricula') $statusBadgeClass = 'badge-primary';
                        elseif ($r['status'] === 'nao_respondeu' || $r['status'] === 'sem_interesse' || $r['status'] === 'cancelado') $statusBadgeClass = 'badge-danger';

                        $lastContact = ReservationService::formatContactDate($r['last_contact_at']);
                        $nextFollowUp = !empty($r['next_follow_up_at']) ? date('d/m/Y H:i', strtotime($r['next_follow_up_at'])) : '-';
                        $isOverdue = (!empty($r['next_follow_up_at']) && strtotime($r['next_follow_up_at']) < time() && !in_array($r['status'], ['matriculado', 'cancelado', 'sem_interesse']));
                    ?>
                        <tr>
                            <td style="text-align: center;">
                                <input type="checkbox" name="selected_reservations[]" value="<?= $r['id'] ?>" class="resCheckbox" style="cursor: pointer;">
                            </td>
                            <td><strong>#<?= $r['id'] ?></strong></td>
                            <td>
                                <div>
                                    <a href="reserva-detalhes.php?id=<?= $r['id'] ?>" style="font-weight: 700; color: #03045e; text-decoration: none;">
                                        <?= htmlspecialchars($r['lead_name']) ?>
                                    </a>
                                </div>
                                <div style="font-size: 0.8rem; color: #555; margin-top: 3px; display: flex; align-items: center; gap: 0.6rem;">
                                    <a href="<?= htmlspecialchars($whatsappLink) ?>" target="_blank" style="color: #25d366; text-decoration: none; font-weight: 600;" title="Conversar no WhatsApp">
                                        <i class="fab fa-whatsapp"></i> <?= htmlspecialchars($r['lead_phone']) ?>
                                    </a>
                                    <span style="color: #ccc;">|</span>
                                    <a href="mailto:<?= htmlspecialchars($r['lead_email']) ?>" style="color: #666; text-decoration: none;" title="Enviar E-mail">
                                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($r['lead_email']) ?>
                                    </a>
                                </div>
                                <?php if ($r['note_count'] > 0): ?>
                                    <div style="font-size: 0.75rem; color: #ff8000; margin-top: 2px;">
                                        <i class="fas fa-comment-alt"></i> <?= $r['note_count'] ?> nota(s) interna(s)
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($r['campaign_title']) ?></strong>
                                <?php if ($r['is_waiting_list']): ?>
                                    <span class="badge badge-warning" style="display: block; font-size: 0.7rem; margin-top: 2px;">Lista de Espera</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $modBadgeClass ?>"><?= htmlspecialchars($modalityLabels[$r['preferred_modality']] ?? $r['preferred_modality']) ?></span>
                            </td>
                            <td style="font-size: 0.85rem;">
                                <?= htmlspecialchars($r['city'] ?: '-') ?><?= !empty($r['state']) ? '/' . htmlspecialchars($r['state']) : '' ?>
                            </td>
                            <td style="font-size: 0.8rem; color: #666;">
                                <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                            </td>
                            <td style="font-size: 0.8rem; color: <?= $lastContact === 'Nunca' ? '#888' : '#28a745' ?>; font-weight: <?= $lastContact === 'Nunca' ? 'normal' : '600' ?>;">
                                <?= htmlspecialchars($lastContact) ?>
                            </td>
                            <td style="font-size: 0.8rem;">
                                <?php if ($isOverdue): ?>
                                    <span style="color: #dc3545; font-weight: 700;" title="Acompanhamento atrasado!">
                                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($nextFollowUp) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: <?= $nextFollowUp === '-' ? '#888' : '#03045e' ?>;">
                                        <?= htmlspecialchars($nextFollowUp) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- Alteração Rápida de Status -->
                                <select onchange="changeStatus(<?= $r['id'] ?>, this.value)" class="form-control" style="font-size: 0.8rem; padding: 0.25rem 0.5rem; width: auto; min-width: 140px;">
                                    <?php foreach ($statusLabels as $sVal => $sLbl): ?>
                                        <option value="<?= $sVal ?>" <?= $r['status'] === $sVal ? 'selected' : '' ?>><?= $sLbl ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <?php if ($r['crm_status'] === 'synced'): ?>
                                    <span class="badge badge-success" title="Sincronizado com EvoCRM"><i class="fas fa-check"></i> Enviado</span>
                                <?php elseif ($r['crm_status'] === 'pending' || $r['crm_status'] === 'processing'): ?>
                                    <span class="badge badge-warning" title="Na fila de envio"><i class="fas fa-clock"></i> Pendente</span>
                                <?php elseif ($r['crm_status'] === 'error'): ?>
                                    <span class="badge badge-danger" title="Erro ao enviar ao CRM"><i class="fas fa-exclamation"></i> Erro</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary" title="Aguardando fila"><i class="fas fa-minus"></i> -</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <a href="reserva-detalhes.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-secondary" title="Ver Detalhes e Histórico">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= htmlspecialchars($whatsappLink) ?>" target="_blank" class="btn btn-sm btn-success" title="WhatsApp Rápido">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<!-- Formulário Oculto para Atualização Rápida de Status via JS -->
<form id="formQuickStatus" method="POST" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="update_single_status">
    <input type="hidden" name="reservation_id" id="quickReservationId">
    <input type="hidden" name="new_status" id="quickNewStatus">
</form>

<!-- Paginação -->
<?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): 
            $getParams = $_GET;
            $getParams['page'] = $i;
            $link = '?' . http_build_query($getParams);
        ?>
            <a href="<?= $link ?>" class="<?= $page === $i ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>

<script>
function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.resCheckbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
}

function changeStatus(reservationId, newStatus) {
    document.getElementById('quickReservationId').value = reservationId;
    document.getElementById('quickNewStatus').value = newStatus;
    document.getElementById('formQuickStatus').submit();
}
</script>

<?php require_once 'includes/footer.php'; ?>
