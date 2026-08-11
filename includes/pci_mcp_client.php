<?php
/**
 * PciMcpClient - Cliente de Integração com o servidor MCP da PCI Concursos
 * URL Oficial: https://mcp.pciconcursos.com.br/mcp
 */
class PciMcpClient {
    private static $mcpUrl = 'https://mcp.pciconcursos.com.br/mcp';
    private static $cacheDir = __DIR__ . '/../uploads/cache_editais/';
    private static $cacheLifetime = 900; // 15 minutos em segundos

    /**
     * Executa uma tool no servidor MCP do PCI Concursos com suporte a cache local
     */
    public static function callTool($toolName, $arguments = []) {
        // Assegurar diretório de cache
        if (!file_exists(self::$cacheDir)) {
            @mkdir(self::$cacheDir, 0777, true);
        }

        $cacheKey = md5($toolName . '_' . json_encode($arguments));
        $cacheFile = self::$cacheDir . 'mcp_' . $cacheKey . '.json';

        // Retornar cache válido se disponível
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < self::$cacheLifetime) {
            $cachedContent = @file_get_contents($cacheFile);
            if ($cachedContent) {
                $decoded = json_decode($cachedContent, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        // Montar Payload JSON-RPC 2.0
        $payload = json_encode([
            'jsonrpc' => '2.0',
            'id' => rand(100, 99999),
            'method' => 'tools/call',
            'params' => [
                'name' => $toolName,
                'arguments' => $arguments
            ]
        ]);

        $ch = curl_init(self::$mcpUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) ISP-Preparatorios/1.0'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 12
        ]);

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        
        if ($curlError || empty($rawResponse)) {
            // Em caso de falha de rede, tentar usar cache expirado se existir
            if (file_exists($cacheFile)) {
                $expiredContent = @file_get_contents($cacheFile);
                if ($expiredContent) {
                    return json_decode($expiredContent, true);
                }
            }
            return ['meta' => ['total' => 0], 'data' => [], 'errors' => [$curlError ?: 'Sem resposta da API PCI']];
        }

        $rpcResponse = json_decode($rawResponse, true);
        $resultData = ['meta' => ['total' => 0], 'data' => [], 'errors' => []];

        if (isset($rpcResponse['result']['content'][0]['text'])) {
            $parsedText = json_decode($rpcResponse['result']['content'][0]['text'], true);
            if (is_array($parsedText)) {
                $resultData = $parsedText;
                // Salvar no cache local
                @file_put_contents($cacheFile, json_encode($resultData));
            }
        }

        return $resultData;
    }

    /**
     * Busca concursos por cargo (ex: 'professor', 'pedagogo', 'advogado')
     */
    public static function buscarPorCargo($cargo, $uf = 'MA') {
        return self::callTool('buscar_por_cargo', [
            'cargo' => $cargo,
            'uf' => strtoupper($uf)
        ]);
    }

    /**
     * Busca concursos em uma cidade específica
     */
    public static function buscarPorCidade($cidade) {
        return self::callTool('buscar_por_cidade', [
            'cidade' => $cidade
        ]);
    }

    /**
     * Lista concursos abertos filtrados por estado/região ou categoria professores
     */
    public static function listarConcursos($uf = 'MA', $professoresOnly = false) {
        $uf = strtoupper($uf);

        // Se marcou apenas professores, tentar buscar por cargo 'professor' para o estado
        if ($professoresOnly) {
            $res = self::buscarPorCargo('professor', $uf);
            if (!empty($res['data'])) {
                return $res;
            }
        }

        // Pesquisa geral ou fallback
        $resPesquisa = self::callTool('pesquisar_concursos', [
            'termo' => 'concurso',
            'uf' => $uf
        ]);

        if (!empty($resPesquisa['data'])) {
            return $resPesquisa;
        }

        // Se pesquisa geral por estado for vazia, tentar listar concursos por cargo genérico
        return self::buscarPorCargo('', $uf);
    }

    /**
     * Pesquisa geral livre em título, órgão ou cargo
     */
    public static function pesquisarConcursos($termo, $uf = 'MA') {
        return self::callTool('pesquisar_concursos', [
            'termo' => $termo,
            'uf' => strtoupper($uf)
        ]);
    }
}
