<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/header.php';

$tipo = $_GET['tipo'] ?? 'todos';
$eventId = !empty($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$busca = trim($_GET['busca'] ?? '');

// Estatísticas
$totEventos = 0;
$totGeral = 0;
try {
    $totEventos = $pdo->query("SELECT COUNT(*) FROM event_registrations")->fetchColumn() ?: 0;
    $totGeral = $pdo->query("SELECT COUNT(*) FROM inscricoes")->fetchColumn() ?: 0;
} catch (Exception $e) {}

// Lista de Eventos para Filtro
$eventosFiltro = [];
try {
    $eventosFiltro = $pdo->query("SELECT id, title FROM eventos ORDER BY event_date DESC")->fetchAll();
} catch (Exception $e) {}

// Consulta de Inscrições em Eventos
$eventRegistrations = [];
try {
    $whereEvt = " WHERE 1=1";
    $paramsEvt = [];

    if ($eventId > 0) {
        $whereEvt .= " AND r.event_id = ?";
        $paramsEvt[] = $eventId;
    }

    if (!empty($busca)) {
        $whereEvt .= " AND (r.name LIKE ? OR r.email LIKE ? OR r.phone LIKE ?)";
        $paramsEvt[] = "%$busca%";
        $paramsEvt[] = "%$busca%";
        $paramsEvt[] = "%$busca%";
    }

    $stmtEvt = $pdo->prepare("SELECT r.*, e.title as event_title, e.event_date FROM event_registrations r LEFT JOIN eventos e ON r.event_id = e.id $whereEvt ORDER BY r.id DESC LIMIT 100");
    $stmtEvt->execute($paramsEvt);
    $eventRegistrations = $stmtEvt->fetchAll();
} catch (Exception $e) {}

// Consulta de Inscrições Gerais / Contatos
$generalInscricoes = [];
try {
    $whereGen = " WHERE 1=1";
    $paramsGen = [];

    if (!empty($busca)) {
        $whereGen .= " AND (i.name LIKE ? OR i.email LIKE ? OR i.phone LIKE ?)";
        $paramsGen[] = "%$busca%";
        $paramsGen[] = "%$busca%";
        $paramsGen[] = "%$busca%";
    }

    $stmtGen = $pdo->prepare("SELECT i.*, c.title as curso FROM inscricoes i LEFT JOIN cursos c ON i.course_id = c.id $whereGen ORDER BY i.id DESC LIMIT 100");
    $stmtGen->execute($paramsGen);
    $generalInscricoes = $stmtGen->fetchAll();
} catch (Exception $e) {}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 10px;">
    <h2><i class="fas fa-user-plus"></i> Inscrições &amp; Contatos</h2>
    <div style="display: flex; gap: 8px;">
        <a href="gerenciar-reservas.php" class="btn btn-outline" style="border-color: #ff8000; color: #ff8000;"><i class="fas fa-clipboard-list"></i> Ver Reservas de Turmas</a>
    </div>
</div>

<!-- Cards de Resumo -->
<div class="grid-cards" style="margin-bottom: 1.5rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
    <div class="stat-card" style="border-left-color: #ff8000;">
        <h3>Inscrições em Eventos</h3>
        <p class="val"><?= number_format($totEventos) ?></p>
    </div>
    <div class="stat-card" style="border-left-color: #03045e;">
        <h3>Contatos Gerais / Site</h3>
        <p class="val"><?= number_format($totGeral) ?></p>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 1.5rem;">
    <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
        <div style="flex: 2; min-width: 200px;">
            <input type="text" name="busca" class="form-control" placeholder="Buscar por Nome, E-mail ou Telefone..." value="<?= htmlspecialchars($busca) ?>">
        </div>
        <div style="flex: 1; min-width: 180px;">
            <select name="event_id" class="form-control">
                <option value="">Todos os Eventos</option>
                <?php foreach($eventosFiltro as $ef): ?>
                    <option value="<?= $ef['id'] ?>" <?= $eventId == $ef['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ef['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn"><i class="fas fa-search"></i> Filtrar</button>
        <?php if (!empty($busca) || $eventId > 0): ?>
            <a href="inscricoes.php" class="btn btn-outline" style="color: #666; border-color: #ccc;">Limpar</a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabela 1: Inscrições em Eventos e Aulões -->
<div class="card" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 style="margin: 0; color: #ff8000; font-size: 1.15rem;"><i class="fas fa-calendar-check"></i> Inscrições em Eventos &amp; Aulões (<?= count($eventRegistrations) ?>)</h3>
    </div>

    <?php if(empty($eventRegistrations)): ?>
        <p style="color: #777; padding: 1rem 0;">Nenhuma inscrição em evento encontrada com os filtros selecionados.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Protocolo</th>
                        <th>Participante</th>
                        <th>Contato</th>
                        <th>Evento</th>
                        <th>Modalidade</th>
                        <th>Data Inscrição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($eventRegistrations as $er): 
                        $cleanPhone = preg_replace('/[^0-9]/', '', $er['phone']);
                        if (strlen($cleanPhone) === 10 || strlen($cleanPhone) === 11) {
                            $cleanPhone = '55' . $cleanPhone;
                        }
                        $msgZap = "Olá {$er['name']}! Vimos sua inscrição no evento *{$er['event_title']}* do ISP Preparatórios.";
                        $zapLink = "https://wa.me/{$cleanPhone}?text=" . urlencode($msgZap);
                    ?>
                    <tr>
                        <td>
                            <strong style="color: #03045e; font-family: monospace; font-size: 0.95rem;">
                                EVT-<?= str_pad($er['id'], 6, '0', STR_PAD_LEFT) ?>
                            </strong>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($er['name']) ?></strong>
                        </td>
                        <td>
                            <div style="font-size: 0.88rem;"><?= htmlspecialchars($er['email']) ?></div>
                            <div style="font-size: 0.85rem; color: #555;"><?= htmlspecialchars($er['phone']) ?></div>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($er['event_title'] ?? 'Evento Removido') ?></strong>
                            <?php if(!empty($er['event_date'])): ?>
                                <div style="font-size: 0.8rem; color: #888;">Data: <?= date('d/m/Y H:i', strtotime($er['event_date'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($er['modality'] === 'presencial'): ?>
                                <span class="badge" style="background: rgba(255,128,0,0.15); color: #ff8000; border: 1px solid #ff8000; font-weight: bold;">🏫 Presencial</span>
                            <?php elseif ($er['modality'] === 'online'): ?>
                                <span class="badge" style="background: rgba(0,204,255,0.15); color: #0088cc; border: 1px solid #0088cc; font-weight: bold;">💻 Online</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Geral</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($er['created_at'])) ?></td>
                        <td>
                            <?php if (!empty($cleanPhone)): ?>
                                <a href="<?= $zapLink ?>" target="_blank" class="btn" style="background: #25d366; color: #fff; padding: 6px 12px; font-size: 0.82rem; font-weight: bold; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; text-decoration: none;">
                                    <i class="fab fa-whatsapp"></i> Chamar
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Tabela 2: Contatos e Inscrições Gerais do Site -->
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 style="margin: 0; color: #03045e; font-size: 1.15rem;"><i class="fas fa-envelope-open-text"></i> Contatos e Inscrições Gerais do Site (<?= count($generalInscricoes) ?>)</h3>
    </div>

    <?php if(empty($generalInscricoes)): ?>
        <p style="color: #777; padding: 1rem 0;">Nenhum contato encontrado.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Contato</th>
                        <th>Interesse / Mensagem</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($generalInscricoes as $i): 
                        $cleanPhone = preg_replace('/[^0-9]/', '', $i['phone']);
                        if (strlen($cleanPhone) === 10 || strlen($cleanPhone) === 11) {
                            $cleanPhone = '55' . $cleanPhone;
                        }
                        $msgZap = "Olá {$i['name']}! Recebemos sua mensagem no site do ISP Preparatórios.";
                        $zapLink = "https://wa.me/{$cleanPhone}?text=" . urlencode($msgZap);
                    ?>
                    <tr>
                        <td>#<?= $i['id'] ?></td>
                        <td><strong><?= htmlspecialchars($i['name']) ?></strong></td>
                        <td>
                            <div style="font-size: 0.88rem;"><?= htmlspecialchars($i['email']) ?></div>
                            <div style="font-size: 0.85rem; color: #555;"><?= htmlspecialchars($i['phone']) ?></div>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($i['curso'] ?? 'Contato Geral') ?></strong>
                            <?php if(!empty($i['message'])): ?>
                                <div style="font-size: 0.82rem; color: #666; margin-top: 4px; max-width: 350px;">
                                    <?= nl2br(htmlspecialchars($i['message'])) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($i['created_at'])) ?></td>
                        <td>
                            <?php if (!empty($cleanPhone)): ?>
                                <a href="<?= $zapLink ?>" target="_blank" class="btn" style="background: #25d366; color: #fff; padding: 6px 12px; font-size: 0.82rem; font-weight: bold; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; text-decoration: none;">
                                    <i class="fab fa-whatsapp"></i> Chamar
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
