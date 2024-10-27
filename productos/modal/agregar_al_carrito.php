<?php
// Conectar a la base de datos
include '../../php/conexion.php'; // Asegúrate de tener la conexión a la base de datos

// Iniciar sesión (asegurarse de que la sesión esté activa para obtener el ID del usuario)
session_start();

// Verificar que los datos fueron enviados mediante POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productoId = $_POST['productoId'];
    $cantidad = $_POST['cantidad'];
    $usuarioId = $_SESSION['id_usuario']; // Obtener el ID del usuario desde la sesión

    // Verificar si el producto ya está en el carrito del usuario
    $consulta = "SELECT * FROM carrito WHERE Producto = ? AND Usuario = ?";
    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param('ii', $productoId, $usuarioId);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        // Si el producto ya está en el carrito, actualizar la cantidad
        $actualizar = "UPDATE carrito SET Cantidad = Cantidad + ? WHERE Producto = ? AND Usuario = ?";
        $stmt = $conexion->prepare($actualizar);
        $stmt->bind_param('iii', $cantidad, $productoId, $usuarioId);
        $stmt->execute();
    } else {
        // Si el producto no está en el carrito, agregarlo
        $insertar = "INSERT INTO carrito (Cantidad, Producto, Usuario) VALUES (?, ?, ?)";
        $stmt = $conexion->prepare($insertar);
        $stmt->bind_param('iii', $cantidad, $productoId, $usuarioId);
        $stmt->execute();
    }

    // Cerrar la conexión y la sentencia
    $stmt->close();
    $conexion->close();

    // Respuesta exitosa
    echo "Producto agregado al carrito";
} else {
    // Si no se envió nada por POST
    echo "Error: No se enviaron datos.";
}
?>
