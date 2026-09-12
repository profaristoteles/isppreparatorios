<?php
/**
 * Template de Landing Page Pública de Campanhas de Reserva / Lista de Interesse
 * ISP Preparatórios — Elite em Concursos Públicos
 * 
 * Design Premium de Alta Conversão, Responsivo e Moderno
 */

if (!isset($campaign)) {
    header("Location: /");
    exit;
}

// Meta SEO dinâmica para o header.php
$dynamic_title = !empty($campaign['meta_title']) ? $campaign['meta_title'] : ($campaign['title'] . " - Reserva de Vagas | ISP Preparatórios");
$dynamic_desc = !empty($campaign['meta_description']) ? $campaign['meta_description'] : (!empty($campaign['short_description']) ? strip_tags($campaign['short_description']) : "Garanta seu interesse e reserve sua vaga para a nova turma do ISP Preparatórios.");

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/reservation_service.php';

// Contagem de reservas para contador público
$stmtCountTotal = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE campaign_id = ? AND status != 'cancelado'");
$stmtCountTotal->execute([(int)$campaign['id']]);
$totalReservasAtuais = (int)$stmtCountTotal->fetchColumn();

// Contagem apenas de vagas principais regulares (sem contar lista de espera)
$stmtCountMain = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE campaign_id = ? AND is_waiting_list = 0 AND status != 'cancelado'");
$stmtCountMain->execute([(int)$campaign['id']]);
$totalVagasRegularesOcupadas = (int)$stmtCountMain->fetchColumn();

// Status e regras
$status = $campaign['status'];
$isLimitReached = ($campaign['max_reservations'] > 0 && $totalVagasRegularesOcupadas >= (int)$campaign['max_reservations']);
$isWaitingList = ($isLimitReached || $status === 'reservas_encerradas') && (bool)$campaign['allow_waiting_list'];
$isClosed = ($isLimitReached || $status === 'reservas_encerradas') && !$campaign['allow_waiting_list'];
$isEnrollmentOpen = ($status === 'matriculas_abertas');

$allowsPresencial = (bool)$campaign['allows_presencial'];
$allowsOnline = (bool)$campaign['allows_online'];

// Perguntas personalizadas (JSON)
$customFields = [];
if (!empty($campaign['custom_fields_json'])) {
    $decoded = json_decode($campaign['custom_fields_json'], true);
    if (is_array($decoded)) {
        $customFields = $decoded;
    }
}

/**
 * Formatador inteligente de descrição de campanha para renderizar cards e listas elegantes
 */
function render_formatted_description($rawText) {
    if (empty($rawText)) return '';
    
    // Se o texto já contiver tags HTML ricas (<p>, <div>, <ul>, etc.)
    if (preg_match('/<\s*(p|div|ul|ol|h2|h3|h4|table)\b/i', $rawText)) {
        return '<div class="reserva-custom-html">' . $rawText . '</div>';
    }

    $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $rawText));
    $output = '';
    $inList = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (empty($trimmed)) {
            if ($inList) {
                $output .= '</ul></div>';
                $inList = false;
            }
            continue;
        }

        // Detecta item com marcador (•, -, *, etc.)
        if (preg_match('/^([•\-\*]|\d+\.)\s*(.+)$/u', $trimmed, $m)) {
            if (!$inList) {
                $output .= '<div class="reserva-bullet-wrapper"><ul class="reserva-bullet-list">';
                $inList = true;
            }
            $output .= '<li><i class="fas fa-check-circle"></i> <span>' . htmlspecialchars($m[2]) . '</span></li>';
            continue;
        }

        if ($inList) {
            $output .= '</ul></div>';
            $inList = false;
        }

        // Detecta títulos/seções chave
        if (preg_match('/^(Disciplinas|Horários|Pacotes|Bônus|Modalidades|Carga horária|Público-alvo|Metodologia)[\w\s]*:/i', $trimmed)) {
            $output .= '<h4 class="reserva-content-section-title"><i class="fas fa-layer-group"></i> ' . htmlspecialchars($trimmed) . '</h4>';
        } else {
            $output .= '<p class="reserva-content-paragraph">' . htmlspecialchars($trimmed) . '</p>';
        }
    }

    if ($inList) {
        $output .= '</ul></div>';
    }

    return $output;
}
?>

<!-- Estilos Dedicados e Otimizados para a Landing Page de Reservas -->
<style>
/* === Cores e Variáveis da LP === */
:root {
    --lp-bg: #030438;
    --lp-card-bg: rgba(7, 12, 38, 0.88);
    --lp-card-border: rgba(255, 255, 255, 0.12);
    --lp-accent: #ff8000;
    --lp-accent-glow: rgba(255, 128, 0, 0.35);
    --lp-blue-glow: rgba(0, 150, 255, 0.25);
    --lp-text-muted: #a0aec0;
}

.reserva-page-wrapper {
    background-color: var(--lp-bg);
    background-image: 
        radial-gradient(circle at 10% 20%, rgba(3, 4, 94, 0.8) 0%, transparent 50%),
        radial-gradient(circle at 90% 10%, rgba(255, 128, 0, 0.15) 0%, transparent 40%),
        radial-gradient(circle at 50% 80%, rgba(0, 119, 255, 0.12) 0%, transparent 60%);
    color: #ffffff;
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    padding-top: 95px; /* Espaço para não encavalar com o menu fixo */
    padding-bottom: 6rem;
    position: relative;
    overflow-x: hidden;
}

/* Background Mesh Pattern */
.reserva-page-wrapper::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
    background-size: 32px 32px;
    pointer-events: none;
    opacity: 0.6;
}

/* Container */
.reserva-container {
    max-width: 1240px;
    margin: 0 auto;
    padding: 0 1.5rem;
    position: relative;
    z-index: 2;
}

/* Hero Section */
.reserva-hero-section {
    padding: 2.5rem 0 3.5rem;
}

.reserva-hero-grid {
    display: grid;
    grid-template-columns: 1.25fr 0.95fr;
    gap: 3.5rem;
    align-items: start;
}

