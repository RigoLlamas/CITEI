<?php
include '../php/conexion.php';

// Consulta para actualizar el estado de las ofertas expiradas
$sql = "UPDATE ofertas SET Estado = 'Expirada' WHERE Expiracion < CURDATE() AND Estado = 'Activada'";

if (mysqli_query($conexion, $sql)) {
    echo "Ofertas expiradas actualizadas correctamente.";
} else {
    echo "Error al actualizar ofertas expiradas: " . mysqli_error($conexion);
}

// Cerrar la conexión
mysqli_close($conexion);
?>
