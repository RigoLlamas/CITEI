<?php
    // Datos de la base de datos local
    $servername = "citei.c34sm80cijk7.us-east-2.rds.amazonaws.com";
    $username = "adminCITEI";
    $password = "XjqcRn6EfvUuFIwu";
    $dbname = "citei";

    // Crear conexión
    $conexion = new mysqli($servername, $username, $password, $dbname);
    $conexion->set_charset("utf8mb4");


    // Verificar conexión
    if ($conexion->connect_error) {
        die("Conexión fallida: " . $conexion->connect_error);
    }

?>