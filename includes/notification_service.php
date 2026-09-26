<?php
/**
 * Serviço Unificado de Notificações - ISP Preparatórios
 * 
 * Responsável pelo envio resiliente de notificações por E-mail (SMTP/mail)
 * e WhatsApp (Evolution API, Z-API, Webhooks) para o Administrador e Alunos.
 */

if (!function_exists('get_config')) {
    require_once __DIR__ . '/../db_config.php';
}

class NotificationService {

    /**
     * Recupera os contatos configurados do Administrador
     */
    public static function getAdminContacts($pdo, $config = null) {
        if (!$config) {
            $config = get_config($pdo);
        }

        // 1. Resolução do E-mail do Administrador
        $adminEmail = trim($config['notify_admin_email'] ?? '');
        if (empty($adminEmail)) {
            $adminEmail = trim($config['email'] ?? '');
        }
        if (empty($adminEmail)) {
            try {
                $stmt = $pdo->query("SELECT email FROM admin_usuarios ORDER BY id ASC LIMIT 1");
                $adminEmail = $stmt->fetchColumn() ?: '';
            } catch (Exception $e) {
                $adminEmail = '';
            }
        }

        // 2. Resolução do WhatsApp do Administrador
        $adminPhone = trim($config['notify_admin_whatsapp'] ?? '');
        if (empty($adminPhone)) {
            $adminPhone = trim($config['phone'] ?? '');
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $adminPhone);
        if (!empty($cleanPhone)) {
            // Se tiver 10 ou 11 dígitos, adicionar DDI 55 do Brasil se necessário
            if (strlen($cleanPhone) === 10 || strlen($cleanPhone) === 11) {
                $cleanPhone = '55' . $cleanPhone;
            }
        }

        return [
            'email' => $adminEmail,
            'phone' => $adminPhone,
            'phone_clean' => $cleanPhone,
            'email_enabled' => (bool)($config['notify_email_enabled'] ?? 1),
            'whatsapp_enabled' => (bool)($config['notify_whatsapp_enabled'] ?? 1),
            'notify_on_reservation' => (bool)($config['notify_on_reservation'] ?? 1),
            'notify_on_event' => (bool)($config['notify_on_event'] ?? 1),
            'notify_on_contact' => (bool)($config['notify_on_contact'] ?? 1),
            'notify_on_lead' => (bool)($config['notify_on_lead'] ?? 0),
        ];
    }

