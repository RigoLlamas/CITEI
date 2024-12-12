<?php
// Conectar a la base de datos
include '../php/conexion.php'; // Asegúrate de que este archivo esté configurado correctamente
session_start();

// Establecer el tipo de contenido a JSON
header('Content-Type: application/json');

// Verificar que los datos fueron enviados mediante POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que el usuario está autenticado
    if (!isset($_SESSION['id_usuario'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Usuario no autenticado.'
        ]);
        exit();
    }

    // Obtener los datos enviados desde el cliente (JavaScript)
    $productoId = $_POST['productoId'] ?? null;
    $cantidad = $_POST['cantidad'] ?? null;
    $usuarioId = $_SESSION['id_usuario']; // Obtener el ID del usuario desde la sesión

    // Validar que los datos no estén vacíos
    if (empty($productoId) || empty($cantidad)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Faltan datos necesarios.'
        ]);
        exit();
    }

    // Verificar que los datos sean válidos
    if (is_numeric($cantidad) && is_numeric($productoId)) {
        // Convertir a enteros
        $productoId = intval($productoId);
        $cantidad = intval($cantidad);

        // Validar que la cantidad sea al menos 1
        if ($cantidad < 1) {
            echo json_encode([
                'status' => 'error',
                'message' => 'La cantidad debe ser al menos 1.'
            ]);
            exit();
        }

        // Preparar la consulta para actualizar la cantidad del producto en el carrito
        $actualizar = "UPDATE carrito SET Cantidad = ? WHERE Producto = ? AND Usuario = ?";
        $stmt = $conexion->prepare($actualizar);

        if ($stmt) {
            $stmt->bind_param('iii', $cantidad, $productoId, $usuarioId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Cantidad actualizada correctamente.',
                        'cantidad' => $cantidad
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'No se encontró el producto en el carrito o la cantidad es la misma.'
                    ]);
                }
            } else {
                // Error en la ejecución de la consulta
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar la cantidad: ' . $stmt->error
                ]);
            }

            // Cerrar la sentencia
            $stmt->close();
        } else {
            // Error en la preparación de la consulta
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al preparar la consulta: ' . $conexion->error
            ]);
        }

        // Cerrar la conexión
        $conexion->close();
    } else {
        // Datos inválidos
        echo json_encode([
            'status' => 'error',
            'message' => 'Datos inválidos proporcionados.'
        ]);
    }
} else {
    // Método HTTP no permitido
    echo json_encode([
        'status' => 'error',
        'message' => 'Método HTTP no permitido.'
    ]);
}
?>
