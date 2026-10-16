<?php
require_once 'db_config.php';

// Metadados dinâmicos para a página inicial
$dynamic_title = "Cursos Preparatórios, Banco de Questões e Tecnologia para Concursos";
$dynamic_desc = "Prepare-se com o ISP Preparatórios: cursos especializados em Educação Especial Inclusiva, AEE, Libras, Braille e disciplinas gerais, com videoaulas, banco de questões, simulados e apoio pedagógico.";

require_once 'includes/header.php';

// Busca de cursos e campanhas ativas do banco de dados
$cursos_destaque = $pdo->query("SELECT * FROM cursos WHERE active=1 ORDER BY id DESC LIMIT 6")->fetchAll();
$campanhas_destaque = $pdo->query("SELECT * FROM reservation_campaigns WHERE active=1 AND status NOT IN ('cancelada', 'rascunho') ORDER BY order_index ASC, id DESC LIMIT 4")->fetchAll();
$depoimentos = $pdo->query("SELECT * FROM depoimentos WHERE active=1 ORDER BY id DESC LIMIT 6")->fetchAll();
?>

<!-- 1. APRESENTAÇÃO PRINCIPAL (HERO) -->
<section class="hero" id="inicio" aria-labelledby="hero-heading">
    <div class="hero-bg-light" aria-hidden="true"></div>
    <div class="hero-bg-light-2" aria-hidden="true"></div>
    
    <div class="hero-wrapper">
        <div class="hero-content">
            <span class="hero-pre-title reveal">PREPARAÇÃO PARA CONCURSOS PÚBLICOS</span>
            <h1 id="hero-heading" class="reveal">
                Sua preparação para concursos com <span style="color: var(--brand-orange);">aulas, prática e tecnologia.</span>
            </h1>
            <p class="hero-description reveal" style="max-width: 780px; margin: 0 auto 1.8rem; font-size: 1.15rem; line-height: 1.7; border-left: none; padding-left: 0;">
                Prepare-se com o ISP Preparatórios: cursos especializados, banco de questões, simulados e ferramentas para organizar seus estudos em um só lugar.
            </p>
            <p class="reveal" style="color: #cbd5e1; font-weight: 500; font-size: 1rem; margin-bottom: 2.5rem; letter-spacing: 0.3px;">
                Foco em <strong style="color: #ffffff;">Educação Especial Inclusiva</strong>, <strong style="color: #ffffff;">AEE</strong>, <strong style="color: #ffffff;">Libras</strong>, <strong style="color: #ffffff;">Braille</strong> e nas disciplinas do seu concurso.
            </p>
            <div class="cta-group reveal" style="justify-content: center;">
                <a href="#cursos" class="btn">Conhecer os cursos</a>
                <a href="#plataforma" class="btn btn-outline">Conhecer a plataforma</a>
            </div>
        </div>

        <!-- Moldura representativa da interface da Área do Aluno -->
        <div class="hero-platform-mockup reveal" aria-label="Visualização do Ambiente Virtual de Aprendizagem do Aluno">
            <div class="mockup-window-header">
                <div class="mockup-dots" aria-hidden="true">
                    <span class="dot-red"></span>
                    <span class="dot-yellow"></span>
                    <span class="dot-green"></span>
                </div>
                <div class="mockup-address-bar">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                    <span>aluno.isppreparatorios.com.br</span>
                </div>
                <div class="mockup-status-badge">
                    Ambiente do Aluno ISP
                </div>
            </div>
            <div class="mockup-screen-container">
                <img src="/img/plataforma/home-aluno.png" alt="Interface real do painel do aluno no Ambiente Virtual de Aprendizagem do ISP Preparatórios" loading="lazy">
            </div>
            <p class="mockup-caption">Interface do Ambiente Virtual de Aprendizagem (AVA) do ISP Preparatórios.</p>
        </div>
    </div>
</section>

<!-- 2. PILARES & CREDENCIAIS INSTITUCIONAIS -->
<section class="stats reveal" style="padding: 2.8rem 5%; display: flex; justify-content: space-around; flex-wrap: wrap; gap: 2rem; background: #02023a; border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border);" aria-label="Diferenciais institucionais do ISP">
    <div style="text-align: left;">
        <span style="font-family: var(--font-mono); font-size: 1.8rem; font-weight: 700; display: block; margin-bottom: 0.3rem; color: var(--text-primary);">Ed. Especial</span>
        <span style="font-family: var(--font-mono); text-transform: uppercase; font-size: 0.72rem; color: var(--brand-orange); letter-spacing: 2px;">Inclusiva & AEE</span>
    </div>
    <div style="text-align: left;">
        <span style="font-family: var(--font-mono); font-size: 2.2rem; font-weight: 700; display: block; margin-bottom: 0.3rem; color: var(--text-primary);">18 Anos</span>
        <span style="font-family: var(--font-mono); text-transform: uppercase; font-size: 0.72rem; color: var(--brand-orange); letter-spacing: 2px;">Experiência docente</span>
    </div>
    <div style="text-align: left;">
        <span style="font-family: var(--font-mono); font-size: 2.2rem; font-weight: 700; display: block; margin-bottom: 0.3rem; color: var(--text-primary);">2 Formatos</span>
        <span style="font-family: var(--font-mono); text-transform: uppercase; font-size: 0.72rem; color: var(--brand-orange); letter-spacing: 2px;">Presencial e Online</span>
    </div>
    <div style="text-align: left;">
        <span style="font-family: var(--font-mono); font-size: 2.2rem; font-weight: 700; display: block; margin-bottom: 0.3rem; color: var(--text-primary);">Aulas & Prática</span>
        <span style="font-family: var(--font-mono); text-transform: uppercase; font-size: 0.72rem; color: var(--brand-orange); letter-spacing: 2px;">Teoria e Questões</span>
    </div>
