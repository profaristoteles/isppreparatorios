<?php
$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'isp_preparatorios';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Em produção, não mostramos o erro detalhado por segurança
    if (getenv('ENVIRONMENT') === 'production') {
        die("Erro de conexão. Por favor, tente novamente mais tarde.");
    }
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Auto-migration: coluna status em posts (rascunho/publicado)
try {
    $cols = $pdo->query("SHOW COLUMNS FROM posts LIKE 'status'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN status VARCHAR(20) DEFAULT 'publicado' AFTER cover_image");
    }
    
    $colsSeo = $pdo->query("SHOW COLUMNS FROM posts LIKE 'seo_keywords'")->fetchAll();
    if (empty($colsSeo)) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN seo_keywords VARCHAR(500) DEFAULT '' AFTER status");
    }
} catch (Exception $e) { /* tabela pode não existir ainda */ }

// Auto-migration: coluna ai_openrouter_model na tabela configuracoes
try {
    $cols = $pdo->query("SHOW COLUMNS FROM configuracoes LIKE 'ai_openrouter_model'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE configuracoes ADD COLUMN ai_openrouter_model VARCHAR(255) DEFAULT 'meta-llama/llama-3.3-70b-instruct'");
    }
} catch (Exception $e) { /* tabela pode não existir ainda */ }


// Auto-migration: colunas SEO de posts (meta_title, meta_description, slug, image_alt)
// Garante que existam E tenham tamanho suficiente (VARCHAR 500)
try {
    $seoPostCols = [
        'meta_title' => "VARCHAR(500) DEFAULT ''",
        'meta_description' => "VARCHAR(500) DEFAULT ''",
        'slug' => "VARCHAR(500) DEFAULT ''",
        'image_alt' => "VARCHAR(500) DEFAULT ''"
    ];
    foreach ($seoPostCols as $col => $tipo) {
        $exists = $pdo->query("SHOW COLUMNS FROM posts LIKE " . $pdo->quote($col))->fetchAll();
        if (empty($exists)) {
            $pdo->exec("ALTER TABLE posts ADD COLUMN $col $tipo");
        } else {
            // Expandir coluna se necessário (de VARCHAR(70)/VARCHAR(160) para VARCHAR(500))
            $pdo->exec("ALTER TABLE posts MODIFY COLUMN $col $tipo");
        }
    }
} catch (Exception $e) { /* tabela pode não existir ainda */ }

// Auto-migration: campos editaveis das landing pages de apostilas
try {
    $apostilaCols = [
        'image_alt' => "ALTER TABLE apostilas ADD COLUMN image_alt VARCHAR(255) DEFAULT '' AFTER video_url",
        'sec3_text' => "ALTER TABLE apostilas ADD COLUMN sec3_text TEXT AFTER sec3_title",
        'sec3_bullets' => "ALTER TABLE apostilas ADD COLUMN sec3_bullets TEXT AFTER sec3_text",
        'extra_sections' => "ALTER TABLE apostilas ADD COLUMN extra_sections TEXT AFTER sec3_btn_text"
    ];

    foreach ($apostilaCols as $column => $sql) {
        $cols = $pdo->query("SHOW COLUMNS FROM apostilas LIKE " . $pdo->quote($column))->fetchAll();
        if (empty($cols)) {
            $pdo->exec($sql);
        }
    }
} catch (Exception $e) { /* tabela pode nao existir ainda */ }

// Auto-migration: campos configuráveis de cursos (categoria, recursos/inclusos, formas de pagamento)
try {
    $cursoCols = [
        'category' => "ALTER TABLE cursos ADD COLUMN category VARCHAR(255) DEFAULT '' AFTER title",
        'features' => "ALTER TABLE cursos ADD COLUMN features TEXT AFTER description",
        'payment_methods' => "ALTER TABLE cursos ADD COLUMN payment_methods TEXT AFTER payment_link",
        'price_presencial' => "ALTER TABLE cursos ADD COLUMN price_presencial DECIMAL(10,2) DEFAULT NULL AFTER price",
        'link_presencial' => "ALTER TABLE cursos ADD COLUMN link_presencial VARCHAR(500) DEFAULT '' AFTER price_presencial",
        'price_online' => "ALTER TABLE cursos ADD COLUMN price_online DECIMAL(10,2) DEFAULT NULL AFTER link_presencial",
        'link_online' => "ALTER TABLE cursos ADD COLUMN link_online VARCHAR(500) DEFAULT '' AFTER price_online"
    ];

    foreach ($cursoCols as $column => $sql) {
        $cols = $pdo->query("SHOW COLUMNS FROM cursos LIKE " . $pdo->quote($column))->fetchAll();
        if (empty($cols)) {
            $pdo->exec($sql);
        }
    }
} catch (Exception $e) { /* tabela pode nao existir ainda */ }

