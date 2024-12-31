<?php
session_start();
include '../php/conexion.php';

if (isset($_SESSION['codigo_verificacion'])) {
    $codigo_enviado = $_SESSION['codigo_verificacion'];

    // Agregar una bandera para mostrar el mensaje de SweetAlert
    $mostrar_mensaje_correo = true;
} else {
    // Manejar el caso de código de verificación faltante
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

        $direccion_completa = $municipio === NULL
            ? $calle . " " . $num_exterior . ", CP " . $codigo_postal
            : $calle . " " . $num_exterior . ", " . $municipio . ", CP " . $codigo_postal;

        $coordenadas = obtenerCoordenadas($direccion_completa);

        $latitud = $coordenadas ? $coordenadas['latitud'] : "NULL";
        $longitud = $coordenadas ? $coordenadas['longitud'] : "NULL";

        $sql = "INSERT INTO usuarios (FK_Municipio, Nombres, Apellidos, Empresa, Calle, Correo, Clave, Telefono, NumInterior, NumExterior, Notificaciones, CP, Latitud, Longitud)
            VALUES ('$municipio', '$nombre', '$apellidos', '$empresa', '$calle', '$email', '$contrasena', '$numero', '$num_interior', '$num_exterior', '$notificacion', '$codigo_postal', '$latitud', '$longitud')";

        if ($conexion->query($sql) === TRUE) {
            header("Location: ../login/login.html");
            exit();
        } else {
            echo "Error al insertar el registro: " . $conexion->error;
        }

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
    <!-- Incluir SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        // Mostrar el mensaje de SweetAlert al cargar la página
        <?php if (isset($mostrar_mensaje_correo) && $mostrar_mensaje_correo): ?>
            Swal.fire({
                icon: 'success',
                title: 'Correo enviado',
                text: 'Se ha enviado un correo electrónico con el código de verificación a tu correo registrado.',
                confirmButtonText: 'Aceptar'
            });
        <?php endif; ?>

        <?php if (isset($error_clave) && $error_clave): ?>
            document.getElementById('clave').placeholder = "Clave incorrecta, intenta de nuevo";
        <?php endif; ?>
    </script>
</body>

</html>