</section>

<div class="prism-divider" aria-hidden="true"></div>

<!-- 3. DIFERENCIAIS: POR QUE ESTUDAR NO ISP? -->
<section class="section-padding" id="diferenciais" aria-labelledby="diferenciais-heading">
    <div class="container">
        <div class="section-header reveal" style="text-align: center;">
            <span class="hero-pre-title">POR QUE ESTUDAR NO ISP?</span>
            <h2 id="diferenciais-heading">Preparação direcionada para quem busca aprovação</h2>
            <p style="color: var(--text-secondary); max-width: 650px; margin: 1rem auto 0; font-size: 1.05rem;">
                Conteúdo elaborado por professores com atuação prática na área, alinhando fundamentação teórica às exigências reais das bancas de concurso.
            </p>
        </div>
        
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 1.8rem;">
            <article class="feature-card reveal">
                <span class="feature-id">PILAR 01</span>
                <h3>Preparação especializada</h3>
                <p>Estrutura focada em Educação Especial Inclusiva, Atendimento Educacional Especializado (AEE), Libras e Braille, integrada aos Conhecimentos Gerais exigidos nos editais.</p>
            </article>

            <article class="feature-card reveal">
                <span class="feature-id">PILAR 02</span>
                <h3>Aulas e materiais organizados</h3>
                <p>Conteúdos divididos em módulos e disciplinas, com videoaulas didáticas, encontros ao vivo pelo Google Meet e apostilas em PDF para acompanhar cada aula.</p>
            </article>

            <article class="feature-card reveal">
                <span class="feature-id">PILAR 03</span>
                <h3>Prática com questões e simulados</h3>
                <p>Resolução de questões de bancas examinadoras com textos de apoio, gabarito oficial e simulados com tempo cronometrado para avaliar seu ritmo de prova.</p>
            </article>

            <article class="feature-card reveal">
                <span class="feature-id">PILAR 04</span>
                <h3>Acompanhamento do desempenho</h3>
                <p>Painel do aluno com percentual de aulas concluídas, estatísticas de resolução de exercícios e visualização do seu progresso em cada disciplina.</p>
            </article>

            <article class="feature-card reveal">
                <span class="feature-id">PILAR 05</span>
                <h3>Ferramentas de revisão</h3>
                <p>Recursos de reforço com cartões de memorização (flashcards), cadernos de questões para treino recorrente e materiais de apoio para consulta rápida.</p>
            </article>

            <article class="feature-card reveal">
                <span class="feature-id">PILAR 06</span>
                <h3>Apoio de inteligência artificial</h3>
                <p>Assistente virtual integrado para esclarecer tópicos teóricos e produzir explicações para questões resolvidas, complementando o estudo dos materiais.</p>
            </article>
        </div>
    </div>
</section>

