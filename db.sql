-- Criação do Banco de Dados
CREATE DATABASE IF NOT EXISTS isp_preparatorios;
USE isp_preparatorios;

-- Tabela de Administradores
CREATE TABLE IF NOT EXISTS admin_usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserindo um usuário admin padrão (Senha: admin123)
-- Hash gerado com password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO admin_usuarios (name, email, password) VALUES 
('Administrador', 'admin@isppreparatorios.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Tabela de Configurações do Site
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    theme_color_primary VARCHAR(20) DEFAULT '#03045e',
    theme_color_secondary VARCHAR(20) DEFAULT '#ff8000',
    footer_text TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    facebook VARCHAR(255),
    instagram VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Configurações iniciais
INSERT INTO configuracoes (theme_color_primary, theme_color_secondary, footer_text, phone, email, facebook, instagram) VALUES 
('#03045e', '#ff8000', 'ISP Preparatórios. Preparando você para o futuro.', '(00) 00000-0000', 'contato@isppreparatorios.com.br', 'https://facebook.com/isp', 'https://instagram.com/isp');

-- Tabela de Banners (Hero)
CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    subtitle VARCHAR(255),
    image VARCHAR(255) NOT NULL,
    link VARCHAR(255),
    order_index INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Categorias de Cursos
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

INSERT INTO categorias (name) VALUES ('Carreiras Policiais'), ('Tribunais'), ('Administrativas'), ('Educação');

-- Tabela de Cursos
CREATE TABLE IF NOT EXISTS cursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    thumbnail VARCHAR(255),
    duration VARCHAR(255),
    price DECIMAL(10,2),
    payment_link VARCHAR(255) DEFAULT '',
    info_extra TEXT,
    disciplinas TEXT,
    conteudo TEXT,
    modality VARCHAR(100) DEFAULT 'Presencial e Online',
    status VARCHAR(50) DEFAULT 'Disponível',
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- Tabela de Posts do Blog
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) DEFAULT 'Geral',
    content LONGTEXT NOT NULL,
    cover_image VARCHAR(255),
    status VARCHAR(20) DEFAULT 'publicado',
    seo_keywords VARCHAR(255) DEFAULT '',
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Inscrições / Leads
CREATE TABLE IF NOT EXISTS inscricoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    course_id INT,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES cursos(id) ON DELETE SET NULL
);

-- Tabela de Depoimentos
CREATE TABLE IF NOT EXISTS depoimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(100),
    content TEXT NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Apostilas
CREATE TABLE IF NOT EXISTS apostilas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) DEFAULT '',
    price DECIMAL(10,2) DEFAULT 0,
    cover_image VARCHAR(255) NOT NULL,
    payment_link VARCHAR(255) NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    topics TEXT,
    preview_images TEXT,
    hero_btn_text VARCHAR(100) DEFAULT 'Garantir Meu Material',
    sec1_title VARCHAR(255) DEFAULT 'O que você vai encontrar no material?',
    sec1_btn_text VARCHAR(100) DEFAULT 'Quero Ter Acesso Agora',
    sec2_title VARCHAR(255) DEFAULT 'Veja o Material por Dentro',
    sec3_title VARCHAR(255) DEFAULT 'Acelerando sua Aprovação',
    sec3_text TEXT,
    sec3_bullets TEXT,
    sec3_btn_text VARCHAR(100) DEFAULT 'Comprar Agora',
    extra_sections TEXT,
    video_title VARCHAR(255) DEFAULT '',
    video_url VARCHAR(255) DEFAULT '',
    image_alt VARCHAR(255) DEFAULT '',
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de Campanhas de Reserva / Lista de Interesse
CREATE TABLE IF NOT EXISTS reservation_campaigns (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Reservas (Vínculo Lead <-> Campanha)
CREATE TABLE IF NOT EXISTS reservations (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Histórico de Ações da Reserva
CREATE TABLE IF NOT EXISTS reservation_history (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Notas e Observações Internas
CREATE TABLE IF NOT EXISTS reservation_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    admin_id INT DEFAULT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_res_notes_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    INDEX idx_res_notes_res (reservation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
