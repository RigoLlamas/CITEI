<?php
include '../php/conexion.php';

// Verificar si se ha recibido la nómina del repartidor
if (isset($_GET['nomina'])) {
    $nomina = (float)$_GET['nomina'];

    // Consulta para eliminar el repartidor
    $sql = "DELETE FROM repartidor WHERE Nomina = $nomina";

    if (mysqli_query($conexion, $sql)) {
        // Redirigir a la página principal después de la eliminación
        header('Location: gestionar_repartidores.php?success=true');
        exit();
    } else {
        echo "Error al eliminar el repartidor: " . mysqli_error($conexion);
    }

    mysqli_close($conexion);
} else {
    echo "Nómina no especificada.";
}
?>
