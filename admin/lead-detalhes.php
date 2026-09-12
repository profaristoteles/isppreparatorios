<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';
require_once '../includes/reservation_service.php';

$lead_id = (int)($_GET['id'] ?? 0);
if (!$lead_id) {
    header("Location: gerenciar-leads.php");
    exit;
}

// Carregar Lead
$stmtLead = $pdo->prepare("SELECT * FROM leads WHERE id = ? LIMIT 1");
$stmtLead->execute([$lead_id]);
$lead = $stmtLead->fetch();

if (!$lead) {
    $_SESSION['erro'] = "Lead não encontrado.";
    header("Location: gerenciar-leads.php");
    exit;
}

// Carregar Tags acumuladas
$stmtTags = $pdo->prepare("SELECT t.name, t.slug, lt.created_at FROM tags t JOIN lead_tags lt ON lt.tag_id = t.id WHERE lt.lead_id = ? ORDER BY lt.created_at DESC");
$stmtTags->execute([$lead_id]);
$tagsList = $stmtTags->fetchAll();

// Carregar Histórico Imutável de Consentimentos LGPD
$stmtConsents = $pdo->prepare("SELECT * FROM lead_consents WHERE lead_id = ? ORDER BY id DESC");
$stmtConsents->execute([$lead_id]);
$consentsList = $stmtConsents->fetchAll();

// Carregar Eventos da Jornada
$stmtEvents = $pdo->prepare("SELECT e.*, v.title as video_title FROM free_video_events e JOIN free_videos v ON e.video_id = v.id WHERE e.lead_id = ? ORDER BY e.id DESC");
$stmtEvents->execute([$lead_id]);
$eventsList = $stmtEvents->fetchAll();

// Carregar Downloads Efetivados
$stmtDLs = $pdo->prepare("SELECT d.*, m.title as material_title, v.title as video_title FROM lead_downloads d JOIN free_materials m ON d.material_id = m.id JOIN free_videos v ON d.video_id = v.id WHERE d.subject_type = 'lead' AND d.subject_id = ? ORDER BY d.id DESC");
$stmtDLs->execute([$lead_id]);
$downloadsList = $stmtDLs->fetchAll();

// Carregar Histórico na Fila de Integração
$stmtQueue = $pdo->prepare("SELECT * FROM integration_queue WHERE entity_type = 'lead' AND entity_id = ? ORDER BY id DESC");
$stmtQueue->execute([$lead_id]);
$queueList = $stmtQueue->fetchAll();

