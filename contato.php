<?php
require_once 'db_config.php';
require_once 'includes/header.php';
?>

<div class="container section-padding" style="max-width: 700px;">
    <div class="section-header reveal" style="text-align: center;">
        <span class="hero-pre-title">COMUNICAÇÃO ENCRIPTADA</span>
        <h2>Central de Transmissão</h2>
    </div>
    
    <div class="content-block reveal" style="padding: 4rem 3rem;">
        <p style="font-family: var(--font-mono); text-align: center; color: var(--brand-orange); margin-bottom: 2rem;">// INSIRA SEUS DADOS PARA INICIAR PROTOCOLO</p>
        
        <form method="POST" action="inscricao.php">
            <input type="hidden" name="course_id" value="">
            <div class="form-group">
                <label>IDENTIFICAÇÃO (NOME)</label>
                <input type="text" name="name" required placeholder="Ex: Agente Alpha">
            </div>
            <div class="form-group">
                <label>PONTO DE RETORNO (E-MAIL)</label>
                <input type="email" name="email" required placeholder="alvo@servidor.com">
            </div>
            <div class="form-group">
                <label>FREQUÊNCIA (TELEFONE)</label>
                <input type="text" name="phone" placeholder="(00) 00000-0000">
            </div>
            <div class="form-group">
                <label>PACOTE DE DADOS (MENSAGEM)</label>
                <textarea name="message" rows="5" required placeholder="Descreva o suporte necessário..."></textarea>
            </div>
            <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">TRANSMITIR DADOS</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
