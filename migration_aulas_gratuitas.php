<?php
/**
 * Migration para a Área de Aulas Gratuitas - ISP Preparatórios
 * 
 * Cria as 15 tabelas necessárias, índices, chaves estrangeiras e a coluna whatsapp_group_url.
 * Não realiza NENHUMA alteração destrutiva em tabelas legadas do sistema.
 */
require_once __DIR__ . '/db_config.php';

echo "<pre>\n";
echo "==================================================\n";
echo " INICIANDO MIGRAÇÃO: Aulas Gratuitas - ISP\n";
echo "==================================================\n\n";

try {
    $pdo->beginTransaction();

    // 1. Canais
    $sqlCanais = "CREATE TABLE IF NOT EXISTS free_channels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        slug VARCHAR(150) NOT NULL UNIQUE,
        description TEXT,
        cover_image VARCHAR(255) DEFAULT '',
        active TINYINT(1) DEFAULT 1,
        order_index INT DEFAULT 0,
        deleted_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlCanais);
    echo "[OK] Tabela 'free_channels' verificada/criada.\n";

    // 2. Bancas Organizadoras
    $sqlBancas = "CREATE TABLE IF NOT EXISTS free_boards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        slug VARCHAR(150) NOT NULL UNIQUE,
        description TEXT,
        logo VARCHAR(255) DEFAULT '',
        active TINYINT(1) DEFAULT 1,
        deleted_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlBancas);
    echo "[OK] Tabela 'free_boards' verificada/criada.\n";

    // 3. Professores
    $sqlProfessores = "CREATE TABLE IF NOT EXISTS free_teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        slug VARCHAR(150) NOT NULL UNIQUE,
        bio TEXT,
        photo VARCHAR(255) DEFAULT '',
        active TINYINT(1) DEFAULT 1,
        deleted_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlProfessores);
    echo "[OK] Tabela 'free_teachers' verificada/criada.\n";

    // 4. Disciplinas
    $sqlDisciplinas = "CREATE TABLE IF NOT EXISTS free_disciplines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        slug VARCHAR(150) NOT NULL UNIQUE,
        description TEXT,
        active TINYINT(1) DEFAULT 1,
        deleted_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlDisciplinas);
    echo "[OK] Tabela 'free_disciplines' verificada/criada.\n";

    // 5. Concursos
    $sqlConcursos = "CREATE TABLE IF NOT EXISTS free_contests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        slug VARCHAR(150) NOT NULL UNIQUE,
        description TEXT,
        board_id INT DEFAULT NULL,
        exam_date DATE DEFAULT NULL,
        active TINYINT(1) DEFAULT 1,
        deleted_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_contests_board FOREIGN KEY (board_id) REFERENCES free_boards(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlConcursos);
    echo "[OK] Tabela 'free_contests' verificada/criada.\n";

    // 6. Videoaulas
    $sqlVideos = "CREATE TABLE IF NOT EXISTS free_videos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        channel_id INT NOT NULL,
        discipline_id INT DEFAULT NULL,
        teacher_id INT DEFAULT NULL,
        board_id INT DEFAULT NULL,
        contest_id INT DEFAULT NULL,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        short_description TEXT,
        full_description LONGTEXT,
        thumbnail VARCHAR(255) DEFAULT '',
        youtube_url VARCHAR(500) NOT NULL,
        youtube_id VARCHAR(50) NOT NULL,
        category VARCHAR(150) DEFAULT '',
        campaign_code VARCHAR(100) DEFAULT '',
        published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('publicado', 'rascunho') DEFAULT 'rascunho',
        is_featured TINYINT(1) DEFAULT 0,
        order_index INT DEFAULT 0,
        seo_title VARCHAR(255) DEFAULT '',
        seo_description VARCHAR(500) DEFAULT '',
        active TINYINT(1) DEFAULT 1,
        deleted_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_videos_channel FOREIGN KEY (channel_id) REFERENCES free_channels(id) ON DELETE RESTRICT,
        CONSTRAINT fk_videos_discipline FOREIGN KEY (discipline_id) REFERENCES free_disciplines(id) ON DELETE SET NULL,
        CONSTRAINT fk_videos_teacher FOREIGN KEY (teacher_id) REFERENCES free_teachers(id) ON DELETE SET NULL,
        CONSTRAINT fk_videos_board FOREIGN KEY (board_id) REFERENCES free_boards(id) ON DELETE SET NULL,
        CONSTRAINT fk_videos_contest FOREIGN KEY (contest_id) REFERENCES free_contests(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlVideos);
    echo "[OK] Tabela 'free_videos' verificada/criada.\n";

    // 7. Materiais Complementares (PDFs)
    $sqlMateriais = "CREATE TABLE IF NOT EXISTS free_materials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        video_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        file_path VARCHAR(255) NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        mime_type VARCHAR(100) DEFAULT 'application/pdf',
        file_size INT DEFAULT 0,
        active TINYINT(1) DEFAULT 1,
        deleted_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_materials_video FOREIGN KEY (video_id) REFERENCES free_videos(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlMateriais);
    echo "[OK] Tabela 'free_materials' verificada/criada.\n";

    // 8. Tabela Central de Leads
    $sqlLeads = "CREATE TABLE IF NOT EXISTS leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        phone_original VARCHAR(30) DEFAULT NULL,
        phone_normalized VARCHAR(20) DEFAULT NULL,
        last_consent_accepted TINYINT(1) DEFAULT 1,
        last_consent_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        source VARCHAR(100) DEFAULT 'aulas-gratuitas',
        utm_source VARCHAR(100) DEFAULT '',
        utm_medium VARCHAR(100) DEFAULT '',
        utm_campaign VARCHAR(100) DEFAULT '',
        utm_content VARCHAR(100) DEFAULT '',
        utm_term VARCHAR(100) DEFAULT '',
        first_conversion DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_conversion DATETIME DEFAULT CURRENT_TIMESTAMP,
        tags_cache TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT uk_leads_phone_normalized UNIQUE (phone_normalized)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlLeads);
    echo "[OK] Tabela 'leads' verificada/criada.\n";

    // 9. Histórico de Consentimentos LGPD
    $sqlConsents = "CREATE TABLE IF NOT EXISTS lead_consents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_id INT NOT NULL,
        consent_type VARCHAR(50) DEFAULT 'marketing_communications',
        accepted TINYINT(1) DEFAULT 1,
        consent_text_version VARCHAR(50) NOT NULL,
        privacy_policy_version VARCHAR(50) NOT NULL,
        source VARCHAR(100) DEFAULT 'aulas-gratuitas',
        accepted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        revoked_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_consents_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlConsents);
    echo "[OK] Tabela 'lead_consents' verificada/criada.\n";

    // 10. Tags
    $sqlTags = "CREATE TABLE IF NOT EXISTS tags (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlTags);
    echo "[OK] Tabela 'tags' verificada/criada.\n";

    // 11. Relacionamento Lead <-> Tags
    $sqlLeadTags = "CREATE TABLE IF NOT EXISTS lead_tags (
        lead_id INT NOT NULL,
        tag_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (lead_id, tag_id),
        CONSTRAINT fk_lead_tags_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE RESTRICT,
        CONSTRAINT fk_lead_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlLeadTags);
    echo "[OK] Tabela 'lead_tags' verificada/criada.\n";

    // 12. Analytics & Eventos da Jornada
    $sqlEvents = "CREATE TABLE IF NOT EXISTS free_video_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        video_id INT NOT NULL,
        session_token VARCHAR(64) NOT NULL,
        lead_id INT DEFAULT NULL,
        event_type ENUM('video_accessed', 'material_requested', 'lead_captured', 'material_downloaded') NOT NULL,
        metadata TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_events_video FOREIGN KEY (video_id) REFERENCES free_videos(id) ON DELETE RESTRICT,
        CONSTRAINT fk_events_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL,
        INDEX idx_events_session (session_token),
        INDEX idx_events_video (video_id),
        INDEX idx_events_type (event_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlEvents);
    echo "[OK] Tabela 'free_video_events' verificada/criada.\n";

    // 13. Histórico de Downloads
    $sqlDownloads = "CREATE TABLE IF NOT EXISTS lead_downloads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject_type ENUM('lead', 'conflict') DEFAULT 'lead',
        subject_id INT NOT NULL,
        material_id INT NOT NULL,
        video_id INT NOT NULL,
        ip_address VARCHAR(45) DEFAULT '',
        user_agent VARCHAR(255) DEFAULT '',
        downloaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_downloads_material FOREIGN KEY (material_id) REFERENCES free_materials(id) ON DELETE RESTRICT,
        CONSTRAINT fk_downloads_video FOREIGN KEY (video_id) REFERENCES free_videos(id) ON DELETE RESTRICT,
        INDEX idx_downloads_subject (subject_type, subject_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlDownloads);
    echo "[OK] Tabela 'lead_downloads' verificada/criada.\n";

    // 14. Conflitos de Leads
    $sqlConflicts = "CREATE TABLE IF NOT EXISTS lead_conflicts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        existing_lead_id_email INT DEFAULT NULL,
        existing_lead_id_phone INT DEFAULT NULL,
        incoming_name VARCHAR(150) NOT NULL,
        incoming_email VARCHAR(150) NOT NULL,
        incoming_phone_normalized VARCHAR(20) NOT NULL,
        payload TEXT NOT NULL,
        status ENUM('pending', 'resolved', 'ignored') DEFAULT 'pending',
        resolved_lead_id INT DEFAULT NULL,
        resolved_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_conflicts_lead_email FOREIGN KEY (existing_lead_id_email) REFERENCES leads(id) ON DELETE SET NULL,
        CONSTRAINT fk_conflicts_lead_phone FOREIGN KEY (existing_lead_id_phone) REFERENCES leads(id) ON DELETE SET NULL,
        CONSTRAINT fk_conflicts_resolved_lead FOREIGN KEY (resolved_lead_id) REFERENCES leads(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlConflicts);
    echo "[OK] Tabela 'lead_conflicts' verificada/criada.\n";

    // 15. Fila Assíncrona de Integrações
    $sqlQueue = "CREATE TABLE IF NOT EXISTS integration_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        integration VARCHAR(50) NOT NULL,
        entity_type VARCHAR(50) NOT NULL,
        entity_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        payload TEXT NOT NULL,
        status ENUM('pending', 'processing', 'synced', 'error') DEFAULT 'pending',
        attempts INT DEFAULT 0,
        max_attempts INT DEFAULT 5,
        last_error TEXT DEFAULT NULL,
        locked_at DATETIME DEFAULT NULL,
        worker_id VARCHAR(100) DEFAULT NULL,
        next_retry_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_queue_status_retry (status, next_retry_at),
        INDEX idx_queue_locked (locked_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlQueue);
    echo "[OK] Tabela 'integration_queue' verificada/criada.\n";

    // 16. Alterar tabela configuracoes para adicionar whatsapp_group_url se não existir
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM configuracoes LIKE 'whatsapp_group_url'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE configuracoes ADD COLUMN whatsapp_group_url VARCHAR(255) DEFAULT '' AFTER instagram");
            echo "[OK] Coluna 'whatsapp_group_url' adicionada na tabela 'configuracoes'.\n";
        }
    } catch (Exception $e) { /* tabela pode não existir */ }

    // 17. Seed Inicial: Primeiro Canal (ISP Preparatórios)
    $stmtCh = $pdo->prepare("SELECT id FROM free_channels WHERE slug = 'isp-preparatorios' OR slug = 'isp-resolve' OR name LIKE '%ISP%' LIMIT 1");
    $stmtCh->execute();
    $chanId = $stmtCh->fetchColumn();
    if (!$chanId) {
        $stmtIns = $pdo->prepare("INSERT INTO free_channels (name, slug, description, active, order_index) VALUES (?, ?, ?, 1, 1)");
        $stmtIns->execute([
            'ISP Preparatórios',
            'isp-preparatorios',
            'Canal oficial do ISP Preparatórios com aulas gratuitas, resolução de questões, revisões e conteúdos voltados à preparação para concursos públicos e processos seletivos.'
        ]);
        $chanId = $pdo->lastInsertId();
        echo "[SEED] Canal 'ISP Preparatórios' criado (ID: $chanId).\n";
    } else {
        // Atualização cadastral preservando ID
        $pdo->prepare("UPDATE free_channels SET name = 'ISP Preparatórios', slug = 'isp-preparatorios', description = 'Canal oficial do ISP Preparatórios com aulas gratuitas, resolução de questões, revisões e conteúdos voltados à preparação para concursos públicos e processos seletivos.' WHERE id = ?")->execute([$chanId]);
        echo "[UPDATE] Canal ID $chanId atualizado para 'ISP Preparatórios' (slug: isp-preparatorios).\n";
    }

    // 18. Seed Inicial: Banca (Instituto JK), Professora (Walneane), Disciplina (Língua Portuguesa)
    $stmtBoard = $pdo->prepare("SELECT id FROM free_boards WHERE slug = 'instituto-jk'");
    $stmtBoard->execute();
    $boardId = $stmtBoard->fetchColumn();
    if (!$boardId) {
        $pdo->prepare("INSERT INTO free_boards (name, slug, description) VALUES ('Instituto JK', 'instituto-jk', 'Banca examinadora de concursos públicos.')")->execute();
        $boardId = $pdo->lastInsertId();
        echo "[SEED] Banca 'Instituto JK' criada (ID: $boardId).\n";
    }

    $stmtDisc = $pdo->prepare("SELECT id FROM free_disciplines WHERE slug = 'lingua-portuguesa'");
    $stmtDisc->execute();
    $discId = $stmtDisc->fetchColumn();
    if (!$discId) {
        $pdo->prepare("INSERT INTO free_disciplines (name, slug, description) VALUES ('Língua Portuguesa', 'lingua-portuguesa', 'Disciplina de Língua Portuguesa para Concursos.')")->execute();
        $discId = $pdo->lastInsertId();
        echo "[SEED] Disciplina 'Língua Portuguesa' criada (ID: $discId).\n";
    }

    $stmtTeach = $pdo->prepare("SELECT id FROM free_teachers WHERE slug = 'walneane'");
    $stmtTeach->execute();
    $teachId = $stmtTeach->fetchColumn();
    if (!$teachId) {
        $pdo->prepare("INSERT INTO free_teachers (name, slug, bio) VALUES ('Walneane', 'walneane', 'Professora especialista em Língua Portuguesa.')")->execute();
        $teachId = $pdo->lastInsertId();
        echo "[SEED] Professora 'Walneane' criada (ID: $teachId).\n";
    }

    // 19. Seed Inicial: Primeira Videoaula (Publicada com material para demonstração imediata)
    $stmtVid = $pdo->prepare("SELECT id FROM free_videos WHERE slug = 'resolucao-questoes-lingua-portuguesa-instituto-jk'");
    $stmtVid->execute();
    $vidId = $stmtVid->fetchColumn();
    if (!$vidId) {
        $stmtVidIns = $pdo->prepare("INSERT INTO free_videos (channel_id, discipline_id, teacher_id, board_id, title, slug, short_description, full_description, youtube_url, youtube_id, campaign_code, status, is_featured, seo_title, seo_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'publicado', 1, ?, ?)");
        $stmtVidIns->execute([
            $chanId,
            $discId,
            $teachId,
            $boardId,
            'Resolução de Questões de Língua Portuguesa – Instituto JK',
            'resolucao-questoes-lingua-portuguesa-instituto-jk',
            'Aula de resolução de questões da banca Instituto JK com a professora Walneane.',
            'Assista à resolução completa das questões de Língua Portuguesa do Instituto JK e baixe o caderno de questões exclusivo para acompanhar.',
            'https://www.youtube.com/watch?v=_qT76mO0wAc',
            '_qT76mO0wAc',
            'ISP-YT-JK-PORT-001',
            'Resolução de Questões de Língua Portuguesa – Instituto JK | ISP Preparatórios',
            'Estude com a professora Walneane e resolva questões da banca Instituto JK no ISP Preparatórios.'
        ]);
        $vidId = $pdo->lastInsertId();
        echo "[SEED] Videoaula de demonstração criada como 'publicado' (ID: $vidId).\n";

        // Inserir Material em PDF vinculado
        $pdo->prepare("INSERT INTO free_materials (video_id, title, description, file_path, original_filename, mime_type, file_size, active) VALUES (?, 'Caderno de Questões - Instituto JK', 'Material complementar em PDF com as questões de Língua Portuguesa resolvidas na videoaula.', 'caderno_questones_jk_portugues.pdf', 'Caderno_Questoes_Instituto_JK.pdf', 'application/pdf', 1048576, 1)")->execute([$vidId]);
        echo "[SEED] Material PDF de demonstração vinculado (ID: " . $pdo->lastInsertId() . ").\n";
    } else {
        // Atualiza videoaula para publicado se ainda estiver em rascunho
        $pdo->prepare("UPDATE free_videos SET status = 'publicado', youtube_id = IF(youtube_id = '', '_qT76mO0wAc', youtube_id) WHERE id = ?")->execute([$vidId]);
        
        // Garante material PDF cadastrado
        $stmtMatChk = $pdo->prepare("SELECT id FROM free_materials WHERE video_id = ? LIMIT 1");
        $stmtMatChk->execute([$vidId]);
        if (!$stmtMatChk->fetchColumn()) {
            $pdo->prepare("INSERT INTO free_materials (video_id, title, description, file_path, original_filename, mime_type, file_size, active) VALUES (?, 'Caderno de Questões - Instituto JK', 'Material complementar em PDF com as questões de Língua Portuguesa resolvidas na videoaula.', 'caderno_questones_jk_portugues.pdf', 'Caderno_Questoes_Instituto_JK.pdf', 'application/pdf', 1048576, 1)")->execute([$vidId]);
        }
        echo "[UPDATE] Videoaula ID $vidId atualizada para 'publicado' com material complementar.\n";
    }

    $pdo->commit();
    echo "\n==================================================\n";
    echo " MIGRAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "==================================================\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n[ERRO NA MIGRAÇÃO]: " . $e->getMessage() . "\n";
}
echo "</pre>";
