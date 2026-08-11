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

// Extrair estado (UF) mencionado na mensagem se houver
$ufsList = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
foreach ($ufsList as $sigla) {
    if (preg_match('/\b' . strtolower($sigla) . '\b/i', $message) || preg_match('/no ' . strtolower($sigla) . '\b/i', $message)) {
        $uf = $sigla;
        break;
    }
}

// 1. Obter dados relevantes via PCI MCP
$concursosData = [];
$cidade = '';
$isCidade = false;

if (strpos($msgLower, 'são luís') !== false || strpos($msgLower, 'sao luis') !== false) {
    $cidade = 'São Luís';
    $isCidade = true;
} elseif (strpos($msgLower, 'imperatriz') !== false) {
    $cidade = 'Imperatriz';
    $isCidade = true;
} elseif (strpos($msgLower, 'caxias') !== false) {
    $cidade = 'Caxias';
    $isCidade = true;
}

if ($isCidade) {
    $mcpRes = PciMcpClient::buscarPorCidade($cidade);
    $concursosData = $mcpRes['data'] ?? [];
} else {
    // Limpar termos de parada para buscar o órgão ou cargo exato mencionado pelo usuário
    $queryTerm = preg_replace('/(quais|quais os|quais as|qual|tem|concurso|concursos|aberto|abertos|para|de|em|no|na|que|dia|é|a|prova|edital|quando|quanto|salario|vagas|inscrição|inscrições|requisitos)\b/i', '', $message);
    $queryTerm = trim(preg_replace('/\s+/', ' ', $queryTerm));

    if (!empty($queryTerm) && strlen($queryTerm) >= 3) {
        $mcpRes = PciMcpClient::pesquisarConcursos($queryTerm, $uf);
        $concursosData = $mcpRes['data'] ?? [];
    }

    if (empty($concursosData)) {
        if (strpos($msgLower, 'profess') !== false || strpos($msgLower, 'educaç') !== false || strpos($msgLower, 'pedagog') !== false) {
            $mcpRes = PciMcpClient::buscarPorCargo('professor', $uf);
        } else {
            $mcpRes = PciMcpClient::pesquisarConcursos($queryTerm ?: 'concurso', $uf);
        }
        $concursosData = $mcpRes['data'] ?? [];
    }
}

// 2. Analisar o tipo de pergunta do usuário para resposta inteligente
$isPerguntaData = (strpos($msgLower, 'prova') !== false || strpos($msgLower, 'quando') !== false || strpos($msgLower, 'data') !== false || strpos($msgLower, 'dia') !== false || strpos($msgLower, 'prazo') !== false || strpos($msgLower, 'inscriç') !== false);
$isPerguntaSalario = (strpos($msgLower, 'salário') !== false || strpos($msgLower, 'salario') !== false || strpos($msgLower, 'quanto ganha') !== false || strpos($msgLower, 'remuneraç') !== false || strpos($msgLower, 'vagas') !== false);
$isPerguntaCargos = (strpos($msgLower, 'cargos') !== false || strpos($msgLower, 'vaga') !== false || strpos($msgLower, 'funç') !== false || strpos($msgLower, 'quais os cargos') !== false);

