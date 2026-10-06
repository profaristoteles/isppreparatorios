<?php
/**
 * Modal Unificado de Disparo de WhatsApp Individual - ISP Preparatórios
 * Incluído no painel admin para envio direto via Evolution API / Gateway WhatsApp
 */
?>
<!-- Modal de Disparo WhatsApp Individual -->
<div id="modalWhatsAppIndividual" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(3, 4, 94, 0.65); backdrop-filter: blur(4px); z-index: 99999; justify-content: center; align-items: center; padding: 1rem; box-sizing: border-box;">
    <div style="background: #ffffff; border-radius: 12px; max-width: 650px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 40px rgba(0,0,0,0.3); border: 1px solid rgba(0,0,0,0.1); display: flex; flex-direction: column;">
        
        <!-- Header do Modal -->
        <div style="background: linear-gradient(135deg, #075e54 0%, #128c7e 100%); color: #ffffff; padding: 1.1rem 1.4rem; border-top-left-radius: 12px; border-top-right-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 38px; height: 38px; border-radius: 50%; background: #25d366; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.3rem; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #fff;">Disparo Direto no WhatsApp</h3>
                    <small style="opacity: 0.9; font-size: 0.8rem;">Envio imediato via API pelo painel (sem abrir WhatsApp Web)</small>
                </div>
            </div>
            <button type="button" onclick="fecharModalWhatsApp()" style="background: none; border: none; color: #ffffff; font-size: 1.5rem; cursor: pointer; padding: 0; line-height: 1; opacity: 0.85;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.85'">&times;</button>
        </div>

        <!-- Conteúdo do Modal -->
        <div style="padding: 1.3rem 1.4rem; display: flex; flex-direction: column; gap: 1.1rem;">
            
            <!-- Card do Destinatário -->
            <div style="background: #f8fbf9; border: 1px solid #dcf8c6; border-radius: 8px; padding: 0.8rem 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                <div>
                    <div style="font-size: 0.78rem; text-transform: uppercase; color: #075e54; font-weight: 700; letter-spacing: 0.5px;">Destinatário Selecionado</div>
                    <div style="font-size: 1.05rem; font-weight: 700; color: #03045e; margin-top: 2px;" id="modalZapLeadName">-</div>
                    <div style="font-size: 0.84rem; color: #555; margin-top: 2px;">
                        <i class="fab fa-whatsapp" style="color: #25d366;"></i> <span id="modalZapLeadPhone" style="font-weight: 600;">-</span>
                        <span id="modalZapLeadEmail" style="margin-left: 8px; color: #777;"></span>
                    </div>
                </div>
                <div id="modalZapLeadBadge">
                    <span class="badge badge-info" style="font-size: 0.75rem;">Lead do Site</span>
                </div>
            </div>

            <!-- Seleção de Modelos Rápidos (Templates) -->
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 700; color: #333; margin-bottom: 0.4rem;">
                    <i class="fas fa-magic" style="color: #0077b6;"></i> Modelos Rápidos de Mensagem:
                </label>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-zap-tpl" onclick="aplicarTemplateZap('boas_vindas')" style="font-size: 0.78rem; padding: 4px 10px; border-radius: 20px;">
                        👋 Boas-Vindas
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-zap-tpl" onclick="aplicarTemplateZap('novas_turmas')" style="font-size: 0.78rem; padding: 4px 10px; border-radius: 20px;">
                        📚 Novas Turmas
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-zap-tpl" onclick="aplicarTemplateZap('aulas_materiais')" style="font-size: 0.78rem; padding: 4px 10px; border-radius: 20px;">
                        🎁 Aulas &amp; PDFs
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-zap-tpl" onclick="aplicarTemplateZap('duvidas')" style="font-size: 0.78rem; padding: 4px 10px; border-radius: 20px;">
                        💬 Dúvidas / Suporte
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-zap-tpl" onclick="aplicarTemplateZap('limpar')" style="font-size: 0.78rem; padding: 4px 10px; border-radius: 20px;">
                        ✏️ Em Branco
                    </button>
                </div>
            </div>

            <!-- Campo de Texto da Mensagem -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #333; margin: 0;">
                        Texto da Mensagem: <span style="color: red;">*</span>
                    </label>
                    <div style="font-size: 0.78rem; color: #777;">
                        Tags: 
                        <a href="javascript:void(0)" onclick="inserirTagZap('{primeiro_nome}')" style="color: #0077b6; text-decoration: none; font-weight: 600;">{primeiro_nome}</a> | 
                        <a href="javascript:void(0)" onclick="inserirTagZap('{nome}')" style="color: #0077b6; text-decoration: none; font-weight: 600;">{nome}</a> | 
                        <a href="javascript:void(0)" onclick="inserirTagZap('{link_site}')" style="color: #0077b6; text-decoration: none; font-weight: 600;">{link_site}</a>
                    </div>
                </div>
                <textarea id="modalZapMessage" rows="5" class="form-control" placeholder="Digite a mensagem para o lead..." style="width: 100%; box-sizing: border-box; font-size: 0.92rem; border-radius: 6px; padding: 10px; resize: vertical; border: 1px solid #ced4da;" oninput="atualizarPreviewZap()"></textarea>
                <div style="display: flex; justify-content: space-between; font-size: 0.75rem; color: #888; margin-top: 3px;">
                    <span>Dica: Use formatação do WhatsApp (*negrito*, _itálico_, ~tachado~).</span>
                    <span id="modalZapCharCount">0 caracteres</span>
                </div>
            </div>

            <!-- Preview em tempo real simulando o WhatsApp -->
            <div>
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #555; margin-bottom: 0.3rem;">
                    <i class="fas fa-eye"></i> Pré-visualização da Mensagem:
                </label>
                <div style="background: #efeae2; border-radius: 8px; padding: 12px 16px; border: 1px solid #d1d7db; background-image: radial-gradient(#dfd9cf 1px, transparent 1px); background-size: 16px 16px;">
                    <div style="background: #d9fdd3; border-radius: 8px 8px 0px 8px; padding: 10px 14px; max-width: 90%; margin-left: auto; box-shadow: 0 1px 2px rgba(0,0,0,0.15); position: relative; font-size: 0.9rem; line-height: 1.4; color: #111b21; word-break: break-word;">
                        <div id="modalZapPreviewContent" style="white-space: pre-wrap;">Digite a mensagem acima para visualizar...</div>
                        <div style="display: flex; justify-content: flex-end; align-items: center; gap: 4px; font-size: 0.68rem; color: #667781; margin-top: 4px;">
                            <span id="modalZapPreviewTime"><?= date('H:i') ?></span>
                            <i class="fas fa-check-double" style="color: #53bdeb; font-size: 0.72rem;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerta de Status / Resposta da API -->
            <div id="modalZapAlertBox" style="display: none; padding: 10px 14px; border-radius: 6px; font-size: 0.88rem;"></div>

        </div>

        <!-- Footer do Modal -->
        <div style="background: #f8f9fa; padding: 1rem 1.4rem; border-top: 1px solid #e9ecef; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <a id="modalZapFallbackLink" href="#" target="_blank" style="color: #666; font-size: 0.82rem; text-decoration: underline; display: inline-flex; align-items: center; gap: 4px;">
                    <i class="fab fa-whatsapp"></i> Abrir no WhatsApp Web (manual)
                </a>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="fecharModalWhatsApp()" style="padding: 6px 14px;">
                    Cancelar
                </button>
                <button type="button" id="btnModalZapEnviar" class="btn btn-success btn-sm" onclick="enviarMensagemWhatsAppIndividual()" style="background: #25d366; border-color: #25d366; color: #ffffff; font-weight: 700; padding: 6px 18px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(37,211,102,0.35);">
                    <i class="fab fa-whatsapp"></i> <span>Disparar Mensagem Agora</span>
                </button>
            </div>
        </div>

    </div>