    /**
     * Notifica o administrador sobre uma nova Reserva de Vaga / Turma
     */
    public static function notifyAdminNewReservation($pdo, array $reserva) {
        $config = get_config($pdo);
        $admin = self::getAdminContacts($pdo, $config);

        if (!$admin['notify_on_reservation']) {
            return;
        }

        $nome = $reserva['name'] ?? 'Não informado';
        $email = $reserva['email'] ?? 'Não informado';
        $telefone = $reserva['phone'] ?? 'Não informado';
        $turma = $reserva['campaign_title'] ?? 'Turma / Preparatório';
        $modalidade = ucfirst($reserva['preferred_modality'] ?? 'Presencial');
        $cidadeUf = trim(($reserva['city'] ?? '') . ' / ' . ($reserva['state'] ?? ''), ' /');
        if (empty($cidadeUf)) $cidadeUf = 'Não especificada';
        $protocolo = $reserva['protocol'] ?? ('ISP-' . ($reserva['reservation_id'] ?? 'NOVO'));
        $isFila = !empty($reserva['is_waiting_list']) ? 'Sim (Lista de Espera)' : 'Não (Vaga Principal)';

        $studentPhoneDigits = preg_replace('/[^0-9]/', '', $telefone);
        if (strlen($studentPhoneDigits) === 10 || strlen($studentPhoneDigits) === 11) {
            $studentPhoneDigits = '55' . $studentPhoneDigits;
        }
        $whatsappStudentUrl = !empty($studentPhoneDigits) ? "https://wa.me/{$studentPhoneDigits}" : '';

        // Assunto e Títulos
        $subject = "🚨 Nova Reserva de Vaga: {$nome} - {$turma}";
        $badgeText = !empty($reserva['is_waiting_list']) ? "LISTA DE ESPERA" : "NOVA RESERVA";
        $badgeColor = !empty($reserva['is_waiting_list']) ? "#ffc107" : "#28a745";

        $details = [
            'Aluno(a)' => $nome,
            'WhatsApp' => $telefone,
            'E-mail' => $email,
            'Turma / Campanha' => $turma,
            'Modalidade' => $modalidade,
            'Cidade / UF' => $cidadeUf,
            'Lista de Espera?' => $isFila,
            'Protocolo' => $protocolo,
            'Data / Hora' => date('d/m/Y \à\s H:i')
        ];

        // Mensagem WhatsApp para o Admin
        $whatsappMsg = "🔔 *NOVA RESERVA DE VAGA RECEBIDA!*\n";
        $whatsappMsg .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $whatsappMsg .= "👤 *Aluno(a):* {$nome}\n";
        $whatsappMsg .= "📱 *WhatsApp:* {$telefone}\n";
        $whatsappMsg .= "✉️ *E-mail:* {$email}\n";
        $whatsappMsg .= "🎯 *Turma:* {$turma}\n";
        $whatsappMsg .= "📍 *Modalidade:* {$modalidade}\n";
        $whatsappMsg .= "🏙️ *Cidade/UF:* {$cidadeUf}\n";
        $whatsappMsg .= "🏷️ *Status:* {$badgeText}\n";
        $whatsappMsg .= "🔖 *Protocolo:* {$protocolo}\n";
        $whatsappMsg .= "📅 *Data:* " . date('d/m/Y H:i') . "\n";
        $whatsappMsg .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
        if (!empty($whatsappStudentUrl)) {
            $whatsappMsg .= "👉 *Chamar Aluno no Zap:* {$whatsappStudentUrl}\n";
        }
        $whatsappMsg .= "🚀 *Painel Admin:* " . self::getBaseUrl() . "/admin/gerenciar-reservas.php";

        // 1. Enviar E-mail ao Administrador
        if ($admin['email_enabled'] && !empty($admin['email'])) {
            $html = self::renderHtmlEmail(
                "Nova Reserva Confirmada",
                "Um novo interessado acabou de garantir lugar na turma <strong>" . htmlspecialchars($turma) . "</strong>.",
                $badgeText,
                $badgeColor,
                $details,
                $whatsappStudentUrl,
                self::getBaseUrl() . "/admin/reserva-detalhes.php?id=" . ($reserva['reservation_id'] ?? '')
            );
            self::sendEmail($config, $admin['email'], $subject, $html, $whatsappMsg, 'reserva', $pdo);
        }

        // 2. Enviar WhatsApp ao Administrador
        if ($admin['whatsapp_enabled'] && !empty($admin['phone_clean'])) {
            self::sendWhatsApp($config, $admin['phone_clean'], $whatsappMsg, [
                'type' => 'reserva',
                'reservation_id' => $reserva['reservation_id'] ?? null,
                'lead_name' => $nome,
                'lead_phone' => $telefone
            ], $pdo);
        }
    }

    /**
     * Notifica o administrador sobre uma nova Inscrição em Evento / Aulão
     */
    public static function notifyAdminNewEventRegistration($pdo, array $inscricao) {
        $config = get_config($pdo);
        $admin = self::getAdminContacts($pdo, $config);

        if (!$admin['notify_on_event']) {
            return;
        }

        $nome = $inscricao['name'] ?? 'Não informado';
        $email = $inscricao['email'] ?? 'Não informado';
        $telefone = $inscricao['phone'] ?? 'Não informado';
        $evento = $inscricao['event_title'] ?? 'Evento / Aulão';
        $dataEvento = !empty($inscricao['event_date']) ? date('d/m/Y H:i', strtotime($inscricao['event_date'])) : 'Data a confirmar';
        $modalidade = ucfirst($inscricao['modality'] ?? 'Presencial');
        $protocolo = $inscricao['protocol'] ?? ('EVT-' . ($inscricao['registration_id'] ?? 'NOVO'));

        $studentPhoneDigits = preg_replace('/[^0-9]/', '', $telefone);
        if (strlen($studentPhoneDigits) === 10 || strlen($studentPhoneDigits) === 11) {
            $studentPhoneDigits = '55' . $studentPhoneDigits;
        }
        $whatsappStudentUrl = !empty($studentPhoneDigits) ? "https://wa.me/{$studentPhoneDigits}" : '';

        $subject = "🎟️ Nova Inscrição em Evento: {$nome} - {$evento}";
        $badgeText = "EVENTO / AULÃO";
        $badgeColor = "#ff8000";

        $details = [
            'Participante' => $nome,
            'WhatsApp' => $telefone,
            'E-mail' => $email,
            'Evento / Aulão' => $evento,
            'Data do Evento' => $dataEvento,
            'Modalidade' => $modalidade,
            'Protocolo' => $protocolo,
            'Data da Inscrição' => date('d/m/Y \à\s H:i')
        ];

        // Mensagem WhatsApp para o Admin
        $whatsappMsg = "🎟️ *NOVA INSCRIÇÃO EM EVENTO / AULÃO!*\n";
        $whatsappMsg .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $whatsappMsg .= "👤 *Participante:* {$nome}\n";
        $whatsappMsg .= "📱 *WhatsApp:* {$telefone}\n";
        $whatsappMsg .= "✉️ *E-mail:* {$email}\n";
        $whatsappMsg .= "🎯 *Evento:* {$evento}\n";
        $whatsappMsg .= "📅 *Data do Evento:* {$dataEvento}\n";
        $whatsappMsg .= "📍 *Modalidade:* {$modalidade}\n";
        $whatsappMsg .= "🔖 *Protocolo:* {$protocolo}\n";
        $whatsappMsg .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
        if (!empty($whatsappStudentUrl)) {
            $whatsappMsg .= "👉 *Chamar Participante no Zap:* {$whatsappStudentUrl}\n";
        }
        $whatsappMsg .= "🚀 *Painel ISP:* " . self::getBaseUrl() . "/admin/gerenciar-eventos.php";

        // 1. Enviar E-mail ao Administrador
        if ($admin['email_enabled'] && !empty($admin['email'])) {
            $html = self::renderHtmlEmail(
                "Nova Inscrição em Evento",
                "Um novo participante acabou de se inscrever para o evento <strong>" . htmlspecialchars($evento) . "</strong>.",
                $badgeText,
                $badgeColor,
                $details,
                $whatsappStudentUrl,
                self::getBaseUrl() . "/admin/inscricoes.php?tipo=evento"
            );
            self::sendEmail($config, $admin['email'], $subject, $html, $whatsappMsg, 'evento', $pdo);
        }

        // 2. Enviar WhatsApp ao Administrador
        if ($admin['whatsapp_enabled'] && !empty($admin['phone_clean'])) {
            self::sendWhatsApp($config, $admin['phone_clean'], $whatsappMsg, [
                'type' => 'evento',
                'event_title' => $evento,
                'participant_name' => $nome,
                'participant_phone' => $telefone
            ], $pdo);
        }
    }

