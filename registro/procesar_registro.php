<?php
session_start();

$_SESSION['nombre'] = $_POST['nombre'];
$_SESSION['apellidos'] = $_POST['apellidos'];
$_SESSION['email'] = $_POST['email'];
$_SESSION['contrasena'] = $_POST['contraseña'];
$_SESSION['numero'] = $_POST['numero'];
$_SESSION['empresa'] = $_POST['empresa'];
$_SESSION['municipio'] = $_POST['municipio'];
$_SESSION['calle'] = $_POST['calle'];
$_SESSION['codigo_postal'] = $_POST['codigo'];
$_SESSION['num_interior'] = $_POST['num_interior'];
$_SESSION['num_exterior'] = $_POST['num_exterior'];

if (isset($_POST['notificacion'])) {
    $_SESSION['notificaciones'] = ($_POST['notificacion'] === 'true') ? true : false;
}

header("Location: ingresar_clave.php");
exit();
?>