<!-- 4. CONHEÇA A PLATAFORMA (MÓDULOS INTEGRADOS) -->
<section class="section-padding" id="plataforma" style="background: rgba(2, 2, 58, 0.45); border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border);" aria-labelledby="plataforma-heading">
    <div class="container">
        <div class="section-header reveal" style="text-align: center;">
            <span class="hero-pre-title">ECOSSISTEMA INTEGRADO</span>
            <h2 id="plataforma-heading">Conheça a plataforma de estudos</h2>
            <p style="color: var(--text-secondary); max-width: 700px; margin: 1rem auto 0; font-size: 1.05rem;">
                Ambiente de aprendizagem planejado para unir videoaulas, leitura, treino de questões e ferramentas de acompanhamento em uma experiência fluida no computador, tablet ou celular.
            </p>
        </div>

        <div class="notice-banner reveal" role="note">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="var(--brand-orange)" aria-hidden="true" style="flex-shrink: 0;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            <div>
                <strong>Aviso aos estudantes:</strong> Os recursos disponíveis variam conforme o curso ou plano contratado. Consulte os detalhes na página da sua turma.
            </div>
        </div>

        <div class="platform-grid">
            <!-- Módulo 1 -->
            <div class="platform-card reveal">
                <div class="platform-card-header">
                    <div class="platform-icon-box" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-8 12.5v-9l6 4.5-6 4.5z"/></svg>
                    </div>
                    <h3>Aulas gravadas</h3>
                </div>
                <p>Player dedicado com controle de velocidade de reprodução (1.25x, 1.5x, 2.0x), memorização do ponto onde você parou, espaço para anotações e registro automático de conclusão de aula.</p>
                <div class="platform-card-note">Controle de qualidade automático e navegação contínua entre aulas.</div>
            </div>

            <!-- Módulo 2 -->
            <div class="platform-card reveal">
                <div class="platform-card-header">
                    <div class="platform-icon-box" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>
                    </div>
                    <h3>Aulas ao vivo</h3>
                </div>
                <p>Transmissões integradas via Google Meet com avisos na plataforma para você interagir em tempo real com os professores. Para turmas com previsão de gravação, os vídeos ficam disponíveis na sala virtual.</p>
                <div class="platform-card-note">Disponibilidade e gravação conforme as regras pedagógicas de cada turma.</div>
            </div>

            <!-- Módulo 3 -->
            <div class="platform-card reveal">
                <div class="platform-card-header">
                    <div class="platform-icon-box" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    </div>
                    <h3>Banco de questões</h3>
                </div>
                <p>Filtros por banca, ano, disciplina e cargo. Questões com textos de apoio, fórmulas e imagens, gabarito oficial e geração de cadernos em PDF. Comentários de professores e videoaulas são exibidos conforme cadastrados.</p>
                <div class="platform-card-note">Acervo especializado em concursos da área educacional e disciplinas gerais.</div>
            </div>

            <!-- Módulo 4 -->
            <div class="platform-card reveal">
                <div class="platform-card-header">
                    <div class="platform-icon-box" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M15 1H9v2h6V1zm-4 13h2V8h-2v6zm8.03-6.61l1.42-1.42c-.43-.51-.9-.99-1.41-1.41l-1.42 1.42C16.07 4.74 14.12 4 12 4c-4.97 0-9 4.03-9 9s4.02 9 9 9 9-4.03 9-9c0-2.12-.74-4.07-1.97-5.61zM12 20c-3.87 0-7-3.13-7-7s3.13-7 7-7 7 3.13 7 7-3.13 7-7 7z"/></svg>
                    </div>
                    <h3>Simulados</h3>
                </div>
                <p>Pratique em condições reais com controle de tempo cronometrado e relatório de resultado ao final. Acompanhe seus acertos e identifique disciplinas que merecem maior dedicação antes da prova.</p>
                <div class="platform-card-note">Análise quantitativa de erros e acertos por tentativa de simulado.</div>
            </div>

            <!-- Módulo 5 -->
            <div class="platform-card reveal">
                <div class="platform-card-header">
                    <div class="platform-icon-box" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H8V4h12v12z"/></svg>
                    </div>
                    <h3>Flashcards</h3>
                </div>
                <p>Revisão ativa por cartões com metodologia de repetição para consolidar marcos regulatórios, artigos de leis, autores pedagógicos e termos técnicos essenciais em Educação Especial.</p>
                <div class="platform-card-note">Fixação de conteúdos conceituais de alta incidência nas provas.</div>
            </div>

            <!-- Módulo 6 -->
            <div class="platform-card reveal">
                <div class="platform-card-header">
                    <div class="platform-icon-box" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
                    </div>
                    <h3>Tutor com Inteligência Artificial</h3>
                </div>
                <p>Assistente virtual de estudos integrado para esclarecer dúvidas teóricas e auxiliar na explicação de questões resolvidas. As orientações da IA são um apoio ao aprendizado e devem ser conferidas nas fontes oficiais.</p>
                <div class="platform-card-note">Apoio para síntese e explicação; respostas sujeitas à validação pelo aluno.</div>
            </div>

            <!-- Módulo 7 -->
            <div class="platform-card reveal">
                <div class="platform-card-header">
                    <div class="platform-icon-box" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                    </div>
                    <h3>Plano e painel de estudos</h3>
                </div>
                <p>Acompanhe visualmente a carga horária estudada, aulas concluídas e tarefas pendentes. Mantenha a disciplina de estudos com base em indicadores reais calculados diretamente pelo sistema.</p>
                <div class="platform-card-note">Visão clara do progresso acadêmico ao longo do período do curso.</div>
            </div>

            <!-- Módulo 8 -->
            <div class="platform-card reveal">
                <div class="platform-card-header">
                    <div class="platform-icon-box" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                    </div>
                    <h3>Materiais e apostilas</h3>
                </div>
                <p>Apostilas digitais, slides de aulas e resumos disponibilizados pelos professores, acessíveis por visualizador integrado no navegador. Os downloads em PDF dependem da permissão de cada material.</p>
                <div class="platform-card-note">Leitura contínua na própria sala de aula virtual com suporte a zoom.</div>
            </div>

            <!-- Módulo 9 -->
            <div class="platform-card reveal">
                <div class="platform-card-header">
                    <div class="platform-icon-box" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
                    </div>
                    <h3>Eventos e aulões</h3>
                </div>
                <p>Espaço para inscrição e participação em revisões de véspera, maratonas temáticas e transmissões especiais organizadas para os concursos atendidos pelo ISP.</p>
                <div class="platform-card-note">Inscrição nas modalidades presencial ou remota conforme o cronograma.</div>
            </div>

            <!-- Módulo 10 -->
            <div class="platform-card reveal">
                <div class="platform-card-header">
                    <div class="platform-icon-box" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                    </div>
                    <h3>Certificados com validação</h3>
                </div>
                <p>Emissão de certificados digitais para cursos e eventos que contemplem certificação acadêmica, com código verificador público para comprovação de carga horária cumprida.</p>
                <div class="platform-card-note">Disponível exclusivamente para formações com previsão de certificado.</div>
            </div>
        </div>

        <!-- Demonstração visual da sala de aula real -->
        <div class="hero-platform-mockup reveal" style="margin-top: 3.5rem;" aria-label="Visualização da sala de aula virtual">
            <div class="mockup-window-header">
                <div class="mockup-dots" aria-hidden="true">
                    <span class="dot-red"></span>
                    <span class="dot-yellow"></span>
                    <span class="dot-green"></span>
                </div>
                <div class="mockup-address-bar">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                    <span>aluno.isppreparatorios.com.br/aulas</span>
                </div>
                <div class="mockup-status-badge">
                    Sala de Aula Virtual
                </div>
            </div>
            <div class="mockup-screen-container">
                <img src="/img/plataforma/aula-desktop.png" alt="Interface real da sala de aula com player de vídeo e grade de disciplinas" loading="lazy">
            </div>
            <p class="mockup-caption">Player com controle de velocidade, lista de disciplinas e materiais integrados na mesma tela.</p>
        </div>
    </div>
