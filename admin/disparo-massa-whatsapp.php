<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once '../includes/evocrm_service.php';
require_once 'includes/admin_security.php';

$crm_cfg = EvoCRMService::getConfig($pdo);
$config = get_config($pdo);

$isEvolutionConfigured = !empty($crm_cfg['apiUrl']) && !empty($crm_cfg['apiKey']);
$channels = $pdo->query("SELECT id, name FROM free_channels WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();
$reservationCampaigns = $pdo->query("SELECT id, title FROM reservation_campaigns ORDER BY title ASC")->fetchAll();

require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 10px;">
    <div>
        <h2><i class="fab fa-whatsapp" style="color: #25d366;"></i> Disparo de Mensagens em Massa via Evolution API</h2>
        <p style="color: #666; margin: 0.3rem 0 0 0; font-size: 0.9rem;">
            Envie comunicados, avisos de novas turmas, novidades e promoções para sua base de leads e alunos cadastrados.
        </p>
    </div>
    <div>
        <?php if ($isEvolutionConfigured): ?>
            <span class="badge badge-success" style="font-size: 0.85rem; padding: 8px 14px;">
                <i class="fas fa-check-circle"></i> Evolution API Conectada (<?= htmlspecialchars($crm_cfg['instance'] ?: 'isp') ?>)
            </span>
        <?php else: ?>
            <a href="configuracoes.php" class="badge badge-warning" style="font-size: 0.85rem; padding: 8px 14px; text-decoration: none;">
                <i class="fas fa-exclamation-triangle"></i> Configurar Evolution API
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$isEvolutionConfigured): ?>
    <div style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 1.2rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <h4 style="margin: 0 0 0.5rem 0;"><i class="fas fa-info-circle"></i> Evolution API ainda não está configurada</h4>
        <p style="margin: 0 0 0.8rem 0; font-size: 0.92rem;">
            Para realizar disparos em massa, informe a <strong>URL</strong>, o <strong>Nome da Instância</strong> e a <strong>API Key</strong> da sua Evolution API.
        </p>
        <a href="configuracoes.php" class="btn btn-sm btn-primary"><i class="fas fa-cog"></i> Ir para Configurações do CRM &amp; Evolution API</a>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Coluna 1: Segmentação & Configurações da Mensagem -->
    <div class="card">
        <h3 style="margin-top: 0; color: #03045e;"><i class="fas fa-users"></i> 1. Segmentação do Público</h3>

        <div class="form-group">
            <label>Selecione o Grupo de Destinatários:</label>
            <select id="target_group" class="form-control" onchange="atualizarPublicoAlvo()">
                <option value="all_leads">🎯 Todos os Leads de Aulas Gratuitas</option>
                <option value="leads_channel">📺 Leads de um Canal Específico (Aulas)</option>
                <option value="all_reservations">📝 Alunos com Reserva de Turmas (Todas as Campanhas)</option>
                <option value="reservations_campaign">📌 Alunos de uma Campanha Específica de Reserva</option>
                <option value="all_events">📅 Alunos Inscritos em Eventos/Lives</option>
                <option value="all_contacts">🌐 Todos os Contatos Cadastrados (Base Geral Unificada)</option>
            </select>
        </div>

        <div class="form-group" id="channel_filter_group" style="display: none;">
            <label>Escolha o Canal:</label>
            <select id="filter_channel_id" class="form-control" onchange="carregarContatosDestino()">
                <option value="">Selecione um canal...</option>
                <?php foreach ($channels as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" id="campaign_filter_group" style="display: none;">
            <label>Escolha a Campanha de Reserva:</label>
            <select id="filter_campaign_id" class="form-control" onchange="carregarContatosDestino()">
                <option value="">Selecione uma campanha...</option>
                <?php foreach ($reservationCampaigns as $rc): ?>
                    <option value="<?= $rc['id'] ?>"><?= htmlspecialchars($rc['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="background: #f8f9fa; border: 1px solid #e9ecef; padding: 12px 16px; border-radius: 6px; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <span style="font-size: 0.88rem; color: #555;">Destinatários com WhatsApp válido:</span>
            </div>
            <div>
                <strong id="recipient_count_badge" style="font-size: 1.15rem; color: #03045e;">
                    <i class="fas fa-spinner fa-spin"></i>
                </strong>
            </div>
        </div>

        <h3 style="margin-top: 1.5rem; color: #03045e;"><i class="fas fa-shield-alt"></i> 2. Segurança e Anti-Bloqueio</h3>

        <div class="form-group">
            <label>Intervalo entre mensagens (Segurança Anti-Ban):</label>
            <div style="display: flex; align-items: center; gap: 12px;">
                <input type="range" id="delay_seconds" min="4" max="25" value="8" style="flex: 1;" oninput="document.getElementById('delay_val').innerText = this.value">
                <span style="font-weight: bold; min-width: 50px;"><span id="delay_val">8</span> seg</span>
            </div>
            <small style="color: #666;">
                Recomendamos de 8 a 15 segundos entre disparos para simular digitação humana e evitar bloqueios na sua conta do WhatsApp.
            </small>
        </div>

        <div class="form-group" style="margin-top: 1.5rem;">
            <label>Enviar Teste Prévia (Apenas para o seu número):</label>
            <div style="display: flex; gap: 8px;">
                <input type="text" id="test_phone" class="form-control" placeholder="DDD + Número (ex: 11999999999)" value="<?= htmlspecialchars($config['notify_admin_whatsapp'] ?: $config['phone']) ?>">
                <button type="button" class="btn btn-secondary btn-sm" id="btnSendTest" onclick="enviarMensagemTeste()" style="white-space: nowrap;">
                    <i class="fas fa-paper-plane"></i> Enviar Teste
                </button>
            </div>
            <div id="test_feedback" style="margin-top: 6px; font-size: 0.85rem; display: none;"></div>
        </div>
    </div>

    <!-- Coluna 2: Redação da Mensagem & Painel de Disparo -->
    <div class="card">
        <h3 style="margin-top: 0; color: #03045e;"><i class="fas fa-comment-dots"></i> 3. Mensagem do WhatsApp</h3>

        <div style="margin-bottom: 0.8rem;">
            <small style="color: #666; font-weight: 600;">Tags Dinâmicas (Clique para inserir):</small>
            <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 4px;">
                <button type="button" class="badge badge-info" style="cursor: pointer; border: none; padding: 5px 8px;" onclick="inserirTag('{primeiro_nome}')">{primeiro_nome}</button>
                <button type="button" class="badge badge-info" style="cursor: pointer; border: none; padding: 5px 8px;" onclick="inserirTag('{nome}')">{nome}</button>
                <button type="button" class="badge badge-info" style="cursor: pointer; border: none; padding: 5px 8px;" onclick="inserirTag('{link_site}')">{link_site}</button>
            </div>
        </div>

        <div class="form-group">
            <textarea id="message_text" class="form-control" rows="8" placeholder="Olá {primeiro_nome}, tudo bem? Aqui é o Professor Aristóteles do ISP Preparatórios..."></textarea>
            <small style="color: #777;">
                Dica: Use <code>*negrito*</code>, <code>_itálico_</code> ou <code>~tachado~</code> para formatar o texto.
            </small>
        </div>

        <!-- Botão Principal de Disparo -->
        <div style="margin-top: 1.5rem;">
            <button type="button" class="btn btn-success" id="btnStartBroadcast" onclick="iniciarDisparoEmMassa()" style="width: 100%; padding: 12px; font-size: 1rem; font-weight: 600; background: #25d366; border-color: #25d366;">
                <i class="fab fa-whatsapp"></i> Iniciar Disparo em Massa
            </button>
            <button type="button" class="btn btn-danger" id="btnCancelBroadcast" onclick="cancelarDisparo()" style="width: 100%; padding: 10px; margin-top: 8px; display: none;">
                <i class="fas fa-stop"></i> Pausar / Cancelar Disparo
            </button>
        </div>

        <!-- Painel de Progresso em Tempo Real -->
        <div id="progress_container" style="display: none; margin-top: 1.5rem; background: #f8f9fa; border: 1px solid #ddd; padding: 1rem; border-radius: 6px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 0.9rem;">
                <strong id="progress_status_text">Iniciando disparos...</strong>
                <span id="progress_percentage">0%</span>
            </div>
            
            <div style="background: #e9ecef; border-radius: 4px; height: 16px; overflow: hidden; margin-bottom: 10px;">
                <div id="progress_bar" style="background: #25d366; height: 100%; width: 0%; transition: width 0.3s;"></div>
            </div>

            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: #555; margin-bottom: 8px;">
                <span>✅ Enviados: <strong id="count_success" style="color: #28a745;">0</strong></span>
                <span>❌ Falhas: <strong id="count_failed" style="color: #dc3545;">0</strong></span>
                <span>⏳ Restantes: <strong id="count_remaining">0</strong></span>
            </div>

            <div id="broadcast_log" style="height: 120px; overflow-y: auto; background: #1e1e1e; color: #00ff66; font-family: monospace; font-size: 0.75rem; padding: 8px; border-radius: 4px;">
                [Aguardando início do disparo...]
            </div>
        </div>
    </div>
</div>

<script>
let globalRecipients = [];
let isBroadcasting = false;
let shouldStop = false;

function atualizarPublicoAlvo() {
    const grp = document.getElementById('target_group').value;
    const chanGrp = document.getElementById('channel_filter_group');
    const campGrp = document.getElementById('campaign_filter_group');
    if (chanGrp) chanGrp.style.display = (grp === 'leads_channel') ? 'block' : 'none';
    if (campGrp) campGrp.style.display = (grp === 'reservations_campaign') ? 'block' : 'none';
    carregarContatosDestino();
}

function carregarContatosDestino() {
    const grp = document.getElementById('target_group').value;
    let filterId = 0;
    if (grp === 'leads_channel') {
        filterId = document.getElementById('filter_channel_id').value;
    } else if (grp === 'reservations_campaign') {
        filterId = document.getElementById('filter_campaign_id').value;
    }
    const badge = document.getElementById('recipient_count_badge');

    badge.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    const formData = new FormData();
    formData.append('action', 'fetch_recipients');
    formData.append('target_group', grp);
    formData.append('filter_id', filterId);

    fetch('ajax_whatsapp_broadcast.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            globalRecipients = data.recipients || [];
            badge.innerText = data.count + ' contatos';
        } else {
            badge.innerText = '0 contatos';
        }
    })
    .catch(() => {
        badge.innerText = 'Erro ao carregar';
    });
}

function inserirTag(tag) {
    const txtArea = document.getElementById('message_text');
    const start = txtArea.selectionStart;
    const end = txtArea.selectionEnd;
    const text = txtArea.value;
    txtArea.value = text.substring(0, start) + tag + text.substring(end);
    txtArea.focus();
    txtArea.selectionEnd = start + tag.length;
}

function enviarMensagemTeste() {
    const phone = document.getElementById('test_phone').value.trim();
    const msg = document.getElementById('message_text').value.trim();
    const btn = document.getElementById('btnSendTest');
    const feedback = document.getElementById('test_feedback');

    if (!phone) {
        alert('Informe seu número para teste.');
        return;
    }
    if (!msg) {
        alert('Escreva uma mensagem antes de testar.');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    feedback.style.display = 'none';

    const formData = new FormData();
    formData.append('action', 'send_single_message');
    formData.append('phone', phone);
    formData.append('name', 'Admin');
    formData.append('email', 'admin@isp.com.br');
    formData.append('message', msg);

    fetch('ajax_whatsapp_broadcast.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Teste';
        feedback.style.display = 'block';
        if (data.success) {
            feedback.innerHTML = '<span style="color: #28a745;"><i class="fas fa-check-circle"></i> Mensagem de teste enviada com sucesso ao seu WhatsApp!</span>';
        } else {
            feedback.innerHTML = '<span style="color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> ' + (data.message || 'Erro no envio.') + '</span>';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Teste';
        feedback.style.display = 'block';
        feedback.innerHTML = '<span style="color: #dc3545;"><i class="fas fa-times-circle"></i> Erro de rede.</span>';
    });
}

async function iniciarDisparoEmMassa() {
    const msg = document.getElementById('message_text').value.trim();
    if (!msg) {
        alert('Por favor, redija a mensagem antes de iniciar o disparo.');
        return;
    }

    if (globalRecipients.length === 0) {
        alert('Nenhum destinatário disponível para disparo no grupo selecionado.');
        return;
    }

    const delay = parseInt(document.getElementById('delay_seconds').value) || 8;
    const confirmMsg = `Confirmar disparo para ${globalRecipients.length} contatos com intervalo de ${delay} segundos entre cada mensagem?`;
    if (!confirm(confirmMsg)) {
        return;
    }

    isBroadcasting = true;
    shouldStop = false;

    document.getElementById('btnStartBroadcast').style.display = 'none';
    document.getElementById('btnCancelBroadcast').style.display = 'block';
    document.getElementById('progress_container').style.display = 'block';

    const logBox = document.getElementById('broadcast_log');
    logBox.innerHTML = `[${new Date().toLocaleTimeString()}] Iniciando disparo para ${globalRecipients.length} contatos...\n`;

    let successCount = 0;
    let failedCount = 0;
    const total = globalRecipients.length;

    for (let i = 0; i < total; i++) {
        if (shouldStop) {
            logBox.innerHTML += `[${new Date().toLocaleTimeString()}] ⏹️ Disparo cancelado pelo usuário.\n`;
            break;
        }

        const r = globalRecipients[i];
        document.getElementById('progress_status_text').innerText = `Enviando ${i + 1} de ${total} para ${r.name}...`;

        const formData = new FormData();
        formData.append('action', 'send_single_message');
        formData.append('phone', r.phone);
        formData.append('name', r.name);
        formData.append('email', r.email);
        formData.append('message', msg);

        try {
            const resp = await fetch('ajax_whatsapp_broadcast.php', { method: 'POST', body: formData });
            const resJson = await resp.json();

            if (resJson.success) {
                successCount++;
                logBox.innerHTML += `[${new Date().toLocaleTimeString()}] ✅ ${r.name} (${r.phone}): Enviado\n`;
            } else {
                failedCount++;
                logBox.innerHTML += `[${new Date().toLocaleTimeString()}] ❌ ${r.name} (${r.phone}): ${resJson.message}\n`;
            }
        } catch (err) {
            failedCount++;
            logBox.innerHTML += `[${new Date().toLocaleTimeString()}] ❌ ${r.name} (${r.phone}): Erro de rede\n`;
        }

        logBox.scrollTop = logBox.scrollHeight;

        // Atualiza barras e contadores
        const progressPercent = Math.round(((i + 1) / total) * 100);
        document.getElementById('progress_bar').style.width = progressPercent + '%';
        document.getElementById('progress_percentage').innerText = progressPercent + '%';
        document.getElementById('count_success').innerText = successCount;
        document.getElementById('count_failed').innerText = failedCount;
        document.getElementById('count_remaining').innerText = (total - (i + 1));

        // Aguarda delay antes da próxima mensagem se não for o último
        if (i < total - 1 && !shouldStop) {
            await new Promise(resolve => setTimeout(resolve, delay * 1000));
        }
    }

    isBroadcasting = false;
    document.getElementById('btnStartBroadcast').style.display = 'block';
    document.getElementById('btnCancelBroadcast').style.display = 'none';
    document.getElementById('progress_status_text').innerText = shouldStop ? 'Disparo interrompido.' : 'Disparo concluído com sucesso!';
    logBox.innerHTML += `[${new Date().toLocaleTimeString()}] 🏁 Fim do processo. Enviados: ${successCount}, Falhas: ${failedCount}.\n`;
}

function cancelarDisparo() {
    shouldStop = true;
    document.getElementById('btnCancelBroadcast').innerText = 'Parando...';
}

// Inicializa a contagem ao abrir
document.addEventListener('DOMContentLoaded', () => {
    carregarContatosDestino();
});
</script>

<?php require_once 'includes/footer.php'; ?>
