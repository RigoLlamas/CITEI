<?php
$fecha_actual = date('Y-m-d');

$query_actualizacion = "UPDATE ofertas 
                        SET Estado = 'Activada' 
                        WHERE Despliegue <= ? AND Estado = 'En revisión'";
$stmt_update = $conexion->prepare($query_actualizacion);
$stmt_update->bind_param('s', $fecha_actual);
$stmt_update->execute();

$stmt_update->close();

include 'sugerencia_promociones.php';

?>