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
    title VARCHAR(255) NOT NULL,
    description TEXT,
    thumbnail VARCHAR(255),
    duration VARCHAR(100),
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
    sec3_btn_text VARCHAR(100) DEFAULT 'Comprar Agora',
    video_title VARCHAR(255) DEFAULT '',
    video_url VARCHAR(255) DEFAULT '',
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
