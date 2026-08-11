<?php
require_once 'db_config.php';
require_once 'includes/pci_mcp_client.php';

$uf = isset($_GET['uf']) ? strtoupper(trim($_GET['uf'])) : 'MA';
$allowed_ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

if (!in_array($uf, $allowed_ufs)) {
    $uf = 'MA';
}

$termo = isset($_GET['termo']) ? trim($_GET['termo']) : '';
$cidade = isset($_GET['cidade']) ? trim($_GET['cidade']) : '';
$professoresOnly = isset($_GET['professores']) ? (bool)$_GET['professores'] : false;

// Executar busca no PCI Concursos via MCP Client
if (!empty($cidade)) {
    $mcpResult = PciMcpClient::buscarPorCidade($cidade);
} elseif (!empty($termo)) {
    $mcpResult = PciMcpClient::pesquisarConcursos($termo, $uf);
} elseif ($professoresOnly) {
    $mcpResult = PciMcpClient::buscarPorCargo('professor', $uf);
} else {
    // Busca padrão de concursos em destaque no estado selecionado
    $mcpResult = PciMcpClient::buscarPorCargo('professor', $uf);
    if (empty($mcpResult['data'])) {
        $mcpResult = PciMcpClient::pesquisarConcursos('concurso', $uf);
    }
}

$concursos = $mcpResult['data'] ?? [];
$totalEncontrados = $mcpResult['meta']['total'] ?? count($concursos);

$dynamic_title = "Editais de Concursos em $uf - ISP Preparatórios";
$dynamic_desc = "Acompanhe os concursos públicos e seletivos abertos em $uf com dados oficiais da PCI Concursos, prazos, vagas e cargos.";
require_once 'includes/header.php';
?>

