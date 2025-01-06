<?php
session_start();
include '../php/conexion.php';
include '../php/geocoding.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Almacenar los datos enviados en sesión (persistencia temporal)
    $_SESSION['codigo_ingresado'] = $_POST['clave'] ?? $_SESSION['codigo_ingresado'] ?? 0;
    $_SESSION['codigo_verificacion'] = $_POST['codigo_verificacion'] ?? $_SESSION['codigo_verificacion'] ?? null;
    $_SESSION['nombre'] = isset($_POST['nombre']) ? trim($_POST['nombre']) : $_SESSION['nombre'] ?? null;
    $_SESSION['apellidos'] = isset($_POST['apellidos']) ? trim($_POST['apellidos']) : $_SESSION['apellidos'] ?? null;
    $_SESSION['email'] = isset($_POST['email']) ? trim($_POST['email']) : $_SESSION['email'] ?? null;
    $_SESSION['contrasena'] = isset($_POST['contraseña']) ? trim($_POST['contraseña']) : $_SESSION['contrasena'] ?? null;
    $_SESSION['numero'] = isset($_POST['numero']) ? trim($_POST['numero']) : $_SESSION['numero'] ?? null;
    $_SESSION['empresa'] = isset($_POST['empresa']) ? trim($_POST['empresa']) : $_SESSION['empresa'] ?? null;
    $_SESSION['municipio'] = isset($_POST['municipio']) ? trim($_POST['municipio']) : $_SESSION['municipio'] ?? null;
    $_SESSION['calle'] = isset($_POST['calle']) ? trim($_POST['calle']) : $_SESSION['calle'] ?? null;
    $_SESSION['codigo_postal'] = isset($_POST['codigo']) ? trim($_POST['codigo']) : $_SESSION['codigo_postal'] ?? null;
    $_SESSION['num_interior'] = isset($_POST['num_interior']) ? trim($_POST['num_interior']) : $_SESSION['num_interior'] ?? null;
    $_SESSION['num_exterior'] = isset($_POST['num_exterior']) ? trim($_POST['num_exterior']) : $_SESSION['num_exterior'] ?? null;
    $_SESSION['notificacion'] = isset($_POST['notificacion']) && $_POST['notificacion'] === 'true' ? 1 : $_SESSION['notificacion'] ?? 0;

    // Verificar si se recibió el código de verificación
    if ($_SESSION['codigo_verificacion']) {
        if ($_SESSION['codigo_ingresado'] != 0) {


            if ($_SESSION['codigo_ingresado'] == $_SESSION['codigo_verificacion']) {
                // Construir dirección completa
                $direccion_completa = $_SESSION['municipio'] === null
                    ? $_SESSION['calle'] . " " . $_SESSION['num_exterior'] . ", CP " . $_SESSION['codigo_postal']
                    : $_SESSION['calle'] . " " . $_SESSION['num_exterior'] . ", " . $_SESSION['municipio'] . ", CP " . $_SESSION['codigo_postal'];

                // Obtener coordenadas de la dirección
                $coordenadas = obtenerCoordenadas($direccion_completa);
                $latitud = $coordenadas ? $coordenadas['latitud'] : null;
                $longitud = $coordenadas ? $coordenadas['longitud'] : null;

                $stmt = $conexion->prepare("
                INSERT INTO usuarios 
                (FK_Municipio, Nombres, Apellidos, Empresa, Calle, Correo, Clave, Telefono, NumInterior, NumExterior, Notificaciones, CP, Latitud, Longitud) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
                $stmt->bind_param(
                    'issssssssssidd',
                    $_SESSION['municipio'],
                    $_SESSION['nombre'],
                    $_SESSION['apellidos'],
                    $_SESSION['empresa'],
                    $_SESSION['calle'],
                    $_SESSION['email'],
                    $_SESSION['contrasena'],
                    $_SESSION['numero'],
                    $_SESSION['num_interior'],
                    $_SESSION['num_exterior'],
                    $_SESSION['notificacion'],
                    $_SESSION['codigo_postal'],
                    $latitud,
                    $longitud
                );

                // Ejecutar y manejar el resultado
                if ($stmt->execute()) {
                    header("Location: ../login/login.html");
                    exit();
                } else {
                    echo "Error al insertar el registro: " . $stmt->error;
                }

                $stmt->close();
            } else {
                $error_clave = true;
            }
        }
    } else {
        echo "Código de verificación no encontrado. Por favor, vuelva a enviar el formulario.";
        exit();
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
        <?php
        if ($_SESSION['notificacion'] != true) {

        ?>
            Swal.fire({
                icon: 'success',
                title: 'Correo enviado',
                text: 'Se ha enviado un correo electrónico con el código de verificación a tu correo registrado.',
                confirmButtonText: 'Aceptar'
            });
        <?php
            $_SESSION['notificacion'] = true;
        }
        ?>
        // Validar errores de clave
        <?php if (isset($error_clave) && $error_clave): ?>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: "Error",
                    text: "Clave incorrecta, intenta de nuevo.",
                    icon: "error",
                    confirmButtonText: "Aceptar"
                }).then(() => {
                    document.getElementById('clave').placeholder = "Clave incorrecta, intenta de nuevo";
                    document.getElementById('clave').value = ""; // Opcional: limpiar el campo
                });
            });
        <?php endif; ?>
    </script>
</body>

</html>