<?php
include '../php/conexion.php';

// Obtener el ID de la oferta desde la URL
if (isset($_GET['oferta'])) {
    $oferta_id = (int)$_GET['oferta'];

    // Verificar si todos los campos de la oferta están completos
    $sql_verificar = "SELECT Tipo, Valor, Despliegue, Expiracion, Condicion, Producto FROM ofertas WHERE Oferta = ?";
    $stmt_verificar = mysqli_prepare($conexion, $sql_verificar);
    mysqli_stmt_bind_param($stmt_verificar, 'i', $oferta_id);
    mysqli_stmt_execute($stmt_verificar);
    $result_verificar = mysqli_stmt_get_result($stmt_verificar);
    $oferta = mysqli_fetch_assoc($result_verificar);

    // Verificar que los campos no estén vacíos o nulos
    if ($oferta && !empty($oferta['Tipo']) && !empty($oferta['Valor']) && !empty($oferta['Despliegue']) && !empty($oferta['Expiracion']) && !empty($oferta['Condicion']) && isset($oferta['Producto'])) {
        // Todos los campos están completos, actualizar el estado de la oferta a "Activada" y la fecha de despliegue
        $sql_desplegar = "UPDATE ofertas SET Estado = 'Activada' WHERE Oferta = ?";
        $stmt_desplegar = mysqli_prepare($conexion, $sql_desplegar);
        mysqli_stmt_bind_param($stmt_desplegar, 'i', $oferta_id);

        if (mysqli_stmt_execute($stmt_desplegar)) {
            // Redirigir a la página de gestión de promociones si la oferta se activó correctamente
            header('Location: gestionar_promociones.php');
        } else {
            echo "Error al desplegar la oferta: " . mysqli_error($conexion);
        }

        mysqli_stmt_close($stmt_desplegar);
    } else {
        // Si hay campos vacíos, redirigir a la página de modificación
        header('Location: modificar_oferta.php?oferta=' . $oferta_id);
    }

    mysqli_stmt_close($stmt_verificar);
    mysqli_close($conexion);
} else {
    echo "Error: No se especificó ninguna oferta para desplegar.";
}
?>
