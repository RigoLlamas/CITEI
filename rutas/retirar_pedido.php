<?php
include '../php/conexion.php';

$entregaId = intval($_GET['entrega']);
$nomina = intval($_GET['nomina']);

$response = [];

if ($entregaId && $nomina) {
    $query = "DELETE FROM envios WHERE Entrega = ? AND Repartidor = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ii", $entregaId, $nomina);
    if ($stmt->execute()) {
        $response['success'] = true;
    } else {
        $response['success'] = false;
        $response['error'] = $stmt->error;
    }
    $stmt->close();
} else {
    $response['success'] = false;
    $response['error'] = "Datos insuficientes.";
}

echo json_encode($response);
?>
