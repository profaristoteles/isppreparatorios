<?php
require_once 'db_config.php';

$uf = isset($_GET['uf']) ? strtoupper($_GET['uf']) : 'MA';
$allowed_ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

if (!in_array($uf, $allowed_ufs)) {
    $uf = 'MA';
}

$url = "https://concursosnobrasil.com/concursos/" . strtolower($uf) . "/";

// Simple scraper using file_get_contents and DOMDocument
$options = [
    'http' => [
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
    ]
];
$context = stream_context_create($options);
$html = @file_get_contents($url, false, $context);

$concursos = [];
if ($html) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    $xpath = new DOMXPath($dom);

    // Look for tr elements inside the table with class 'list-concursos' or similar
    // Based on the site structure, they are often inside a table or div
    $rows = $xpath->query("//table[contains(@class, 'list-concursos')]//tr | //div[contains(@class, 'list-concursos')]//tr");
    
    if ($rows->length == 0) {
        // Alternative query if table class is different
        $rows = $xpath->query("//tr[td/a]");
    }

    foreach ($rows as $row) {
        $cols = $row->getElementsByTagName('td');
        if ($cols->length >= 2) {
            $link_node = $cols->item(0)->getElementsByTagName('a')->item(0);
            if ($link_node) {
                $name = trim($link_node->nodeValue);
                $link = $link_node->getAttribute('href');
                $vagas = trim($cols->item(1)->nodeValue);
                
                // Determine if it's planned (previsto)
                $is_previsto = false;
                $spans = $cols->item(0)->getElementsByTagName('span');
                foreach ($spans as $span) {
                    if (strpos(strtolower($span->getAttribute('class')), 'previsto') !== false || strpos(strtolower($span->nodeValue), 'previsto') !== false) {
                        $is_previsto = true;
                        break;
                    }
                }
                
                // Some sites put "previsto" in the text
                if (strpos(strtolower($name), 'previsto') !== false) {
                    $is_previsto = true;
                }

                $concursos[] = [
                    'org' => $name,
                    'link' => $link,
                    'vagas' => $vagas,
                    'status' => $is_previsto ? 'expected' : 'open'
                ];
            }
        }
    }
}

// Keywords to filter for Education
$keywords = ['prefeitura', 'seduc', 'educação', 'professor', 'uf', 'if', 'universidade', 'instituto', 'escola', 'pedagogo', 'semed', 'ensino'];

function isEducation($name, $keywords) {
    $name = mb_strtolower($name);
    foreach ($keywords as $key) {
        if (strpos($name, $key) !== false) return true;
    }
    return false;
}

// Separate and filter
$abertos_edu = [];
$previstos_edu = [];

foreach ($concursos as $c) {
    if (isEducation($c['org'], $keywords)) {
        if ($c['status'] == 'open') {
            $abertos_edu[] = $c;
        } else {
            $previstos_edu[] = $c;
        }
    }
}

$dynamic_title = "Editais e Próximos Concursos - $uf";
$dynamic_desc = "Confira os concursos abertos e previstos na área da educação em $uf com links diretos para editais.";
require_once 'includes/header.php';
?>

<!-- Loading Overlay -->
<div id="loading-overlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(2, 2, 58, 0.9); z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
    <div class="spinner" style="width: 50px; height: 50px; border: 5px solid rgba(255,128,0,0.1); border-top-color: var(--brand-orange); border-radius: 50%; animation: spin 1s linear infinite;"></div>
    <p style="color: #fff; margin-top: 1.5rem; font-family: var(--font-mono); letter-spacing: 2px;">BUSCANDO VAGAS EM <?= $uf ?>...</p>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
.loading-active { overflow: hidden; }
</style>

<script>
function handleUFChange(select) {
    document.getElementById('loading-overlay').style.display = 'flex';
    document.body.classList.add('loading-active');
    setTimeout(() => {
        select.form.submit();
    }, 100); // Small delay to ensure loader is visible
}
    document.getElementById('loading-overlay').style.display = 'none';
</script>

