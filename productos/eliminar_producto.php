
<?php
include '../php/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productoId = $_POST['id'] ?? null;

    if ($productoId) {
        // Consulta para eliminar el producto (puedes cambiar Visibilidad a 0 si no quieres borrarlo físicamente)
        $sql = "UPDATE producto SET Visibilidad = 0 WHERE PK_Producto = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('i', $productoId);

        if ($stmt->execute()) {
            echo "El producto ha sido eliminado correctamente.";
        } else {
            echo "Error al eliminar el producto.";
        }
        $stmt->close();
    } else {
        echo "ID de producto no válido.";
    }
} else {
    echo "Método de solicitud no permitido.";
}
$conexion->close();
?>
