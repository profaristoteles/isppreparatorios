<?php
require_once 'db_config.php';

try {
    // 1. Criar tabela
    $sql = "CREATE TABLE IF NOT EXISTS menu_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        label VARCHAR(255) NOT NULL,
        url VARCHAR(255) NOT NULL,
        order_index INT DEFAULT 0,
        is_button TINYINT(1) DEFAULT 0,
        target_blank TINYINT(1) DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);

    // 2. Garantir que Aulas Gratuitas exista no menu
    $stmtMenu = $pdo->prepare("SELECT id FROM menu_items WHERE url = '/aulas-gratuitas' LIMIT 1");
    $stmtMenu->execute();
    if (!$stmtMenu->fetchColumn()) {
        $pdo->prepare("INSERT INTO menu_items (label, url, order_index, is_button, target_blank) VALUES ('Aulas Gratuitas', '/aulas-gratuitas', 4, 0, 0)")->execute();
        echo "Item 'Aulas Gratuitas' adicionado ao menu principal do site.\n";
    }

    // Verificar se já existem itens para popular padrão inicial caso zerada
    $count = $pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
    if ($count <= 1) {
        $itens = [
            ['Início', '/index.php', 1, 0, 0],
            ['Diferenciais', '/index.php#diferenciais', 2, 0, 0],
            ['Cursos', '/cursos.php', 3, 0, 0],
            ['Aulas Gratuitas', '/aulas-gratuitas', 4, 0, 0],
            ['Editais', '/editais.php', 5, 0, 0],
            ['Eventos (Aulas/Lives)', '/eventos.php', 6, 0, 0],
            ['Modalidades', '/index.php#modalidades', 7, 0, 0],
            ['Depoimentos', '/index.php#depoimentos', 8, 0, 0],
            ['Blog', '/blog.php', 9, 0, 0],
            ['Apostilas', '/apostilas.php', 10, 0, 0],
            ['Já sou Aluno(a)', 'https://ispreparatorios.classbuild.com', 11, 1, 1],
        ];

        $stmt = $pdo->prepare("INSERT IGNORE INTO menu_items (label, url, order_index, is_button, target_blank) VALUES (?, ?, ?, ?, ?)");
        foreach ($itens as $item) {
            $stmt->execute($item);
        }
    }

    echo "Migração do Menu concluída!";
} catch (PDOException $e) {
    echo "Erro na migração: " . $e->getMessage();
}
