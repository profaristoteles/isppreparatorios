<?php
require_once 'auth.php';
require_once '../db_config.php';
require_once '../includes/evocrm_service.php';
require_once 'includes/admin_security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    
    $primary = $_POST['theme_color_primary'];
    $secondary = $_POST['theme_color_secondary'];
    $footer = $_POST['footer_text'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $facebook = $_POST['facebook'];
    $instagram = $_POST['instagram'];
    $youtube = $_POST['youtube'];
    $tiktok = $_POST['tiktok'];
    $whatsapp_group_url = trim($_POST['whatsapp_group_url'] ?? '');
    
    $ai_provider = $_POST['ai_provider'] ?? 'gemini';
    $ai_api_key = $_POST['ai_api_key'] ?? '';
    $ai_groq_key = $_POST['ai_groq_key'] ?? '';
    $ai_openai_key = $_POST['ai_openai_key'] ?? '';
    $ai_openrouter_key = $_POST['ai_openrouter_key'] ?? '';
    $ai_openrouter_model = trim($_POST['ai_openrouter_model'] ?? '');
    if (empty($ai_openrouter_model)) {
        $ai_openrouter_model = 'meta-llama/llama-3.3-70b-instruct';
    }
    $site_description = $_POST['site_description'] ?? '';

    // Configurações de Notificações Administrativas
    $notify_admin_email = trim($_POST['notify_admin_email'] ?? '');
    $notify_admin_whatsapp = trim($_POST['notify_admin_whatsapp'] ?? '');
    $notify_email_enabled = isset($_POST['notify_email_enabled']) ? 1 : 0;
    $notify_whatsapp_enabled = isset($_POST['notify_whatsapp_enabled']) ? 1 : 0;
    $notify_on_reservation = isset($_POST['notify_on_reservation']) ? 1 : 0;
    $notify_on_event = isset($_POST['notify_on_event']) ? 1 : 0;
    $notify_on_contact = isset($_POST['notify_on_contact']) ? 1 : 0;
    $notify_on_lead = isset($_POST['notify_on_lead']) ? 1 : 0;

    $smtp_enabled = isset($_POST['smtp_enabled']) ? 1 : 0;
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = !empty($_POST['smtp_port']) ? (int)$_POST['smtp_port'] : 587;
    $smtp_user = trim($_POST['smtp_user'] ?? '');
    $smtp_pass = trim($_POST['smtp_pass'] ?? '');
    $smtp_secure = trim($_POST['smtp_secure'] ?? 'tls');
    $smtp_from_email = trim($_POST['smtp_from_email'] ?? '');
    $smtp_from_name = trim($_POST['smtp_from_name'] ?? 'ISP Preparatórios');

    $whatsapp_api_provider = trim($_POST['whatsapp_api_provider'] ?? 'none');
    $whatsapp_api_url = trim($_POST['whatsapp_api_url'] ?? '');
    $whatsapp_api_key = trim($_POST['whatsapp_api_key'] ?? '');
    $whatsapp_api_instance = trim($_POST['whatsapp_api_instance'] ?? '');

    // Configurações de CRM & Evolution API
    $evocrm_enabled = isset($_POST['evocrm_enabled']) ? 1 : 0;
    $evocrm_provider = trim($_POST['evocrm_provider'] ?? 'evolution');
    $evocrm_api_url = trim($_POST['evocrm_api_url'] ?? '');
    $evocrm_api_key = trim($_POST['evocrm_api_key'] ?? '');
    $evocrm_instance = trim($_POST['evocrm_instance'] ?? '');

    $stmt = $pdo->prepare("UPDATE configuracoes SET 
        theme_color_primary=?, theme_color_secondary=?, footer_text=?, phone=?, email=?, 
        facebook=?, instagram=?, youtube=?, tiktok=?, whatsapp_group_url=?, 
        ai_provider=?, ai_api_key=?, ai_groq_key=?, ai_openai_key=?, ai_openrouter_key=?, ai_openrouter_model=?, site_description=?,
        notify_admin_email=?, notify_admin_whatsapp=?, notify_email_enabled=?, notify_whatsapp_enabled=?,
        notify_on_reservation=?, notify_on_event=?, notify_on_contact=?, notify_on_lead=?,
        smtp_enabled=?, smtp_host=?, smtp_port=?, smtp_user=?, smtp_pass=?, smtp_secure=?, smtp_from_email=?, smtp_from_name=?,
        whatsapp_api_provider=?, whatsapp_api_url=?, whatsapp_api_key=?, whatsapp_api_instance=?,
        evocrm_enabled=?, evocrm_provider=?, evocrm_api_url=?, evocrm_api_key=?, evocrm_instance=?
    WHERE id=1");

    $saveSuccess = $stmt->execute([
        $primary, $secondary, $footer, $phone, $email, 
        $facebook, $instagram, $youtube, $tiktok, $whatsapp_group_url, 
        $ai_provider, $ai_api_key, $ai_groq_key, $ai_openai_key, $ai_openrouter_key, $ai_openrouter_model, $site_description,
        $notify_admin_email, $notify_admin_whatsapp, $notify_email_enabled, $notify_whatsapp_enabled,
        $notify_on_reservation, $notify_on_event, $notify_on_contact, $notify_on_lead,
        $smtp_enabled, $smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtp_secure, $smtp_from_email, $smtp_from_name,
        $whatsapp_api_provider, $whatsapp_api_url, $whatsapp_api_key, $whatsapp_api_instance,
        $evocrm_enabled, $evocrm_provider, $evocrm_api_url, $evocrm_api_key, $evocrm_instance
    ]);

    if ($saveSuccess) {
        $_SESSION['msg'] = "Configurações salvas com sucesso!";
    } else {
        $_SESSION['erro'] = "Erro ao salvar configurações.";
    }
    header("Location: configuracoes.php");
    exit;
}

$config = get_config($pdo);

// Verificação de Status Informativo das Integrações
$crm_cfg = EvoCRMService::getConfig($pdo);
$evocrm_enabled = $crm_cfg['enabled'];
$download_secret = getenv('DOWNLOAD_SECRET_KEY') ?: '';
$mautic_enabled = filter_var(getenv('MAUTIC_ENABLED') ?: false, FILTER_VALIDATE_BOOLEAN);

require_once 'includes/header.php';
?>

<div class="card">
    <h2>Configurações do Site</h2>
    <form method="POST">
        <?= csrf_field() ?>
        
        <div style="display: flex; gap: 1rem;">
            <div class="form-group" style="flex:1;">
                <label>Cor Primária (Hex)</label>
                <input type="color" name="theme_color_primary" class="form-control" value="<?= htmlspecialchars($config['theme_color_primary']) ?>">
            </div>
            <div class="form-group" style="flex:1;">
                <label>Cor Secundária (Hex)</label>
                <input type="color" name="theme_color_secondary" class="form-control" value="<?= htmlspecialchars($config['theme_color_secondary']) ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label>Telefone / WhatsApp Geral</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($config['phone']) ?>">
        </div>

        <div class="form-group">
            <label>Link do Grupo VIP no WhatsApp (Aulas Gratuitas)</label>
            <input type="url" name="whatsapp_group_url" class="form-control" placeholder="https://chat.whatsapp.com/..." value="<?= htmlspecialchars($config['whatsapp_group_url'] ?? '') ?>">
            <small style="color: #666;">Este link será exibido ao lead após a liberação do material gratuito.</small>
        </div>

        <div class="form-group">
            <label>E-mail de Contato</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($config['email']) ?>">
        </div>

        <div class="form-group">
            <label>Texto do Rodapé</label>
            <textarea name="footer_text" class="form-control" rows="3"><?= htmlspecialchars($config['footer_text']) ?></textarea>
        </div>

        <div class="form-group">
            <label>Descrição Global do Site (SEO)</label>
            <textarea name="site_description" class="form-control" rows="3" placeholder="Aparecerá quando você compartilhar a página inicial do site..."><?= htmlspecialchars($config['site_description'] ?? '') ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Link Facebook</label>
                <input type="url" name="facebook" class="form-control" value="<?= htmlspecialchars($config['facebook']) ?>">
            </div>
            <div class="form-group">
                <label>Link Instagram</label>
                <input type="url" name="instagram" class="form-control" value="<?= htmlspecialchars($config['instagram']) ?>">
            </div>
            <div class="form-group">
                <label>Link YouTube</label>
                <input type="url" name="youtube" class="form-control" value="<?= htmlspecialchars($config['youtube'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Link TikTok</label>
                <input type="url" name="tiktok" class="form-control" value="<?= htmlspecialchars($config['tiktok'] ?? '') ?>">
            </div>
        </div>

        <!-- Seção de Integração com CRM & Evolution API (Aulas Gratuitas, Reservas & Disparos) -->
        <div style="background: #ffffff; padding: 1.8rem; border-radius: 8px; border: 1px solid #dcdfe6; border-left: 4px solid #03045e; margin-top: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;">
                <div>
                    <h3 style="color: #03045e; margin: 0 0 0.4rem 0; font-size: 1.25rem;"><i class="fas fa-plug"></i> Integração CRM &amp; Evolution API (Leads &amp; Reservas)</h3>
                    <p style="color: #555; font-size: 0.88rem; margin: 0; line-height: 1.5;">
                        Envie automaticamente todos os novos alunos e contatos cadastrados para o seu CRM ou para sua instância da <strong>Evolution API</strong> para envio de mensagens em massa e nutrição de leads.
                    </p>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <div id="crm_status_badge">
                        <?php if ($evocrm_enabled): ?>
                            <span class="badge badge-success" style="font-size: 0.85rem; padding: 6px 12px;"><i class="fas fa-check-circle"></i> ATIVO</span>
                        <?php else: ?>
                            <span class="badge badge-secondary" style="font-size: 0.85rem; padding: 6px 12px;"><i class="fas fa-pause-circle"></i> INATIVO</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Toggle Ativar/Desativar -->
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1.2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0; font-weight: 600; color: #03045e;">
                    <input type="checkbox" name="evocrm_enabled" id="evocrm_enabled" value="1" <?= $evocrm_enabled ? 'checked' : '' ?> onchange="toggleCrmFields()" style="width: 18px; height: 18px;">
                    <span>Ativar sincronização automática de contatos com CRM / Evolution API</span>
                </label>
                <div>
                    <a href="fila-integracoes.php" class="btn btn-secondary btn-sm" style="text-decoration: none;">
                        <i class="fas fa-list"></i> Ver Fila de Integrações
                    </a>
                </div>
            </div>

            <div id="crm_fields_container" style="<?= $evocrm_enabled ? '' : 'display: none;' ?>">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Plataforma / Provedor do CRM</label>
                        <select name="evocrm_provider" id="evocrm_provider" class="form-control" onchange="toggleCrmProvider()">
                            <option value="evolution" <?= ($config['evocrm_provider'] ?? 'evolution') === 'evolution' ? 'selected' : '' ?>>🟢 Evolution API (Recomendado - WhatsApp &amp; Mensagens em Massa)</option>
                            <option value="evocrm" <?= ($config['evocrm_provider'] ?? '') === 'evocrm' ? 'selected' : '' ?>>🔵 EvoCRM / Webhook CRM (/contacts/upsert)</option>
                        </select>
                        <small style="color: #666;">A Evolution API sincroniza os números no WhatsApp e permite disparos em massa diretamente pelo sistema.</small>
                    </div>

                    <div class="form-group" id="crm_instance_group">
                        <label>Nome da Instância (Evolution API)</label>
                        <input type="text" name="evocrm_instance" id="evocrm_instance" class="form-control" placeholder="Ex: isp" value="<?= htmlspecialchars($config['evocrm_instance'] ?? ($config['whatsapp_api_instance'] ?? 'isp')) ?>">
                        <small style="color: #666;">Nome da instância configurada no seu painel da Evolution API.</small>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>URL da API</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="url" name="evocrm_api_url" id="evocrm_api_url" class="form-control" placeholder="https://api.seudominio.com" value="<?= htmlspecialchars($config['evocrm_api_url'] ?? ($config['whatsapp_api_url'] ?? '')) ?>">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="copiarCredenciaisNotificacoes()" title="Copiar URL e Chave já configuradas no WhatsApp de Notificações" style="white-space: nowrap;">
                                <i class="fas fa-copy"></i> Copiar do Zap
                            </button>
                        </div>
                        <small style="color: #666;">Endereço base da sua Evolution API (sem barra no final).</small>
                    </div>

                    <div class="form-group">
                        <label>Chave de API / Token (apikey)</label>
                        <input type="password" name="evocrm_api_key" id="evocrm_api_key" class="form-control" placeholder="Cole sua API Key ou Bearer Token..." value="<?= htmlspecialchars($config['evocrm_api_key'] ?? ($config['whatsapp_api_key'] ?? '')) ?>">
                        <small style="color: #666;">Chave global da Evolution API ou token de autenticação do CRM.</small>
                    </div>
                </div>

                <!-- Painel de Ações Rápidas (Teste & Sincronização sob Demanda) -->
                <div style="background: #eef2f7; border: 1px solid #d0dbe7; padding: 1rem 1.2rem; border-radius: 6px; margin-top: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <strong style="color: #03045e; font-size: 0.92rem;"><i class="fas fa-vial"></i> Testar Conexão &amp; Sincronizar Fila</strong>
                        <div style="font-size: 0.82rem; color: #555;">Valide se a Evolution API está respondendo e envie os contatos pendentes imediatamente.</div>
                    </div>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-secondary btn-sm" id="btnTestCrm" onclick="executarTesteCrm()">
                            <i class="fas fa-bolt"></i> Testar Conexão
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" id="btnSyncQueue" onclick="executarSyncFila()">
                            <i class="fas fa-sync"></i> Sincronizar Fila Agora
                        </button>
                    </div>
                </div>
                <div id="test_crm_result" style="width: 100%; margin-top: 8px; display: none;"></div>
            </div>

            <!-- Status Informativo das Demais Integrações (HMAC e Mautic) -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; text-align: center; margin-top: 1.2rem; padding-top: 1rem; border-top: 1px solid #eee;">
                <div style="background: #fdfdfd; padding: 0.8rem; border-radius: 6px; border: 1px solid #eee;">
                    <div style="font-weight: 600; font-size: 0.85rem; color: #555;">Download Seguro HMAC</div>
                    <div style="margin-top: 0.3rem;">
                        <?php if (!empty($download_secret)): ?>
                            <span class="badge badge-success"><i class="fas fa-lock"></i> CONFIGURADO</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> CHAVE PADRÃO (.env)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="background: #fdfdfd; padding: 0.8rem; border-radius: 6px; border: 1px solid #eee;">
                    <div style="font-weight: 600; font-size: 0.85rem; color: #555;">Mautic API (Futuro)</div>
                    <div style="margin-top: 0.3rem;">
                        <?php if ($mautic_enabled): ?>
                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> ATIVO</span>
                        <?php else: ?>
                            <span class="badge badge-secondary"><i class="fas fa-pause-circle"></i> INATIVO (Modo CSV)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção de Notificações Administrativas (E-mail & WhatsApp) -->
        <div style="background: #f8f9fa; padding: 1.8rem; border-radius: 8px; border-left: 4px solid #28a745; margin-top: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 0.8rem;">
                <div>
                    <h3 style="color: #1e7e34; margin: 0 0 0.4rem 0; font-size: 1.25rem;"><i class="fas fa-bell"></i> Notificações de Reservas &amp; Inscrições (E-mail &amp; WhatsApp do Admin)</h3>
                    <p style="color: #555; font-size: 0.88rem; margin: 0; line-height: 1.5;">
                        Configure os canais onde o administrador receberá os alertas instantâneos de novos alunos inscritos em <strong>eventos</strong>, <strong>reservas de turmas</strong> e <strong>contatos gerais</strong>.
                    </p>
                </div>
                <div style="display: flex; gap: 8px;">
                    <span class="badge" style="background: #e2f0d9; color: #28a745; font-weight: 700; padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; border: 1px solid rgba(40,167,69,0.3);">
                        <i class="fas fa-shield-alt"></i> Alertas Ativos
                    </span>
                </div>
            </div>

            <!-- Dados do Destinatário Administrador -->
            <div style="background: #ffffff; padding: 1.2rem; border-radius: 8px; border: 1px solid #dee2e6; margin-top: 1.2rem; margin-bottom: 1.5rem;">
                <h4 style="margin: 0 0 1rem 0; font-size: 1rem; color: #03045e;"><i class="fas fa-user-shield"></i> Destinatários dos Alertas Administrativos</h4>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.2rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label style="font-weight: 600; color: #333;">E-mail do Administrador para Notificações</label>
                        <input type="email" name="notify_admin_email" id="notify_admin_email" class="form-control" placeholder="Padrão: <?= htmlspecialchars($config['email']) ?>" value="<?= htmlspecialchars($config['notify_admin_email'] ?? '') ?>">
                        <small style="color: #666;">Se deixar vazio, usará o e-mail geral cadastrado (<strong><?= htmlspecialchars($config['email']) ?></strong>).</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label style="font-weight: 600; color: #333;">WhatsApp do Administrador para Notificações</label>
                        <input type="text" name="notify_admin_whatsapp" id="notify_admin_whatsapp" class="form-control" placeholder="Padrão: <?= htmlspecialchars($config['phone']) ?>" value="<?= htmlspecialchars($config['notify_admin_whatsapp'] ?? '') ?>">
                        <small style="color: #666;">Informe DDD + número (ex: 99 99999-9999). Se vazio, usará o telefone geral.</small>
                    </div>
                </div>

                <!-- Gatilhos de Notificação -->
                <div style="margin-top: 1.2rem; padding-top: 1rem; border-top: 1px solid #eee;">
                    <label style="font-weight: 600; color: #333; display: block; margin-bottom: 0.6rem;">Disparar notificações para:</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.8rem;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                            <input type="checkbox" name="notify_on_reservation" value="1" <?= (!isset($config['notify_on_reservation']) || $config['notify_on_reservation']) ? 'checked' : '' ?>>
                            <span><strong>Novas Reservas</strong> de Vagas / Turmas</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                            <input type="checkbox" name="notify_on_event" value="1" <?= (!isset($config['notify_on_event']) || $config['notify_on_event']) ? 'checked' : '' ?>>
                            <span><strong>Inscrições em Eventos</strong> e Aulões</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                            <input type="checkbox" name="notify_on_contact" value="1" <?= (!isset($config['notify_on_contact']) || $config['notify_on_contact']) ? 'checked' : '' ?>>
                            <span><strong>Contatos</strong> e Inscrições Gerais</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer;">
                            <input type="checkbox" name="notify_on_lead" value="1" <?= (!empty($config['notify_on_lead'])) ? 'checked' : '' ?>>
                            <span>Downloads de Materiais Gratuitos</span>
                        </label>
                    </div>
                </div>

                <div style="display: flex; gap: 1.5rem; margin-top: 1rem; padding-top: 0.8rem; border-top: 1px dashed #eee; flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 0.9rem; cursor: pointer; color: #03045e; font-weight: 600;">
                        <input type="checkbox" name="notify_email_enabled" value="1" <?= (!isset($config['notify_email_enabled']) || $config['notify_email_enabled']) ? 'checked' : '' ?>>
                        <span><i class="fas fa-envelope"></i> Ativar Envio por E-mail</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; font-size: 0.9rem; cursor: pointer; color: #25d366; font-weight: 600;">
                        <input type="checkbox" name="notify_whatsapp_enabled" value="1" <?= (!isset($config['notify_whatsapp_enabled']) || $config['notify_whatsapp_enabled']) ? 'checked' : '' ?>>
                        <span><i class="fab fa-whatsapp"></i> Ativar Envio por WhatsApp</span>
                    </label>
                </div>
            </div>

            <!-- Bloco 1: Configuração de E-mail (SMTP / PHP mail) -->
            <div style="background: #ffffff; padding: 1.2rem; border-radius: 8px; border: 1px solid #dee2e6; margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 10px;">
                    <h4 style="margin: 0; font-size: 1rem; color: #03045e;"><i class="fas fa-paper-plane"></i> Configuração de Envio de E-mail</h4>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <label style="font-size: 0.88rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="checkbox" name="smtp_enabled" id="smtp_enabled" value="1" <?= !empty($config['smtp_enabled']) ? 'checked' : '' ?> onchange="toggleSmtpFields()">
                            <span>Usar SMTP Autenticado (Recomendado)</span>
                        </label>
                    </div>
                </div>

                <div id="smtp_fields_container" style="<?= empty($config['smtp_enabled']) ? 'display: none;' : '' ?> background: #fdfdfd; padding: 1rem; border-radius: 6px; border: 1px solid #e9ecef; margin-bottom: 1rem;">
                    <p style="color: #666; font-size: 0.85rem; margin-top: 0; margin-bottom: 1rem;">
                        O envio via SMTP garante que os e-mails cheguem à Caixa de Entrada sem cair em spam (compatível com Hostinger, cPanel, Gmail, SendGrid, Locaweb, etc.).
                    </p>
                    <div style="display: grid; grid-template-columns: 2fr 1fr 2fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Servidor SMTP (Host)</label>
                            <input type="text" name="smtp_host" id="smtp_host" class="form-control" placeholder="smtp.seudominio.com.br" value="<?= htmlspecialchars($config['smtp_host'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Porta</label>
                            <input type="number" name="smtp_port" id="smtp_port" class="form-control" placeholder="587 ou 465" value="<?= htmlspecialchars($config['smtp_port'] ?? '587') ?>">
                        </div>
                        <div class="form-group">
                            <label>Segurança</label>
                            <select name="smtp_secure" class="form-control">
                                <option value="tls" <?= ($config['smtp_secure'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (Porta 587)</option>
                                <option value="ssl" <?= ($config['smtp_secure'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (Porta 465)</option>
                                <option value="none" <?= ($config['smtp_secure'] ?? '') === 'none' ? 'selected' : '' ?>>Nenhuma</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>Usuário SMTP / E-mail</label>
                            <input type="text" name="smtp_user" id="smtp_user" class="form-control" placeholder="contato@isppreparatorios.com.br" value="<?= htmlspecialchars($config['smtp_user'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Senha SMTP</label>
                            <input type="password" name="smtp_pass" id="smtp_pass" class="form-control" placeholder="••••••••••••" value="<?= htmlspecialchars($config['smtp_pass'] ?? '') ?>">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>E-mail do Remetente (From)</label>
                            <input type="email" name="smtp_from_email" id="smtp_from_email" class="form-control" placeholder="noreply@isppreparatorios.com.br" value="<?= htmlspecialchars($config['smtp_from_email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Nome do Remetente</label>
                            <input type="text" name="smtp_from_name" class="form-control" placeholder="ISP Preparatórios" value="<?= htmlspecialchars($config['smtp_from_name'] ?? 'ISP Preparatórios') ?>">
                        </div>
                    </div>
                </div>

                <!-- Ferramenta de Teste de E-mail -->
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; background: #f8fafc; padding: 0.8rem; border-radius: 6px; border: 1px dashed #cbd5e1;">
                    <div style="flex: 1; min-width: 240px;">
                        <input type="email" id="test_email_recipient" class="form-control" placeholder="Digite um e-mail para testar o envio..." value="<?= htmlspecialchars($config['notify_admin_email'] ?: $config['email']) ?>">
                    </div>
                    <button type="button" onclick="executarTesteEmail()" class="btn" id="btnTestEmail" style="background: #03045e; color: #fff; padding: 10px 18px; font-size: 0.9rem; font-weight: 600;">
                        <i class="fas fa-vial"></i> Testar Envio de E-mail
                    </button>
                    <div id="test_email_result" style="width: 100%; margin-top: 5px; display: none;"></div>
                </div>
            </div>

            <!-- Bloco 2: Configuração de WhatsApp (Gateway / API) -->
            <div style="background: #ffffff; padding: 1.2rem; border-radius: 8px; border: 1px solid #dee2e6; margin-bottom: 1.5rem;">
                <h4 style="margin: 0 0 1rem 0; font-size: 1rem; color: #25d366;"><i class="fab fa-whatsapp"></i> Gateway de Envio para WhatsApp</h4>
                
                <div class="form-group">
                    <label>Provedor de Disparo do WhatsApp</label>
                    <select name="whatsapp_api_provider" id="whatsapp_api_provider" class="form-control" onchange="toggleWhatsAppFields()">
                        <option value="none" <?= ($config['whatsapp_api_provider'] ?? 'none') === 'none' ? 'selected' : '' ?>>⚠️ Desativado (Apenas links wa.me no painel e no e-mail)</option>
                        <option value="evolution" <?= ($config['whatsapp_api_provider'] ?? '') === 'evolution' ? 'selected' : '' ?>>🟢 Evolution API (Recomendado - v1 e v2)</option>
                        <option value="zapi" <?= ($config['whatsapp_api_provider'] ?? '') === 'zapi' ? 'selected' : '' ?>>🔵 Z-API</option>
                        <option value="webhook" <?= ($config['whatsapp_api_provider'] ?? '') === 'webhook' ? 'selected' : '' ?>>🟠 Webhook Customizado / Outro Gateway</option>
                    </select>
                </div>

                <div id="whatsapp_fields_container" style="<?= ($config['whatsapp_api_provider'] ?? 'none') === 'none' ? 'display: none;' : '' ?>">
                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>URL da API / Webhook</label>
                            <input type="url" name="whatsapp_api_url" id="whatsapp_api_url" class="form-control" placeholder="https://api.meuzap.com" value="<?= htmlspecialchars($config['whatsapp_api_url'] ?? '') ?>">
                            <small style="color: #666;">URL base da sua instância Evolution API, Z-API ou endpoint do webhook.</small>
                        </div>
                        <div class="form-group">
                            <label>Nome da Instância (Instance)</label>
                            <input type="text" name="whatsapp_api_instance" id="whatsapp_api_instance" class="form-control" placeholder="Ex: isp" value="<?= htmlspecialchars($config['whatsapp_api_instance'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Token / Chave de API (API Key)</label>
                        <input type="password" name="whatsapp_api_key" id="whatsapp_api_key" class="form-control" placeholder="Cole seu Token ou API Key..." value="<?= htmlspecialchars($config['whatsapp_api_key'] ?? '') ?>">
                    </div>

                    <!-- Ferramenta de Teste de WhatsApp -->
                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; background: #f8fafc; padding: 0.8rem; border-radius: 6px; border: 1px dashed #cbd5e1; margin-top: 1rem;">
                        <div style="flex: 1; min-width: 240px;">
                            <input type="text" id="test_whatsapp_recipient" class="form-control" placeholder="DDD + Número (ex: 99 99999-9999)..." value="<?= htmlspecialchars($config['notify_admin_whatsapp'] ?: $config['phone']) ?>">
                        </div>
                        <button type="button" onclick="executarTesteWhatsApp()" class="btn" id="btnTestWhatsApp" style="background: #25d366; color: #fff; padding: 10px 18px; font-size: 0.9rem; font-weight: 600; border: none;">
                            <i class="fab fa-whatsapp"></i> Testar Mensagem no Zap
                        </button>
                        <div id="test_whatsapp_result" style="width: 100%; margin-top: 5px; display: none;"></div>
                    </div>
                </div>
            </div>

            <!-- Log Recente de Notificações Disparadas -->
            <?php
            $recent_notifs = [];
            try {
                $recent_notifs = $pdo->query("SELECT * FROM notifications_log ORDER BY id DESC LIMIT 6")->fetchAll();
            } catch (Exception $e) {}
            ?>
            <?php if (!empty($recent_notifs)): ?>
            <div style="background: #ffffff; padding: 1.2rem; border-radius: 8px; border: 1px solid #dee2e6;">
                <h4 style="margin: 0 0 0.8rem 0; font-size: 0.95rem; color: #555;"><i class="fas fa-history"></i> Histórico Recente de Notificações Disparadas</h4>
                <div style="overflow-x: auto;">
                    <table class="table" style="font-size: 0.85rem; margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th>Data/Hora</th>
                                <th>Canal</th>
                                <th>Evento</th>
                                <th>Destinatário</th>
                                <th>Assunto / Prévia</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_notifs as $n): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></td>
                                <td>
                                    <?php if ($n['channel'] === 'email'): ?>
                                        <span class="badge" style="background: #03045e; color: #fff;"><i class="fas fa-envelope"></i> E-mail</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #25d366; color: #fff;"><i class="fab fa-whatsapp"></i> WhatsApp</span>
                                    <?php endif; ?>
                                </td>
                                <td><span style="font-family: monospace; text-transform: uppercase;"><?= htmlspecialchars($n['event_type']) ?></span></td>
                                <td><?= htmlspecialchars($n['recipient']) ?></td>
                                <td><?= htmlspecialchars(substr($n['title'], 0, 45)) ?></td>
                                <td>
                                    <?php if ($n['status'] === 'sent'): ?>
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Enviado</span>
                                    <?php elseif ($n['status'] === 'disabled'): ?>
                                        <span class="badge badge-secondary">Inativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger" title="<?= htmlspecialchars($n['error_message'] ?? '') ?>"><i class="fas fa-exclamation-triangle"></i> Falhou</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Seção de Inteligência Artificial -->
        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #ff8000; margin-top: 1.5rem;">
            <h4 style="color: #ff8000; margin-bottom: 0.5rem;"><i class="fas fa-robot"></i> Configuração de Inteligência Artificial</h4>
            <p style="color: #666; font-size: 0.85rem; margin-bottom: 1rem;">Provedor ativo para geração automática de artigos no blog.</p>

            <div class="form-group">
                <label>Provedor de IA Ativo</label>
                <select name="ai_provider" id="ai_provider" class="form-control" onchange="toggleApiKeys()">
                    <option value="gemini" <?= ($config['ai_provider'] ?? '') === 'gemini' ? 'selected' : '' ?>>🔵 Google Gemini (Gratuito)</option>
                    <option value="groq" <?= ($config['ai_provider'] ?? '') === 'groq' ? 'selected' : '' ?>>🟢 Groq (Gratuito e Rápido)</option>
                    <option value="openai" <?= ($config['ai_provider'] ?? '') === 'openai' ? 'selected' : '' ?>>⚪ OpenAI (ChatGPT)</option>
                    <option value="openrouter" <?= ($config['ai_provider'] ?? '') === 'openrouter' ? 'selected' : '' ?>>🟠 OpenRouter (Múltiplos LLMs)</option>
                </select>
            </div>

            <!-- Gemini -->
            <div id="key_gemini" class="ai-key-group" style="margin-top: 1rem;">
                <label>Google Gemini API Key</label>
                <input type="password" name="ai_api_key" class="form-control" placeholder="AIzaSy..." value="<?= htmlspecialchars($config['ai_api_key'] ?? '') ?>">
            </div>

            <!-- Groq -->
            <div id="key_groq" class="ai-key-group" style="margin-top: 1rem;">
                <label>Groq API Key</label>
                <input type="password" name="ai_groq_key" class="form-control" placeholder="gsk_..." value="<?= htmlspecialchars($config['ai_groq_key'] ?? '') ?>">
            </div>

            <!-- OpenAI -->
            <div id="key_openai" class="ai-key-group" style="margin-top: 1rem;">
                <label>OpenAI API Key</label>
                <input type="password" name="ai_openai_key" class="form-control" placeholder="sk-..." value="<?= htmlspecialchars($config['ai_openai_key'] ?? '') ?>">
            </div>

            <!-- OpenRouter -->
            <div id="key_openrouter" class="ai-key-group" style="margin-top: 1rem;">
                <label>OpenRouter API Key</label>
                <input type="password" name="ai_openrouter_key" class="form-control" placeholder="sk-or-..." value="<?= htmlspecialchars($config['ai_openrouter_key'] ?? '') ?>">
                
                <label style="margin-top: 0.5rem;">Modelo LLM (OpenRouter)</label>
                <input type="text" name="ai_openrouter_model" id="ai_openrouter_model" class="form-control" placeholder="Ex: meta-llama/llama-3.3-70b-instruct" value="<?= htmlspecialchars($config['ai_openrouter_model'] ?? 'meta-llama/llama-3.3-70b-instruct') ?>">
            </div>
        </div>

        <button type="submit" class="btn" style="margin-top: 1.5rem;"><i class="fas fa-save"></i> Salvar Configurações</button>
    </form>
</div>

<script>
function toggleApiKeys() {
    const provider = document.getElementById('ai_provider').value;
    document.querySelectorAll('.ai-key-group').forEach(el => el.style.display = 'none');
    const target = document.getElementById('key_' + provider);
    if (target) target.style.display = 'block';
}
toggleApiKeys();

function toggleSmtpFields() {
    const chk = document.getElementById('smtp_enabled');
    const container = document.getElementById('smtp_fields_container');
    if (container) {
        container.style.display = chk.checked ? 'block' : 'none';
    }
}

function toggleWhatsAppFields() {
    const prov = document.getElementById('whatsapp_api_provider').value;
    const container = document.getElementById('whatsapp_fields_container');
    if (container) {
        container.style.display = (prov === 'none') ? 'none' : 'block';
    }
}

function executarTesteEmail() {
    const email = document.getElementById('test_email_recipient').value.trim();
    const btn = document.getElementById('btnTestEmail');
    const resDiv = document.getElementById('test_email_result');
    if (!email) {
        alert('Por favor, informe o e-mail de teste.');
        return;
    }

    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
    resDiv.style.display = 'none';

    const formData = new FormData();
    formData.append('action', 'test_email');
    formData.append('target', email);

    fetch('ajax_test_notification.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        resDiv.style.display = 'block';
        if (data.success) {
            resDiv.innerHTML = '<div style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px 14px; border-radius: 6px; font-size: 0.88rem;"><i class="fas fa-check-circle"></i> <strong>Sucesso!</strong> ' + (data.message || 'E-mail de teste enviado com sucesso.') + '</div>';
        } else {
            resDiv.innerHTML = '<div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px 14px; border-radius: 6px; font-size: 0.88rem;"><i class="fas fa-exclamation-triangle"></i> <strong>Falha:</strong> ' + (data.message || 'Erro ao enviar e-mail de teste.') + '</div>';
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        resDiv.style.display = 'block';
        resDiv.innerHTML = '<div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px 14px; border-radius: 6px; font-size: 0.88rem;"><i class="fas fa-times-circle"></i> Erro de comunicação com o servidor.</div>';
    });
}

function executarTesteWhatsApp() {
    const zap = document.getElementById('test_whatsapp_recipient').value.trim();
    const btn = document.getElementById('btnTestWhatsApp');
    const resDiv = document.getElementById('test_whatsapp_result');
    if (!zap) {
        alert('Por favor, informe o WhatsApp de teste com DDD.');
        return;
    }

    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Disparando...';
    resDiv.style.display = 'none';

    const formData = new FormData();
    formData.append('action', 'test_whatsapp');
    formData.append('target', zap);

    fetch('ajax_test_notification.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        resDiv.style.display = 'block';
        if (data.success) {
            resDiv.innerHTML = '<div style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px 14px; border-radius: 6px; font-size: 0.88rem;"><i class="fas fa-check-circle"></i> <strong>Sucesso!</strong> ' + (data.message || 'Mensagem enviada com sucesso ao WhatsApp.') + '</div>';
        } else {
            resDiv.innerHTML = '<div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px 14px; border-radius: 6px; font-size: 0.88rem;"><i class="fas fa-exclamation-triangle"></i> <strong>Falha:</strong> ' + (data.message || 'Erro ao enviar mensagem no WhatsApp.') + '</div>';
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        resDiv.style.display = 'block';
        resDiv.innerHTML = '<div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px 14px; border-radius: 6px; font-size: 0.88rem;"><i class="fas fa-times-circle"></i> Erro de comunicação com o servidor.</div>';
    });
}

function toggleCrmFields() {
    const chk = document.getElementById('evocrm_enabled');
    const container = document.getElementById('crm_fields_container');
    const badge = document.getElementById('crm_status_badge');
    if (container) {
        container.style.display = chk.checked ? 'block' : 'none';
    }
    if (badge) {
        badge.innerHTML = chk.checked 
            ? '<span class="badge badge-success" style="font-size: 0.85rem; padding: 6px 12px;"><i class="fas fa-check-circle"></i> ATIVO</span>'
            : '<span class="badge badge-secondary" style="font-size: 0.85rem; padding: 6px 12px;"><i class="fas fa-pause-circle"></i> INATIVO</span>';
    }
}

function toggleCrmProvider() {
    const prov = document.getElementById('evocrm_provider').value;
    const instGroup = document.getElementById('crm_instance_group');
    if (instGroup) {
        instGroup.style.display = (prov === 'evolution') ? 'block' : 'none';
    }
}

function copiarCredenciaisNotificacoes() {
    const zapUrl = document.getElementById('whatsapp_api_url');
    const zapKey = document.getElementById('whatsapp_api_key');
    const zapInst = document.getElementById('whatsapp_api_instance');

    const crmUrl = document.getElementById('evocrm_api_url');
    const crmKey = document.getElementById('evocrm_api_key');
    const crmInst = document.getElementById('evocrm_instance');

    if (zapUrl && zapUrl.value) crmUrl.value = zapUrl.value;
    if (zapKey && zapKey.value) crmKey.value = zapKey.value;
    if (zapInst && zapInst.value) crmInst.value = zapInst.value;

    alert('Credenciais copiadas da seção de Notificações com sucesso!');
}

function executarTesteCrm() {
    const btn = document.getElementById('btnTestCrm');
    const resDiv = document.getElementById('test_crm_result');
    const provider = document.getElementById('evocrm_provider').value;
    const apiUrl = document.getElementById('evocrm_api_url').value.trim();
    const apiKey = document.getElementById('evocrm_api_key').value.trim();
    const instance = document.getElementById('evocrm_instance').value.trim();

    if (!apiUrl || !apiKey) {
        alert('Por favor, preencha a URL da API e a Chave de API antes de testar.');
        return;
    }

    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testando conexão...';
    resDiv.style.display = 'none';

    const formData = new FormData();
    formData.append('provider', provider);
    formData.append('api_url', apiUrl);
    formData.append('api_key', apiKey);
    formData.append('instance', instance);

    fetch('ajax_test_crm.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        resDiv.style.display = 'block';
        if (data.success) {
            resDiv.innerHTML = '<div style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px 14px; border-radius: 6px; font-size: 0.88rem;"><i class="fas fa-check-circle"></i> ' + (data.message || 'Conexão realizada com sucesso!') + '</div>';
        } else {
            resDiv.innerHTML = '<div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px 14px; border-radius: 6px; font-size: 0.88rem;"><i class="fas fa-exclamation-triangle"></i> ' + (data.message || 'Erro ao conectar à API.') + '</div>';
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        resDiv.style.display = 'block';
        resDiv.innerHTML = '<div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px 14px; border-radius: 6px; font-size: 0.88rem;"><i class="fas fa-times-circle"></i> Erro de comunicação com o servidor.</div>';
    });
}

function executarSyncFila() {
    const btn = document.getElementById('btnSyncQueue');
    const resDiv = document.getElementById('test_crm_result');

    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sincronizando...';
    resDiv.style.display = 'none';

    fetch('ajax_crm_sync.php', {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        resDiv.style.display = 'block';
        if (data.success) {
            resDiv.innerHTML = '<div style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px 14px; border-radius: 6px; font-size: 0.88rem;"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
        } else {
            resDiv.innerHTML = '<div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px 14px; border-radius: 6px; font-size: 0.88rem;"><i class="fas fa-exclamation-triangle"></i> ' + (data.message || 'Falha ao sincronizar fila.') + '</div>';
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        resDiv.style.display = 'block';
        resDiv.innerHTML = '<div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px 14px; border-radius: 6px; font-size: 0.88rem;"><i class="fas fa-times-circle"></i> Erro de comunicação com o servidor.</div>';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
