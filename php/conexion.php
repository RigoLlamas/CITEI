<?php
    $config = include __DIR__ . '/../config.php';

    // Obtener detalles de conexión a la base de datos desde config.php
    $servername = $config['database']['host'];
    $username = $config['database']['user'];
    $password = $config['database']['password'];
    $dbname = $config['database']['dbname'];

    // Crear conexión y verificamos
    $conexion = new mysqli($servername, $username, $password, $dbname);
    $conexion->set_charset("utf8mb4");

    if ($conexion->connect_error) {
        die("Conexión fallida: " . $conexion->connect_error);
    }
?>
