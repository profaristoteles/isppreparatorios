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

    // 2. Verificar se já existem itens para não duplicar
    $count = $pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
    
    if ($count == 0) {
        // Popula com o menu existente
        $itens = [
            ['Início', '/index.php', 1, 0, 0],
            ['Diferenciais', '/index.php#diferenciais', 2, 0, 0],
            ['Cursos', '/cursos.php', 3, 0, 0],
            ['Editais', '/editais.php', 4, 0, 0],
            ['Eventos (Aulas/Lives)', '/eventos.php', 5, 0, 0],
            ['Modalidades', '/index.php#modalidades', 6, 0, 0],
            ['Depoimentos', '/index.php#depoimentos', 7, 0, 0],
            ['Blog', '/blog.php', 8, 0, 0],
            ['Apostilas', '/apostilas.php', 9, 0, 0],
            ['Já sou Aluno(a)', 'https://ispreparatorios.classbuild.com', 10, 1, 1], // is_button=1, target_blank=1
        ];

        $stmt = $pdo->prepare("INSERT INTO menu_items (label, url, order_index, is_button, target_blank) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($itens as $item) {
            $stmt->execute($item);
        }
        
        echo "Migração do Menu concluída! Tabela 'menu_items' criada e preenchida com os links iniciais.";
    } else {
        echo "Tabela 'menu_items' já existe e possui dados. Nenhuma ação necessária.";
    }

} catch (PDOException $e) {
    echo "Erro na migração: " . $e->getMessage();
}
