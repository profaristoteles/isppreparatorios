<?php
/**
 * Visualização e Detalhes da Reserva / Lista de Interesse
 * ISP Preparatórios
 */

require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';
require_once '../includes/reservation_service.php';

$reservationId = (int)($_GET['id'] ?? 0);
if (!$reservationId) {
    header("Location: gerenciar-reservas.php");
    exit;
}

$statusLabels = ReservationService::getReservationStatusLabels();
$modalityLabels = ReservationService::getModalityLabels();

// Processar Ações (Atualizar Status, Adicionar Nota, Reenviar CRM)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $action = $_POST['action'] ?? '';
    $adminId = $_SESSION['admin_id'] ?? null;
    $adminName = $_SESSION['admin_name'] ?? 'Administrador';

    if ($action === 'update_status') {
        $newStatus = $_POST['new_status'] ?? '';
        $res = ReservationService::updateReservationStatus($pdo, $reservationId, $newStatus, $adminId, $adminName);
        if ($res['success']) {
            $_SESSION['msg'] = $res['message'];
        } else {
            $_SESSION['erro'] = $res['message'];
        }
        header("Location: reserva-detalhes.php?id=" . $reservationId);
        exit;
    }

    if ($action === 'add_note') {
        $note = trim($_POST['note'] ?? '');
        $res = ReservationService::addInternalNote($pdo, $reservationId, $note, $adminId, $adminName);
        if ($res['success']) {
            $_SESSION['msg'] = $res['message'];
        } else {
            $_SESSION['erro'] = $res['message'];
        }
        header("Location: reserva-detalhes.php?id=" . $reservationId);
        exit;
    }

    if ($action === 'register_contact') {
        $channel = $_POST['channel'] ?? 'WhatsApp';
        $notes = trim($_POST['notes'] ?? '');
        $clearFollowUp = !empty($_POST['clear_follow_up']);
        $nextFollowUpAt = !empty($_POST['next_follow_up_at']) ? $_POST['next_follow_up_at'] : null;
        $newStatus = !empty($_POST['new_status']) ? $_POST['new_status'] : null;

        $res = ReservationService::registerCommercialContact($pdo, $reservationId, $channel, $notes, $nextFollowUpAt, $newStatus, $adminId, $adminName, $clearFollowUp);
        if ($res['success']) {
            $_SESSION['msg'] = $res['message'];
        } else {
            $_SESSION['erro'] = $res['message'];
        }
        header("Location: reserva-detalhes.php?id=" . $reservationId);
        exit;
    }

    if ($action === 'update_follow_up') {
        $clearFollowUp = !empty($_POST['clear_follow_up']);
        $nextFollowUpAt = !empty($_POST['next_follow_up_at']) ? $_POST['next_follow_up_at'] : null;
        $res = ReservationService::updateFollowUpDate($pdo, $reservationId, $nextFollowUpAt, $adminId, $adminName, $clearFollowUp);
        if ($res['success']) {
            $_SESSION['msg'] = $res['message'];
        } else {
            $_SESSION['erro'] = $res['message'];
        }
        header("Location: reserva-detalhes.php?id=" . $reservationId);
        exit;
    }

    if ($action === 'resync_crm') {
        $res = ReservationService::reenqueueEvoCRM($pdo, $reservationId);
        if ($res['success']) {
            $_SESSION['msg'] = $res['message'];
        } else {
            $_SESSION['erro'] = $res['message'];
        }
        header("Location: reserva-detalhes.php?id=" . $reservationId);
        exit;
    }
}