@media (max-width: 992px) {
    .reserva-page-wrapper {
        padding-top: 85px;
    }
    .reserva-hero-section {
        padding: 1.5rem 0 2.5rem;
    }
    .reserva-hero-grid {
        grid-template-columns: 1fr;
        gap: 2.5rem;
    }
}

/* Badges de Status */
.reserva-badges-row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
    align-items: center;
    margin-bottom: 1.5rem;
}

.badge-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding: 0.45rem 1rem;
    border-radius: 30px;
    backdrop-filter: blur(8px);
}

.badge-tag.tag-orange {
    background: rgba(255, 128, 0, 0.18);
    border: 1px solid rgba(255, 128, 0, 0.6);
    color: #ff9d3b;
    box-shadow: 0 0 15px rgba(255, 128, 0, 0.25);
}

.badge-tag.tag-green {
    background: rgba(40, 167, 69, 0.2);
    border: 1px solid rgba(40, 167, 69, 0.6);
    color: #4ade80;
    box-shadow: 0 0 15px rgba(40, 167, 69, 0.2);
}

.badge-tag.tag-yellow {
    background: rgba(255, 193, 7, 0.18);
    border: 1px solid rgba(255, 193, 7, 0.6);
    color: #fbbf24;
}

.badge-tag.tag-neutral {
    background: rgba(255, 255, 255, 0.07);
    border: 1px solid rgba(255, 255, 255, 0.18);
    color: #e2e8f0;
}

/* Título e Textos */
.reserva-main-title {
    font-size: clamp(2.2rem, 4.5vw, 3.4rem);
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 1.3rem;
    letter-spacing: -0.5px;
    color: #ffffff;
}

.reserva-main-title .gradient-highlight {
    background: linear-gradient(135deg, #ffffff 0%, #ffb266 50%, #ff8000 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.reserva-lead-desc {
    font-size: 1.15rem;
    line-height: 1.75;
    color: rgba(255, 255, 255, 0.88);
    margin-bottom: 2rem;
    font-weight: 400;
}

/* Social Proof Banner */
.reserva-counter-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    background: linear-gradient(90deg, rgba(255, 128, 0, 0.15), rgba(255, 128, 0, 0.05));
    border: 1px solid rgba(255, 128, 0, 0.35);
    border-radius: 40px;
    padding: 0.5rem 1.2rem;
    margin-bottom: 2rem;
}

.reserva-counter-pill .pulse-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--lp-accent);
    box-shadow: 0 0 10px var(--lp-accent);
    animation: counterPulse 1.8s infinite;
}

@keyframes counterPulse {
    0% { transform: scale(0.9); opacity: 0.8; box-shadow: 0 0 0 0 rgba(255, 128, 0, 0.7); }
    70% { transform: scale(1.15); opacity: 1; box-shadow: 0 0 0 8px rgba(255, 128, 0, 0); }
    100% { transform: scale(0.9); opacity: 0.8; box-shadow: 0 0 0 0 rgba(255, 128, 0, 0); }
}

/* Quick Specs Grid */
.reserva-specs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 1rem;
    margin-bottom: 2.5rem;
}

.spec-item-card {
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 12px;
    padding: 1.1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    backdrop-filter: blur(8px);
    transition: transform 0.2s ease, border-color 0.2s ease;
}

.spec-item-card:hover {
    transform: translateY(-2px);
    border-color: rgba(255, 128, 0, 0.3);
}

.spec-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: linear-gradient(135deg, rgba(255, 128, 0, 0.25), rgba(3, 4, 94, 0.4));
    border: 1px solid rgba(255, 128, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: var(--lp-accent);
    flex-shrink: 0;
}

.spec-text-box .spec-label {
    display: block;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: var(--lp-text-muted);
    letter-spacing: 0.5px;
    font-weight: 500;
}

.spec-text-box .spec-value {
    font-size: 1rem;
    font-weight: 700;
    color: #ffffff;
    line-height: 1.3;
}

/* Caixa de Atenção/LGPD */
.reserva-info-banner {
    background: linear-gradient(90deg, rgba(3, 4, 94, 0.5), rgba(0, 119, 255, 0.15));
    border: 1px solid rgba(0, 195, 255, 0.3);
    border-left: 5px solid #00c3ff;
    border-radius: 10px;
    padding: 1.2rem 1.4rem;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    margin-top: 2rem;
}

.reserva-info-banner i {
    color: #00c3ff;
    font-size: 1.4rem;
    margin-top: 0.15rem;
}

.reserva-info-banner p {
    margin: 0;
    font-size: 0.92rem;
    line-height: 1.6;
    color: #e2e8f0;
}

/* ============================================================
   FORMULÁRIO LATERAL DE RESERVA (GLASSMORPHISM DE ALTO IMPACTO)
   ============================================================ */
.reserva-sticky-col {
    position: sticky;
    top: 100px;
    z-index: 10;
}

.reserva-form-card {
    background: var(--lp-card-bg);
    border: 1px solid var(--lp-card-border);
    border-radius: 20px;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.65), 0 0 40px rgba(3, 4, 94, 0.5);
    overflow: hidden;
    backdrop-filter: blur(16px);
}

.reserva-form-banner {
    width: 100%;
    height: 190px;
    position: relative;
    background: #020220;
    overflow: hidden;
}

.reserva-form-banner img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.reserva-form-banner-overlay {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 80px;
    background: linear-gradient(to top, rgba(7, 12, 38, 1), transparent);
}

.reserva-form-content {
    padding: 2rem;
}

.reserva-form-header {
    text-align: center;
    margin-bottom: 1.8rem;
}

.reserva-form-header h2 {
    font-size: 1.55rem;
    font-weight: 800;
    color: #ffffff;
    margin-bottom: 0.4rem;
    letter-spacing: -0.3px;
}

.reserva-form-header p {
    font-size: 0.88rem;
    color: var(--lp-text-muted);
    margin: 0;
    line-height: 1.5;
}

/* Inputs do Formulário */
.reserva-form-group {
    margin-bottom: 1.1rem;
}

.reserva-form-group label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: #e2e8f0;
    margin-bottom: 0.4rem;
}

.reserva-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.reserva-input-wrapper i {
    position: absolute;
    left: 1rem;
    color: #718096;
    font-size: 0.95rem;
    pointer-events: none;
    transition: color 0.2s ease;
}