</section>

<!-- 5. CATÁLOGO DE CURSOS E TURMAS (DINÂMICO DO BD) -->
<section class="section-padding" id="cursos" aria-labelledby="cursos-heading">
    <div class="container">
        <div class="section-header reveal" style="text-align: center;">
            <span class="hero-pre-title">CURSOS & TURMAS</span>
            <h2 id="cursos-heading">Escolha sua turma preparatória</h2>
            <p style="color: var(--text-secondary); max-width: 650px; margin: 1rem auto 0; font-size: 1.05rem;">
                Cursos desenhados com metodologia ativa e alinhados aos editais vigentes e previstos para as redes municipais e estaduais.
            </p>
        </div>
        
        <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr)); gap: 1.8rem;">
            <!-- Campanhas de Pré-Reserva Ativas -->
            <?php if(!empty($campanhas_destaque)): ?>
                <?php foreach($campanhas_destaque as $camp): 
                    $modality_str = 'Presencial e Online';
                    if ($camp['allows_presencial'] && !$camp['allows_online']) {
                        $modality_str = 'Presencial';
                    } elseif (!$camp['allows_presencial'] && $camp['allows_online']) {
                        $modality_str = 'Online';
                    }
                ?>
                <div class="feature-card reveal" style="display: flex; flex-direction: column; padding: 1.25rem;">
                    <div style="position: relative; margin-bottom: 1.2rem; border-radius: 8px; overflow: hidden; border: 1px solid var(--glass-border);">
                        <?php if(!empty($camp['image'])): ?>
                            <img src="/uploads/<?= htmlspecialchars($camp['image']) ?>" alt="<?= htmlspecialchars($camp['title']) ?>" style="width: 100%; aspect-ratio: 1/1; object-fit: cover; display: block;" loading="lazy">
                        <?php else: ?>
                            <div style="width: 100%; aspect-ratio: 1/1; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center;">
                                <span style="color: var(--text-secondary);">Sem Imagem</span>
                            </div>
                        <?php endif; ?>
                        
                        <div style="position: absolute; top: 10px; left: 10px; right: 10px; display: flex; gap: 0.4rem; flex-wrap: wrap;">
                            <span style="background: var(--brand-orange); color: #fff; font-size: 0.7rem; font-family: var(--font-mono); padding: 0.3rem 0.6rem; border-radius: 20px; font-weight: 700;">🚨 RESERVA</span>
                            <?php if(!empty($camp['city'])): ?>
                                <span style="background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(5px); color: #fff; font-size: 0.7rem; font-family: var(--font-mono); padding: 0.3rem 0.6rem; border-radius: 20px; font-weight: 500;">📍 <?= htmlspecialchars($camp['city']) ?></span>
                            <?php else: ?>
                                <span style="background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(5px); color: #fff; font-size: 0.7rem; font-family: var(--font-mono); padding: 0.3rem 0.6rem; border-radius: 20px; font-weight: 500;">📍 <?= htmlspecialchars($modality_str) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h3 style="font-size: 1.2rem; margin-bottom: 1rem; flex-grow: 1; line-height: 1.4;"><?= htmlspecialchars($camp['title']) ?></h3>
                    
                    <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--glass-border); padding-top: 1rem; gap: 0.5rem; flex-wrap: wrap;">
                        <span style="font-family: var(--font-mono); color: var(--brand-orange); font-size: 0.85rem; font-weight: 600;">Reserva Gratuita</span>
                        <a href="/reserva/<?= htmlspecialchars($camp['slug']) ?>" class="btn" style="flex: 1; min-width: 120px; text-align: center; padding: 0.7rem 1rem; font-size: 0.8rem; background: var(--brand-orange); color: #fff; border-color: var(--brand-orange);">Reservar interesse</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Cursos Regulares -->
            <?php foreach($cursos_destaque as $c): ?>
            <?php $is_reserva = ($c['status'] ?? 'Disponível') == 'Pegando Reserva'; ?>
            <div class="feature-card reveal" style="display: flex; flex-direction: column; padding: 1.25rem;">
                <div style="position: relative; margin-bottom: 1.2rem; border-radius: 8px; overflow: hidden; border: 1px solid var(--glass-border);">
                    <?php if(!empty($c['thumbnail'])): ?>
                        <img src="/uploads/<?= htmlspecialchars($c['thumbnail']) ?>" alt="<?= htmlspecialchars($c['image_alt'] ?: $c['title']) ?>" style="width: 100%; aspect-ratio: 1/1; object-fit: cover; display: block;" loading="lazy">
                    <?php else: ?>
                        <div style="width: 100%; aspect-ratio: 1/1; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center;">
                            <span style="color: var(--text-secondary);">Sem Imagem</span>
                        </div>
                    <?php endif; ?>
                    
                    <div style="position: absolute; top: 10px; left: 10px; right: 10px; display: flex; gap: 0.4rem; flex-wrap: wrap;">
                        <?php if($is_reserva): ?>
                            <span style="background: var(--brand-orange); color: #fff; font-size: 0.7rem; font-family: var(--font-mono); padding: 0.3rem 0.6rem; border-radius: 20px; font-weight: 700;">🚨 RESERVA</span>
                        <?php endif; ?>
                        <?php if(!empty($c['duration'])): ?>
                            <span style="background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(5px); color: #fff; font-size: 0.7rem; font-family: var(--font-mono); padding: 0.3rem 0.6rem; border-radius: 20px; font-weight: 500;">⏱ <?= htmlspecialchars($c['duration']) ?></span>
                        <?php endif; ?>
                        <span style="background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(5px); color: #fff; font-size: 0.7rem; font-family: var(--font-mono); padding: 0.3rem 0.6rem; border-radius: 20px; font-weight: 500;">📍 <?= htmlspecialchars($c['modality'] ?: 'Presencial e Online') ?></span>
                    </div>
                </div>
                
                <h3 style="font-size: 1.2rem; margin-bottom: 1rem; flex-grow: 1; line-height: 1.4;"><?= htmlspecialchars($c['title']) ?></h3>
                
                <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--glass-border); padding-top: 1rem; gap: 0.5rem; flex-wrap: wrap;">
                    <?php if($is_reserva): ?>
                        <span style="font-family: var(--font-mono); color: var(--brand-orange); font-size: 0.85rem; font-weight: 600;">Valores sob consulta</span>
                        <a href="<?= !empty($c['slug']) ? '/curso/'.$c['slug'] : 'curso-detalhes.php?id='.$c['id'] ?>" class="btn" style="flex: 1; min-width: 120px; text-align: center; padding: 0.7rem 1rem; font-size: 0.8rem; background: var(--brand-orange); color: #fff; border-color: var(--brand-orange);">Reservar interesse</a>
                    <?php else: ?>
                        <?php if($c['price'] > 0): ?>
                            <span style="font-family: var(--font-mono); color: var(--text-primary); font-size: 1.15rem; font-weight: 700;">R$ <?= number_format($c['price'], 2, ',', '.') ?></span>
                        <?php else: ?>
                            <span style="font-family: var(--font-mono); color: var(--brand-orange); font-size: 0.85rem; font-weight: 600;">Condições no curso</span>
                        <?php endif; ?>
                        <a href="<?= !empty($c['slug']) ? '/curso/'.$c['slug'] : 'curso-detalhes.php?id='.$c['id'] ?>" class="btn" style="flex: 1; min-width: 120px; text-align: center; padding: 0.7rem 1rem; font-size: 0.8rem;">Conhecer o curso</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="reveal" style="text-align: center; margin-top: 3.5rem;">
            <a href="cursos.php" class="btn btn-outline">Ver grade completa de cursos &rarr;</a>
        </div>
    </div>
