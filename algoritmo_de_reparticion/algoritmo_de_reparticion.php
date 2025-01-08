<?php
require_once 'conexion_algoritmo.php';
require_once 'clases/Ordenamiento.php';

ob_start();
header('Content-Type: application/json');

try {
    // Instanciar la clase Ordenamiento con la conexión existente
    $ordenamiento = new Ordenamiento($conexion);

    // Obtener los pedidos desde la base de datos
    $pedidos = $ordenamiento->obtenerPedidosDesdeBD();
    // Crear repartidores disponibles usando Ordenamiento
    $repartidores = $ordenamiento->crearRepartidoresDisponibles();

    // Verificar si hay pedidos y repartidores; si no, detener el flujo
    if (empty($pedidos) || empty($repartidores)) {
        $mensaje = [];
        if (empty($pedidos)) {
            $mensaje[] = 'No hay pedidos disponibles.';
        }
        if (empty($repartidores)) {
            $mensaje[] = 'No hay repartidores disponibles.';
        }
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => implode(' ', $mensaje)
        ]);
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

    if ($horaActual < $horaLimite) {
        $nodosAsignados = $ordenamiento->asignarNodosARepartidores($pedidos, $repartidores, $sede);
    } else {
        ob_clean();
        echo json_encode([
            'status' => 'error',
            'message' => 'No es posible calcular en este horario.'
        ]);
        $conexion->close();
        exit();
    }

    // Generar rutas óptimas para cada repartidor
    foreach ($nodosAsignados as $nominaRepartidor => $pedidosAsignados) {
        $coordenadasPedidos = [];
        foreach ($pedidosAsignados as $pedido) {
            $coordenadasPedidos[$pedido->getPedido()] = [
                'latitud' => $pedido->getLatitud(),
                'longitud' => $pedido->getLongitud()
            ];
        }

        $nodos = $coordenadasPedidos;
        $nodos['Sede'] = [
            'latitud' => $sede['latitud'],
            'longitud' => $sede['longitud']
        ];

        // Generar la ruta óptima usando el algoritmo de Dijkstra
        $inicio = 'Sede';
        $rutaOptima = $ordenamiento->generarRutaOptimaDijkstra($nodos, $inicio);
    }

    // Responder con éxito
    ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'El algoritmo se ejecuto correctamente.'
    ]);
} catch (Exception $e) {
    // Manejar errores de manera consistente
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Hubo un error al ejecutar el algoritmo: ' . $e->getMessage()
    ]);
}
