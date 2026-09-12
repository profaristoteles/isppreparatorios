<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - ISP Preparatórios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; margin: 0; display: flex; min-height: 100vh; background: #f4f6f9; color: #333; }
        .sidebar { width: 260px; background: #03045e; color: #fff; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h2 { text-align: center; padding: 1.2rem 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); margin: 0; font-size: 1.2rem; font-weight: 700; background: #00022e; }
        .sidebar a { color: rgba(255,255,255,0.85); text-decoration: none; padding: 0.75rem 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.05); transition: all 0.2s ease; display: flex; align-items: center; justify-content: space-between; font-size: 0.9rem; }
        .sidebar a i { margin-right: 0.6rem; width: 18px; text-align: center; }
        .sidebar a:hover, .sidebar a.active { background: #ff8000; color: #fff; text-decoration: none; }
        .sidebar .menu-section-header { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: #ff8000; padding: 1rem 1.2rem 0.4rem 1.2rem; font-weight: 700; background: rgba(0,0,0,0.15); }
        
        .main-content { flex: 1; padding: 2rem; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #ddd; padding-bottom: 1rem; background: #fff; padding: 1rem 1.5rem; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .header h1 { font-size: 1.5rem; margin: 0; color: #03045e; font-weight: 700; }
        
        .table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-radius: 6px; overflow: hidden; }
        .table th, .table td { padding: 0.85rem 1rem; text-align: left; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        .table th { background: #f8f9fa; color: #03045e; font-weight: 600; }
        .table tr:hover { background: #fdfdfd; }
        
        .btn { padding: 0.5rem 1rem; background: #03045e; color: #fff; border: none; cursor: pointer; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; font-weight: 500; transition: background 0.2s; }
        .btn:hover { background: #ff8000; color: #fff; }
        .btn-secondary { background: #6c757d; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #bd2130; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-warning:hover { background: #e0a800; color: #000; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.8rem; }
        
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 0.9rem; color: #333; }
        .form-control { width: 100%; padding: 0.6rem 0.8rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-family: inherit; font-size: 0.9rem; }
        .form-control:focus { outline: none; border-color: #03045e; box-shadow: 0 0 0 2px rgba(3, 4, 94, 0.15); }
        .card { background: #fff; padding: 1.5rem; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
        
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; font-size: 0.9rem; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }

        .badge { padding: 0.25rem 0.5rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }
        
        .grid-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: #fff; padding: 1.2rem; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-left: 4px solid #03045e; }
        .stat-card h3 { font-size: 0.85rem; color: #666; margin: 0 0 0.5rem 0; font-weight: 500; text-transform: uppercase; }
        .stat-card .val { font-size: 1.8rem; font-weight: 700; color: #03045e; margin: 0; }
        
        .pagination { display: flex; gap: 0.3rem; margin-top: 1rem; justify-content: center; }
        .pagination a, .pagination span { padding: 0.4rem 0.8rem; border: 1px solid #ccc; text-decoration: none; color: #03045e; border-radius: 4px; font-size: 0.85rem; }
        .pagination .active { background: #03045e; color: #fff; border-color: #03045e; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Admin ISP</h2>
    <a href="dashboard.php"><span><i class="fas fa-chart-line"></i> Dashboard Geral</span></a>
    
    <div class="menu-section-header">Aulas Gratuitas</div>
    <a href="aulas-gratuitas-dashboard.php"><span><i class="fas fa-graduation-cap"></i> Dashboard</span></a>
    <a href="gerenciar-canais.php"><span><i class="fas fa-tv"></i> Canais</span></a>
    <a href="gerenciar-videoaulas.php"><span><i class="fas fa-video"></i> Videoaulas</span></a>
    <a href="gerenciar-materiais.php"><span><i class="fas fa-file-pdf"></i> Materiais</span></a>
    <a href="gerenciar-professores.php"><span><i class="fas fa-chalkboard-teacher"></i> Professores</span></a>
    <a href="gerenciar-disciplinas.php"><span><i class="fas fa-book"></i> Disciplinas</span></a>
    <a href="gerenciar-bancas.php"><span><i class="fas fa-building"></i> Bancas</span></a>
    <a href="gerenciar-concursos.php"><span><i class="fas fa-award"></i> Concursos</span></a>
    <a href="gerenciar-leads.php"><span><i class="fas fa-users"></i> Leads</span></a>
    <a href="conflitos-leads.php"><span><i class="fas fa-exclamation-triangle"></i> Conflitos</span></a>
    <a href="fila-integracoes.php"><span><i class="fas fa-sync"></i> Fila de Integrações</span></a>

    <div class="menu-section-header">Reservas & Novas Turmas</div>
    <a href="reservas-dashboard.php"><span><i class="fas fa-chart-pie"></i> Dashboard Reservas</span></a>
    <a href="gerenciar-campanhas-reserva.php"><span><i class="fas fa-bullhorn"></i> Campanhas</span></a>
    <a href="gerenciar-reservas.php"><span><i class="fas fa-clipboard-list"></i> Reservas</span></a>

    <div class="menu-section-header">Geral do Site</div>
    <a href="gerenciar-cursos.php"><span><i class="fas fa-graduation-cap"></i> Cursos</span></a>
    <a href="gerenciar-apostilas.php"><span><i class="fas fa-book-open"></i> Apostilas</span></a>
    <a href="gerenciar-eventos.php"><span><i class="fas fa-calendar-alt"></i> Eventos (Leads)</span></a>
    <a href="gerenciar-posts.php"><span><i class="fas fa-blog"></i> Blog</span></a>
    <a href="gerenciar-depoimentos.php"><span><i class="fas fa-comment-dots"></i> Depoimentos</span></a>
    <a href="gerenciar-menu.php"><span><i class="fas fa-bars"></i> Menu do Site</span></a>
    <a href="inscricoes.php"><span><i class="fas fa-user-plus"></i> Inscrições</span></a>
    <a href="configuracoes.php"><span><i class="fas fa-cog"></i> Configurações</span></a>
    <a href="perfil.php"><span><i class="fas fa-user-circle"></i> Meu Perfil</span></a>
    <a href="logout.php"><span><i class="fas fa-sign-out-alt"></i> Sair</span></a>
</div>

<div class="main-content">
    <div class="header">
        <h1>Painel Administrativo</h1>
        <div>Olá, <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrador') ?></strong></div>
    </div>
    
    <?php if(isset($_SESSION['msg'])): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['msg'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['msg']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['erro'])): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['erro'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['erro']); ?></div>
    <?php endif; ?>
