<?php
/**
 * Migration para o Módulo de Reservas de Vagas / Lista de Interesse - ISP Preparatórios
 * 
 * Cria as tabelas reservation_campaigns, reservations, reservation_history e reservation_notes.
 * Garante idempotência e não realiza nenhuma alteração destrutiva em tabelas existentes.
 */
require_once __DIR__ . '/db_config.php';

try {
    // 1. Tabela de Campanhas de Reserva
    $sqlCampaigns = "CREATE TABLE IF NOT EXISTS reservation_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        short_description TEXT,
        description LONGTEXT,
        image VARCHAR(255) DEFAULT '',
        city VARCHAR(100) DEFAULT '',
        state VARCHAR(10) DEFAULT '',
        location VARCHAR(255) DEFAULT '',
        target_audience VARCHAR(255) DEFAULT '',
        start_date DATE DEFAULT NULL,
        end_date DATE DEFAULT NULL,
        class_start_date DATE DEFAULT NULL,
        max_reservations INT DEFAULT 0,
        show_counter TINYINT(1) DEFAULT 1,
        allow_waiting_list TINYINT(1) DEFAULT 1,
        allows_presencial TINYINT(1) DEFAULT 1,
        allows_online TINYINT(1) DEFAULT 0,
        status ENUM('rascunho', 'reservas_abertas', 'reservas_encerradas', 'matriculas_abertas', 'turma_confirmada', 'cancelada') DEFAULT 'reservas_abertas',
        custom_fields_json TEXT DEFAULT NULL,
        enrollment_link VARCHAR(500) DEFAULT '',
        enrollment_button_text VARCHAR(100) DEFAULT 'Fazer Minha Matrícula',
        meta_title VARCHAR(255) DEFAULT '',
        meta_description VARCHAR(500) DEFAULT '',
        active TINYINT(1) DEFAULT 1,
        order_index INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_campaign_status (status),
        INDEX idx_campaign_active (active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlCampaigns);

    // 2. Tabela de Reservas (Vínculo Lead <-> Campanha)
    $sqlReservations = "CREATE TABLE IF NOT EXISTS reservations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        campaign_id INT NOT NULL,
        lead_id INT NOT NULL,
        preferred_modality ENUM('presencial', 'online', 'ambas') NOT NULL,
        status ENUM('nova', 'contato_pendente', 'contatado', 'interessado', 'aguardando_matricula', 'matriculado', 'nao_respondeu', 'sem_interesse', 'cancelado') DEFAULT 'nova',
        is_waiting_list TINYINT(1) DEFAULT 0,
        city VARCHAR(100) DEFAULT '',
        state VARCHAR(10) DEFAULT '',
        custom_answers_json TEXT DEFAULT NULL,
        source VARCHAR(100) DEFAULT 'site-reserva',
        utm_source VARCHAR(100) DEFAULT '',
        utm_medium VARCHAR(100) DEFAULT '',
        utm_campaign VARCHAR(100) DEFAULT '',
        utm_content VARCHAR(100) DEFAULT '',
        utm_term VARCHAR(100) DEFAULT '',
        consent_accepted TINYINT(1) DEFAULT 1,
        consent_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        next_follow_up_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_reservations_campaign FOREIGN KEY (campaign_id) REFERENCES reservation_campaigns(id) ON DELETE RESTRICT,
        CONSTRAINT fk_reservations_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE RESTRICT,
        UNIQUE KEY uk_reservation_campaign_lead (campaign_id, lead_id),
        INDEX idx_reservations_campaign (campaign_id),
        INDEX idx_reservations_lead (lead_id),
        INDEX idx_reservations_status (status),
        INDEX idx_reservations_modality (preferred_modality),
        INDEX idx_reservations_follow_up (next_follow_up_at),
        INDEX idx_reservations_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlReservations);

    // Auto-migration incremental: coluna next_follow_up_at e índice idx_reservations_created se tabela já existir
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM reservations LIKE 'next_follow_up_at'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE reservations ADD COLUMN next_follow_up_at DATETIME DEFAULT NULL AFTER consent_date");
            $pdo->exec("ALTER TABLE reservations ADD INDEX idx_reservations_follow_up (next_follow_up_at)");
        }
        $idxRes = $pdo->query("SHOW INDEX FROM reservations WHERE Key_name = 'idx_reservations_created'")->fetchAll();
        if (empty($idxRes)) {
            $pdo->exec("ALTER TABLE reservations ADD INDEX idx_reservations_created (created_at)");
        }
    } catch (Exception $eCol) {}

    // 3. Tabela de Histórico de Ações da Reserva
    $sqlHistory = "CREATE TABLE IF NOT EXISTS reservation_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reservation_id INT NOT NULL,
        admin_id INT DEFAULT NULL,
        action_type VARCHAR(50) NOT NULL,
        old_status VARCHAR(50) DEFAULT NULL,
        new_status VARCHAR(50) DEFAULT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_res_history_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
        INDEX idx_res_hist_res_action (reservation_id, action_type),
        INDEX idx_res_hist_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlHistory);

    // Auto-migration incremental: índices de histórico se tabela já existir
    try {
        $idxHist1 = $pdo->query("SHOW INDEX FROM reservation_history WHERE Key_name = 'idx_res_hist_res_action'")->fetchAll();
        if (empty($idxHist1)) {
            $pdo->exec("ALTER TABLE reservation_history ADD INDEX idx_res_hist_res_action (reservation_id, action_type)");
        }
        $idxHist2 = $pdo->query("SHOW INDEX FROM reservation_history WHERE Key_name = 'idx_res_hist_created'")->fetchAll();
        if (empty($idxHist2)) {
            $pdo->exec("ALTER TABLE reservation_history ADD INDEX idx_res_hist_created (created_at)");
        }
    } catch (Exception $eHistIdx) {}

    // 4. Tabela de Notas e Observações Internas
    $sqlNotes = "CREATE TABLE IF NOT EXISTS reservation_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reservation_id INT NOT NULL,
        admin_id INT DEFAULT NULL,
        note TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_res_notes_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
        INDEX idx_res_notes_res (reservation_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlNotes);

    if (php_sapi_name() === 'cli' || !empty($_GET['run_cli'])) {
        echo "[OK] Migração de Reservas executada com sucesso!\n";
    }
} catch (Exception $e) {
    if (php_sapi_name() === 'cli' || !empty($_GET['run_cli'])) {
        echo "[ERRO] Falha na migração de Reservas: " . $e->getMessage() . "\n";
    }
}
