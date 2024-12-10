<?php
session_start();
include '../php/conexion.php';

if (isset($_SESSION['codigo_verificacion'])) {
    $codigo_enviado = $_SESSION['codigo_verificacion'];
} else {
    // Handle the missing verification code scenario, e.g., redirect or show an error
    echo "Código de verificación no encontrado. Por favor, vuelva a enviar el formulario.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo_ingresado = $_POST['clave'];

    if ($codigo_ingresado == $codigo_enviado) {
        include '../php/geocoding.php';
        $nombre = trim($_SESSION['nombre']);
        $apellidos = trim($_SESSION['apellidos']);
        $email = trim($_SESSION['email']);
        $contrasena = trim($_SESSION['contrasena']);
        $numero = trim($_SESSION['numero']);
        $empresa = trim($_SESSION['empresa']);
        $municipio = trim($_SESSION['municipio']);
        $calle = trim($_SESSION['calle']);
        $codigo_postal = trim($_SESSION['codigo_postal']);
        $num_interior = trim($_SESSION['num_interior']);
        $num_exterior = trim($_SESSION['num_exterior']);
        $notificacion = isset($_SESSION['notificacion']) && $_SESSION['notificacion'] !== '' ? (int)$_SESSION['notificacion'] : 0;

        if ($municipio == NULL) {
            $direccion_completa = $calle . " " . $num_exterior . ", CP " . $codigo_postal;
        } else {
            $direccion_completa = $calle . " " . $num_exterior . ", " . $municipio . ", CP " . $codigo_postal;
        }

        $coordenadas = obtenerCoordenadas($direccion_completa);

        if ($coordenadas) {
            $latitud = $coordenadas ? $coordenadas['latitud'] : "NULL";
            $longitud = $coordenadas ? $coordenadas['longitud'] : "NULL";
        } else {
            $latitud = "NULL";
            $longitud = "NULL";
        }

        $sql = "INSERT INTO usuarios (FK_Municipio, Nombres, Apellidos, Empresa, Calle, Correo, Clave, Telefono, NumInterior, NumExterior, Notificaciones, CP, Latitud, Longitud)
            VALUES ('$municipio', '$nombre', '$apellidos', '$empresa', '$calle', '$email', '$contrasena', '$numero', '$num_interior', '$num_exterior', '$notificacion', '$codigo_postal', '$latitud', '$longitud')";
        if ($conexion->query($sql) === TRUE) {
            header("Location: ../login/login.html");
            exit();
        } else {
            echo "Error al insertar el registro: " . $conexion->error;
        }

        // Cerrar la conexión
        $conexion->close();
    } else {
        $error_clave = true;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Ingresar Clave</title>
    <script src="../js/navbar.js"></script>
    <script src="../js/pie.js"></script>
</head>

<body>
    <div class="login__contenedor">
        <form id="loginForm" class="login__formulario" method="POST" action="">
            <p id="errorMessage">Ingrese el código enviado</p>
            <div class="login__datos" style="margin-bottom: 5.84rem;">
                <label for="clave">Clave recibida</label>
                <input autocomplete="off" id="clave" type="text" name="clave" required>
            </div>
            <div class="login__botones">
                <button class="login__boton" type="submit">Ingresar</button>
            </div>
        </form>
    </div>
    <script>
        <?php if ($error_clave): ?>
            document.getElementById('clave').placeholder = "Clave incorrecta, intenta de nuevo";
        <?php endif; ?>
    </script>

</body>

</html>