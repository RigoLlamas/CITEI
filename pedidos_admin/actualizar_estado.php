<?php
include('../php/conexion.php');
require '../php/notificacion.php';

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
            echo json_encode(["success" => true, "message" => "Estado actualizado correctamente."]);

            // Consulta para obtener el correo y el campo de notificación del usuario asociado a la venta
            $consultaCorreo = "SELECT u.Correo, u.Notificaciones 
                               FROM usuarios u 
                               JOIN pedidos p ON u.PK_Usuario = p.FK_Usuario 
                               WHERE p.NumVenta = ?";
            $stmtCorreo = $conexion->prepare($consultaCorreo);
            $stmtCorreo->bind_param("i", $numVenta);
            $stmtCorreo->execute();
            $stmtCorreo->bind_result($correoDestino, $notificaciones);

            if ($stmtCorreo->fetch()) {
                // Verificar si el usuario tiene habilitadas las notificaciones
                if ($notificaciones) {
                    // Enviar notificación al correo del usuario
                    $mensaje = "El estado de tu pedido #$numVenta ha sido actualizado a: $nuevoEstado.";
                    if (!enviarCorreoNotificacion($correoDestino, $mensaje)) {
                        echo json_encode(["success" => false, "message" => "Error al enviar el correo."]);
                    }
                } else {
                    echo json_encode(["success" => true, "message" => "Estado actualizado, pero el usuario tiene las notificaciones deshabilitadas."]);
                }
            } else {
                echo json_encode(["success" => false, "message" => "No se encontró el correo del usuario."]);
            }

            // Cerrar la declaración de correo
            $stmtCorreo->close();
        } else {
            // Enviar respuesta de error
            echo json_encode(["success" => false, "message" => "Error al actualizar el estado en la base de datos."]);
        }
        $stmt->close();
    } else {
        // Respuesta si faltan datos
        echo json_encode(["success" => false, "message" => "Faltan datos para actualizar el estado."]);
    }
    $conexion->close();
} else {
    echo json_encode(["success" => false, "message" => "Solicitud inválida."]);
}
?>
