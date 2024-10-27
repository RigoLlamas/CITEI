<?php
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['mision'], $data['vision'], $data['objetivo'])) {
    // Validación de 500 caracteres por campo
    if (strlen($data['mision']) > 500 || strlen($data['vision']) > 500 || strlen($data['objetivo']) > 500) {
        echo json_encode(['success' => false, 'message' => 'Cada campo debe tener un máximo de 500 caracteres.']);
        exit;
    }

    $file_path = 'mision_vision_objetivo.txt';
    $new_content = $data['mision'] . "###" . $data['vision'] . "###" . $data['objetivo'];

    if (file_put_contents($file_path, $new_content)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo escribir en el archivo.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Faltan datos.']);
}
?>