    /**
     * Notifica o administrador sobre uma Mensagem de Contato / Inscrição Geral
     */
    public static function notifyAdminNewInquiry($pdo, array $inquiry) {
        $config = get_config($pdo);
        $admin = self::getAdminContacts($pdo, $config);

        if (!$admin['notify_on_contact']) {
            return;
        }

        $nome = $inquiry['name'] ?? 'Não informado';
        $email = $inquiry['email'] ?? 'Não informado';
        $telefone = $inquiry['phone'] ?? 'Não informado';
        $curso = $inquiry['course_title'] ?? ($inquiry['curso'] ?? 'Contato Geral do Site');
        $mensagem = !empty($inquiry['message']) ? $inquiry['message'] : 'Solicitação de contato via site.';

        $studentPhoneDigits = preg_replace('/[^0-9]/', '', $telefone);
        if (strlen($studentPhoneDigits) === 10 || strlen($studentPhoneDigits) === 11) {
            $studentPhoneDigits = '55' . $studentPhoneDigits;
        }
        $whatsappStudentUrl = !empty($studentPhoneDigits) ? "https://wa.me/{$studentPhoneDigits}" : '';

        $subject = "💬 Novo Contato / Inscrição: {$nome} ({$curso})";
        $badgeText = "CONTATO / LEAD";
        $badgeColor = "#0077b6";

        $details = [
            'Nome' => $nome,
            'WhatsApp / Tel' => $telefone,
            'E-mail' => $email,
            'Interesse / Assunto' => $curso,
            'Mensagem' => $mensagem,
            'Data / Hora' => date('d/m/Y \à\s H:i')
        ];

        // Mensagem WhatsApp para o Admin
        $whatsappMsg = "💬 *NOVO CONTATO / INTERESSE RECEBIDO!*\n";
        $whatsappMsg .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $whatsappMsg .= "👤 *Nome:* {$nome}\n";
        $whatsappMsg .= "📱 *Telefone/WhatsApp:* {$telefone}\n";
        $whatsappMsg .= "✉️ *E-mail:* {$email}\n";
        $whatsappMsg .= "🎯 *Interesse:* {$curso}\n";
        $whatsappMsg .= "📝 *Mensagem:* " . substr($mensagem, 0, 150) . (strlen($mensagem) > 150 ? '...' : '') . "\n";
        $whatsappMsg .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
        if (!empty($whatsappStudentUrl)) {
            $whatsappMsg .= "👉 *Chamar no Zap:* {$whatsappStudentUrl}\n";
        }
        $whatsappMsg .= "🚀 *Painel ISP:* " . self::getBaseUrl() . "/admin/inscricoes.php";

        // 1. Enviar E-mail ao Administrador
        if ($admin['email_enabled'] && !empty($admin['email'])) {
            $html = self::renderHtmlEmail(
                "Novo Contato do Site",
                "Um visitante enviou uma mensagem de interesse através do formulário de contato/inscrição.",
                $badgeText,
                $badgeColor,
                $details,
                $whatsappStudentUrl,
                self::getBaseUrl() . "/admin/inscricoes.php"
            );
            self::sendEmail($config, $admin['email'], $subject, $html, $whatsappMsg, 'contato', $pdo);
        }

        // 2. Enviar WhatsApp ao Administrador
        if ($admin['whatsapp_enabled'] && !empty($admin['phone_clean'])) {
            self::sendWhatsApp($config, $admin['phone_clean'], $whatsappMsg, [
                'type' => 'contato',
                'name' => $nome,
                'phone' => $telefone
            ], $pdo);
        }
    }

