<?php

// Detalles de conexión a la base de datos
$servername = 'citei.c34sm80cijk7.us-east-2.rds.amazonaws.com';
$username = 'adminCITEI';
$password = 'XjqcRn6EfvUuFIwu';
$dbname = 'citei';

// Crear conexión
$conexion = new mysqli($servername, $username, $password, $dbname);
$conexion->set_charset("utf8mb4");

// Verificar conexión
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
} else {
    echo "Conexión exitosa a la base de datos.<br>";
}

// Realizar una consulta SELECT
$sql = "SELECT * FROM repartidor"; // Cambia "NombreTabla" por el nombre de tu tabla
$resultado = $conexion->query($sql);

// Verificar si se obtuvieron resultados
if ($resultado->num_rows > 0) {
    // Iterar y mostrar cada fila de los resultados
    while($fila = $resultado->fetch_assoc()) {
        echo "Nomina: " . $fila["Nomina"] . " - Nombre: " . $fila["Nombre"] . "<br>"; // Ajusta los campos según tu tabla
    }
} else {
    echo "No se encontraron resultados.";
}

// Cerrar conexión
$conexion->close();

?>
