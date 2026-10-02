<?php
/**
 * Log Auditável de Conversões e Eventos
 * ISP Preparatórios
 */

require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';
require_once '../includes/MarketingTracker.php';

// Filtros
$filterEvent = trim($_GET['event'] ?? '');
$filterCampaign = trim($_GET['campaign'] ?? '');
$filterDate = trim($_GET['date'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($filterEvent)) {
    $where[] = "c.event_name = ?";
    $params[] = $filterEvent;
}
if (!empty($filterCampaign)) {
    $where[] = "(c.campaign_slug = ? OR m.slug = ?)";
    $params[] = $filterCampaign;
    $params[] = $filterCampaign;
}
if (!empty($filterDate)) {
    $where[] = "DATE(c.created_at) = ?";
    $params[] = $filterDate;
}
if (!empty($filterStatus)) {
    $where[] = "c.status = ?";
    $params[] = $filterStatus;
}

$whereSql = implode(" AND ", $where);

// Paginação
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM marketing_conversions c LEFT JOIN marketing_campaigns m ON c.campaign_id = m.id WHERE {$whereSql}");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

$query = "SELECT c.*, m.name AS campaign_name, r.id AS res_id, r.status AS res_status 
    FROM marketing_conversions c 
    LEFT JOIN marketing_campaigns m ON c.campaign_id = m.id 
    LEFT JOIN reservations r ON (c.entity_type = 'reservation' AND c.entity_id = r.id)
    WHERE {$whereSql} 
    ORDER BY c.id DESC 
    LIMIT {$perPage} OFFSET {$offset}";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$conversions = $stmt->fetchAll();

// Métricas Globais
$stats = MarketingTracker::getConversionStats($pdo);
$eventsList = $pdo->query("SELECT DISTINCT event_name FROM marketing_conversions ORDER BY event_name ASC")->fetchAll(PDO::FETCH_COLUMN);
$campaignsList = $pdo->query("SELECT slug, name FROM marketing_campaigns ORDER BY name ASC")->fetchAll();

require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h2 style="margin: 0; color: #03045e; font-size: 1.4rem;">
            <i class="fas fa-chart-line" style="color: #ff8000; margin-right: 0.5rem;"></i>
            Log de Conversões & Eventos de Rastreamento
        </h2>
        <p style="margin: 0.3rem 0 0; color: #666; font-size: 0.9rem;">
            Histórico auditável de conversões confirmadas, identificadores de deduplicação (event_id) e parâmetros de atribuição.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="export_marketing_conversoes_csv.php?<?= http_build_query($_GET) ?>" class="btn" style="background: #28a745;" title="Exportar dados filtrados em planilha CSV">
            <i class="fas fa-file-excel"></i> Exportar CSV
        </a>
        <a href="marketing-pixels.php" class="btn btn-secondary btn-sm"><i class="fas fa-bullseye"></i> Pixels</a>
    </div>
</div>

<!-- Cards de Métricas -->
<div class="grid-cards" style="margin-bottom: 1.5rem;">
    <div class="stat-card" style="border-left-color: #03045e;">
        <h3>Total de Eventos Gravados</h3>
        <p class="val"><?= number_format($stats['total'], 0, ',', '.') ?></p>
        <small style="color: #777;">Todas as conversões</small>
    </div>
    <div class="stat-card" style="border-left-color: #ff8000;">
        <h3>Leads Oficiais Gerados</h3>
        <p class="val" style="color: #ff8000;"><?= number_format($stats['leads_total'], 0, ',', '.') ?></p>
        <small style="color: #777;">Novas reservas confirmadas</small>
    </div>
    <div class="stat-card" style="border-left-color: #28a745;">
        <h3>Conversões Hoje</h3>
        <p class="val" style="color: #28a745;"><?= number_format($stats['today'], 0, ',', '.') ?></p>
        <small style="color: #777;">Nas últimas 24 horas</small>
    </div>
    <div class="stat-card" style="border-left-color: #17a2b8;">
        <h3>Campanha Caxias (Leads)</h3>
        <p class="val" style="color: #17a2b8;"><?= number_format($stats['caxias_leads'], 0, ',', '.') ?></p>
        <small style="color: #777;">LP Prefeitura Caxias</small>
    </div>
</div>

<!-- Filtros de Pesquisa -->
<div class="card" style="padding: 1rem 1.2rem; margin-bottom: 1.5rem;">
    <form action="marketing-conversoes.php" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)) 120px; gap: 0.8rem; align-items: flex-end;">
        <div>
            <label style="font-size: 0.8rem; font-weight: 600; color: #555; display: block; margin-bottom: 0.2rem;">Evento</label>
            <select name="event" class="form-control" style="font-size: 0.85rem; padding: 0.45rem;">
                <option value="">Todos os Eventos</option>
                <?php foreach ($eventsList as $eName): ?>
                    <option value="<?= htmlspecialchars($eName) ?>" <?= $filterEvent === $eName ? 'selected' : '' ?>><?= htmlspecialchars($eName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label style="font-size: 0.8rem; font-weight: 600; color: #555; display: block; margin-bottom: 0.2rem;">Campanha</label>
            <select name="campaign" class="form-control" style="font-size: 0.85rem; padding: 0.45rem;">
                <option value="">Todas as Campanhas</option>
                <?php foreach ($campaignsList as $camp): ?>
                    <option value="<?= htmlspecialchars($camp['slug']) ?>" <?= $filterCampaign === $camp['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($camp['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label style="font-size: 0.8rem; font-weight: 600; color: #555; display: block; margin-bottom: 0.2rem;">Data</label>
            <input type="date" name="date" class="form-control" style="font-size: 0.85rem; padding: 0.45rem;" value="<?= htmlspecialchars($filterDate) ?>">
        </div>

        <div>
            <label style="font-size: 0.8rem; font-weight: 600; color: #555; display: block; margin-bottom: 0.2rem;">Status</label>
            <select name="status" class="form-control" style="font-size: 0.85rem; padding: 0.45rem;">
                <option value="">Todos os Status</option>
                <option value="recorded" <?= $filterStatus === 'recorded' ? 'selected' : '' ?>>Gravado (recorded)</option>
                <option value="sent_browser" <?= $filterStatus === 'sent_browser' ? 'selected' : '' ?>>Browser (sent_browser)</option>
                <option value="sent_capi" <?= $filterStatus === 'sent_capi' ? 'selected' : '' ?>>CAPI (sent_capi)</option>
                <option value="duplicate_prevented" <?= $filterStatus === 'duplicate_prevented' ? 'selected' : '' ?>>Duplicado Prevenido</option>
            </select>
        </div>

        <div>
            <button type="submit" class="btn btn-sm" style="width: 100%; height: 35px; background: #03045e; justify-content: center;">
                <i class="fas fa-search"></i> Filtrar
            </button>
        </div>
    </form>
</div>

<!-- Tabela de Conversões -->
<div class="card" style="padding: 0; overflow: hidden;">
    <table class="table" style="margin: 0;">
        <thead>
            <tr>
                <th style="width: 65px;">ID</th>
                <th style="width: 130px;">Data / Hora</th>
                <th>Evento</th>
                <th>Campanha / Origem</th>
                <th>Reserva / Lead</th>
                <th>Parâmetros UTM</th>
                <th>event_id (Deduplicação CAPI)</th>
                <th style="text-align: center; width: 90px;">Status</th>
                <th style="text-align: right; width: 60px;">Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($conversions)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 2.5rem; color: #888;">
                        Nenhuma conversão registrada com os filtros selecionados.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($conversions as $conv): 
                    $eventBadgeClass = 'badge-secondary';
                    if ($conv['event_name'] === 'Lead') $eventBadgeClass = 'badge-success';
                    elseif ($conv['event_name'] === 'PageView') $eventBadgeClass = 'badge-info';
                    elseif ($conv['event_name'] === 'CompleteRegistration') $eventBadgeClass = 'badge-warning';
                ?>
                    <tr>
                        <td style="font-family: monospace; font-size: 0.85rem; color: #666;">#<?= $conv['id'] ?></td>
                        <td style="font-size: 0.82rem; color: #555; white-space: nowrap;">
                            <i class="far fa-clock" style="color: #aaa;"></i> <?= date('d/m/Y H:i:s', strtotime($conv['created_at'])) ?>
                        </td>
                        <td>
                            <span class="badge <?= $eventBadgeClass ?>" style="font-size: 0.85rem; font-weight: 700;">
                                <?= htmlspecialchars($conv['event_name']) ?>
                            </span>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($conv['campaign_name'] ?: ($conv['campaign_slug'] ?: 'Sem Campanha')) ?></strong>
                            <?php if (!empty($conv['page_url'])): ?>
                                <div style="font-size: 0.75rem; color: #777; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?= htmlspecialchars($conv['page_url']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($conv['entity_type'] === 'reservation' && !empty($conv['entity_id'])): ?>
                                <a href="reserva-detalhes.php?id=<?= $conv['entity_id'] ?>" target="_blank" style="color: #03045e; font-weight: 600; text-decoration: none; font-size: 0.85rem;">
                                    <i class="fas fa-clipboard-check" style="color: #28a745;"></i> Reserva #<?= $conv['entity_id'] ?>
                                </a>
                                <?php if (!empty($conv['res_status'])): ?>
                                    <span style="font-size: 0.72rem; color: #666; display: block;">Status: <?= htmlspecialchars($conv['res_status']) ?></span>
                                <?php endif; ?>
                            <?php elseif (!empty($conv['lead_id'])): ?>
                                <a href="lead-detalhes.php?id=<?= $conv['lead_id'] ?>" target="_blank" style="color: #03045e; font-size: 0.85rem;">
                                    Lead #<?= $conv['lead_id'] ?>
                                </a>
                            <?php else: ?>
                                <span style="color: #aaa; font-size: 0.8rem;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-size: 0.75rem; line-height: 1.35; font-family: monospace; color: #475569;">
                                <?php if (!empty($conv['utm_source'])): ?>
                                    <div><strong>src:</strong> <?= htmlspecialchars($conv['utm_source']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($conv['utm_medium'])): ?>
                                    <div><strong>med:</strong> <?= htmlspecialchars($conv['utm_medium']) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($conv['utm_campaign'])): ?>
                                    <div><strong>cmp:</strong> <?= htmlspecialchars($conv['utm_campaign']) ?></div>
                                <?php endif; ?>
                                <?php if (empty($conv['utm_source']) && empty($conv['utm_campaign'])): ?>
                                    <span style="color: #aaa;">Orgânico / Direto</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <code style="font-size: 0.75rem; background: #eef2ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; display: inline-block; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($conv['event_id']) ?>">
                                <?= htmlspecialchars($conv['event_id']) ?>
                            </code>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge badge-success" style="font-size: 0.72rem;">
                                <?= htmlspecialchars($conv['status']) ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button type="button" class="btn btn-sm btn-secondary" onclick='viewPayload(<?= json_encode($conv, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG) ?>)' title="Ver Payload / Parâmetros">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginação -->
    <?php if ($totalPages > 1): ?>
        <div style="padding: 1rem; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 0.85rem; color: #666;">
                Mostrando <?= count($conversions) ?> de <?= $totalRows ?> conversões registradas.
            </div>
            <div class="pagination" style="margin: 0;">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo; Anterior</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="<?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Próxima &raquo;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Detalhes do Payload -->
<div id="modalPayload" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 9999; align-items: center; justify-content: center; padding: 1.5rem;">
    <div style="background: #fff; width: 100%; max-width: 600px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); overflow: hidden;">
        <div style="padding: 1.2rem 1.5rem; background: #03045e; color: #fff; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalPayloadTitle" style="margin: 0; font-size: 1.15rem; font-weight: 700;">Detalhes da Conversão</h3>
            <button type="button" onclick="closePayloadModal()" style="background: transparent; border: none; color: #fff; font-size: 1.2rem; cursor: pointer;">&times;</button>
        </div>
        <div style="padding: 1.5rem; max-height: 75vh; overflow-y: auto;">
            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 600; font-size: 0.85rem; color: #555;">event_id (Identificador Único CAPI/Browser):</label>
                <div id="modalEventId" style="font-family: monospace; font-size: 0.85rem; background: #f1f5f9; padding: 0.5rem; border-radius: 4px; word-break: break-all;"></div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="font-weight: 600; font-size: 0.85rem; color: #555;">Parâmetros Enviados (Payload JSON):</label>
                <pre id="modalPayloadContent" style="background: #020220; color: #a5f3fc; padding: 1rem; border-radius: 6px; font-size: 0.85rem; max-height: 250px; overflow: auto;"></pre>
            </div>

            <div style="font-size: 0.8rem; color: #64748b;">
                <strong>Nota de Privacidade LGPD:</strong> Parâmetros públicos enviados à Meta contêm apenas metadados do evento (nome do curso, modalidade e estágio de pré-cadastro). Dados pessoais como nome, e-mail e telefone nunca são expostos neste payload.
            </div>
        </div>
        <div style="padding: 0.9rem 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right;">
            <button type="button" onclick="closePayloadModal()" class="btn btn-secondary btn-sm">Fechar</button>
        </div>
    </div>
</div>

<script>
function viewPayload(conv) {
    document.getElementById('modalPayloadTitle').innerText = 'Conversão #' + conv.id + ' — ' + conv.event_name;
    document.getElementById('modalEventId').innerText = conv.event_id || 'Nenhum';

    let jsonFormatted = 'Nenhum parâmetro registrado.';
    if (conv.payload_json) {
        try {
            const parsed = JSON.parse(conv.payload_json);
            jsonFormatted = JSON.stringify(parsed, null, 2);
        } catch(e) {
            jsonFormatted = conv.payload_json;
        }
    }
    document.getElementById('modalPayloadContent').innerText = jsonFormatted;
    document.getElementById('modalPayload').style.display = 'flex';
}

function closePayloadModal() {
    document.getElementById('modalPayload').style.display = 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>
