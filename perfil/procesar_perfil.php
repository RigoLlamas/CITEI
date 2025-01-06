<?php
include '../php/conexion.php';
include '../php/geocoding.php';

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

if ($municipio != 0) {
    // Consulta para obtener el nombre del municipio
    $sql_municipio = "SELECT Municipio FROM municipio WHERE PK_Municipio = $municipio";
    $resultado_municipio = mysqli_query($conexion, $sql_municipio);

    if ($resultado_municipio && mysqli_num_rows($resultado_municipio) > 0) {
        $row_municipio = mysqli_fetch_assoc($resultado_municipio);
        $nombre_municipio = $row_municipio['Municipio'];
        $direccion_completa = $calle . " " . $num_exterior . ", " . $nombre_municipio;
    } else {
        // En caso de que no se encuentre el municipio
        $direccion_completa = $calle . " " . $num_exterior;
    }
} else {
    $direccion_completa = $calle . " " . $num_exterior;
}


$coordenadas = obtenerCoordenadas($direccion_completa);

if ($coordenadas) {
    $latitud = $coordenadas['latitud'];
    $longitud = $coordenadas['longitud'];
} else {
    $latitud = null;
    $longitud = null;
}

// Consulta para actualizar los datos del usuario
$sql = "UPDATE usuarios 
        SET Nombres = '$nombre', 
            Apellidos = '$apellidos', 
            Telefono = '$telefono', 
            Empresa = '$empresa', 
            Calle = '$calle', 
            NumExterior = '$num_exterior', 
            NumInterior = '$num_interior', 
            FK_Municipio = $municipio, 
            Notificaciones = $notificaciones, 
            Latitud = " . ($latitud !== null ? "'$latitud'" : "NULL") . ", 
            Longitud = " . ($longitud !== null ? "'$longitud'" : "NULL") . " 
        WHERE PK_Usuario = $pk_usuario";

if (mysqli_query($conexion, $sql)) {
    header("Location: perfil_usuario.php?mensaje=success");
} else {
    header("Location: perfil_usuario.php?mensaje=error");
}
mysqli_close($conexion);
exit();
