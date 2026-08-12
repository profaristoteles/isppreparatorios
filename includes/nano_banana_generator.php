<?php
/**
 * NanoBananaGenerator - Geração de Imagens no Estilo Nano Banana 3D / Flux por IA
 */
class NanoBananaGenerator {
    /**
     * Gera uma imagem por IA no estilo Nano Banana 3D e salva no diretório /uploads
     * 
     * @param string $prompt Descrição da imagem desejada
     * @param PDO|null $pdo Conexão com o banco de dados (para obter chave da OpenAI se houver)
     * @param int $width Largura desejada em px
     * @param int $height Altura desejada em px
     * @return string|false Nome do arquivo salvo (ex: ai_nano_123.jpg) ou false se falhar
     */
    public static function generateImage($prompt, $pdo = null, $width = 1024, $height = 600) {
        if (empty(trim($prompt))) {
            return false;
        }

        // Decorar prompt com as diretrizes visuais do estilo Nano Banana 3D
        $nanoStyle = "nano banana 3d illustration style, 3d isometric clay visual, glossy colorful textures, vibrant studio lighting, soft shadows, clean digital render, high detail, ";
        $fullPrompt = $nanoStyle . trim($prompt);

        $openai_key = '';
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT ai_openai_key FROM configuracoes LIMIT 1");
                $config = $stmt ? $stmt->fetch() : null;
                $openai_key = $config['ai_openai_key'] ?? '';
            } catch (Exception $e) {}
        }

        $imageData = null;

        // 1. Tentar OpenAI DALL-E 3 se a chave estiver configurada
        if (!empty($openai_key)) {
            try {
                $url = 'https://api.openai.com/v1/images/generations';
                $payload = json_encode([
                    "model" => "dall-e-3",
                    "prompt" => $fullPrompt,
                    "n" => 1,
                    "size" => "1024x1024"
                ]);

                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        "Authorization: Bearer $openai_key"
                    ],
                    CURLOPT_TIMEOUT => 60,
                    CURLOPT_CONNECTTIMEOUT => 15,
                    CURLOPT_SSL_VERIFYPEER => false
                ]);
                $res = curl_exec($ch);
                curl_close($ch);

                if ($res) {
                    $json = json_decode($res, true);
                    $imgUrl = $json['data'][0]['url'] ?? '';
                    if ($imgUrl) {
                        $imageData = @file_get_contents($imgUrl);
                    }
                }
            } catch (Exception $e) {}
        }

        // 2. Fallback de Alta Performance: Pollinations AI (Modelo Flux / Nano Banana 3D)
        if (!$imageData) {
            $seed = rand(10000, 999999);
            $encodedPrompt = urlencode($fullPrompt);
            $pollUrl = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width={$width}&height={$height}&model=flux&nologo=true&seed={$seed}";

            $ch = curl_init($pollUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/122.0.0.0'
            ]);
            $imageData = curl_exec($ch);
            curl_close($ch);
        }

        // Validar se o resultado é uma imagem real
        if (!$imageData || strlen($imageData) < 2000) {
            return false;
        }

        // Determinar o diretório de destino
        $targetDir = __DIR__ . '/../uploads/';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $filename = 'ai_nano_' . uniqid() . '.jpg';
        $filepath = $targetDir . $filename;

        if (file_put_contents($filepath, $imageData) !== false) {
            return $filename;
        }

        return false;
    }
}