// Carregar Reserva com Lead e Campanha
$stmt = $pdo->prepare("SELECT r.*, 
                       l.name as lead_name, l.email as lead_email, l.phone_original as lead_phone, l.phone_normalized as lead_phone_normalized, l.created_at as lead_created_at,
                       c.title as campaign_title, c.slug as campaign_slug, c.status as campaign_status, c.allows_presencial, c.allows_online
                       FROM reservations r 
                       JOIN leads l ON r.lead_id = l.id 
                       JOIN reservation_campaigns c ON r.campaign_id = c.id 
                       WHERE r.id = ? LIMIT 1");
$stmt->execute([$reservationId]);
$reservation = $stmt->fetch();

if (!$reservation) {
    $_SESSION['erro'] = "Reserva não encontrada.";
    header("Location: gerenciar-reservas.php");
    exit;
}

// Carregar Histórico de Ações
$stmtHist = $pdo->prepare("SELECT h.*, u.name as admin_name 
                          FROM reservation_history h 
                          LEFT JOIN admin_usuarios u ON h.admin_id = u.id 
                          WHERE h.reservation_id = ? 
                          ORDER BY h.id DESC");
$stmtHist->execute([$reservationId]);
$history = $stmtHist->fetchAll();

// Carregar Notas Internas
$stmtNotes = $pdo->prepare("SELECT n.*, u.name as admin_name 
                           FROM reservation_notes n 
                           LEFT JOIN admin_usuarios u ON n.admin_id = u.id 
                           WHERE n.reservation_id = ? 
                           ORDER BY n.id DESC");
$stmtNotes->execute([$reservationId]);
$notes = $stmtNotes->fetchAll();

// Carregar Dados da Fila de Integração EvoCRM
$stmtQueue = $pdo->prepare("SELECT * FROM integration_queue WHERE entity_type = 'reservation' AND entity_id = ? ORDER BY id DESC LIMIT 1");
$stmtQueue->execute([$reservationId]);
$crmQueue = $stmtQueue->fetch();

$whatsappLink = ReservationService::generateWhatsAppLink($reservation['lead_phone'], $reservation['lead_name'], $reservation['campaign_title'], $reservation['preferred_modality']);

// Decodificar respostas customizadas se existirem
$customAnswers = [];
if (!empty($reservation['custom_answers_json'])) {
    $customAnswers = json_decode($reservation['custom_answers_json'], true) ?: [];
}

$lastContactAt = ReservationService::getLastCommercialContactDate($pdo, $reservationId);
$lastContactFormatted = ReservationService::formatContactDate($lastContactAt);
$nextFollowUpFormatted = !empty($reservation['next_follow_up_at']) ? date('d/m/Y \à\s H:i', strtotime($reservation['next_follow_up_at'])) : 'Nunca agendado';
$nextFollowUpInputValue = !empty($reservation['next_follow_up_at']) ? date('Y-m-d\TH:i', strtotime($reservation['next_follow_up_at'])) : '';

require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h2><i class="fas fa-id-card"></i> Reserva #<?= $reservation['id'] ?>: <?= htmlspecialchars($reservation['lead_name']) ?></h2>
        <p style="color: #666; margin: 0; font-size: 0.9rem;">Campanha: <strong><?= htmlspecialchars($reservation['campaign_title']) ?></strong></p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="<?= htmlspecialchars($whatsappLink) ?>" target="_blank" class="btn btn-success" style="background: #25d366;">
            <i class="fab fa-whatsapp"></i> Conversar no WhatsApp
        </a>
        <a href="mailto:<?= htmlspecialchars($reservation['lead_email']) ?>" class="btn btn-secondary">
            <i class="fas fa-envelope"></i> Enviar E-mail
        </a>
        <a href="gerenciar-reservas.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
    
    <!-- Coluna Esquerda: Dados Principais e Respostas -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        <!-- Cartão de Dados Cadastrais e do Lead -->
        <div class="card">
            <h3><i class="fas fa-user-check"></i> Dados do Interessado (Lead)</h3>
            <table class="table" style="margin-top: 0.8rem;">
                <tr>
                    <th style="width: 180px;">Nome Completo:</th>
                    <td><strong><?= htmlspecialchars($reservation['lead_name']) ?></strong></td>
                </tr>
                <tr>
                    <th>WhatsApp / Telefone:</th>
                    <td>
                        <a href="<?= htmlspecialchars($whatsappLink) ?>" target="_blank" style="color: #25d366; font-weight: 700; text-decoration: none;">
                            <i class="fab fa-whatsapp"></i> <?= htmlspecialchars($reservation['lead_phone']) ?>
                        </a>
                        <span style="font-size: 0.8rem; color: #888; margin-left: 0.5rem;">(Normalizado: <?= htmlspecialchars($reservation['lead_phone_normalized']) ?>)</span>
                    </td>
                </tr>
                <tr>
                    <th>E-mail:</th>
                    <td>
                        <a href="mailto:<?= htmlspecialchars($reservation['lead_email']) ?>"><?= htmlspecialchars($reservation['lead_email']) ?></a>
                    </td>
                </tr>
                <tr>
                    <th>Cidade / UF:</th>
                    <td><?= htmlspecialchars($reservation['city'] ?: '-') ?><?= !empty($reservation['state']) ? ' / ' . htmlspecialchars($reservation['state']) : '' ?></td>
                </tr>
                <tr>
                    <th>Cadastro Mestre:</th>
                    <td>
                        <a href="lead-detalhes.php?id=<?= $reservation['lead_id'] ?>" class="btn btn-sm btn-secondary">
                            <i class="fas fa-external-link-alt"></i> Ver Perfil Completo do Lead (#<?= $reservation['lead_id'] ?>)
                        </a>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Cartão de Detalhes da Reserva -->
        <div class="card">
            <h3><i class="fas fa-bookmark"></i> Detalhes da Reserva de Vaga</h3>
            <table class="table" style="margin-top: 0.8rem;">
                <tr>
                    <th style="width: 180px;">Campanha:</th>
                    <td>
                        <strong><?= htmlspecialchars($reservation['campaign_title']) ?></strong>
                        <a href="/reserva/<?= htmlspecialchars($reservation['campaign_slug']) ?>" target="_blank" style="font-size: 0.8rem; margin-left: 0.5rem;">(Ver Landing Page)</a>
                    </td>
                </tr>
                <tr>
                    <th>Modalidade Preferida:</th>
                    <td>
                        <strong style="color: #ff8000; font-size: 1rem;">
                            <?= htmlspecialchars($modalityLabels[$reservation['preferred_modality']] ?? $reservation['preferred_modality']) ?>
                        </strong>
                    </td>
                </tr>
                <tr>
                    <th>Fila de Espera:</th>
                    <td>
                        <?php if ($reservation['is_waiting_list']): ?>
                            <span class="badge badge-warning"><i class="fas fa-hourglass-half"></i> Sim, em Lista de Espera</span>
                        <?php else: ?>
                            <span class="badge badge-success"><i class="fas fa-check"></i> Vaga Regular</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Data da Reserva:</th>
                    <td><?= date('d/m/Y \à\s H:i:s', strtotime($reservation['created_at'])) ?></td>
                </tr>
                <tr>
                    <th>Último Contato:</th>
                    <td>
                        <strong style="color: <?= $lastContactFormatted === 'Nunca' ? '#888' : '#28a745' ?>;">
                            <i class="fas fa-history"></i> <?= htmlspecialchars($lastContactFormatted) ?>
                        </strong>
                    </td>
                </tr>
                <tr>
                    <th>Próximo Contato:</th>
                    <td>
                        <strong style="color: #03045e;">
                            <i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($nextFollowUpFormatted) ?>
                        </strong>
                        <?php if (!empty($reservation['next_follow_up_at']) && strtotime($reservation['next_follow_up_at']) < time()): ?>
                            <span class="badge badge-danger" style="margin-left: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Contato Vencido</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Consentimento LGPD:</th>
                    <td><span class="badge badge-success"><i class="fas fa-shield-alt"></i> Consentimento Aceito</span></td>
                </tr>
            </table>

            <!-- Respostas Complementares -->
            <?php if (!empty($customAnswers)): ?>
                <h4 style="margin-top: 1.5rem; color: #03045e;"><i class="fas fa-tasks"></i> Perguntas Complementares Respondidas:</h4>
                <table class="table" style="margin-top: 0.5rem;">
                    <?php foreach ($customAnswers as $qKey => $qVal): ?>
                        <tr>
                            <th style="width: 200px;"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $qKey))) ?>:</th>
                            <td><strong><?= htmlspecialchars($qVal) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <!-- Linha do Tempo / Histórico de Auditoria -->
        <div class="card">
            <h3><i class="fas fa-history"></i> Histórico de Auditoria</h3>
            <?php if (empty($history)): ?>
                <p style="color: #888; margin-top: 0.8rem;">Nenhum evento registrado ainda.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.8rem; margin-top: 1rem;">
                    <?php foreach ($history as $h): 
                        $borderCol = '#03045e';
                        $icon = 'fa-history';
                        if ($h['action_type'] === 'contato_comercial') {
                            $borderCol = '#28a745';
                            $icon = 'fa-headset';
                        } elseif ($h['action_type'] === 'reativacao') {
                            $borderCol = '#ff8000';
                            $icon = 'fa-sync-alt';
                        } elseif ($h['action_type'] === 'agendamento_contato') {
                            $borderCol = '#17a2b8';
                            $icon = 'fa-calendar-alt';
                        } elseif ($h['action_type'] === 'erro_crm') {
                            $borderCol = '#dc3545';
                            $icon = 'fa-exclamation-triangle';
                        }
                    ?>
                        <div style="padding: 0.8rem 1rem; background: #f8f9fa; border-left: 4px solid <?= $borderCol ?>; border-radius: 0 4px 4px 0;">
                            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #666; margin-bottom: 0.3rem;">
                                <span><i class="fas <?= $icon ?>"></i> <?= date('d/m/Y H:i:s', strtotime($h['created_at'])) ?></span>
                                <span><?= htmlspecialchars($h['admin_name'] ?: 'Sistema') ?></span>
                            </div>
                            <div style="font-size: 0.9rem; color: #333;">
                                <?= htmlspecialchars($h['description']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Coluna Direita: Acompanhamento Comercial, Status, CRM e Notas Internas -->
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        <!-- Card: Acompanhamento Comercial & Registro de Contato -->
        <div class="card" style="border-top: 4px solid #28a745;">
            <h3><i class="fas fa-headset"></i> Acompanhamento Comercial</h3>
            
            <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 0.8rem; margin-top: 0.8rem; font-size: 0.85rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.4rem;">
                    <span style="color: #666;">Último contato:</span>
                    <strong style="color: <?= $lastContactFormatted === 'Nunca' ? '#888' : '#28a745' ?>;">
                        <?= htmlspecialchars($lastContactFormatted) ?>
                    </strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #666;">Próximo contato:</span>
                    <strong style="color: #03045e;">
                        <?= htmlspecialchars($nextFollowUpFormatted) ?>
                        <?php if (!empty($reservation['next_follow_up_at']) && strtotime($reservation['next_follow_up_at']) < time()): ?>
                            <span class="badge badge-danger" style="font-size: 0.7rem; margin-left: 0.3rem;">Vencido</span>
                        <?php endif; ?>
                    </strong>
                </div>
            </div>

            <!-- Formulário: Marcar como Contatado -->
            <form method="POST" style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.7rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="register_contact">
                <div style="font-weight: 600; font-size: 0.85rem; color: #03045e;">
                    <i class="fas fa-check-circle"></i> Marcar como Contatado:
                </div>
                
                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 0.8rem;">Canal de Contato:</label>
                    <select name="channel" class="form-control" style="font-size: 0.85rem;">
                        <option value="WhatsApp">WhatsApp</option>
                        <option value="E-mail">E-mail</option>
                        <option value="Telefone">Telefone</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 0.8rem;">Atualizar Situação:</label>
                    <select name="new_status" class="form-control" style="font-size: 0.85rem;">
                        <?php foreach ($statusLabels as $sKey => $sLbl): ?>
                            <option value="<?= $sKey ?>" <?= ($reservation['status'] === 'nova' ? 'contatado' : $reservation['status']) === $sKey ? 'selected' : '' ?>><?= $sLbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 0.8rem;">Próximo Contato (Opcional):</label>
                    <input type="datetime-local" name="next_follow_up_at" class="form-control" style="font-size: 0.85rem;" value="<?= $nextFollowUpInputValue ?>">
                </div>

                <div class="form-group" style="margin: 0;">
                    <label style="font-size: 0.8rem;">Observação (Opcional):</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Ex: Lead tem interesse na turma, pediu para retornar após as 18h..." style="font-size: 0.85rem;"></textarea>
                </div>

                <button type="submit" class="btn btn-success" style="width: 100%; background: #28a745; margin-top: 0.3rem;">
                    <i class="fas fa-save"></i> Registrar Contato Realizado
                </button>
            </form>

            <hr style="margin: 1.2rem 0; border: 0; border-top: 1px solid #eee;">

            <!-- Reagendar Próximo Contato -->
            <form method="POST" style="display: flex; flex-direction: column; gap: 0.5rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_follow_up">
                <label style="font-size: 0.8rem; font-weight: 600; color: #03045e; margin: 0;">
                    <i class="fas fa-calendar-plus"></i> Reagendar Próximo Contato:
                </label>
                <div style="display: flex; gap: 0.4rem;">
                    <input type="datetime-local" name="next_follow_up_at" class="form-control" style="font-size: 0.8rem;" value="<?= $nextFollowUpInputValue ?>">
                    <button type="submit" class="btn btn-secondary btn-sm" title="Salvar Agendamento">
                        <i class="fas fa-clock"></i>
                    </button>
                    <?php if (!empty($reservation['next_follow_up_at'])): ?>
                        <button type="submit" name="clear_follow_up" value="1" class="btn btn-sm btn-outline-danger" title="Remover agendamento" onclick="return confirm('Deseja remover o acompanhamento agendado?');">
                            <i class="fas fa-times"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Gestão do Status Comercial -->
        <div class="card" style="border-top: 4px solid #03045e;">
            <h3><i class="fas fa-funnel-dollar"></i> Status Comercial</h3>
            <form method="POST" style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.8rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_status">
                <div class="form-group" style="margin: 0;">
                    <label>Situação Atual no Funil:</label>
                    <select name="new_status" class="form-control" style="font-weight: 600;">
                        <?php foreach ($statusLabels as $sKey => $sLbl): ?>
                            <option value="<?= $sKey ?>" <?= $reservation['status'] === $sKey ? 'selected' : '' ?>><?= $sLbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn" style="width: 100%;">
                    <i class="fas fa-check"></i> Atualizar Status
                </button>
            </form>
        </div>

        <!-- Integração com EvoCRM -->
        <div class="card" style="border-top: 4px solid #28a745;">
            <h3><i class="fas fa-sync"></i> Integração com EvoCRM</h3>
            <div style="margin-top: 0.8rem; font-size: 0.9rem;">
                <?php if (!$crmQueue): ?>
                    <div class="alert alert-warning" style="margin: 0.5rem 0;">
                        <i class="fas fa-info-circle"></i> Ainda não enfileirado no CRM.
                    </div>
                <?php else: ?>
                    <div style="margin-bottom: 0.8rem;">
                        Status da Sincronização:
                        <?php if ($crmQueue['status'] === 'synced'): ?>
                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> Sincronizado</span>
                        <?php elseif ($crmQueue['status'] === 'error'): ?>
                            <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Erro de Sincronização</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><i class="fas fa-clock"></i> <?= ucfirst($crmQueue['status']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 0.8rem; color: #666; margin-bottom: 0.5rem;">
                        Tentativas: <strong><?= $crmQueue['attempts'] ?>/<?= $crmQueue['max_attempts'] ?></strong>
                    </div>
                    <?php if (!empty($crmQueue['last_error'])): ?>
                        <div style="background: #fff3cd; color: #856404; padding: 0.5rem; border-radius: 4px; font-size: 0.75rem; word-break: break-word; margin-bottom: 0.8rem;">
                            <strong>Último Erro:</strong> <?= htmlspecialchars($crmQueue['last_error']) ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <form method="POST" style="margin-top: 0.8rem;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="resync_crm">
                    <button type="submit" class="btn btn-secondary btn-sm" style="width: 100%;">
                        <i class="fas fa-redo"></i> Reenviar ao CRM Agora
                    </button>
                </form>
            </div>
        </div>

        <!-- UTMs e Origem -->
        <div class="card">
            <h3><i class="fas fa-bullhorn"></i> Origem e Rastreamento</h3>
            <table class="table" style="margin-top: 0.8rem; font-size: 0.85rem;">
                <tr><th>Origem:</th><td><code><?= htmlspecialchars($reservation['source']) ?></code></td></tr>
                <tr><th>UTM Source:</th><td><code><?= htmlspecialchars($reservation['utm_source'] ?: '-') ?></code></td></tr>
                <tr><th>UTM Medium:</th><td><code><?= htmlspecialchars($reservation['utm_medium'] ?: '-') ?></code></td></tr>
                <tr><th>UTM Campaign:</th><td><code><?= htmlspecialchars($reservation['utm_campaign'] ?: '-') ?></code></td></tr>
                <tr><th>UTM Content:</th><td><code><?= htmlspecialchars($reservation['utm_content'] ?: '-') ?></code></td></tr>
                <tr><th>UTM Term:</th><td><code><?= htmlspecialchars($reservation['utm_term'] ?: '-') ?></code></td></tr>
            </table>
        </div>

        <!-- Observações Internas -->
        <div class="card">
            <h3><i class="fas fa-comment-dots"></i> Observações Internas</h3>
            <form method="POST" style="margin-top: 0.8rem; display: flex; flex-direction: column; gap: 0.6rem;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_note">
                <textarea name="note" required class="form-control" rows="3" placeholder="Adicione uma nota sobre este interessado (ex: prefere contato após as 18h, tem interesse apenas no sábado...)" style="font-size: 0.85rem;"></textarea>
                <button type="submit" class="btn btn-sm" style="background: #03045e;">
                    <i class="fas fa-plus"></i> Salvar Nota
                </button>
            </form>

            <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 0.6rem;">
                <?php if (empty($notes)): ?>
                    <p style="color: #888; font-size: 0.85rem; margin: 0;">Nenhuma anotação cadastrada.</p>
                <?php else: foreach ($notes as $n): ?>
                    <div style="background: #f8f9fa; border: 1px solid #eee; border-radius: 4px; padding: 0.8rem; font-size: 0.85rem;">
                        <div style="display: flex; justify-content: space-between; color: #666; font-size: 0.75rem; margin-bottom: 0.3rem;">
                            <strong><?= htmlspecialchars($n['admin_name'] ?: 'Admin') ?></strong>
                            <span><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></span>
                        </div>
                        <div style="color: #333; line-height: 1.4;">
                            <?= nl2br(htmlspecialchars($n['note'])) ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>

    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
