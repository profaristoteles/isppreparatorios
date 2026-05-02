<?php
$json = file_get_contents('https://concursos-api.deno.dev/ma');
$data = json_decode($json, true);
echo json_encode($data, JSON_PRETTY_PRINT);
?>
