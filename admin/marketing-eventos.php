<?php
/**
 * Gerenciador de Eventos de Conversão e Rastreamento
 * ISP Preparatórios
 */

require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';
require_once '../includes/MarketingTracker.php';

// Processar Ações (Salvar / Alternar / Excluir)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $eventName = trim($_POST['event_name'] ?? 'Lead');
        $triggerType = trim($_POST['trigger_type'] ?? 'backend_confirm');
        $triggerSelector = trim($_POST['trigger_selector'] ?? '');
        $campaignId = !empty($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : null;
        $pagePath = trim($_POST['page_path'] ?? '');
        $active = !empty($_POST['active']) ? 1 : 0;

        // Montar parâmetros estruturados com segurança (Sem JS livre)
        $contentName = trim($_POST['param_content_name'] ?? '');
        $contentCategory = trim($_POST['param_content_category'] ?? '');
        $statusParam = trim($_POST['param_status'] ?? '');
        $valueParam = trim($_POST['param_value'] ?? '');
        $currencyParam = trim($_POST['param_currency'] ?? 'BRL');

        $paramsArray = [];
        if (!empty($contentName)) $paramsArray['content_name'] = $contentName;
        if (!empty($contentCategory)) $paramsArray['content_category'] = $contentCategory;
        if (!empty($statusParam)) $paramsArray['status'] = $statusParam;
        if (!empty($valueParam) && is_numeric($valueParam)) {
            $paramsArray['value'] = (float)$valueParam;
            $paramsArray['currency'] = $currencyParam ?: 'BRL';
        }

        // Se o usuário passou JSON adicional, validar estritamente
        $rawJson = trim($_POST['parameters_json'] ?? '');
        if (!empty($rawJson)) {
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded)) {
                $paramsArray = array_merge($paramsArray, $decoded);
            }
        }
        $parametersJson = !empty($paramsArray) ? json_encode($paramsArray, JSON_UNESCAPED_UNICODE) : '{}';

        if (empty($name)) {
            $_SESSION['erro'] = "Por favor, informe o nome interno do evento.";
            header("Location: marketing-eventos.php");
            exit;
        }

        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE marketing_events SET 
                    name = ?, event_name = ?, trigger_type = ?, trigger_selector = ?, 
                    campaign_id = ?, page_path = ?, parameters_json = ?, active = ?
                    WHERE id = ?");
                $stmt->execute([$name, $eventName, $triggerType, $triggerSelector, $campaignId, $pagePath, $parametersJson, $active, $id]);
                $_SESSION['msg'] = "Evento #{$id} atualizado com sucesso!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO marketing_events (
                    name, event_name, trigger_type, trigger_selector, campaign_id, page_path, parameters_json, active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $eventName, $triggerType, $triggerSelector, $campaignId, $pagePath, $parametersJson, $active]);
                $_SESSION['msg'] = "Novo evento de rastreamento criado com sucesso!";
            }
        } catch (Exception $e) {
            $_SESSION['erro'] = "Erro ao salvar evento: " . $e->getMessage();
        }
        header("Location: marketing-eventos.php");
        exit;
    }

    if ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE marketing_events SET active = NOT active WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['msg'] = "Status do evento #{$id} alterado com sucesso!";
        } catch (Exception $e) {
            $_SESSION['erro'] = "Erro ao alterar status: " . $e->getMessage();
        }
        header("Location: marketing-eventos.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM marketing_events WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['msg'] = "Evento #{$id} excluído com sucesso!";
        } catch (Exception $e) {
            $_SESSION['erro'] = "Erro ao excluir evento: " . $e->getMessage();
        }
        header("Location: marketing-eventos.php");
        exit;
    }
}

