<?php
include '../php/conexion.php';

// Verificar si se ha recibido la placa del vehículo
if (isset($_GET['placa'])) {
    $placa = $conexion->real_escape_string($_GET['placa']);

    // Consulta para eliminar el vehículo
    $sql = "DELETE FROM vehiculo WHERE Placa = '$placa'";

    if (mysqli_query($conexion, $sql)) {
        // Redirigir a la página principal después de la eliminación
        header("Location: gestionar_vehiculos.php?mensaje=Vehículo eliminado correctamente.");
        exit();
    } else {
        echo "Error al eliminar el vehículo: " . mysqli_error($conexion);
    }

    mysqli_close($conexion);
} else {
    echo "Placa no especificada.";
}
?>