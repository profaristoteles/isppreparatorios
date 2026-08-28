<?php
/**
 * Utilitários e Lógicas de Negócio para Aulas Gratuitas - ISP Preparatórios
 */

require_once __DIR__ . '/../db_config.php';

/**
 * Obtém ou cria o token de sessão do visitante via Cookie seguro HttpOnly.
 */
function get_or_create_session_token() {
    $cookie_name = 'isp_session_token';
    if (!empty($_COOKIE[$cookie_name]) && preg_match('/^[a-f0-9]{32}$/', $_COOKIE[$cookie_name])) {
        return $_COOKIE[$cookie_name];
    }
    
    $token = bin2hex(random_bytes(16));
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    
    setcookie($cookie_name, $token, [
        'expires' => time() + (86400 * 365), // 1 ano
        'path' => '/',
        'domain' => '',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    $_COOKIE[$cookie_name] = $token;
    return $token;
}

/**
 * Normaliza número de telefone/WhatsApp para formato numérico internacional (E.164 sem +).
 * Exemplo: (99) 99999-9999 -> 5599999999999
 */
function normalize_phone_number($phone) {
    if (empty($phone)) return null;
    $digits = preg_replace('/\D/', '', $phone);
    if (empty($digits)) return null;
    
    // Se tiver 10 ou 11 dígitos (padrão Brasil com DDD), adiciona 55 no início
    if (strlen($digits) === 10 || strlen($digits) === 11) {
        $digits = '55' . $digits;
    }
    
    return $digits;
}

/**
 * Gera um token HMAC assinado para autorização segura de download de PDF.
 */
function generate_download_token($subject_type, $subject_id, $material_id, $video_id) {
    $secret = getenv('DOWNLOAD_SECRET_KEY') ?: 'isp_secret_fallback_key_2026_change_in_env';
    $ttl = (int)(getenv('DOWNLOAD_TOKEN_TTL') ?: 86400);
    $expires_at = time() + $ttl;
    
    $payload = implode('|', [$subject_type, (int)$subject_id, (int)$material_id, (int)$video_id, $expires_at]);
    $signature = hash_hmac('sha256', $payload, $secret);
    
    $raw_token = $payload . '.' . $signature;
    return rtrim(strtr(base64_encode($raw_token), '+/', '-_'), '=');
}

/**
 * Valida o token HMAC e retorna o array com a carga útil se válido.
 */
function verify_download_token($token) {
    if (empty($token)) return false;
    
    $raw_token = base64_decode(strtr($token, '-_', '+/'));
    if (!$raw_token || strpos($raw_token, '.') === false) return false;
    
    list($payload, $signature) = explode('.', $raw_token, 2);
    $secret = getenv('DOWNLOAD_SECRET_KEY') ?: 'isp_secret_fallback_key_2026_change_in_env';
    
    $expected_signature = hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expected_signature, $signature)) {
        return false;
    }
    
    $parts = explode('|', $payload);
    if (count($parts) !== 5) return false;
    
    list($subject_type, $subject_id, $material_id, $video_id, $expires_at) = $parts;
    
    if (time() > (int)$expires_at) {
        return false;
    }
    
    return [
        'subject_type' => $subject_type,
        'subject_id' => (int)$subject_id,
        'material_id' => (int)$material_id,
        'video_id' => (int)$video_id,
        'expires_at' => (int)$expires_at
    ];
}

/**
 * Registra evento analítico de videoaula com limite de 60 minutos para 'video_accessed' e 5 minutos para 'material_requested'.
 */
