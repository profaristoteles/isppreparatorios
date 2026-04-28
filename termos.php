<?php
require_once 'db_config.php';
require_once 'includes/header.php';
$site_config = get_config($pdo);
$empresa = 'ISP PREPARATÓRIOS';
$email = htmlspecialchars($site_config['email'] ?? 'contato@isppreparatorios.com.br');
$phone = htmlspecialchars($site_config['phone'] ?? '');
?>

<div class="container section-padding" style="max-width: 900px;">
    <div class="section-header reveal" style="text-align: center;">
        <span class="hero-pre-title">DOCUMENTO LEGAL</span>
        <h2>Termos de Uso</h2>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Última atualização: <?= date('d/m/Y') ?></p>
    </div>

    <div class="content-block reveal" style="padding: 3rem; line-height: 1.8; color: var(--text-secondary);">

        <h3 style="color: var(--brand-orange); margin-bottom: 1rem;">1. Termos Gerais</h3>
        <p>Ao acessar e utilizar o site <strong style="color: #fff;"><?= $empresa ?></strong>, você concorda integralmente com os presentes Termos de Uso. Caso não concorde com alguma disposição aqui prevista, recomendamos que não utilize nossos serviços.</p>
        <p>Estes termos podem ser atualizados a qualquer momento, sem aviso prévio, sendo de responsabilidade do usuário verificar periodicamente as atualizações.</p>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">2. Dos Serviços</h3>
        <p>O <?= $empresa ?> é uma plataforma educacional voltada à preparação para concursos públicos, que oferece:</p>
        <ul>
            <li>Cursos preparatórios presenciais e online;</li>
            <li>Apostilas e materiais didáticos digitais;</li>
            <li>Conteúdos informativos via blog;</li>
            <li>Atendimento e suporte ao aluno via WhatsApp e outros canais.</li>
        </ul>
        <p>Os serviços são ofertados conforme disponibilidade e podem ser alterados, suspensos ou encerrados a qualquer momento, sem necessidade de aviso prévio.</p>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">3. Do Cadastro e Inscrição</h3>
        <p>Para acessar determinados serviços, o usuário deverá fornecer informações pessoais verídicas e completas por meio dos formulários disponibilizados. O usuário se compromete a:</p>
        <ul>
            <li>Fornecer dados verdadeiros, precisos e atualizados;</li>
            <li>Não utilizar dados de terceiros sem autorização;</li>
            <li>Manter seus dados cadastrais atualizados;</li>
            <li>Zelar pela confidencialidade de suas credenciais de acesso, se aplicável.</li>
        </ul>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">4. Dos Pagamentos e Reembolsos</h3>
        <p>Os valores dos cursos e materiais estão sujeitos a alteração sem aviso prévio. Os pagamentos são processados por plataformas de terceiros (gateways de pagamento), sendo de responsabilidade do usuário verificar as condições antes da compra.</p>
        <ul>
            <li><strong style="color: #fff;">Reembolso:</strong> O aluno poderá solicitar reembolso integral em até 7 (sete) dias corridos após a data da compra, conforme previsto no Art. 49 do Código de Defesa do Consumidor, desde que não tenha consumido mais de 20% do conteúdo disponibilizado.</li>
            <li><strong style="color: #fff;">Cancelamento:</strong> A solicitação de cancelamento deve ser feita por e-mail para <?= $email ?>.</li>
        </ul>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">5. Da Propriedade Intelectual</h3>
        <p>Todo o conteúdo disponibilizado no site e nas plataformas do <?= $empresa ?>, incluindo, mas não se limitando a: textos, imagens, vídeos, apostilas, logotipos, layouts e códigos, são de propriedade exclusiva do <?= $empresa ?> ou de seus parceiros, protegidos pelas leis de direitos autorais e propriedade industrial.</p>
        <p>É <strong style="color: #fff;">expressamente proibido</strong>:</p>
        <ul>
            <li>Reproduzir, copiar, distribuir ou comercializar qualquer conteúdo sem autorização prévia;</li>
            <li>Compartilhar acesso a cursos ou materiais com terceiros;</li>
            <li>Utilizar o conteúdo para fins comerciais sem consentimento por escrito;</li>
            <li>Gravar, filmar ou capturar telas de aulas e materiais.</li>
        </ul>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">6. Das Responsabilidades do Usuário</h3>
        <p>O usuário se compromete a:</p>
        <ul>
            <li>Utilizar a plataforma de forma ética e em conformidade com a legislação brasileira;</li>
            <li>Não utilizar a plataforma para disseminar conteúdos ilegais, ofensivos ou discriminatórios;</li>
            <li>Não tentar acessar áreas restritas da plataforma sem autorização;</li>
            <li>Não realizar qualquer tipo de engenharia reversa, hacking ou atividades que comprometam a segurança do sistema.</li>
        </ul>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">7. Da Limitação de Responsabilidade</h3>
        <p>O <?= $empresa ?> não se responsabiliza por:</p>
        <ul>
            <li>Resultados individuais em concursos públicos, sendo o desempenho dependente do esforço pessoal de cada aluno;</li>
            <li>Interrupções temporárias no acesso ao site por manutenção, falhas técnicas ou eventos de força maior;</li>
            <li>Atos praticados por terceiros que afetem a experiência do usuário;</li>
            <li>Alterações em editais de concursos, cronogramas ou legislações após a publicação de conteúdos.</li>
        </ul>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">8. Da Política de Privacidade</h3>
        <p>O tratamento de dados pessoais é regido pela nossa <a href="privacidade.php" style="color: var(--prism-cyan);">Política de Privacidade</a>, que é parte integrante destes Termos de Uso. Ao aceitar estes termos, o usuário também declara estar ciente e de acordo com a referida política.</p>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">9. Do Foro</h3>
        <p>Para a solução de controvérsias decorrentes do presente instrumento será aplicado integralmente o Direito brasileiro. Fica eleito o Foro da Comarca onde se encontra a sede do <?= $empresa ?> para dirimir eventuais questões oriundas destes Termos de Uso.</p>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">10. Contato</h3>
        <p>Em caso de dúvidas sobre estes Termos de Uso, entre em contato:</p>
        <ul>
            <li><strong style="color: #fff;">E-mail:</strong> <?= $email ?></li>
            <?php if(!empty($phone)): ?>
            <li><strong style="color: #fff;">Telefone/WhatsApp:</strong> <?= $phone ?></li>
            <?php endif; ?>
        </ul>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
