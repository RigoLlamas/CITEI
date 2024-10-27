<?php
session_start();

if (isset($_POST['codigo_verificacion'])) {
    $_SESSION['codigo_verificacion'] = $_POST['codigo_verificacion'];
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Código no recibido']);
}
?>
