<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';

// Totais Principais
$total_canais = $pdo->query("SELECT COUNT(*) FROM free_channels WHERE active=1 AND deleted_at IS NULL")->fetchColumn();
$total_pub = $pdo->query("SELECT COUNT(*) FROM free_videos WHERE status='publicado' AND active=1 AND deleted_at IS NULL")->fetchColumn();
$total_rascunho = $pdo->query("SELECT COUNT(*) FROM free_videos WHERE status='rascunho' AND active=1 AND deleted_at IS NULL")->fetchColumn();
$total_materiais = $pdo->query("SELECT COUNT(*) FROM free_materials WHERE active=1 AND deleted_at IS NULL")->fetchColumn();
$total_leads = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
$total_downloads = $pdo->query("SELECT COUNT(*) FROM lead_downloads")->fetchColumn();
$total_conflitos = $pdo->query("SELECT COUNT(*) FROM lead_conflicts WHERE status='pending'")->fetchColumn();
$total_fila_pend = $pdo->query("SELECT COUNT(*) FROM integration_queue WHERE status='pending'")->fetchColumn();
$total_fila_erro = $pdo->query("SELECT COUNT(*) FROM integration_queue WHERE status='error'")->fetchColumn();

// Métricas de Tempo
$leads_hoje = $pdo->query("SELECT COUNT(*) FROM leads WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$leads_7d = $pdo->query("SELECT COUNT(*) FROM leads WHERE created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn();
$leads_30d = $pdo->query("SELECT COUNT(*) FROM leads WHERE created_at >= NOW() - INTERVAL 30 DAY")->fetchColumn();

$dl_hoje = $pdo->query("SELECT COUNT(*) FROM lead_downloads WHERE DATE(downloaded_at) = CURDATE()")->fetchColumn();
$dl_7d = $pdo->query("SELECT COUNT(*) FROM lead_downloads WHERE downloaded_at >= NOW() - INTERVAL 7 DAY")->fetchColumn();
$dl_30d = $pdo->query("SELECT COUNT(*) FROM lead_downloads WHERE downloaded_at >= NOW() - INTERVAL 30 DAY")->fetchColumn();

// Funil de Analytics (free_video_events)
$evt_views = $pdo->query("SELECT COUNT(*) FROM free_video_events WHERE event_type='video_accessed'")->fetchColumn();
$evt_requests = $pdo->query("SELECT COUNT(*) FROM free_video_events WHERE event_type='material_requested'")->fetchColumn();
$evt_leads = $pdo->query("SELECT COUNT(*) FROM free_video_events WHERE event_type='lead_captured'")->fetchColumn();
$evt_downloads = $pdo->query("SELECT COUNT(*) FROM free_video_events WHERE event_type='material_downloaded'")->fetchColumn();

// Taxas de conversão
$taxa_view_req = $evt_views > 0 ? round(($evt_requests / $evt_views) * 100, 1) : 0;
$taxa_req_lead = $evt_requests > 0 ? round(($evt_leads / $evt_requests) * 100, 1) : 0;
$taxa_lead_dl  = $evt_leads > 0 ? round(($evt_downloads / $evt_leads) * 100, 1) : 0;

// Top 5 Videos por Visualização
$top_views = $pdo->query("SELECT v.title, COUNT(e.id) as total FROM free_video_events e JOIN free_videos v ON e.video_id = v.id WHERE e.event_type='video_accessed' GROUP BY e.video_id ORDER BY total DESC LIMIT 5")->fetchAll();

// Top 5 Videos por Leads
$top_video_leads = $pdo->query("SELECT v.title, COUNT(DISTINCT e.lead_id) as total FROM free_video_events e JOIN free_videos v ON e.video_id = v.id WHERE e.event_type='lead_captured' AND e.lead_id IS NOT NULL GROUP BY e.video_id ORDER BY total DESC LIMIT 5")->fetchAll();

// Top 5 Bancas por Leads
$top_bancas = $pdo->query("SELECT b.name, COUNT(DISTINCT e.lead_id) as total FROM free_video_events e JOIN free_videos v ON e.video_id = v.id JOIN free_boards b ON v.board_id = b.id WHERE e.event_type='lead_captured' AND e.lead_id IS NOT NULL GROUP BY v.board_id ORDER BY total DESC LIMIT 5")->fetchAll();

// Top 5 Disciplinas por Leads
$top_disciplinas = $pdo->query("SELECT d.name, COUNT(DISTINCT e.lead_id) as total FROM free_video_events e JOIN free_videos v ON e.video_id = v.id JOIN free_disciplines d ON v.discipline_id = d.id WHERE e.event_type='lead_captured' AND e.lead_id IS NOT NULL GROUP BY v.discipline_id ORDER BY total DESC LIMIT 5")->fetchAll();

// Top 5 Professores por Leads
$top_professores = $pdo->query("SELECT t.name, COUNT(DISTINCT e.lead_id) as total FROM free_video_events e JOIN free_videos v ON e.video_id = v.id JOIN free_teachers t ON v.teacher_id = t.id WHERE e.event_type='lead_captured' AND e.lead_id IS NOT NULL GROUP BY v.teacher_id ORDER BY total DESC LIMIT 5")->fetchAll();

require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h2><i class="fas fa-graduation-cap"></i> Dashboard - Aulas Gratuitas</h2>
    <a href="gerenciar-videoaulas.php" class="btn"><i class="fas fa-plus"></i> Nova Videoaula</a>
</div>

<!-- Indicadores Rápidos -->
<div class="grid-cards">
    <div class="stat-card" style="border-left-color: #03045e;">
        <h3>Canais Ativos</h3>
        <p class="val"><?= number_format($total_canais) ?></p>
    </div>
    <div class="stat-card" style="border-left-color: #28a745;">
        <h3>Aulas Publicadas</h3>
        <p class="val"><?= number_format($total_pub) ?></p>
    </div>
    <div class="stat-card" style="border-left-color: #ffc107;">
        <h3>Aulas Rascunho</h3>
        <p class="val"><?= number_format($total_rascunho) ?></p>
    </div>
    <div class="stat-card" style="border-left-color: #17a2b8;">
        <h3>Materiais PDF</h3>
        <p class="val"><?= number_format($total_materiais) ?></p>
    </div>
    <div class="stat-card" style="border-left-color: #ff8000;">
        <h3>Total de Leads</h3>
        <p class="val"><?= number_format($total_leads) ?></p>
    </div>
    <div class="stat-card" style="border-left-color: #6f42c1;">
        <h3>Downloads Efetivados</h3>
        <p class="val"><?= number_format($total_downloads) ?></p>
    </div>
    <div class="stat-card" style="border-left-color: #dc3545;">
        <h3>Conflitos Pendentes</h3>
        <p class="val"><?= number_format($total_conflitos) ?></p>
    </div>
    <div class="stat-card" style="border-left-color: <?= $total_fila_erro > 0 ? '#dc3545' : '#6c757d' ?>;">
        <h3>Fila Integração</h3>
        <p class="val"><?= number_format($total_fila_pend) ?> <small style="font-size: 0.9rem; color: #dc3545;">(<?= $total_fila_erro ?> erros)</small></p>
    </div>
</div>

<!-- Métricas Temporais -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
    <div class="card">
        <h3><i class="fas fa-user-plus"></i> Captura de Leads</h3>
        <table class="table" style="margin-top: 0.5rem;">
            <tr><th>Período</th><th>Total de Leads</th></tr>
            <tr><td>Hoje</td><td><strong><?= number_format($leads_hoje) ?></strong></td></tr>
            <tr><td>Últimos 7 dias</td><td><strong><?= number_format($leads_7d) ?></strong></td></tr>
            <tr><td>Últimos 30 dias</td><td><strong><?= number_format($leads_30d) ?></strong></td></tr>
        </table>
    </div>

    <div class="card">
        <h3><i class="fas fa-download"></i> Downloads de Materiais</h3>
        <table class="table" style="margin-top: 0.5rem;">
            <tr><th>Período</th><th>Total de Downloads</th></tr>
            <tr><td>Hoje</td><td><strong><?= number_format($dl_hoje) ?></strong></td></tr>
            <tr><td>Últimos 7 dias</td><td><strong><?= number_format($dl_7d) ?></strong></td></tr>
            <tr><td>Últimos 30 dias</td><td><strong><?= number_format($dl_30d) ?></strong></td></tr>
        </table>
    </div>
</div>

<!-- Funil de Conversão -->
<div class="card" style="margin-bottom: 1.5rem;">
    <h3><i class="fas fa-filter"></i> Funil de Conversão da Jornada</h3>
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; text-align: center; margin-top: 1rem;">
        <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; border: 1px solid #eee;">
            <div style="font-size: 0.8rem; color: #666; font-weight: 600;">1. VISUALIZAÇÕES</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #03045e; margin: 0.3rem 0;"><?= number_format($evt_views) ?></div>
            <span class="badge badge-secondary">100%</span>
        </div>
        <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; border: 1px solid #eee;">
            <div style="font-size: 0.8rem; color: #666; font-weight: 600;">2. SOLICITAÇÕES</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #03045e; margin: 0.3rem 0;"><?= number_format($evt_requests) ?></div>
            <span class="badge badge-info"><?= $taxa_view_req ?>% das views</span>
        </div>
        <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; border: 1px solid #eee;">
            <div style="font-size: 0.8rem; color: #666; font-weight: 600;">3. LEADS CAPTURADOS</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #ff8000; margin: 0.3rem 0;"><?= number_format($evt_leads) ?></div>
            <span class="badge badge-warning"><?= $taxa_req_lead ?>% das solicitações</span>
        </div>
        <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; border: 1px solid #eee;">
            <div style="font-size: 0.8rem; color: #666; font-weight: 600;">4. DOWNLOADS EFETIVOS</div>
            <div style="font-size: 1.5rem; font-weight: 700; color: #28a745; margin: 0.3rem 0;"><?= number_format($evt_downloads) ?></div>
            <span class="badge badge-success"><?= $taxa_lead_dl ?>% dos leads</span>
        </div>
    </div>
</div>

<!-- Rankings TOP 5 -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <div class="card">
        <h3><i class="fas fa-award"></i> Top Videoaulas por Visualizações</h3>
        <table class="table" style="margin-top: 0.5rem;">
            <thead><tr><th>Videoaula</th><th style="width: 80px;">Views</th></tr></thead>
            <tbody>
                <?php if(empty($top_views)): ?>
                    <tr><td colspan="2" style="color: #888;">Nenhuma visualização registrada ainda.</td></tr>
                <?php else: foreach($top_views as $row): ?>
                    <tr><td><?= htmlspecialchars($row['title']) ?></td><td><strong><?= number_format($row['total']) ?></strong></td></tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3><i class="fas fa-users"></i> Top Videoaulas por Leads</h3>
        <table class="table" style="margin-top: 0.5rem;">
            <thead><tr><th>Videoaula</th><th style="width: 80px;">Leads</th></tr></thead>
            <tbody>
                <?php if(empty($top_video_leads)): ?>
                    <tr><td colspan="2" style="color: #888;">Nenhum lead registrado em aulas ainda.</td></tr>
                <?php else: foreach($top_video_leads as $row): ?>
                    <tr><td><?= htmlspecialchars($row['title']) ?></td><td><strong><?= number_format($row['total']) ?></strong></td></tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3><i class="fas fa-building"></i> Top Bancas por Leads</h3>
        <table class="table" style="margin-top: 0.5rem;">
            <thead><tr><th>Banca</th><th style="width: 80px;">Leads</th></tr></thead>
            <tbody>
                <?php if(empty($top_bancas)): ?>
                    <tr><td colspan="2" style="color: #888;">Nenhuma banca com leads ainda.</td></tr>
                <?php else: foreach($top_bancas as $row): ?>
                    <tr><td><?= htmlspecialchars($row['name']) ?></td><td><strong><?= number_format($row['total']) ?></strong></td></tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3><i class="fas fa-chalkboard-teacher"></i> Top Professores por Leads</h3>
        <table class="table" style="margin-top: 0.5rem;">
            <thead><tr><th>Professor(a)</th><th style="width: 80px;">Leads</th></tr></thead>
            <tbody>
                <?php if(empty($top_professores)): ?>
                    <tr><td colspan="2" style="color: #888;">Nenhum professor com leads ainda.</td></tr>
                <?php else: foreach($top_professores as $row): ?>
                    <tr><td><?= htmlspecialchars($row['name']) ?></td><td><strong><?= number_format($row['total']) ?></strong></td></tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