.reserva-input-wrapper .reserva-control {
    padding-left: 2.7rem;
}

.reserva-control {
    width: 100%;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 10px;
    padding: 0.8rem 1rem;
    color: #ffffff;
    font-size: 0.95rem;
    font-family: inherit;
    transition: all 0.25s ease;
    box-sizing: border-box;
}

.reserva-control::placeholder {
    color: #718096;
}

.reserva-control:focus {
    outline: none;
    border-color: var(--lp-accent);
    background: rgba(255, 255, 255, 0.08);
    box-shadow: 0 0 15px var(--lp-accent-glow);
}

.reserva-input-wrapper:focus-within i {
    color: var(--lp-accent);
}

/* ============================================================
   SELETOR DE MODALIDADE (CARDS INTERATIVOS SELECIONÁVEIS)
   Substitui botões de rádio nativos desalinhados por UI premium
   ============================================================ */
.modality-selector-container {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1.2rem;
}

.modality-selector-label {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--lp-accent);
    margin-bottom: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.modality-cards-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.65rem;
}

@media (max-width: 480px) {
    .modality-cards-group {
        grid-template-columns: 1fr;
    }
}

.modality-card-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    padding: 0.75rem 0.85rem;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.04);
    border: 1.5px solid rgba(255, 255, 255, 0.12);
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
}

.modality-card-item:hover {
    background: rgba(255, 128, 0, 0.06);
    border-color: rgba(255, 128, 0, 0.4);
    transform: translateY(-1px);
}

.modality-card-item.selected {
    background: rgba(255, 128, 0, 0.15);
    border-color: var(--lp-accent);
    box-shadow: 0 0 20px rgba(255, 128, 0, 0.25);
}

.modality-card-left {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.modality-badge-icon {
    font-size: 1.3rem;
    line-height: 1;
}

.modality-card-text {
    display: flex;
    flex-direction: column;
}

.modality-card-title {
    font-weight: 700;
    font-size: 0.92rem;
    color: #ffffff;
}

.modality-card-desc {
    font-size: 0.76rem;
    color: var(--lp-text-muted);
}

.modality-card-item.selected .modality-card-title {
    color: #ffb266;
}

.modality-radio-indicator {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s ease;
}

.modality-radio-indicator .dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--lp-accent);
    opacity: 0;
    transform: scale(0.5);
    transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modality-card-item.selected .modality-radio-indicator {
    border-color: var(--lp-accent);
    box-shadow: 0 0 10px var(--lp-accent-glow);
}

.modality-card-item.selected .modality-radio-indicator .dot {
    opacity: 1;
    transform: scale(1);
}

/* Termo LGPD */
.reserva-lgpd-box {
    display: flex;
    align-items: flex-start;
    gap: 0.7rem;
    font-size: 0.78rem;
    color: #a0aec0;
    margin: 1.2rem 0;
    line-height: 1.45;
}

.reserva-lgpd-box input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--lp-accent);
    cursor: pointer;
    margin-top: 2px;
    flex-shrink: 0;
}

.reserva-lgpd-box a {
    color: var(--lp-accent);
    text-decoration: underline;
}

/* Botão Principal */
.btn-submit-reserva {
    width: 100%;
    background: linear-gradient(135deg, #ff8000 0%, #ff5500 100%);
    color: #ffffff;
    font-size: 1.05rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding: 1.1rem 1.5rem;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    box-shadow: 0 10px 25px rgba(255, 128, 0, 0.35);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
}

.btn-submit-reserva:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 35px rgba(255, 128, 0, 0.5);
    background: linear-gradient(135deg, #ff941a 0%, #ff661a 100%);
}

.btn-submit-reserva:active {
    transform: translateY(0);
}

.reserva-trust-tag {
    text-align: center;
    font-size: 0.78rem;
    color: #718096;
    margin-top: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
}

/* ============================================================
   SEÇÕES COMPLEMENTARES DE CONTEÚDO E AUTORIDADE
   ============================================================ */
.reserva-details-section {
    padding: 3rem 0;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.reserva-section-heading {
    text-align: center;
    max-width: 750px;
    margin: 0 auto 3rem;
}

.reserva-section-heading .section-pretitle {
    color: var(--lp-accent);
    text-transform: uppercase;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    display: block;
    margin-bottom: 0.4rem;
}

.reserva-section-heading h3 {
    font-size: 2.1rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.25;
}

/* Cards de Pilares / Metodologia */
.reserva-cards-trio {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3.5rem;
}

.reserva-feature-box {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 2rem;
    backdrop-filter: blur(8px);
    transition: transform 0.25s ease, border-color 0.25s ease;
}

.reserva-feature-box:hover {
    transform: translateY(-4px);
    border-color: rgba(255, 128, 0, 0.35);
}

.reserva-feature-box .feat-icon {
    width: 52px;
    height: 52px;
    border-radius: 12px;
    background: rgba(255, 128, 0, 0.15);
    border: 1px solid rgba(255, 128, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--lp-accent);
    margin-bottom: 1.2rem;
}

.reserva-feature-box h4 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.6rem;
}

.reserva-feature-box p {
    font-size: 0.95rem;
    line-height: 1.6;
    color: var(--lp-text-muted);
    margin: 0;
}

/* Renderização da Descrição Formatada */
.reserva-formatted-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 16px;
    padding: 2.2rem;
    margin-bottom: 3rem;
}

.reserva-content-section-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--lp-accent);
    margin: 1.5rem 0 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding-bottom: 0.4rem;
}

.reserva-content-paragraph {
    font-size: 1.02rem;
    line-height: 1.75;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 1rem;
}

.reserva-bullet-wrapper {
    margin: 1rem 0 1.5rem;
}

.reserva-bullet-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 0.75rem;
}

.reserva-bullet-list li {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.06);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.95rem;
    color: #edf2f7;
}

.reserva-bullet-list li i {
    color: #38a169;
    font-size: 1rem;
    flex-shrink: 0;
}

/* Timeline: Como Funciona a Reserva */
.reserva-steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3.5rem;
}

