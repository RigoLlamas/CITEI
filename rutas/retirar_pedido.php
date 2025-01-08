<?php
include '../php/conexion.php';
include '../php/solo_admins.php';

$entregaId = intval($_GET['entrega']);
$nomina = intval($_GET['nomina']);

$response = [];

if ($entregaId && $nomina) {
    // Obtener los números de venta relacionados al envío
    $queryVentas = "SELECT NumVenta FROM envios WHERE Entrega = ? AND Repartidor = ?";
    $stmtVentas = $conexion->prepare($queryVentas);
    $stmtVentas->bind_param("ii", $entregaId, $nomina);
    $stmtVentas->execute();
    $resultVentas = $stmtVentas->get_result();

    if ($resultVentas->num_rows > 0) {
        $ventas = [];
        while ($row = $resultVentas->fetch_assoc()) {
            $ventas[] = $row['NumVenta'];
        }

        // Eliminar el envío
        $queryDelete = "DELETE FROM envios WHERE Entrega = ? AND Repartidor = ?";
        $stmtDelete = $conexion->prepare($queryDelete);
        $stmtDelete->bind_param("ii", $entregaId, $nomina);
        if ($stmtDelete->execute()) {
            // Actualizar el estado de los pedidos relacionados
            $queryUpdate = "UPDATE pedidos SET Estado = 'Retirado' WHERE NumVenta IN (" . implode(',', $ventas) . ")";
            if ($conexion->query($queryUpdate)) {
                $response['success'] = true;
            } else {
                $response['success'] = false;
                $response['error'] = $conexion->error;
            }
        } else {
            $response['success'] = false;
            $response['error'] = $stmtDelete->error;
        }
        $stmtDelete->close();
    } else {
        $response['success'] = false;
        $response['error'] = "No se encontraron pedidos para este envío.";
    }
    $stmtVentas->close();
} else {
    $response['success'] = false;
    $response['error'] = "Datos insuficientes.";
}

echo json_encode($response);
?>
