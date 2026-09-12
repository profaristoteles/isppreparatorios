<?php
/**
 * Service Central do Módulo de Campanhas de Reserva / Lista de Interesse
 * ISP Preparatórios
 * 
 * Centraliza regras de validação, criação/deduplicação de leads,
 * registro de reservas, histórico de auditoria, notas e enfileiramento EvoCRM.
 */

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/aulas_gratuitas_utils.php';

class ReservationService {

    /**
     * Rótulos amigáveis para status de campanhas
     */
    public static function getCampaignStatusLabels() {
        return [
            'rascunho'            => 'Rascunho',
            'reservas_abertas'    => 'Reservas Abertas',
            'reservas_encerradas' => 'Reservas Encerradas',
            'matriculas_abertas'  => 'Matrículas Abertas',
            'turma_confirmada'    => 'Turma Confirmada',
            'cancelada'           => 'Cancelada'
        ];
    }

    /**
     * Rótulos amigáveis para status comercial das reservas
     */
    public static function getReservationStatusLabels() {
        return [
            'nova'                => 'Nova Reserva',
            'contato_pendente'    => 'Contato Pendente',
            'contatado'           => 'Contatado',
            'interessado'         => 'Interessado',
            'aguardando_matricula'=> 'Aguardando Matrícula',
            'matriculado'         => 'Matriculado',
            'nao_respondeu'       => 'Não Respondeu',
            'sem_interesse'       => 'Sem Interesse',
            'cancelado'           => 'Cancelado'
        ];
    }

    /**
     * Rótulos para modalidades
     */
    public static function getModalityLabels() {
        return [
            'presencial' => 'Presencial',
            'online'     => 'Online',
            'ambas'      => 'Presencial + Online (Ambas)'
        ];
    }

    /**
     * Gera link do WhatsApp com mensagem comercial pré-configurada
     */
    public static function generateWhatsAppLink($phone, $name, $campaignTitle, $modality) {
        $cleanPhone = normalize_phone_number($phone);
        if (empty($cleanPhone)) return '#';

        $modalityLabels = self::getModalityLabels();
        $modalityName = $modalityLabels[$modality] ?? ucfirst($modality);

        $msg = "Olá, {$name}! Tudo bem?\n\n";
        $msg .= "Aqui é da equipe do ISP Preparatórios.\n\n";
        $msg .= "Você realizou uma reserva de interesse para o {$campaignTitle}, na modalidade {$modalityName}, e estamos entrando em contato para passar algumas informações.";

        return "https://wa.me/" . $cleanPhone . "?text=" . urlencode($msg);
    }

