<?php
/**
 * MarketingTracker - Camada Centralizada de Rastreamento de Conversões e Marketing
 * ISP Preparatórios
 *
 * Responsável por:
 * - Gerenciar configurações do Meta Pixel e integrações
 * - Injeção dinâmica e segura do script no cabeçalho
 * - Registro e auditoria de conversões no banco de dados
 * - Geração de event_id para deduplicação com Meta Conversions API (CAPI)
 * - Prover dados estruturados para o front-end
 */

class MarketingTracker {

    /**
     * Cache em memória das configurações na mesma execução
     */
    private static ?array $settingsCache = null;

    /**
     * Retorna as configurações globais de marketing
     */
    public static function getSettings(PDO $pdo): array {
        if (self::$settingsCache !== null) {
            return self::$settingsCache;
        }

        $defaults = [
            'id' => 1,
            'meta_pixel_enabled' => 1,
            'meta_pixel_id' => '854510411013557',
            'meta_advanced_matching' => 0,
            'meta_auto_events' => 0,
            'debug_mode' => 0,
            'meta_test_event_code' => '',
            'capi_enabled' => 0,
            'capi_access_token' => '',
            'capi_test_code' => ''
        ];

        try {
            $stmt = $pdo->query("SELECT * FROM marketing_settings WHERE id = 1 LIMIT 1");
            $row = $stmt->fetch();
            if ($row) {
                self::$settingsCache = array_merge($defaults, $row);
                return self::$settingsCache;
            }
        } catch (Exception $e) {
            error_log("MarketingTracker::getSettings error: " . $e->getMessage());
        }

        self::$settingsCache = $defaults;
        return self::$settingsCache;
    }

