<?php
$file_path = 'mision_vision_objetivo.txt';

if (!file_exists($file_path)) {
    echo json_encode(['error' => 'El archivo no existe']);
    exit;
}

$mision_vision_objetivo = file_get_contents($file_path);
list($mision, $vision, $objetivo) = explode("###", $mision_vision_objetivo);

// Limitar a 500 caracteres cada campo antes de enviarlos
$mision = substr($mision, 0, 500);
$vision = substr($vision, 0, 500);
$objetivo = substr($objetivo, 0, 500);

echo json_encode([
    'mision' => htmlspecialchars($mision),
    'vision' => htmlspecialchars($vision),
    'objetivo' => htmlspecialchars($objetivo),
]);
?>