</div>

<script>
// Estado do Modal de WhatsApp
const ZapModalState = {
    leadId: 0,
    name: '',
    phone: '',
    email: '',
    contextType: 'individual_lead',
    csrfToken: '<?= generate_csrf_token() ?>'
};

const ZapTemplates = {
    boas_vindas: "Olá {primeiro_nome}, tudo bem? Sou da coordenação do *ISP Preparatórios*! Vi que você se cadastrou em nossa plataforma de estudos. Como podemos te ajudar na sua aprovação hoje?",
    novas_turmas: "Olá {primeiro_nome}! Temos excelentes novidades sobre as próximas turmas preparatórias e editais no *ISP Preparatórios*. Gostaria de receber o cronograma completo com condições especiais de matrícula?",
    aulas_materiais: "Olá {primeiro_nome}! Passando para avisar que liberamos novos materiais em PDF e videoaulas atualizadas em nossa plataforma. Acesse e bons estudos: {link_site}",
    duvidas: "Olá {primeiro_nome}, tudo bem? Notei seu interesse em nossos cursos. Ficou com alguma dúvida sobre o edital, professores ou metodologia? Estou à disposição para te orientar!",
    limpar: ""
};

/**
 * Abre o Modal com os dados do lead
 */
function abrirModalWhatsApp(leadData) {
    if (!leadData || !leadData.phone) {
        alert('Este contato não possui número de WhatsApp cadastrado.');
        return;
    }

    ZapModalState.leadId = leadData.id || 0;
    ZapModalState.name = leadData.name || 'Aluno';
    ZapModalState.phone = leadData.phone || '';
    ZapModalState.email = leadData.email || '';
    ZapModalState.contextType = leadData.contextType || 'individual_lead';

    // Elementos da UI
    document.getElementById('modalZapLeadName').innerText = ZapModalState.name;
    document.getElementById('modalZapLeadPhone').innerText = ZapModalState.phone;
    document.getElementById('modalZapLeadEmail').innerText = ZapModalState.email ? `(${ZapModalState.email})` : '';
    
    const badgeEl = document.getElementById('modalZapLeadBadge');
    if (leadData.source) {
        badgeEl.innerHTML = `<span class="badge badge-info" style="font-size: 0.75rem;">${leadData.source}</span>`;
    } else {
        badgeEl.innerHTML = `<span class="badge badge-secondary" style="font-size: 0.75rem;">Lead #${ZapModalState.leadId || ''}</span>`;
    }

    // Reset de alertas e botão
    const alertBox = document.getElementById('modalZapAlertBox');
    alertBox.style.display = 'none';
    alertBox.innerHTML = '';
    
    const btnSend = document.getElementById('btnModalZapEnviar');
    btnSend.disabled = false;
    btnSend.innerHTML = '<i class="fab fa-whatsapp"></i> <span>Disparar Mensagem Agora</span>';

    // Mensagem inicial
    const textarea = document.getElementById('modalZapMessage');
    if (leadData.initialMessage) {
        textarea.value = leadData.initialMessage;
    } else if (!textarea.value.trim()) {
        textarea.value = ZapTemplates.boas_vindas;
    }

    atualizarPreviewZap();

    const modal = document.getElementById('modalWhatsAppIndividual');
    modal.style.display = 'flex';
    textarea.focus();
}

