<?php
/**
 * Template de Landing Page Pública de Campanhas de Reserva / Lista de Interesse
 * ISP Preparatórios
 */

if (!isset($campaign)) {
    header("Location: /");
    exit;
}

// Meta SEO dinâmica para o header.php
$dynamic_title = !empty($campaign['meta_title']) ? $campaign['meta_title'] : ($campaign['title'] . " - Reserva de Vagas | ISP Preparatórios");
$dynamic_desc = !empty($campaign['meta_description']) ? $campaign['meta_description'] : (!empty($campaign['short_description']) ? strip_tags($campaign['short_description']) : "Garanta seu interesse e reserve sua vaga para a nova turma do ISP Preparatórios.");

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/reservation_service.php';

// Contagem de reservas para contador público
$stmtCountTotal = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE campaign_id = ? AND status != 'cancelado'");
$stmtCountTotal->execute([(int)$campaign['id']]);
$totalReservasAtuais = (int)$stmtCountTotal->fetchColumn();

// Contagem apenas de vagas principais regulares (sem contar lista de espera)
$stmtCountMain = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE campaign_id = ? AND is_waiting_list = 0 AND status != 'cancelado'");
$stmtCountMain->execute([(int)$campaign['id']]);
$totalVagasRegularesOcupadas = (int)$stmtCountMain->fetchColumn();

// Status e regras
$status = $campaign['status'];
$isLimitReached = ($campaign['max_reservations'] > 0 && $totalVagasRegularesOcupadas >= (int)$campaign['max_reservations']);
$isWaitingList = ($isLimitReached || $status === 'reservas_encerradas') && (bool)$campaign['allow_waiting_list'];
$isClosed = ($isLimitReached || $status === 'reservas_encerradas') && !$campaign['allow_waiting_list'];
$isEnrollmentOpen = ($status === 'matriculas_abertas');

$allowsPresencial = (bool)$campaign['allows_presencial'];
$allowsOnline = (bool)$campaign['allows_online'];

// Perguntas personalizadas (JSON)
$customFields = [];
if (!empty($campaign['custom_fields_json'])) {
    $decoded = json_decode($campaign['custom_fields_json'], true);
    if (is_array($decoded)) {
        $customFields = $decoded;
    }
}
?>

