<?php

// Incluir los archivos de conexión y clases
require_once 'conexion_algoritmo.php';
require_once 'clases/Ordenamiento.php';

try {
    // Instanciar la clase Ordenamiento con la conexión existente
    $ordenamiento = new Ordenamiento($conexion);

    // Obtener los pedidos desde la base de datos
    $pedidos = $ordenamiento->obtenerPedidosDesdeBD();
    echo "Pedidos obtenidos\n";
    // Crear repartidores disponibles usando Ordenamiento
    $repartidores = $ordenamiento->crearRepartidoresDisponibles();
    echo "Repartidores obtenidos\n";
    // Verificar si hay pedidos y repartidores; si no, detener el flujo
    if (empty($pedidos) || empty($repartidores)) {
        if (empty($pedidos))
            echo "Pedidos no obtenidos\n";
        if (empty($repartidores))
            echo "Repartidor no obtenidos\n";
        $conexion->close();
        exit();
    }

    // Definir la ubicación de la sede
    $sede = [
        'latitud' => 20.676722,
        'longitud' => -103.347447
    ];

    $horaLimite = new DateTime('18:00:00');
    $horaActual = new DateTime();
    echo "Hora actual: " . $horaActual->format('H:i:s') . "\n";

    if ($horaActual < $horaLimite) {
        $nodosAsignados = $ordenamiento->asignarNodosARepartidores($pedidos, $repartidores, $sede);
        echo "Se asignaron nodos a los repartidores\n";
    }

    // Generar y mostrar rutas óptimas para cada repartidor asignado
    foreach ($nodosAsignados as $nominaRepartidor => $pedidosAsignados) {
        // Crear un array con las coordenadas de cada pedido para el repartidor actual
        $coordenadasPedidos = [];
        foreach ($pedidosAsignados as $pedido) {
            $coordenadasPedidos[$pedido->getPedido()] = [
                'latitud' => $pedido->getLatitud(),
                'longitud' => $pedido->getLongitud()
            ];
        }

        // Añadir la sede como punto de inicio y fin
        $nodos = $coordenadasPedidos;
        $nodos['Sede'] = [
            'latitud' => $sede['latitud'],
            'longitud' => $sede['longitud']
        ];

        // Generar la ruta óptima usando el algoritmo de Dijkstra
        $inicio = 'Sede';
        $rutaOptima = $ordenamiento->generarRutaOptimaDijkstra($nodos, $inicio);
    }
} catch (Exception $e) {
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Algoritmo</title>
</head>

<body>

</body>

</html>