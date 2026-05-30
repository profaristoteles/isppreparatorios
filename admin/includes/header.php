<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - ISP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; margin: 0; display: flex; min-height: 100vh; background: #f4f6f9; }
        .sidebar { width: 250px; background: #03045e; color: #fff; display: flex; flex-direction: column; }
        .sidebar h2 { text-align: center; padding: 1rem 0; border-bottom: 1px solid #00022e; margin: 0; }
        .sidebar a { color: #fff; text-decoration: none; padding: 1rem; border-bottom: 1px solid #00022e; transition: background 0.2s; }
        .sidebar a:hover { background: #ff8000; }
        .main-content { flex: 1; padding: 2rem; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #ccc; padding-bottom: 1rem; }
        
        .table { width: 100%; border-collapse: collapse; margin-top: 1rem; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .table th, .table td { padding: 1rem; text-align: left; border-bottom: 1px solid #eee; }
        .table th { background: #f8f9fa; }
        
        .btn { padding: 0.5rem 1rem; background: #03045e; color: #fff; border: none; cursor: pointer; text-decoration: none; border-radius: 4px; display: inline-block; }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: #000; }
        
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        .form-control { width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .card { background: #fff; padding: 1.5rem; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1rem; }
        
        .alert { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Admin ISP</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="gerenciar-cursos.php">Cursos</a>
    <a href="gerenciar-apostilas.php">Apostilas</a>
    <a href="gerenciar-eventos.php">Eventos (Leads)</a>
    <a href="gerenciar-posts.php">Blog</a>
    <a href="gerenciar-depoimentos.php">Depoimentos</a>
    <a href="gerenciar-menu.php">Menu do Site</a>
    <a href="inscricoes.php">Inscrições</a>
    <a href="configuracoes.php">Configurações</a>
    <a href="perfil.php">Meu Perfil</a>
    <a href="logout.php">Sair</a>
</div>

<div class="main-content">
    <div class="header">
        <h1>Dashboard</h1>
        <div>Olá, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Administrador') ?></div>
    </div>
    
    <?php if(isset($_SESSION['msg'])): ?>
        <div class="alert alert-success"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
    <?php endif; ?>
    <?php if(isset($_SESSION['erro'])): ?>
        <div class="alert alert-error"><?= $_SESSION['erro']; unset($_SESSION['erro']); ?></div>
    <?php endif; ?>
