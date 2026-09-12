<?php
/**
 * Router / Redirect para Landing Pages de Campanhas de Reserva
 * ISP Preparatórios
 */

require_once __DIR__ . '/db_config.php';

$slug = trim($_GET['slug'] ?? '');

if (empty($slug)) {
    header("Location: /");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM reservation_campaigns WHERE slug = ? AND active = 1 LIMIT 1");
$stmt->execute([$slug]);
$campaign = $stmt->fetch();

if (!$campaign) {
    header("HTTP/1.0 404 Not Found");
    require_once __DIR__ . '/includes/header.php';
    echo "<main style='min-height: 60vh; display: flex; align-items: center; justify-content: center; text-align: center; padding: 4rem 1rem;'>
            <div>
                <h1 style='font-size: 2.5rem; color: #03045e; margin-bottom: 1rem;'>Campanha não encontrada</h1>
                <p style='color: #666; margin-bottom: 2rem;'>A campanha de reserva que você procura não existe ou não está mais ativa.</p>
                <a href='/' class='btn' style='background: #ff8000; color: #fff; padding: 0.8rem 1.5rem; text-decoration: none; border-radius: 6px;'>Voltar para a Página Inicial</a>
            </div>
          </main>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

require __DIR__ . '/template-reserva.php';