// 3. Gerar a resposta personalizada
if (empty($concursosData)) {
    $reply = "Não encontrei concursos abertos no banco da **PCI Concursos** para a sua busca em **$uf**.\n\nVocê pode consultar outros estados ou tentar buscar por termos como *'Professor'*, *'São Luís'*, ou *'Prefeitura'*.";
} else {
    $itemPrincipal = $concursosData[0];
    $titulo = $itemPrincipal['titulo'] ?? 'Concurso Público';
    $vagasSalario = $itemPrincipal['vagas_salario'] ?? 'Consultar edital';
    $formacao = $itemPrincipal['formacao'] ?? 'Diversos níveis';
    $dias = $itemPrincipal['datas']['dias_restantes'] ?? null;
    $fimInscricoes = $itemPrincipal['datas']['fim'] ?? null;
    $link = $itemPrincipal['noticia']['link'] ?? 'https://www.pciconcursos.com.br';
    $cargos = $itemPrincipal['cargos'] ?? [];

    if ($isPerguntaData) {
        $reply = "📅 **Datas e Prazos — $titulo**\n\n";
        if ($fimInscricoes) {
            $dateFormatted = date('d/m/Y', strtotime($fimInscricoes));
            $reply .= "• **Encerramento das Inscrições**: **$dateFormatted**";
            if ($dias !== null) {
                if ($dias == 0) {
                    $reply .= " ⚠️ *(Último dia de inscrições!)*\n";
                } else {
                    $reply .= " *($dias dia(s) restante(s))*\n";
                }
            } else {
                $reply .= "\n";
            }
        } else {
            $reply .= "• **Status**: Inscrições Abertas\n";
        }
        $reply .= "• **Data da Prova Objetiva**: O cronograma detalhado das provas objetivas e de títulos é publicado diretamente no edital do órgão.\n\n";
        $reply .= "👉 [Clique aqui para abrir o Edital Completo na PCI Concursos]($link)\n\n";
        $reply .= "💡 *Se prepare com o ISP Preparatórios para gabaritar esta prova!*";
    } elseif ($isPerguntaSalario) {
        $reply = "💰 **Vagas e Remuneração — $titulo**\n\n";
        $reply .= "• **Oportunidades**: **$vagasSalario**\n";
        $reply .= "• **Escolaridade Exigida**: $formacao\n\n";
        if (!empty($itemPrincipal['cargos_resumo'])) {
            $reply .= "• **Resumo de Cargos**: {$itemPrincipal['cargos_resumo']}\n\n";
        }
        $reply .= "👉 [Ver Detalhes do Edital no PCI Concursos]($link)";
    } elseif ($isPerguntaCargos && !empty($cargos)) {
        $reply = "📋 **Lista de Cargos Oferecidos — $titulo**\n\n";
        $reply .= "O edital oferece vagas para os seguintes cargos:\n";
        foreach (array_slice($cargos, 0, 8) as $cg) {
            $reply .= "• " . trim($cg) . "\n";
        }
        if (count($cargos) > 8) {
            $diff = count($cargos) - 8;
            $reply .= "• *...e mais $diff cargo(s).*\n";
        }
        $reply .= "\n• **Vagas/Salário**: $vagasSalario\n";
        $reply .= "👉 [Ver Edital Oficial no PCI Concursos]($link)";
    } else {
        // Resposta geral estruturada e limpa
        $total = count($concursosData);
        $reply = "Localizei **$total concurso(s)** com inscrições abertas no PCI Concursos para **$titulo**:\n\n";

        foreach (array_slice($concursosData, 0, 3) as $idx => $c) {
            $t = $c['titulo'] ?? 'Concurso';
            $v = $c['vagas_salario'] ?? 'Consulte o edital';
            $d = $c['datas']['dias_restantes'] ?? null;
            $l = $c['noticia']['link'] ?? 'https://www.pciconcursos.com.br';
            $prazotxt = ($d !== null) ? " ($d dia(s) restante(s))" : "";

            $reply .= "🔹 **$t**\n";
            $reply .= "   • **Vagas & Salário**: $v\n";
            if (!empty($c['cargos_resumo'])) {
                $reply .= "   • **Cargos**: {$c['cargos_resumo']}\n";
            }
            $reply .= "   • [Ver Notícia/Edital Oficial]($l)\n\n";
        }

        $reply .= "💡 *Dica: Fale com a equipe do ISP Preparatórios para adquirir os melhores materiais preparatórios para este concurso!*";
    }
}

echo json_encode([
    'success' => true,
    'reply' => $reply,
    'items' => $concursosData
], JSON_UNESCAPED_UNICODE);
