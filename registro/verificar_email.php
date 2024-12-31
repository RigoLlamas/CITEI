<?php
header('Content-Type: application/json');

include '../php/conexion.php';

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (isset($data['email'])) {
    $email = $data['email'];

    // Consulta SQL para verificar la existencia del correo
    $consulta_sql = "SELECT * FROM usuarios WHERE Correo = ?";
    $stmt = $conexion->prepare($consulta_sql);

    if (!$stmt) {
        echo json_encode(['error' => 'Error al preparar la consulta: ' . $conexion->error]);
        exit;
    }

    // Asignar parámetros y ejecutar
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // Verificar si existe el correo
    $resultado = $stmt->get_result();
    $existe = $resultado->num_rows > 0;

    // Enviar respuesta JSON
    echo json_encode(['exists' => $existe]);
    $stmt->close();
} else {
    echo json_encode(['error' => 'No se proporcionó un email']);
}
$conexion->close();
?>
