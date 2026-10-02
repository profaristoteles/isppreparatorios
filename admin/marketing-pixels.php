<?php
/**
 * Gerenciador de Rastreamento - Pixels & Conversões
 * ISP Preparatórios
 */

require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';
require_once '../includes/MarketingTracker.php';

// Salvar Configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $saved = MarketingTracker::saveSettings($pdo, $_POST);
    if ($saved) {
        $_SESSION['msg'] = "Configurações de rastreamento do Meta Pixel salvas com sucesso!";
    } else {
        $_SESSION['erro'] = "Ocorreu um erro ao salvar as configurações.";
    }
    header("Location: marketing-pixels.php");
    exit;
}

$settings = MarketingTracker::getSettings($pdo);
$stats = MarketingTracker::getConversionStats($pdo);

require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h2 style="margin: 0; color: #03045e; font-size: 1.4rem;">
            <i class="fas fa-bullseye" style="color: #ff8000; margin-right: 0.5rem;"></i>
            Configuração do Meta Pixel & Conversões
        </h2>
        <p style="margin: 0.3rem 0 0; color: #666; font-size: 0.9rem;">
            Gerencie o Pixel oficial do Facebook/Meta, modo de depuração e deduplicação sem editar arquivos de código.
        </p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="marketing-eventos.php" class="btn btn-secondary btn-sm"><i class="fas fa-tags"></i> Gerenciar Eventos</a>
        <a href="marketing-conversoes.php" class="btn btn-sm" style="background: #ff8000;"><i class="fas fa-chart-line"></i> Log de Conversões</a>
    </div>
</div>

<!-- Métricas Rápidas -->
<div class="grid-cards" style="margin-bottom: 1.5rem;">
    <div class="stat-card" style="border-left-color: <?= $settings['meta_pixel_enabled'] ? '#28a745' : '#dc3545' ?>;">
        <h3>Status do Pixel</h3>
        <p class="val" style="font-size: 1.25rem;">
            <?php if ($settings['meta_pixel_enabled']): ?>
                <span class="badge badge-success"><i class="fas fa-check-circle"></i> ATIVO</span>
            <?php else: ?>
                <span class="badge badge-danger"><i class="fas fa-ban"></i> DESATIVADO</span>
            <?php endif; ?>
        </p>
        <small style="color: #777;">ID: <?= htmlspecialchars($settings['meta_pixel_id']) ?></small>
    </div>
    <div class="stat-card" style="border-left-color: #03045e;">
        <h3>Total de Conversões</h3>
        <p class="val"><?= number_format($stats['total'], 0, ',', '.') ?></p>
        <small style="color: #777;"><?= $stats['today'] ?> registradas hoje</small>
    </div>
    <div class="stat-card" style="border-left-color: #ff8000;">
        <h3>Leads Registrados</h3>
        <p class="val" style="color: #ff8000;"><?= number_format($stats['leads_total'], 0, ',', '.') ?></p>
        <small style="color: #777;"><?= $stats['leads_unique'] ?> contatos únicos</small>
    </div>
    <div class="stat-card" style="border-left-color: #17a2b8;">
        <h3>Campanha Caxias</h3>
        <p class="val" style="color: #17a2b8;"><?= number_format($stats['caxias_leads'], 0, ',', '.') ?></p>
        <small style="color: #777;">Leads gerados na LP</small>
    </div>
</div>

