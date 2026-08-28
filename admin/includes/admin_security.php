<?php
/**
 * Utilitários de Segurança e CSRF para o Painel Administrativo
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Gera ou recupera o token CSRF da sessão atual
 */
function generate_csrf_token() {
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf_token'];
}

/**
 * Renderiza um campo oculto HTML com o token CSRF
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Valida o token CSRF enviado via POST
 */
function verify_csrf_token() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postedToken = $_POST['csrf_token'] ?? '';
        $sessionToken = $_SESSION['admin_csrf_token'] ?? '';
        if (empty($postedToken) || empty($sessionToken) || !hash_equals($sessionToken, $postedToken)) {
            $_SESSION['erro'] = "Sessão ou token de segurança inválido. Por favor, tente novamente.";
            $referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
            header("Location: " . $referer);
            exit;
        }
    }
}

/**
 * Extrai o ID de um vídeo do YouTube a partir de múltiplos formatos de URL
 */
function extract_youtube_id($url) {
    if (empty($url)) return '';
    $url = trim($url);
    
    // Se a string já tiver 11 caracteres alfanuméricos/traços sem barras ou pontos, é o ID direto
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
        return $url;
    }
    
    $patterns = [
        '/(?:youtube\.com\/watch\?v=)([a-zA-Z0-9_-]{11})/i',
        '/(?:youtu\.be\/)([a-zA-Z0-9_-]{11})/i',
        '/(?:youtube\.com\/live\/)([a-zA-Z0-9_-]{11})/i',
        '/(?:youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/i',
        '/(?:youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/i',
        '/[?&]v=([a-zA-Z0-9_-]{11})/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    return '';
}
