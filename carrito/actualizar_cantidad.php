<?php
// Conectar a la base de datos
include '../php/conexion.php'; // Asegúrate de que este archivo esté configurado correctamente
session_start();

// Verificar que los datos fueron enviados mediante POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos enviados desde el cliente (JavaScript)
    $productoId = $_POST['productoId'];
    $cantidad = $_POST['cantidad'];
    $usuarioId = $_SESSION['id_usuario']; // Obtener el ID del usuario desde la sesión

    // Verificar que los datos sean válidos
    if (is_numeric($cantidad) && is_numeric($productoId)) {
        // Actualizar la cantidad del producto en el carrito
        $actualizar = "UPDATE carrito SET Cantidad = ? WHERE Producto = ? AND Usuario = ?";
        $stmt = $conexion->prepare($actualizar);
        $stmt->bind_param('iii', $cantidad, $productoId, $usuarioId);

        if ($stmt->execute()) {
            echo "Cantidad actualizada correctamente en la base de datos.";
        } else {
            echo "Error al actualizar la cantidad: " . $stmt->error;
        }

        // Cerrar la sentencia y la conexión
        $stmt->close();
        $conexion->close();
    } else {
        echo "Datos inválidos.";
    }
}
?>