.reserva-step-card {
    position: relative;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    padding: 2rem 1.6rem;
}

.step-number {
    position: absolute;
    top: -14px;
    left: 20px;
    background: linear-gradient(135deg, var(--lp-accent), #e06c00);
    color: #fff;
    font-size: 0.85rem;
    font-weight: 800;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(255, 128, 0, 0.4);
}

.reserva-step-card h5 {
    font-size: 1.15rem;
    font-weight: 700;
    color: #fff;
    margin: 0.4rem 0 0.5rem;
}

.reserva-step-card p {
    font-size: 0.9rem;
    color: var(--lp-text-muted);
    line-height: 1.55;
    margin: 0;
}

/* FAQ Accordion */
.faq-accordion-group {
    max-width: 850px;
    margin: 0 auto 3rem;
    display: flex;
    flex-direction: column;
    gap: 0.9rem;
}

.faq-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    overflow: hidden;
    transition: border-color 0.2s ease;
}

.faq-item.active {
    border-color: rgba(255, 128, 0, 0.4);
    background: rgba(255, 255, 255, 0.05);
}

.faq-question {
    padding: 1.2rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    font-weight: 700;
    font-size: 1.05rem;
    color: #ffffff;
    user-select: none;
}

.faq-question i {
    color: var(--lp-accent);
    transition: transform 0.3s ease;
}

.faq-item.active .faq-question i {
    transform: rotate(180deg);
}

.faq-answer {
    display: none;
    padding: 0 1.5rem 1.4rem;
    font-size: 0.95rem;
    line-height: 1.7;
    color: var(--lp-text-muted);
}

.faq-item.active .faq-answer {
    display: block;
}

/* Barra Fixa Flutuante no Mobile (Sticky CTA) */
.mobile-sticky-cta-bar {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(7, 12, 38, 0.95);
    border-top: 1px solid rgba(255, 128, 0, 0.4);
    padding: 0.9rem 1.2rem;
    z-index: 9998;
    backdrop-filter: blur(12px);
    box-shadow: 0 -10px 25px rgba(0, 0, 0, 0.5);
}

@media (max-width: 992px) {
    .mobile-sticky-cta-bar {
        display: block;
    }
}
</style>

