<?php
require_once 'db_config.php';
require_once 'includes/header.php';

// Cursos normais
$stmtCursos = $pdo->query("SELECT * FROM cursos WHERE active=1 ORDER BY id DESC");
$cursos = $stmtCursos->fetchAll();

// Campanhas de reserva ativas
$stmtReservas = $pdo->query("SELECT * FROM reservation_campaigns WHERE active=1 AND status NOT IN ('cancelada', 'rascunho') ORDER BY order_index ASC, id DESC");
$campanhas_reserva = $stmtReservas->fetchAll();

$total_cursos = count($cursos);
$total_campanhas = count($campanhas_reserva);
?>

<div class="container section-padding">
    <div class="section-header reveal" style="text-align: center; margin-bottom: 2rem;">
        <span class="hero-pre-title" style="color: var(--brand-orange); font-family: var(--font-mono); letter-spacing: 2px; font-size: 0.85rem; font-weight: 700;">PREPARAÇÃO FOCADA & APROVAÇÃO</span>
        <h1 style="font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; margin-top: 0.4rem;">Cursos e Turmas</h1>
        <p style="color: var(--text-secondary); max-width: 650px; margin: 0.8rem auto 0; font-size: 1.05rem;">
            Escolha sua preparação direcionada para Concursos de Educação Especial e Magistério. Conheça nossos cursos disponíveis e turmas com pré-reserva aberta.
        </p>
    </div>

    <!-- Destaque para Campanhas de Reserva Ativas -->
    <?php if ($total_campanhas > 0): ?>
    <div class="reservation-highlight-banner reveal" style="background: linear-gradient(135deg, rgba(255, 128, 0, 0.12) 0%, rgba(3, 4, 94, 0.4) 100%); border: 1px solid rgba(255, 128, 0, 0.35); border-radius: 16px; padding: 1.25rem 1.8rem; margin-bottom: 2.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1.2rem; flex-wrap: wrap; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        <div style="display: flex; align-items: center; gap: 1.2rem;">
            <div style="width: 52px; height: 52px; border-radius: 14px; background: rgba(255, 128, 0, 0.2); border: 1px solid rgba(255, 128, 0, 0.4); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; flex-shrink: 0;">
                🔥
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                    <strong style="color: #ff9d3b; font-size: 1.1rem; font-family: var(--font-main);">Novas Turmas com Pré-Reserva Aberta!</strong>
                    <span style="background: var(--brand-orange); color: #fff; font-size: 0.68rem; font-weight: 800; font-family: var(--font-mono); padding: 0.2rem 0.6rem; border-radius: 12px; text-transform: uppercase; letter-spacing: 0.5px;">100% Gratuito</span>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0.3rem 0 0;">
                    Garanta prioridade na chamada, vagas limitadas e condições especiais de lançamento para novos editais.
                </p>
            </div>
        </div>
        <div>
            <button type="button" onclick="filterCourses('reserva')" class="btn" style="background: var(--brand-orange); color: #fff; padding: 0.7rem 1.4rem; font-size: 0.85rem; font-weight: 700; border-radius: 30px; border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(255, 128, 0, 0.3); transition: all 0.2s ease;">
                Ver Turmas com Reserva &darr;
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Barra de Filtros / Abas -->
    <div class="filter-tabs-container reveal" style="display: flex; justify-content: center; gap: 0.6rem; margin-bottom: 2.5rem; flex-wrap: wrap;">
        <button type="button" class="course-filter-btn active" data-filter="all" onclick="filterCourses('all')">
            <span>Todos</span>
            <span class="count-badge"><?= $total_cursos + $total_campanhas ?></span>
        </button>

        <?php if ($total_campanhas > 0): ?>
        <button type="button" class="course-filter-btn highlight-tab" data-filter="reserva" onclick="filterCourses('reserva')">
            <span>🔥 Pré-Reservas Abertas</span>
            <span class="count-badge count-highlight"><?= $total_campanhas ?></span>
        </button>
        <?php endif; ?>

        <button type="button" class="course-filter-btn" data-filter="regular" onclick="filterCourses('regular')">
            <span>📚 Cursos Disponíveis</span>
            <span class="count-badge"><?= $total_cursos ?></span>
        </button>
    </div>
    
    <!-- Grid Unificado -->
    <div id="courses-grid" class="grid" style="grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr)); gap: 1.8rem;">
        
        <?php if ($total_cursos == 0 && $total_campanhas == 0): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 4rem 1rem;">
                <p style="font-family: var(--font-mono); color: var(--brand-orange); font-size: 1.1rem;">Nenhum curso ou turma disponível no momento.</p>
            </div>
        <?php endif; ?>

        <!-- 1. CAMPANHAS DE RESERVA (Destaque Principal) -->
        <?php foreach($campanhas_reserva as $camp): 
            $status_label = '🔥 PRÉ-RESERVA ABERTA';
            $status_color = 'var(--brand-orange)';
            if ($camp['status'] === 'turma_confirmada') {
                $status_label = '✅ TURMA CONFIRMADA';
                $status_color = '#10b981';
            } elseif ($camp['status'] === 'matriculas_abertas') {
                $status_label = '🚀 MATRÍCULAS ABERTAS';
                $status_color = '#3b82f6';
            } elseif ($camp['status'] === 'reservas_encerradas') {
                $status_label = '⏳ LISTA DE ESPERA';
                $status_color = '#f59e0b';
            }

            // Modalidade formatada
            $modality_str = 'Presencial e Online';
            if ($camp['allows_presencial'] && !$camp['allows_online']) {
                $modality_str = 'Presencial';
            } elseif (!$camp['allows_presencial'] && $camp['allows_online']) {
                $modality_str = 'Online / Ao Vivo';
            }
        ?>
        <div class="feature-card course-item-card reveal" data-category="reserva" style="display: flex; flex-direction: column; padding: 1.3rem; border: 1px solid rgba(255, 128, 0, 0.4); background: radial-gradient(circle at top left, rgba(255, 128, 0, 0.08) 0%, rgba(2, 2, 58, 0.7) 100%); position: relative; border-radius: 16px;">
            
            <div style="position: relative; margin-bottom: 1.2rem; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255, 128, 0, 0.25);">
                <?php if(!empty($camp['image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($camp['image']) ?>" alt="<?= htmlspecialchars($camp['title']) ?>" style="width: 100%; aspect-ratio: 16/9; object-fit: cover; display: block; transition: transform 0.4s ease;">
                <?php else: ?>
                    <div style="width: 100%; aspect-ratio: 16/9; background: linear-gradient(135deg, #03045e, #001845); display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1rem; text-align: center;">
                        <span style="font-size: 2.2rem; margin-bottom: 0.4rem;">🎯</span>
                        <span style="color: #ff9d3b; font-family: var(--font-mono); font-size: 0.78rem; letter-spacing: 1px; font-weight: 700;">NOVA TURMA PREVISTA</span>
                    </div>
                <?php endif; ?>
                
                <!-- Badges sobrepostos -->
                <div style="position: absolute; top: 10px; left: 10px; right: 10px; display: flex; gap: 0.4rem; flex-wrap: wrap;">
                    <span style="background: <?= $status_color ?>; color: #fff; font-size: 0.68rem; font-family: var(--font-mono); padding: 0.35rem 0.65rem; border-radius: 20px; font-weight: 800; box-shadow: 0 4px 10px rgba(0,0,0,0.4); text-transform: uppercase;">
                        <?= $status_label ?>
                    </span>
                    <span style="background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(6px); color: #fff; font-size: 0.68rem; font-family: var(--font-mono); padding: 0.35rem 0.65rem; border-radius: 20px; font-weight: 600; border: 1px solid rgba(255,255,255,0.15);">
                        📍 <?= htmlspecialchars($camp['city'] ?: 'Unidade Principal') ?>
                    </span>
                </div>
            </div>
            
            <div style="display: flex; gap: 0.4rem; margin-bottom: 0.6rem; flex-wrap: wrap;">
                <span style="background: rgba(255, 128, 0, 0.15); color: #ff9d3b; font-size: 0.7rem; font-family: var(--font-mono); padding: 0.2rem 0.6rem; border-radius: 8px; font-weight: 600; border: 1px solid rgba(255, 128, 0, 0.3);">
                    🎓 <?= $modality_str ?>
                </span>
                <?php if(!empty($camp['class_start_date'])): ?>
                    <span style="background: rgba(255, 255, 255, 0.08); color: var(--text-secondary); font-size: 0.7rem; font-family: var(--font-mono); padding: 0.2rem 0.6rem; border-radius: 8px;">
                        📅 Início Previsto: <?= date('d/m/Y', strtotime($camp['class_start_date'])) ?>
                    </span>
                <?php endif; ?>
            </div>

            <h3 style="font-size: 1.25rem; margin-bottom: 0.6rem; font-weight: 700; line-height: 1.35; color: #fff;">
                <?= htmlspecialchars($camp['title']) ?>
            </h3>
            
            <?php if (!empty($camp['short_description'])): ?>
                <p style="color: var(--text-secondary); font-size: 0.88rem; line-height: 1.5; margin-bottom: 1.2rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                    <?= htmlspecialchars($camp['short_description']) ?>
                </p>
            <?php else: ?>
                <p style="color: var(--text-secondary); font-size: 0.88rem; line-height: 1.5; margin-bottom: 1.2rem;">
                    Participe da lista prioritária e garanta condições especiais e bônus de lançamento antes da abertura geral.
                </p>
            <?php endif; ?>
            
            <div style="margin-top: auto; border-top: 1px solid rgba(255, 128, 0, 0.2); padding-top: 1rem; display: flex; flex-direction: column; gap: 0.8rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--text-secondary); display: block; text-transform: uppercase;">Inscrição de Interesse</span>
                        <span style="font-family: var(--font-mono); color: #10b981; font-weight: 700; font-size: 0.95rem;">100% Gratuita</span>
                    </div>
                    <span style="font-size: 0.72rem; color: #ff9d3b; font-family: var(--font-mono); background: rgba(255, 128, 0, 0.12); padding: 0.25rem 0.55rem; border-radius: 6px; border: 1px solid rgba(255, 128, 0, 0.25);">
                        Vagas Limitadas
                    </span>
                </div>
                
                <a href="/reserva/<?= htmlspecialchars($camp['slug']) ?>" class="btn" style="display: block; width: 100%; text-align: center; padding: 0.85rem 1rem; font-size: 0.9rem; font-weight: 700; background: linear-gradient(135deg, #ff8000, #e65100); color: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(255, 128, 0, 0.35); text-decoration: none; transition: transform 0.2s, box-shadow 0.2s;">
                    Garantir Minha Pré-Reserva &rarr;
                </a>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- 2. CURSOS REGULARES / EXISTENTES -->
        <?php foreach($cursos as $c): ?>
        <?php $is_reserva = ($c['status'] ?? 'Disponível') == 'Pegando Reserva'; ?>
        <div class="feature-card course-item-card reveal" data-category="regular" style="display: flex; flex-direction: column; padding: 1.2rem;">
            <div style="position: relative; margin-bottom: 1.2rem; border-radius: 8px; overflow: hidden; border: 1px solid var(--glass-border);">
                <?php if($c['thumbnail']): ?>
                    <img src="uploads/<?= $c['thumbnail'] ?>" alt="<?= htmlspecialchars($c['image_alt'] ?: $c['title']) ?>" style="width: 100%; aspect-ratio: 1/1; object-fit: cover; display: block; transition: transform 0.3s ease;">
                <?php else: ?>
                    <div style="width: 100%; aspect-ratio: 1/1; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center;">
                        <span style="color: var(--text-secondary);">Sem Imagem</span>
                    </div>
                <?php endif; ?>
                
                <div style="position: absolute; top: 10px; left: 10px; right: 10px; display: flex; gap: 0.4rem; flex-wrap: wrap;">
                    <?php if($is_reserva): ?>
                        <span style="background: var(--brand-orange); color: #fff; font-size: 0.7rem; font-family: var(--font-mono); padding: 0.3rem 0.6rem; border-radius: 20px; font-weight: 700; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">🚨 RESERVA</span>
                    <?php else: ?>
                        <span style="background: rgba(16, 185, 129, 0.85); color: #fff; font-size: 0.68rem; font-family: var(--font-mono); padding: 0.3rem 0.6rem; border-radius: 20px; font-weight: 700; backdrop-filter: blur(4px);">MATRÍCULAS ABERTAS</span>
                    <?php endif; ?>
                    <span style="background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(5px); color: #fff; font-size: 0.7rem; font-family: var(--font-mono); padding: 0.3rem 0.6rem; border-radius: 20px; font-weight: 500; border: 1px solid rgba(255,255,255,0.1);">⏱ <?= htmlspecialchars($c['duration']) ?></span>
                    <span style="background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(5px); color: #fff; font-size: 0.7rem; font-family: var(--font-mono); padding: 0.3rem 0.6rem; border-radius: 20px; font-weight: 500; border: 1px solid rgba(255,255,255,0.1);">📍 <?= htmlspecialchars($c['modality'] ?: 'Presencial e Online') ?></span>
                </div>
            </div>
            
            <h3 style="font-size: 1.2rem; margin-bottom: 1rem; flex-grow: 1; line-height: 1.4;"><?= htmlspecialchars($c['title']) ?></h3>
            
            <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--glass-border); padding-top: 1rem; gap: 0.5rem; flex-wrap: wrap;">
                <?php if($is_reserva): ?>
                    <span style="font-family: var(--font-mono); color: var(--brand-orange); font-size: 0.85rem; font-weight: 600;">Sob consulta</span>
                    <a href="<?= !empty($c['slug']) ? '/curso/'.$c['slug'] : 'curso-detalhes.php?id='.$c['id'] ?>" class="btn" style="flex: 1; min-width: 100px; text-align: center; padding: 0.7rem 1rem; font-size: 0.8rem; background: var(--brand-orange); color: #fff; border-color: var(--brand-orange);">Reservar</a>
                <?php else: ?>
                    <?php if($c['price'] > 0): ?>
                        <span style="font-family: var(--font-mono); color: var(--text-primary); font-size: 1.2rem; font-weight: 700;">R$ <?= number_format($c['price'], 2, ',', '.') ?></span>
                    <?php else: ?>
                        <span style="font-family: var(--font-mono); color: var(--text-secondary); font-size: 0.85rem;">Consulte valores</span>
                    <?php endif; ?>
                    <a href="<?= !empty($c['slug']) ? '/curso/'.$c['slug'] : 'curso-detalhes.php?id='.$c['id'] ?>" class="btn" style="flex: 1; min-width: 100px; text-align: center; padding: 0.7rem 1rem; font-size: 0.8rem;">Saiba Mais</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>

<style>
/* Estilos das Abas de Filtro */
.course-filter-btn {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--glass-border);
    color: var(--text-secondary);
    padding: 0.65rem 1.2rem;
    border-radius: 30px;
    font-family: var(--font-mono);
    font-size: 0.85rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.25s ease;
}

.course-filter-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    border-color: rgba(255, 255, 255, 0.25);
}

