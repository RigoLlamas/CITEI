<?php
header('Content-Type: application/json');

include 'conexion.php'; 
include 'verificar_existencia.php'; 

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (isset($data['email'])) {
    $email = $data['email'];

    $consulta_sql = "SELECT * FROM usuarios WHERE Correo = ?";

    $parametros = ["s", $email]; 

    $exists = verificar_existencia($consulta_sql, $parametros);
    
    echo json_encode(['exists' => $exists]);
} else {
    echo json_encode(['error' => 'No email provided']);
}
?>
