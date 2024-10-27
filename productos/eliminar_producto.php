<?php
include '../php/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_producto'])) {
    $id_producto = (int)$_POST['id_producto'];

    $sql = "UPDATE producto SET Visibilidad = 0 WHERE PK_Producto = $id_producto";

    if ($conexion->query($sql) === TRUE) {
        echo "Producto eliminado (oculto) exitosamente.";
        header("Location: ../productos/productos.php");
    } else {
        echo "Error al eliminar el producto: " . $conexion->error;
    }
    $conexion->close();
}
?>
