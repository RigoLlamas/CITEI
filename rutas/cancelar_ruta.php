<?php
include '../php/conexion.php';

$nomina = intval($_GET['nomina']);
$response = [];

if ($nomina) {
    $conexion->begin_transaction();

    try {
        // Eliminar todos los envíos del repartidor
        $queryEliminar = "DELETE FROM envios WHERE Repartidor = ?";
        $stmtEliminar = $conexion->prepare($queryEliminar);
        $stmtEliminar->bind_param("i", $nomina);
        $stmtEliminar->execute();
        $stmtEliminar->close();

        // Actualizar el estado del repartidor a 'Disponible'
        $queryActualizar = "UPDATE repartidor SET Estado = 'Disponible' WHERE Nomina = ?";
        $stmtActualizar = $conexion->prepare($queryActualizar);
        $stmtActualizar->bind_param("i", $nomina);
        $stmtActualizar->execute();
        $stmtActualizar->close();

        $conexion->commit();
        $response['success'] = true;
    } catch (Exception $e) {
        $conexion->rollback();
        $response['success'] = false;
        $response['error'] = $e->getMessage();
    }
} else {
    $response['success'] = false;
    $response['error'] = "Datos insuficientes.";
}

echo json_encode($response);
?>
