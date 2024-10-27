<?php
include 'conexion.php';

function verificar_existencia($consulta_sql, $parametros) {
    global $conexion; 

    $stmt = $conexion->prepare($consulta_sql);

    if ($parametros) {
        $stmt->bind_param(...$parametros);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();
    return $resultado->num_rows > 0;
}
?>