<div class="container section-padding" style="margin-top: 5rem;">
    <div class="section-header reveal" style="text-align: center;">
        <span class="hero-pre-title">CENTRAL DE EDITAIS</span>
        <h1>Concursos em <span style="color: var(--brand-orange);"><?= htmlspecialchars($uf) ?></span></h1>
        <p style="color: var(--text-secondary); max-width: 600px; margin: 1rem auto 0;">Acompanhe as oportunidades com links diretos para editais e notícias.</p>
    </div>

    <!-- Filtro por Estado -->
    <div class="reveal" style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); padding: 1.5rem; border-radius: 12px; margin-bottom: 3rem; display: flex; align-items: center; justify-content: center; gap: 1rem; flex-wrap: wrap;">
        <span style="color: #fff; font-weight: bold;">Mudar Estado:</span>
        <form method="GET" id="uf-form" style="display: flex; gap: 0.5rem;">
            <select name="uf" class="form-control" style="width: auto; background: #02023a; color: #fff; border: 1px solid var(--prism-cyan);" onchange="handleUFChange(this)">
                <?php foreach($allowed_ufs as $sigla): ?>
                    <option value="<?= $sigla ?>" <?= $uf == $sigla ? 'selected' : '' ?>><?= $sigla ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: start;">
        
        <!-- Abertos -->
        <div class="reveal">
            <h2 style="font-size: 1.5rem; color: #fff; margin-bottom: 1.5rem; border-left: 4px solid #2ecc71; padding-left: 1rem;">Inscrições Abertas</h2>
            
            <?php if(empty($abertos_edu)): ?>
                <div style="background: rgba(255,255,255,0.02); padding: 2rem; border-radius: 12px; border: 1px solid var(--glass-border); text-align: center;">
                    <p style="color: var(--text-secondary);">Nenhum edital de educação com inscrições abertas em <?= $uf ?>.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach($abertos_edu as $c): ?>
                        <div class="feature-card" style="padding: 1.5rem; transition: transform 0.3s; border-left: 4px solid #2ecc71; display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                            <div style="flex: 1;">
                                <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: #fff;"><?= htmlspecialchars($c['org']) ?></h3>
                                <span style="font-family: var(--font-mono); font-size: 0.85rem; color: var(--brand-orange);"><?= htmlspecialchars($c['vagas']) ?></span>
                            </div>
                            <a href="<?= htmlspecialchars($c['link']) ?>" target="_blank" class="btn btn-outline" style="font-size: 0.75rem; padding: 0.5rem 1rem; white-space: nowrap;">VER EDITAL</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Previstos -->
        <div class="reveal">
            <h2 style="font-size: 1.5rem; color: #fff; margin-bottom: 1.5rem; border-left: 4px solid var(--brand-orange); padding-left: 1rem;">Concursos Previstos</h2>
            
            <?php if(empty($previstos_edu)): ?>
                <div style="background: rgba(255,255,255,0.02); padding: 2rem; border-radius: 12px; border: 1px solid var(--glass-border); text-align: center;">
                    <p style="color: var(--text-secondary);">Nenhum concurso previsto para educação identificado em <?= $uf ?>.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach($previstos_edu as $c): ?>
                        <div class="feature-card" style="padding: 1.5rem; transition: transform 0.3s; border-left: 4px solid var(--brand-orange); display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                            <div style="flex: 1;">
                                <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: #fff;"><?= htmlspecialchars($c['org']) ?></h3>
                                <span style="font-family: var(--font-mono); font-size: 0.85rem; color: var(--brand-orange);"><?= htmlspecialchars($c['vagas']) ?></span>
                            </div>
                            <a href="<?= htmlspecialchars($c['link']) ?>" target="_blank" class="btn btn-outline" style="font-size: 0.75rem; padding: 0.5rem 1rem; white-space: nowrap; border-color: var(--brand-orange); color: var(--brand-orange);">VER NOTÍCIA</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <div class="reveal" style="margin-top: 4rem; text-align: center; background: rgba(3, 4, 94, 0.4); border: 1px solid var(--prism-cyan); padding: 2rem; border-radius: 12px;">
        <h3 style="color: #fff; margin-bottom: 1rem;">Interessado em algum desses concursos?</h3>
        <p style="color: #ccc; margin-bottom: 1.5rem;">Prepare-se com quem mais aprova professores no Maranhão.</p>
        <a href="cursos.php" class="btn">Conhecer Nossos Cursos</a>
    </div>

    <p style="text-align: center; color: #666; font-size: 0.75rem; margin-top: 2rem;">
        Fonte dos dados: Concursos no Brasil. Atualizado automaticamente.
    </p>
</div>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
