<?php
include '../php/conexion.php';
session_start();

    $productoId = $_POST['productoId'];
    $usuarioId = $_SESSION['id_usuario']; // Asegúrate de que el usuario esté autenticado

    // Eliminar el producto del carrito del usuario
    $queryEliminar = "DELETE FROM carrito WHERE Producto = ? AND Usuario = ?";
    $stmt = $conexion->prepare($queryEliminar);
    $stmt->bind_param('ii', $productoId, $usuarioId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Producto eliminado del carrito.";
    } else {
        echo "Error al eliminar el producto.";
    }

    $stmt->close();
?>