    /**
     * Cria ou reaproveita um lead mestre com prevenção e registro de conflitos
     */
    public static function processReservationLead($pdo, array $leadData) {
        $email = strtolower(trim($leadData['email'] ?? ''));
        $name = trim($leadData['name'] ?? '');
        $phoneOriginal = trim($leadData['phone'] ?? '');
        $phoneNormalized = normalize_phone_number($phoneOriginal);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => 'Por favor, informe um e-mail válido.'];
        }
        if (empty($name)) {
            return ['status' => 'error', 'message' => 'Por favor, informe seu nome completo.'];
        }
        if (empty($phoneNormalized)) {
            return ['status' => 'error', 'message' => 'Por favor, informe um WhatsApp válido com DDD.'];
        }

        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }

            // 1. Procurar lead por E-mail
            $stmtEmail = $pdo->prepare("SELECT * FROM leads WHERE email = ? LIMIT 1");
            $stmtEmail->execute([$email]);
            $leadByEmail = $stmtEmail->fetch();

            // 2. Procurar lead por Telefone Normalizado
            $stmtPhone = $pdo->prepare("SELECT * FROM leads WHERE phone_normalized = ? LIMIT 1");
            $stmtPhone->execute([$phoneNormalized]);
            $leadByPhone = $stmtPhone->fetch();

            // 3. Conflito de Identidade (E-mail -> Lead A, Telefone -> Lead B, A != B)
            if ($leadByEmail && $leadByPhone && $leadByEmail['id'] != $leadByPhone['id']) {
                $stmtConf = $pdo->prepare("INSERT INTO lead_conflicts (existing_lead_id_email, existing_lead_id_phone, incoming_name, incoming_email, incoming_phone_normalized, payload, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                $stmtConf->execute([
                    $leadByEmail['id'],
                    $leadByPhone['id'],
                    $name,
                    $email,
                    $phoneNormalized,
                    json_encode($leadData)
                ]);
                $conflictId = $pdo->lastInsertId();

                $pdo->commit();
                return [
                    'status' => 'conflict',
                    'conflict_id' => $conflictId,
                    'message' => 'Conflito de dados registrado para análise administrativa.'
                ];
            }

            $targetLead = $leadByEmail ?: $leadByPhone;

            if ($targetLead) {
                $leadId = $targetLead['id'];

                // Atualizar lead existente mantendo consistência e enriquecendo com novos dados
                $stmtUp = $pdo->prepare("UPDATE leads SET 
                    last_conversion = NOW(), 
                    name = ?, 
                    phone_original = ?, 
                    phone_normalized = ?, 
                    utm_source = COALESCE(NULLIF(?, ''), utm_source), 
                    utm_medium = COALESCE(NULLIF(?, ''), utm_medium), 
                    utm_campaign = COALESCE(NULLIF(?, ''), utm_campaign), 
                    utm_content = COALESCE(NULLIF(?, ''), utm_content), 
                    utm_term = COALESCE(NULLIF(?, ''), utm_term) 
                    WHERE id = ?");
                $stmtUp->execute([
                    $name,
                    $phoneOriginal,
                    $phoneNormalized,
                    $leadData['utm_source'] ?? '',
                    $leadData['utm_medium'] ?? '',
                    $leadData['utm_campaign'] ?? '',
                    $leadData['utm_content'] ?? '',
                    $leadData['utm_term'] ?? '',
                    $leadId
                ]);
            } else {
                // Inserir novo Lead no sistema
                try {
                    $stmtIns = $pdo->prepare("INSERT INTO leads (name, email, phone_original, phone_normalized, source, utm_source, utm_medium, utm_campaign, utm_content, utm_term) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtIns->execute([
                        $name,
                        $email,
                        $phoneOriginal,
                        $phoneNormalized,
                        $leadData['source'] ?? 'site-reserva',
                        $leadData['utm_source'] ?? '',
                        $leadData['utm_medium'] ?? '',
                        $leadData['utm_campaign'] ?? '',
                        $leadData['utm_content'] ?? '',
                        $leadData['utm_term'] ?? ''
                    ]);
                    $leadId = $pdo->lastInsertId();
                } catch (PDOException $eRace) {
                    if ($eRace->getCode() == '23000') {
                        $stmtReFind = $pdo->prepare("SELECT id FROM leads WHERE email = ? OR phone_normalized = ? LIMIT 1");
                        $stmtReFind->execute([$email, $phoneNormalized]);
                        $leadId = $stmtReFind->fetchColumn();
                    } else {
                        throw $eRace;
                    }
                }
            }

            // 4. Registrar Consentimento LGPD
            $stmtCons = $pdo->prepare("INSERT INTO lead_consents (lead_id, consent_type, accepted, consent_text_version, privacy_policy_version, source) VALUES (?, 'reservation_interest_terms', 1, 'v1.0', 'v1.0', ?)");
            $stmtCons->execute([$leadId, $leadData['source'] ?? 'site-reserva']);

            // 5. Associar Tags
            $tagsToAdd = $leadData['tags'] ?? [];
            if (!is_array($tagsToAdd)) $tagsToAdd = [$tagsToAdd];

            foreach ($tagsToAdd as $tName) {
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
                $stmtLt->execute([$leadId, $tagId]);
            }

            // Atualiza tags_cache
            $stmtCache = $pdo->prepare("SELECT GROUP_CONCAT(t.name SEPARATOR ', ') FROM tags t JOIN lead_tags lt ON lt.tag_id = t.id WHERE lt.lead_id = ?");
            $stmtCache->execute([$leadId]);
            $tagsCache = $stmtCache->fetchColumn();
            $pdo->prepare("UPDATE leads SET tags_cache = ? WHERE id = ?")->execute([$tagsCache, $leadId]);

            $pdo->commit();

            return [
                'status' => 'success',
                'lead_id' => (int)$leadId,
                'phone_normalized' => $phoneNormalized
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['status' => 'error', 'message' => 'Erro ao processar lead: ' . $e->getMessage()];
        }
    }

    /**
     * Cria uma nova reserva de interesse para uma campanha
     */
    public static function createReservation($pdo, array $input) {
        $campaignId = (int)($input['campaign_id'] ?? 0);
        $campaignSlug = trim($input['campaign_slug'] ?? '');

        // 1. Buscar a campanha
        if ($campaignId > 0) {
            $stmtCamp = $pdo->prepare("SELECT * FROM reservation_campaigns WHERE id = ? AND active = 1 LIMIT 1");
            $stmtCamp->execute([$campaignId]);
        } else {
            $stmtCamp = $pdo->prepare("SELECT * FROM reservation_campaigns WHERE slug = ? AND active = 1 LIMIT 1");
            $stmtCamp->execute([$campaignSlug]);
        }
        $campaign = $stmtCamp->fetch();

        if (!$campaign) {
            return ['success' => false, 'message' => 'Campanha de reserva não encontrada ou inativa.'];
        }

        $campaignId = (int)$campaign['id'];

        // 2. Validar status da campanha
        if ($campaign['status'] === 'cancelada') {
            return ['success' => false, 'message' => 'Esta campanha foi cancelada.'];
        }
        if ($campaign['status'] === 'rascunho') {
            return ['success' => false, 'message' => 'Esta campanha ainda não está disponível para reservas.'];
        }

        // 3. Regra de Limite de Vagas e Lista de Espera (Apenas vagas principais ocupadas, sem contar lista de espera)
        $isWaitingList = 0;
        if ($campaign['max_reservations'] > 0) {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE campaign_id = ? AND is_waiting_list = 0 AND status != 'cancelado'");
            $stmtCount->execute([$campaignId]);
            $currentReservations = (int)$stmtCount->fetchColumn();

            if ($currentReservations >= (int)$campaign['max_reservations']) {
                if ($campaign['allow_waiting_list']) {
                    $isWaitingList = 1;
                } else {
                    return [
                        'success' => false,
                        'code' => 'limit_reached',
                        'message' => 'As reservas para esta turma já foram encerradas (limite de vagas atingido).'
                    ];
                }
            }
        }

        if ($campaign['status'] === 'reservas_encerradas') {
            if ($campaign['allow_waiting_list']) {
                $isWaitingList = 1;
            } else {
                return [
                    'success' => false,
                    'code' => 'closed',
                    'message' => 'As reservas para esta turma estão encerradas no momento.'
                ];
            }
        }

        // 4. Resolução da Modalidade (Com proteção contra manipulação manual de POST)
        $allowsPresencial = (bool)$campaign['allows_presencial'];
        $allowsOnline = (bool)$campaign['allows_online'];

        $preferredModality = strtolower(trim($input['preferred_modality'] ?? ''));

        if ($allowsPresencial && $allowsOnline) {
            if (!in_array($preferredModality, ['presencial', 'online', 'ambas'])) {
                return ['success' => false, 'message' => 'Por favor, selecione como pretende participar (Presencial, Online ou Ambas).'];
            }
        } elseif ($allowsPresencial && !$allowsOnline) {
            if (!empty($preferredModality) && $preferredModality !== 'presencial') {
                return ['success' => false, 'message' => 'Esta turma está disponível exclusivamente na modalidade Presencial.'];
            }
            $preferredModality = 'presencial';
        } elseif ($allowsOnline && !$allowsPresencial) {
            if (!empty($preferredModality) && $preferredModality !== 'online') {
                return ['success' => false, 'message' => 'Esta turma está disponível exclusivamente na modalidade Online.'];
            }
            $preferredModality = 'online';
        } else {
            $preferredModality = 'presencial'; // Fallback seguro
        }

        // 5. Validação de LGPD
        if (empty($input['consent_privacy'])) {
            return ['success' => false, 'message' => 'Você precisa autorizar o contato e aceitar a Política de Privacidade para reservar sua vaga.'];
        }

        // 6. Preparar Lead e Tags
        $autoTags = [
            'isp-preparatorios',
            'reserva',
            'campanha:' . $campaign['slug'],
            'modalidade:' . $preferredModality
        ];
        if ($isWaitingList) {
            $autoTags[] = 'lista-de-espera';
        }

        $leadPayload = [
            'name' => trim($input['name'] ?? ''),
            'email' => strtolower(trim($input['email'] ?? '')),
            'phone' => trim($input['phone'] ?? ''),
            'source' => 'reserva:' . $campaign['slug'],
            'utm_source' => trim($input['utm_source'] ?? ''),
            'utm_medium' => trim($input['utm_medium'] ?? ''),
            'utm_campaign' => trim($input['utm_campaign'] ?? ''),
            'utm_content' => trim($input['utm_content'] ?? ''),
            'utm_term' => trim($input['utm_term'] ?? ''),
            'tags' => $autoTags
        ];

        $leadRes = self::processReservationLead($pdo, $leadPayload);

        if ($leadRes['status'] === 'error') {
            return ['success' => false, 'message' => $leadRes['message']];
        }

        if ($leadRes['status'] === 'conflict') {
            return [
                'success' => false,
                'status' => 'conflict',
                'conflict_id' => $leadRes['conflict_id'],
                'message' => 'Seus dados foram recebidos com sucesso e encaminhados para validação da equipe do ISP Preparatórios.'
            ];
        }

        $leadId = (int)$leadRes['lead_id'];
        $phoneNormalized = $leadRes['phone_normalized'];

        // 7. Verificar se o Lead já possui reserva nesta mesma campanha
        $stmtExist = $pdo->prepare("SELECT id, status, preferred_modality, is_waiting_list, created_at FROM reservations WHERE campaign_id = ? AND lead_id = ? LIMIT 1");
        $stmtExist->execute([$campaignId, $leadId]);
        $existingRes = $stmtExist->fetch();

        if ($existingRes) {
            // Se a reserva já existe e NÃO está cancelada
            if ($existingRes['status'] !== 'cancelado') {
                $statusLabels = self::getReservationStatusLabels();
                $currentStatusName = $statusLabels[$existingRes['status']] ?? $existingRes['status'];

                return [
                    'success' => true,
                    'already_registered' => true,
                    'reservation_id' => (int)$existingRes['id'],
                    'message' => 'Você já possui uma reserva registrada para esta turma.',
                    'preferred_modality' => $existingRes['preferred_modality'],
                    'is_waiting_list' => (bool)$existingRes['is_waiting_list'],
                    'created_at' => $existingRes['created_at'],
                    'campaign_title' => $campaign['title']
                ];
            }

            // Se a reserva existente estiver CANCELADA: Reativar sem duplicar linha no banco
            $reservationId = (int)$existingRes['id'];
            $city = trim($input['city'] ?? '');
            $state = strtoupper(trim($input['state'] ?? ''));
            $customAnswers = !empty($input['custom_answers']) ? json_encode($input['custom_answers'], JSON_UNESCAPED_UNICODE) : null;

            try {
                $pdo->beginTransaction();

                $stmtReactivate = $pdo->prepare("UPDATE reservations SET 
                    status = 'nova',
                    preferred_modality = ?,
                    is_waiting_list = ?,
                    city = ?,
                    state = ?,
                    custom_answers_json = ?,
                    source = 'site-reserva',
                    utm_source = COALESCE(NULLIF(?, ''), utm_source),
                    utm_medium = COALESCE(NULLIF(?, ''), utm_medium),
                    utm_campaign = COALESCE(NULLIF(?, ''), utm_campaign),
                    utm_content = COALESCE(NULLIF(?, ''), utm_content),
                    utm_term = COALESCE(NULLIF(?, ''), utm_term),
                    consent_accepted = 1,
                    consent_date = NOW(),
                    updated_at = NOW()
                    WHERE id = ?");

                $stmtReactivate->execute([
                    $preferredModality,
                    $isWaitingList,
                    $city,
                    $state,
                    $customAnswers,
                    trim($input['utm_source'] ?? ''),
                    trim($input['utm_medium'] ?? ''),
                    trim($input['utm_campaign'] ?? ''),
                    trim($input['utm_content'] ?? ''),
                    trim($input['utm_term'] ?? ''),
                    $reservationId
                ]);

                // Registrar reativação no histórico de auditoria
                $historyDesc = "Reserva reativada pelo interessado.";
                $stmtHist = $pdo->prepare("INSERT INTO reservation_history (reservation_id, admin_id, action_type, old_status, new_status, description) VALUES (?, NULL, 'reativacao', 'cancelado', 'nova', ?)");
                $stmtHist->execute([$reservationId, $historyDesc]);

                $pdo->commit();

                // Reenfileirar no EvoCRM
                try {
                    $evoPayload = [
                        'lead_id' => $leadId,
                        'reservation_id' => $reservationId,
                        'name' => $leadPayload['name'],
                        'email' => $leadPayload['email'],
                        'phone' => $phoneNormalized,
                        'tags' => $autoTags,
                        'campaign_code' => $campaign['slug'],
                        'source' => 'Reserva - ISP Preparatórios',
                        'custom_fields' => [
                            'source'             => 'Reserva - ISP Preparatórios',
                            'type'               => 'Reserva',
                            'campaign_title'     => $campaign['title'],
                            'campaign_slug'      => $campaign['slug'],
                            'preferred_modality' => $preferredModality,
                            'is_waiting_list'    => $isWaitingList ? 'Sim' : 'Não',
                            'city'               => $city,
                            'state'              => $state,
                            'reservation_id'     => $reservationId
                        ]
                    ];

                    $stmtQ = $pdo->prepare("INSERT INTO integration_queue (integration, entity_type, entity_id, action, payload, status) VALUES ('evocrm', 'reservation', ?, 'upsert_contact', ?, 'pending')");
                    $stmtQ->execute([$reservationId, json_encode($evoPayload, JSON_UNESCAPED_UNICODE)]);
                } catch (Exception $eQueue) {
                    error_log("Aviso: Falha ao reenfileirar reserva reativada #{$reservationId} na integration_queue: " . $eQueue->getMessage());
                }

                $siteConfig = get_config($pdo);
                $ispPhone = !empty($siteConfig['phone']) ? $siteConfig['phone'] : '99999999999';
                $ispWhatsAppUrl = self::generateWhatsAppLink($ispPhone, $leadPayload['name'], $campaign['title'], $preferredModality);

                return [
                    'success' => true,
                    'already_registered' => false,
                    'reactivated' => true,
                    'reservation_id' => $reservationId,
                    'protocol' => 'ISP-' . str_pad($reservationId, 6, '0', STR_PAD_LEFT),
                    'lead_id' => $leadId,
                    'campaign_title' => $campaign['title'],
                    'preferred_modality' => $preferredModality,
                    'is_waiting_list' => (bool)$isWaitingList,
                    'whatsapp_contact_url' => $ispWhatsAppUrl,
                    'message' => $isWaitingList 
                        ? 'Recebemos seu interesse! Sua reserva foi reativada e registrada na lista de espera.' 
                        : 'Reserva realizada com sucesso!'
                ];
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                return ['success' => false, 'message' => 'Erro ao reativar reserva: ' . $e->getMessage()];
            }
        }

        // 8. Inserir a nova Reserva
        $city = trim($input['city'] ?? '');
        $state = strtoupper(trim($input['state'] ?? ''));
        $customAnswers = !empty($input['custom_answers']) ? json_encode($input['custom_answers'], JSON_UNESCAPED_UNICODE) : null;

        try {
            $pdo->beginTransaction();

            $stmtIns = $pdo->prepare("INSERT INTO reservations (
                campaign_id, lead_id, preferred_modality, status, is_waiting_list, city, state, 
                custom_answers_json, source, utm_source, utm_medium, utm_campaign, utm_content, utm_term, consent_accepted
            ) VALUES (?, ?, ?, 'nova', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

            $stmtIns->execute([
                $campaignId,
                $leadId,
                $preferredModality,
                $isWaitingList,
                $city,
                $state,
                $customAnswers,
                'site-reserva',
                trim($input['utm_source'] ?? ''),
                trim($input['utm_medium'] ?? ''),
                trim($input['utm_campaign'] ?? ''),
                trim($input['utm_content'] ?? ''),
                trim($input['utm_term'] ?? '')
            ]);

            $reservationId = (int)$pdo->lastInsertId();

            // 9. Registrar no Histórico de Auditoria
            $historyDesc = $isWaitingList 
                ? "Reserva registrada na Lista de Espera via landing page pública (Modalidade: {$preferredModality})."
                : "Reserva de vaga registrada com sucesso via landing page pública (Modalidade: {$preferredModality}).";

            $stmtHist = $pdo->prepare("INSERT INTO reservation_history (reservation_id, admin_id, action_type, old_status, new_status, description) VALUES (?, NULL, 'criacao', NULL, 'nova', ?)");
            $stmtHist->execute([$reservationId, $historyDesc]);

            $pdo->commit();

            // 10. Enfileirar assincronamente na integration_queue para o EvoCRM
            try {
                $evoPayload = [
                    'lead_id' => $leadId,
                    'reservation_id' => $reservationId,
                    'name' => $leadPayload['name'],
                    'email' => $leadPayload['email'],
                    'phone' => $phoneNormalized,
                    'tags' => $autoTags,
                    'campaign_code' => $campaign['slug'],
                    'source' => 'Reserva - ISP Preparatórios',
                    'custom_fields' => [
                        'source'             => 'Reserva - ISP Preparatórios',
                        'type'               => 'Reserva',
                        'campaign_title'     => $campaign['title'],
                        'campaign_slug'      => $campaign['slug'],
                        'preferred_modality' => $preferredModality,
                        'is_waiting_list'    => $isWaitingList ? 'Sim' : 'Não',
                        'city'               => $city,
                        'state'              => $state,
                        'reservation_id'     => $reservationId
                    ]
                ];

                $stmtQ = $pdo->prepare("INSERT INTO integration_queue (integration, entity_type, entity_id, action, payload, status) VALUES ('evocrm', 'reservation', ?, 'upsert_contact', ?, 'pending')");
                $stmtQ->execute([$reservationId, json_encode($evoPayload, JSON_UNESCAPED_UNICODE)]);
            } catch (Exception $eQueue) {
                error_log("Aviso: Falha ao enfileirar reserva #{$reservationId} na integration_queue: " . $eQueue->getMessage());
            }

            // Gerar Link do WhatsApp para contato imediato opcional com a coordenação
            $siteConfig = get_config($pdo);
            $ispPhone = !empty($siteConfig['phone']) ? $siteConfig['phone'] : '99999999999';
            $ispWhatsAppUrl = self::generateWhatsAppLink($ispPhone, $leadPayload['name'], $campaign['title'], $preferredModality);

            return [
                'success' => true,
                'already_registered' => false,
                'reservation_id' => $reservationId,
                'protocol' => 'ISP-' . str_pad($reservationId, 6, '0', STR_PAD_LEFT),
                'lead_id' => $leadId,
                'campaign_title' => $campaign['title'],
                'preferred_modality' => $preferredModality,
                'is_waiting_list' => (bool)$isWaitingList,
                'whatsapp_contact_url' => $ispWhatsAppUrl,
                'message' => $isWaitingList 
                    ? 'Recebemos seu interesse! Sua vaga foi registrada na nossa lista de espera.' 
                    : 'Reserva realizada com sucesso!'
            ];
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Captura eventual SQLSTATE 23000 / erro 1062 resultante de concorrência ou duplicate POST
            if ($e->getCode() == '23000' || (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) || strpos($e->getMessage(), '23000') !== false || strpos($e->getMessage(), '1062') !== false) {
                $stmtCheckAgain = $pdo->prepare("SELECT id, status, preferred_modality, is_waiting_list, created_at FROM reservations WHERE campaign_id = ? AND lead_id = ? LIMIT 1");
                $stmtCheckAgain->execute([$campaignId, $leadId]);
                $concurrentRes = $stmtCheckAgain->fetch();

                if ($concurrentRes) {
                    return [
                        'success' => true,
                        'already_registered' => true,
                        'reservation_id' => (int)$concurrentRes['id'],
                        'message' => 'Você já possui uma reserva registrada para esta turma.',
                        'preferred_modality' => $concurrentRes['preferred_modality'],
                        'is_waiting_list' => (bool)$concurrentRes['is_waiting_list'],
                        'created_at' => $concurrentRes['created_at'],
                        'campaign_title' => $campaign['title']
                    ];
                }
            }
            return ['success' => false, 'message' => 'Erro ao registrar reserva: ' . $e->getMessage()];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Erro ao registrar reserva: ' . $e->getMessage()];
        }
    }

    /**
     * Atualiza o status comercial de uma reserva e registra histórico
     */
    public static function updateReservationStatus($pdo, $reservationId, $newStatus, $adminId = null, $adminName = 'Administrador') {
        $labels = self::getReservationStatusLabels();
        if (!isset($labels[$newStatus])) {
            return ['success' => false, 'message' => 'Status inválido.'];
        }

        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$reservationId]);
        $reservation = $stmt->fetch();

        if (!$reservation) {
            return ['success' => false, 'message' => 'Reserva não encontrada.'];
        }

        $oldStatus = $reservation['status'];
        if ($oldStatus === $newStatus) {
            return ['success' => true, 'message' => 'Status já atualizado.'];
        }

        $stmtUp = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $stmtUp->execute([$newStatus, $reservationId]);

        $oldLabel = $labels[$oldStatus] ?? $oldStatus;
        $newLabel = $labels[$newStatus] ?? $newStatus;
        $desc = "Status alterado de '{$oldLabel}' para '{$newLabel}' por {$adminName}.";

        $stmtHist = $pdo->prepare("INSERT INTO reservation_history (reservation_id, admin_id, action_type, old_status, new_status, description) VALUES (?, ?, 'mudanca_status', ?, ?, ?)");
        $stmtHist->execute([(int)$reservationId, $adminId, $oldStatus, $newStatus, $desc]);

        return ['success' => true, 'message' => 'Status atualizado com sucesso.'];
    }

    /**
     * Adiciona uma observação interna à reserva
     */
    public static function addInternalNote($pdo, $reservationId, $noteText, $adminId = null, $adminName = 'Administrador') {
        $noteText = trim($noteText);
        if (empty($noteText)) {
            return ['success' => false, 'message' => 'A observação não pode ser vazia.'];
        }

        $stmtNote = $pdo->prepare("INSERT INTO reservation_notes (reservation_id, admin_id, note) VALUES (?, ?, ?)");
        $stmtNote->execute([(int)$reservationId, $adminId, $noteText]);

        $desc = "Nota interna adicionada por {$adminName}.";
        $stmtHist = $pdo->prepare("INSERT INTO reservation_history (reservation_id, admin_id, action_type, old_status, new_status, description) VALUES (?, ?, 'nota_interna', NULL, NULL, ?)");
        $stmtHist->execute([(int)$reservationId, $adminId, $desc]);

        return ['success' => true, 'message' => 'Observação registrada com sucesso.'];
    }

    /**
     * Reenfileira a reserva para envio ao EvoCRM
     */
    public static function reenqueueEvoCRM($pdo, $reservationId) {
        $stmt = $pdo->prepare("SELECT r.*, l.name, l.email, l.phone_normalized, c.title as campaign_title, c.slug as campaign_slug 
                               FROM reservations r 
                               JOIN leads l ON r.lead_id = l.id 
                               JOIN reservation_campaigns c ON r.campaign_id = c.id 
                               WHERE r.id = ? LIMIT 1");
        $stmt->execute([(int)$reservationId]);
        $res = $stmt->fetch();

        if (!$res) {
            return ['success' => false, 'message' => 'Reserva não encontrada.'];
        }

        // Prevenção de jobs duplicados na fila: verificar se já existe job pendente ou em processamento
        $stmtCheck = $pdo->prepare("SELECT id, status FROM integration_queue 
                                    WHERE integration = 'evocrm' 
                                      AND entity_type = 'reservation' 
                                      AND entity_id = ? 
                                      AND action = 'upsert_contact' 
                                      AND status IN ('pending', 'processing') 
                                    LIMIT 1");
        $stmtCheck->execute([(int)$reservationId]);
        $existingJob = $stmtCheck->fetch();

        if ($existingJob) {
            return [
                'success' => true,
                'already_queued' => true,
                'message' => 'Esta reserva já está na fila de sincronização com o EvoCRM (Status: ' . $existingJob['status'] . '). Aguarde o processamento antes de reenviar.'
            ];
        }

        $autoTags = [
            'isp-preparatorios',
            'reserva',
            'campanha:' . $res['campaign_slug'],
            'modalidade:' . $res['preferred_modality']
        ];
        if ($res['is_waiting_list']) {
            $autoTags[] = 'lista-de-espera';
        }

        $evoPayload = [
            'lead_id' => (int)$res['lead_id'],
            'reservation_id' => (int)$res['id'],
            'name' => $res['name'],
            'email' => $res['email'],
            'phone' => $res['phone_normalized'],
            'tags' => $autoTags,
            'campaign_code' => $res['campaign_slug'],
            'source' => 'Reserva - ISP Preparatórios',
            'custom_fields' => [
                'source'             => 'Reserva - ISP Preparatórios',
                'type'               => 'Reserva',
                'campaign_title'     => $res['campaign_title'],
                'campaign_slug'      => $res['campaign_slug'],
                'preferred_modality' => $res['preferred_modality'],
                'is_waiting_list'    => $res['is_waiting_list'] ? 'Sim' : 'Não',
                'city'               => $res['city'],
                'state'              => $res['state'],
                'reservation_id'     => (int)$res['id']
            ]
        ];

        $stmtQ = $pdo->prepare("INSERT INTO integration_queue (integration, entity_type, entity_id, action, payload, status) VALUES ('evocrm', 'reservation', ?, 'upsert_contact', ?, 'pending')");
        $stmtQ->execute([(int)$res['id'], json_encode($evoPayload, JSON_UNESCAPED_UNICODE)]);

        $stmtHist = $pdo->prepare("INSERT INTO reservation_history (reservation_id, admin_id, action_type, old_status, new_status, description) VALUES (?, NULL, 'envio_crm', NULL, NULL, 'Reenfileirado manualmente para sincronização com EvoCRM.')");
        $stmtHist->execute([(int)$res['id']]);

        return ['success' => true, 'message' => 'Reserva reenfileirada com sucesso na fila de integrações.'];
    }

    /**
     * Registra um contato comercial estruturado no histórico e atualiza data de acompanhamento
     */
    public static function registerCommercialContact($pdo, $reservationId, $channel, $notes = '', $nextFollowUpAt = null, $newStatus = null, $adminId = null, $adminName = 'Administrador', $clearFollowUp = false) {
        $allowedChannels = ['WhatsApp', 'E-mail', 'Telefone', 'Outro'];
        if (!in_array($channel, $allowedChannels)) {
            $channel = 'Outro';
        }

        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$reservationId]);
        $reservation = $stmt->fetch();

        if (!$reservation) {
            return ['success' => false, 'message' => 'Reserva não encontrada.'];
        }

        $oldStatus = $reservation['status'];
        $labels = self::getReservationStatusLabels();
        
        if (empty($newStatus) || !isset($labels[$newStatus])) {
            $newStatus = ($oldStatus === 'nova') ? 'contatado' : $oldStatus;
        }

        // Validação estrita de next_follow_up_at: não converter silenciosamente datas inválidas em NULL
        $validatedFollowUp = $reservation['next_follow_up_at']; // Preserva valor anterior por padrão
        if ($clearFollowUp) {
            $validatedFollowUp = null; // Apenas quando explicitamente solicitado remover
        } elseif ($nextFollowUpAt !== null && trim($nextFollowUpAt) !== '') {
            $ts = strtotime($nextFollowUpAt);
            if ($ts === false || $ts <= 0) {
                return [
                    'success' => false,
                    'message' => 'Data e hora de acompanhamento inválida. Por favor, utilize um formato de data válido (o agendamento anterior foi mantido).'
                ];
            }
            $validatedFollowUp = date('Y-m-d H:i:s', $ts);
        }

        try {
            $pdo->beginTransaction();

            $stmtUp = $pdo->prepare("UPDATE reservations SET status = ?, next_follow_up_at = ? WHERE id = ?");
            $stmtUp->execute([$newStatus, $validatedFollowUp, (int)$reservationId]);

            $desc = "Contato comercial realizado via {$channel}.";
            if (!empty($notes)) {
                $desc .= " Obs: " . trim($notes);
            }
            if ($validatedFollowUp !== $reservation['next_follow_up_at']) {
                if ($validatedFollowUp) {
                    $desc .= " Próximo acompanhamento agendado para: " . date('d/m/Y H:i', strtotime($validatedFollowUp)) . ".";
                } else {
                    $desc .= " Acompanhamento cancelado/removido.";
                }
            }
            $desc .= " (por {$adminName})";

            $stmtHist = $pdo->prepare("INSERT INTO reservation_history (reservation_id, admin_id, action_type, old_status, new_status, description) VALUES (?, ?, 'contato_comercial', ?, ?, ?)");
            $stmtHist->execute([(int)$reservationId, $adminId, $oldStatus, $newStatus, $desc]);

            $pdo->commit();
            return ['success' => true, 'message' => 'Contato comercial registrado com sucesso!'];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Erro ao registrar contato comercial: ' . $e->getMessage()];
        }
    }

    /**
     * Atualiza diretamente a data/hora do próximo acompanhamento
     */
    public static function updateFollowUpDate($pdo, $reservationId, $nextFollowUpAt, $adminId = null, $adminName = 'Administrador', $explicitClear = false) {
        $stmt = $pdo->prepare("SELECT next_follow_up_at FROM reservations WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$reservationId]);
        $res = $stmt->fetch();
        if (!$res) {
            return ['success' => false, 'message' => 'Reserva não encontrada.'];
        }

        $validatedFollowUp = null;

        if ($explicitClear) {
            // Remoção explícita de agendamento
            $validatedFollowUp = null;
        } elseif ($nextFollowUpAt === null || trim($nextFollowUpAt) === '') {
            return [
                'success' => false,
                'message' => 'Nenhuma data informada. Para remover o agendamento, utilize a opção de remover acompanhamento.'
            ];
        } else {
            $ts = strtotime($nextFollowUpAt);
            if ($ts === false || $ts <= 0) {
                return [
                    'success' => false,
                    'message' => 'Data e hora de acompanhamento inválida. O valor anterior foi preservado.'
                ];
            }
            $validatedFollowUp = date('Y-m-d H:i:s', $ts);
        }

        $stmtUp = $pdo->prepare("UPDATE reservations SET next_follow_up_at = ? WHERE id = ?");
        $stmtUp->execute([$validatedFollowUp, (int)$reservationId]);

        $desc = $validatedFollowUp 
            ? "Próximo acompanhamento agendado para " . date('d/m/Y H:i', strtotime($validatedFollowUp)) . " por {$adminName}."
            : "Agendamento de acompanhamento removido por {$adminName}.";

        $stmtHist = $pdo->prepare("INSERT INTO reservation_history (reservation_id, admin_id, action_type, old_status, new_status, description) VALUES (?, ?, 'agendamento_contato', NULL, NULL, ?)");
        $stmtHist->execute([(int)$reservationId, $adminId, $desc]);

        return [
            'success' => true,
            'message' => $validatedFollowUp ? 'Próximo acompanhamento atualizado com sucesso!' : 'Acompanhamento removido com sucesso!'
        ];
    }

    /**
     * Retorna a data/hora do último contato comercial registrado no histórico
     */
    public static function getLastCommercialContactDate($pdo, $reservationId) {
        $stmt = $pdo->prepare("SELECT created_at FROM reservation_history WHERE reservation_id = ? AND action_type = 'contato_comercial' ORDER BY id DESC LIMIT 1");
        $stmt->execute([(int)$reservationId]);
        $val = $stmt->fetchColumn();
        return $val ?: null;
    }

    /**
     * Formata a data do último contato comercial conforme especificação:
     * - "Hoje, 10:32"
     * - "11/09/2026"
     * - "Nunca"
     */
    public static function formatContactDate($datetime) {
        if (empty($datetime)) {
            return 'Nunca';
        }
        $ts = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
        if (!$ts || $ts <= 0) {
            return 'Nunca';
        }

        $datePart = date('Y-m-d', $ts);
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        if ($datePart === $today) {
            return 'Hoje, ' . date('H:i', $ts);
        } elseif ($datePart === $yesterday) {
            return 'Ontem, ' . date('H:i', $ts);
        } else {
            return date('d/m/Y', $ts);
        }
    }
}
