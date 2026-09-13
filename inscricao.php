<?php
// Ocultar widget de chat flutuante na página de inscrição para não sobrepor o formulário embed
$hide_leadconnector_chat = true;

require_once 'db_config.php';
require_once 'includes/header.php';
?>

<style>
.inscricao-page-wrapper {
    max-width: 760px;
    margin: 0 auto;
    padding: 3rem 1.5rem;
}
@media (max-width: 576px) {
    .inscricao-page-wrapper {
        padding: 1.8rem 1rem 3rem;
    }
    .inscricao-page-wrapper h2 {
        font-size: 1.75rem !important;
    }
    .inscricao-page-wrapper p {
        font-size: 0.92rem !important;
    }
    .inscricao-iframe-container {
        border-radius: 12px !important;
        box-shadow: none !important;
    }
}
.inscricao-iframe-container {
    padding: 0;
    min-height: 620px;
    width: 100%;
    overflow: hidden;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.02);
    box-shadow: 0 10px 35px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.08);
}
</style>

<div class="container inscricao-page-wrapper">
    <div class="section-header reveal" style="text-align: center; margin-bottom: 2rem;">
        <span class="hero-pre-title">INSCRIÇÃO</span>
        <h2 style="font-size: 2.2rem; font-weight: 800;">Garanta Sua Vaga</h2>
        <p style="color: var(--text-secondary); margin-top: 0.5rem; font-size: 1.05rem;">Preencha o formulário abaixo e nossa equipe entrará em contato com prioridade.</p>
    </div>

    <div class="reveal inscricao-iframe-container">
        <iframe
            src="https://api.leadconnectorhq.com/widget/form/Vl5jgvoIhC1umjjNY892"
            style="width:100%;min-height:620px;border:none;border-radius:12px;display:block;"
            id="inline-Vl5jgvoIhC1umjjNY892" 
            data-layout="{'id':'INLINE'}"
            data-trigger-type="alwaysShow"
            data-trigger-value=""
            data-activation-type="alwaysActivated"
            data-activation-value=""
            data-deactivation-type="neverDeactivate"
            data-deactivation-value=""
            data-form-name="Leads Site ISP"
            data-height="undefined"
            data-layout-iframe-id="inline-Vl5jgvoIhC1umjjNY892"
            data-form-id="Vl5jgvoIhC1umjjNY892"
            title="Leads Site ISP"
        >
        </iframe>
        <script src="https://link.msgsndr.com/js/form_embed.js"></script>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
