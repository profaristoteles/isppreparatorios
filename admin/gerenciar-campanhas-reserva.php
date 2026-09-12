<?php
/**
 * Gerenciamento de Campanhas de Reserva / Lista de Interesse
 * ISP Preparatórios
 */

require_once 'auth.php';
require_once '../db_config.php';
require_once 'includes/admin_security.php';
require_once '../includes/reservation_service.php';

$statusLabels = ReservationService::getCampaignStatusLabels();

// Processar Ações (Salvar / Editar / Excluir / Alternar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
            $slug = trim($slug, '-');
        }

        $short_description = trim($_POST['short_description'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = strtoupper(trim($_POST['state'] ?? ''));
        $location = trim($_POST['location'] ?? '');
        $target_audience = trim($_POST['target_audience'] ?? '');
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $class_start_date = !empty($_POST['class_start_date']) ? $_POST['class_start_date'] : null;
        $max_reservations = max(0, (int)($_POST['max_reservations'] ?? 0));
        $show_counter = !empty($_POST['show_counter']) ? 1 : 0;
        $allow_waiting_list = !empty($_POST['allow_waiting_list']) ? 1 : 0;
        
        $allows_presencial = !empty($_POST['allows_presencial']) ? 1 : 0;
        $allows_online = !empty($_POST['allows_online']) ? 1 : 0;
        
        // Pelo menos uma modalidade deve estar disponível
        if (!$allows_presencial && !$allows_online) {
            $_SESSION['erro'] = "A campanha deve permitir ao menos uma modalidade (Presencial ou Online).";
            header("Location: gerenciar-campanhas-reserva.php");
            exit;
        }

        $status = $_POST['status'] ?? 'reservas_abertas';
        if (!array_key_exists($status, $statusLabels)) {
            $status = 'reservas_abertas';
        }

        $enrollment_link = trim($_POST['enrollment_link'] ?? '');
        $enrollment_button_text = trim($_POST['enrollment_button_text'] ?? 'Fazer Minha Matrícula');
        $meta_title = trim($_POST['meta_title'] ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $custom_fields_json = trim($_POST['custom_fields_json'] ?? '');

        // Validar se JSON de campos customizados é válido, se preenchido
        if (!empty($custom_fields_json)) {
            $jsonTest = json_decode($custom_fields_json, true);
            if ($jsonTest === null) {
                $_SESSION['erro'] = "O formato das perguntas customizadas não é um JSON válido.";
                header("Location: gerenciar-campanhas-reserva.php");
                exit;
            }
        } else {
            $custom_fields_json = null;
        }

        // Upload de Imagem de Capa
        $imageName = $_POST['existing_image'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $imageName = 'campanha_' . uniqid() . '.' . $ext;
                $dest = __DIR__ . '/../uploads/' . $imageName;
                move_uploaded_file($_FILES['image']['tmp_name'], $dest);
            }
        }

        try {
            if ($id > 0) {
                // Atualizar
                $stmt = $pdo->prepare("UPDATE reservation_campaigns SET 
                    title = ?, slug = ?, short_description = ?, description = ?, image = ?, 
                    city = ?, state = ?, location = ?, target_audience = ?, start_date = ?, end_date = ?, 
                    class_start_date = ?, max_reservations = ?, show_counter = ?, allow_waiting_list = ?, 
                    allows_presencial = ?, allows_online = ?, status = ?, custom_fields_json = ?, 
                    enrollment_link = ?, enrollment_button_text = ?, meta_title = ?, meta_description = ? 
                    WHERE id = ?");
                $stmt->execute([
                    $title, $slug, $short_description, $description, $imageName,
                    $city, $state, $location, $target_audience, $start_date, $end_date,
                    $class_start_date, $max_reservations, $show_counter, $allow_waiting_list,
                    $allows_presencial, $allows_online, $status, $custom_fields_json,
                    $enrollment_link, $enrollment_button_text, $meta_title, $meta_description,
                    $id
                ]);
                $_SESSION['msg'] = "Campanha '{$title}' atualizada com sucesso!";
            } else {
                // Inserir Nova
                $stmt = $pdo->prepare("INSERT INTO reservation_campaigns (
                    title, slug, short_description, description, image, city, state, location, target_audience,
                    start_date, end_date, class_start_date, max_reservations, show_counter, allow_waiting_list,
                    allows_presencial, allows_online, status, custom_fields_json, enrollment_link, enrollment_button_text,
                    meta_title, meta_description, active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([
                    $title, $slug, $short_description, $description, $imageName, $city, $state, $location, $target_audience,
                    $start_date, $end_date, $class_start_date, $max_reservations, $show_counter, $allow_waiting_list,
                    $allows_presencial, $allows_online, $status, $custom_fields_json, $enrollment_link, $enrollment_button_text,
                    $meta_title, $meta_description
                ]);
                $_SESSION['msg'] = "Nova campanha '{$title}' criada com sucesso!";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $_SESSION['erro'] = "Já existe uma campanha com este slug (URL amigável). Escolha outro nome.";
            } else {
                $_SESSION['erro'] = "Erro ao salvar campanha: " . $e->getMessage();
            }
        }

        header("Location: gerenciar-campanhas-reserva.php");
        exit;
    }

    if ($action === 'toggle_active') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE reservation_campaigns SET active = NOT active WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['msg'] = "Status de visibilidade da campanha alterado.";
        header("Location: gerenciar-campanhas-reserva.php");
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // Verificar se existem reservas atreladas
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE campaign_id = ?");
        $stmtCount->execute([$id]);
        $hasReservas = (int)$stmtCount->fetchColumn();

        if ($hasReservas > 0) {
            $_SESSION['erro'] = "Esta campanha não pode ser excluída pois já possui {$hasReservas} reserva(s) registrada(s). Você pode mudar o status para 'Cancelada' ou desativá-la.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM reservation_campaigns WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['msg'] = "Campanha excluída com sucesso!";
        }
        header("Location: gerenciar-campanhas-reserva.php");
        exit;
    }
}

// Carregar campanhas com contagem de reservas por modalidade
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM reservations r WHERE r.campaign_id = c.id AND r.status != 'cancelado') as total_reservas,
        (SELECT COUNT(*) FROM reservations r WHERE r.campaign_id = c.id AND r.preferred_modality = 'presencial' AND r.status != 'cancelado') as res_presencial,
        (SELECT COUNT(*) FROM reservations r WHERE r.campaign_id = c.id AND r.preferred_modality = 'online' AND r.status != 'cancelado') as res_online,
        (SELECT COUNT(*) FROM reservations r WHERE r.campaign_id = c.id AND r.preferred_modality = 'ambas' AND r.status != 'cancelado') as res_ambas,
        (SELECT COUNT(*) FROM reservations r WHERE r.campaign_id = c.id AND r.status = 'matriculado') as total_matriculados
        FROM reservation_campaigns c 
        ORDER BY c.id DESC";
