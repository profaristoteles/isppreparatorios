<?php
require_once 'auth.php';
require_once '../db_config.php';
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

    $stmt = $pdo->prepare("UPDATE configuracoes SET theme_color_primary=?, theme_color_secondary=?, footer_text=?, phone=?, email=?, facebook=?, instagram=?, youtube=?, tiktok=?, whatsapp_group_url=?, ai_provider=?, ai_api_key=?, ai_groq_key=?, ai_openai_key=?, ai_openrouter_key=?, ai_openrouter_model=?, site_description=? WHERE id=1");
    if($stmt->execute([$primary, $secondary, $footer, $phone, $email, $facebook, $instagram, $youtube, $tiktok, $whatsapp_group_url, $ai_provider, $ai_api_key, $ai_groq_key, $ai_openai_key, $ai_openrouter_key, $ai_openrouter_model, $site_description])){
        $_SESSION['msg'] = "Configurações salvas com sucesso!";
    } else {
        $_SESSION['erro'] = "Erro ao salvar configurações.";
    }
    header("Location: configuracoes.php");
    exit;
}

$config = get_config($pdo);

// Verificação de Status Informativo das Integrações de Aulas Gratuitas (Sem expor credenciais)
$evocrm_enabled = filter_var(getenv('EVOCRM_ENABLED') ?: false, FILTER_VALIDATE_BOOLEAN);
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

        <!-- Seção Informativa de Integrações de Aulas Gratuitas -->
        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #03045e; margin-top: 2rem;">
            <h4 style="color: #03045e; margin-bottom: 0.5rem;"><i class="fas fa-plug"></i> Status das Integrações da Área de Aulas Gratuitas</h4>
            <p style="color: #666; font-size: 0.85rem; margin-bottom: 1rem;">O status dos serviços abaixo é gerenciado com segurança via variáveis de ambiente (.env).</p>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; text-align: center;">
                <div style="background: #fff; padding: 1rem; border-radius: 6px; border: 1px solid #ddd;">
                    <div style="font-weight: 600; font-size: 0.9rem;">EvoCRM</div>
                    <div style="margin-top: 0.5rem;">
                        <?php if ($evocrm_enabled): ?>
                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> ATIVO</span>
                        <?php else: ?>
                            <span class="badge badge-secondary"><i class="fas fa-pause-circle"></i> INATIVO (.env)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="background: #fff; padding: 1rem; border-radius: 6px; border: 1px solid #ddd;">
                    <div style="font-weight: 600; font-size: 0.9rem;">Download Seguro HMAC</div>
                    <div style="margin-top: 0.5rem;">
                        <?php if (!empty($download_secret)): ?>
                            <span class="badge badge-success"><i class="fas fa-lock"></i> CONFIGURADO</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> CHAVE PADRÃO</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="background: #fff; padding: 1rem; border-radius: 6px; border: 1px solid #ddd;">
                    <div style="font-weight: 600; font-size: 0.9rem;">Mautic API (Futuro)</div>
                    <div style="margin-top: 0.5rem;">
                        <?php if ($mautic_enabled): ?>
                            <span class="badge badge-success"><i class="fas fa-check-circle"></i> ATIVO</span>
                        <?php else: ?>
                            <span class="badge badge-secondary"><i class="fas fa-pause-circle"></i> INATIVO (Modo CSV)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
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
</script>

<?php require_once 'includes/footer.php'; ?>
