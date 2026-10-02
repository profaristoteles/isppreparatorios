<?php
/**
 * Gerenciador de Campanhas de Marketing & Atribuição UTM
 * ISP Preparatórios
 */

require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';
require_once '../includes/MarketingTracker.php';

// Processar Ações (Salvar / Excluir)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
            $slug = trim($slug, '-');
        }

        $status = $_POST['status'] ?? 'ativa';
        $description = trim($_POST['description'] ?? '');
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $landingPage = trim($_POST['landing_page'] ?? '');
        $reservationCampaignId = !empty($_POST['reservation_campaign_id']) ? (int)$_POST['reservation_campaign_id'] : null;
        $pixelId = trim($_POST['pixel_id'] ?? '') ?: null;
        $primaryEvent = trim($_POST['primary_event'] ?? 'Lead');
        $defaultUtmSource = trim($_POST['default_utm_source'] ?? 'meta');
        $defaultUtmMedium = trim($_POST['default_utm_medium'] ?? 'paid_social');
        $defaultUtmCampaign = trim($_POST['default_utm_campaign'] ?? $slug);
        $defaultUtmContent = trim($_POST['default_utm_content'] ?? '');
        $defaultUtmTerm = trim($_POST['default_utm_term'] ?? '');

        if (empty($name) || empty($landingPage)) {
            $_SESSION['erro'] = "Nome da campanha e URL da Landing Page são obrigatórios.";
            header("Location: marketing-campanhas.php");
            exit;
        }

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE marketing_campaigns SET 
                    name = ?, slug = ?, status = ?, description = ?, start_date = ?, end_date = ?,
                    landing_page = ?, reservation_campaign_id = ?, pixel_id = ?, primary_event = ?,
                    default_utm_source = ?, default_utm_medium = ?, default_utm_campaign = ?,
                    default_utm_content = ?, default_utm_term = ?
                    WHERE id = ?");
                $stmt->execute([
                    $name, $slug, $status, $description, $startDate, $endDate,
                    $landingPage, $reservationCampaignId, $pixelId, $primaryEvent,
                    $defaultUtmSource, $defaultUtmMedium, $defaultUtmCampaign,
                    $defaultUtmContent, $defaultUtmTerm, $id
                ]);
                $_SESSION['msg'] = "Campanha #{$id} atualizada com sucesso!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO marketing_campaigns (
                    name, slug, status, description, start_date, end_date,
                    landing_page, reservation_campaign_id, pixel_id, primary_event,
                    default_utm_source, default_utm_medium, default_utm_campaign,
                    default_utm_content, default_utm_term
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $name, $slug, $status, $description, $startDate, $endDate,
                    $landingPage, $reservationCampaignId, $pixelId, $primaryEvent,
                    $defaultUtmSource, $defaultUtmMedium, $defaultUtmCampaign,
                    $defaultUtmContent, $defaultUtmTerm
                ]);
                $_SESSION['msg'] = "Nova campanha de marketing criada com sucesso!";
            }
        } catch (Exception $e) {
            $_SESSION['erro'] = "Erro ao salvar campanha: " . $e->getMessage();
        }
        header("Location: marketing-campanhas.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM marketing_campaigns WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['msg'] = "Campanha #{$id} excluída com sucesso!";
        } catch (Exception $e) {
            $_SESSION['erro'] = "Erro ao excluir campanha: " . $e->getMessage();
        }
        header("Location: marketing-campanhas.php");
        exit;
    }
}

