<?php
require_once 'db_config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM cursos WHERE id = ? AND active = 1");
$stmt->execute([$id]);
$curso = $stmt->fetch();

if (!$curso) {
    header("Location: cursos.php");
    exit;
}

$link_venda = !empty($curso['payment_link']) ? $curso['payment_link'] : "inscricao.php?curso_id=" . $curso['id'];

require_once 'includes/header.php';

function render_html_or_text($content) {
    if (empty($content)) return '';
    if (preg_match('/<(p|br|ul|ol|li|strong|em|div|span|h[1-6])[> ]/i', $content)) {
        return $content;
    }
    return nl2br(htmlspecialchars($content));
}
?>

<main style="background: var(--obsidian-deep); min-height: 100vh;">
    <!-- Course Header / Hero -->
    <section style="padding: 10rem 5% 4rem; background: radial-gradient(circle at top right, var(--prism-cyan) 0%, transparent 40%), radial-gradient(circle at bottom left, var(--prism-amber) 0%, transparent 40%); border-bottom: 1px solid var(--glass-border);">
        <div class="container" style="display: flex; flex-wrap: wrap; gap: 4rem; align-items: center;">
            <div style="flex: 1; min-width: 300px;">
                <span style="font-family: var(--font-mono); color: var(--brand-orange); letter-spacing: 2px; text-transform: uppercase; font-size: 0.8rem; margin-bottom: 1rem; display: block;">Educação Especial Inclusiva</span>
                <h1 style="font-size: clamp(2.5rem, 5vw, 4rem); line-height: 1.1; margin-bottom: 1.5rem;"><?= htmlspecialchars($curso['title']) ?></h1>
                
                <div style="display: flex; gap: 2rem; margin-bottom: 2rem; color: rgba(255, 255, 255, 0.85); font-family: var(--font-mono); font-size: 0.9rem;">
                    <div>
                        <strong style="display: block; color: var(--text-primary);">DURAÇÃO</strong>
                        <?= htmlspecialchars($curso['duration']) ?>
                    </div>
                    <div>
                        <strong style="display: block; color: var(--text-primary);">MODALIDADE</strong>
                        <?= htmlspecialchars($curso['modality'] ?: 'Presencial e Online') ?>
                    </div>
                </div>
            </div>
            
            <div style="flex: 1; min-width: 300px; display: flex; justify-content: center;">
                <?php if($curso['thumbnail']): ?>
                    <div style="background: rgba(255,255,255,0.05); border-radius: 12px; padding: 1rem; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
                        <img src="uploads/<?= $curso['thumbnail'] ?>" alt="<?= htmlspecialchars($curso['title']) ?>" style="width: 100%; max-width: 500px; max-height: 400px; object-fit: contain; border-radius: 8px;">
                    </div>
                <?php else: ?>
                    <div style="width: 100%; max-width: 500px; height: 300px; background: rgba(255,255,255,0.05); border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,0.1);">
                        <span style="color: rgba(255, 255, 255, 0.85);">Sem Imagem</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Details Section -->
    <section class="section-padding" style="padding-top: 4rem;">
        <div class="container" style="display: flex; flex-wrap: wrap; gap: 4rem;">
            <!-- Left: Description -->
            <div style="flex: 2; min-width: 300px;">
                <h2 style="font-size: 2rem; margin-bottom: 2rem; color: var(--brand-orange);">Sobre o Curso</h2>
                <div style="color: rgba(255, 255, 255, 0.85); font-size: 1.1rem; line-height: 1.8; margin-bottom: 3rem;">
                    <?= render_html_or_text($curso['description']) ?>
                </div>


            </div>
            
            <!-- Right: Investment Card -->
            <?php $is_reserva = ($curso['status'] ?? 'Disponível') == 'Pegando Reserva'; ?>
            <div style="flex: 1; min-width: 300px;">
                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--glass-border); border-radius: 12px; padding: 2.5rem; position: sticky; top: 120px;">
                    <?php if($is_reserva): ?>
                        <h3 style="font-size: 1.2rem; margin-bottom: 1rem; color: var(--brand-orange); text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">🚨 Reserva de Vagas</h3>
                        <div style="font-size: 1.1rem; color: var(--text-primary); margin-bottom: 2rem; line-height: 1.6; border-left: 3px solid var(--brand-orange); padding-left: 1rem;">
                            Garanta seu lugar na próxima turma! Os valores e condições especiais de pré-venda serão informados pela nossa equipe após o seu cadastro.
                        </div>
                    <?php else: ?>
                        <h3 style="font-size: 1.2rem; margin-bottom: 1rem; color: rgba(255, 255, 255, 0.85);">Investimento</h3>
                        <div style="font-size: 3rem; font-weight: 700; color: var(--text-primary); margin-bottom: 2rem; font-family: var(--font-mono);">
                            <span style="font-size: 1.5rem; vertical-align: super;">R$</span> <?= number_format($curso['price'], 2, ',', '.') ?>
                        </div>
                    <?php endif; ?>
                    
                    <ul style="list-style: none; margin: 0 0 2rem 0; padding: 0; color: rgba(255, 255, 255, 0.85);">
                        <li style="margin-bottom: 0.8rem; display: flex; align-items: center; gap: 10px;">
                            <span style="color: #25D366;">✓</span> <?php echo $is_reserva ? "Prioridade de Matrícula" : "Acesso Imediato"; ?>
                        </li>
                        <li style="margin-bottom: 0.8rem; display: flex; align-items: center; gap: 10px;">
                            <span style="color: #25D366;">✓</span> Material em PDF
                        </li>
                        <li style="margin-bottom: 0.8rem; display: flex; align-items: center; gap: 10px;">
                            <span style="color: #25D366;">✓</span> Simulados
                        </li>
                        <li style="margin-bottom: 0.8rem; display: flex; align-items: center; gap: 10px;">
                            <span style="color: #25D366;">✓</span> Suporte
                        </li>
                    </ul>

                    <?php if($is_reserva): ?>
                        <a href="<?= htmlspecialchars($link_venda) ?>" <?= !empty($curso['payment_link']) ? 'target="_blank"' : '' ?> class="btn" style="width: 100%; font-size: 1rem; padding: 1.2rem; text-align: center; display: block; box-sizing: border-box; background: var(--brand-orange); border-color: var(--brand-orange);">Reservar Minha Vaga</a>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars($link_venda) ?>" <?= !empty($curso['payment_link']) ? 'target="_blank"' : '' ?> class="btn" style="width: 100%; font-size: 1rem; padding: 1.2rem; text-align: center; display: block; box-sizing: border-box;">Garantir Minha Vaga</a>
                    <?php endif; ?>
                    
                    <div style="text-align: center; margin-top: 1.5rem;">
                        <a href="https://wa.me/559931999394" target="_blank" style="color: #25D366; text-decoration: none; font-size: 0.9rem; font-weight: 500;">Dúvidas? Fale pelo WhatsApp</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