<main class="reserva-page-wrapper">
    <!-- Hero Section Principal -->
    <section class="reserva-hero-section">
        <div class="reserva-container">
            <div class="reserva-hero-grid">
                
                <!-- Coluna Esquerda: Informações Principais da Campanha -->
                <div class="reserva-hero-info">
                    
                    <!-- Badges de Status e Modalidade -->
                    <div class="reserva-badges-row">
                        <?php if ($isEnrollmentOpen): ?>
                            <span class="badge-tag tag-green">
                                <i class="fas fa-check-circle"></i> Matrículas Oficiais Abertas
                            </span>
                        <?php elseif ($isWaitingList): ?>
                            <span class="badge-tag tag-yellow">
                                <i class="fas fa-hourglass-half"></i> Lista de Espera Disponível
                            </span>
                        <?php elseif ($isClosed): ?>
                            <span class="badge-tag tag-neutral" style="color: #f87171; border-color: rgba(239, 68, 68, 0.5);">
                                <i class="fas fa-lock"></i> Vagas Esgotadas
                            </span>
                        <?php else: ?>
                            <span class="badge-tag tag-orange">
                                <i class="fas fa-fire"></i> Lista de Interesse / Pré-Reserva
                            </span>
                        <?php endif; ?>

                        <!-- Modalidades Disponíveis -->
                        <?php if ($allowsPresencial && $allowsOnline): ?>
                            <span class="badge-tag tag-neutral">
                                <i class="fas fa-chalkboard-teacher"></i> Presencial + Ao Vivo
                            </span>
                        <?php elseif ($allowsPresencial): ?>
                            <span class="badge-tag tag-neutral">
                                <i class="fas fa-building"></i> Modalidade Presencial
                            </span>
                        <?php elseif ($allowsOnline): ?>
                            <span class="badge-tag tag-neutral">
                                <i class="fas fa-laptop"></i> Modalidade Online (Ao Vivo)
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($campaign['city'])): ?>
                            <span class="badge-tag tag-neutral">
                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($campaign['city']) ?><?= !empty($campaign['state']) ? ' - ' . htmlspecialchars($campaign['state']) : '' ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Título da Campanha -->
                    <h1 class="reserva-main-title">
                        <span class="gradient-highlight"><?= htmlspecialchars($campaign['title']) ?></span>
                    </h1>

                    <!-- Prova Social Viva (Contador de Interesse) -->
                    <?php if (!empty($campaign['show_counter']) && $totalReservasAtuais > 0): ?>
                        <div class="reserva-counter-pill">
                            <div class="pulse-dot"></div>
                            <span style="font-size: 0.92rem; font-weight: 600; color: #ffb774;">
                                🔥 <strong><?= $totalReservasAtuais ?> pessoas</strong> já garantiram interesse nesta turma!
                            </span>
                        </div>
                    <?php endif; ?>

                    <!-- Descrição Curta / Gancho de Conversão -->
                    <?php if (!empty($campaign['short_description'])): ?>
                        <p class="reserva-lead-desc">
                            <?= nl2br(htmlspecialchars($campaign['short_description'])) ?>
                        </p>
                    <?php endif; ?>

                    <!-- Grid de Destaques Rápidos -->
                    <div class="reserva-specs-grid">
                        <?php if (!empty($campaign['class_start_date'])): ?>
                            <div class="spec-item-card">
                                <div class="spec-icon-box"><i class="fas fa-calendar-alt"></i></div>
                                <div class="spec-text-box">
                                    <span class="spec-label">Previsão de Início</span>
                                    <span class="spec-value"><?= date('d/m/Y', strtotime($campaign['class_start_date'])) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($campaign['city'])): ?>
                            <div class="spec-item-card">
                                <div class="spec-icon-box"><i class="fas fa-map-marked-alt"></i></div>
                                <div class="spec-text-box">
                                    <span class="spec-label">Localização</span>
                                    <span class="spec-value"><?= htmlspecialchars($campaign['city']) ?><?= !empty($campaign['state']) ? '/' . htmlspecialchars($campaign['state']) : '' ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($allowsPresencial && $allowsOnline): ?>
                            <div class="spec-item-card">
                                <div class="spec-icon-box"><i class="fas fa-chalkboard"></i></div>
                                <div class="spec-text-box">
                                    <span class="spec-label">Formato de Aula</span>
                                    <span class="spec-value">Presencial ou Online</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($campaign['target_audience'])): ?>
                            <div class="spec-item-card" style="grid-column: 1 / -1;">
                                <div class="spec-icon-box"><i class="fas fa-bullseye"></i></div>
                                <div class="spec-text-box">
                                    <span class="spec-label">Público-Alvo</span>
                                    <span class="spec-value"><?= htmlspecialchars($campaign['target_audience']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Descrição Detalhada da Turma (Integrada ao Hero para preencher a coluna e eliminar o vão vazio) -->
                    <?php if (!empty($campaign['description'])): ?>
                        <div class="reserva-hero-description-block" style="margin: 2rem 0 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem;">
                                <span style="background: rgba(255, 128, 0, 0.2); color: #ff9d3b; width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">📋</span>
                                <h3 style="font-size: 1.25rem; font-weight: 700; color: #ffffff; margin: 0;">Estrutura e Informações da Turma</h3>
                            </div>
                            <div class="reserva-formatted-card" style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 14px; padding: 1.6rem; box-shadow: 0 4px 20px rgba(0,0,0,0.25);">
                                <?= render_formatted_description($campaign['description']) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Aviso de Segurança e Compromisso Zero -->
                    <div class="reserva-info-banner">
                        <i class="fas fa-shield-alt"></i>
                        <p>
                            <strong>Compromisso Zero:</strong> A pré-reserva garante sua <strong>prioridade máxima</strong> na formação da turma e acesso aos <strong>descontos de 1º lote</strong>. Não há cobrança antecipada nem dados bancários exigidos neste momento.
                        </p>
                    </div>

                </div>

                <!-- Coluna Direita: Formulário Flutuante Sticky -->
                <div class="reserva-sticky-col" id="colunaFormulario">
                    <div class="reserva-form-card">
                        
                        <?php if (!empty($campaign['image'])): ?>
                            <div class="reserva-form-banner">
                                <img src="/uploads/<?= htmlspecialchars($campaign['image']) ?>" alt="<?= htmlspecialchars($campaign['title']) ?>">
                                <div class="reserva-form-banner-overlay"></div>
                            </div>
                        <?php endif; ?>

                        <div class="reserva-form-content">
                            <div class="reserva-form-header">
                                <h2><?= $isWaitingList ? 'Lista de Espera Oficial' : 'Garanta Sua Prioridade' ?></h2>
                                <p>
                                    <?= $isWaitingList 
                                        ? 'As vagas principais estão preenchidas. Cadastre-se para ser convocado em caso de desistência ou nova turma.' 
                                        : 'Preencha os dados abaixo para receber as informações de abertura e garantir os valores promocionais.' ?>
                                </p>
                            </div>

                            <?php if ($isClosed): ?>
                                <div style="background: rgba(220,53,69,0.15); border: 1px solid #dc3545; border-radius: 12px; padding: 1.5rem; text-align: center;">
                                    <i class="fas fa-times-circle" style="font-size: 2.5rem; color: #dc3545; margin-bottom: 0.8rem;"></i>
                                    <h3 style="color: #fff; font-size: 1.2rem; margin-bottom: 0.5rem;">Reservas Encerradas</h3>
                                    <p style="color: #ccc; font-size: 0.9rem; margin-bottom: 1.2rem;">O limite de participantes para esta turma foi atingido e a lista de espera foi encerrada.</p>
                                    <a href="/cursos.php" class="btn" style="background: var(--lp-accent); color: #fff; text-decoration: none; padding: 0.7rem 1.4rem; border-radius: 8px; font-weight: 700; font-size: 0.9rem; display: inline-block;">
                                        Ver Cursos Disponíveis
                                    </a>
                                </div>
                            <?php else: ?>

                                <!-- Formulário de Reserva com AJAX -->
                                <form id="formReserva">
                                    <input type="hidden" name="campaign_id" value="<?= (int)$campaign['id'] ?>">
                                    <input type="hidden" name="campaign_slug" value="<?= htmlspecialchars($campaign['slug']) ?>">

                                    <!-- Honeypot Anti-Spam -->
                                    <div style="display:none !important;" aria-hidden="true">
                                        <input type="text" name="website_url_check" tabindex="-1" autocomplete="off">
                                        <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                                    </div>

                                    <!-- Captura automática de UTMs da URL -->
                                    <input type="hidden" name="utm_source" id="utm_source">
                                    <input type="hidden" name="utm_medium" id="utm_medium">
                                    <input type="hidden" name="utm_campaign" id="utm_campaign">
                                    <input type="hidden" name="utm_content" id="utm_content">
                                    <input type="hidden" name="utm_term" id="utm_term">

                                    <!-- Nome Completo -->
                                    <div class="reserva-form-group">
                                        <label for="name_field">Nome Completo *</label>
                                        <div class="reserva-input-wrapper">
                                            <i class="fas fa-user"></i>
                                            <input type="text" name="name" id="name_field" required class="reserva-control" placeholder="Digite seu nome completo">
                                        </div>
                                    </div>

                                    <!-- WhatsApp -->
                                    <div class="reserva-form-group">
                                        <label for="phone_field">WhatsApp com DDD *</label>
                                        <div class="reserva-input-wrapper">
                                            <i class="fab fa-whatsapp" style="font-size: 1.1rem;"></i>
                                            <input type="tel" name="phone" id="phone_field" required class="reserva-control" placeholder="(99) 99999-9999">
                                        </div>
                                    </div>

                                    <!-- E-mail -->
                                    <div class="reserva-form-group">
                                        <label for="email_field">Seu Melhor E-mail *</label>
                                        <div class="reserva-input-wrapper">
                                            <i class="fas fa-envelope"></i>
                                            <input type="email" name="email" id="email_field" required class="reserva-control" placeholder="exemplo@email.com">
                                        </div>
                                    </div>

                                    <!-- Cidade e Estado -->
                                    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0.8rem;" class="reserva-form-group">
                                        <div>
                                            <label for="city_field">Cidade</label>
                                            <div class="reserva-input-wrapper">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <input type="text" name="city" id="city_field" class="reserva-control" value="<?= htmlspecialchars($campaign['city']) ?>" placeholder="Sua cidade">
                                            </div>
                                        </div>
                                        <div>
                                            <label for="state_field">UF</label>
                                            <input type="text" name="state" id="state_field" maxlength="2" class="reserva-control" value="<?= htmlspecialchars($campaign['state']) ?>" placeholder="MA" style="text-align: center; text-transform: uppercase;">
                                        </div>
                                    </div>

                                    <!-- Seletor de Modalidade (Apenas Presencial e Online) -->
                                    <?php if ($allowsPresencial && $allowsOnline): ?>
                                        <div class="modality-selector-container">
                                            <div class="modality-selector-label">
                                                <i class="fas fa-graduation-cap"></i> Modalidade de Estudo *
                                            </div>
                                            <input type="hidden" name="preferred_modality" id="selectedModality" value="presencial" required>

                                            <div class="modality-cards-group">
                                                <!-- Card Presencial -->
                                                <div class="modality-card-item selected" data-value="presencial" onclick="selectModality('presencial')">
                                                    <div class="modality-card-left">
                                                        <span class="modality-badge-icon">🏫</span>
                                                        <div class="modality-card-text">
                                                            <span class="modality-card-title">Presencial</span>
                                                            <span class="modality-card-desc">Em sala de aula</span>
                                                        </div>
                                                    </div>
                                                    <div class="modality-radio-indicator"><div class="dot"></div></div>
                                                </div>

                                                <!-- Card Online -->
                                                <div class="modality-card-item" data-value="online" onclick="selectModality('online')">
                                                    <div class="modality-card-left">
                                                        <span class="modality-badge-icon">💻</span>
                                                        <div class="modality-card-text">
                                                            <span class="modality-card-title">Online</span>
                                                            <span class="modality-card-desc">Ao vivo / Plataforma</span>
                                                        </div>
                                                    </div>
                                                    <div class="modality-radio-indicator"><div class="dot"></div></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php elseif ($allowsPresencial): ?>
                                        <input type="hidden" name="preferred_modality" value="presencial">
                                    <?php elseif ($allowsOnline): ?>
                                        <input type="hidden" name="preferred_modality" value="online">
                                    <?php else: ?>
                                        <input type="hidden" name="preferred_modality" value="presencial">
                                    <?php endif; ?>

                                    <!-- Perguntas Customizadas Opcionais -->
                                    <?php if (!empty($customFields)): ?>
                                        <?php foreach ($customFields as $fKey => $fConfig): 
                                            $fieldLabel = $fConfig['label'] ?? $fKey;
                                            // Se o seletor acima já capturou a modalidade, ignorar pergunta customizada duplicada
                                            if ($allowsPresencial && $allowsOnline && stripos($fieldLabel, 'modalidade') !== false) {
                                                continue;
                                            }
                                        ?>
                                            <div class="reserva-form-group">
                                                <label><?= htmlspecialchars($fieldLabel) ?></label>
                                                <?php if (!empty($fConfig['options']) && is_array($fConfig['options'])): ?>
                                                    <select name="custom_<?= htmlspecialchars($fKey) ?>" class="reserva-control" style="background: #111728;">
                                                        <option value="">Selecione uma opção...</option>
                                                        <?php foreach ($fConfig['options'] as $opt): ?>
                                                            <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php else: ?>
                                                    <input type="text" name="custom_<?= htmlspecialchars($fKey) ?>" class="reserva-control" placeholder="<?= htmlspecialchars($fConfig['placeholder'] ?? '') ?>">
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <!-- Consentimento LGPD -->
                                    <div class="reserva-lgpd-box">
                                        <input type="checkbox" name="consent_privacy" id="consent_privacy" required checked>
                                        <label for="consent_privacy">
                                            Autorizo o ISP Preparatórios a utilizar meus dados para envio de informações sobre esta turma, abertura de matrículas e cronograma pedagógico, conforme a <a href="/privacidade.php" target="_blank">Política de Privacidade</a>.
                                        </label>
                                    </div>

                                    <!-- Feedback de Erro/Aviso -->
                                    <div id="formFeedback" style="display: none; padding: 0.9rem 1rem; border-radius: 8px; font-size: 0.9rem; margin-bottom: 1rem;"></div>

                                    <!-- Botão de Envio Principal -->
                                    <button type="submit" id="btnSubmitReserva" class="btn-submit-reserva">
                                        <span><?= $isWaitingList ? 'Entrar na Lista de Espera' : 'Quero Participar Desta Turma' ?></span>
                                        <i class="fas fa-arrow-right"></i>
                                    </button>

                                    <div class="reserva-trust-tag">
                                        <i class="fas fa-lock"></i> Seus dados estão 100% seguros • Sem pagamento antecipado
                                    </div>
                                </form>

                                <!-- Painel de Confirmação e Sucesso -->
                                <div id="painelSucesso" style="display: none; text-align: center; padding: 1rem 0;">
                                    <div style="width: 76px; height: 76px; background: rgba(40,167,69,0.2); border: 2px solid #28a745; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.3rem; box-shadow: 0 0 25px rgba(40,167,69,0.4);">
                                        <i class="fas fa-check" style="font-size: 2.2rem; color: #28a745;"></i>
                                    </div>
                                    
                                    <h3 id="sucessoTitulo" style="color: #fff; font-size: 1.5rem; font-weight: 800; margin-bottom: 0.6rem;">
                                        Reserva Confirmada com Sucesso!
                                    </h3>
                                    
                                    <p id="sucessoMsg" style="color: #cbd5e1; font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;">
                                        Seu interesse foi registrado com prioridade. Nossa equipe entrará em contato via WhatsApp com os detalhes da turma e liberação do primeiro lote promocional.
                                    </p>

                                    <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.12); border-radius: 12px; padding: 1.2rem; margin-bottom: 1.5rem; text-align: left;">
                                        <div style="font-size: 0.88rem; color: #a0aec0; margin-bottom: 0.4rem;">
                                            Protocolo de Reserva: <strong id="sucessoProtocolo" style="color: #fff; font-family: monospace; font-size: 1rem;"></strong>
                                        </div>
                                        <div style="font-size: 0.88rem; color: #a0aec0; margin-bottom: 0.4rem;">
                                            Turma: <strong id="sucessoCampanha" style="color: #fff;"></strong>
                                        </div>
                                        <div style="font-size: 0.88rem; color: #a0aec0;">
                                            Modalidade: <strong id="sucessoModalidade" style="color: var(--lp-accent);"></strong>
                                        </div>
                                    </div>

                                    <a id="btnWhatsAppISP" href="#" target="_blank" style="background: #25d366; color: #fff; text-decoration: none; padding: 0.9rem 1.6rem; border-radius: 10px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 0.6rem; font-size: 1rem; width: 100%; box-shadow: 0 8px 20px rgba(37,211,102,0.35); margin-bottom: 1rem; transition: transform 0.2s ease;">
                                        <i class="fab fa-whatsapp" style="font-size: 1.3rem;"></i> Conversar com a Coordenação no WhatsApp
                                    </a>

                                    <div>
                                        <button type="button" onclick="window.location.reload();" style="background: transparent; border: none; color: #718096; text-decoration: underline; cursor: pointer; font-size: 0.85rem;">
                                            Registrar outra reserva de interesse
                                        </button>
                                    </div>
                                </div>

                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ============================================================
         SEÇÃO DE CONTEÚDO E ESTRUTURA DA TURMA
         ============================================================ -->
    <section class="reserva-details-section">
        <div class="reserva-container">
            
            <div class="reserva-section-heading">
                <span class="section-pretitle">Preparação de Elite</span>
                <h3>Por Que Escolher o ISP Para Sua Aprovação?</h3>
            </div>

            <!-- Trio de Pilares da Preparação -->
            <div class="reserva-cards-trio">
                <div class="reserva-feature-box">
                    <div class="feat-icon"><i class="fas fa-bullseye"></i></div>
                    <h4>100% Focado na Banca</h4>
                    <p>Aulas planejadas estritamente a partir do perfil da banca organizadora, priorizando os conteúdos mais cobrados e o estilo das questões.</p>
                </div>

                <div class="reserva-feature-box">
                    <div class="feat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <h4>Professores Especialistas</h4>
                    <p>Corpo docente renomado com ampla experiência em concursos públicos na área da educação e carreiras afins.</p>
                </div>

                <div class="reserva-feature-box">
                    <div class="feat-icon"><i class="fas fa-file-pdf"></i></div>
                    <h4>Material de Apoio & Questões</h4>
                    <p>Cadernos de questões comentadas, resumos estratégicos e banco exclusivo de exercícios para fixação prática.</p>
                </div>
            </div>

            <!-- Como Funciona o Processo da Pré-Reserva -->
            <div class="reserva-section-heading">
                <span class="section-pretitle">Transparência Total</span>
                <h3>Como Funciona a Sua Pré-Reserva?</h3>
            </div>

            <div class="reserva-steps-grid">
                <div class="reserva-step-card">
                    <div class="step-number">1</div>
                    <h5>Manifestação de Interesse</h5>
                    <p>Você preenche o formulário gratuitamente. Nenhum pagamento é cobrado neste momento e você garante seu lugar na fila prioritária.</p>
                </div>

                <div class="reserva-step-card">
                    <div class="step-number">2</div>
                    <h5>Confirmação da Turma</h5>
                    <p>Nossa equipe coordena a formação das vagas e informa o cronograma oficial, horários e lista final de professores pelo WhatsApp.</p>
                </div>

                <div class="reserva-step-card">
                    <div class="step-number">3</div>
                    <h5>Condição Exclusiva de 1º Lote</h5>
                    <p>Você terá 24h a 48h de prioridade exclusiva para efetivar sua matrícula antes do público geral, garantindo a maior economia de lançamento.</p>
                </div>
            </div>

            <!-- FAQ / Perguntas Frequentes -->
            <div class="reserva-section-heading">
                <span class="section-pretitle">Tire Suas Dúvidas</span>
                <h3>Perguntas Frequentes</h3>
            </div>

            <div class="faq-accordion-group">
                <div class="faq-item active">
                    <div class="faq-question">
                        <span>A reserva de interesse possui algum custo?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Não! A pré-reserva de interesse é <strong>100% gratuita</strong>. Ela serve para você garantir prioridade na vaga e receber em primeira mão a liberação das matrículas com os valores promocionais de primeiro lote.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Quando serão divulgados os valores oficiais e cronograma?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Assim que a turma atingir o quórum de interessados, a coordenação pedagógica do ISP entrará em contato via WhatsApp e e-mail com a tabela de valores promocionais, formas de parcelamento e cronograma de aulas.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>Como funciona a modalidade Online / Ao Vivo?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Na modalidade online, você assiste às transmissões em tempo real com possibilidade de interagir e tirar dúvidas com o professor, além de contar com o replay de todas as aulas gravadas na plataforma de estudos para assistir quando e onde quiser.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <span>O que acontece se as vagas principais forem preenchidas?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Caso as vagas da turma regular estejam preenchidas, você entra automaticamente na Lista de Espera. Havendo desistências ou abertura de turmas adicionais, os inscritos da lista são convocados na ordem rigorosa de inscrição.
                    </div>
                </div>
            </div>

            <!-- Se Matrículas Oficiais Já Estiverem Abertas -->
            <?php if ($isEnrollmentOpen && !empty($campaign['enrollment_link'])): ?>
                <div style="text-align: center; background: linear-gradient(135deg, rgba(40,167,69,0.15), rgba(3,4,94,0.4)); border: 1px solid rgba(40,167,69,0.4); padding: 3rem 2rem; border-radius: 20px; margin-top: 3rem;">
                    <span style="background: #28a745; color: #fff; padding: 0.3rem 0.9rem; border-radius: 20px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase;">
                        Inscrições Definitivas Liberadas
                    </span>
                    <h3 style="color: #fff; font-size: 2rem; font-weight: 800; margin: 1rem 0 0.5rem;">
                        As Matrículas Oficiais Desta Turma Estão Abertas!
                    </h3>
                    <p style="color: #cbd5e1; max-width: 600px; margin: 0 auto 1.8rem;">
                        Garanta sua vaga definitiva antes que o lote promocional encerre.
                    </p>
                    <a href="<?= htmlspecialchars($campaign['enrollment_link']) ?>" target="_blank" class="btn-submit-reserva" style="display: inline-flex; width: auto; padding: 1.1rem 2.5rem; text-decoration: none; margin: 0 auto;">
                        <i class="fas fa-shopping-cart"></i> <?= htmlspecialchars($campaign['enrollment_button_text'] ?: 'Fazer Minha Matrícula Agora') ?>
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- Barra Fixa Flutuante no Mobile (Sticky CTA) -->
    <div class="mobile-sticky-cta-bar">
        <button type="button" onclick="scrollToForm()" class="btn-submit-reserva" style="padding: 0.85rem 1.2rem; font-size: 0.95rem;">
            <span>⚡ <?= $isWaitingList ? 'Entrar na Lista de Espera' : 'Garantir Vaga na Turma' ?></span>
            <i class="fas fa-arrow-up"></i>
        </button>
    </div>
