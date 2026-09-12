<?php
/**
 * Dashboard de Métricas, Demanda e Conversão de Reservas
 * ISP Preparatórios
 */

require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';
require_once '../includes/reservation_service.php';

$campaignIdFilter = !empty($_GET['campaign_id']) ? (int)$_GET['campaign_id'] : 0;
$whereCamp = $campaignIdFilter > 0 ? " WHERE r.campaign_id = $campaignIdFilter " : " WHERE 1=1 ";

// 1. Cards Principais
$totalReservas = (int)$pdo->query("SELECT COUNT(*) FROM reservations r $whereCamp AND r.status != 'cancelado'")->fetchColumn();
$reservasHoje = (int)$pdo->query("SELECT COUNT(*) FROM reservations r $whereCamp AND r.status != 'cancelado' AND DATE(r.created_at) = CURDATE()")->fetchColumn();
$reservas7d = (int)$pdo->query("SELECT COUNT(*) FROM reservations r $whereCamp AND r.status != 'cancelado' AND r.created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn();
$reservas30d = (int)$pdo->query("SELECT COUNT(*) FROM reservations r $whereCamp AND r.status != 'cancelado' AND r.created_at >= NOW() - INTERVAL 30 DAY")->fetchColumn();

// Status comercial
$novos = (int)$pdo->query("SELECT COUNT(*) FROM reservations r $whereCamp AND r.status = 'nova'")->fetchColumn();
$contatados = (int)$pdo->query("SELECT COUNT(*) FROM reservations r $whereCamp AND r.status IN ('contatado', 'interessado')")->fetchColumn();
$aguardandoMatricula = (int)$pdo->query("SELECT COUNT(*) FROM reservations r $whereCamp AND r.status = 'aguardando_matricula'")->fetchColumn();
$matriculados = (int)$pdo->query("SELECT COUNT(*) FROM reservations r $whereCamp AND r.status = 'matriculado'")->fetchColumn();

// Indicadores de Produtividade Comercial
$reservasSemContato = (int)$pdo->query("SELECT COUNT(*) FROM reservations r $whereCamp AND r.status != 'cancelado' AND NOT EXISTS (SELECT 1 FROM reservation_history h WHERE h.reservation_id = r.id AND h.action_type = 'contato_comercial')")->fetchColumn();
$contatosPendentesHoje = (int)$pdo->query("SELECT COUNT(*) FROM reservations r $whereCamp AND r.status NOT IN ('cancelado', 'matriculado', 'sem_interesse') AND DATE(r.next_follow_up_at) = CURDATE()")->fetchColumn();
$acompanhamentosAtrasados = (int)$pdo->query("SELECT COUNT(*) FROM reservations r $whereCamp AND r.status NOT IN ('cancelado', 'matriculado', 'sem_interesse') AND r.next_follow_up_at IS NOT NULL AND r.next_follow_up_at < NOW()")->fetchColumn();

$taxaConversaoGlobal = $totalReservas > 0 ? round(($matriculados / $totalReservas) * 100, 1) : 0;

// 2. Demanda por Modalidade
$sqlMod = "SELECT preferred_modality, COUNT(*) as qtd,
           SUM(CASE WHEN status = 'matriculado' THEN 1 ELSE 0 END) as matriculados
           FROM reservations r $whereCamp AND status != 'cancelado'
           GROUP BY preferred_modality";
$modData = $pdo->query($sqlMod)->fetchAll();

$demandaPresencial = 0; $matrPresencial = 0;
$demandaOnline = 0; $matrOnline = 0;
$demandaAmbas = 0; $matrAmbas = 0;

foreach ($modData as $m) {
    if ($m['preferred_modality'] === 'presencial') {
        $demandaPresencial = (int)$m['qtd'];
        $matrPresencial = (int)$m['matriculados'];
    } elseif ($m['preferred_modality'] === 'online') {
        $demandaOnline = (int)$m['qtd'];
        $matrOnline = (int)$m['matriculados'];
    } elseif ($m['preferred_modality'] === 'ambas') {
        $demandaAmbas = (int)$m['qtd'];
        $matrAmbas = (int)$m['matriculados'];
    }
}

$percPresencial = $totalReservas > 0 ? round(($demandaPresencial / $totalReservas) * 100, 1) : 0;
$percOnline = $totalReservas > 0 ? round(($demandaOnline / $totalReservas) * 100, 1) : 0;
$percAmbas = $totalReservas > 0 ? round(($demandaAmbas / $totalReservas) * 100, 1) : 0;

// 3. Desempenho por Campanha
$sqlCamp = "SELECT c.id, c.title, c.slug, c.status as campaign_status,
            COUNT(r.id) as total,
            SUM(CASE WHEN r.preferred_modality = 'presencial' AND r.status != 'cancelado' THEN 1 ELSE 0 END) as presencial,
            SUM(CASE WHEN r.preferred_modality = 'online' AND r.status != 'cancelado' THEN 1 ELSE 0 END) as online,
            SUM(CASE WHEN r.preferred_modality = 'ambas' AND r.status != 'cancelado' THEN 1 ELSE 0 END) as ambas,
            SUM(CASE WHEN r.status = 'nova' THEN 1 ELSE 0 END) as novas,
            SUM(CASE WHEN r.status IN ('contatado', 'interessado') THEN 1 ELSE 0 END) as contatadas,
            SUM(CASE WHEN r.status = 'aguardando_matricula' THEN 1 ELSE 0 END) as aguardando,
            SUM(CASE WHEN r.status = 'matriculado' THEN 1 ELSE 0 END) as matriculados,
            SUM(CASE WHEN r.status = 'sem_interesse' THEN 1 ELSE 0 END) as sem_interesse
            FROM reservation_campaigns c
            LEFT JOIN reservations r ON r.campaign_id = c.id
            GROUP BY c.id
            ORDER BY total DESC";
$campRel = $pdo->query($sqlCamp)->fetchAll();

// 4. Cidades com Mais Interessados
$sqlCities = "SELECT COALESCE(NULLIF(city, ''), 'Não Informada') as cidade, 
              COALESCE(NULLIF(state, ''), '') as uf, 
              COUNT(*) as qtd 
              FROM reservations r $whereCamp AND status != 'cancelado'
              GROUP BY cidade, uf 
              ORDER BY qtd DESC LIMIT 8";
$cities = $pdo->query($sqlCities)->fetchAll();

// 5. Origem / UTMs
$sqlSources = "SELECT COALESCE(NULLIF(utm_source, ''), 'Direto / Site') as origem, COUNT(*) as qtd 
               FROM reservations r $whereCamp AND status != 'cancelado'
               GROUP BY origem 
               ORDER BY qtd DESC LIMIT 6";
$sources = $pdo->query($sqlSources)->fetchAll();

$allCampaigns = $pdo->query("SELECT id, title FROM reservation_campaigns ORDER BY id DESC")->fetchAll();

require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2><i class="fas fa-chart-pie"></i> Dashboard de Reservas & Inteligência de Demanda</h2>
        <p style="color: #666; margin: 0; font-size: 0.9rem;">Métricas de conversão e preferências de modalidade para apoiar decisões pedagógicas e comerciais.</p>
    </div>
    
    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <form method="GET" style="display: flex; gap: 0.5rem;">
            <select name="campaign_id" class="form-control" onchange="this.form.submit()" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                <option value="">Todas as Campanhas</option>
                <?php foreach ($allCampaigns as $ac): ?>
                    <option value="<?= $ac['id'] ?>" <?= $campaignIdFilter === (int)$ac['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ac['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <a href="gerenciar-reservas.php" class="btn btn-secondary btn-sm"><i class="fas fa-list"></i> Ver Lista de Reservas</a>
    </div>
</div>

<!-- Grid de Cards Principais -->
<div class="grid-cards" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="stat-card">
        <h3>Total de Reservas</h3>
        <div class="val"><?= $totalReservas ?></div>
        <small style="color: #666;">Interessados ativos</small>
    </div>
    <div class="stat-card" style="border-left-color: #28a745;">
        <h3>Reservas Hoje</h3>
        <div class="val" style="color: #28a745;"><?= $reservasHoje ?></div>
        <small style="color: #666;">Últimas 24 horas</small>
    </div>
    <div class="stat-card" style="border-left-color: #ff8000;">
        <h3>Últimos 7 Dias</h3>
        <div class="val" style="color: #ff8000;"><?= $reservas7d ?></div>
        <small style="color: #666;">Semana corrente</small>
    </div>
    <div class="stat-card" style="border-left-color: #00c3ff;">
        <h3>Últimos 30 Dias</h3>
        <div class="val" style="color: #00c3ff;"><?= $reservas30d ?></div>
        <small style="color: #666;">Mês corrente</small>
    </div>
</div>

<!-- Funil Comercial e Conversão -->
<div class="grid-cards" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 2rem;">
    <div class="stat-card" style="border-left-color: #17a2b8;">
        <h3>Novos Interessados</h3>
        <div class="val" style="color: #17a2b8;"><?= $novos ?></div>
        <small style="color: #666;">Aguardando primeiro contato</small>
    </div>
    <div class="stat-card" style="border-left-color: #ffc107;">
        <h3>Contatados</h3>
        <div class="val" style="color: #ff9800;"><?= $contatados ?></div>
        <small style="color: #666;">Em negociação / interessados</small>
    </div>
    <div class="stat-card" style="border-left-color: #6f42c1;">
        <h3>Aguardando Matrícula</h3>
        <div class="val" style="color: #6f42c1;"><?= $aguardandoMatricula ?></div>
        <small style="color: #666;">Aguardando abertura de link</small>
    </div>
    <div class="stat-card" style="border-left-color: #28a745; background: #f4fff6;">
        <h3>Matriculados</h3>
        <div class="val" style="color: #28a745;"><?= $matriculados ?> <small style="font-size: 1rem;">(<?= $taxaConversaoGlobal ?>%)</small></div>
        <small style="color: #28a745; font-weight: 600;">Conversão Global de Alunos</small>
    </div>
</div>

<!-- Acompanhamento e Produtividade Comercial -->
<div style="margin-bottom: 1.5rem;">
    <h3 style="margin: 0 0 0.8rem 0; color: #03045e; font-size: 1.1rem;">
        <i class="fas fa-headset"></i> Acompanhamento Comercial & Produtividade
    </h3>
    <div class="grid-cards" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <a href="gerenciar-reservas.php?status=nova" style="text-decoration: none; color: inherit;">
            <div class="stat-card" style="border-left-color: #e63946; cursor: pointer; transition: transform 0.2s;">
                <h3>Reservas Sem Contato</h3>
                <div class="val" style="color: #e63946;"><?= $reservasSemContato ?></div>
                <small style="color: #666;">Aguardando primeiro contato</small>
            </div>
        </a>
        <a href="gerenciar-reservas.php?follow_up_filter=hoje" style="text-decoration: none; color: inherit;">
            <div class="stat-card" style="border-left-color: #ff9800; cursor: pointer; transition: transform 0.2s;">
                <h3>Contatos Pendentes Hoje</h3>
                <div class="val" style="color: #ff9800;"><?= $contatosPendentesHoje ?></div>
                <small style="color: #666;">Agendados para hoje</small>
            </div>
        </a>
        <a href="gerenciar-reservas.php?follow_up_filter=vencido" style="text-decoration: none; color: inherit;">
            <div class="stat-card" style="border-left-color: #dc3545; cursor: pointer; transition: transform 0.2s;">
                <h3>Acompanhamentos Atrasados</h3>
                <div class="val" style="color: #dc3545;"><?= $acompanhamentosAtrasados ?></div>
                <small style="color: #dc3545; font-weight: 600;">Atrasados / Vencidos</small>
            </div>
        </a>
        <a href="gerenciar-reservas.php?status=aguardando_matricula" style="text-decoration: none; color: inherit;">
            <div class="stat-card" style="border-left-color: #6f42c1; cursor: pointer; transition: transform 0.2s;">
                <h3>Aguardando Matrícula</h3>
                <div class="val" style="color: #6f42c1;"><?= $aguardandoMatricula ?></div>
                <small style="color: #666;">Prontos para fechamento</small>
            </div>
        </a>
        <div class="stat-card" style="border-left-color: #28a745; background: #f8fff9;">
            <h3>Taxa de Conversão</h3>
            <div class="val" style="color: #28a745;"><?= $taxaConversaoGlobal ?>%</div>
            <small style="color: #28a745; font-weight: 600;"><?= $matriculados ?> de <?= $totalReservas ?> reservas</small>
        </div>
    </div>
</div>

<!-- Análise Estratégica de Modalidade (Demanda para Tomada de Decisão) -->
<div class="card" style="margin-bottom: 2rem; border-top: 4px solid #ff8000;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
        <div>
            <h3 style="margin: 0; color: #03045e;"><i class="fas fa-balance-scale"></i> Demanda por Modalidade: Presencial vs Online vs Ambas</h3>
            <p style="margin: 0; font-size: 0.85rem; color: #666;">Utilize esta métrica para decidir se vale a pena abrir turmas presenciais, online ou no modelo híbrido.</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-top: 1rem;">
        
        <!-- Presencial -->
        <div style="background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 8px; padding: 1.5rem; border-left: 5px solid #03045e;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <span style="font-weight: 700; color: #03045e; font-size: 1.1rem;"><i class="fas fa-chalkboard"></i> Presencial</span>
                <span class="badge badge-primary" style="font-size: 0.9rem;"><?= $percPresencial ?>% da Demanda</span>
            </div>
            <div style="font-size: 2.2rem; font-weight: 800; color: #03045e; margin: 0.5rem 0;"><?= $demandaPresencial ?></div>
            <div style="font-size: 0.85rem; color: #555;">
                Matriculados: <strong><?= $matrPresencial ?></strong> 
                <?php if ($demandaPresencial > 0): ?>
                    <span style="color: #28a745; font-weight: 600;">(<?= round(($matrPresencial / $demandaPresencial) * 100, 1) ?>% conv.)</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Online -->
        <div style="background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 8px; padding: 1.5rem; border-left: 5px solid #ff8000;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <span style="font-weight: 700; color: #ff8000; font-size: 1.1rem;"><i class="fas fa-laptop"></i> Online</span>
                <span class="badge badge-warning" style="font-size: 0.9rem;"><?= $percOnline ?>% da Demanda</span>
            </div>
            <div style="font-size: 2.2rem; font-weight: 800; color: #ff8000; margin: 0.5rem 0;"><?= $demandaOnline ?></div>
            <div style="font-size: 0.85rem; color: #555;">
                Matriculados: <strong><?= $matrOnline ?></strong> 
                <?php if ($demandaOnline > 0): ?>
                    <span style="color: #28a745; font-weight: 600;">(<?= round(($matrOnline / $demandaOnline) * 100, 1) ?>% conv.)</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ambas / Flexível -->
        <div style="background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 8px; padding: 1.5rem; border-left: 5px solid #28a745;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <span style="font-weight: 700; color: #28a745; font-size: 1.1rem;"><i class="fas fa-star"></i> Ambas as Modalidades</span>
                <span class="badge badge-success" style="font-size: 0.9rem;"><?= $percAmbas ?>% da Demanda</span>
            </div>
            <div style="font-size: 2.2rem; font-weight: 800; color: #28a745; margin: 0.5rem 0;"><?= $demandaAmbas ?></div>
            <div style="font-size: 0.85rem; color: #555;">
                Matriculados: <strong><?= $matrAmbas ?></strong> 
                <?php if ($demandaAmbas > 0): ?>
                    <span style="color: #28a745; font-weight: 600;">(<?= round(($matrAmbas / $demandaAmbas) * 100, 1) ?>% conv.)</span>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Tabela de Desempenho por Campanha -->
<div class="card" style="margin-bottom: 2rem;">
    <h3><i class="fas fa-bullhorn"></i> Desempenho Detalhado por Campanha</h3>
    <div style="overflow-x: auto;">
        <table class="table" style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th>Campanha / Turma</th>
                    <th>Total Reservas</th>
                    <th>Presencial</th>
                    <th>Online</th>
                    <th>Ambas</th>
                    <th>Novas</th>
                    <th>Contatadas</th>
                    <th>Aguardando</th>
                    <th>Matriculados</th>
                    <th>Conversão (%)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campRel)): ?>
                    <tr><td colspan="10" style="text-align: center; color: #888; padding: 2rem;">Nenhuma campanha cadastrada.</td></tr>
                <?php else: foreach ($campRel as $cr): 
                    $convRate = $cr['total'] > 0 ? round(($cr['matriculados'] / $cr['total']) * 100, 1) : 0;
                ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($cr['title']) ?></strong>
                            <div style="font-size: 0.75rem; color: #666;">
                                <code>/reserva/<?= htmlspecialchars($cr['slug']) ?></code>
                            </div>
                        </td>
                        <td><strong style="font-size: 1.1rem;"><?= $cr['total'] ?></strong></td>
                        <td><?= $cr['presencial'] ?></td>
                        <td><?= $cr['online'] ?></td>
                        <td><?= $cr['ambas'] ?></td>
                        <td><span class="badge badge-info"><?= $cr['novas'] ?></span></td>
                        <td><span class="badge badge-warning"><?= $cr['contatadas'] ?></span></td>
                        <td><span class="badge badge-secondary"><?= $cr['aguardando'] ?></span></td>
                        <td><strong style="color: #28a745; font-size: 1.05rem;"><?= $cr['matriculados'] ?></strong></td>
                        <td>
                            <div style="font-weight: 700; color: <?= $convRate >= 20 ? '#28a745' : '#ff8000' ?>;">
                                <?= $convRate ?>%
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Origem e Cidades com mais Interessados -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    
    <div class="card">
        <h3><i class="fas fa-map-marker-alt"></i> Concentração por Cidade / Região</h3>
        <table class="table" style="margin-top: 0.8rem;">
            <thead>
                <tr>
                    <th>Cidade / UF</th>
                    <th style="text-align: right;">Interessados</th>
                    <th style="text-align: right;">% Demanda</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cities)): ?>
                    <tr><td colspan="3" style="text-align: center; color: #888;">Nenhuma reserva registrada.</td></tr>
                <?php else: foreach ($cities as $ct): 
                    $ctPerc = $totalReservas > 0 ? round(($ct['qtd'] / $totalReservas) * 100, 1) : 0;
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($ct['cidade']) ?></strong> <?= !empty($ct['uf']) ? '/' . htmlspecialchars($ct['uf']) : '' ?></td>
                        <td style="text-align: right;"><strong><?= $ct['qtd'] ?></strong></td>
                        <td style="text-align: right; color: #666;"><?= $ctPerc ?>%</td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3><i class="fas fa-share-alt"></i> Origem dos Interessados (UTM Source)</h3>
        <table class="table" style="margin-top: 0.8rem;">
            <thead>
                <tr>
                    <th>Canal / Origem</th>
                    <th style="text-align: right;">Interessados</th>
                    <th style="text-align: right;">% Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sources)): ?>
                    <tr><td colspan="3" style="text-align: center; color: #888;">Nenhuma reserva registrada.</td></tr>
                <?php else: foreach ($sources as $src): 
                    $srcPerc = $totalReservas > 0 ? round(($src['qtd'] / $totalReservas) * 100, 1) : 0;
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($src['origem']) ?></strong></td>
                        <td style="text-align: right;"><strong><?= $src['qtd'] ?></strong></td>
                        <td style="text-align: right; color: #666;"><?= $srcPerc ?>%</td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