<main style="background: var(--obsidian-deep, #0a0e1a); color: #fff; min-height: 100vh; padding-bottom: 5rem;">
    <!-- Hero Section da Campanha -->
    <section style="padding: 6rem 5% 3rem; position: relative; border-bottom: 1px solid rgba(255,255,255,0.08); overflow: hidden;">
        <div style="position: absolute; top: -100px; right: -100px; width: 400px; height: 400px; background: radial-gradient(circle, rgba(255,128,0,0.15) 0%, rgba(3,4,94,0) 70%); filter: blur(50px); pointer-events: none;"></div>

        <div class="container" style="max-width: 1200px; margin: 0 auto; display: flex; flex-wrap: wrap; gap: 3.5rem; align-items: flex-start; position: relative; z-index: 2;">
            
            <!-- Coluna Esquerda: Informações da Campanha -->
            <div style="flex: 1 1 550px; min-width: 320px;">
                <!-- Badges de Status e Modalidades -->
                <div style="display: flex; flex-wrap: wrap; gap: 0.6rem; align-items: center; margin-bottom: 1.5rem;">
                    <?php if ($isEnrollmentOpen): ?>
                        <span style="background: rgba(40,167,69,0.2); color: #28a745; border: 1px solid #28a745; padding: 0.35rem 0.9rem; border-radius: 20px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;">
                            <i class="fas fa-check-circle"></i> Matrículas Abertas
                        </span>
                    <?php elseif ($isWaitingList): ?>
                        <span style="background: rgba(255,193,7,0.2); color: #ffc107; border: 1px solid #ffc107; padding: 0.35rem 0.9rem; border-radius: 20px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;">
                            <i class="fas fa-hourglass-half"></i> Lista de Espera Aberta
                        </span>
                    <?php elseif ($isClosed): ?>
                        <span style="background: rgba(220,53,69,0.2); color: #dc3545; border: 1px solid #dc3545; padding: 0.35rem 0.9rem; border-radius: 20px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;">
                            <i class="fas fa-lock"></i> Reservas Encerradas
                        </span>
                    <?php else: ?>
                        <span style="background: rgba(255,128,0,0.2); color: var(--brand-orange, #ff8000); border: 1px solid var(--brand-orange, #ff8000); padding: 0.35rem 0.9rem; border-radius: 20px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;">
                            <i class="fas fa-fire"></i> Lista de Interesse / Pré-Reserva
                        </span>
                    <?php endif; ?>

                    <!-- Modalidades Disponíveis -->
                    <?php if ($allowsPresencial && $allowsOnline): ?>
                        <span style="background: rgba(255,255,255,0.08); color: #eee; border: 1px solid rgba(255,255,255,0.15); padding: 0.35rem 0.9rem; border-radius: 20px; font-size: 0.8rem;">
                            <i class="fas fa-chalkboard-teacher"></i> Presencial + Online
                        </span>
                    <?php elseif ($allowsPresencial): ?>
                        <span style="background: rgba(255,255,255,0.08); color: #eee; border: 1px solid rgba(255,255,255,0.15); padding: 0.35rem 0.9rem; border-radius: 20px; font-size: 0.8rem;">
                            <i class="fas fa-building"></i> Modalidade Presencial
                        </span>
                    <?php elseif ($allowsOnline): ?>
                        <span style="background: rgba(255,255,255,0.08); color: #eee; border: 1px solid rgba(255,255,255,0.15); padding: 0.35rem 0.9rem; border-radius: 20px; font-size: 0.8rem;">
                            <i class="fas fa-laptop"></i> Modalidade Online (Ao Vivo)
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Título -->
                <h1 style="font-size: clamp(2rem, 4vw, 3.2rem); font-weight: 800; line-height: 1.2; margin-bottom: 1.2rem; color: #fff; letter-spacing: -0.5px;">
                    <?= htmlspecialchars($campaign['title']) ?>
                </h1>

                <!-- Contador Social de Interesse -->
                <?php if (!empty($campaign['show_counter']) && $totalReservasAtuais > 0): ?>
                    <div style="display: inline-flex; align-items: center; gap: 0.6rem; background: rgba(255,128,0,0.12); border: 1px solid rgba(255,128,0,0.3); padding: 0.5rem 1.1rem; border-radius: 30px; margin-bottom: 1.8rem;">
                        <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #ff8000; box-shadow: 0 0 10px #ff8000; animation: pulse 2s infinite;"></span>
                        <span style="color: #ffb774; font-size: 0.9rem; font-weight: 600;">
                            🔥 <strong><?= $totalReservasAtuais ?> pessoas</strong> já demonstraram interesse nesta turma!
                        </span>
                    </div>
                <?php endif; ?>

                <!-- Descrição Curta -->
                <?php if (!empty($campaign['short_description'])): ?>
                    <p style="font-size: 1.15rem; line-height: 1.7; color: rgba(255,255,255,0.85); margin-bottom: 2rem;">
                        <?= nl2br(htmlspecialchars($campaign['short_description'])) ?>
                    </p>
                <?php endif; ?>

                <!-- Grid de Destaques Informativos -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2.5rem;">
                    <?php if (!empty($campaign['class_start_date'])): ?>
                        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-left: 4px solid var(--brand-orange, #ff8000); padding: 1rem 1.2rem; border-radius: 0 8px 8px 0;">
                            <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #aaa; letter-spacing: 0.5px;">Previsão de Início</span>
                            <strong style="color: #fff; font-size: 1.1rem;"><?= date('d/m/Y', strtotime($campaign['class_start_date'])) ?></strong>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($campaign['city'])): ?>
                        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-left: 4px solid #00c3ff; padding: 1rem 1.2rem; border-radius: 0 8px 8px 0;">
                            <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #aaa; letter-spacing: 0.5px;">Localidade</span>
                            <strong style="color: #fff; font-size: 1.1rem;"><?= htmlspecialchars($campaign['city']) ?><?= !empty($campaign['state']) ? ' - ' . htmlspecialchars($campaign['state']) : '' ?></strong>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($campaign['target_audience'])): ?>
                        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-left: 4px solid #28a745; padding: 1rem 1.2rem; border-radius: 0 8px 8px 0;">
                            <span style="display: block; font-size: 0.75rem; text-transform: uppercase; color: #aaa; letter-spacing: 0.5px;">Público-Alvo</span>
                            <strong style="color: #fff; font-size: 1rem;"><?= htmlspecialchars($campaign['target_audience']) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Descrição Completa -->
                <?php if (!empty($campaign['description'])): ?>
                    <div class="campaign-description" style="font-size: 1.05rem; line-height: 1.8; color: rgba(255,255,255,0.85); margin-bottom: 2rem;">
                        <?= $campaign['description'] ?>
                    </div>
                <?php endif; ?>

                <!-- Aviso Importante -->
                <div style="background: rgba(3,4,94,0.3); border: 1px solid rgba(0,204,255,0.2); border-radius: 8px; padding: 1.2rem; margin-top: 2rem;">
                    <div style="display: flex; gap: 0.8rem; align-items: flex-start;">
                        <i class="fas fa-info-circle" style="color: #00c3ff; font-size: 1.3rem; margin-top: 0.2rem;"></i>
                        <p style="margin: 0; font-size: 0.9rem; color: #d0e4ff; line-height: 1.5;">
                            <strong>Atenção:</strong> A reserva de interesse <strong>não constitui matrícula definitiva nem exige pagamento antecipado</strong>. Ela serve para você garantir prioridade máxima na abertura das inscrições e condições promocionais exclusivas de primeiro lote.
                        </p>
                    </div>
                </div>

                <!-- Botão Futuro de Matrícula (Se ativado) -->
                <?php if ($isEnrollmentOpen && !empty($campaign['enrollment_link'])): ?>
                    <div style="margin-top: 2.5rem; text-align: center; background: rgba(40,167,69,0.1); border: 1px solid rgba(40,167,69,0.3); padding: 1.8rem; border-radius: 12px;">
                        <h3 style="color: #28a745; margin-bottom: 0.8rem;">🎉 As Matrículas Oficiais Estão Abertas!</h3>
                        <p style="color: #ccc; font-size: 0.95rem; margin-bottom: 1.2rem;">Garanta sua vaga definitiva antes que o lote vire.</p>
                        <a href="<?= htmlspecialchars($campaign['enrollment_link']) ?>" target="_blank" class="btn" style="background: #28a745; color: #fff; font-size: 1.1rem; font-weight: 700; padding: 0.9rem 2rem; border-radius: 6px; text-decoration: none; display: inline-block;">
                            <i class="fas fa-shopping-cart"></i> <?= htmlspecialchars($campaign['enrollment_button_text'] ?: 'Fazer Minha Matrícula Agora') ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Coluna Direita: Formulário de Reserva Sticky -->
            <div style="flex: 1 1 400px; max-width: 480px; width: 100%;">
                <div style="position: sticky; top: 100px; background: rgba(18, 24, 38, 0.95); border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.6); overflow: hidden; backdrop-filter: blur(10px);">
                    
                    <?php if (!empty($campaign['image'])): ?>
                        <div style="width: 100%; height: 180px; background: #000; overflow: hidden; position: relative;">
                            <img src="/uploads/<?= htmlspecialchars($campaign['image']) ?>" alt="<?= htmlspecialchars($campaign['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 60px; background: linear-gradient(to top, rgba(18,24,38,1), transparent);"></div>
                        </div>
                    <?php endif; ?>

                    <div style="padding: 2rem 1.8rem;">
                        <div style="text-align: center; margin-bottom: 1.5rem;">
                            <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.4rem; color: #fff;">
                                <?= $isWaitingList ? 'Entrar na Lista de Espera' : 'Reserve Sua Vaga' ?>
                            </h2>
                            <p style="color: #aaa; font-size: 0.85rem; margin: 0;">
                                <?= $isWaitingList ? 'As vagas principais estão preenchidas. Registre-se para ser chamado em desistências.' : 'Preencha os campos abaixo para garantir prioridade e descontos exclusivos.' ?>
                            </p>
                        </div>

                        <?php if ($isClosed): ?>
                            <div style="background: rgba(220,53,69,0.15); border: 1px solid #dc3545; border-radius: 8px; padding: 1.5rem; text-align: center;">
                                <i class="fas fa-times-circle" style="font-size: 2.5rem; color: #dc3545; margin-bottom: 0.8rem;"></i>
                                <h3 style="color: #fff; font-size: 1.2rem; margin-bottom: 0.5rem;">Inscrições Encerradas</h3>
                                <p style="color: #ccc; font-size: 0.9rem; margin-bottom: 1rem;">O limite de reservas para esta turma foi atingido e a lista de espera foi finalizada.</p>
                                <a href="/" class="btn" style="background: var(--brand-orange, #ff8000); color: #fff; text-decoration: none; padding: 0.6rem 1.2rem; border-radius: 4px; font-size: 0.85rem;">Conhecer Outras Turmas</a>
                            </div>
                        <?php else: ?>

                            <!-- Formulário de Reserva com AJAX -->
                            <form id="formReserva" style="display: flex; flex-direction: column; gap: 1rem;">
                                <input type="hidden" name="campaign_id" value="<?= (int)$campaign['id'] ?>">
                                <input type="hidden" name="campaign_slug" value="<?= htmlspecialchars($campaign['slug']) ?>">

                                <!-- Honeypot Anti-Spam (invisível para humanos) -->
                                <div style="display:none !important;" aria-hidden="true">
                                    <input type="text" name="website_url_check" tabindex="-1" autocomplete="off">
                                    <input type="text" name="website_hp" tabindex="-1" autocomplete="off">
                                </div>

                                <!-- Captura automática de UTMs da URL -->
                                <input type="hidden" name="utm_source" id="utm_source">
                                <input type="hidden" name="utm_medium" id="utm_medium">
                                <input type="hidden" name="utm_campaign" id="utm_campaign">
                                <input type="hidden" name="utm_content" id="utm_content">
                                <input type="hidden" name="utm_term" id="utm_term">

                                <!-- Nome Completo -->
                                <div>
                                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.3rem; color: #eee;">Nome Completo *</label>
                                    <input type="text" name="name" required class="form-control" placeholder="Seu nome completo" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 0.75rem 0.9rem; border-radius: 6px; width: 100%; box-sizing: border-box; font-size: 0.95rem;">
                                </div>

                                <!-- WhatsApp -->
                                <div>
                                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.3rem; color: #eee;">WhatsApp com DDD *</label>
                                    <input type="tel" name="phone" id="phone_field" required class="form-control" placeholder="(99) 99999-9999" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 0.75rem 0.9rem; border-radius: 6px; width: 100%; box-sizing: border-box; font-size: 0.95rem;">
                                </div>

                                <!-- E-mail -->
                                <div>
                                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.3rem; color: #eee;">Seu Melhor E-mail *</label>
                                    <input type="email" name="email" required class="form-control" placeholder="exemplo@email.com" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 0.75rem 0.9rem; border-radius: 6px; width: 100%; box-sizing: border-box; font-size: 0.95rem;">
                                </div>

                                <!-- Cidade e Estado -->
                                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 0.8rem;">
                                    <div>
                                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.3rem; color: #eee;">Cidade</label>
                                        <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($campaign['city']) ?>" placeholder="Sua cidade" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 0.75rem 0.9rem; border-radius: 6px; width: 100%; box-sizing: border-box; font-size: 0.95rem;">
                                    </div>
                                    <div>
                                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.3rem; color: #eee;">UF</label>
                                        <input type="text" name="state" maxlength="2" class="form-control" value="<?= htmlspecialchars($campaign['state']) ?>" placeholder="MA" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 0.75rem 0.9rem; border-radius: 6px; width: 100%; box-sizing: border-box; font-size: 0.95rem; text-transform: uppercase;">
                                    </div>
                                </div>

                                <!-- Escolha da Modalidade (Somente exibida se a campanha oferecer ambas) -->
                                <?php if ($allowsPresencial && $allowsOnline): ?>
                                    <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); padding: 1rem; border-radius: 8px;">
                                        <label style="display: block; font-size: 0.9rem; font-weight: 700; margin-bottom: 0.6rem; color: var(--brand-orange, #ff8000);">
                                            <i class="fas fa-question-circle"></i> Como você pretende participar? *
                                        </label>
                                        <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                            <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.9rem; cursor: pointer;">
                                                <input type="radio" name="preferred_modality" value="presencial" required checked style="accent-color: var(--brand-orange, #ff8000);">
                                                <span><strong>🏫 Presencial</strong> (em sala de aula)</span>
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.9rem; cursor: pointer;">
                                                <input type="radio" name="preferred_modality" value="online" style="accent-color: var(--brand-orange, #ff8000);">
                                                <span><strong>💻 Online</strong> (transmissão ao vivo / plataforma)</span>
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 0.6rem; font-size: 0.9rem; cursor: pointer;">
                                                <input type="radio" name="preferred_modality" value="ambas" style="accent-color: var(--brand-orange, #ff8000);">
                                                <span><strong>⭐ Tenho interesse nas duas modalidades</strong></span>
                                            </label>
                                        </div>
                                    </div>
                                <?php elseif ($allowsPresencial): ?>
                                    <input type="hidden" name="preferred_modality" value="presencial">
                                <?php elseif ($allowsOnline): ?>
                                    <input type="hidden" name="preferred_modality" value="online">
                                <?php else: ?>
                                    <input type="hidden" name="preferred_modality" value="presencial">
                                <?php endif; ?>

                                <!-- Perguntas Customizadas Opcionais da Campanha -->
                                <?php if (!empty($customFields)): ?>
                                    <?php foreach ($customFields as $fKey => $fConfig): ?>
                                        <div>
                                            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.3rem; color: #eee;">
                                                <?= htmlspecialchars($fConfig['label'] ?? $fKey) ?>
                                            </label>
                                            <?php if (!empty($fConfig['options']) && is_array($fConfig['options'])): ?>
                                                <select name="custom_<?= htmlspecialchars($fKey) ?>" class="form-control" style="background: #1a2233; border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 0.75rem 0.9rem; border-radius: 6px; width: 100%;">
                                                    <option value="">Selecione...</option>
                                                    <?php foreach ($fConfig['options'] as $opt): ?>
                                                        <option value="<?= htmlspecialchars($opt) ?>"><?= htmlspecialchars($opt) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else: ?>
                                                <input type="text" name="custom_<?= htmlspecialchars($fKey) ?>" class="form-control" placeholder="<?= htmlspecialchars($fConfig['placeholder'] ?? '') ?>" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 0.75rem 0.9rem; border-radius: 6px; width: 100%;">
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <!-- Termo de Consentimento LGPD -->
                                <div style="display: flex; align-items: flex-start; gap: 0.6rem; font-size: 0.8rem; color: #aaa; margin-top: 0.5rem;">
                                    <input type="checkbox" name="consent_privacy" id="consent_privacy" required checked style="accent-color: var(--brand-orange, #ff8000); margin-top: 0.2rem; cursor: pointer;">
                                    <label for="consent_privacy" style="cursor: pointer; line-height: 1.4;">
                                        Autorizo o ISP Preparatórios a utilizar meus dados para entrar em contato comigo sobre esta reserva, abertura da turma, matrícula e informações pedagógicas, conforme a <a href="/privacidade.php" target="_blank" style="color: var(--brand-orange, #ff8000); text-decoration: underline;">Política de Privacidade</a>.
                                    </label>
                                </div>

                                <!-- Feedback e Mensagens -->
                                <div id="formFeedback" style="display: none; padding: 0.8rem 1rem; border-radius: 6px; font-size: 0.9rem; margin-top: 0.5rem;"></div>

                                <!-- Botão de Submissão -->
                                <button type="submit" id="btnSubmitReserva" class="btn" style="background: var(--brand-orange, #ff8000); color: #fff; font-size: 1.05rem; font-weight: 700; padding: 0.9rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; text-transform: uppercase; letter-spacing: 0.5px; transition: all 0.2s ease; margin-top: 0.5rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                    <span><?= $isWaitingList ? 'Entrar na Lista de Espera' : 'Quero Participar Desta Turma' ?></span>
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </form>

                            <!-- Painel de Confirmação e Sucesso (Exibido após envio com sucesso) -->
                            <div id="painelSucesso" style="display: none; text-align: center; padding: 1.5rem 0;">
                                <div style="width: 70px; height: 70px; background: rgba(40,167,69,0.2); border: 2px solid #28a745; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.2rem;">
                                    <i class="fas fa-check" style="font-size: 2rem; color: #28a745;"></i>
                                </div>
                                <h3 id="sucessoTitulo" style="color: #fff; font-size: 1.4rem; font-weight: 700; margin-bottom: 0.5rem;">Reserva Realizada com Sucesso!</h3>
                                <p id="sucessoMsg" style="color: #ccc; font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;">
                                    Recebemos seu interesse em participar desta turma. A equipe do ISP Preparatórios entrará em contato pelo WhatsApp para informar sobre abertura das matrículas, cronograma e valores.
                                </p>

                                <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; text-align: left;">
                                    <div style="font-size: 0.85rem; color: #aaa; margin-bottom: 0.3rem;">Protocolo: <strong id="sucessoProtocolo" style="color: #fff;"></strong></div>
                                    <div style="font-size: 0.85rem; color: #aaa; margin-bottom: 0.3rem;">Campanha: <strong id="sucessoCampanha" style="color: #fff;"></strong></div>
                                    <div style="font-size: 0.85rem; color: #aaa;">Modalidade: <strong id="sucessoModalidade" style="color: var(--brand-orange, #ff8000);"></strong></div>
                                </div>

                                <a id="btnWhatsAppISP" href="#" target="_blank" class="btn" style="background: #25d366; color: #fff; text-decoration: none; padding: 0.8rem 1.4rem; border-radius: 6px; font-weight: 700; display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.95rem; margin-bottom: 1rem;">
                                    <i class="fab fa-whatsapp" style="font-size: 1.2rem;"></i> Falar com o ISP pelo WhatsApp
                                </a>

                                <div>
                                    <button type="button" onclick="window.location.reload();" style="background: transparent; border: none; color: #888; text-decoration: underline; cursor: pointer; font-size: 0.85rem;">
                                        Registrar outro interesse
                                    </button>
                                </div>
                            </div>

                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Captura de parâmetros UTM da URL e armazenamento em sessionStorage para persistência
    const params = new URLSearchParams(window.location.search);
    const utmFields = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];
    
    utmFields.forEach(field => {
        let val = params.get(field);
        if (val) {
            sessionStorage.setItem(field, val);
        } else {
            val = sessionStorage.getItem(field) || '';
        }
        const inputEl = document.getElementById(field);
        if (inputEl) inputEl.value = val;
    });

    // 2. Máscara amigável para telefone/WhatsApp
    const phoneInput = document.getElementById('phone_field');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.substring(0, 11);
            if (v.length > 10) {
                e.target.value = '(' + v.substring(0, 2) + ') ' + v.substring(2, 7) + '-' + v.substring(7, 11);
            } else if (v.length > 6) {
                e.target.value = '(' + v.substring(0, 2) + ') ' + v.substring(2, 6) + '-' + v.substring(6, 10);
            } else if (v.length > 2) {
                e.target.value = '(' + v.substring(0, 2) + ') ' + v.substring(2);
            } else {
                e.target.value = v;
            }
        });
    }

    // 3. Submissão via AJAX
    const form = document.getElementById('formReserva');
    const feedback = document.getElementById('formFeedback');
    const btnSubmit = document.getElementById('btnSubmitReserva');
    const painelSucesso = document.getElementById('painelSucesso');

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            feedback.style.display = 'none';
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Registrando...</span>';

            const formData = new FormData(form);

            fetch('/ajax_reservation.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<span><?= $isWaitingList ? 'Entrar na Lista de Espera' : 'Quero Participar Desta Turma' ?></span> <i class="fas fa-arrow-right"></i>';

                if (data.success) {
                    form.style.display = 'none';
                    painelSucesso.style.display = 'block';

                    if (data.already_registered) {
                        document.getElementById('sucessoTitulo').innerText = 'Você Já Possui Reserva!';
                        document.getElementById('sucessoMsg').innerText = data.message || 'Seu interesse já está cadastrado nesta turma. Nossa equipe entrará em contato em breve.';
                    } else {
                        document.getElementById('sucessoTitulo').innerText = data.message || 'Reserva Realizada com Sucesso!';
                    }

                    document.getElementById('sucessoProtocolo').innerText = data.protocol || ('ISP-' + (data.reservation_id || '0001'));
                    document.getElementById('sucessoCampanha').innerText = data.campaign_title || '<?= addslashes($campaign['title']) ?>';
                    
                    const modMap = {'presencial': 'Presencial', 'online': 'Online', 'ambas': 'Presencial + Online (Ambas)'};
                    document.getElementById('sucessoModalidade').innerText = modMap[data.preferred_modality] || data.preferred_modality || 'Presencial';

                    if (data.whatsapp_contact_url) {
                        document.getElementById('btnWhatsAppISP').href = data.whatsapp_contact_url;
                    }
                } else {
                    feedback.style.display = 'block';
                    feedback.style.background = 'rgba(220,53,69,0.2)';
                    feedback.style.border = '1px solid #dc3545';
                    feedback.style.color = '#ff8888';
                    feedback.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Ocorreu um erro ao registrar sua reserva.');
                }
            })
            .catch(err => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<span><?= $isWaitingList ? 'Entrar na Lista de Espera' : 'Quero Participar Desta Turma' ?></span> <i class="fas fa-arrow-right"></i>';
                feedback.style.display = 'block';
                feedback.style.background = 'rgba(220,53,69,0.2)';
                feedback.style.border = '1px solid #dc3545';
                feedback.style.color = '#ff8888';
                feedback.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Erro de comunicação com o servidor. Por favor, tente novamente.';
            });
        });
    }
});
</script>

<style>
@keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 128, 0, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(255, 128, 0, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 128, 0, 0); }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
