<?php
/**
 * Adapter de Integração com a API do EvoCRM & Evolution API - ISP Preparatórios
 * 
 * Suporta configuração via Banco de Dados (Painel Admin) ou Variáveis de Ambiente (.env / Docker).
 * Desacoplado da requisição do usuário e processado de forma transparente pela Fila de Integrações.
 */

class EvoCRMService {

    /**
     * Obtém as configurações consolidadas (Banco de dados com fallback para variáveis de ambiente)
     */
    public static function getConfig($pdo = null) {
        if (!$pdo) {
            global $pdo;
        }

        $dbConfig = null;
        if ($pdo) {
            try {
                if (function_exists('get_config')) {
                    $dbConfig = get_config($pdo);
                }
            } catch (\Exception $e) {}
        }

        $dbEnabled  = !empty($dbConfig['evocrm_enabled']);
        $envEnabled = filter_var(getenv('EVOCRM_ENABLED') ?: false, FILTER_VALIDATE_BOOLEAN);
        $enabled    = $dbEnabled || $envEnabled;

        $provider = !empty($dbConfig['evocrm_provider']) ? $dbConfig['evocrm_provider'] : (getenv('EVOCRM_PROVIDER') ?: 'evolution');
        $apiUrl   = rtrim(!empty($dbConfig['evocrm_api_url']) ? $dbConfig['evocrm_api_url'] : (getenv('EVOCRM_API_URL') ?: ''), '/');
        $apiKey   = !empty($dbConfig['evocrm_api_key']) ? $dbConfig['evocrm_api_key'] : (getenv('EVOCRM_API_KEY') ?: '');
        $instance = trim(!empty($dbConfig['evocrm_instance']) ? $dbConfig['evocrm_instance'] : (getenv('EVOCRM_INSTANCE') ?: ''));

        // Fallback inteligente: se usar Evolution API e não tiver URL/Key específica de CRM, reaproveita a das Notificações
        if ($provider === 'evolution') {
            if (empty($apiUrl) && !empty($dbConfig['whatsapp_api_url'])) {
                $apiUrl = rtrim($dbConfig['whatsapp_api_url'], '/');
            }
            if (empty($apiKey) && !empty($dbConfig['whatsapp_api_key'])) {
                $apiKey = $dbConfig['whatsapp_api_key'];
            }
            if (empty($instance) && !empty($dbConfig['whatsapp_api_instance'])) {
                $instance = $dbConfig['whatsapp_api_instance'];
            }
        }

        return [
            'enabled'  => $enabled,
            'provider' => $provider,
            'apiUrl'   => $apiUrl,
            'apiKey'   => $apiKey,
            'instance' => $instance
        ];
    }

    /**
     * Envia ou atualiza um contato no EvoCRM ou na Evolution API.
     * 
     * @param array $payload ['name', 'email', 'phone', 'tags', 'campaign_code', 'lead_id']
     * @param PDO|null $pdo
     * @return array ['success' => bool, 'status' => string, 'message' => string, 'response' => mixed]
     */
    public static function upsertContact($payload, $pdo = null) {
        $cfg = self::getConfig($pdo);

        if (!$cfg['enabled'] || empty($cfg['apiUrl']) || empty($cfg['apiKey'])) {
            return [
                'success' => true,
                'status'  => 'disabled',
                'message' => 'Integração com CRM / Evolution API inativa ou aguardando credenciais.'
            ];
        }

        if ($cfg['provider'] === 'evolution') {
            return self::sendToEvolutionApi($cfg, $payload);
        }

        return self::sendToEvoCrmGeneric($cfg, $payload);
    }