</section>

<!-- 6. BANCO DE QUESTÕES EM DESTAQUE -->
<section class="section-padding" id="banco-questoes" style="background: rgba(2, 2, 58, 0.35); border-top: 1px solid var(--glass-border);" aria-labelledby="banco-heading">
    <div class="container">
        <div class="banco-highlight-box reveal">
            <div class="banco-grid">
                <div>
                    <span class="hero-pre-title" style="margin-bottom: 0.8rem;">PRÁTICA COM FOCO NA BANCA</span>
                    <h2 id="banco-heading" style="font-size: clamp(2rem, 4vw, 3rem); line-height: 1.15; margin-bottom: 1.2rem;">
                        Transforme questões em prática para o seu concurso.
                    </h2>
                    <p style="color: #cbd5e1; font-size: 1.05rem; line-height: 1.7; margin-bottom: 1.5rem;">
                        Resolva itens de provas anteriores organizados pelas principais bancas e disciplinas. Monte listas de treino, gere cadernos em PDF para estudo e acompanhe seu percentual de acerto.
                    </p>

                    <div class="banco-tag-list" aria-label="Bancas e filtros disponíveis">
                        <span class="banco-tag">FGV</span>
                        <span class="banco-tag">Cebraspe</span>
                        <span class="banco-tag">Vunesp</span>
                        <span class="banco-tag">Instituto Legatus</span>
                        <span class="banco-tag">FCC</span>
                        <span class="banco-tag">IBFC</span>
                        <span class="banco-tag">Educação Especial</span>
                        <span class="banco-tag">Português</span>
                        <span class="banco-tag">Legislação</span>
                    </div>

                    <ul class="banco-features-list">
                        <li>
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                            <span><strong>Filtros por banca, ano e disciplina:</strong> localize exatamente o que mais cai na sua prova sem perder tempo.</span>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                            <span><strong>Geração de cadernos em PDF:</strong> exporte listas organizadas para imprimir ou resolver offline, com controle de cotas mensais.</span>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                            <span><strong>Gabaritos e resoluções:</strong> gabaritos oficiais com comentários e videoaulas adicionados progressivamente nos itens cadastrados.</span>
                        </li>
                    </ul>

                    <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                        <a href="https://aluno.isppreparatorios.com.br" target="_blank" rel="noopener noreferrer" class="btn" style="background: var(--brand-orange); border-color: var(--brand-orange); color: #fff;">
                            Acessar o Banco de Questões
                        </a>
                        <a href="cursos.php" class="btn btn-outline">
                            Ver Cursos que incluem Questões
                        </a>
                    </div>
                </div>

                <div>
                    <div class="mockup-screen-container" style="box-shadow: 0 15px 40px rgba(0,0,0,0.6); border: 1px solid rgba(255,255,255,0.15); border-radius: 12px;">
                        <img src="/img/plataforma/lista-exercicios.png" alt="Tela do banco de questões com lista de exercícios e filtros no AVA" loading="lazy">
                    </div>
                    <p class="mockup-caption">Visualização da resolução de listas e filtros de questões no AVA.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 7. MODALIDADES DE ENSINO -->