.course-filter-btn.active {
    background: #03045e;
    color: #fff;
    border-color: #ff8000;
    box-shadow: 0 0 15px rgba(255, 128, 0, 0.2);
}

.course-filter-btn.highlight-tab {
    border-color: rgba(255, 128, 0, 0.4);
    color: #ff9d3b;
}

.course-filter-btn.highlight-tab.active {
    background: linear-gradient(135deg, #ff8000, #d96d00);
    color: #fff;
    border-color: #ff8000;
    box-shadow: 0 4px 15px rgba(255, 128, 0, 0.35);
}

.course-filter-btn .count-badge {
    background: rgba(255, 255, 255, 0.15);
    color: inherit;
    font-size: 0.72rem;
    padding: 0.15rem 0.5rem;
    border-radius: 12px;
    font-weight: 700;
}

.course-filter-btn.active .count-badge {
    background: rgba(255, 255, 255, 0.25);
    color: #fff;
}

.course-item-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease, opacity 0.25s ease;
}

.course-item-card:hover {
    transform: translateY(-5px);
}
</style>

<script>
function filterCourses(category) {
    // Atualiza botões
    document.querySelectorAll('.course-filter-btn').forEach(btn => {
        if (btn.getAttribute('data-filter') === category) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // Filtra cards
    const cards = document.querySelectorAll('.course-item-card');
    cards.forEach(card => {
        const itemCat = card.getAttribute('data-category');
        if (category === 'all' || itemCat === category) {
            card.style.display = 'flex';
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 10);
        } else {
            card.style.opacity = '0';
            card.style.transform = 'translateY(10px)';
            setTimeout(() => {
                card.style.display = 'none';
            }, 200);
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