<!-- Estilos Customizados da Central de Editais PCI -->
<style>
.editais-hero {
    text-align: center;
    padding: 3rem 1rem 2rem;
}
.filter-card {
    background: rgba(13, 17, 38, 0.7);
    border: 1px solid var(--glass-border);
    border-radius: 16px;
    padding: 1.75rem;
    margin-bottom: 2.5rem;
    backdrop-filter: blur(12px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
}
.filter-grid {
    display: grid;
    grid-template-columns: 120px 1fr 1fr auto;
    gap: 1rem;
    align-items: center;
}
@media (max-width: 992px) {
    .filter-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 576px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }
}
.filter-input {
    background: rgba(2, 2, 40, 0.8);
    border: 1px solid rgba(0, 210, 255, 0.3);
    color: #fff;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    width: 100%;
    transition: border-color 0.3s, box-shadow 0.3s;
}
.filter-input:focus {
    outline: none;
    border-color: var(--brand-orange);
    box-shadow: 0 0 10px rgba(255, 128, 0, 0.4);
}
.concurso-card {
    background: rgba(10, 14, 35, 0.6);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    padding: 1.75rem;
    margin-bottom: 1.5rem;
    transition: transform 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
}
.concurso-card:hover {
    transform: translateY(-4px);
    border-color: var(--brand-orange);
    box-shadow: 0 12px 30px rgba(255, 128, 0, 0.15);
}
.concurso-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, var(--brand-orange), #00d2ff);
}
.badge-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.85rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.5px;
}
.badge-open {
    background: rgba(46, 204, 113, 0.15);
    color: #2ecc71;
    border: 1px solid rgba(46, 204, 113, 0.4);
}
.badge-urgent {
    background: rgba(231, 76, 60, 0.15);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.4);
}
.badge-uf {
    background: rgba(0, 210, 255, 0.15);
    color: #00d2ff;
    border: 1px solid rgba(0, 210, 255, 0.3);
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
    font-weight: 700;
    font-size: 0.8rem;
}
.cargos-pill-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 1rem 0;
}
.cargo-pill {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #ddd;
    font-size: 0.78rem;
    padding: 0.25rem 0.65rem;
    border-radius: 12px;
}
.loading-spinner {
    display: none;
    text-align: center;
    padding: 3rem;
}
.spinner-ring {
    width: 45px;
    height: 45px;
    border: 4px solid rgba(255, 128, 0, 0.2);
    border-top-color: var(--brand-orange);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto 1rem;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<div class="container section-padding" style="margin-top: 4rem;">
    
    <!-- Header -->
    <div class="editais-hero reveal">
        <span class="hero-pre-title">CENTRAL DE EDITAIS & CONCURSOS</span>
        <h1 style="font-weight: 800; margin-top: 0.5rem;">Concursos em <span style="color: var(--brand-orange);"><?= htmlspecialchars($uf) ?></span></h1>
        <p style="color: var(--text-secondary); max-width: 650px; margin: 0.75rem auto 0; font-size: 1.05rem;">
            Consultas oficiais atualizadas em tempo real via <strong>PCI Concursos</strong>.
            Fique por dentro das inscrições abertas, vagas, salários e prazos.
        </p>
    </div>

    <!-- Filtro de Busca Dinâmica -->
    <div class="filter-card reveal">
        <form method="GET" id="search-form" action="editais.php">
            <div class="filter-grid">
                
                <!-- Estado UF -->
                <div>
                    <label style="color: #aaa; font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 0.3rem;">ESTADO</label>
                    <select name="uf" class="filter-input" onchange="this.form.submit()">
                        <?php foreach($allowed_ufs as $sigla): ?>
                            <option value="<?= $sigla ?>" <?= $uf == $sigla ? 'selected' : '' ?>><?= $sigla ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Cargo / Palavra-chave -->
                <div>
                    <label style="color: #aaa; font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 0.3rem;">BUSCAR CARGO / ÓRGÃO</label>
                    <input type="text" name="termo" class="filter-input" placeholder="Ex: Professor, Pedagogo, IFMA..." value="<?= htmlspecialchars($termo) ?>">
                </div>

                <!-- Cidade -->
                <div>
                    <label style="color: #aaa; font-size: 0.8rem; font-weight: 600; display: block; margin-bottom: 0.3rem;">CIDADE</label>
                    <input type="text" name="cidade" class="filter-input" placeholder="Ex: São Luís, Imperatriz..." value="<?= htmlspecialchars($cidade) ?>">
                </div>

                <!-- Botão de Ação -->
                <div style="display: flex; gap: 0.5rem; align-items: flex-end; height: 100%;">
                    <button type="submit" class="btn" style="padding: 0.75rem 1.5rem; width: 100%; white-space: nowrap;">
                        <i class="fas fa-search" style="margin-right: 0.4rem;"></i> BUSCAR
                    </button>
                </div>

            </div>

            <!-- Opção de Filtro Educação -->
            <div style="margin-top: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1rem;">
                <label style="color: #ddd; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="professores" value="1" <?= $professoresOnly ? 'checked' : '' ?> onchange="this.form.submit()" style="accent-color: var(--brand-orange); transform: scale(1.2);">
                    <span>Exibir apenas <strong>Concursos da Educação / Professores</strong></span>
                </label>

                <span style="color: #888; font-size: 0.85rem;">
                    Encontrados: <strong style="color: var(--brand-orange);"><?= $totalEncontrados ?></strong> concurso(s)
                </span>
            </div>
        </form>
    </div>

    <!-- Indicador de Carregamento AJAX -->
    <div id="loading-indicator" class="loading-spinner">
        <div class="spinner-ring"></div>
        <p style="color: var(--brand-orange); font-weight: 600; letter-spacing: 1px;">CONSULTANDO PCI CONCURSOS...</p>
    </div>

    <!-- Lista de Concursos (Grid) -->
    <div id="editais-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 1.5rem;">
        
        <?php if(empty($concursos)): ?>
            <div style="grid-column: 1 / -1; background: rgba(255,255,255,0.02); padding: 3rem 2rem; border-radius: 16px; border: 1px solid var(--glass-border); text-align: center;">
                <i class="fas fa-search" style="font-size: 3rem; color: #555; margin-bottom: 1rem; display: block;"></i>
                <h3 style="color: #fff; margin-bottom: 0.5rem;">Nenhum edital encontrado</h3>
                <p style="color: var(--text-secondary); max-width: 500px; margin: 0 auto 1.5rem;">
                    Não encontramos concursos com os filtros selecionados para o estado de <strong><?= htmlspecialchars($uf) ?></strong>.
                </p>
                <a href="editais.php?uf=<?= $uf ?>" class="btn btn-outline" style="font-size: 0.85rem;">Limpar Filtros</a>
            </div>
        <?php else: ?>
            <?php foreach($concursos as $c): 
                $diasRestantes = $c['datas']['dias_restantes'] ?? null;
                $isUrgent = ($diasRestantes !== null && $diasRestantes <= 5);
                $noticiaLink = $c['noticia']['link'] ?? '#';
                $cargosList = $c['cargos'] ?? [];
                if (empty($cargosList) && !empty($c['cargos_resumo'])) {
                    $cargosList = explode(',', $c['cargos_resumo']);
                }
            ?>
                <div class="concurso-card reveal">
                    <div>
                        <!-- Header do Card -->
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.75rem;">
                            <span class="badge-uf"><?= htmlspecialchars($c['uf'] ?? $uf) ?></span>
                            <div>
                                <?php if($diasRestantes !== null): ?>
                                    <?php if($diasRestantes == 0): ?>
                                        <span class="badge-status badge-urgent">
                                            <i class="fas fa-exclamation-triangle"></i> Encerra Hoje!
                                        </span>
                                    <?php elseif($diasRestantes > 0): ?>
                                        <span class="badge-status <?= $isUrgent ? 'badge-urgent' : 'badge-open' ?>">
                                            <i class="fas fa-clock"></i> <?= $diasRestantes ?> dia<?= $diasRestantes > 1 ? 's' : '' ?> restante<?= $diasRestantes > 1 ? 's' : '' ?>
                                        </span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge-status badge-open">
                                        <i class="fas fa-check-circle"></i> Inscrições Abertas
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Título do Órgão -->
                        <h3 style="font-size: 1.2rem; color: #fff; margin-bottom: 0.75rem; line-height: 1.35; font-weight: 700;">
                            <?= htmlspecialchars($c['titulo'] ?? 'Concurso Público') ?>
                        </h3>

                        <!-- Informações de Vagas / Salário -->
                        <?php if(!empty($c['vagas_salario'])): ?>
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--brand-orange); font-size: 0.95rem; font-weight: 600; margin-bottom: 0.75rem;">
                                <i class="fas fa-coins"></i>
                                <span><?= htmlspecialchars($c['vagas_salario']) ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Formação requerida -->
                        <?php if(!empty($c['formacao'])): ?>
                            <div style="color: #aaa; font-size: 0.85rem; margin-bottom: 0.75rem;">
                                <i class="fas fa-graduation-cap" style="color: var(--prism-cyan); margin-right: 0.3rem;"></i>
                                <span>Escolaridade: <?= htmlspecialchars($c['formacao']) ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Pílulas de Cargos -->
                        <?php if(!empty($cargosList)): ?>
                            <div class="cargos-pill-list">
                                <?php 
                                $limitCargos = array_slice($cargosList, 0, 4);
                                foreach($limitCargos as $cg): 
                                ?>
                                    <span class="cargo-pill"><?= htmlspecialchars(trim($cg)) ?></span>
                                <?php endforeach; ?>
                                <?php if(count($cargosList) > 4): ?>
                                    <span class="cargo-pill" style="color: var(--brand-orange); border-color: rgba(255,128,0,0.3);">
                                        +<?= count($cargosList) - 4 ?> cargos
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Botões de Ação do Card -->
                    <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem; align-items: center;">
                        <a href="<?= htmlspecialchars($noticiaLink) ?>" target="_blank" rel="noopener" class="btn btn-outline" style="flex: 1; font-size: 0.8rem; padding: 0.65rem 0.5rem; text-align: center;">
                            <i class="fas fa-external-link-alt" style="margin-right: 0.3rem;"></i> VER EDITAL
                        </a>
                        <a href="cursos.php" class="btn" style="flex: 1; font-size: 0.8rem; padding: 0.65rem 0.5rem; text-align: center; background: linear-gradient(135deg, var(--brand-orange), #ff6600);">
                            PREPARAR-SE <i class="fas fa-arrow-right" style="margin-left: 0.3rem;"></i>
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <!-- Banner Promocional ISP Preparatórios -->
    <div class="reveal" style="margin-top: 4rem; text-align: center; background: linear-gradient(135deg, rgba(3, 4, 94, 0.6), rgba(10, 14, 35, 0.9)); border: 1px solid var(--prism-cyan); padding: 2.5rem 2rem; border-radius: 16px; box-shadow: 0 10px 30px rgba(0, 210, 255, 0.1);">
        <h2 style="color: #fff; margin-bottom: 0.75rem; font-size: 1.6rem;">Vai prestar algum destes concursos?</h2>
        <p style="color: var(--text-secondary); max-width: 600px; margin: 0 auto 1.5rem; font-size: 1.05rem;">
            O ISP Preparatórios é líder em aprovação para carreiras educacionais e públicas. Estude com professores especialistas e material focado na banca!
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="cursos.php" class="btn" style="padding: 0.85rem 2rem;">Ver Nossos Cursos</a>
            <a href="https://wa.me/5598988000000" target="_blank" class="btn btn-outline" style="padding: 0.85rem 2rem; border-color: #2ecc71; color: #2ecc71;">
                <i class="fab fa-whatsapp" style="margin-right: 0.4rem;"></i> Falar no WhatsApp
            </a>
        </div>
    </div>

    <p style="text-align: center; color: #666; font-size: 0.78rem; margin-top: 2.5rem;">
        Dados integrados diretamente via servidor <strong>PCI Concursos (MCP API)</strong>. Atualizado em tempo real.
    </p>

</div>

<?php require_once 'includes/footer.php'; ?>