</main>

<script>
// 1. Função Interativa para Seleção dos Cards de Modalidade
function selectModality(val) {
    const hiddenInput = document.getElementById('selectedModality');
    if (hiddenInput) {
        hiddenInput.value = val;
    }
    const cards = document.querySelectorAll('.modality-card-item');
    cards.forEach(card => {
        if (card.getAttribute('data-value') === val) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    });
}

// 2. Scroll Suave para o Formulário (Usado no Mobile Sticky CTA)
function scrollToForm() {
    const formEl = document.getElementById('colunaFormulario');
    if (formEl) {
        formEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => {
            const firstInput = document.getElementById('name_field');
            if (firstInput) firstInput.focus();
        }, 500);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // 3. Acordeão Interativo de FAQ
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        question.addEventListener('click', () => {
            const wasActive = item.classList.contains('active');
            faqItems.forEach(i => i.classList.remove('active'));
            if (!wasActive) {
                item.classList.add('active');
            }
        });
    });

    // 4. Captura de parâmetros UTM da URL e persistência em sessionStorage
    const params = new URLSearchParams(window.location.search);
    const utmFields = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
    
    utmFields.forEach(field => {
        let val = params.get(field);
        if (val) {
            sessionStorage.setItem(field, val);
        } else {
            val = sessionStorage.getItem(field) || '';
        }
        const inputEl = document.getElementById(field);
        if (inputEl) inputEl.value = val;
    });

    // 5. Máscara amigável de Telefone / WhatsApp
    const phoneInput = document.getElementById('phone_field');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.substring(0, 11);
            if (v.length > 10) {
                e.target.value = '(' + v.substring(0, 2) + ') ' + v.substring(2, 7) + '-' + v.substring(7, 11);
            } else if (v.length > 6) {
                e.target.value = '(' + v.substring(0, 2) + ') ' + v.substring(2, 6) + '-' + v.substring(6, 10);
            } else if (v.length > 2) {
                e.target.value = '(' + v.substring(0, 2) + ') ' + v.substring(2);
            } else {
                e.target.value = v;
            }
        });
    }

    // 6. Submissão do Formulário via AJAX com Feedback Visual Imediato
    const form = document.getElementById('formReserva');
    const feedback = document.getElementById('formFeedback');
    const btnSubmit = document.getElementById('btnSubmitReserva');
    const painelSucesso = document.getElementById('painelSucesso');

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            feedback.style.display = 'none';
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Garantindo Sua Vaga...</span>';

            const formData = new FormData(form);

            fetch('/ajax_reservation.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<span><?= $isWaitingList ? 'Entrar na Lista de Espera' : 'Quero Participar Desta Turma' ?></span> <i class="fas fa-arrow-right"></i>';

                if (data.success) {
                    form.style.display = 'none';
                    painelSucesso.style.display = 'block';

                    if (data.already_registered) {
                        document.getElementById('sucessoTitulo').innerText = 'Você Já Possui Reserva Registrada!';
                        document.getElementById('sucessoMsg').innerText = data.message || 'Seu interesse já está confirmado nesta turma. Nossa equipe entrará em contato em breve.';
                    } else {
                        document.getElementById('sucessoTitulo').innerText = data.message || 'Reserva Realizada com Sucesso!';
                    }

                    document.getElementById('sucessoProtocolo').innerText = data.protocol || ('ISP-' + (data.reservation_id || '0001'));
                    document.getElementById('sucessoCampanha').innerText = data.campaign_title || '<?= addslashes($campaign['title']) ?>';
                    
                    const modMap = {'presencial': 'Presencial em Sala', 'online': 'Online / Ao Vivo', 'ambas': 'Presencial + Online (Ambas)'};
                    document.getElementById('sucessoModalidade').innerText = modMap[data.preferred_modality] || data.preferred_modality || 'Presencial';

                    if (data.whatsapp_contact_url) {
                        document.getElementById('btnWhatsAppISP').href = data.whatsapp_contact_url;
                    }

                    // Rolar até o topo do card para visibilidade do sucesso
                    painelSucesso.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    feedback.style.display = 'block';
                    feedback.style.background = 'rgba(220,53,69,0.2)';
                    feedback.style.border = '1px solid #dc3545';
                    feedback.style.color = '#ff8888';
                    feedback.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Ocorreu um erro ao registrar sua reserva.');
                }
            })
            .catch(err => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<span><?= $isWaitingList ? 'Entrar na Lista de Espera' : 'Quero Participar Desta Turma' ?></span> <i class="fas fa-arrow-right"></i>';
                feedback.style.display = 'block';
                feedback.style.background = 'rgba(220,53,69,0.2)';
                feedback.style.border = '1px solid #dc3545';
                feedback.style.color = '#ff8888';
                feedback.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Erro de comunicação com o servidor. Por favor, tente novamente.';
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
