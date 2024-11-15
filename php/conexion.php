<?php

    $config = include $_SERVER['DOCUMENT_ROOT'] . '/CITEI/config.php';

    // Obtener detalles de conexión a la base de datos desde config.php
    $servername = $config['database']['host'];
    $username = $config['database']['user'];
    $password = $config['database']['password'];
    $dbname = $config['database']['dbname'];

    // Crear conexión
    $conexion = new mysqli($servername, $username, $password, $dbname);
    $conexion->set_charset("utf8mb4");


    // Verificar conexión
    if ($conexion->connect_error) {
        die("Conexión fallida: " . $conexion->connect_error);
    }

?>