<section class="section-padding" id="modalidades" aria-labelledby="modalidades-heading">
    <div class="container">
        <div class="section-header reveal" style="text-align: center;">
            <span class="hero-pre-title">FORMATOS DE ESTUDO</span>
            <h2 id="modalidades-heading">Presencial e Online: escolha seu formato</h2>
            <p style="color: var(--text-secondary); max-width: 650px; margin: 1rem auto 0; font-size: 1.05rem;">
                Duas modalidades estruturadas com o mesmo rigor pedagógico. Conheça as particularidades de cada formato para sua rotina.
            </p>
        </div>
        
        <div class="grid grid-modalidades" style="gap: 2rem;">
            <!-- Modalidade Presencial -->
            <div class="content-block reveal" style="width: 100%; max-width: none; border-radius: 16px; background: rgba(6, 8, 138, 0.45);">
                <span class="feature-id">IMERSÃO EM SALA</span>
                <h3 style="font-size: 1.6rem; color: #ffffff; margin-bottom: 0.5rem;">Aulas Presenciais</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1.5rem;">
                    Ideal para quem busca contato direto com os professores, compromisso de horário e ambiente concentrado em sala de aula em Caxias - MA.
                </p>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.8rem;">
                    <li style="display: flex; gap: 0.6rem; align-items: flex-start; color: #e2e8f0;">
                        <span style="color: var(--brand-orange); font-weight: bold;">✓</span>
                        Interação face a face com instrutores e esclarecimento imediato de pontos difíceis
                    </li>
                    <li style="display: flex; gap: 0.6rem; align-items: flex-start; color: #e2e8f0;">
                        <span style="color: var(--brand-orange); font-weight: bold;">✓</span>
                        Material didático impresso fornecido para acompanhamento das aulas em sala
                    </li>
                    <li style="display: flex; gap: 0.6rem; align-items: flex-start; color: #e2e8f0;">
                        <span style="color: var(--brand-orange); font-weight: bold;">✓</span>
                        Simulados presenciais aplicados com controle de tempo simulando o dia da prova
                    </li>
                    <li style="display: flex; gap: 0.6rem; align-items: flex-start; color: #e2e8f0;">
                        <span style="color: var(--brand-orange); font-weight: bold;">✓</span>
                        Turmas com vagas delimitadas para garantir atenção pedagógica individualizada
                    </li>
                </ul>
                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--glass-border); font-size: 0.82rem; color: rgba(255,255,255,0.6);">
                    * Os recursos digitais inclusos para alunos presenciais variam conforme o pacote da turma contratada.
                </div>
            </div>
            
            <!-- Modalidade Online -->
            <div class="content-block reveal" style="width: 100%; max-width: none; border-radius: 16px; background: rgba(6, 8, 138, 0.45);">
                <span class="feature-id">FLEXIBILIDADE DIGITAL</span>
                <h3 style="font-size: 1.6rem; color: #ffffff; margin-bottom: 0.5rem;">Aulas Online</h3>
                <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 1.5rem;">
                    Perfeito para quem precisa de flexibilidade para conciliar estudos com trabalho, acessando a plataforma quando e onde quiser.
                </p>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.8rem;">
                    <li style="display: flex; gap: 0.6rem; align-items: flex-start; color: #e2e8f0;">
                        <span style="color: var(--brand-orange); font-weight: bold;">✓</span>
                        Acesso integral às videoaulas gravadas com player inteligente e controle de velocidade
                    </li>
                    <li style="display: flex; gap: 0.6rem; align-items: flex-start; color: #e2e8f0;">
                        <span style="color: var(--brand-orange); font-weight: bold;">✓</span>
                        Aulas ao vivo pelo Google Meet com gravações disponibilizadas na plataforma
                    </li>
                    <li style="display: flex; gap: 0.6rem; align-items: flex-start; color: #e2e8f0;">
                        <span style="color: var(--brand-orange); font-weight: bold;">✓</span>
                        Apostilas e materiais complementares em PDF para visualização online e download
                    </li>
                    <li style="display: flex; gap: 0.6rem; align-items: flex-start; color: #e2e8f0;">
                        <span style="color: var(--brand-orange); font-weight: bold;">✓</span>
                        Acesso pelo celular, tablet ou computador, sem necessidade de instalar aplicativos
                    </li>
                </ul>
                <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--glass-border); font-size: 0.82rem; color: rgba(255,255,255,0.6);">
                    * Acesso liberado automaticamente na Área do Aluno logo após a confirmação do pagamento.
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 8. COMO COMEÇAR (3 ETAPAS DIDÁTICAS) -->
<section class="section-padding" id="como-comecar" style="background: rgba(2, 2, 58, 0.45); border-top: 1px solid var(--glass-border); border-bottom: 1px solid var(--glass-border);" aria-labelledby="como-comecar-heading">
    <div class="container">
        <div class="section-header reveal" style="text-align: center;">
            <span class="hero-pre-title">PASSO A PASSO</span>
            <h2 id="como-comecar-heading">Como iniciar sua preparação</h2>
            <p style="color: var(--text-secondary); max-width: 650px; margin: 1rem auto 0; font-size: 1.05rem;">
                Processo simples, transparente e sem burocracia para você focar no que realmente importa: estudar.
            </p>
        </div>

        <div class="steps-grid">
            <div class="step-card reveal">
                <div class="step-number">01</div>
                <h3>Escolha seu curso ou plano</h3>
                <p>Navegue pelo catálogo de cursos, turmas com pré-reserva aberta ou planos do banco de questões e escolha a opção alinhada ao cargo e à banca do seu concurso.</p>
                <div class="step-meta-badges">
                    <span class="step-badge">Presencial ou Online</span>
                    <span class="step-badge">Ed. Especial e Gerais</span>
                </div>
            </div>

            <div class="step-card reveal">
                <div class="step-number">02</div>
                <h3>Conclua a inscrição e o pagamento</h3>
                <p>Preencha seus dados cadastrais e efetue o pagamento de forma segura pelo checkout oficial. Para turmas em pré-reserva, a manifestação de interesse é 100% gratuita.</p>
                <div class="step-meta-badges">
                    <span class="step-badge">PIX com compensação imediata</span>
                    <span class="step-badge">Cartão de Crédito</span>
                </div>
            </div>

            <div class="step-card reveal">
                <div class="step-number">03</div>
                <h3>Acesse a Área do Aluno</h3>
                <p>Seu acesso é liberado automaticamente após a confirmação do pagamento. Você receberá um e-mail com o link seguro para cadastrar sua senha e começar a assistir às aulas.</p>
                <div class="step-meta-badges">
                    <span class="step-badge">Acesso web imediato</span>
                    <span class="step-badge">Suporte de segunda a sexta</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 9. PERGUNTAS FREQUENTES (FAQ ACESSÍVEL) -->
