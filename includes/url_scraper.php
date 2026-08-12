<?php
/**
 * UrlScraper - Extrator de Conteúdo de Páginas Web / Links para o Agente IA
 */
class UrlScraper {
    /**
     * Baixa o HTML de uma URL, remove elementos irrelevantes (nav, scripts, ads)
     * e retorna o texto limpo com o título da matéria.
     */
    public static function extractTextFromUrl($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'error' => 'URL informada é inválida. Certifique-se de incluir http:// ou https://'];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36'
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($html === false || $httpCode >= 400) {
            return ['success' => false, 'error' => "Não foi possível acessar o link (HTTP $httpCode): " . ($curlError ?: 'Resposta inválida do servidor de destino.')];
        }

        // Tentar extrair o título da página
        $title = '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $mTitle)) {
            $title = trim(html_entity_decode(strip_tags($mTitle[1]), ENT_QUOTES, 'UTF-8'));
        }

        // Tentar extrair og:title se title for genérico
        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/is', $html, $mOgTitle)) {
            $title = trim(html_entity_decode($mOgTitle[1], ENT_QUOTES, 'UTF-8'));
        }

        // Remover scripts, estilos, navegação, rodapé, cabeçalhos e anúncios
        $cleanHtml = preg_replace([
            '/<script\b[^>]*>(.*?)<\/script>/is',
            '/<style\b[^>]*>(.*?)<\/style>/is',
            '/<header\b[^>]*>(.*?)<\/header>/is',
            '/<footer\b[^>]*>(.*?)<\/footer>/is',
            '/<nav\b[^>]*>(.*?)<\/nav>/is',
            '/<aside\b[^>]*>(.*?)<\/aside>/is',
            '/<!--(.*?)-->/is'
        ], ' ', $html);

        // Converter quebras de linha de bloco
        $cleanHtml = preg_replace('/<(p|h1|h2|h3|h4|h5|h6|li|tr|div|br)[^>]*>/i', "\n$0", $cleanHtml);
        $text = strip_tags($cleanHtml);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Limpar múltiplos espaços em branco e linhas vazias
        $lines = array_map('trim', explode("\n", $text));
        $validLines = [];
        foreach ($lines as $line) {
            if (strlen($line) > 15 && !preg_match('/^(cookie|politica|direitos|menu|login|cadastre|compartilhe|facebook|instagram|whatsapp)/i', $line)) {
                $validLines[] = $line;
            }
        }

        $cleanText = implode("\n", array_slice($validLines, 0, 350));

        if (mb_strlen($cleanText) < 80) {
            return ['success' => false, 'error' => 'Não foi possível extrair o texto principal do link. O site pode estar protegido por captcha ou JavaScript estático.'];
        }

        if (mb_strlen($cleanText) > 12000) {
            $cleanText = mb_substr($cleanText, 0, 12000) . "\n...[conteúdo resumido para o agente]";
        }

        return [
            'success' => true,
            'url' => $url,
            'title' => $title,
            'content' => $cleanText
        ];
    }
}
