<?php
require_once 'db_config.php';

$uf = isset($_GET['uf']) ? strtoupper($_GET['uf']) : 'MA';
$allowed_ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

if (!in_array($uf, $allowed_ufs)) {
    $uf = 'MA';
}

$api_url = "https://concursos-api.deno.dev/" . strtolower($uf);

// Fetch API data
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

$abertos = $data['concursos_abertos'] ?? [];
$previstos = $data['concursos_previstos'] ?? [];

// Keywords to filter for Education
$keywords = ['prefeitura', 'seduc', 'educação', 'professor', 'uf', 'if', 'universidade', 'instituto', 'escola', 'pedagogo'];

function isEducation($name, $keywords) {
    $name = mb_strtolower($name);
    foreach ($keywords as $key) {
        if (strpos($name, $key) !== false) return true;
    }
    return false;
}

// Separate education from others
$abertos_edu = array_filter($abertos, function($c) use ($keywords) { return isEducation($c['Órgão'], $keywords); });
$previstos_edu = array_filter($previstos, function($c) use ($keywords) { return isEducation($c['Órgão'], $keywords); });

$dynamic_title = "Editais e Próximos Concursos - $uf";
$dynamic_desc = "Confira os concursos abertos e previstos na área da educação em $uf. Fique por dentro dos editais e prepare-se com o ISP.";
require_once 'includes/header.php';
?>

<div class="container section-padding" style="margin-top: 5rem;">
    <div class="section-header reveal" style="text-align: center;">
        <span class="hero-pre-title">CENTRAL DE EDITAIS</span>
        <h1>Concursos em <span style="color: var(--brand-orange);"><?= htmlspecialchars($data['estado'] ?? $uf) ?></span></h1>
        <p style="color: var(--text-secondary); max-width: 600px; margin: 1rem auto 0;">Acompanhe as oportunidades abertas e previstas na área da educação e prefeituras.</p>
    </div>

    <!-- Filtro por Estado -->
    <div class="reveal" style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); padding: 1.5rem; border-radius: 12px; margin-bottom: 3rem; display: flex; align-items: center; justify-content: center; gap: 1rem; flex-wrap: wrap;">
        <span style="color: #fff; font-weight: bold;">Mudar Estado:</span>
        <form method="GET" id="uf-form" style="display: flex; gap: 0.5rem;">
            <select name="uf" class="form-control" style="width: auto; background: #02023a; color: #fff; border: 1px solid var(--prism-cyan);" onchange="this.form.submit()">
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
                    <p style="color: var(--text-secondary);">Nenhum edital de educação com inscrições abertas no momento em <?= $uf ?>.</p>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <?php foreach($abertos_edu as $c): ?>
                        <div class="feature-card" style="padding: 1.5rem; transition: transform 0.3s; border-left: 4px solid #2ecc71;">
                            <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: #fff;"><?= htmlspecialchars($c['Órgão']) ?></h3>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-family: var(--font-mono); font-size: 0.85rem; color: var(--brand-orange);"><?= htmlspecialchars($c['Vagas']) ?> Vagas</span>
                                <span style="background: rgba(46, 204, 113, 0.1); color: #2ecc71; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">ABERTO</span>
                            </div>
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
                        <div class="feature-card" style="padding: 1.5rem; transition: transform 0.3s; border-left: 4px solid var(--brand-orange);">
                            <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: #fff;"><?= htmlspecialchars($c['Órgão']) ?></h3>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-family: var(--font-mono); font-size: 0.85rem; color: var(--brand-orange);"><?= htmlspecialchars($c['Vagas']) ?> Vagas</span>
                                <span style="background: rgba(255, 128, 0, 0.1); color: var(--brand-orange); padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">PREVISTO</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <div class="reveal" style="margin-top: 4rem; text-align: center; background: rgba(3, 4, 94, 0.4); border: 1px solid var(--prism-cyan); padding: 2rem; border-radius: 12px;">
        <h3 style="color: #fff; margin-bottom: 1rem;">Interessado em algum desses concursos?</h3>
        <p style="color: #ccc; margin-bottom: 1.5rem;">Nossos cursos são atualizados de acordo com os editais mais recentes.</p>
        <a href="cursos.php" class="btn">Conhecer Nossos Cursos</a>
    </div>

    <p style="text-align: center; color: #666; font-size: 0.75rem; margin-top: 2rem;">
        Fonte dos dados: API de Concursos Públicos (Jeiel Miranda). Atualizado em tempo real.
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