    /**
     * Notifica o administrador sobre um novo Lead de Aula Gratuita / Material
     */
    public static function notifyAdminNewLead($pdo, array $lead) {
        $config = get_config($pdo);
        $admin = self::getAdminContacts($pdo, $config);

        if (!$admin['notify_on_lead']) {
            return;
        }

        $nome = $lead['name'] ?? 'Não informado';
        $email = $lead['email'] ?? 'Não informado';
        $telefone = $lead['phone'] ?? 'Não informado';
        $material = $lead['material_title'] ?? ($lead['campaign_code'] ?? 'Aulas Gratuitas');

        $studentPhoneDigits = preg_replace('/[^0-9]/', '', $telefone);
        if (strlen($studentPhoneDigits) === 10 || strlen($studentPhoneDigits) === 11) {
            $studentPhoneDigits = '55' . $studentPhoneDigits;
        }
        $whatsappStudentUrl = !empty($studentPhoneDigits) ? "https://wa.me/{$studentPhoneDigits}" : '';

        $subject = "📥 Novo Lead (Material Gratuito): {$nome}";
        $badgeText = "MATERIAL GRATUITO";
        $badgeColor = "#17a2b8";

        $details = [
            'Lead' => $nome,
            'WhatsApp' => $telefone,
            'E-mail' => $email,
            'Material / Campanha' => $material,
            'Data' => date('d/m/Y \à\s H:i')
        ];

        $whatsappMsg = "📥 *NOVO DOWNLOAD DE MATERIAL GRATUITO!*\n";
        $whatsappMsg .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $whatsappMsg .= "👤 *Lead:* {$nome}\n";
        $whatsappMsg .= "📱 *WhatsApp:* {$telefone}\n";
        $whatsappMsg .= "✉️ *E-mail:* {$email}\n";
        $whatsappMsg .= "📄 *Material:* {$material}\n";
        $whatsappMsg .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
        if (!empty($whatsappStudentUrl)) {
            $whatsappMsg .= "👉 *Chamar no WhatsApp:* {$whatsappStudentUrl}\n";
        }

        if ($admin['email_enabled'] && !empty($admin['email'])) {
            $html = self::renderHtmlEmail(
                "Novo Lead Cadastrado",
                "Um visitante baixou material gratuito na área de Aulas Gratuitas.",
                $badgeText,
                $badgeColor,
                $details,
                $whatsappStudentUrl,
                self::getBaseUrl() . "/admin/gerenciar-leads.php"
            );
            self::sendEmail($config, $admin['email'], $subject, $html, $whatsappMsg, 'lead_material', $pdo);
        }

        if ($admin['whatsapp_enabled'] && !empty($admin['phone_clean'])) {
            self::sendWhatsApp($config, $admin['phone_clean'], $whatsappMsg, [
                'type' => 'lead_material',
                'name' => $nome,
                'phone' => $telefone
            ], $pdo);
        }
    }

    /**
     * Envia E-mail (Via SMTP autenticado ou mail nativo do PHP)
     */
    public static function sendEmail($config, $recipientEmail, $subject, $htmlContent, $plainContent = '', $eventType = 'sistema', $pdo = null) {
        $recipientEmail = trim($recipientEmail);
        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            self::logNotification($pdo, 'email', $eventType, $recipientEmail, $subject, 'E-mail do destinatário inválido', 'failed', 'Endereço de e-mail inválido');
            return ['success' => false, 'message' => 'E-mail do destinatário inválido.'];
        }

        $smtpEnabled = !empty($config['smtp_enabled']);
        $fromEmail = !empty($config['smtp_from_email']) ? trim($config['smtp_from_email']) : (!empty($config['email']) ? trim($config['email']) : 'noreply@isppreparatorios.com.br');
        $fromName = !empty($config['smtp_from_name']) ? trim($config['smtp_from_name']) : 'ISP Preparatórios';

