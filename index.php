<?php
require_once 'db_config.php';
require_once 'includes/header.php';

$cursos_destaque = $pdo->query("SELECT * FROM cursos WHERE active=1 ORDER BY id DESC LIMIT 3")->fetchAll();
$depoimentos = $pdo->query("SELECT * FROM depoimentos WHERE active=1 ORDER BY id DESC")->fetchAll();
?>

<main>
    <section class="hero">
        <div class="hero-bg-light"></div>
        <div class="hero-bg-light-2"></div>
        
            <span class="hero-pre-title reveal">PREPARATÓRIO PARA CONCURSOS PÚBLICOS</span>
            <h1 class="reveal" style="font-size: clamp(2.5rem, 6vw, 5rem);">Prepare-se com foco em<br><span style="color: var(--brand-orange);">Educação Especial Inclusiva</span></h1>
            <p class="hero-description reveal">
                O ISP Preparatórios oferece formação direcionada para quem busca aprovação em cargos de Educação Especial Inclusiva, com suporte completo em Conhecimentos Gerais.
            </p>
            <div class="cta-group reveal">
                <a href="cursos.php" class="btn">Ver Cursos Disponíveis</a>
                <a href="#modalidades" class="btn btn-outline">Presencial e Online</a>
            </div>
    </section>

    <!-- Stats -->
    <section class="stats reveal" style="padding: 3rem 5%; display: flex; justify-content: space-between; background: #02023a; border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border);">
        <div style="text-align: left;">
            <span style="font-family: var(--font-mono); font-size: 2rem; display: block; margin-bottom: 0.5rem; color: var(--text-primary);">Ed. Especial</span>
            <span style="font-family: var(--font-mono); text-transform: uppercase; font-size: 0.7rem; color: var(--brand-orange); letter-spacing: 2px;">Inclusiva</span>
        </div>
        <div style="text-align: left;">
            <span style="font-family: var(--font-mono); font-size: 3rem; display: block; margin-bottom: 0.5rem; color: var(--text-primary);">18</span>
            <span style="font-family: var(--font-mono); text-transform: uppercase; font-size: 0.7rem; color: var(--brand-orange); letter-spacing: 2px;">Anos de experiência docente</span>
        </div>
        <div style="text-align: left;">
            <span style="font-family: var(--font-mono); font-size: 3rem; display: block; margin-bottom: 0.5rem; color: var(--text-primary);">2</span>
            <span style="font-family: var(--font-mono); text-transform: uppercase; font-size: 0.7rem; color: var(--brand-orange); letter-spacing: 2px;">Modalidades de ensino</span>
        </div>
        <div style="text-align: left;">
            <span style="font-family: var(--font-mono); font-size: 3rem; display: block; margin-bottom: 0.5rem; color: var(--text-primary);">100%</span>
            <span style="font-family: var(--font-mono); text-transform: uppercase; font-size: 0.7rem; color: var(--brand-orange); letter-spacing: 2px;">Foco no seu cargo</span>
        </div>
    </section>

    <div class="prism-divider"></div>

    <!-- Diferenciais -->
    <section class="section-padding" id="diferenciais">
        <div class="container">
            <div class="section-header reveal">
                <span class="hero-pre-title">POR QUE O ISP?</span>
                <h2>Preparação séria e objetiva</h2>
                <p style="color: var(--text-secondary); max-width: 600px; margin-top: 1rem;">Conteúdo construído por quem conhece a área, com foco direto no que as bancas cobram.</p>
            </div>
            
            <div class="grid">
                <div class="feature-card reveal">
                    <span class="feature-id">FOCO</span>
                    <h3 style="font-size: 1.2rem;">Educação Especial Inclusiva</h3>
                    <p>Todo o conteúdo é organizado em torno dos cargos da área de Educação Especial, sem dispersão com temas que não contribuem para a sua aprovação.</p>
                </div>
                <div class="feature-card reveal">
                    <span class="feature-id">EXPERIÊNCIA</span>
                    <h3 style="font-size: 1.2rem;">Professores na área</h3>
                    <p>Formação conduzida por quem atua há anos na Educação Especial e conhece, na prática, o que as bancas exigem.</p>
                </div>
                <div class="feature-card reveal">
                    <span class="feature-id">ATUALIZAÇÃO</span>
                    <h3 style="font-size: 1.2rem;">Legislação atualizada</h3>
                    <p>LDBEN, Política Nacional de Educação Especial e demais marcos legais trabalhados com foco direto nas questões de concurso.</p>
                </div>
                <div class="feature-card reveal">
                    <span class="feature-id">TREINAMENTO</span>
                    <h3 style="font-size: 1.2rem;">Simulados comentados</h3>
                    <p>Treino constante com questões no perfil das principais bancas, com correção e comentários para acelerar o aprendizado.</p>
                </div>
                <div class="feature-card reveal">
                    <span class="feature-id">SUPORTE</span>
                    <h3 style="font-size: 1.2rem;">Acompanhamento próximo</h3>
                    <p>Suporte direto para tirar dúvidas, acompanhar o desempenho e ajustar a estratégia de estudo ao longo do curso.</p>
                </div>
                <div class="feature-card reveal">
                    <span class="feature-id">FORMAÇÃO COMPLETA</span>
                    <h3 style="font-size: 1.2rem;">Conhecimentos Gerais</h3>
                    <p>Língua Portuguesa, Matemática, Informática, Conhecimentos Pedagógicos e Regionais para completar a sua preparação.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Cursos (Dinâmico do BD) -->
    <section class="section-padding" style="background: rgba(2, 2, 58, 0.5);">
        <div class="container">
            <div class="section-header reveal">
                <span class="hero-pre-title">GRADE DE CURSOS</span>
                <h2>O que você estuda no ISP</h2>
                <p style="color: var(--text-secondary); max-width: 600px; margin-top: 1rem;">Dois eixos de formação para uma preparação completa e alinhada ao edital.</p>
            </div>
            
            <div class="grid">
                <?php foreach($cursos_destaque as $c): ?>
                <?php $is_reserva = ($c['status'] ?? 'Disponível') == 'Pegando Reserva'; ?>
                <div class="feature-card reveal" style="display: flex; flex-direction: column; padding: 1.2rem;">
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
                            <?php endif; ?>
                            <span style="background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(5px); color: #fff; font-size: 0.7rem; font-family: var(--font-mono); padding: 0.3rem 0.6rem; border-radius: 20px; font-weight: 500; border: 1px solid rgba(255,255,255,0.1);">⏱ <?= htmlspecialchars($c['duration']) ?></span>
                            <span style="background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(5px); color: #fff; font-size: 0.7rem; font-family: var(--font-mono); padding: 0.3rem 0.6rem; border-radius: 20px; font-weight: 500; border: 1px solid rgba(255,255,255,0.1);">📍 <?= htmlspecialchars($c['modality'] ?: 'Presencial e Online') ?></span>
                        </div>
                    </div>
                    
                    <h3 style="font-size: 1.2rem; margin-bottom: 1rem; flex-grow: 1; line-height: 1.4;"><?= htmlspecialchars($c['title']) ?></h3>
                    
                    <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--glass-border); padding-top: 1rem; gap: 0.5rem; flex-wrap: wrap;">
                        <?php if($is_reserva): ?>
                            <span style="font-family: var(--font-mono); color: var(--brand-orange); font-size: 0.85rem; font-weight: 600;">Valores sob consulta</span>
                            <a href="<?= !empty($c['slug']) ? '/curso/'.$c['slug'] : 'curso-detalhes.php?id='.$c['id'] ?>" class="btn" style="flex: 1; min-width: 100px; text-align: center; padding: 0.7rem 1rem; font-size: 0.8rem; background: var(--brand-orange); color: #fff; border-color: var(--brand-orange);">Reservar</a>
                        <?php else: ?>
                            <?php if($c['price'] > 0): ?>
                                <span style="font-family: var(--font-mono); color: var(--text-primary); font-size: 1.2rem; font-weight: 700;">R$ <?= number_format($c['price'], 2, ',', '.') ?></span>
                            <?php endif; ?>
                            <a href="<?= !empty($c['slug']) ? '/curso/'.$c['slug'] : 'curso-detalhes.php?id='.$c['id'] ?>" class="btn" style="flex: 1; min-width: 100px; text-align: center; padding: 0.7rem 1rem; font-size: 0.8rem;">Saiba Mais</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="reveal" style="text-align: center; margin-top: 4rem;">
                <a href="cursos.php" class="btn btn-outline">Ver todos os Cursos</a>
            </div>
        </div>
    </section>

    <!-- Modalidades -->
    <section class="section-padding" id="modalidades">
        <div class="container">
            <div class="section-header reveal" style="text-align: center;">
                <span class="hero-pre-title">MODALIDADES DE ENSINO</span>
                <h2>Aprenda do jeito que funciona para você</h2>
                <p style="color: var(--text-secondary); max-width: 600px; margin: 1rem auto 0;">Duas modalidades pensadas para diferentes realidades, com a mesma qualidade de conteúdo.</p>
            </div>
            
            <div class="grid grid-modalidades">
                <div class="content-block reveal" style="width: 100%; max-width: none;">
                    <span class="feature-id">PRESENCIAL</span>
                    <h3>Aulas Presenciais</h3>
                    <ul style="margin-top: 1.5rem; list-style-type: none; margin-left: 0;">
                        <li style="margin-bottom: 10px;"><span style="color: var(--brand-orange);">→</span> Interação direta com o instrutor</li>
                        <li style="margin-bottom: 10px;"><span style="color: var(--brand-orange);">→</span> Grupos reduzidos para mais atenção</li>
                        <li style="margin-bottom: 10px;"><span style="color: var(--brand-orange);">→</span> Material impresso incluso</li>
                        <li style="margin-bottom: 10px;"><span style="color: var(--brand-orange);">→</span> Simulados presenciais com correção</li>
                        <li style="margin-bottom: 10px;"><span style="color: var(--brand-orange);">→</span> Ambiente estruturado de estudo</li>
                    </ul>
                </div>
                
                <div class="content-block reveal" style="width: 100%; max-width: none;">
                    <span class="feature-id">ONLINE</span>
                    <h3>Aulas Online</h3>
                    <ul style="margin-top: 1.5rem; list-style-type: none; margin-left: 0;">
                        <li style="margin-bottom: 10px;"><span style="color: var(--brand-orange);">→</span> Acesso às videoaulas gravadas</li>
                        <li style="margin-bottom: 10px;"><span style="color: var(--brand-orange);">→</span> Aulas ao vivo com participação</li>
                        <li style="margin-bottom: 10px;"><span style="color: var(--brand-orange);">→</span> Material digital completo em PDF</li>
                        <li style="margin-bottom: 10px;"><span style="color: var(--brand-orange);">→</span> Suporte por grupo exclusivo</li>
                        <li style="margin-bottom: 10px;"><span style="color: var(--brand-orange);">→</span> Estude no seu ritmo, onde estiver</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Depoimentos -->
    <?php if(!empty($depoimentos)): ?>
    <section class="section-padding" style="background: rgba(2, 2, 58, 0.5);" id="depoimentos">
        <div class="container">
            <div class="section-header reveal">
                <span class="hero-pre-title">DEPOIMENTOS</span>
                <h2>Quem passou, conta</h2>
                <p style="color: var(--text-secondary); max-width: 600px; margin-top: 1rem;">Resultados reais de alunos que se prepararam com o ISP Preparatórios.</p>
            </div>
            
            <div class="grid">
                <?php foreach($depoimentos as $dep): ?>
                <div class="feature-card reveal" style="background: rgba(255,255,255,0.02);">
                    <div style="color: var(--brand-orange); margin-bottom: 1rem; font-size: 1.2rem;">★★★★★</div>
                    <p style="font-style: italic;">"<?= htmlspecialchars($dep['content']) ?>"</p>
                    <div style="margin-top: 2rem; border-top: 1px solid var(--glass-border); padding-top: 1rem;">
                        <strong style="color: var(--text-primary); display: block;"><?= htmlspecialchars($dep['name']) ?></strong>
                        <span style="color: var(--text-secondary); font-size: 0.8rem;"><?= htmlspecialchars($dep['role']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA -->
    <section class="final-cta" style="text-align: center; background: radial-gradient(circle at center, #06088a 0%, #03045e 100%);">
        <h2 style="font-size: clamp(2rem, 5vw, 4rem); margin-bottom: 1rem;" class="reveal">Sua aprovação começa com a decisão certa</h2>
        <p class="reveal" style="color: var(--text-secondary); max-width: 600px; margin: 0 auto 3rem; font-size: 1.2rem;">Inscreva-se agora e tenha acesso à preparação mais especializada para cargos de Educação Especial do Maranhão.</p>
        <div class="cta-group reveal" style="justify-content: center;">
            <a href="inscricao.php" class="btn">Garantir minha vaga</a>
            <a href="https://wa.me/559931999394" class="btn btn-outline" target="_blank">Falar pelo WhatsApp</a>
        </div>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
