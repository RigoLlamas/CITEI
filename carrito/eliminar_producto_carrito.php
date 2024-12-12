<?php
include '../php/conexion.php';
session_start();
header('Content-Type: application/json');

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado.']);
    exit();
}

// Obtener el ID del producto desde la solicitud POST
if (isset($_POST['productoId'])) {
    $productoId = intval($_POST['productoId']);
    $usuarioId = $_SESSION['id_usuario'];

    // Preparar la consulta para eliminar el producto del carrito
    $consultaEliminar = "DELETE FROM carrito WHERE Usuario = ? AND Producto = ?";
    $stmtEliminar = $conexion->prepare($consultaEliminar);
    
    if ($stmtEliminar) {
        $stmtEliminar->bind_param('ii', $usuarioId, $productoId);
        $stmtEliminar->execute();

        if ($stmtEliminar->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Producto eliminado correctamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Producto no encontrado en el carrito.']);
        }
        $stmtEliminar->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error en la preparación de la consulta.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'No se recibió el ID del producto.']);
}
?>
