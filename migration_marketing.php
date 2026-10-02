<?php
/**
 * Migration para Infraestrutura Centralizada de Rastreamento de Conversões e Marketing
 * ISP Preparatórios
 *
 * Cria tabelas:
 * - marketing_settings
 * - marketing_campaigns
 * - marketing_events
 * - marketing_conversions
 *
 * Adiciona colunas complementares e índices em reservations e leads.
 * Idempotente e não-destrutiva.
 */

require_once __DIR__ . '/db_config.php';

try {
    // 1. Tabela de Configurações Globais de Marketing / Meta Pixel
    $sqlSettings = "CREATE TABLE IF NOT EXISTS marketing_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        meta_pixel_enabled TINYINT(1) DEFAULT 1,
        meta_pixel_id VARCHAR(50) DEFAULT '854510411013557',
        meta_advanced_matching TINYINT(1) DEFAULT 0,
        meta_auto_events TINYINT(1) DEFAULT 0,
        debug_mode TINYINT(1) DEFAULT 0,
        meta_test_event_code VARCHAR(50) DEFAULT '',
        capi_enabled TINYINT(1) DEFAULT 0,
        capi_access_token TEXT DEFAULT NULL,
        capi_test_code VARCHAR(50) DEFAULT '',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlSettings);

    // Inicializar linha padrão se vazia
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM marketing_settings");
    if ((int)$stmtCount->fetchColumn() === 0) {
        $pdo->exec("INSERT INTO marketing_settings (id, meta_pixel_enabled, meta_pixel_id, meta_advanced_matching, meta_auto_events, debug_mode)
                    VALUES (1, 1, '854510411013557', 0, 0, 0)");
    }

    // 2. Tabela de Campanhas de Marketing
    $sqlCampaigns = "CREATE TABLE IF NOT EXISTS marketing_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        status ENUM('rascunho', 'ativa', 'pausada', 'encerrada') DEFAULT 'ativa',
        description TEXT DEFAULT NULL,
        start_date DATE DEFAULT NULL,
        end_date DATE DEFAULT NULL,
        landing_page VARCHAR(500) NOT NULL,
        reservation_campaign_id INT DEFAULT NULL,
        pixel_id VARCHAR(50) DEFAULT NULL,
        primary_event VARCHAR(50) DEFAULT 'Lead',
        default_utm_source VARCHAR(100) DEFAULT 'meta',
        default_utm_medium VARCHAR(100) DEFAULT 'paid_social',
        default_utm_campaign VARCHAR(100) DEFAULT '',
        default_utm_content VARCHAR(100) DEFAULT '',
        default_utm_term VARCHAR(100) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_mkt_camp_status (status),
        INDEX idx_mkt_camp_slug (slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlCampaigns);

    // 3. Tabela de Configurações de Eventos de Conversão
    $sqlEvents = "CREATE TABLE IF NOT EXISTS marketing_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        event_name VARCHAR(50) NOT NULL,
        trigger_type ENUM('backend_confirm', 'page_load', 'form_submit', 'element_click', 'url_match', 'custom_action') DEFAULT 'backend_confirm',
        trigger_selector VARCHAR(255) DEFAULT '',
        campaign_id INT DEFAULT NULL,
        page_path VARCHAR(255) DEFAULT '',
        parameters_json TEXT DEFAULT NULL,
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_mkt_evt_name (event_name),
        INDEX idx_mkt_evt_trigger (trigger_type),
        INDEX idx_mkt_evt_active (active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlEvents);

    // 4. Tabela de Log de Conversões Auditável
    $sqlConversions = "CREATE TABLE IF NOT EXISTS marketing_conversions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id VARCHAR(100) NOT NULL UNIQUE,
        event_name VARCHAR(50) NOT NULL,
        campaign_id INT DEFAULT NULL,
        campaign_slug VARCHAR(100) DEFAULT '',
        entity_type VARCHAR(50) DEFAULT 'reservation',
        entity_id INT DEFAULT NULL,
        lead_id INT DEFAULT NULL,
        page_url VARCHAR(500) DEFAULT '',
        referrer VARCHAR(500) DEFAULT '',
        utm_source VARCHAR(100) DEFAULT '',
        utm_medium VARCHAR(100) DEFAULT '',
        utm_campaign VARCHAR(100) DEFAULT '',
        utm_content VARCHAR(100) DEFAULT '',
        utm_term VARCHAR(100) DEFAULT '',
        status ENUM('recorded', 'sent_browser', 'sent_capi', 'duplicate_prevented', 'failed') DEFAULT 'recorded',
        payload_json TEXT DEFAULT NULL,
        user_agent VARCHAR(500) DEFAULT '',
        ip_address_hash VARCHAR(64) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_conv_event_id (event_id),
        INDEX idx_conv_event_name (event_name),
        INDEX idx_conv_campaign (campaign_slug),
        INDEX idx_conv_entity (entity_type, entity_id),
        INDEX idx_conv_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlConversions);

    // 5. Adicionar colunas incrementais em reservations
    $colsRes = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'event_id'")->fetchAll();
    if (empty($colsRes)) {
        $pdo->exec("ALTER TABLE reservations ADD COLUMN event_id VARCHAR(100) DEFAULT NULL AFTER utm_term");
        $pdo->exec("ALTER TABLE reservations ADD INDEX idx_reservations_event_id (event_id)");
    }

    $colsLanding = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'landing_page'")->fetchAll();
    if (empty($colsLanding)) {
        $pdo->exec("ALTER TABLE reservations ADD COLUMN landing_page VARCHAR(500) DEFAULT '' AFTER event_id");
        $pdo->exec("ALTER TABLE reservations ADD COLUMN referrer VARCHAR(500) DEFAULT '' AFTER landing_page");
        $pdo->exec("ALTER TABLE reservations ADD COLUMN first_visit_at DATETIME DEFAULT NULL AFTER referrer");
    }

    // 6. Adicionar colunas incrementais em leads
    $colsLeadLanding = $pdo->query("SHOW COLUMNS FROM leads LIKE 'landing_page'")->fetchAll();
    if (empty($colsLeadLanding)) {
        $pdo->exec("ALTER TABLE leads ADD COLUMN landing_page VARCHAR(500) DEFAULT '' AFTER utm_term");
        $pdo->exec("ALTER TABLE leads ADD COLUMN referrer VARCHAR(500) DEFAULT '' AFTER landing_page");
        $pdo->exec("ALTER TABLE leads ADD COLUMN first_visit_at DATETIME DEFAULT NULL AFTER referrer");
    }

    // 7. Seeding da campanha atual na tabela reservation_campaigns caso não exista
    $stmtCaxias = $pdo->prepare("SELECT id FROM reservation_campaigns WHERE slug = ? LIMIT 1");
    $stmtCaxias->execute(['preparatorio-concurso-caxias-ma-legatus']);
    $resCampId = $stmtCaxias->fetchColumn();

    if (!$resCampId) {
        $stmtInsRes = $pdo->prepare("INSERT INTO reservation_campaigns (
            title, slug, short_description, description, city, state, location, target_audience,
            allows_presencial, allows_online, status, meta_title, meta_description, active, order_index
        ) VALUES (
            'Preparatório Intensivo - Concurso Prefeitura de Caxias-MA',
            'preparatorio-concurso-caxias-ma-legatus',
            'Garanta sua vaga na turma preparatória com foco na banca Legatus para o concurso da Prefeitura de Caxias-MA.',
            'Turma direcionada para o Concurso Público da Prefeitura de Caxias-MA com a banca Instituto Legatus. Aulas presenciais e online ao vivo com professores especialistas em aprovação.',
            'Caxias',
            'MA',
            'Unidade Central ISP - Caxias, MA',
            'Candidatos aos cargos de Educação e Conhecimentos Gerais do Concurso de Caxias-MA',
            1, 1, 'reservas_abertas',
            'Preparatório Intensivo - Concurso Prefeitura de Caxias-MA | ISP Preparatórios',
            'Pré-reserva de vagas para o Preparatório Intensivo do Concurso da Prefeitura de Caxias-MA com a banca Legatus.',
            1, 1
        )");
        $stmtInsRes->execute();
        $resCampId = (int)$pdo->lastInsertId();
    }

    // 8. Seeding da campanha de marketing correspondente
    $stmtMktCamp = $pdo->prepare("SELECT id FROM marketing_campaigns WHERE slug = ? LIMIT 1");
    $stmtMktCamp->execute(['preparatorio-caxias-2026']);
    $mktCampId = $stmtMktCamp->fetchColumn();

    if (!$mktCampId) {
        $stmtInsMkt = $pdo->prepare("INSERT INTO marketing_campaigns (
            name, slug, status, description, landing_page, reservation_campaign_id,
            primary_event, default_utm_source, default_utm_medium, default_utm_campaign
        ) VALUES (
            'Preparatório Intensivo — Concurso Prefeitura de Caxias-MA',
            'preparatorio-caxias-2026',
            'ativa',
            'Campanha de aquisição e pré-reserva para a turma presencial e online de Caxias-MA (Banca Legatus).',
            '/reserva/preparatorio-concurso-caxias-ma-legatus',
            ?,
            'Lead',
            'meta',
            'paid_social',
            'preparatorio_caxias_2026'
        )");
        $stmtInsMkt->execute([$resCampId]);
        $mktCampId = (int)$pdo->lastInsertId();
    }

    // 9. Seeding dos eventos padrão em marketing_events
    // 9.1 Lead para a campanha de Caxias
    $stmtEvtLead = $pdo->prepare("SELECT id FROM marketing_events WHERE event_name = 'Lead' AND campaign_id = ? LIMIT 1");
    $stmtEvtLead->execute([$mktCampId]);
    if (!$stmtEvtLead->fetchColumn()) {
        $leadParams = json_encode([
            'content_name' => 'Preparatório Intensivo - Concurso Prefeitura de Caxias-MA',
            'content_category' => 'Preparatório',
            'status' => 'pre_cadastro'
        ], JSON_UNESCAPED_UNICODE);

        $stmtInsEvt = $pdo->prepare("INSERT INTO marketing_events (
            name, event_name, trigger_type, campaign_id, page_path, parameters_json, active
        ) VALUES (
            'Lead Reserva Caxias (Backend)',
            'Lead',
            'backend_confirm',
            ?,
            '/reserva/preparatorio-concurso-caxias-ma-legatus',
            ?,
            1
        )");
        $stmtInsEvt->execute([$mktCampId, $leadParams]);
    }

    // 9.2 PageView Global
    $stmtEvtPv = $pdo->prepare("SELECT id FROM marketing_events WHERE event_name = 'PageView' AND campaign_id IS NULL LIMIT 1");
    $stmtEvtPv->execute();
    if (!$stmtEvtPv->fetchColumn()) {
        $stmtInsPv = $pdo->prepare("INSERT INTO marketing_events (
            name, event_name, trigger_type, campaign_id, page_path, parameters_json, active
        ) VALUES (
            'PageView Global',
            'PageView',
            'page_load',
            NULL,
            '*',
            '{}',
            1
        )");
        $stmtInsPv->execute();
    }

} catch (Exception $e) {
    error_log("Erro na execução da migration_marketing.php: " . $e->getMessage());
    throw $e;
}
