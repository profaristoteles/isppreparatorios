<?php
require_once 'includes/pci_mcp_client.php';

header('Content-Type: application/json; charset=utf-8');

function safe_lower($str) {
    return function_exists('mb_strtolower') ? mb_strtolower($str, 'UTF-8') : strtolower($str);
}

$inputRaw = file_get_contents('php://input');
$inputData = json_decode($inputRaw, true) ?: $_POST;

$message = isset($inputData['message']) ? trim($inputData['message']) : '';
$uf = isset($inputData['uf']) ? strtoupper(trim($inputData['uf'])) : 'MA';

if (empty($message)) {
    echo json_encode([
        'success' => false,
        'reply' => 'Por favor, digite uma pergunta sobre o concurso ou cargo que deseja consultar.'
    ]);
    exit;
}

$msgLower = safe_lower($message);

// Identificar se especificou algum estado (UF) na mensagem
$ufsList = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
foreach ($ufsList as $sigla) {
    if (preg_match('/\b' . strtolower($sigla) . '\b/i', $message) || preg_match('/no ' . strtolower($sigla) . '\b/i', $message)) {
        $uf = $sigla;
        break;
    }
}

// Analisar intenção
$concursosData = [];
$intentType = 'pesquisa';

if (strpos($msgLower, 'são luís') !== false || strpos($msgLower, 'sao luis') !== false || strpos($msgLower, 'imperatriz') !== false || strpos($msgLower, 'caxias') !== false) {
    // Cidade específica
    $cidade = (strpos($msgLower, 'imperatriz') !== false) ? 'Imperatriz' : ((strpos($msgLower, 'caxias') !== false) ? 'Caxias' : 'São Luís');
    $mcpRes = PciMcpClient::buscarPorCidade($cidade);
    $concursosData = $mcpRes['data'] ?? [];
    $intentType = "cidade ($cidade)";
} elseif (strpos($msgLower, 'profess') !== false || strpos($msgLower, 'educaç') !== false || strpos($msgLower, 'pedagog') !== false) {
    // Professores / Educação
    $cargo = (strpos($msgLower, 'pedagog') !== false) ? 'pedagogo' : 'professor';
    $mcpRes = PciMcpClient::buscarPorCargo($cargo, $uf);
    $concursosData = $mcpRes['data'] ?? [];
    $intentType = "cargo de $cargo em $uf";
} else {
    // Pesquisa geral com palavras chaves extraídas
    $cleanQuery = preg_replace('/(quais|quais os|tem|concurso|concursos|aberto|abertos|para|de|em|no|na)\b/i', '', $message);
    $cleanQuery = trim(preg_replace('/\s+/', ' ', $cleanQuery));
    
    if (empty($cleanQuery)) {
        $cleanQuery = 'concurso';
    }

    $mcpRes = PciMcpClient::pesquisarConcursos($cleanQuery, $uf);
    $concursosData = $mcpRes['data'] ?? [];
    $intentType = "busca por '$cleanQuery' em $uf";
}

// Montar resposta amigável do assistente
if (empty($concursosData)) {
    $reply = "Não encontrei concursos com inscrições abertas para **$intentType** no momento no banco da **PCI Concursos**.\n\nVocê pode tentar pesquisar por outro estado ou cargo (ex: *Professor no MA*, *Concursos em SP*).";
} else {
    $total = count($concursosData);
    $reply = "Localizei **$total concurso(s)** com inscrições abertas via **PCI Concursos** para $intentType:\n\n";

    foreach (array_slice($concursosData, 0, 4) as $idx => $item) {
        $num = $idx + 1;
        $titulo = $item['titulo'] ?? 'Concurso';
        $vagas = $item['vagas_salario'] ?? 'Vagas não especificadas';
        $dias = $item['datas']['dias_restantes'] ?? null;
        $link = $item['noticia']['link'] ?? 'https://www.pciconcursos.com.br';

        $prazotxt = ($dias !== null) ? " ($dias dia(s) restante(s))" : "";

        $reply .= "$num. **$titulo**\n";
        $reply .= "   • **Vagas/Salário**: $vagas\n";
        if (!empty($item['cargos_resumo'])) {
            $reply .= "   • **Cargos**: {$item['cargos_resumo']}\n";
        }
        $reply .= "   • [Ver Edital Oficial no PCI Concursos]($link)\n\n";
    }

    $reply .= "💡 *Dica: Você pode se preparar para qualquer um destes concursos com a equipe do ISP Preparatórios!*";
}

echo json_encode([
    'success' => true,
    'reply' => $reply,
    'items' => $concursosData
], JSON_UNESCAPED_UNICODE);