    /**
     * Sincroniza contato com a Evolution API
     */
    private static function sendToEvolutionApi($cfg, $payload) {
        $apiUrl   = $cfg['apiUrl'];
        $apiKey   = $cfg['apiKey'];
        $instance = $cfg['instance'] ?: 'isp';

        $rawPhone = $payload['phone'] ?? '';
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        if (strlen($cleanPhone) === 10 || strlen($cleanPhone) === 11) {
            $cleanPhone = '55' . $cleanPhone;
        }

        if (empty($cleanPhone)) {
            return [
                'success' => false,
                'status'  => 'error',
                'message' => 'Número de telefone inválido para envio à Evolution API.'
            ];
        }

        $name = trim($payload['name'] ?? 'Aluno ISP');

        // 1. Tenta criar/atualizar o contato na Evolution API (/contact/create/{instance})
        $endpointContact = $apiUrl . '/contact/create/' . urlencode($instance);
        $dataContact = [
            'number' => $cleanPhone,
            'name'   => $name
        ];

        $ch = curl_init($endpointContact);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($dataContact),
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'apikey: ' . $apiKey,
                'Accept: application/json'
            ]
        ]);
        $responseContact = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // 2. Se /contact/create não for suportado pela versão (ex: 404), usa /chat/whatsappNumbers/{instance} que é universal
        if ($httpCode === 404 || ($httpCode >= 400 && $httpCode !== 401 && $httpCode !== 403)) {
            $endpointCheck = $apiUrl . '/chat/whatsappNumbers/' . urlencode($instance);
            $ch2 = curl_init($endpointCheck);
            curl_setopt_array($ch2, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode(['numbers' => [$cleanPhone]]),
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'apikey: ' . $apiKey,
                    'Accept: application/json'
                ]
            ]);
            $responseCheck = curl_exec($ch2);
            $httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch2);
            curl_close($ch2);
            $responseContact = $responseCheck;
        }

        if ($curlError) {
            return [
                'success' => false,
                'status'  => 'error',
                'message' => 'Erro de conexão com Evolution API: ' . $curlError
            ];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($responseContact, true);
            return [
                'success'  => true,
                'status'   => 'synced',
                'message'  => "Contato {$cleanPhone} sincronizado com sucesso na Evolution API (Instância: {$instance}).",
                'response' => $responseData
            ];
        }

        return [
            'success'  => false,
            'status'   => 'error',
            'message'  => "Evolution API retornou HTTP {$httpCode}: " . substr($responseContact, 0, 300),
            'response' => $responseContact
        ];
    }

    /**
     * Envia para endpoint REST padrão / Generic EvoCRM (/contacts/upsert)
     */
    private static function sendToEvoCrmGeneric($cfg, $payload) {
        $endpoint = $cfg['apiUrl'] . '/contacts/upsert';
        
        $data = [
            'name'          => $payload['name'] ?? '',
            'email'         => $payload['email'] ?? '',
            'phone'         => $payload['phone'] ?? '',
            'tags'          => $payload['tags'] ?? [],
            'campaign_code' => $payload['campaign_code'] ?? '',
            'custom_fields' => array_merge([
                'source'  => $payload['source'] ?? 'Aulas Gratuitas',
                'lead_id' => $payload['lead_id'] ?? ''
            ], is_array($payload['custom_fields'] ?? null) ? $payload['custom_fields'] : [])
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $cfg['apiKey'],
                'Accept: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'message' => 'Erro de conexão cURL com EvoCRM: ' . $error,
                'response' => null
            ];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            return [
                'success'  => true,
                'status'   => 'synced',
                'message'  => 'Contato sincronizado com sucesso com EvoCRM.',
                'response' => $responseData
            ];
        }

        return [
            'success'  => false,
            'message'  => "EvoCRM retornou HTTP $httpCode: " . substr($response, 0, 300),
            'response' => $response
        ];
    }

    /**
     * Testa a conexão em tempo real com o CRM / Evolution API
     */
    public static function testConnection($customConfig = null, $pdo = null) {
        $cfg = $customConfig ?: self::getConfig($pdo);

        if (empty($cfg['apiUrl'])) {
            return ['success' => false, 'message' => 'Informe a URL da API para realizar o teste.'];
        }
        if (empty($cfg['apiKey'])) {
            return ['success' => false, 'message' => 'Informe a Chave de API (Token) para realizar o teste.'];
        }

        $provider = $cfg['provider'] ?? 'evolution';
        $apiUrl   = rtrim($cfg['apiUrl'], '/');
        $apiKey   = $cfg['apiKey'];
        $instance = trim($cfg['instance'] ?? 'isp');

        if ($provider === 'evolution') {
            $testEndpoint = $apiUrl . '/instance/connectionState/' . urlencode($instance);
            $ch = curl_init($testEndpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_HTTPHEADER     => [
                    'apikey: ' . $apiKey,
                    'Accept: application/json'
                ]
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return ['success' => false, 'message' => 'Falha ao conectar na Evolution API: ' . $error];
            }

            if ($httpCode === 200) {
                $json = json_decode($response, true);
                $state = $json['instance']['state'] ?? ($json['state'] ?? 'conectado');
                return [
                    'success' => true,
                    'message' => "🟢 Conexão com Evolution API realizada com sucesso! Instância: '{$instance}', Estado do WhatsApp: '{$state}'."
                ];
            } elseif ($httpCode === 401 || $httpCode === 403) {
                return ['success' => false, 'message' => 'Erro 401/403: API Key inválida ou não autorizada na Evolution API.'];
            } elseif ($httpCode === 404) {
                // Tenta buscar lista geral de instâncias para verificar se a API Key está correta
                $ch2 = curl_init($apiUrl . '/instance/fetchInstances');
                curl_setopt_array($ch2, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 8,
                    CURLOPT_HTTPHEADER     => ['apikey: ' . $apiKey]
                ]);
                $resp2 = curl_exec($ch2);
                $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                curl_close($ch2);

                if ($code2 === 200) {
                    return [
                        'success' => true,
                        'message' => "🟡 Conectado à Evolution API! Porém a instância '{$instance}' ainda não foi criada. Crie a instância no painel da sua Evolution API e escaneie o QR Code."
                    ];
                }
                return ['success' => false, 'message' => "Instância '{$instance}' não encontrada na Evolution API (HTTP 404)."];
            }

            return ['success' => false, 'message' => "Evolution API respondeu HTTP {$httpCode}: " . substr($response, 0, 200)];
        }

        // Teste Genérico / EvoCRM
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Accept: application/json'
            ]
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => 'Falha ao conectar à URL do CRM: ' . $error];
        }

        if ($httpCode >= 200 && $httpCode < 400) {
            return ['success' => true, 'message' => "🟢 Conexão com a API do CRM bem-sucedida (HTTP {$httpCode})!"];
        }

        return ['success' => false, 'message' => "CRM retornou HTTP {$httpCode}: " . substr($response, 0, 200)];
    }
}