// Buscar Eventos e Campanhas
$events = $pdo->query("SELECT e.*, c.name AS campaign_name 
    FROM marketing_events e 
    LEFT JOIN marketing_campaigns c ON e.campaign_id = c.id 
    ORDER BY e.active DESC, e.id DESC")->fetchAll();

$campaigns = $pdo->query("SELECT id, name, slug FROM marketing_campaigns ORDER BY name ASC")->fetchAll();

// Mapeamento de rótulos amigáveis de gatilhos
$triggerLabels = [
    'backend_confirm' => 'Confirmação do Backend (Mais Seguro)',
    'page_load'       => 'Carregamento da Página (PageView)',
    'form_submit'     => 'Sucesso de Formulário',
    'element_click'   => 'Clique em Elemento (Botão/Link)',
    'url_match'       => 'Acesso a URL Específica',
    'custom_action'   => 'Ação Personalizada do App'
];

require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h2 style="margin: 0; color: #03045e; font-size: 1.4rem;">
            <i class="fas fa-tags" style="color: #ff8000; margin-right: 0.5rem;"></i>
            Configuração de Eventos de Conversão
        </h2>
        <p style="margin: 0.3rem 0 0; color: #666; font-size: 0.9rem;">
            Defina gatilhos, parâmetros e regras de disparo para o Meta Pixel sem editar código PHP.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <button type="button" onclick="openEventModal()" class="btn" style="background: #28a745;">
            <i class="fas fa-plus"></i> Novo Evento
        </button>
        <a href="marketing-pixels.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Configuração do Pixel</a>
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <table class="table" style="margin: 0;">
        <thead>
            <tr>
                <th style="width: 60px;">ID</th>
                <th>Nome Interno</th>
                <th>Evento Meta</th>
                <th>Tipo de Gatilho</th>
                <th>Campanha / Página</th>
                <th>Parâmetros Estruturados</th>
                <th style="text-align: center;">Status</th>
                <th style="width: 140px; text-align: right;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($events)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2.5rem; color: #888;">
                        Nenhum evento de rastreamento configurado. Clique em "Novo Evento" para cadastrar.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($events as $evt): 
                    $params = json_decode($evt['parameters_json'] ?: '{}', true) ?: [];
                ?>
                    <tr>
                        <td style="font-family: monospace; font-size: 0.85rem; color: #666;">#<?= $evt['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($evt['name']) ?></strong>
                            <div style="font-size: 0.78rem; color: #888;">Criado em <?= date('d/m/Y H:i', strtotime($evt['created_at'])) ?></div>
                        </td>
                        <td>
                            <span class="badge" style="background: #03045e; color: #fff; font-size: 0.85rem;">
                                <?= htmlspecialchars($evt['event_name']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge" style="background: #e9ecef; color: #333;">
                                <?= htmlspecialchars($triggerLabels[$evt['trigger_type']] ?? $evt['trigger_type']) ?>
                            </span>
                            <?php if (!empty($evt['trigger_selector'])): ?>
                                <div style="font-size: 0.75rem; font-family: monospace; color: #666; margin-top: 2px;">
                                    <?= htmlspecialchars($evt['trigger_selector']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($evt['campaign_name'])): ?>
                                <span class="badge badge-info"><i class="fas fa-bullhorn"></i> <?= htmlspecialchars($evt['campaign_name']) ?></span>
                            <?php elseif (!empty($evt['page_path'])): ?>
                                <code style="font-size: 0.8rem;"><?= htmlspecialchars($evt['page_path']) ?></code>
                            <?php else: ?>
                                <span class="badge badge-secondary">Global (Todo o site)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($params)): ?>
                                <div style="font-size: 0.8rem; max-width: 250px; line-height: 1.4; color: #475569;">
                                    <?php foreach ($params as $k => $v): ?>
                                        <span style="font-weight: 600;"><?= htmlspecialchars($k) ?>:</span> <?= htmlspecialchars(is_array($v) ? json_encode($v) : $v) ?><br>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #aaa; font-size: 0.8rem;">Nenhum parâmetro</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <form action="marketing-eventos.php" method="POST" style="display: inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="id" value="<?= $evt['id'] ?>">
                                <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0;">
                                    <?php if ($evt['active']): ?>
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><i class="fas fa-pause"></i> Inativo</span>
                                    <?php endif; ?>
                                </button>
                            </form>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <button type="button" class="btn btn-sm btn-secondary" onclick='editEvent(<?= json_encode($evt, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG) ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="marketing-eventos.php" method="POST" style="display: inline;" onsubmit="return confirm('Deseja realmente remover esta configuração de evento?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $evt['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal de Cadastro / Edição de Evento -->
<div id="modalEvento" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.65); z-index: 9999; align-items: center; justify-content: center; padding: 1.5rem;">
    <div style="background: #fff; width: 100%; max-width: 650px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;">
        <div style="padding: 1.2rem 1.5rem; background: #03045e; color: #fff; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="modalTitle" style="margin: 0; font-size: 1.2rem; font-weight: 700;">Novo Evento de Conversão</h3>
            <button type="button" onclick="closeEventModal()" style="background: transparent; border: none; color: #fff; font-size: 1.2rem; cursor: pointer;">&times;</button>
        </div>
        
        <form action="marketing-eventos.php" method="POST" style="padding: 1.5rem; overflow-y: auto;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="evt_id" value="0">

            <div class="form-group">
                <label for="evt_name">Nome Interno de Identificação *</label>
                <input type="text" name="name" id="evt_name" class="form-control" required placeholder="Ex: Lead Reserva Caxias (Backend)">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="evt_event_name">Evento Meta Oficial *</label>
                    <select name="event_name" id="evt_event_name" class="form-control" required>
                        <option value="Lead">Lead (Geração de Lead / Reserva)</option>
                        <option value="PageView">PageView (Visualização de Página)</option>
                        <option value="ViewContent">ViewContent (Visualização de Conteúdo)</option>
                        <option value="CompleteRegistration">CompleteRegistration (Cadastro Completo)</option>
                        <option value="Contact">Contact (Contato WhatsApp/Email)</option>
                        <option value="InitiateCheckout">InitiateCheckout (Início de Matrícula)</option>
                        <option value="Purchase">Purchase (Compra / Matrícula Paga)</option>
                        <option value="Custom">Custom (Evento Personalizado)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="evt_trigger_type">Tipo de Gatilho *</label>
                    <select name="trigger_type" id="evt_trigger_type" class="form-control" required>
                        <option value="backend_confirm">Confirmação do Backend (Recomendado)</option>
                        <option value="page_load">Carregamento da Página</option>
                        <option value="form_submit">Sucesso de Formulário</option>
                        <option value="element_click">Clique em Elemento (Seletor CSS)</option>
                        <option value="url_match">Acesso a URL Específica</option>
                        <option value="custom_action">Ação Personalizada do App</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label for="evt_campaign_id">Campanha Associada (Opcional)</label>
                    <select name="campaign_id" id="evt_campaign_id" class="form-control">
                        <option value="">Todas / Global (Qualquer campanha)</option>
                        <?php foreach ($campaigns as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="evt_page_path">Caminho da Página / URL Alvo</label>
                    <input type="text" name="page_path" id="evt_page_path" class="form-control" placeholder="Ex: /reserva/preparatorio-concurso-caxias-ma-legatus ou *">
                </div>
            </div>

            <div class="form-group">
                <label for="evt_trigger_selector">Seletor CSS ou ID do Elemento (Para cliques ou forms)</label>
                <input type="text" name="trigger_selector" id="evt_trigger_selector" class="form-control" placeholder="Ex: #btnSubmitReserva ou .btn-whatsapp-lp">
            </div>

            <!-- Parâmetros Estruturados do Evento -->
            <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; padding: 1rem; margin-bottom: 1.2rem;">
                <h4 style="margin: 0 0 0.8rem; font-size: 0.95rem; color: #03045e;">
                    <i class="fas fa-sliders-h"></i> Parâmetros do Evento Meta (Estruturados)
                </h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem;">
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600;">content_name (Nome do Conteúdo)</label>
                        <input type="text" name="param_content_name" id="param_content_name" class="form-control" style="font-size: 0.85rem;" placeholder="Ex: Preparatório Intensivo Caxias">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600;">content_category (Categoria)</label>
                        <input type="text" name="param_content_category" id="param_content_category" class="form-control" style="font-size: 0.85rem;" placeholder="Ex: Preparatório">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600;">status (Etapa)</label>
                        <input type="text" name="param_status" id="param_status" class="form-control" style="font-size: 0.85rem;" placeholder="Ex: pre_cadastro">
                    </div>
                    <div>
                        <label style="font-size: 0.8rem; font-weight: 600;">value (Valor se aplicável)</label>
                        <input type="number" step="0.01" name="param_value" id="param_value" class="form-control" style="font-size: 0.85rem;" placeholder="0.00">
                    </div>
                </div>

                <div style="margin-top: 0.8rem;">
                    <label style="font-size: 0.8rem; font-weight: 600;">Parâmetros Extras em JSON Seguro (Opcional)</label>
                    <textarea name="parameters_json" id="evt_parameters_json" class="form-control" rows="2" style="font-family: monospace; font-size: 0.8rem;" placeholder='{"custom_key": "custom_val"}'></textarea>
                </div>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 0.6rem;">
                <input type="checkbox" name="active" id="evt_active" value="1" checked style="width: 16px; height: 16px;">
                <label for="evt_active" style="margin: 0; font-weight: 600; cursor: pointer;">Evento Ativo no Sistema</label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.8rem; margin-top: 1.5rem; border-top: 1px solid #eee; padding-top: 1rem;">
                <button type="button" onclick="closeEventModal()" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn" style="background: #03045e;"><i class="fas fa-save"></i> Salvar Evento</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEventModal() {
    document.getElementById('modalTitle').innerText = 'Novo Evento de Conversão';
    document.getElementById('evt_id').value = '0';
    document.getElementById('evt_name').value = '';
    document.getElementById('evt_event_name').value = 'Lead';
    document.getElementById('evt_trigger_type').value = 'backend_confirm';
    document.getElementById('evt_campaign_id').value = '';
    document.getElementById('evt_page_path').value = '';
    document.getElementById('evt_trigger_selector').value = '';
    document.getElementById('param_content_name').value = '';
    document.getElementById('param_content_category').value = '';
    document.getElementById('param_status').value = '';
    document.getElementById('param_value').value = '';
    document.getElementById('evt_parameters_json').value = '';
    document.getElementById('evt_active').checked = true;

    const modal = document.getElementById('modalEvento');
    modal.style.display = 'flex';
}

function editEvent(evt) {
    document.getElementById('modalTitle').innerText = 'Editar Evento #' + evt.id;
    document.getElementById('evt_id').value = evt.id;
    document.getElementById('evt_name').value = evt.name || '';
    document.getElementById('evt_event_name').value = evt.event_name || 'Lead';
    document.getElementById('evt_trigger_type').value = evt.trigger_type || 'backend_confirm';
    document.getElementById('evt_campaign_id').value = evt.campaign_id || '';
    document.getElementById('evt_page_path').value = evt.page_path || '';
    document.getElementById('evt_trigger_selector').value = evt.trigger_selector || '';
    document.getElementById('evt_active').checked = parseInt(evt.active) === 1;

    let params = {};
    try {
        params = JSON.parse(evt.parameters_json || '{}');
    } catch(e) {}

    document.getElementById('param_content_name').value = params.content_name || '';
    document.getElementById('param_content_category').value = params.content_category || '';
    document.getElementById('param_status').value = params.status || '';
    document.getElementById('param_value').value = params.value || '';

    // Remove campos mapeados dos extras
    delete params.content_name;
    delete params.content_category;
    delete params.status;
    delete params.value;
    delete params.currency;

    document.getElementById('evt_parameters_json').value = Object.keys(params).length > 0 ? JSON.stringify(params, null, 2) : '';

    const modal = document.getElementById('modalEvento');
    modal.style.display = 'flex';
}

function closeEventModal() {
    document.getElementById('modalEvento').style.display = 'none';
}
</script>

<?php require_once 'includes/footer.php'; ?>
