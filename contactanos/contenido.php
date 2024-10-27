<?php
$file_path = 'contactanos.txt';

if (!file_exists($file_path)) {
    echo json_encode(['error' => 'El archivo no existe']);
    exit;
}

$contactanos = file_get_contents($file_path);

// Limitar a 500 caracteres antes de enviar
$contactanos = substr($contactanos, 0, 500);

echo json_encode([
    'contactanos' => htmlspecialchars($contactanos),
]);
?>