        $success = false;
        $errorMsg = null;

        if ($smtpEnabled && !empty($config['smtp_host'])) {
            // Envio via SMTP
            $smtpResult = self::sendViaSmtp($config, $recipientEmail, $fromEmail, $fromName, $subject, $htmlContent, $plainContent);
            $success = $smtpResult['success'];
            $errorMsg = $smtpResult['message'];
        } else {
            // Envio via PHP mail() nativo
            $headers = [];
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=UTF-8';
            $headers[] = 'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromEmail . '>';
            $headers[] = 'Reply-To: ' . $fromEmail;
            $headers[] = 'X-Mailer: PHP/' . phpversion();

            // Desativa temporariamente exibição de erros no mail() para não interromper requisição
            $oldTrack = ini_set('track_errors', '1');
            $sent = @mail($recipientEmail, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlContent, implode("\r\n", $headers));
            ini_set('track_errors', $oldTrack ?: '0');

            if ($sent) {
                $success = true;
                $errorMsg = 'Enviado com sucesso via PHP mail()';
            } else {
                $success = false;
                $errorMsg = 'Falha ao enviar via PHP mail(). O servidor pode não ter sendmail/postfix configurado. Recomenda-se ativar SMTP nas configurações.';
            }
        }

        $status = $success ? 'sent' : 'failed';
        self::logNotification($pdo, 'email', $eventType, $recipientEmail, $subject, substr($plainContent ?: strip_tags($htmlContent), 0, 300), $status, $success ? null : $errorMsg);