<form action="marketing-pixels.php" method="POST">
    <?= csrf_field() ?>

    <div class="card" style="margin-bottom: 1.5rem;">
        <h3 style="margin-top: 0; color: #03045e; border-bottom: 1px solid #eee; padding-bottom: 0.7rem; font-size: 1.15rem; display: flex; align-items: center; justify-content: space-between;">
            <span><i class="fab fa-facebook" style="color: #1877f2; margin-right: 0.5rem;"></i> Configurações Globais do Meta Pixel</span>
            <span class="badge <?= $settings['meta_pixel_enabled'] ? 'badge-success' : 'badge-danger' ?>">
                <?= $settings['meta_pixel_enabled'] ? 'Monitoramento Ativo' : 'Monitoramento Pausado' ?>
            </span>
        </h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1rem;">
            <div>
                <!-- Ativar / Desativar Pixel -->
                <div class="form-group" style="background: #f8f9fa; padding: 1rem; border-radius: 6px; border: 1px solid #e9ecef;">
                    <label style="display: flex; align-items: center; cursor: pointer; gap: 0.6rem; font-weight: 700; color: #03045e; font-size: 0.95rem; margin-bottom: 0.3rem;">
                        <input type="checkbox" name="meta_pixel_enabled" value="1" <?= $settings['meta_pixel_enabled'] ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: #28a745;">
                        Ativar Meta Pixel no Site
                    </label>
                    <small style="color: #666; display: block; margin-left: 2rem;">
                        Quando desativado, o script do Pixel não é carregado no site e as funções de rastreamento operam em modo silencioso sem gerar erros JavaScript.
                    </small>
                </div>

                <!-- Pixel ID -->
                <div class="form-group">
                    <label for="meta_pixel_id">Pixel ID Oficial da Meta *</label>
                    <input type="text" name="meta_pixel_id" id="meta_pixel_id" class="form-control" value="<?= htmlspecialchars($settings['meta_pixel_id']) ?>" required placeholder="Ex: 854510411013557" style="font-family: monospace; font-size: 1rem; letter-spacing: 0.5px;">
                    <small style="color: #666;">ID numérico da conta do Meta Business Suite. Valor atual padrão do ISP: <code>854510411013557</code>.</small>
                </div>

                <!-- Advanced Matching -->
                <div class="form-group" style="background: #f8f9fa; padding: 0.9rem; border-radius: 6px; border: 1px solid #e9ecef;">
                    <label style="display: flex; align-items: center; cursor: pointer; gap: 0.6rem; font-weight: 600; color: #333; margin-bottom: 0.2rem;">
                        <input type="checkbox" name="meta_advanced_matching" value="1" <?= $settings['meta_advanced_matching'] ? 'checked' : '' ?> style="width: 16px; height: 16px;">
                        Ativar Advanced Matching (Correspondência Avançada)
                    </label>
                    <small style="color: #666; display: block; margin-left: 1.8rem;">
                        Envia dados anônimos (hashes SHA256 de e-mail e telefone) para aumentar a precisão de atribuição dos anúncios na Meta.
                    </small>
                </div>
            </div>

            <div>
                <!-- Modo Depuração / Teste -->
                <div class="form-group" style="background: #fff8e6; padding: 1rem; border-radius: 6px; border: 1px solid #ffeeba;">
                    <label style="display: flex; align-items: center; cursor: pointer; gap: 0.6rem; font-weight: 700; color: #856404; font-size: 0.95rem; margin-bottom: 0.3rem;">
                        <input type="checkbox" name="debug_mode" value="1" <?= $settings['debug_mode'] ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: #ff8000;">
                        Modo de Depuração / Teste no Console
                    </label>
                    <small style="color: #856404; display: block; margin-left: 2rem;">
                        Quando ativado, exibe logs detalhados com <code>[ISP Tracker]</code> no DevTools (F12) mostrando eventos disparados, parâmetros e deduplicação.
                    </small>
                </div>

                <!-- Meta Test Event Code -->
                <div class="form-group">
                    <label for="meta_test_event_code">Código de Teste do Gerenciador de Eventos (Test Events)</label>
                    <input type="text" name="meta_test_event_code" id="meta_test_event_code" class="form-control" value="<?= htmlspecialchars($settings['meta_test_event_code']) ?>" placeholder="Ex: TEST12345" style="font-family: monospace; text-transform: uppercase;">
                    <small style="color: #666;">
                        Obtenha na aba <em>Gerenciador de Eventos → Testar Eventos</em> da Meta para validar disparos em tempo real. Deixe vazio em produção normal.
                    </small>
                </div>

                <!-- Eventos Automáticos da Meta -->
                <div class="form-group" style="background: #f8f9fa; padding: 0.9rem; border-radius: 6px; border: 1px solid #e9ecef;">
                    <label style="display: flex; align-items: center; cursor: pointer; gap: 0.6rem; font-weight: 600; color: #333; margin-bottom: 0.2rem;">
                        <input type="checkbox" name="meta_auto_events" value="1" <?= $settings['meta_auto_events'] ? 'checked' : '' ?> style="width: 16px; height: 16px;">
                        Permitir Detecção Automática da Meta (Automatic Events)
                    </label>
                    <small style="color: #666; display: block; margin-left: 1.8rem;">
                        Quando desmarcado, eventos como <em>SubscribedButtonClick</em> não sobrepõem os eventos reais estruturados do ISP.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Preparação e Suporte para Meta Conversions API (CAPI) -->
    <div class="card" style="margin-bottom: 1.5rem; border-left: 4px solid #03045e;">
        <h3 style="margin-top: 0; color: #03045e; border-bottom: 1px solid #eee; padding-bottom: 0.7rem; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-server" style="color: #03045e;"></i>
            Meta Conversions API (CAPI Server-Side) — Preparação & Deduplicação
        </h3>
        
        <p style="color: #555; font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem;">
            A infraestrutura centralizada do ISP gera e registra automaticamente um <code>event_id</code> único para cada reserva. Esse identificador garante que, quando a integração via API de Servidor (CAPI) estiver ativa, a Meta <strong>desduplique perfeitamente</strong> o evento entre o navegador e o servidor sem registrar conversões duplicadas.
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; cursor: pointer; gap: 0.6rem; font-weight: 600;">
                        <input type="checkbox" name="capi_enabled" value="1" <?= $settings['capi_enabled'] ? 'checked' : '' ?> style="width: 16px; height: 16px;">
                        Ativar Envio via Conversions API (Server-Side)
                    </label>
                    <small style="color: #666; display: block; margin-left: 1.8rem;">Envia dados de conversão diretamente pelo PHP para os servidores da Meta.</small>
                </div>

                <div class="form-group">
                    <label for="capi_test_code">Código de Teste CAPI (Opcional)</label>
                    <input type="text" name="capi_test_code" id="capi_test_code" class="form-control" value="<?= htmlspecialchars($settings['capi_test_code']) ?>" placeholder="Ex: TEST45892" style="font-family: monospace;">
                </div>
            </div>

            <div>
                <div class="form-group">
                    <label for="capi_access_token">Token de Acesso do Sistema (Access Token CAPI)</label>
                    <input type="password" name="capi_access_token" id="capi_access_token" class="form-control" value="<?= htmlspecialchars($settings['capi_access_token']) ?>" placeholder="EAAG..." autocomplete="off">
                    <small style="color: #666;">Gerado no Gerenciador de Eventos → Configurações → Gerar Token de Acesso. Armazenado com segurança no servidor, nunca exposto no front-end.</small>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: right; margin-bottom: 2rem;">
        <button type="submit" class="btn" style="padding: 0.8rem 2rem; font-size: 1rem; background: #03045e;">
            <i class="fas fa-save"></i> Salvar Configurações de Rastreamento
        </button>
    </div>