// Carregar Reservas do Lead
$stmtRes = $pdo->prepare("SELECT r.*, c.title as campaign_title, c.slug as campaign_slug 
                          FROM reservations r 
                          JOIN reservation_campaigns c ON r.campaign_id = c.id 
                          WHERE r.lead_id = ? 
                          ORDER BY r.id DESC");
$stmtRes->execute([$lead_id]);
$reservationsList = $stmtRes->fetchAll();

require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h2><i class="fas fa-user"></i> Detalhes do Lead: <?= htmlspecialchars($lead['name']) ?> (#<?= $lead['id'] ?>)</h2>
    <a href="gerenciar-leads.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar para a Lista</a>
</div>

<!-- Dados Cadastrais e UTMs -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
    <div class="card">
        <h3><i class="fas fa-id-card"></i> Informações de Cadastro</h3>
        <table class="table" style="margin-top: 0.5rem;">
            <tr><th style="width: 180px;">Nome Completo:</th><td><strong><?= htmlspecialchars($lead['name']) ?></strong></td></tr>
            <tr><th>E-mail:</th><td><a href="mailto:<?= htmlspecialchars($lead['email']) ?>"><?= htmlspecialchars($lead['email']) ?></a></td></tr>
            <tr><th>WhatsApp / Telefone:</th><td><i class="fab fa-whatsapp text-success"></i> <?= htmlspecialchars($lead['phone_original']) ?></td></tr>
            <tr><th>Telefone Normalizado:</th><td><code><?= htmlspecialchars($lead['phone_normalized'] ?: '-') ?></code></td></tr>
            <tr><th>Origem:</th><td><span class="badge badge-info"><?= htmlspecialchars($lead['source']) ?></span></td></tr>
            <tr><th>Primeira Conversão:</th><td><?= date('d/m/Y H:i:s', strtotime($lead['first_conversion'])) ?></td></tr>
            <tr><th>Última Conversão:</th><td><?= date('d/m/Y H:i:s', strtotime($lead['last_conversion'])) ?></td></tr>
        </table>
    </div>

    <div class="card">
        <h3><i class="fas fa-bullhorn"></i> Parâmetros UTMs</h3>
        <table class="table" style="margin-top: 0.5rem;">
            <tr><th>UTM Source:</th><td><code><?= htmlspecialchars($lead['utm_source'] ?: '-') ?></code></td></tr>
            <tr><th>UTM Medium:</th><td><code><?= htmlspecialchars($lead['utm_medium'] ?: '-') ?></code></td></tr>
            <tr><th>UTM Campaign:</th><td><code><?= htmlspecialchars($lead['utm_campaign'] ?: '-') ?></code></td></tr>
            <tr><th>UTM Content:</th><td><code><?= htmlspecialchars($lead['utm_content'] ?: '-') ?></code></td></tr>
            <tr><th>UTM Term:</th><td><code><?= htmlspecialchars($lead['utm_term'] ?: '-') ?></code></td></tr>
        </table>
    </div>
</div>

<!-- Tags Acumuladas -->
<div class="card" style="margin-bottom: 1.5rem;">
    <h3><i class="fas fa-tags"></i> Tags Acumuladas do Lead (<?= count($tagsList) ?>)</h3>
    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.8rem;">
        <?php if (empty($tagsList)): ?>
            <span style="color: #888;">Nenhuma tag atribuída.</span>
        <?php else: foreach ($tagsList as $t): ?>
            <span class="badge badge-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.7rem;">
                <i class="fas fa-tag"></i> <?= htmlspecialchars($t['name']) ?> 
                <small style="opacity: 0.7;">(<?= date('d/m/Y', strtotime($t['created_at'])) ?>)</small>
            </span>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- Reservas em Novas Turmas / Lista de Interesse -->
<div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid #ff8000;">
    <h3><i class="fas fa-bookmark text-warning"></i> Reservas em Novas Turmas (<?= count($reservationsList) ?>)</h3>
    <table class="table" style="margin-top: 0.8rem;">
        <thead>
            <tr>
                <th style="width: 60px;">ID</th>
                <th>Campanha / Turma</th>
                <th>Modalidade</th>
                <th>Status Comercial</th>
                <th>Fila de Espera</th>
                <th>Data da Reserva</th>
                <th style="text-align: right; width: 100px;">Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($reservationsList)): ?>
                <tr><td colspan="7" style="text-align: center; color: #888;">Este lead não possui reservas registradas.</td></tr>
            <?php else: foreach ($reservationsList as $resItem): 
                $resStatusLabels = ReservationService::getReservationStatusLabels();
                $resModLabels = ReservationService::getModalityLabels();

                $rBadgeClass = 'badge-secondary';
                if ($resItem['status'] === 'nova') $rBadgeClass = 'badge-info';
                elseif ($resItem['status'] === 'contatado' || $resItem['status'] === 'interessado') $rBadgeClass = 'badge-warning';
                elseif ($resItem['status'] === 'matriculado') $rBadgeClass = 'badge-success';
                elseif ($resItem['status'] === 'aguardando_matricula') $rBadgeClass = 'badge-primary';
                elseif ($resItem['status'] === 'nao_respondeu' || $resItem['status'] === 'sem_interesse' || $resItem['status'] === 'cancelado') $rBadgeClass = 'badge-danger';
            ?>
                <tr>
                    <td><strong>#<?= $resItem['id'] ?></strong></td>
                    <td>
                        <strong><?= htmlspecialchars($resItem['campaign_title']) ?></strong>
                    </td>
                    <td>
                        <span class="badge badge-secondary">
                            <?= htmlspecialchars($resModLabels[$resItem['preferred_modality']] ?? $resItem['preferred_modality']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $rBadgeClass ?>">
                            <?= htmlspecialchars($resStatusLabels[$resItem['status']] ?? $resItem['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?= $resItem['is_waiting_list'] ? '<span class="badge badge-warning">Sim (Lista de Espera)</span>' : '<span class="badge badge-success">Vaga Regular</span>' ?>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($resItem['created_at'])) ?></td>
                    <td style="text-align: right;">
                        <a href="reserva-detalhes.php?id=<?= $resItem['id'] ?>" class="btn btn-sm btn-secondary" title="Ver Detalhes da Reserva">
                            <i class="fas fa-eye"></i> Detalhes
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Histórico Imutável de Consentimentos LGPD -->
<div class="card" style="margin-bottom: 1.5rem;">
    <h3><i class="fas fa-shield-alt"></i> Histórico de Consentimentos LGPD (Imutável)</h3>
    <table class="table" style="margin-top: 0.5rem;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tipo de Aceite</th>
                <th>Status</th>
                <th>Versão do Texto</th>
                <th>Versão da Política</th>
                <th>Origem</th>
                <th>Data do Aceite</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($consentsList)): ?>
                <tr><td colspan="7" style="text-align: center; color: #888;">Nenhum registro de consentimento localizado.</td></tr>
            <?php else: foreach ($consentsList as $c): ?>
                <tr>
                    <td>#<?= $c['id'] ?></td>
                    <td><?= htmlspecialchars($c['consent_type']) ?></td>
                    <td>
                        <?php if ($c['accepted']): ?>
                            <span class="badge badge-success">Aceito</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Revogado</span>
                        <?php endif; ?>
                    </td>
                    <td><code><?= htmlspecialchars($c['consent_text_version']) ?></code></td>
                    <td><code><?= htmlspecialchars($c['privacy_policy_version']) ?></code></td>
                    <td><?= htmlspecialchars($c['source']) ?></td>
                    <td><?= date('d/m/Y H:i:s', strtotime($c['accepted_at'])) ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Eventos da Jornada & Downloads Efetivados -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
    <div class="card">
        <h3><i class="fas fa-history"></i> Eventos da Jornada (<?= count($eventsList) ?>)</h3>
        <table class="table" style="margin-top: 0.5rem;">
            <thead>
                <tr>
                    <th>Evento</th>
                    <th>Videoaula</th>
                    <th>Data/Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($eventsList)): ?>
                    <tr><td colspan="3" style="text-align: center; color: #888;">Nenhum evento registrado.</td></tr>
                <?php else: foreach ($eventsList as $evt): ?>
                    <tr>
                        <td><span class="badge badge-info"><?= htmlspecialchars($evt['event_type']) ?></span></td>
                        <td><?= htmlspecialchars($evt['video_title']) ?></td>
                        <td><?= date('d/m/Y H:i:s', strtotime($evt['created_at'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3><i class="fas fa-file-download"></i> Downloads Efetivados (<?= count($downloadsList) ?>)</h3>
        <table class="table" style="margin-top: 0.5rem;">
            <thead>
                <tr>
                    <th>Material PDF</th>
                    <th>Videoaula</th>
                    <th>Data/Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($downloadsList)): ?>
                    <tr><td colspan="3" style="text-align: center; color: #888;">Nenhum download efetuado.</td></tr>
                <?php else: foreach ($downloadsList as $dl): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($dl['material_title']) ?></strong></td>
                        <td><?= htmlspecialchars($dl['video_title']) ?></td>
                        <td><?= date('d/m/Y H:i:s', strtotime($dl['downloaded_at'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Status na Fila de Integrações -->
<div class="card">
    <h3><i class="fas fa-sync"></i> Histórico na Fila de Integrações</h3>
    <table class="table" style="margin-top: 0.5rem;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Integração</th>
                <th>Ação</th>
                <th>Status</th>
                <th>Tentativas</th>
                <th>Último Erro</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($queueList)): ?>
                <tr><td colspan="7" style="text-align: center; color: #888;">Nenhuma sincronização enfileirada para este lead.</td></tr>
            <?php else: foreach ($queueList as $q): ?>
                <tr>
                    <td>#<?= $q['id'] ?></td>
                    <td><?= htmlspecialchars($q['integration']) ?></td>
                    <td><?= htmlspecialchars($q['action']) ?></td>
                    <td>
                        <?php if ($q['status'] === 'synced'): ?>
                            <span class="badge badge-success">Sincronizado</span>
                        <?php elseif ($q['status'] === 'error'): ?>
                            <span class="badge badge-danger">Erro</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><?= htmlspecialchars($q['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= $q['attempts'] ?> / <?= $q['max_attempts'] ?></td>
                    <td><small style="color: #dc3545;"><?= htmlspecialchars($q['last_error'] ?: '-') ?></small></td>
                    <td><?= date('d/m/Y H:i:s', strtotime($q['created_at'])) ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
