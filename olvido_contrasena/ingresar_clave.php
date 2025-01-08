<?php
session_start();

// Verifica que el correo esté en la sesión
if (!isset($_SESSION['correo'])) {
    header("Location: olvido_contrasena.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Ingresar clave</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../js/navbar.js"></script>
    <script src="../js/pie.js"></script>
</head>

<body>
    <div class="login__contenedor">
        <form id="claveForm" class="login__formulario">
            <p>Ingrese el código enviado a su correo: <?php echo $_SESSION['correo']; ?></p>
            <div class="login__datos" style="margin-bottom: 5.84rem;">
                <label for="clave">Código recibido</label>
                <input id="clave" type="text" name="clave" required>
            </div>
            <div class="login__botones">
                <button class="login__boton" type="submit">Verificar</button>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function () {
            $('#claveForm').submit(function (event) {
                event.preventDefault(); // Evita el envío por defecto del formulario

                // Obtiene el código ingresado por el usuario
                var codigoIngresado = $('#clave').val().trim();
                
                // Obtiene el código almacenado en localStorage
                var codigoGuardado = localStorage.getItem('codigo_verificacion');

                // Verifica si ambos códigos coinciden
                if (codigoIngresado === codigoGuardado) {
                    // Si los códigos coinciden, redirigir a la página de restablecer contrasena
                    window.location.href = 'restablecer_contrasena.php';
                } else {
                    // Si el código no coincide, limpia el campo y muestra un mensaje de error
                    $('#clave').val('');
                    document.getElementById('clave').placeholder = "Código incorrecto, inténtalo de nuevo";
                }
            });
        });
    </script>
</body>

</html>
