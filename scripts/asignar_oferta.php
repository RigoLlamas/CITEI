<?php
include '../php/conexion.php';

// Obtener todas las ofertas activas y sus condiciones
$sql_ofertas = "
    SELECT o.Oferta, o.Descripcion, o.Condicion, c.Tipo, c.Valor
    FROM ofertas o
    JOIN condiciones c ON o.Condicion = c.Condicion
";
$result_ofertas = mysqli_query($conexion, $sql_ofertas);

// Comprobar el historial de cada usuario
$sql_usuarios = "SELECT PK_Usuario FROM usuarios";
$result_usuarios = mysqli_query($conexion, $sql_usuarios);

while ($usuario = mysqli_fetch_assoc($result_usuarios)) {
    $usuario_id = $usuario['PK_Usuario'];
    
    // Evaluar cada oferta y verificar si el usuario cumple con la condición
    while ($oferta = mysqli_fetch_assoc($result_ofertas)) {
        $oferta_id = $oferta['Oferta'];
        $condicion_tipo = $oferta['Tipo'];
        $condicion_valor = $oferta['Valor'];
        
        // Dependiendo del tipo de condición, evaluamos el historial de ventas
        if ($condicion_tipo === 'Cantidad de compras') {
            // Contar cuántos pedidos ha realizado el usuario
            $sql_historial = "SELECT COUNT(*) as cantidad_compras 
                              FROM historial_ventas 
                              WHERE Usuario = $usuario_id";
            $result_historial = mysqli_query($conexion, $sql_historial);
            $historial = mysqli_fetch_assoc($result_historial);
            
            if ($historial['cantidad_compras'] >= $condicion_valor) {
                // Si cumple con la condición de cantidad de compras, asignar la oferta
                asignar_oferta($usuario_id, $oferta_id, $conexion);
            }
            
        } elseif ($condicion_tipo === 'Productos comprados') {
            // Contar cuántos productos ha comprado el usuario
            $sql_historial = "SELECT SUM(Cantidad) as productos_comprados 
                              FROM historial_ventas 
                              WHERE Usuario = $usuario_id";
            $result_historial = mysqli_query($conexion, $sql_historial);
            $historial = mysqli_fetch_assoc($result_historial);
            
            if ($historial['productos_comprados'] >= $condicion_valor) {
                // Si cumple con la condición de productos comprados, asignar la oferta
                asignar_oferta($usuario_id, $oferta_id, $conexion);
            }
            
        } elseif ($condicion_tipo === 'Temporada') {
            // Condición especial por temporada (puede estar basada en fechas específicas)
            // Ejemplo: Asignar la oferta si estamos en Black Friday
            $current_month = date('m');
            if ($current_month == 11) { // Ejemplo: Black Friday en noviembre
                asignar_oferta($usuario_id, $oferta_id, $conexion);
            }
        }
    }
}

// Función para asignar la oferta
function asignar_oferta($usuario_id, $oferta_id, $conexion) {
    // Verificar si la oferta ya ha sido asignada al usuario
    $sql_verificar = "SELECT * FROM asignacion_ofertas WHERE Usuario = ? AND Oferta = ?";
    $stmt_verificar = mysqli_prepare($conexion, $sql_verificar);
    mysqli_stmt_bind_param($stmt_verificar, 'ii', $usuario_id, $oferta_id);
    mysqli_stmt_execute($stmt_verificar);
    mysqli_stmt_store_result($stmt_verificar);

    if (mysqli_stmt_num_rows($stmt_verificar) == 0) {
        // Si no ha sido asignada, insertar la nueva asignación
        $sql_asignacion = "INSERT INTO asignacion_ofertas (Uso, Fecha, Oferta, Usuario) 
                           VALUES (0, CURDATE(), ?, ?)";
        $stmt_asignacion = mysqli_prepare($conexion, $sql_asignacion);
        mysqli_stmt_bind_param($stmt_asignacion, 'ii', $oferta_id, $usuario_id);
        mysqli_stmt_execute($stmt_asignacion);
        mysqli_stmt_close($stmt_asignacion);
        echo "Oferta $oferta_id asignada al usuario $usuario_id.<br>";
    } else {
        echo "La oferta $oferta_id ya ha sido asignada al usuario $usuario_id.<br>";
    }
    mysqli_stmt_close($stmt_verificar);
}


?>