function log_free_video_event($pdo, $video_id, $event_type, $lead_id = null, $metadata = []) {
    $session_token = get_or_create_session_token();
    
    // Throttling de 60 min para visualização de vídeo
    if ($event_type === 'video_accessed') {
        $stmtCheck = $pdo->prepare("SELECT id FROM free_video_events WHERE video_id = ? AND session_token = ? AND event_type = 'video_accessed' AND created_at >= NOW() - INTERVAL 60 MINUTE LIMIT 1");
        $stmtCheck->execute([(int)$video_id, $session_token]);
        if ($stmtCheck->fetch()) {
            return false; // Ignora visualização redundante
        }
    }
    
    // Throttling de 5 min para requisição de material
    if ($event_type === 'material_requested') {
        $stmtCheck = $pdo->prepare("SELECT id FROM free_video_events WHERE video_id = ? AND session_token = ? AND event_type = 'material_requested' AND created_at >= NOW() - INTERVAL 5 MINUTE LIMIT 1");
        $stmtCheck->execute([(int)$video_id, $session_token]);
        if ($stmtCheck->fetch()) {
            return false; // Ignora duplo clique acidental
        }
    }
    
    $stmtIns = $pdo->prepare("INSERT INTO free_video_events (video_id, session_token, lead_id, event_type, metadata) VALUES (?, ?, ?, ?, ?)");
    $stmtIns->execute([
        (int)$video_id,
        $session_token,
        $lead_id ? (int)$lead_id : null,
        $event_type,
        !empty($metadata) ? json_encode($metadata) : null
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * Captura e deduplica Lead com tratamento estrito de conflitos de identidade e transação PDO.
 */
function process_lead_capture($pdo, $lead_data) {
    $email = strtolower(trim($lead_data['email'] ?? ''));
    $name = trim($lead_data['name'] ?? '');
    $phone_original = trim($lead_data['phone'] ?? '');
    $phone_normalized = normalize_phone_number($phone_original);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'error', 'message' => 'Informe um e-mail válido.'];
    }
    if (empty($name)) {
        return ['status' => 'error', 'message' => 'Por favor, informe seu nome completo.'];
    }
    if (empty($phone_normalized)) {
        return ['status' => 'error', 'message' => 'Por favor, informe um WhatsApp válido.'];
    }
    
    // Iniciar Transação PDO
    try {
        $pdo->beginTransaction();
        
        // 1. Procurar lead por E-mail
        $stmtEmail = $pdo->prepare("SELECT * FROM leads WHERE email = ? LIMIT 1");
        $stmtEmail->execute([$email]);
        $leadByEmail = $stmtEmail->fetch();
        
        // 2. Procurar lead por Telefone Normalizado
        $stmtPhone = $pdo->prepare("SELECT * FROM leads WHERE phone_normalized = ? LIMIT 1");
        $stmtPhone->execute([$phone_normalized]);
        $leadByPhone = $stmtPhone->fetch();
        
        // 3. CASO 5: Verificar Conflito de Identidade (E-mail -> Lead A, Telefone -> Lead B, A != B)
        if ($leadByEmail && $leadByPhone && $leadByEmail['id'] != $leadByPhone['id']) {
            // GRAVA O CONFLITO SEM MUTAR NENHUM DOS DOIS LEADS E SEM ASSOCIAR TAGS/CONSENTIMENTOS
            $stmtConf = $pdo->prepare("INSERT INTO lead_conflicts (existing_lead_id_email, existing_lead_id_phone, incoming_name, incoming_email, incoming_phone_normalized, payload, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmtConf->execute([
                $leadByEmail['id'],
                $leadByPhone['id'],
                $name,
                $email,
                $phone_normalized,
                json_encode($lead_data)
            ]);
            $conflict_id = $pdo->lastInsertId();
            
            $pdo->commit();
            return [
                'status' => 'conflict',
                'conflict_id' => $conflict_id,
                'message' => 'Conflito de dados registrado para análise administrativa.'
            ];
        }
        
        // Determinar lead a ser atualizado (se houver correspondência única)
        $targetLead = $leadByEmail ?: $leadByPhone;
        $session_token = get_or_create_session_token();
        
        if ($targetLead) {
            $lead_id = $targetLead['id'];
            
            // Atualiza Lead Existente (CASO 2, 3, 4)
            $stmtUp = $pdo->prepare("UPDATE leads SET last_conversion = NOW(), name = ?, phone_original = ?, phone_normalized = ?, utm_source = COALESCE(NULLIF(?, ''), utm_source), utm_medium = COALESCE(NULLIF(?, ''), utm_medium), utm_campaign = COALESCE(NULLIF(?, ''), utm_campaign), utm_content = COALESCE(NULLIF(?, ''), utm_content), utm_term = COALESCE(NULLIF(?, ''), utm_term) WHERE id = ?");
            $stmtUp->execute([
                $name,
                $phone_original,
                $phone_normalized,
                $lead_data['utm_source'] ?? '',
                $lead_data['utm_medium'] ?? '',
                $lead_data['utm_campaign'] ?? '',
                $lead_data['utm_content'] ?? '',
                $lead_data['utm_term'] ?? '',
                $lead_id
            ]);
        } else {
            // Cria Novo Lead (CASO 1)
            try {
                $stmtIns = $pdo->prepare("INSERT INTO leads (name, email, phone_original, phone_normalized, source, utm_source, utm_medium, utm_campaign, utm_content, utm_term) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtIns->execute([
                    $name,
                    $email,
                    $phone_original,
                    $phone_normalized,
                    $lead_data['source'] ?? 'aulas-gratuitas',
                    $lead_data['utm_source'] ?? '',
                    $lead_data['utm_medium'] ?? '',
                    $lead_data['utm_campaign'] ?? '',
                    $lead_data['utm_content'] ?? '',
                    $lead_data['utm_term'] ?? ''
                ]);
                $lead_id = $pdo->lastInsertId();
            } catch (PDOException $eRace) {
                // Tratamento de race condition de chave única (UNIQUE email ou phone_normalized)
                if ($eRace->getCode() == '23000') {
                    $stmtReFind = $pdo->prepare("SELECT id FROM leads WHERE email = ? OR phone_normalized = ? LIMIT 1");
                    $stmtReFind->execute([$email, $phone_normalized]);
                    $lead_id = $stmtReFind->fetchColumn();
                } else {
                    throw $eRace;
                }
            }
        }
        
        // 4. Salva consentimentos LGPD separados em lead_consents
        
        // Consentimento A (Obrigatório): Ciência da Política de Privacidade e Tratamento para Entrega do Material
        $stmtConsA = $pdo->prepare("INSERT INTO lead_consents (lead_id, consent_type, accepted, consent_text_version, privacy_policy_version, source) VALUES (?, 'material_delivery_privacy_acknowledgement', 1, ?, ?, ?)");
        $stmtConsA->execute([
            $lead_id,
            $lead_data['consent_text_version'] ?? 'v1.0',
            $lead_data['privacy_policy_version'] ?? 'v1.0',
            $lead_data['source'] ?? 'aulas-gratuitas'
        ]);
        
        // Consentimento B (Opcional): Comunicações de Marketing e Ofertas
        $marketing_accepted = !empty($lead_data['consent_marketing']) ? 1 : 0;
        $stmtConsB = $pdo->prepare("INSERT INTO lead_consents (lead_id, consent_type, accepted, consent_text_version, privacy_policy_version, source) VALUES (?, 'marketing_communications', ?, ?, ?, ?)");
        $stmtConsB->execute([
            $lead_id,
            $marketing_accepted,
            $lead_data['consent_text_version'] ?? 'v1.0',
            $lead_data['privacy_policy_version'] ?? 'v1.0',
            $lead_data['source'] ?? 'aulas-gratuitas'
        ]);
        
        // 5. Salva e mescla Tags de comportamento do sistema
        $tag_list = $lead_data['tags'] ?? [];
        if (!is_array($tag_list)) $tag_list = [$tag_list];
        
        foreach ($tag_list as $tName) {
            $tName = trim($tName);
            if (empty($tName)) continue;
            $tSlug = strtolower(preg_replace('/[^a-z0-9:-]+/', '-', $tName));
            
            $stmtT = $pdo->prepare("SELECT id FROM tags WHERE slug = ? LIMIT 1");
            $stmtT->execute([$tSlug]);
            $tagId = $stmtT->fetchColumn();
            if (!$tagId) {
                $stmtTagIns = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                $stmtTagIns->execute([$tName, $tSlug]);
                $tagId = $pdo->lastInsertId();
            }
            
            $stmtLt = $pdo->prepare("INSERT IGNORE INTO lead_tags (lead_id, tag_id) VALUES (?, ?)");
            $stmtLt->execute([$lead_id, $tagId]);
        }
        
        // Atualiza cache de tags do lead
        $stmtCache = $pdo->prepare("SELECT GROUP_CONCAT(t.name SEPARATOR ', ') FROM tags t JOIN lead_tags lt ON lt.tag_id = t.id WHERE lt.lead_id = ?");
        $stmtCache->execute([$lead_id]);
        $tagsCache = $stmtCache->fetchColumn();
        $pdo->prepare("UPDATE leads SET tags_cache = ? WHERE id = ?")->execute([$tagsCache, $lead_id]);
        
        // 6. Atualiza eventos anônimos recentes da sessão (janela de 24h) para associar ao lead_id
        $stmtEvtUp = $pdo->prepare("UPDATE free_video_events SET lead_id = ? WHERE session_token = ? AND lead_id IS NULL AND created_at >= NOW() - INTERVAL 24 HOUR");
        $stmtEvtUp->execute([$lead_id, $session_token]);
        
        // COMMIT DA TRANSAÇÃO PRINCIPAL (Lead, Consentimentos, Tags e Eventos salvos com garantia)
        $pdo->commit();
        
        // 7. Enfileira sincronização com EvoCRM em bloco isolado (Falha da fila NÃO afeta a entrega do material)
        try {
            $evoPayload = [
                'lead_id' => $lead_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone_normalized,
                'tags' => explode(', ', $tagsCache),
                'campaign_code' => $lead_data['campaign_code'] ?? '',
                'marketing_consent' => ($marketing_accepted === 1)
            ];
            $stmtQ = $pdo->prepare("INSERT INTO integration_queue (integration, entity_type, entity_id, action, payload, status) VALUES ('evocrm', 'lead', ?, 'upsert_contact', ?, 'pending')");
            $stmtQ->execute([$lead_id, json_encode($evoPayload)]);
        } catch (Exception $eQueue) {
            // Log do aviso sem interromper a execução nem cancelar o token/download
            error_log("Aviso: Falha ao enfileirar lead #{$lead_id} na integration_queue: " . $eQueue->getMessage());
        }
        
        return [
            'status' => 'success',
            'lead_id' => $lead_id
        ];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['status' => 'error', 'message' => 'Falha ao processar cadastro: ' . $e->getMessage()];
    }
}
