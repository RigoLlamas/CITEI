<?php
session_start();
include '../php/conexion.php'; 

// Variable para manejar errores de validación
$errorClave = "";

if (!isset($_SESSION['correo'])) {
    header("Location: olvido_contraseña.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['nueva_clave']) && isset($_POST['confirmar_clave'])) {
        $nuevaClave = $_POST['nueva_clave'];
        $confirmarClave = $_POST['confirmar_clave'];

        // Verificar que ambas claves coincidan
        if ($nuevaClave === $confirmarClave) {
                $claveEncriptada = $nuevaClave; //password_hash($nuevaClave, PASSWORD_DEFAULT);
                $correoUsuario = $_SESSION['correo'];

                // Actualizar la contraseña en la base de datos
                $sql = "UPDATE usuarios SET Clave = ? WHERE Correo = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ss", $claveEncriptada, $correoUsuario);
                if ($stmt->execute()) {
                    session_destroy();
                    header("Location: ../login/login.html");
                    exit();
                } else {
                    $errorClave = "Hubo un error al actualizar la contraseña.";
                }

        } else {
            $errorClave = "Las contraseñas no coinciden.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Restablecer contraseña</title>
    <script src="../js/navbar.js"></script>
    <script src="../js/pie.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="login__contenedor">
        <form id="restablecerForm" class="login__formulario" action="restablecer_contraseña.php" method="POST">
            <div class="login__datos" style="margin-bottom: 5.84rem;">
                <label for="nueva_clave">Nueva contraseña</label>
                <input id="nueva_clave" type="password" name="nueva_clave" 
                minlength="5" maxlength="50"
                placeholder="Ingrese su nueva contraseña"
                required>
            </div>
            <div class="login__datos" style="margin-bottom: 5.84rem;">
                <label for="confirmar_clave">Confirmar contraseña</label>
                <input id="confirmar_clave" type="password" name="confirmar_clave" 
                minlength="5" maxlength="50"
                placeholder="Confirme su nueva contraseña"
                required>
            </div>
            <div class="login__botones">
                <button class="login__boton" type="submit">Restablecer contraseña</button>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function () {
            // Mostrar el mensaje de error desde el servidor en el cliente
            <?php if (!empty($errorClave)): ?>
                $('#nueva_clave').val('');
                $('#confirmar_clave').val('');
                $('#nueva_clave').attr('placeholder', '<?php echo $errorClave; ?>');
            <?php endif; ?>
        });
    </script>
</body>
</html>