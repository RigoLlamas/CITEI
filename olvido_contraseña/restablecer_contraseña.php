<?php
session_start();
include '../php/conexion.php'; 

// Variable para manejar errores de validación
$errorClave = "";
$successClave = "";

if (!isset($_SESSION['correo'])) {
    header("Location: olvido_contraseña.php?error=no_sesion");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['nueva_clave']) && isset($_POST['confirmar_clave'])) {
        $nuevaClave = $_POST['nueva_clave'];
        $confirmarClave = $_POST['confirmar_clave'];

        // Verificar que ambas claves coincidan
        if ($nuevaClave === $confirmarClave) {
            // Validar contraseña segura
            if (strlen($nuevaClave) >= 8 && preg_match('/[A-Za-z]/', $nuevaClave) && preg_match('/[0-9]/', $nuevaClave)) {
                try {
                    $correoUsuario = $_SESSION['correo'];

                    // Actualizar la contraseña en la base de datos
                    $sql = "UPDATE usuarios SET Clave = ? WHERE Correo = ?";
                    $stmt = $conexion->prepare($sql);
                    $stmt->bind_param("ss", $nuevaClave, $correoUsuario);

                    if ($stmt->execute()) {
                        $successClave = "¡Contraseña actualizada correctamente!";
                        session_destroy();
                    } else {
                        $errorClave = "Hubo un error al actualizar la contraseña.";
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $errorClave = "Error del servidor: " . $e->getMessage();
                }
            } else {
                $errorClave = "La contraseña debe tener al menos 8 caracteres, incluir letras y números.";
            }
        } else {
            $errorClave = "Las contraseñas no coinciden.";
        }
    }
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Restablecer contraseña</title>
    <script src="../js/navbar.js"></script>
    <script src="../js/pie.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="login__contenedor">
        <form id="restablecerForm" class="login__formulario" action="restablecer_contraseña.php" method="POST">
            <div class="login__datos" style="margin-bottom: 5.84rem;">
                <label for="nueva_clave">Nueva contraseña</label>
                <input id="nueva_clave" type="password" name="nueva_clave" 
                minlength="8" maxlength="50"
                placeholder="Ingrese su nueva contraseña"
                autocomplete="new-password"
                required>
            </div>
            <div class="login__datos" style="margin-bottom: 5.84rem;">
                <label for="confirmar_clave">Confirmar contraseña</label>
                <input id="confirmar_clave" type="password" name="confirmar_clave" 
                minlength="8" maxlength="50"
                placeholder="Confirme su nueva contraseña"
                autocomplete="new-password"
                required>
            </div>
            <div class="login__botones">
                <button class="login__boton" type="submit">Restablecer contraseña</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            <?php if (!empty($errorClave)): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: <?php echo json_encode($errorClave); ?>, // Escapar correctamente el texto
                    confirmButtonText: 'Aceptar'
                });
            <?php endif; ?>

            <?php if (!empty($successClave)): ?>
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: <?php echo json_encode($successClave); ?>, // Escapar correctamente el texto
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    window.location.href = '../login/login.html';
                });
            <?php endif; ?>
        });
    </script>

</body>
</html>
