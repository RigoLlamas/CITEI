<?php
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['contactanos'])) {
    // Limitar a 500 caracteres
    $contactanos = substr($data['contactanos'], 0, 500);

    $file_path = 'contactanos.txt';

    if (file_put_contents($file_path, $contactanos)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo escribir en el archivo.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
}
?>
