<?php
session_start();

include '../../php/conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['productoId']) || !isset($_POST['cantidad'])) {
        echo json_encode(["error" => "Datos insuficientes para agregar al carrito."]);
        exit();
    } else
    if (!isset($_SESSION['id_usuario'])) {
        echo json_encode(["error" => "Inicie sesión para agregar productos al carrito."]);
        exit();
    } else {
        $productoId = $_POST['productoId'];
        $cantidad = $_POST['cantidad'];
        $usuarioId = $_SESSION['id_usuario'];

        // Consultar si el producto ya está en el carrito
        $consulta = "SELECT * FROM carrito WHERE Producto = ? AND Usuario = ?";
        $stmt = $conexion->prepare($consulta);

        if ($stmt) {
            $stmt->bind_param('ii', $productoId, $usuarioId);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows > 0) {
                // Actualizar cantidad
                $actualizar = "UPDATE carrito SET Cantidad = Cantidad + ? WHERE Producto = ? AND Usuario = ?";
                $stmt = $conexion->prepare($actualizar);
                $stmt->bind_param('iii', $cantidad, $productoId, $usuarioId);
                $stmt->execute();
            } else {
                // Insertar nuevo producto
                $insertar = "INSERT INTO carrito (Cantidad, Producto, Usuario) VALUES (?, ?, ?)";
                $stmt = $conexion->prepare($insertar);
                $stmt->bind_param('iii', $cantidad, $productoId, $usuarioId);
                $stmt->execute();
            }

            $stmt->close();
            $conexion->close();

            echo json_encode(["success" => "Producto agregado al carrito."]);
        } else {
            echo json_encode(["error" => "Error al ejecutar la consulta."]);
        }
    }
} else {
    echo json_encode(["error" => "Método no permitido."]);
}