<section class="section-padding" id="faq" aria-labelledby="faq-heading">
    <div class="container">
        <div class="section-header reveal" style="text-align: center;">
            <span class="hero-pre-title">TIRE SUAS DÚVIDAS</span>
            <h2 id="faq-heading">Perguntas frequentes</h2>
            <p style="color: var(--text-secondary); max-width: 650px; margin: 1rem auto 0; font-size: 1.05rem;">
                Respostas claras e transparentes sobre o funcionamento dos cursos, pagamentos e acesso à plataforma.
            </p>
        </div>

        <div class="faq-wrapper reveal">
            <!-- 1 -->
            <details class="faq-item">
                <summary class="faq-summary">
                    <span>1. Como acesso minha compra?</span>
                    <span class="faq-icon" aria-hidden="true">&#9662;</span>
                </summary>
                <div class="faq-answer">
                    Após a confirmação do pagamento, você recebe um e-mail com as orientações de acesso. Se for seu primeiro curso no ISP, receberá um link seguro para definir sua senha na Área do Aluno (<a href="https://aluno.isppreparatorios.com.br" target="_blank" rel="noopener noreferrer" style="color: var(--brand-orange); text-decoration: underline;">aluno.isppreparatorios.com.br</a>). Caso já possua cadastro, basta entrar com seu e-mail e senha habituais e o novo conteúdo estará ativo na aba "Meus Cursos".
                </div>
            </details>

            <!-- 2 -->
            <details class="faq-item">
                <summary class="faq-summary">
                    <span>2. Posso estudar pelo celular?</span>
                    <span class="faq-icon" aria-hidden="true">&#9662;</span>
                </summary>
                <div class="faq-answer">
                    Sim. Nossa plataforma é totalmente responsiva e foi projetada para funcionar com comodidade no navegador do seu smartphone, tablet ou computador, sem necessidade de baixar aplicativos pesados nas lojas virtuais.
                </div>
            </details>

            <!-- 3 -->
            <details class="faq-item">
                <summary class="faq-summary">
                    <span>3. As aulas ao vivo ficam gravadas?</span>
                    <span class="faq-icon" aria-hidden="true">&#9662;</span>
                </summary>
                <div class="faq-answer">
                    Sim. Nas turmas que incluem encontros ao vivo pelo Google Meet, as gravações são disponibilizadas na sala de aula virtual da respectiva disciplina para que você possa assistir novamente ou repor a aula quantas vezes quiser durante a vigência da turma.
                </div>
            </details>

            <!-- 4 -->
            <details class="faq-item">
                <summary class="faq-summary">
                    <span>4. O banco de questões está incluído em todos os cursos?</span>
                    <span class="faq-icon" aria-hidden="true">&#9662;</span>
                </summary>
                <div class="faq-answer">
                    Os recursos disponíveis variam conforme o curso ou plano contratado. Determinados cursos já possuem o banco de questões integrado, enquanto outros incluem listas de exercícios direcionadas ou possibilitam a contratação avulsa do módulo de questões.
                </div>
            </details>

            <!-- 5 -->
            <details class="faq-item">
                <summary class="faq-summary">
                    <span>5. Todas as questões possuem comentários?</span>
                    <span class="faq-icon" aria-hidden="true">&#9662;</span>
                </summary>
                <div class="faq-answer">
                    Todas as questões cadastradas contam com gabarito oficial verificado. Comentários detalhados de professores e videoaulas explicativas são disponibilizados nos itens em que já foram gravados ou revisados. Além disso, o aluno pode utilizar a explicação assistida por inteligência artificial para questões resolvidas.
                </div>
            </details>

            <!-- 6 -->
            <details class="faq-item">
                <summary class="faq-summary">
                    <span>6. Como funciona o tutor com inteligência artificial?</span>
                    <span class="faq-icon" aria-hidden="true">&#9662;</span>
                </summary>
                <div class="faq-answer">
                    O tutor com inteligência artificial é uma ferramenta de apoio ao estudo disponível na plataforma para auxiliar no esclarecimento de conceitos e explicar alternativas de questões resolvidas. Ressaltamos de forma transparente que as respostas da IA servem como suporte e devem ser conferidas nas fontes e materiais normativos oficiais.
                </div>
            </details>

            <!-- 7 -->
            <details class="faq-item">
                <summary class="faq-summary">
                    <span>7. Posso gerar ou baixar PDFs?</span>
                    <span class="faq-icon" aria-hidden="true">&#9662;</span>
                </summary>
                <div class="faq-answer">
                    Sim. Os materiais didáticos disponibilizados pelos professores podem ser lidos diretamente no visualizador integrado e baixados quando a permissão de download estiver habilitada para aquele material. No banco de questões, você também pode gerar cadernos de exercícios em PDF para impressão, respeitando as cotas mensais de exportação do plano.
                </div>
            </details>

            <!-- 8 -->
            <details class="faq-item">
                <summary class="faq-summary">
                    <span>8. Por quanto tempo terei acesso?</span>
                    <span class="faq-icon" aria-hidden="true">&#9662;</span>
                </summary>
                <div class="faq-answer">
                    O período de acesso é definido nas especificações de cada turma ou produto (por exemplo, 6 meses, 12 meses ou até a data de realização da prova do concurso específico). A vigência exata é informada na página do curso e fica registrada no seu painel.
                </div>
            </details>

            <!-- 9 -->
            <details class="faq-item">
                <summary class="faq-summary">
                    <span>9. Quais são as formas de pagamento?</span>
                    <span class="faq-icon" aria-hidden="true">&#9662;</span>
                </summary>
                <div class="faq-answer">
                    Aceitamos pagamentos via PIX (com liberação automatizada do acesso assim que confirmado pelo gateway) e cartão de crédito, com opção de parcelamento conforme as condições exibidas no checkout de cada curso.
                </div>
            </details>

            <!-- 10 -->
            <details class="faq-item">
                <summary class="faq-summary">
                    <span>10. Os cursos oferecem certificado?</span>
                    <span class="faq-icon" aria-hidden="true">&#9662;</span>
                </summary>
                <div class="faq-answer">
                    A emissão de certificado digital é disponibilizada exclusivamente para cursos e eventos que contemplem essa previsão em sua proposta pedagógica. Quando aplicável, o certificado é emitido após o cumprimento da carga horária mínima e possui código público de autenticidade.
                </div>
            </details>
        </div>
    </div>
