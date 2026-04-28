<?php
require '../db_config.php';
$pdo->query("UPDATE configuracoes SET phone='993199394', email='isppreparatorios@gmail.com', facebook='https://facebook.com/isppreparatorios', instagram='https://instagram.com/isppreparatorios' WHERE id=1");
echo 'Updated DB successfully';
