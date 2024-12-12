<?php
include '../php/conexion.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['ofertaId'])) {
        $ofertaId = intval($data['ofertaId']);
        $idUsuario = $_SESSION['id_usuario'];

        // Verificar si la oferta está en estado "Solicitada"
        $consultaVerificar = "
            SELECT EstadoUso 
            FROM asignacion_ofertas 
            WHERE Oferta = ? AND Usuario = ? AND EstadoUso = 'Solicitada'
        ";
        $stmtVerificar = $conexion->prepare($consultaVerificar);
        $stmtVerificar->bind_param('ii', $ofertaId, $idUsuario);
        $stmtVerificar->execute();
        $resultadoVerificar = $stmtVerificar->get_result();

        if ($resultadoVerificar->num_rows > 0) {
            // Cambiar el estado de la oferta a "No utilizada"
            $actualizarEstado = "
                UPDATE asignacion_ofertas 
                SET EstadoUso = 'No utilizada', FechaUso = NULL
                WHERE Oferta = ? AND Usuario = ?
            ";
            $stmtActualizar = $conexion->prepare($actualizarEstado);
            $stmtActualizar->bind_param('ii', $ofertaId, $idUsuario);

            if ($stmtActualizar->execute()) {
                unset($_SESSION['descuento']);
                echo json_encode(['success' => true, 'message' => 'La oferta ha sido cancelada correctamente.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No se pudo cancelar la oferta. Intenta nuevamente.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'La oferta no está en estado "Solicitada".']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID de oferta no recibido.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>