        return ['success' => $success, 'message' => $errorMsg];
    }

    /**
     * Envia mensagem via Gateway de WhatsApp
     */
    public static function sendWhatsApp($config, $recipientPhone, $messageText, array $extraContext = [], $pdo = null) {
        $cleanPhone = preg_replace('/[^0-9]/', '', $recipientPhone);
        if (empty($cleanPhone)) {
            self::logNotification($pdo, 'whatsapp', $extraContext['type'] ?? 'sistema', $recipientPhone, 'Notificação WhatsApp', $messageText, 'failed', 'Telefone de destino inválido');
            return ['success' => false, 'message' => 'Número de telefone do WhatsApp inválido.'];
        }

        $provider = $config['whatsapp_api_provider'] ?? 'none';
        $apiUrl = rtrim(trim($config['whatsapp_api_url'] ?? ''), '/');
        $apiKey = trim($config['whatsapp_api_key'] ?? '');
        $instance = trim($config['whatsapp_api_instance'] ?? '');

        if ($provider === 'none' || empty($apiUrl)) {
            // Provedor de API não configurado. Registramos como link direto (manual link pronto)
            $waLink = self::generateWaMeLink($cleanPhone, $messageText);
            self::logNotification($pdo, 'whatsapp', $extraContext['type'] ?? 'sistema', $cleanPhone, 'Notificação WhatsApp (Link)', $messageText, 'sent', 'API de WhatsApp não configurada. Link wa.me gerado para disparo manual: ' . $waLink);
            return [
                'success' => true,
                'status' => 'manual_link',
                'message' => 'Link do WhatsApp gerado para envio.',
                'link' => $waLink
            ];
        }

        $endpoint = '';
        $headers = ['Content-Type: application/json'];
        $body = [];

        if ($provider === 'evolution') {
            // Evolution API (v1 / v2)
            $endpoint = $apiUrl . '/message/sendText/' . ($instance ?: 'isp');
            $headers[] = 'apikey: ' . $apiKey;
            $body = [
                'number' => $cleanPhone,
                'text' => $messageText,
                'options' => [
                    'delay' => 1200,
                    'presence' => 'composing'
                ]
            ];
        } elseif ($provider === 'zapi') {
            // Z-API
            $endpoint = $apiUrl . '/instances/' . $instance . '/token/' . $apiKey . '/send-text';
            $headers[] = 'Client-Token: ' . $apiKey;
            $body = [
                'phone' => $cleanPhone,
                'message' => $messageText
            ];
        } elseif ($provider === 'webhook') {
            // Webhook Genérico / Outra API
            $endpoint = $apiUrl;
            if (!empty($apiKey)) {
                $headers[] = 'Authorization: Bearer ' . $apiKey;
            }
            $body = [
                'phone' => $cleanPhone,
                'message' => $messageText,
                'context' => $extraContext,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }

        if (empty($endpoint)) {
            self::logNotification($pdo, 'whatsapp', $extraContext['type'] ?? 'sistema', $cleanPhone, 'Notificação WhatsApp', $messageText, 'failed', 'Endpoint do provedor WhatsApp não configurado');
            return ['success' => false, 'message' => 'Configuração de WhatsApp incompleta.'];
        }

        // Disparo cURL com timeout curto (5s) para não prender o usuário
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 6,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            self::logNotification($pdo, 'whatsapp', $extraContext['type'] ?? 'sistema', $cleanPhone, 'Notificação WhatsApp', $messageText, 'failed', 'Erro cURL: ' . $curlError);
            return ['success' => false, 'message' => 'Erro de conexão com o Gateway WhatsApp: ' . $curlError];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            self::logNotification($pdo, 'whatsapp', $extraContext['type'] ?? 'sistema', $cleanPhone, 'Notificação WhatsApp', $messageText, 'sent', null);
            return ['success' => true, 'message' => 'Notificação WhatsApp enviada com sucesso ao administrador!'];
        }

        $errorMsg = "Gateway WhatsApp retornou status HTTP {$httpCode}: " . substr($response, 0, 200);
        self::logNotification($pdo, 'whatsapp', $extraContext['type'] ?? 'sistema', $cleanPhone, 'Notificação WhatsApp', $messageText, 'failed', $errorMsg);
        return ['success' => false, 'message' => $errorMsg];
    }

    /**
     * Envio puro via protocolo SMTP (Sem dependência de bibliotecas externas)
     */
    private static function sendViaSmtp($config, $to, $fromEmail, $fromName, $subject, $htmlBody, $plainBody) {
        $host = trim($config['smtp_host'] ?? '');
        $port = (int)($config['smtp_port'] ?? 587);
        $user = trim($config['smtp_user'] ?? '');
        $pass = trim($config['smtp_pass'] ?? '');
        $secure = strtolower(trim($config['smtp_secure'] ?? 'tls'));

        $prefix = '';
        if ($secure === 'ssl' || $port === 465) {
            $prefix = 'ssl://';
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $socket = @stream_socket_client($prefix . $host . ':' . $port, $errno, $errstr, 6, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            return ['success' => false, 'message' => "Não foi possível conectar ao servidor SMTP ($host:$port): $errstr (Código $errno)"];
        }

        stream_set_timeout($socket, 6);

        $getResponse = function() use ($socket) {
            $res = '';
            while ($str = fgets($socket, 515)) {
                $res .= $str;
                if (substr($str, 3, 1) === ' ') break;
            }
            return $res;
        };

        $sendCommand = function($cmd) use ($socket, $getResponse) {
            fputs($socket, $cmd . "\r\n");
            return $getResponse();
        };

        $greeting = $getResponse();
        if (substr($greeting, 0, 3) !== '220') {
            fclose($socket);
            return ['success' => false, 'message' => "Servidor SMTP não respondeu código 220: $greeting"];
        }

        $ehlo = $sendCommand("EHLO " . (gethostname() ?: 'localhost'));

        // STARTTLS se porta 587 ou secure=tls
        if ($secure === 'tls' && $prefix === '') {
            $tlsRes = $sendCommand("STARTTLS");
            if (substr($tlsRes, 0, 3) === '220') {
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    fclose($socket);
                    return ['success' => false, 'message' => 'Falha ao estabelecer criptografia STARTTLS com o servidor SMTP.'];
                }
                $ehlo = $sendCommand("EHLO " . (gethostname() ?: 'localhost'));
            }
        }

        // Autenticação AUTH LOGIN
        if (!empty($user)) {
            $authRes = $sendCommand("AUTH LOGIN");
            if (substr($authRes, 0, 3) !== '334') {
                fclose($socket);
                return ['success' => false, 'message' => "Servidor SMTP rejeitou comando AUTH LOGIN: $authRes"];
            }

            $userRes = $sendCommand(base64_encode($user));
            if (substr($userRes, 0, 3) !== '334') {
                fclose($socket);
                return ['success' => false, 'message' => "Usuário SMTP rejeitado: $userRes"];
            }

            $passRes = $sendCommand(base64_encode($pass));
            if (substr($passRes, 0, 3) !== '235') {
                fclose($socket);
                return ['success' => false, 'message' => "Senha SMTP rejeitada ou credenciais inválidas: $passRes"];
            }
        }

        // MAIL FROM
        $mailFromRes = $sendCommand("MAIL FROM: <$fromEmail>");
        if (substr($mailFromRes, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'message' => "Erro no comando MAIL FROM: $mailFromRes"];
        }

        // RCPT TO
        $rcptRes = $sendCommand("RCPT TO: <$to>");
        if (substr($rcptRes, 0, 3) !== '250' && substr($rcptRes, 0, 3) !== '251') {
            fclose($socket);
            return ['success' => false, 'message' => "Destinatário rejeitado pelo servidor SMTP ($to): $rcptRes"];
        }

        // DATA
        $dataRes = $sendCommand("DATA");
        if (substr($dataRes, 0, 3) !== '354') {
            fclose($socket);
            return ['success' => false, 'message' => "Erro no comando DATA: $dataRes"];
        }

        $boundary = "bnd_" . md5(uniqid(time()));
        $mimeMsg = "MIME-Version: 1.0\r\n";
        $mimeMsg .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>\r\n";
        $mimeMsg .= "To: <$to>\r\n";
        $mimeMsg .= "Date: " . date('r') . "\r\n";
        $mimeMsg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $mimeMsg .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $mimeMsg .= "\r\n";

        // Plain text version
        $mimeMsg .= "--$boundary\r\n";
        $mimeMsg .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $mimeMsg .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $mimeMsg .= chunk_split(base64_encode($plainBody ?: strip_tags($htmlBody))) . "\r\n";

        // HTML version
        $mimeMsg .= "--$boundary\r\n";
        $mimeMsg .= "Content-Type: text/html; charset=UTF-8\r\n";
        $mimeMsg .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $mimeMsg .= chunk_split(base64_encode($htmlBody)) . "\r\n";

        $mimeMsg .= "--$boundary--\r\n";
        $mimeMsg .= ".\r\n";

        fputs($socket, $mimeMsg);
        $finalRes = $getResponse();
        $sendCommand("QUIT");
        fclose($socket);

        if (substr($finalRes, 0, 3) === '250') {
            return ['success' => true, 'message' => 'E-mail entregue com sucesso via SMTP!'];
        }

        return ['success' => false, 'message' => "Servidor SMTP não confirmou entrega da mensagem: $finalRes"];
    }

    /**
     * Gera link amigável do WhatsApp com mensagem codificada
     */
    public static function generateWaMeLink($phone, $message) {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($clean) === 10 || strlen($clean) === 11) {
            $clean = '55' . $clean;
        }
        return "https://wa.me/{$clean}?text=" . urlencode($message);
    }

    /**
     * Renderiza o template de E-mail HTML Premium com design ISP Preparatórios
     */
    public static function renderHtmlEmail($title, $subtitle, $badgeText, $badgeColor, array $details, $whatsappStudentUrl = '', $adminDashboardUrl = '') {
        $rowsHtml = '';
        foreach ($details as $label => $value) {
            $rowsHtml .= '<tr>
                <td style="padding: 10px 14px; border-bottom: 1px solid #eef2f6; font-size: 13px; font-weight: 600; color: #555; width: 35%; background: #fafbfc;">' . htmlspecialchars($label) . '</td>
                <td style="padding: 10px 14px; border-bottom: 1px solid #eef2f6; font-size: 14px; color: #1e293b; font-weight: 500;">' . nl2br(htmlspecialchars($value)) . '</td>
            </tr>';
        }

        $buttonsHtml = '';
        if (!empty($whatsappStudentUrl)) {
            $buttonsHtml .= '<a href="' . htmlspecialchars($whatsappStudentUrl) . '" target="_blank" style="display: inline-block; background: #25d366; color: #ffffff; text-decoration: none; padding: 12px 22px; border-radius: 8px; font-weight: 700; font-size: 14px; margin-right: 10px; margin-bottom: 10px; box-shadow: 0 4px 12px rgba(37,211,102,0.35);">
                <span style="font-size: 16px;">💬</span> Abrir WhatsApp do Aluno
            </a>';
        }
        if (!empty($adminDashboardUrl)) {
            $buttonsHtml .= '<a href="' . htmlspecialchars($adminDashboardUrl) . '" target="_blank" style="display: inline-block; background: #03045e; color: #ffffff; text-decoration: none; padding: 12px 22px; border-radius: 8px; font-weight: 700; font-size: 14px; margin-bottom: 10px; box-shadow: 0 4px 12px rgba(3,4,94,0.35);">
                <span>🚀</span> Ver no Painel Administrativo
            </a>';
        }

        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 30px 15px;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table role="presentation" width="100%" style="max-width: 620px; background: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #e2e8f0;" cellspacing="0" cellpadding="0">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #03045e 0%, #0077b6 100%); padding: 30px 35px; text-align: left; position: relative;">
                            <div style="display: inline-block; background: {$badgeColor}; color: #ffffff; font-size: 11px; font-weight: 800; letter-spacing: 1px; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; margin-bottom: 12px;">
                                {$badgeText}
                            </div>
                            <h1 style="color: #ffffff; margin: 0 0 8px 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px;">
                                {$title}
                            </h1>
                            <p style="color: rgba(255,255,255,0.85); margin: 0; font-size: 14px; line-height: 1.5;">
                                {$subtitle}
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 28px 35px 20px;">
                            <h3 style="color: #03045e; font-size: 15px; margin: 0 0 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #ff8000; padding-bottom: 6px; display: inline-block;">
                                Detalhes do Aluno / Inscrição
                            </h3>
                            
                            <table role="presentation" width="100%" style="border: 1px solid #eef2f6; border-radius: 8px; overflow: hidden; margin-bottom: 25px; border-collapse: collapse;">
                                {$rowsHtml}
                            </table>

                            <!-- Action Buttons -->
                            <div style="text-align: center; margin: 25px 0 10px;">
                                {$buttonsHtml}
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background: #f8fafc; padding: 20px 35px; border-top: 1px solid #eef2f6; text-align: center;">
                            <p style="margin: 0 0 6px; font-size: 12px; color: #64748b; font-weight: 600;">
                                ISP Preparatórios — Sistema de Gestão de Leads e Reservas
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #94a3b8;">
                                Esta é uma notificação automática gerada pelo site oficial em {$year}.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Registra evento no log de auditoria de notificações
     */
    public static function logNotification($pdo, $channel, $eventType, $recipient, $title, $preview, $status, $errorMessage = null) {
        if (!$pdo) return;
        try {
            $stmt = $pdo->prepare("INSERT INTO notifications_log (channel, event_type, recipient, title, message_preview, status, error_message) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $channel,
                $eventType,
                $recipient ?: 'Não especificado',
                $title,
                $preview,
                $status,
                $errorMessage
            ]);
        } catch (Exception $e) {
            // falha silenciosa para não travar fluxo principal
        }
    }

    /**
     * Retorna a URL base do site de forma dinâmica e segura
     */
    public static function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? 'isppreparatorios.com.br';
        if (empty($host) || $host === 'localhost') {
            $host = 'isppreparatorios.com.br';
        }
        return rtrim($protocol . $host, '/');
    }

    /**
     * Realiza um teste de envio de e-mail usando a configuração atual
     */
    public static function testEmailConnection($pdo, $targetEmail) {
        $config = get_config($pdo);
        $subject = "🧪 Teste de Notificação por E-mail - ISP Preparatórios";
        $details = [
            'Destinatário' => $targetEmail,
            'Status' => 'Conexão Estabelecida com Sucesso',
            'Método de Envio' => !empty($config['smtp_enabled']) ? 'SMTP Autenticado' : 'PHP mail() Nativo',
            'Servidor SMTP' => !empty($config['smtp_host']) ? ($config['smtp_host'] . ':' . ($config['smtp_port'] ?? '587')) : 'Nenhum (Local)',
            'Horário do Teste' => date('d/m/Y H:i:s')
        ];
        $html = self::renderHtmlEmail(
            "Teste de E-mail Bem-Sucedido!",
            "Seu servidor está configurado e pronto para disparar notificações automáticas de novas reservas e inscrições.",
            "TESTE OK",
            "#28a745",
            $details,
            '',
            self::getBaseUrl() . "/admin/configuracoes.php"
        );

        return self::sendEmail($config, $targetEmail, $subject, $html, "Teste de e-mail concluído com sucesso!", 'teste', $pdo);
    }

    /**
     * Realiza um teste de envio de mensagem no WhatsApp usando o Gateway configurado
     */
    public static function testWhatsAppConnection($pdo, $targetPhone) {
        $config = get_config($pdo);
        $cleanPhone = preg_replace('/[^0-9]/', '', $targetPhone);
        if (strlen($cleanPhone) === 10 || strlen($cleanPhone) === 11) {
            $cleanPhone = '55' . $cleanPhone;
        }

        $provider = $config['whatsapp_api_provider'] ?? 'none';
        $msg = "🧪 *TESTE DE INTEGRAÇÃO WHATSAPP - ISP PREPARATÓRIOS*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "✅ Sua conexão com o WhatsApp está funcionando perfeitamente!\n";
        $msg .= "📅 *Data/Hora:* " . date('d/m/Y H:i:s') . "\n";
        $msg .= "⚙️ *Provedor Ativo:* " . strtoupper($provider) . "\n";
        $msg .= "🔔 Você receberá notificações instantâneas aqui sempre que houver novas reservas ou inscrições de alunos no site.\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";

        return self::sendWhatsApp($config, $cleanPhone, $msg, ['type' => 'teste'], $pdo);
    }
}
