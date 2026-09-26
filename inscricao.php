<?php
// Ocultar widget de chat flutuante na página de inscrição para não sobrepor o formulário embed
$hide_leadconnector_chat = true;

require_once 'db_config.php';
require_once 'includes/notification_service.php';

$success_msg = false;
$error_msg = false;

// Processar Envio do Formulário de Contato / Inscrição Geral
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $course_id = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email)) {
        $error_msg = "Por favor, preencha seu nome e e-mail.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Por favor, informe um endereço de e-mail válido.";
    } else {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $phoneNormalized = (strlen($cleanPhone) === 10 || strlen($cleanPhone) === 11) ? ('55' . $cleanPhone) : $cleanPhone;

        // Obter nome do curso se informado
        $courseTitle = 'Contato Geral do Site';
        if ($course_id) {
            try {
                $stmtC = $pdo->prepare("SELECT title FROM cursos WHERE id = ? LIMIT 1");
                $stmtC->execute([$course_id]);
                $cNome = $stmtC->fetchColumn();
                if ($cNome) $courseTitle = $cNome;
            } catch (Exception $e) {}
        }

        try {
            // 1. Salvar na tabela de Inscrições
            $stmt = $pdo->prepare("INSERT INTO inscricoes (name, email, phone, course_id, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $course_id, $message]);
            $inscricaoId = $pdo->lastInsertId();

            // 2. Salvar ou atualizar na tabela de Leads Mestres
            try {
                $stmtFind = $pdo->prepare("SELECT id FROM leads WHERE email = ? LIMIT 1");
                $stmtFind->execute([$email]);
                $leadId = $stmtFind->fetchColumn();

                if ($leadId) {
                    $pdo->prepare("UPDATE leads SET name = ?, phone_original = ?, phone_normalized = ?, last_conversion = NOW() WHERE id = ?")->execute([$name, $phone, $phoneNormalized, $leadId]);
                } else {
                    $pdo->prepare("INSERT INTO leads (name, email, phone_original, phone_normalized, source, first_conversion, last_conversion) VALUES (?, ?, ?, ?, 'contato-site', NOW(), NOW())")->execute([$name, $email, $phone, $phoneNormalized]);
                }
            } catch (Exception $eLead) {}

            // 3. Disparar Notificação para o Administrador (E-mail & WhatsApp)
            try {
                NotificationService::notifyAdminNewInquiry($pdo, [
                    'id' => $inscricaoId,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'course_title' => $courseTitle,
                    'message' => $message,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            } catch (Exception $eNotif) {
                error_log("Aviso: Falha ao disparar notificação de contato: " . $eNotif->getMessage());
            }

            $success_msg = true;

            // Se for requisição AJAX, retornar JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                echo json_encode(['success' => true, 'message' => 'Sua mensagem foi transmitida com sucesso! Em breve entraremos em contato.']);
                exit;
            }

        } catch (Exception $e) {
            $error_msg = "Ocorreu um erro ao salvar seus dados. Por favor, tente novamente.";
        }
    }
}

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
        <span class="hero-pre-title">INSCRIÇÃO &amp; ATENDIMENTO</span>
        <h2 style="font-size: 2.2rem; font-weight: 800;">Garanta Sua Vaga</h2>
        <p style="color: var(--text-secondary); margin-top: 0.5rem; font-size: 1.05rem;">Preencha o formulário abaixo e nossa equipe entrará em contato com prioridade.</p>
    </div>

    <?php if ($success_msg): ?>
        <?php
            $siteConfig = get_config($pdo);
            $ispPhone = !empty($siteConfig['phone']) ? $siteConfig['phone'] : '99999999999';
            $cleanIspPhone = preg_replace('/[^0-9]/', '', $ispPhone);
            $zapCoordUrl = "https://wa.me/{$cleanIspPhone}?text=" . urlencode("Olá! Enviei meus dados pelo site do ISP Preparatórios e gostaria de atendimento.");
        ?>
        <div class="reveal" style="background: rgba(40, 167, 69, 0.12); border: 2px solid #28a745; border-radius: 16px; padding: 3rem 2rem; text-align: center; margin-bottom: 3rem; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <div style="width: 70px; height: 70px; background: #28a745; color: #fff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 1.5rem;">
                <i class="fas fa-check"></i>
            </div>
            <h3 style="font-size: 1.8rem; color: #fff; margin-bottom: 0.8rem; font-weight: 800;">Dados Recebidos com Sucesso!</h3>
            <p style="color: rgba(255,255,255,0.85); font-size: 1.05rem; line-height: 1.6; max-width: 520px; margin: 0 auto 2rem;">
                Nossa equipe de coordenação e atendimento já foi notificada. Entraremos em contato com você o mais breve possível pelo WhatsApp.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="<?= $zapCoordUrl ?>" target="_blank" class="btn" style="background: #25d366; border-color: #25d366; color: #fff; font-weight: 700; padding: 12px 24px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fab fa-whatsapp" style="font-size: 1.3rem;"></i> Conversar no WhatsApp Agora
                </a>
                <a href="/" class="btn btn-outline" style="padding: 12px 24px; border-radius: 8px;">
                    Voltar para o Início
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="alert alert-error" style="margin-bottom: 2rem; background: rgba(220,53,69,0.2); border: 1px solid #dc3545; color: #ff8888; padding: 1rem 1.5rem; border-radius: 8px;">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_msg) ?>
        </div>
    <?php endif; ?>

    <?php if (!$success_msg): ?>
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
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
