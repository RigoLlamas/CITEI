<?php
include '../php/conexion.php';
session_start();

// Si el usuario no es admin (id_usuario != 1), se muestra la alerta de acceso restringido
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_usuario'] != '1') {
?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Clientes</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>

    <body>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Acceso Restringido',
                text: 'No tienes permisos para acceder a esta sección.',
                confirmButtonText: 'Ir al inicio'
            }).then(() => {
                window.location.href = '../login/login.html';
            });
        </script>
    </body>

    </html>
    <?php
    exit();
}


if (isset($_GET['alertado']) && $_GET['alertado'] == '1') {
    $_SESSION['alertCambioAceite'] = true;
}

if (!isset($_SESSION['alertCambioAceite']) || $_SESSION['alertCambioAceite'] !== true) {


    // Consulta para detectar vehículos con más de 10,000 Km
    $sql = "SELECT Placa FROM vehiculo WHERE KilometrosRecorridos > 10000";
    $resultado = mysqli_query($conexion, $sql);

    // Si existe al menos un vehículo con más de 10,000 km,
    // mostramos la alerta
    if ($resultado && mysqli_num_rows($resultado) > 0) {
    ?>
        <!-- Cargamos SweetAlert si hay algún resultado -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                title: 'Cambio de aceite recomendado',
                text: 'Existen vehículos con más de 10,000 Km. Se recomienda realizar un cambio de aceite.',
                icon: 'warning',
                confirmButtonText: 'Enterado'
            }).then((result) => {
                // Redirigimos a la misma página, pero con ?alertado=1
                // para no volver a mostrar la alerta en esta sesión
                window.location.href = '?alertado=1';
            });
        </script>
<?php
    }
}
?>