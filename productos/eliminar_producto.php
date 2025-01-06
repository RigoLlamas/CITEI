<?php
include '../php/conexion.php';
include '../php/solo_admins.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productoId = $_POST['id'] ?? null;

    if ($productoId) {
        // Iniciar una transacción para garantizar consistencia
        $conexion->begin_transaction();

        try {
            // Deshabilitar el producto (cambiar Visibilidad a 0)
            $sqlProducto = "UPDATE producto SET Visibilidad = 0 WHERE PK_Producto = ?";
            $stmtProducto = $conexion->prepare($sqlProducto);
            $stmtProducto->bind_param('i', $productoId);

            if (!$stmtProducto->execute()) {
                throw new Exception("Error al deshabilitar el producto.");
            }
            $stmtProducto->close();

            // Eliminar todas las apariciones del producto en el carrito
            $sqlCarrito = "DELETE FROM carrito WHERE Producto = ?";
            $stmtCarrito = $conexion->prepare($sqlCarrito);
            $stmtCarrito->bind_param('i', $productoId);

            if (!$stmtCarrito->execute()) {
                throw new Exception("Error al eliminar las apariciones del producto en el carrito.");
            }
            $stmtCarrito->close();

            // Confirmar los cambios
            $conexion->commit();
            echo "El producto ha sido eliminado correctamente y retirado del carrito.";
        } catch (Exception $e) {
            // Revertir los cambios en caso de error
            $conexion->rollback();
            echo $e->getMessage();
        }
    } else {
        echo "ID de producto no válido.";
    }
} else {
    echo "Método de solicitud no permitido.";
}
$conexion->close();
?>
