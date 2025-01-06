<?php
session_start();
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
?>