$campaigns = $pdo->query($sql)->fetchAll();

// Se estiver editando via GET
$editCampaign = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmtEdit = $pdo->prepare("SELECT * FROM reservation_campaigns WHERE id = ?");
    $stmtEdit->execute([$editId]);
    $editCampaign = $stmtEdit->fetch();
}

require_once 'includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h2><i class="fas fa-bullhorn"></i> Campanhas de Reserva / Lista de Interesse</h2>
        <p style="color: #666; margin: 0; font-size: 0.9rem;">Crie e gerencie campanhas para medir a demanda de novas turmas e captar interessados.</p>
    </div>
    <button type="button" class="btn btn-success" onclick="document.getElementById('formCampanhaCard').scrollIntoView({behavior: 'smooth'}); document.getElementById('inputTitle').focus();">
        <i class="fas fa-plus"></i> Nova Campanha
    </button>
</div>

<!-- Listagem de Campanhas Existentes -->
<div class="card" style="margin-bottom: 2rem;">
    <h3><i class="fas fa-list"></i> Campanhas Cadastradas (<?= count($campaigns) ?>)</h3>
    <div style="overflow-x: auto;">
        <table class="table" style="margin-top: 1rem;">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th style="width: 70px;">Capa</th>
                    <th>Título / Slug</th>
                    <th>Modalidades</th>
                    <th>Status</th>
                    <th>Reservas (Demanda)</th>
                    <th>Matrículas</th>
                    <th>Período / Previsão</th>
                    <th style="text-align: right; width: 180px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($campaigns)): ?>
                    <tr><td colspan="9" style="text-align: center; color: #888; padding: 2rem;">Nenhuma campanha criada ainda. Utilize o formulário abaixo para criar a primeira!</td></tr>
                <?php else: foreach ($campaigns as $camp): 
                    $badgeClass = 'badge-secondary';
                    if ($camp['status'] === 'reservas_abertas') $badgeClass = 'badge-success';
                    elseif ($camp['status'] === 'matriculas_abertas') $badgeClass = 'badge-info';
                    elseif ($camp['status'] === 'turma_confirmada') $badgeClass = 'badge-primary';
                    elseif ($camp['status'] === 'reservas_encerradas') $badgeClass = 'badge-warning';
                    elseif ($camp['status'] === 'cancelada') $badgeClass = 'badge-danger';
                ?>
                    <tr style="<?= !$camp['active'] ? 'opacity: 0.6; background: #fbfbfb;' : '' ?>">
                        <td><strong>#<?= $camp['id'] ?></strong></td>
                        <td>
                            <?php if (!empty($camp['image'])): ?>
                                <img src="/uploads/<?= htmlspecialchars($camp['image']) ?>" alt="Capa" style="width: 50px; height: 35px; object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                                <span style="display: inline-block; width: 50px; height: 35px; background: #eee; border-radius: 4px; text-align: center; line-height: 35px; color: #aaa; font-size: 0.8rem;"><i class="fas fa-image"></i></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($camp['title']) ?></strong>
                            <div style="font-size: 0.8rem; color: #666; margin-top: 2px;">
                                <code>/reserva/<?= htmlspecialchars($camp['slug']) ?></code>
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 3px; font-size: 0.8rem;">
                                <?php if ($camp['allows_presencial']): ?>
                                    <span style="color: #03045e;"><i class="fas fa-chalkboard"></i> Presencial</span>
                                <?php endif; ?>
                                <?php if ($camp['allows_online']): ?>
                                    <span style="color: #ff8000;"><i class="fas fa-laptop"></i> Online</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($statusLabels[$camp['status']] ?? $camp['status']) ?></span>
                            <?php if (!$camp['active']): ?>
                                <span class="badge badge-secondary" style="margin-top: 3px; display: block;">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= $camp['total_reservas'] ?></strong>
                            <?php if ($camp['max_reservations'] > 0): ?>
                                <small style="color: #888;">/ <?= $camp['max_reservations'] ?> máx</small>
                            <?php endif; ?>
                            <div style="font-size: 0.75rem; color: #666; margin-top: 3px;">
                                <span>Pres: <strong><?= $camp['res_presencial'] ?></strong></span> | 
                                <span>On: <strong><?= $camp['res_online'] ?></strong></span> | 
                                <span>Ambas: <strong><?= $camp['res_ambas'] ?></strong></span>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-success"><?= $camp['total_matriculados'] ?></span>
                            <?php if ($camp['total_reservas'] > 0): ?>
                                <div style="font-size: 0.75rem; color: #28a745; margin-top: 2px;">
                                    <?= round(($camp['total_matriculados'] / $camp['total_reservas']) * 100, 1) ?>% conv.
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 0.8rem;">
                            <?php if (!empty($camp['class_start_date'])): ?>
                                <div>Início: <strong><?= date('d/m/Y', strtotime($camp['class_start_date'])) ?></strong></div>
                            <?php else: ?>
                                <div style="color: #888;">Início a definir</div>
                            <?php endif; ?>
                            <?php if (!empty($camp['city'])): ?>
                                <div style="color: #666;"><?= htmlspecialchars($camp['city']) ?>/<?= htmlspecialchars($camp['state']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <a href="/reserva/<?= htmlspecialchars($camp['slug']) ?>" target="_blank" class="btn btn-sm btn-secondary" title="Ver Landing Page"><i class="fas fa-external-link-alt"></i></a>
                            <a href="gerenciar-campanhas-reserva.php?edit=<?= $camp['id'] ?>" class="btn btn-sm" title="Editar"><i class="fas fa-edit"></i></a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Deseja alternar a visibilidade desta campanha?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="id" value="<?= $camp['id'] ?>">
                                <button type="submit" class="btn btn-sm <?= $camp['active'] ? 'btn-warning' : 'btn-success' ?>" title="<?= $camp['active'] ? 'Desativar' : 'Ativar' ?>">
                                    <i class="fas <?= $camp['active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir esta campanha? Esta ação só é permitida se não houver reservas associadas.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $camp['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Excluir"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Formulário de Criação / Edição de Campanha -->
<div class="card" id="formCampanhaCard">
    <h3>
        <i class="fas <?= $editCampaign ? 'fa-edit' : 'fa-plus-circle' ?>"></i> 
        <?= $editCampaign ? "Editar Campanha: " . htmlspecialchars($editCampaign['title']) : "Criar Nova Campanha de Reserva" ?>
    </h3>

    <?php if ($editCampaign): ?>
        <p><a href="gerenciar-campanhas-reserva.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i> Cancelar Edição</a></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 1.2rem; margin-top: 1rem;">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $editCampaign['id'] ?? 0 ?>">
        <input type="hidden" name="existing_image" value="<?= htmlspecialchars($editCampaign['image'] ?? '') ?>">

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
            <div class="form-group" style="margin: 0;">
                <label>Título da Campanha / Turma *</label>
                <input type="text" name="title" id="inputTitle" required class="form-control" placeholder="Ex: Preparatório Concurso Educação - Nova Turma" value="<?= htmlspecialchars($editCampaign['title'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Slug (URL Amigável)</label>
                <input type="text" name="slug" class="form-control" placeholder="ex: preparatorio-educacao-2027 (vazio para automático)" value="<?= htmlspecialchars($editCampaign['slug'] ?? '') ?>">
            </div>
        </div>

        <!-- Modalidades Disponíveis (Requisito Crítico) -->
        <div style="background: #f8f9fa; border: 1px solid #ddd; border-left: 4px solid #ff8000; padding: 1.2rem; border-radius: 4px;">
            <label style="display: block; font-weight: 700; color: #03045e; margin-bottom: 0.5rem; font-size: 0.95rem;">
                <i class="fas fa-layer-group"></i> Modalidades Disponíveis nesta Campanha *
            </label>
            <p style="margin: 0 0 0.8rem 0; font-size: 0.85rem; color: #666;">
                Marque quais formatos esta turma poderá oferecer. Se marcar ambas, o visitante terá a opção de escolher no formulário público. Se marcar apenas uma, a modalidade será salva automaticamente.
            </p>
            <div style="display: flex; gap: 2rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="allows_presencial" value="1" <?= (!isset($editCampaign) || !empty($editCampaign['allows_presencial'])) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                    <span>🏫 Modalidade Presencial</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="allows_online" value="1" <?= (!empty($editCampaign['allows_online'])) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                    <span>💻 Modalidade Online</span>
                </label>
            </div>
        </div>

        <!-- Descrições -->
        <div class="form-group" style="margin: 0;">
            <label>Descrição Curta / Chamada (Exibida no topo e nas redes sociais)</label>
            <textarea name="short_description" class="form-control" rows="2" placeholder="Resumo atrativo para os candidatos interessados..."><?= htmlspecialchars($editCampaign['short_description'] ?? '') ?></textarea>
        </div>

        <div class="form-group" style="margin: 0;">
            <label>Descrição Completa / Detalhes da Turma (HTML ou Texto)</label>
            <textarea name="description" class="form-control" rows="5" placeholder="Disciplinas previstas, corpo docente, carga horária estimada, diferenciais..."><?= htmlspecialchars($editCampaign['description'] ?? '') ?></textarea>
        </div>

        <!-- Localidade e Público -->
        <div style="display: grid; grid-template-columns: 2fr 1fr 2fr; gap: 1rem;">
            <div class="form-group" style="margin: 0;">
                <label>Cidade</label>
                <input type="text" name="city" class="form-control" placeholder="Ex: Caxias" value="<?= htmlspecialchars($editCampaign['city'] ?? 'Caxias') ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>UF</label>
                <input type="text" name="state" maxlength="2" class="form-control" placeholder="MA" value="<?= htmlspecialchars($editCampaign['state'] ?? 'MA') ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Público-Alvo / Cargo Pretendido</label>
                <input type="text" name="target_audience" class="form-control" placeholder="Ex: Professores, Nível Médio e Superior" value="<?= htmlspecialchars($editCampaign['target_audience'] ?? '') ?>">
            </div>
        </div>

        <!-- Datas -->
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
            <div class="form-group" style="margin: 0;">
                <label>Data Início das Reservas</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($editCampaign['start_date'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Data Encerramento das Reservas</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($editCampaign['end_date'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Data Prevista de Início da Turma</label>
                <input type="date" name="class_start_date" class="form-control" value="<?= htmlspecialchars($editCampaign['class_start_date'] ?? '') ?>">
            </div>
        </div>

        <!-- Limites, Contadores e Status -->
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem;">
            <div class="form-group" style="margin: 0;">
                <label>Limite de Reservas (0 = ilimitado)</label>
                <input type="number" name="max_reservations" class="form-control" min="0" value="<?= htmlspecialchars($editCampaign['max_reservations'] ?? 0) ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Status da Campanha</label>
                <select name="status" class="form-control">
                    <?php foreach ($statusLabels as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= (($editCampaign['status'] ?? 'reservas_abertas') === $val) ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin: 0; display: flex; flex-direction: column; justify-content: center;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin: 0;">
                    <input type="checkbox" name="show_counter" value="1" <?= (!isset($editCampaign) || !empty($editCampaign['show_counter'])) ? 'checked' : '' ?>>
                    <span>Mostrar Contador Público</span>
                </label>
            </div>
            <div class="form-group" style="margin: 0; display: flex; flex-direction: column; justify-content: center;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin: 0;">
                    <input type="checkbox" name="allow_waiting_list" value="1" <?= (!isset($editCampaign) || !empty($editCampaign['allow_waiting_list'])) ? 'checked' : '' ?>>
                    <span>Permitir Lista de Espera</span>
                </label>
            </div>
        </div>

        <!-- Matrícula Futura -->
        <div style="background: #f0f8ff; border: 1px solid #b8daff; padding: 1rem; border-radius: 4px;">
            <label style="font-weight: 700; color: #004085; margin-bottom: 0.5rem; display: block;">
                <i class="fas fa-graduation-cap"></i> Abertura Futura de Matrícula (Opcional)
            </label>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <div>
                    <label style="font-size: 0.85rem;">Link de Checkout / Matrícula Definitiva</label>
                    <input type="url" name="enrollment_link" class="form-control" placeholder="https://..." value="<?= htmlspecialchars($editCampaign['enrollment_link'] ?? '') ?>">
                </div>
                <div>
                    <label style="font-size: 0.85rem;">Texto do Botão de Matrícula</label>
                    <input type="text" name="enrollment_button_text" class="form-control" placeholder="Fazer Minha Matrícula Agora" value="<?= htmlspecialchars($editCampaign['enrollment_button_text'] ?? 'Fazer Minha Matrícula') ?>">
                </div>
            </div>
        </div>

        <!-- Imagem e SEO -->
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
            <div class="form-group" style="margin: 0;">
                <label>Imagem de Capa (Upload)</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <?php if (!empty($editCampaign['image'])): ?>
                    <small style="color: #666;">Atual: <?= htmlspecialchars($editCampaign['image']) ?></small>
                <?php endif; ?>
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Meta Title (SEO)</label>
                <input type="text" name="meta_title" class="form-control" placeholder="Título para Google..." value="<?= htmlspecialchars($editCampaign['meta_title'] ?? '') ?>">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>Meta Description (SEO)</label>
                <input type="text" name="meta_description" class="form-control" placeholder="Descrição curta para Google..." value="<?= htmlspecialchars($editCampaign['meta_description'] ?? '') ?>">
            </div>
        </div>

        <!-- Perguntas Complementares Personalizadas (JSON) -->
        <div class="form-group" style="margin: 0;">
            <label>
                Perguntas Complementares Personalizadas (Configuração JSON opcional) 
                <small style="color: #888; font-weight: normal;">Ex: cargo pretendido, turno, etc.</small>
            </label>
            <textarea name="custom_fields_json" class="form-control" rows="3" placeholder='{"cargo_pretendido": {"label": "Qual cargo você pretende disputar?", "placeholder": "Ex: Professor de Matemática"}, "turno": {"label": "Qual o melhor turno para suas aulas?", "options": ["Matutino", "Vespertino", "Noturno"]}}'><?= htmlspecialchars($editCampaign['custom_fields_json'] ?? '') ?></textarea>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
            <button type="submit" class="btn btn-success" style="padding: 0.7rem 1.8rem; font-size: 1rem;">
                <i class="fas fa-save"></i> <?= $editCampaign ? "Salvar Alterações" : "Criar Campanha" ?>
            </button>
            <?php if ($editCampaign): ?>
                <a href="gerenciar-campanhas-reserva.php" class="btn btn-secondary">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
