<?php
/**
 * Adapter de Integração com a API do EvoCRM
 * 
 * Desacoplado da requisição do usuário. Processado de forma transparente pelo Worker CLI.
 */

class EvoCRMService {

    /**
     * Envia ou atualiza um contato no EvoCRM com tags e metadados.
     * 
     * @param array $payload ['name', 'email', 'phone', 'tags', 'campaign_code', 'lead_id']
     * @return array ['success' => bool, 'message' => string, 'response' => mixed]
     */
    public static function upsertContact($payload) {
        $enabled = filter_var(getenv('EVOCRM_ENABLED') ?: false, FILTER_VALIDATE_BOOLEAN);
        $apiUrl  = rtrim(getenv('EVOCRM_API_URL') ?: '', '/');
        $apiKey  = getenv('EVOCRM_API_KEY') ?: '';

        if (!$enabled || empty($apiUrl) || empty($apiKey)) {
            return [
                'success' => true,
                'status'  => 'disabled',
                'message' => 'Integração EvoCRM inativa no ambiente ou aguardando credenciais.'
            ];
        }

        $endpoint = $apiUrl . '/contacts/upsert';
        
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
                'Authorization: Bearer ' . $apiKey,
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
}
