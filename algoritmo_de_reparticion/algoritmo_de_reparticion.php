<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Algortimo de reparticion</title>
</head>
<body>
    
</body>
</html>

<?php

// Incluir los archivos de conexión y funciones
include_once '../php/conexion.php';
include_once 'clases/Pedido.php';
include_once 'clases/Ordenamiento.php';
include_once 'clases/Repartidor.php';

// Instanciar la clase Ordenamiento con la conexión existente
$ordenamiento = new Ordenamiento($conexion);

// Obtener los pedidos desde la base de datos
$pedidos = $ordenamiento->obtenerPedidosDesdeBD();

// Crear repartidores con vehículos asignados usando Ordenamiento
$repartidores = $ordenamiento->crearRepartidoresDisponibles();

echo "<pre>";
print_r($pedidos);
echo "</pre>";

// Verificar si hay pedidos y repartidores; si no, detener el flujo
if (empty($pedidos) || empty($repartidores)) {
    echo "<h3>No hay pedidos o repartidores disponibles. Proceso detenido.</h3>";
    $conexion->close();
    exit();
}




// Definir la ubicación de la sede
$sede = [
    'latitud' => 20.676722,
    'longitud' => -103.347447
];

// Asignar los pedidos a los repartidores y registrar los envíos en la base de datos
$nodosAsignados = $ordenamiento->asignarNodosARepartidores($pedidos, $repartidores, $sede);

// Verificar y mostrar los resultados
echo "<h3>Resultados de la Asignación de Pedidos</h3>";
foreach ($nodosAsignados as $nominaRepartidor => $pedidosAsignados) {
    echo "<h4>Repartidor: {$nominaRepartidor}</h4>";
    foreach ($pedidosAsignados as $pedido) {
        echo "Pedido: {$pedido->pedido}, Coordenadas: ({$pedido->latitud}, {$pedido->longitud})<br>";
    }
}

// Generar y mostrar rutas óptimas para cada repartidor asignado
foreach ($nodosAsignados as $nominaRepartidor => $pedidosAsignados) {
    echo "<h4>Ruta óptima para Repartidor {$nominaRepartidor}:</h4>";
    
    // Crear un array con las coordenadas de cada pedido para el repartidor actual
    $coordenadasPedidos = [];
    foreach ($pedidosAsignados as $pedido) {
        $coordenadasPedidos[$pedido->pedido] = [
            'latitud' => $pedido->latitud,
            'longitud' => $pedido->longitud
        ];
    }

    // Generar la ruta óptima usando el método vecino más cercano
    $inicio = array_key_first($coordenadasPedidos);
    if ($inicio) {
        $rutaOptima = $ordenamiento->generarRutaOptimaVecinoMasCercano($coordenadasPedidos, $inicio);

        // Mostrar el enlace de Google Maps para visualizar la ruta
        $baseURL = "https://www.google.com/maps/dir/";
        foreach ($rutaOptima as $pedidoId) {
            $baseURL .= $coordenadasPedidos[$pedidoId]['latitud'] . "," . $coordenadasPedidos[$pedidoId]['longitud'] . "/";
        }
        echo "<a href='{$baseURL}' target='_blank'>Ver ruta en Google Maps</a><br><br>";
    } else {
        echo "No hay pedidos asignados para el repartidor {$nominaRepartidor}.<br>";
    }
}

// Cerrar la conexión a la base de datos
$conexion->close();

?>
