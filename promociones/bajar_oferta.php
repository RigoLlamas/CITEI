<?php
include '../php/conexion.php';
include '../php/solo_admins.php';

// Verificar si se ha pasado el ID de la oferta
if (isset($_GET['oferta'])) {
    $oferta_id = (int)$_GET['oferta'];

    // Confirmar que la oferta existe antes de eliminarla
    $sql_verificar = "SELECT Condicion FROM ofertas WHERE Oferta = ?";
    $stmt_verificar = mysqli_prepare($conexion, $sql_verificar);
    mysqli_stmt_bind_param($stmt_verificar, 'i', $oferta_id);
    mysqli_stmt_execute($stmt_verificar);
    $result_verificar = mysqli_stmt_get_result($stmt_verificar);

    if (mysqli_num_rows($result_verificar) > 0) {
        $row = mysqli_fetch_assoc($result_verificar);
        $condicion_id = $row['Condicion']; // Obtener el ID de la condición asociada

        // Eliminar las asignaciones de la oferta (en la tabla asignacion_ofertas)
        $sql_eliminar_asignaciones = "DELETE FROM asignacion_ofertas WHERE Oferta = ?";
        $stmt_eliminar_asignaciones = mysqli_prepare($conexion, $sql_eliminar_asignaciones);
        mysqli_stmt_bind_param($stmt_eliminar_asignaciones, 'i', $oferta_id);
        mysqli_stmt_execute($stmt_eliminar_asignaciones);

        // Eliminar la oferta
        $sql_eliminar_oferta = "DELETE FROM ofertas WHERE Oferta = ?";
        $stmt_eliminar_oferta = mysqli_prepare($conexion, $sql_eliminar_oferta);
        mysqli_stmt_bind_param($stmt_eliminar_oferta, 'i', $oferta_id);

        if (mysqli_stmt_execute($stmt_eliminar_oferta)) {
            // Eliminar la condición asociada
            $sql_eliminar_condicion = "DELETE FROM condiciones WHERE Condicion = ?";
            $stmt_eliminar_condicion = mysqli_prepare($conexion, $sql_eliminar_condicion);
            mysqli_stmt_bind_param($stmt_eliminar_condicion, 'i', $condicion_id);
            mysqli_stmt_execute($stmt_eliminar_condicion);

            echo "Oferta y su condición eliminadas correctamente.";
            header('Location: gestionar_promociones.php?success=true');
            exit();
        } else {
            echo "Error al eliminar la oferta: " . mysqli_error($conexion);
        }

        mysqli_stmt_close($stmt_eliminar_oferta);
        mysqli_stmt_close($stmt_eliminar_asignaciones);
        mysqli_stmt_close($stmt_eliminar_condicion);
    } else {
        echo "Error: La oferta no existe.";
    }

    mysqli_stmt_close($stmt_verificar);
    mysqli_close($conexion);
} else {
    echo "Error: No se especificó ninguna oferta para eliminar.";
}
?>
