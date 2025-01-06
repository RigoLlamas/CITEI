<?php
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['contactanos'])) {
    // Limitar a 500 caracteres
    $contactanos = substr($data['contactanos'], 0, 500);

    $file_path = 'contactanos.txt';

    // Verificar si el archivo existe, y crearlo si no
    if (!file_exists($file_path)) {
        $file_created = touch($file_path);
        if (!$file_created) {
            echo json_encode(['success' => false, 'message' => 'No se pudo crear el archivo.']);
            exit;
        }
    }

    // Escribir en el archivo
    if (file_put_contents($file_path, $contactanos) !== false) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo escribir en el archivo.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
}
?>
