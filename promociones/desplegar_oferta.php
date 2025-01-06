<?php
include '../php/conexion.php';
include '../php/solo_admins.php';

// Obtener el ID de la oferta desde la URL
if (isset($_GET['oferta'])) {
    $oferta_id = (int)$_GET['oferta'];

    // Consulta para obtener los datos de la oferta y su condición asociada
    $sql_verificar = "
        SELECT o.Tipo AS TipoOferta, o.Valor AS ValorOferta, o.Despliegue, o.Condicion, o.Producto, 
               c.Tipo AS TipoCondicion, c.Valor AS ValorCondicion, c.LimiteTiempo, c.CantidadUsos
        FROM ofertas o
        JOIN condiciones c ON o.Condicion = c.Condicion
        WHERE o.Oferta = ?
    ";
    $stmt_verificar = mysqli_prepare($conexion, $sql_verificar);
    mysqli_stmt_bind_param($stmt_verificar, 'i', $oferta_id);
    mysqli_stmt_execute($stmt_verificar);
    $result_verificar = mysqli_stmt_get_result($stmt_verificar);
    $oferta = mysqli_fetch_assoc($result_verificar);

    if ($oferta) {
        $tipo_condicion = $oferta['TipoCondicion'];

        // Validar según el tipo de condición
        $condicion_valida = false;

        if ($tipo_condicion === 'Temporada') {
            // Condición basada en el límite de tiempo
            if (strtotime($oferta['LimiteTiempo']) >= strtotime(date('Y-m-d'))) {
                $condicion_valida = true;
            } else {
                echo "La oferta no puede activarse porque ha expirado.";
            }
        } elseif ($tipo_condicion === 'Cantidad de compras' || $tipo_condicion === 'Productos comprados') {
            // Condición basada en la cantidad de usos
            if ($oferta['CantidadUsos'] > 0) {
                $condicion_valida = true;
            } else {
                echo "La oferta no puede activarse porque ha alcanzado su límite de usos.";
            }
        }

        // Si la condición es válida, activar la oferta
        if ($condicion_valida) {
            $sql_desplegar = "UPDATE ofertas SET Estado = 'Activada' WHERE Oferta = ?";
            $stmt_desplegar = mysqli_prepare($conexion, $sql_desplegar);
            mysqli_stmt_bind_param($stmt_desplegar, 'i', $oferta_id);

            if (mysqli_stmt_execute($stmt_desplegar)) {
                header('Location: gestionar_promociones.php?success=true');
                exit;
            } else {
                error_log("Error al desplegar la oferta con ID $oferta_id: " . mysqli_error($conexion), 0);
                echo "Ocurrió un problema al intentar desplegar la oferta. Por favor, inténtalo más tarde.";
            }

            mysqli_stmt_close($stmt_desplegar);
        }
    } else {
        // Si la oferta no existe o hay datos incompletos, redirigir a modificación
        header('Location: modificar_oferta.php?oferta=' . $oferta_id);
        exit;
    }

    mysqli_stmt_close($stmt_verificar);
    mysqli_close($conexion);
} else {
    echo "Error: No se especificó ninguna oferta para desplegar.";
}
?>

