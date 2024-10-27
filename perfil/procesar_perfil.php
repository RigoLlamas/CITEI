<?php
include '../php/conexion.php';

session_start();

$pk_usuario = $_SESSION['id_usuario'];

// Obtener los datos del formulario
$nombre = $conexion->real_escape_string($_POST['nombre']);
$apellidos = $conexion->real_escape_string($_POST['apellidos']);
$telefono = $conexion->real_escape_string($_POST['numero']);
$empresa = $conexion->real_escape_string($_POST['empresa']);
$calle = $conexion->real_escape_string($_POST['calle']);
$num_exterior = $conexion->real_escape_string($_POST['num_exterior']);
$num_interior = $conexion->real_escape_string($_POST['num_interior']);
$municipio = (int)$_POST['municipio'];
$notificaciones = (int)$_POST['notificacion'];

// Consulta para actualizar los datos del usuario
$sql = "UPDATE usuarios SET Nombres = '$nombre', Apellidos = '$apellidos', Telefono = '$telefono', Empresa = '$empresa', Calle = '$calle', NumExterior = '$num_exterior', NumInterior = '$num_interior', FK_Municipio = $municipio, Notificaciones = $notificaciones WHERE PK_Usuario = $pk_usuario";

if (mysqli_query($conexion, $sql)) {
    echo "Perfil actualizado correctamente.";
} else {
    echo "Error al actualizar el perfil: " . mysqli_error($conexion);
}

mysqli_close($conexion);

// Redirigir a la página de perfil o mostrar un mensaje de éxito
header("Location: perfil_usuario.php?mensaje=Perfil actualizado");
exit();
?>
