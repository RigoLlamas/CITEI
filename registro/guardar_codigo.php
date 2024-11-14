<?php
session_start();

// Leer el cuerpo JSON de la solicitud
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Verificar si el código de verificación fue proporcionado
if (isset($data['codigo_verificacion'])) {
    $_SESSION['codigo_verificacion'] = $data['codigo_verificacion'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Código de verificación no proporcionado']);
}
?>