    /**
     * Atualiza as configurações de marketing
     */
    public static function saveSettings(PDO $pdo, array $data): bool {
        self::$settingsCache = null;
        try {
            $stmt = $pdo->prepare("UPDATE marketing_settings SET 
                meta_pixel_enabled = ?,
                meta_pixel_id = ?,
                meta_advanced_matching = ?,
                meta_auto_events = ?,
                debug_mode = ?,
                meta_test_event_code = ?,
                capi_enabled = ?,
                capi_access_token = ?,
                capi_test_code = ?
                WHERE id = 1");

            return $stmt->execute([
                !empty($data['meta_pixel_enabled']) ? 1 : 0,
                trim($data['meta_pixel_id'] ?? '854510411013557'),
                !empty($data['meta_advanced_matching']) ? 1 : 0,
                !empty($data['meta_auto_events']) ? 1 : 0,
                !empty($data['debug_mode']) ? 1 : 0,
                trim($data['meta_test_event_code'] ?? ''),
                !empty($data['capi_enabled']) ? 1 : 0,
                trim($data['capi_access_token'] ?? ''),
                trim($data['capi_test_code'] ?? '')
            ]);
        } catch (Exception $e) {
            error_log("MarketingTracker::saveSettings error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Localiza uma campanha de marketing por slug
     */
    public static function getCampaignBySlug(PDO $pdo, string $slug): ?array {
        try {
            $stmt = $pdo->prepare("SELECT * FROM marketing_campaigns WHERE slug = ? LIMIT 1");
            $stmt->execute([$slug]);
            $camp = $stmt->fetch();
            return $camp ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Localiza campanha de marketing pela URL da landing page
     */
    public static function getCampaignByLandingPage(PDO $pdo, string $path): ?array {
        try {
            $stmt = $pdo->prepare("SELECT * FROM marketing_campaigns WHERE landing_page = ? OR ? LIKE CONCAT('%', landing_page) LIMIT 1");
            $stmt->execute([$path, $path]);
            $camp = $stmt->fetch();
            return $camp ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Gera um event_id único e consistente para deduplicação entre Browser e Conversions API (CAPI)
     */
    public static function generateEventId(string $prefix = 'lead'): string {
        $cleanPrefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix);
        $random = bin2hex(random_bytes(6));
        $timestamp = time();
        return "isp_{$cleanPrefix}_{$timestamp}_{$random}";
    }

    /**
     * Registra uma conversão auditável no banco de dados
     */
    public static function recordConversion(PDO $pdo, array $data): ?int {
        try {
            $eventId = trim($data['event_id'] ?? self::generateEventId($data['event_name'] ?? 'conv'));
            $eventName = trim($data['event_name'] ?? 'Lead');
            $campaignId = !empty($data['campaign_id']) ? (int)$data['campaign_id'] : null;
            $campaignSlug = trim($data['campaign_slug'] ?? '');
            $entityType = trim($data['entity_type'] ?? 'reservation');
            $entityId = !empty($data['entity_id']) ? (int)$data['entity_id'] : null;
            $leadId = !empty($data['lead_id']) ? (int)$data['lead_id'] : null;
            $pageUrl = trim($data['page_url'] ?? ($_SERVER['REQUEST_URI'] ?? ''));
            $referrer = trim($data['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? ''));
            $utmSource = trim($data['utm_source'] ?? '');
            $utmMedium = trim($data['utm_medium'] ?? '');
            $utmCampaign = trim($data['utm_campaign'] ?? '');
            $utmContent = trim($data['utm_content'] ?? '');
            $utmTerm = trim($data['utm_term'] ?? '');
            $status = trim($data['status'] ?? 'recorded');
            $payloadJson = !empty($data['payload_json']) ? (is_array($data['payload_json']) ? json_encode($data['payload_json'], JSON_UNESCAPED_UNICODE) : $data['payload_json']) : null;
            $userAgent = substr(trim($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);

            // Anomização do IP conforme LGPD (SHA256)
            $ipRaw = $_SERVER['REMOTE_ADDR'] ?? '';
            $ipHash = !empty($ipRaw) ? hash('sha256', $ipRaw . '_isp_salt') : '';

            $stmt = $pdo->prepare("INSERT INTO marketing_conversions (
                event_id, event_name, campaign_id, campaign_slug, entity_type, entity_id, lead_id,
                page_url, referrer, utm_source, utm_medium, utm_campaign, utm_content, utm_term,
                status, payload_json, user_agent, ip_address_hash
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $eventId, $eventName, $campaignId, $campaignSlug, $entityType, $entityId, $leadId,
                $pageUrl, $referrer, $utmSource, $utmMedium, $utmCampaign, $utmContent, $utmTerm,
                $status, $payloadJson, $userAgent, $ipHash
            ]);

            return (int)$pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("MarketingTracker::recordConversion error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Renderiza o script dinâmico no cabeçalho das páginas públicas
     */
    public static function renderHeaderScript(PDO $pdo, array $context = []): string {
        $settings = self::getSettings($pdo);
        $pixelEnabled = (bool)$settings['meta_pixel_enabled'];
        $pixelId = !empty($context['pixel_id']) ? $context['pixel_id'] : $settings['meta_pixel_id'];
        $debugMode = (bool)$settings['debug_mode'];
        $testEventCode = trim($settings['meta_test_event_code'] ?? '');
        $autoEvents = (bool)$settings['meta_auto_events'];

        // Se pixel estiver desativado, injetamos apenas o helper de compatibilidade sem carregar o Pixel da Meta
        if (!$pixelEnabled || empty($pixelId)) {
            $jsPath = __DIR__ . '/../js/marketing-tracker.js';
            $v = file_exists($jsPath) ? filemtime($jsPath) : time();
            $debugJs = $debugMode ? 'true' : 'false';

            return "\n    <!-- Meta Pixel Desativado Administrativamente via Painel ISP -->\n" .
                   "    <script>\n" .
                   "        window.ispMarketingConfig = {\n" .
                   "            pixel_enabled: false,\n" .
                   "            debug_mode: {$debugJs}\n" .
                   "        };\n" .
                   "    </script>\n" .
                   "    <script src=\"/js/marketing-tracker.js?v={$v}\"></script>\n";
        }

        $safePixelId = htmlspecialchars($pixelId, ENT_QUOTES, 'UTF-8');
        $testCodeJs = !empty($testEventCode) ? "    fbq('set', 'testEventCode', '" . addslashes($testEventCode) . "');\n" : "";

        // Advanced Matching opcional (apenas se ativo e se usuário logado ou identificado)
        $advancedMatchingJs = "{}";
        if (!empty($settings['meta_advanced_matching']) && !empty($_SESSION['lead_email'])) {
            $adv = [];
            if (!empty($_SESSION['lead_email'])) $adv['em'] = hash('sha256', strtolower(trim($_SESSION['lead_email'])));
            if (!empty($_SESSION['lead_phone'])) $adv['ph'] = hash('sha256', preg_replace('/\D/', '', $_SESSION['lead_phone']));
            if (!empty($adv)) {
                $advancedMatchingJs = json_encode($adv);
            }
        }

        $jsPath = __DIR__ . '/../js/marketing-tracker.js';
        $v = file_exists($jsPath) ? filemtime($jsPath) : time();
        $debugJs = $debugMode ? 'true' : 'false';
        $safeTestCode = htmlspecialchars($testEventCode, ENT_QUOTES, 'UTF-8');

        $output = <<<HTML

    <!-- Meta Pixel Code Centralizado (ISP Marketing Tracker) -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{$safePixelId}', {$advancedMatchingJs});
{$testCodeJs}    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id={$safePixelId}&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->

    <!-- Configuração e Data Layer Centralizada ISP -->
    <script>
        window.ispMarketingConfig = {
            pixel_enabled: true,
            pixel_id: '{$safePixelId}',
            debug_mode: {$debugJs},
            test_event_code: '{$safeTestCode}'
        };
    </script>
    <script src="/js/marketing-tracker.js?v={$v}"></script>

HTML;

        return $output;
    }

    /**
     * Retorna estatísticas de conversão para o painel administrativo
     */
    public static function getConversionStats(PDO $pdo): array {
        $stats = [
            'total' => 0,
            'today' => 0,
            'leads_total' => 0,
            'leads_unique' => 0,
            'caxias_leads' => 0
        ];

        try {
            $stats['total'] = (int)$pdo->query("SELECT COUNT(*) FROM marketing_conversions")->fetchColumn();
            $stats['today'] = (int)$pdo->query("SELECT COUNT(*) FROM marketing_conversions WHERE DATE(created_at) = CURDATE()")->fetchColumn();
            $stats['leads_total'] = (int)$pdo->query("SELECT COUNT(*) FROM marketing_conversions WHERE event_name = 'Lead'")->fetchColumn();
            $stats['leads_unique'] = (int)$pdo->query("SELECT COUNT(DISTINCT lead_id) FROM marketing_conversions WHERE event_name = 'Lead' AND lead_id IS NOT NULL")->fetchColumn();
            $stats['caxias_leads'] = (int)$pdo->query("SELECT COUNT(*) FROM marketing_conversions WHERE campaign_slug LIKE '%caxias%' AND event_name = 'Lead'")->fetchColumn();
        } catch (Exception $e) {
            error_log("MarketingTracker::getConversionStats error: " . $e->getMessage());
        }

        return $stats;
    }
}
