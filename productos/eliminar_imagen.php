<?php
include '../php/conexion.php';

header('Content-Type: application/json'); // Asegurar que la respuesta sea JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true); // Decodificar JSON recibido

    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
        exit;
    }

    $id_producto = isset($input['id_producto']) ? (int)$input['id_producto'] : null;
    $imagen = isset($input['imagen']) ? basename($input['imagen']) : null;

    if (!$id_producto || !$imagen) {
        echo json_encode(['success' => false, 'message' => 'ID del producto o nombre de la imagen no proporcionados.']);
        exit;
    }

    $directorio_imagenes = "imagenes_productos/producto_" . $id_producto . "/";
    $ruta_imagen = $directorio_imagenes . $imagen;

    if (file_exists($ruta_imagen)) {
        unlink($ruta_imagen); // Eliminar la imagen
        echo json_encode(['success' => true, 'message' => 'Imagen eliminada.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo encontrar la imagen.']);
    }
    exit; // Detener el script después de enviar la respuesta
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>
