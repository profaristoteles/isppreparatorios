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
        <h2>Política de Privacidade</h2>
        <p style="color: var(--text-secondary); font-size: 0.9rem;">Última atualização: <?= date('d/m/Y') ?></p>
    </div>

    <div class="content-block reveal" style="padding: 3rem; line-height: 1.8; color: var(--text-secondary);">

        <h3 style="color: var(--brand-orange); margin-bottom: 1rem;">1. Informações Gerais</h3>
        <p>A presente Política de Privacidade contém informações sobre coleta, uso, armazenamento, tratamento e proteção dos dados pessoais dos usuários e visitantes do site <strong style="color: #fff;"><?= $empresa ?></strong>, com a finalidade de demonstrar absoluta transparência quanto ao assunto e esclarecer a todos interessados sobre os tipos de dados que são coletados, os motivos da coleta e a forma como os usuários podem gerenciar ou excluir as suas informações pessoais.</p>
        <p>Esta política se aplica a todos os usuários e visitantes do nosso site e integra os <a href="termos.php" style="color: var(--prism-cyan);">Termos de Uso</a>.</p>
        <p>O documento foi elaborado em conformidade com a <strong style="color: #fff;">Lei Geral de Proteção de Dados Pessoais (Lei nº 13.709/2018 — LGPD)</strong>, o Marco Civil da Internet (Lei nº 12.965/2014) e o Regulamento da UE nº 2016/679 (GDPR), quando aplicável.</p>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">2. Dados Coletados</h3>
        <p>Os dados pessoais do usuário e visitante são recolhidos pela plataforma da seguinte forma:</p>
        <ul>
            <li><strong style="color: #fff;">Formulários de contato e inscrição:</strong> quando o usuário preenche formulários disponibilizados na plataforma, incluindo nome completo, e-mail, telefone/WhatsApp e curso de interesse.</li>
            <li><strong style="color: #fff;">Dados de navegação:</strong> quando o usuário acessa o nosso site, são coletadas automaticamente informações como endereço IP, tipo de navegador, páginas acessadas, tempo de visita, entre outros dados técnicos.</li>
            <li><strong style="color: #fff;">Cookies:</strong> utilizamos cookies para melhorar a experiência de navegação. O usuário pode, a qualquer momento, bloquear o uso de cookies por meio das configurações do navegador.</li>
        </ul>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">3. Finalidade do Tratamento dos Dados</h3>
        <p>Os dados pessoais do usuário e do visitante coletados e armazenados pelo <?= $empresa ?> têm as seguintes finalidades:</p>
        <ul>
            <li>Enviar informações sobre cursos, materiais didáticos e promoções;</li>
            <li>Entrar em contato para esclarecimento de dúvidas e suporte ao aluno;</li>
            <li>Melhorar a experiência do usuário na plataforma;</li>
            <li>Cumprir obrigações legais e regulatórias;</li>
            <li>Realizar análises estatísticas e de desempenho do site;</li>
            <li>Enviar comunicações de marketing, desde que o usuário tenha consentido previamente.</li>
        </ul>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">4. Tempo de Armazenamento</h3>
        <p>Os dados pessoais do usuário e visitante são armazenados pela plataforma durante o período necessário para a prestação do serviço ou o cumprimento das finalidades previstas neste documento, observando o prazo de prescrição estabelecido na legislação vigente.</p>
        <p>Os dados podem ser removidos ou anonimizados a pedido do usuário, excetuando os casos em que a lei oferecer outro tratamento.</p>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">5. Segurança dos Dados</h3>
        <p>A plataforma se compromete a aplicar as medidas técnicas e organizativas aptas a proteger os dados pessoais de acessos não autorizados e de situações de destruição, perda, alteração, comunicação ou difusão de tais dados.</p>
        <p>A plataforma se exime de responsabilidade por culpa exclusiva de terceiro, como em caso de ataque de hackers ou crackers, ou culpa exclusiva do usuário, como no caso em que ele mesmo transfere seus dados a terceiros.</p>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">6. Compartilhamento de Dados</h3>
        <p>O compartilhamento de dados do usuário ocorre apenas com os dados necessários para a execução dos serviços contratados, como:</p>
        <ul>
            <li>Plataformas de pagamento (para processamento financeiro);</li>
            <li>Ferramentas de comunicação e marketing (para envio de e-mails e mensagens);</li>
            <li>Serviços de hospedagem e infraestrutura tecnológica.</li>
        </ul>
        <p>Em nenhuma hipótese os dados pessoais serão vendidos ou comercializados a terceiros.</p>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">7. Direitos do Titular dos Dados</h3>
        <p>O titular dos dados pessoais tem o direito de, a qualquer momento, solicitar ao <?= $empresa ?>:</p>
        <ul>
            <li>Confirmação da existência de tratamento de dados pessoais;</li>
            <li>Acesso aos dados pessoais coletados;</li>
            <li>Correção de dados incompletos, inexatos ou desatualizados;</li>
            <li>Anonimização, bloqueio ou eliminação de dados desnecessários;</li>
            <li>Portabilidade dos dados a outro fornecedor de serviço;</li>
            <li>Eliminação dos dados pessoais tratados com consentimento;</li>
            <li>Revogação do consentimento.</li>
        </ul>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">8. Consentimento</h3>
        <p>Ao utilizar os serviços e fornecer as informações pessoais na plataforma, o usuário está consentindo com a presente Política de Privacidade. O usuário, ao se cadastrar, manifesta conhecer e pode exercitar seus direitos de cancelar seu cadastro, acessar e atualizar seus dados pessoais e garante a veracidade das informações por ele disponibilizadas.</p>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">9. Alterações na Política</h3>
        <p>A presente Política de Privacidade pode ser atualizada a qualquer momento. Recomendamos que o usuário revise periodicamente esta página para se manter informado sobre possíveis alterações.</p>

        <h3 style="color: var(--brand-orange); margin-top: 2.5rem; margin-bottom: 1rem;">10. Contato</h3>
        <p>Para esclarecer quaisquer dúvidas sobre esta Política de Privacidade ou sobre o tratamento de dados pessoais, entre em contato conosco:</p>
        <ul>
            <li><strong style="color: #fff;">E-mail:</strong> <?= $email ?></li>
            <?php if(!empty($phone)): ?>
            <li><strong style="color: #fff;">Telefone/WhatsApp:</strong> <?= $phone ?></li>
            <?php endif; ?>
        </ul>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
