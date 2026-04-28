<?php
require_once 'auth.php';
require_once '../db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $primary = $_POST['theme_color_primary'];
    $secondary = $_POST['theme_color_secondary'];
    $footer = $_POST['footer_text'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $facebook = $_POST['facebook'];
    $instagram = $_POST['instagram'];
    $ai_provider = $_POST['ai_provider'] ?? 'gemini';
    $ai_api_key = $_POST['ai_api_key'] ?? '';
    $ai_groq_key = $_POST['ai_groq_key'] ?? '';
    $ai_openai_key = $_POST['ai_openai_key'] ?? '';
    $ai_openrouter_key = $_POST['ai_openrouter_key'] ?? '';

    $stmt = $pdo->prepare("UPDATE configuracoes SET theme_color_primary=?, theme_color_secondary=?, footer_text=?, phone=?, email=?, facebook=?, instagram=?, ai_provider=?, ai_api_key=?, ai_groq_key=?, ai_openai_key=?, ai_openrouter_key=? WHERE id=1");
    if($stmt->execute([$primary, $secondary, $footer, $phone, $email, $facebook, $instagram, $ai_provider, $ai_api_key, $ai_groq_key, $ai_openai_key, $ai_openrouter_key])){
        $_SESSION['msg'] = "Configurações salvas com sucesso!";
    } else {
        $_SESSION['erro'] = "Erro ao salvar configurações.";
    }
    header("Location: configuracoes.php");
    exit;
}

$config = get_config($pdo);
require_once 'includes/header.php';
?>

<div class="card">
    <h2>Configurações do Site</h2>
    <form method="POST">
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
            <label>Telefone / WhatsApp</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($config['phone']) ?>">
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
            <label>Link Facebook</label>
            <input type="url" name="facebook" class="form-control" value="<?= htmlspecialchars($config['facebook']) ?>">
        </div>

        <div class="form-group">
            <label>Link Instagram</label>
            <input type="url" name="instagram" class="form-control" value="<?= htmlspecialchars($config['instagram']) ?>">
        </div>

        <!-- Seção de Inteligência Artificial -->
        <div style="background: rgba(255,255,255,0.05); padding: 2rem; border-radius: 8px; border-left: 4px solid var(--prism-cyan); margin-top: 2rem;">
            <h4 style="color: var(--prism-cyan); margin-bottom: 0.5rem;"><i class="fas fa-robot"></i> Configuração de Inteligência Artificial</h4>
            <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">Escolha o provedor de IA e insira a chave de API correspondente. O gerador de artigos do Blog usará o provedor selecionado.</p>

            <div class="form-group">
                <label>Provedor de IA Ativo</label>
                <select name="ai_provider" id="ai_provider" class="form-control" onchange="toggleApiKeys()">
                    <option value="gemini" <?= ($config['ai_provider'] ?? '') === 'gemini' ? 'selected' : '' ?>>🔵 Google Gemini (Gratuito com limites)</option>
                    <option value="groq" <?= ($config['ai_provider'] ?? '') === 'groq' ? 'selected' : '' ?>>🟢 Groq (Gratuito e Rápido)</option>
                    <option value="openai" <?= ($config['ai_provider'] ?? '') === 'openai' ? 'selected' : '' ?>>⚪ OpenAI (ChatGPT - Pago)</option>
                    <option value="openrouter" <?= ($config['ai_provider'] ?? '') === 'openrouter' ? 'selected' : '' ?>>🟠 OpenRouter (Múltiplos modelos)</option>
                </select>
            </div>

            <!-- Gemini -->
            <div id="key_gemini" class="ai-key-group" style="margin-top: 1rem;">
                <label>Google Gemini API Key</label>
                <input type="text" name="ai_api_key" class="form-control" placeholder="AIzaSy..." value="<?= htmlspecialchars($config['ai_api_key'] ?? '') ?>">
                <small style="color: var(--text-secondary); display: block; margin-top: 0.3rem;">Obtenha em <a href="https://aistudio.google.com/apikey" target="_blank" style="color: var(--prism-cyan);">Google AI Studio</a></small>
            </div>

            <!-- Groq -->
            <div id="key_groq" class="ai-key-group" style="margin-top: 1rem;">
                <label>Groq API Key</label>
                <input type="text" name="ai_groq_key" class="form-control" placeholder="gsk_..." value="<?= htmlspecialchars($config['ai_groq_key'] ?? '') ?>">
                <small style="color: var(--text-secondary); display: block; margin-top: 0.3rem;">Obtenha gratuitamente em <a href="https://console.groq.com/keys" target="_blank" style="color: var(--prism-cyan);">console.groq.com</a></small>
            </div>

            <!-- OpenAI -->
            <div id="key_openai" class="ai-key-group" style="margin-top: 1rem;">
                <label>OpenAI API Key</label>
                <input type="text" name="ai_openai_key" class="form-control" placeholder="sk-..." value="<?= htmlspecialchars($config['ai_openai_key'] ?? '') ?>">
                <small style="color: var(--text-secondary); display: block; margin-top: 0.3rem;">Obtenha em <a href="https://platform.openai.com/api-keys" target="_blank" style="color: var(--prism-cyan);">platform.openai.com</a></small>
            </div>

            <!-- OpenRouter -->
            <div id="key_openrouter" class="ai-key-group" style="margin-top: 1rem;">
                <label>OpenRouter API Key</label>
                <input type="text" name="ai_openrouter_key" class="form-control" placeholder="sk-or-..." value="<?= htmlspecialchars($config['ai_openrouter_key'] ?? '') ?>">
                <small style="color: var(--text-secondary); display: block; margin-top: 0.3rem;">Obtenha em <a href="https://openrouter.ai/keys" target="_blank" style="color: var(--prism-cyan);">openrouter.ai</a></small>
            </div>
        </div>

        <button type="submit" class="btn" style="margin-top: 2rem;">Salvar Configurações</button>
    </form>
</div>

<script>
function toggleApiKeys() {
    const provider = document.getElementById('ai_provider').value;
    document.querySelectorAll('.ai-key-group').forEach(el => el.style.display = 'none');
    document.getElementById('key_' + provider).style.display = 'block';
}
toggleApiKeys();
</script>

<?php require_once 'includes/footer.php'; ?>
