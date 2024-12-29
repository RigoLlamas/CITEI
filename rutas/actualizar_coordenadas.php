<?php
include '../php/conexion.php';

if (isset($_GET['nomina'])) {
    $nominaRepartidor = intval($_GET['nomina']);
    $query = "
        SELECT Longitud, Latitud 
        FROM repartidor 
        WHERE Nomina = $nominaRepartidor
    ";
    $result = $conexion->query($query);

    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'No se encontró la ubicación.']);
    }
    $result->free();
} else {
    echo json_encode(['error' => 'Nómina no proporcionada.']);
}
$conexion->close();
?>
