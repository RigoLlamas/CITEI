<?php
include('../php/conexion.php');
require '../php/notificacion.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/error_log.log');
header('Content-Type: application/json');

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

            $consultaCorreo = "SELECT u.Correo, u.Notificaciones 
                               FROM usuarios u 
                               JOIN pedidos p ON u.PK_Usuario = p.FK_Usuario 
                               WHERE p.NumVenta = ?";
            $stmtCorreo = $conexion->prepare($consultaCorreo);
            $stmtCorreo->bind_param("i", $numVenta);
            $stmtCorreo->execute();
            $stmtCorreo->bind_result($correoDestino, $notificaciones);

            if ($stmtCorreo->fetch()) {
                if ($notificaciones) {
                    $mensaje = "El estado de tu pedido #$numVenta ha sido actualizado a: $nuevoEstado.";
                    if (!enviarCorreoNotificacion($correoDestino, $mensaje)) {
                        $response = ["success" => false, "message" => "Error al enviar el correo."];
                    }
                } else {
                    $response["message"] .= " El usuario tiene las notificaciones deshabilitadas.";
                }
            } else {
                $response = ["success" => false, "message" => "No se encontró el correo del usuario."];
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

// Hacer un solo echo al final con la respuesta acumulada
echo json_encode($response);
?>