// Buscar Campanhas de Marketing e Campanhas de Reserva para associação
$campaigns = $pdo->query("SELECT m.*, r.title AS res_title,
    (SELECT COUNT(*) FROM marketing_conversions c WHERE c.campaign_id = m.id OR c.campaign_slug = m.slug) AS conv_count,
    (SELECT COUNT(*) FROM marketing_conversions c WHERE (c.campaign_id = m.id OR c.campaign_slug = m.slug) AND c.event_name = 'Lead') AS lead_count
    FROM marketing_campaigns m 
    LEFT JOIN reservation_campaigns r ON m.reservation_campaign_id = r.id 
    ORDER BY m.id DESC")->fetchAll();

$resCampaigns = $pdo->query("SELECT id, title, slug FROM reservation_campaigns ORDER BY id DESC")->fetchAll();

require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h2 style="margin: 0; color: #03045e; font-size: 1.4rem;">
            <i class="fas fa-bullhorn" style="color: #ff8000; margin-right: 0.5rem;"></i>
            Campanhas de Marketing & Gerenciador UTM
        </h2>
        <p style="margin: 0.3rem 0 0; color: #666; font-size: 0.9rem;">
            Crie campanhas rastreadas, gere links com UTMs padrão e monitore conversões por canal.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <button type="button" onclick="openCampaignModal()" class="btn" style="background: #28a745;">
            <i class="fas fa-plus"></i> Nova Campanha
        </button>
        <a href="marketing-conversoes.php" class="btn btn-secondary btn-sm"><i class="fas fa-chart-line"></i> Ver Conversões</a>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden; margin-bottom: 2rem;">
    <table class="table" style="margin: 0;">
        <thead>
            <tr>
                <th style="width: 60px;">ID</th>
                <th>Campanha & Slug</th>
                <th>Status</th>
                <th>Landing Page</th>
                <th>Turma Vinculada</th>
                <th>Parâmetros UTM Padrão</th>
                <th style="text-align: center;">Conversões / Leads</th>
                <th style="width: 180px; text-align: right;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($campaigns)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2.5rem; color: #888;">
                        Nenhuma campanha de marketing cadastrada. Clique em "Nova Campanha".
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($campaigns as $camp): 
                    $fullLandingUrl = 'https://isppreparatorios.com.br' . (str_starts_with($camp['landing_page'], '/') ? $camp['landing_page'] : '/' . $camp['landing_page']);
                    $trackedUrl = $fullLandingUrl . '?utm_source=' . urlencode($camp['default_utm_source']) . 
                                  '&utm_medium=' . urlencode($camp['default_utm_medium']) . 
                                  '&utm_campaign=' . urlencode($camp['default_utm_campaign'] ?: $camp['slug']);
                    if (!empty($camp['default_utm_content'])) $trackedUrl .= '&utm_content=' . urlencode($camp['default_utm_content']);
                    if (!empty($camp['default_utm_term'])) $trackedUrl .= '&utm_term=' . urlencode($camp['default_utm_term']);
                ?>
                    <tr>
                        <td style="font-family: monospace; font-size: 0.85rem; color: #666;">#<?= $camp['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($camp['name']) ?></strong>
                            <div style="font-family: monospace; font-size: 0.8rem; color: #ff8000;">
                                <?= htmlspecialchars($camp['slug']) ?>
                            </div>
                        </td>
                        <td>
                            <?php 
                            $statusBadges = [
                                'ativa' => 'badge-success',
                                'rascunho' => 'badge-secondary',
                                'pausada' => 'badge-warning',
                                'encerrada' => 'badge-danger'
                            ];
                            ?>
                            <span class="badge <?= $statusBadges[$camp['status']] ?? 'badge-secondary' ?>">
                                <?= ucfirst($camp['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= htmlspecialchars($camp['landing_page']) ?>" target="_blank" style="color: #03045e; text-decoration: none; font-size: 0.85rem; font-weight: 500;">
                                <?= htmlspecialchars($camp['landing_page']) ?> <i class="fas fa-external-link-alt" style="font-size: 0.75rem; color: #888;"></i>
                            </a>
                        </td>
                        <td>
                            <?php if (!empty($camp['res_title'])): ?>
                                <span class="badge badge-info"><i class="fas fa-chalkboard-teacher"></i> <?= htmlspecialchars($camp['res_title']) ?></span>
                            <?php else: ?>
                                <span style="color: #aaa; font-size: 0.8rem;">Nenhuma turma direta</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-size: 0.78rem; line-height: 1.4; color: #475569; font-family: monospace;">
                                <div><strong>source:</strong> <?= htmlspecialchars($camp['default_utm_source']) ?></div>
                                <div><strong>medium:</strong> <?= htmlspecialchars($camp['default_utm_medium']) ?></div>
                                <div><strong>campaign:</strong> <?= htmlspecialchars($camp['default_utm_campaign'] ?: $camp['slug']) ?></div>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <span class="badge" style="background: #e2e8f0; color: #03045e; font-size: 0.85rem; font-weight: 700;">
                                <?= (int)$camp['conv_count'] ?> total
                            </span>
                            <div style="font-size: 0.78rem; color: #28a745; font-weight: 600; margin-top: 2px;">
                                <?= (int)$camp['lead_count'] ?> Leads
                            </div>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <button type="button" class="btn btn-sm" style="background: #ff8000;" onclick="copyTrackedUrl('<?= addslashes($trackedUrl) ?>')" title="Copiar URL Completa com UTMs para Anúncios">
                                <i class="fas fa-link"></i> Copiar URL
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick='editCampaign(<?= json_encode($camp, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG) ?>)' title="Editar Campanha">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="marketing-campanhas.php" method="POST" style="display: inline;" onsubmit="return confirm('Deseja realmente remover esta campanha de marketing?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $camp['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Excluir"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal de Cadastro / Edição de Campanha -->
<div id="modalCampanha" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 9999; align-items: center; justify-content: center; padding: 1.5rem;">
    <div style="background: #fff; width: 100%; max-width: 700px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;">
        <div style="padding: 1.2rem 1.5rem; background: #03045e; color: #fff; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalCampTitle" style="margin: 0; font-size: 1.2rem; font-weight: 700;">Nova Campanha de Marketing</h3>
            <button type="button" onclick="closeCampaignModal()" style="background: transparent; border: none; color: #fff; font-size: 1.2rem; cursor: pointer;">&times;</button>
        </div>
        
        <form action="marketing-campanhas.php" method="POST" style="padding: 1.5rem; overflow-y: auto;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="camp_id" value="0">

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="camp_name">Nome da Campanha *</label>
                    <input type="text" name="name" id="camp_name" class="form-control" required placeholder="Ex: Preparatório Intensivo — Concurso Prefeitura de Caxias-MA">
                </div>
                <div class="form-group">
                    <label for="camp_status">Status *</label>
                    <select name="status" id="camp_status" class="form-control" required>
                        <option value="ativa">Ativa</option>
                        <option value="rascunho">Rascunho</option>
                        <option value="pausada">Pausada</option>
                        <option value="encerrada">Encerrada</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="camp_slug">Slug / Identificador Único *</label>
                    <input type="text" name="slug" id="camp_slug" class="form-control" placeholder="Ex: preparatorio-caxias-2026" style="font-family: monospace;">
                    <small style="color: #666;">Usado internamente para agrupar conversões e UTMs.</small>
                </div>

                <div class="form-group">
                    <label for="camp_landing_page">URL da Landing Page *</label>
                    <input type="text" name="landing_page" id="camp_landing_page" class="form-control" required placeholder="/reserva/preparatorio-concurso-caxias-ma-legatus">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="camp_res_id">Vínculo com Turma de Reserva (Opcional)</label>
                    <select name="reservation_campaign_id" id="camp_res_id" class="form-control">
                        <option value="">Nenhuma turma direta vinculada</option>
                        <?php foreach ($resCampaigns as $rc): ?>
                            <option value="<?= $rc['id'] ?>"><?= htmlspecialchars($rc['title']) ?> (<?= htmlspecialchars($rc['slug']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="camp_primary_event">Evento Principal da Campanha</label>
                    <select name="primary_event" id="camp_primary_event" class="form-control">
                        <option value="Lead">Lead</option>
                        <option value="CompleteRegistration">CompleteRegistration</option>
                        <option value="ViewContent">ViewContent</option>
                        <option value="InitiateCheckout">InitiateCheckout</option>
                        <option value="Purchase">Purchase</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="camp_description">Descrição / Objetivo da Campanha</label>
                <textarea name="description" id="camp_description" class="form-control" rows="2" placeholder="Ex: Campanha de tráfego pago na Meta visando captura de cadastros na lista de interesse da turma de Caxias."></textarea>
            </div>

            <!-- Parâmetros UTM Padrão -->
            <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 1rem; margin-bottom: 1.2rem;">
                <h4 style="margin: 0 0 0.8rem; font-size: 0.95rem; color: #03045e;">
                    <i class="fas fa-tags" style="color: #ff8000;"></i> Parâmetros UTM Padrão para Anúncios
                </h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.8rem;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600;">utm_source</label>
                        <input type="text" name="default_utm_source" id="camp_utm_source" class="form-control" value="meta" style="font-size: 0.85rem;" placeholder="meta / instagram / google">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600;">utm_medium</label>
                        <input type="text" name="default_utm_medium" id="camp_utm_medium" class="form-control" value="paid_social" style="font-size: 0.85rem;" placeholder="paid_social / cpc / stories">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600;">utm_campaign</label>
                        <input type="text" name="default_utm_campaign" id="camp_utm_campaign" class="form-control" style="font-size: 0.85rem;" placeholder="preparatorio_caxias_2026">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; margin-top: 0.8rem;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600;">utm_content (Criativo / Variação)</label>
                        <input type="text" name="default_utm_content" id="camp_utm_content" class="form-control" style="font-size: 0.85rem;" placeholder="Ex: criativo_professores">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600;">utm_term (Público / Termo)</label>
                        <input type="text" name="default_utm_term" id="camp_utm_term" class="form-control" style="font-size: 0.85rem;" placeholder="Ex: professores_caxias">
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="camp_start_date">Data de Início</label>
                    <input type="date" name="start_date" id="camp_start_date" class="form-control">
                </div>
                <div class="form-group">
                    <label for="camp_end_date">Data de Término</label>
                    <input type="date" name="end_date" id="camp_end_date" class="form-control">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.8rem; margin-top: 1.5rem; border-top: 1px solid #eee; padding-top: 1rem;">
                <button type="button" onclick="closeCampaignModal()" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn" style="background: #03045e;"><i class="fas fa-save"></i> Salvar Campanha</button>
            </div>
        </form>
    </div>
</div>

<script>
function copyTrackedUrl(url) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(() => {
            alert("URL rastreada copiada com sucesso para a área de transferência!\n\nCole no Gerenciador de Anúncios da Meta.");
        });
    } else {
        prompt("Copie a URL rastreada abaixo:", url);
    }
}

function openCampaignModal() {
    document.getElementById('modalCampTitle').innerText = 'Nova Campanha de Marketing';
    document.getElementById('camp_id').value = '0';
    document.getElementById('camp_name').value = '';
    document.getElementById('camp_slug').value = '';
    document.getElementById('camp_status').value = 'ativa';
    document.getElementById('camp_landing_page').value = '';
    document.getElementById('camp_res_id').value = '';
    document.getElementById('camp_primary_event').value = 'Lead';
    document.getElementById('camp_description').value = '';
    document.getElementById('camp_utm_source').value = 'meta';
    document.getElementById('camp_utm_medium').value = 'paid_social';
    document.getElementById('camp_utm_campaign').value = '';
    document.getElementById('camp_utm_content').value = '';
    document.getElementById('camp_utm_term').value = '';
    document.getElementById('camp_start_date').value = '';
    document.getElementById('camp_end_date').value = '';

    document.getElementById('modalCampanha').style.display = 'flex';
}

function editCampaign(camp) {
    document.getElementById('modalCampTitle').innerText = 'Editar Campanha #' + camp.id;
    document.getElementById('camp_id').value = camp.id;
    document.getElementById('camp_name').value = camp.name || '';
    document.getElementById('camp_slug').value = camp.slug || '';
    document.getElementById('camp_status').value = camp.status || 'ativa';
    document.getElementById('camp_landing_page').value = camp.landing_page || '';
    document.getElementById('camp_res_id').value = camp.reservation_campaign_id || '';
    document.getElementById('camp_primary_event').value = camp.primary_event || 'Lead';
    document.getElementById('camp_description').value = camp.description || '';
    document.getElementById('camp_utm_source').value = camp.default_utm_source || 'meta';
    document.getElementById('camp_utm_medium').value = camp.default_utm_medium || 'paid_social';
    document.getElementById('camp_utm_campaign').value = camp.default_utm_campaign || '';
    document.getElementById('camp_utm_content').value = camp.default_utm_content || '';
    document.getElementById('camp_utm_term').value = camp.default_utm_term || '';
    document.getElementById('camp_start_date').value = camp.start_date || '';
    document.getElementById('camp_end_date').value = camp.end_date || '';

    document.getElementById('modalCampanha').style.display = 'flex';
}

function closeCampaignModal() {
    document.getElementById('modalCampanha').style.display = 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>
