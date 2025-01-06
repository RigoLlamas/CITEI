<?php
include '../php/conexion.php';
include '../php/solo_admins.php';

if (isset($_GET['nomina'])) {
    $nomina = (float)$_GET['nomina'];

    $sql = "UPDATE repartidor SET Estado = 'Retirado' WHERE Nomina = $nomina";

    if (mysqli_query($conexion, $sql)) {
        $sqlEnvios = "DELETE FROM envios WHERE Repartidor = $nomina";

        if (mysqli_query($conexion, $sqlEnvios)) {
            $sqlPedidos = "UPDATE pedidos 
                           SET Estado = 'En almacen' 
                           WHERE NumVenta IN (
                               SELECT NumVenta FROM envios WHERE Repartidor = $nomina
                           )";

            if (mysqli_query($conexion, $sqlPedidos)) {
                // Redirigir a la página principal después de la actualización
                header('Location: gestionar_repartidores.php?success=true');
                exit();
            } else {
                echo "Error al actualizar los pedidos: " . mysqli_error($conexion);
            }
        } else {
            echo "Error al eliminar los envíos del repartidor: " . mysqli_error($conexion);
        }
    } else {
        echo "Error al actualizar el estado del repartidor: " . mysqli_error($conexion);
    }

    mysqli_close($conexion);
} else {
    echo "Nómina no especificada.";
}
?>