</section>

<!-- 10. DEPOIMENTOS (PRESERVADOS DO BANCO DE DADOS) -->
<?php if(!empty($depoimentos)): ?>
<section class="section-padding" style="background: rgba(2, 2, 58, 0.45); border-top: 1px solid var(--glass-border);" id="depoimentos" aria-labelledby="depoimentos-heading">
    <div class="container">
        <div class="section-header reveal" style="text-align: center;">
            <span class="hero-pre-title">DEPOIMENTOS</span>
            <h2 id="depoimentos-heading">Resultados e relatos de alunos</h2>
            <p style="color: var(--text-secondary); max-width: 600px; margin: 1rem auto 0; font-size: 1.05rem;">
                Experiências reais de estudantes que se prepararam com a equipe do ISP Preparatórios.
            </p>
        </div>
        
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr));">
            <?php foreach($depoimentos as $dep): ?>
            <div class="feature-card reveal" style="background: rgba(255,255,255,0.02);">
                <div style="color: var(--brand-orange); margin-bottom: 1rem; font-size: 1.2rem;" aria-label="Avaliação 5 estrelas">★★★★★</div>
                <p style="font-style: italic;">"<?= htmlspecialchars($dep['content']) ?>"</p>
                <div style="margin-top: 2rem; border-top: 1px solid var(--glass-border); padding-top: 1rem;">
                    <strong style="color: var(--text-primary); display: block;"><?= htmlspecialchars($dep['name']) ?></strong>
                    <span style="color: var(--text-secondary); font-size: 0.85rem;"><?= htmlspecialchars($dep['role']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 11. ENCERRAMENTO E CHAMADA FINAL (FINAL CTA) -->
<section class="final-cta" style="text-align: center; background: radial-gradient(circle at center, #06088a 0%, #03045e 100%); border-top: 1px solid var(--glass-border);" aria-labelledby="cta-final-heading">
    <div class="container" style="max-width: 850px;">
        <span class="hero-pre-title reveal" style="margin-bottom: 0.8rem;">INICIE SUA JORNADA</span>
        <h2 id="cta-final-heading" style="font-size: clamp(2rem, 5vw, 3.5rem); margin-bottom: 1.2rem; line-height: 1.2;" class="reveal">
            Escolha seu próximo passo na preparação.
        </h2>
        <p class="reveal" style="color: #cbd5e1; max-width: 650px; margin: 0 auto 2.5rem; font-size: 1.15rem; line-height: 1.7;">
            Una teoria direcionada, prática consistente com questões e acompanhamento docente para conquistar sua aprovação.
        </p>
        <div class="cta-group reveal" style="justify-content: center; gap: 1rem; flex-wrap: wrap;">
            <a href="cursos.php" class="btn">Ver cursos disponíveis</a>
            <a href="https://wa.me/559931999394" class="btn btn-outline" target="_blank" rel="noopener noreferrer">Falar com o ISP</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
