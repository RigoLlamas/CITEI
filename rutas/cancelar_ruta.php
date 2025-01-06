<?php
include '../php/conexion.php';
include '../php/solo_admins.php';

$nomina = intval($_GET['nomina']);
$response = [];

if ($nomina) {
    $conexion->begin_transaction();

    try {
        // Obtener los números de venta relacionados a los envíos del repartidor
        $queryVentas = "SELECT NumVenta FROM envios WHERE Repartidor = ?";
        $stmtVentas = $conexion->prepare($queryVentas);
        $stmtVentas->bind_param("i", $nomina);
        $stmtVentas->execute();
        $resultVentas = $stmtVentas->get_result();

        $ventas = [];
        while ($row = $resultVentas->fetch_assoc()) {
            $ventas[] = $row['NumVenta'];
        }
        $stmtVentas->close();

        // Si hay pedidos relacionados, actualizarlos a "Retirado"
        if (!empty($ventas)) {
            $queryActualizarPedidos = "UPDATE pedidos SET Estado = 'Retirado' WHERE NumVenta IN (" . implode(',', $ventas) . ")";
            $conexion->query($queryActualizarPedidos);
        }

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
