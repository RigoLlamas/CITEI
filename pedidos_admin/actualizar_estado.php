<?php
// Incluir la conexión a la base de datos
include('../php/conexion.php');

// Verificar que la solicitud es POST y que se enviaron los datos necesarios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decodificar el JSON enviado desde el JavaScript en pedidos.php
    $data = json_decode(file_get_contents("php://input"), true);

    if (!empty($data['numVenta']) && !empty($data['nuevoEstado'])) {
        // Obtener los datos de NumVenta y el nuevo estado
        $numVenta = $data['numVenta'];
        $nuevoEstado = $data['nuevoEstado'];

        // Preparar y ejecutar la consulta para actualizar el estado
        $consulta = "UPDATE pedidos SET Estado = ? WHERE NumVenta = ?";
        $stmt = $conexion->prepare($consulta);
        $stmt->bind_param("si", $nuevoEstado, $numVenta);

        if ($stmt->execute()) {
            // Enviar respuesta de éxito
            echo json_encode(["success" => true, "message" => "Estado actualizado correctamente."]);
        } else {
            // Enviar respuesta de error
            echo json_encode(["success" => false, "message" => "Error al actualizar el estado en la base de datos."]);
        }

        // Cerrar la declaración
        $stmt->close();
    } else {
        // Respuesta si faltan datos
        echo json_encode(["success" => false, "message" => "Faltan datos para actualizar el estado."]);
    }

    // Cerrar la conexión
    $conexion->close();
} else {
    // Enviar respuesta de error si no es una solicitud POST
    echo json_encode(["success" => false, "message" => "Solicitud inválida."]);
}
?>