/**
 * Fecha o Modal
 */
function fecharModalWhatsApp() {
    const modal = document.getElementById('modalWhatsAppIndividual');
    modal.style.display = 'none';
}

/**
 * Aplica um modelo pré-definido de mensagem
 */
function aplicarTemplateZap(tipo) {
    const textarea = document.getElementById('modalZapMessage');
    if (ZapTemplates[tipo] !== undefined) {
        textarea.value = ZapTemplates[tipo];
        atualizarPreviewZap();
        textarea.focus();
    }
}

/**
 * Insere tag no cursor da textarea
 */
function inserirTagZap(tag) {
    const textarea = document.getElementById('modalZapMessage');
    const startPos = textarea.selectionStart;
    const endPos = textarea.selectionEnd;
    textarea.value = textarea.value.substring(0, startPos) + tag + textarea.value.substring(endPos, textarea.value.length);
    textarea.focus();
    textarea.selectionStart = startPos + tag.length;
    textarea.selectionEnd = startPos + tag.length;
    atualizarPreviewZap();
}

/**
 * Atualiza o preview e o contador em tempo real
 */
function atualizarPreviewZap() {
    const textarea = document.getElementById('modalZapMessage');
    const previewEl = document.getElementById('modalZapPreviewContent');
    const countEl = document.getElementById('modalZapCharCount');
    const fallbackLink = document.getElementById('modalZapFallbackLink');

    const rawText = textarea.value;
    countEl.innerText = `${rawText.length} caracteres`;

    const firstName = ZapModalState.name ? ZapModalState.name.split(' ')[0] : 'Aluno';
    const siteUrl = window.location.origin;

    // Substituição das variáveis
    let replacedText = rawText
        .replace(/{nome}/g, ZapModalState.name || 'Aluno')
        .replace(/{primeiro_nome}/g, firstName)
        .replace(/{email}/g, ZapModalState.email || '')
        .replace(/{telefone}/g, ZapModalState.phone || '')
        .replace(/{link_site}/g, siteUrl);

    if (!replacedText.trim()) {
        previewEl.innerText = '(A mensagem está vazia)';
        previewEl.style.color = '#888';
    } else {
        previewEl.innerText = replacedText;
        previewEl.style.color = '#111b21';
    }

    // Atualiza link de contingência para WhatsApp Web
    let cleanPhone = ZapModalState.phone.replace(/[^0-9]/g, '');
    if (cleanPhone.length === 10 || cleanPhone.length === 11) {
        cleanPhone = '55' + cleanPhone;
    }
    fallbackLink.href = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(replacedText)}`;
}

/**
 * Dispara a mensagem individual via Evolution API / Gateway no servidor
 */
function enviarMensagemWhatsAppIndividual() {
    const textarea = document.getElementById('modalZapMessage');
    const message = textarea.value.trim();

    if (!message) {
        alert('Por favor, digite uma mensagem antes de disparar.');
        textarea.focus();
        return;
    }

    const btnSend = document.getElementById('btnModalZapEnviar');
    const alertBox = document.getElementById('modalZapAlertBox');

    btnSend.disabled = true;
    btnSend.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Disparando via API...</span>';

    alertBox.style.display = 'block';
    alertBox.style.background = '#e2f0d9';
    alertBox.style.color = '#385723';
    alertBox.style.border = '1px solid #c5e0b4';
    alertBox.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Conectando à API do WhatsApp e enviando mensagem...';

    const formData = new FormData();
    formData.append('csrf_token', ZapModalState.csrfToken);
    formData.append('lead_id', ZapModalState.leadId);
    formData.append('name', ZapModalState.name);
    formData.append('phone', ZapModalState.phone);
    formData.append('email', ZapModalState.email);
    formData.append('message', message);
    formData.append('context_type', ZapModalState.contextType);

    fetch('ajax_send_whatsapp_individual.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alertBox.style.background = '#d4edda';
            alertBox.style.color = '#155724';
            alertBox.style.border = '1px solid #c3e6cb';
            alertBox.innerHTML = `<i class="fas fa-check-circle"></i> <strong>Sucesso!</strong> ${data.message} <br><small>Disparado em ${data.sent_at} para ${data.phone}.</small>`;

            btnSend.innerHTML = '<i class="fas fa-check"></i> <span>Enviado com Sucesso!</span>';
            btnSend.style.background = '#28a745';

            // Se existir uma tabela de histórico na tela (ex: lead-detalhes.php), adiciona a linha ou recarrega
            if (typeof window.onWhatsAppMessageSent === 'function') {
                window.onWhatsAppMessageSent(data);
            }
        } else {
            alertBox.style.background = '#f8d7da';
            alertBox.style.color = '#721c24';
            alertBox.style.border = '1px solid #f5c6cb';
            
            let extraMsg = '';
            if (data.status === 'not_configured') {
                extraMsg = '<br><a href="configuracoes.php" target="_blank" style="color: #721c24; font-weight: bold; text-decoration: underline;">Clique aqui para configurar a Evolution API / Z-API em Configurações.</a>';
            }
            alertBox.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <strong>Não foi possível enviar:</strong> ${data.message}${extraMsg}`;

            btnSend.disabled = false;
            btnSend.innerHTML = '<i class="fab fa-whatsapp"></i> <span>Tentar Novamente</span>';
        }
    })
    .catch(err => {
        alertBox.style.background = '#f8d7da';
        alertBox.style.color = '#721c24';
        alertBox.style.border = '1px solid #f5c6cb';
        alertBox.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Erro de comunicação com o servidor: ${err.message}`;

        btnSend.disabled = false;
        btnSend.innerHTML = '<i class="fab fa-whatsapp"></i> <span>Tentar Novamente</span>';
    });
}

// Fechar com a tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        fecharModalWhatsApp();
    }
});
</script>