// Auto-migration: campos configuráveis de eventos (preços e links presencial/online)
try {
    $eventoCols = [
        'price' => "ALTER TABLE eventos ADD COLUMN price DECIMAL(10,2) DEFAULT NULL AFTER form_embed",
        'payment_link' => "ALTER TABLE eventos ADD COLUMN payment_link VARCHAR(500) DEFAULT '' AFTER price",
        'price_presencial' => "ALTER TABLE eventos ADD COLUMN price_presencial DECIMAL(10,2) DEFAULT NULL AFTER payment_link",
        'link_presencial' => "ALTER TABLE eventos ADD COLUMN link_presencial VARCHAR(500) DEFAULT '' AFTER price_presencial",
        'price_online' => "ALTER TABLE eventos ADD COLUMN price_online DECIMAL(10,2) DEFAULT NULL AFTER link_presencial",
        'link_online' => "ALTER TABLE eventos ADD COLUMN link_online VARCHAR(500) DEFAULT '' AFTER price_online"
    ];

    foreach ($eventoCols as $column => $sql) {
        $cols = $pdo->query("SHOW COLUMNS FROM eventos LIKE " . $pdo->quote($column))->fetchAll();
        if (empty($cols)) {
            $pdo->exec($sql);
        }
    }
} catch (Exception $e) { /* tabela pode nao existir ainda */ }

// Auto-migration: tabela de Lotes com virada automática por data
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS lotes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_type ENUM('curso', 'evento') NOT NULL,
        item_id INT NOT NULL,
        lote_name VARCHAR(100) NOT NULL,
        price_presencial DECIMAL(10,2) DEFAULT NULL,
        link_presencial VARCHAR(500) DEFAULT '',
        price_online DECIMAL(10,2) DEFAULT NULL,
        link_online VARCHAR(500) DEFAULT '',
        price_geral DECIMAL(10,2) DEFAULT NULL,
        link_geral VARCHAR(500) DEFAULT '',
        data_virada DATETIME DEFAULT NULL,
        order_index INT DEFAULT 0,
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_item (item_type, item_id)
    )");
} catch (Exception $e) { /* falha silenciosa */ }

// Funções utilitárias globais
function get_config($pdo) {
    $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
    return $stmt->fetch();
}

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Retorna as informações do lote ativo para um curso ou evento com virada automática por data/hora
 */
function get_active_lote_info($pdo, $item_type, $item_id, $default_data = []) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM lotes WHERE item_type = ? AND item_id = ? AND active = 1 ORDER BY order_index ASC, id ASC");
        $stmt->execute([$item_type, $item_id]);
        $lotes = $stmt->fetchAll();
    } catch (\Exception $e) {
        $lotes = [];
    }

    $now = date('Y-m-d H:i:s');
    $active_lote = null;
    $next_lote_date = null;

    if (!empty($lotes)) {
        foreach ($lotes as $index => $lote) {
            $data_virada = $lote['data_virada'];
            if (empty($data_virada) || $data_virada >= $now) {
                $active_lote = $lote;
                $next_lote_date = !empty($data_virada) ? $data_virada : null;
                break;
            }
        }
        if (!$active_lote && count($lotes) > 0) {
            $active_lote = end($lotes);
        }
    }

    $result = [
        'has_lotes' => !empty($lotes),
        'lotes_list' => $lotes,
        'active_lote' => $active_lote,
        'lote_name' => $active_lote ? $active_lote['lote_name'] : null,
        'data_virada' => $next_lote_date,
        'price_presencial' => $active_lote ? $active_lote['price_presencial'] : ($default_data['price_presencial'] ?? null),
        'link_presencial' => $active_lote ? $active_lote['link_presencial'] : ($default_data['link_presencial'] ?? ''),
        'price_online' => $active_lote ? $active_lote['price_online'] : ($default_data['price_online'] ?? null),
        'link_online' => $active_lote ? $active_lote['link_online'] : ($default_data['link_online'] ?? ''),
        'price_geral' => $active_lote ? $active_lote['price_geral'] : ($default_data['price'] ?? null),
        'link_geral' => $active_lote ? $active_lote['link_geral'] : ($default_data['payment_link'] ?? ($default_data['form_link'] ?? '')),
    ];

    return $result;
}
?>
