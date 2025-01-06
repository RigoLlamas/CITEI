<?php
include('../php/conexion.php');
include '../php/solo_admins.php';
$response = ["success" => false, "message" => "Solicitud inválida."];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!empty($data['numVenta']) && !empty($data['nuevoEstado'])) {
        $numVenta = $data['numVenta'];
        $nuevoEstado = $data['nuevoEstado'];

        $consulta = "UPDATE pedidos SET Estado = ? WHERE NumVenta = ?";
        $stmt = $conexion->prepare($consulta);
        $stmt->bind_param("si", $nuevoEstado, $numVenta);

        if ($stmt->execute()) {
            $response = ["success" => true, "message" => "Estado actualizado correctamente."];

            // Obtener el correo del usuario y las notificaciones
            $consultaCorreo = "SELECT u.Correo, u.Notificaciones 
                               FROM usuarios u 
                               JOIN pedidos p ON u.PK_Usuario = p.FK_Usuario 
                               WHERE p.NumVenta = ?";
            $stmtCorreo = $conexion->prepare($consultaCorreo);
            $stmtCorreo->bind_param("i", $numVenta);
            $stmtCorreo->execute();
            $stmtCorreo->bind_result($correoDestino, $notificaciones);

            if ($stmtCorreo->fetch()) {
                if ($notificaciones && !empty($correoDestino)) {
                    // Si el usuario tiene notificaciones habilitadas y tiene un correo válido
                    $mensaje = "El estado de tu pedido #$numVenta ha sido actualizado a: $nuevoEstado.";
                    $response['email'] = [
                        "correoDestino" => $correoDestino,
                        "mensaje" => $mensaje
                    ];
                } else {
                    // Si las notificaciones están deshabilitadas o el correo está vacío
                    $response["message"] .= " El usuario tiene las notificaciones deshabilitadas o la dirección de correo está vacía.";
                }
            } else {
                $response["message"] = "No se encontró el correo del usuario asociado al pedido.";
            }
            $stmtCorreo->close();
        } else {
            $response = ["success" => false, "message" => "Error al actualizar el estado en la base de datos."];
        }
        $stmt->close();
    } else {
        $response = ["success" => false, "message" => "Faltan datos para actualizar el estado."];
    }
    $conexion->close();
}

echo json_encode($response);
?>
