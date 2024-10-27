<?php
include 'conexion.php'; 
include 'verificar_existencia.php'; 

if (isset($_POST['email'])) {
    $email = $_POST['email'];

    $consulta_sql = "SELECT * FROM usuarios WHERE Correo = ?";

    $parametros = ["s", $email]; 

    if (verificar_existencia($consulta_sql, $parametros)) {
        echo json_encode(['exists' => true]);
    } else {
        echo json_encode(['exists' => false]);
    }
}
?>
