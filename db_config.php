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
        $pdo->exec("ALTER TABLE posts ADD COLUMN seo_keywords VARCHAR(255) DEFAULT '' AFTER status");
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
        'payment_methods' => "ALTER TABLE cursos ADD COLUMN payment_methods TEXT AFTER payment_link"
    ];

    foreach ($cursoCols as $column => $sql) {
        $cols = $pdo->query("SHOW COLUMNS FROM cursos LIKE " . $pdo->quote($column))->fetchAll();
        if (empty($cols)) {
            $pdo->exec($sql);
        }
    }
} catch (Exception $e) { /* tabela pode nao existir ainda */ }

// Funções utilitárias globais
function get_config($pdo) {
    $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
    return $stmt->fetch();
}

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>