</form>

<!-- Guia de Verificação e Boas Práticas -->
<div class="card" style="background: #f8fafc; border: 1px solid #e2e8f0;">
    <h4 style="margin-top: 0; color: #03045e; font-size: 1rem;">
        <i class="fas fa-info-circle" style="color: #03045e;"></i> Como Testar o Disparo Correto da Conversão
    </h4>
    <ol style="margin: 0; padding-left: 1.3rem; color: #475569; font-size: 0.9rem; line-height: 1.7;">
        <li>Instale a extensão oficial <strong>Meta Pixel Helper</strong> no Google Chrome.</li>
        <li>Abra a página da campanha: <a href="/reserva/preparatorio-concurso-caxias-ma-legatus" target="_blank" style="color: #ff8000; font-weight: 600;">/reserva/preparatorio-concurso-caxias-ma-legatus</a>.</li>
        <li>No carregamento inicial, observe que apenas <code>PageView</code> é registrado (Lead = 0).</li>
        <li>Preencha o formulário com dados de um interessado novo e envie.</li>
        <li>Assim que a mensagem <strong>"Reserva realizada com sucesso!"</strong> for renderizada, o evento padrão <code>Lead</code> será disparado com seu <code>eventID</code> único e parâmetros do curso.</li>
        <li>Se tentar cadastrar os mesmos dados novamente, o sistema informa que a reserva já existe e <strong>NÃO</strong> dispara um novo evento Lead.</li>
    </ol>
</div>

<?php require_once 'includes/footer.php'; ?>
