<?php
/*
// Incluir el archivo con la clase Ordenamiento
include_once 'Ordenamiento.php';

// Simular la conexión a la base de datos (puedes omitir esto si no necesitas una base de datos real para las pruebas)
$conexion = null; // Coloca aquí la conexión real si necesitas guardar resultados en la base de datos

// Crear una instancia de la clase Ordenamiento
$ordenamiento = new Ordenamiento($conexion);

// Simular un conjunto de nodos (direcciones de pedidos con coordenadas GPS)
$nodos = [
    'pedido1' => ['latitud' => 20.676722, 'longitud' => -103.347447], // Catedral de Guadalajara
    'pedido2' => ['latitud' => 20.677945, 'longitud' => -103.338398], // Instituto Cultural Cabañas
    'pedido3' => ['latitud' => 20.673788, 'longitud' => -103.361481], // Parque Revolución
    'pedido4' => ['latitud' => 20.651780, 'longitud' => -103.405800], // Plaza del Sol
    'pedido5' => ['latitud' => 20.674167, 'longitud' => -103.461667], // Estadio Akron
    'pedido6' => ['latitud' => 20.723056, 'longitud' => -103.396111], // Basílica de Zapopan
    'pedido7' => ['latitud' => 20.684722, 'longitud' => -103.435833], // Parque Metropolitano
    'pedido8' => ['latitud' => 20.699167, 'longitud' => -103.384722], // Plaza Patria
    'pedido9' => ['latitud' => 20.676111, 'longitud' => -103.344444], // Mercado San Juan de Dios
    'pedido10' => ['latitud' => 20.666111, 'longitud' => -103.350000] // Parque Agua Azul
];

// Mostrar los pedidos antes de ordenarlos
echo "Pedidos antes de ordenar:<br>";
foreach ($nodos as $pedido => $coordenadas) {
    echo "$pedido -> Latitud: " . $coordenadas['latitud'] . ", Longitud: " . $coordenadas['longitud'] . "<br>";
}

// Nodo de inicio 
$inicio = 'pedido1';

// Ejecutar el algoritmo de Dijkstra para obtener la ruta óptima
$rutaOptima = $ordenamiento->generarRutaOptima($nodos, $inicio);
$rutaIdeal = $ordenamiento->generarRutaOptimaVecinoMasCercano($nodos, $inicio);

// Generar enlace para visualizar la ruta en Google Maps
$baseURL1 = "https://www.google.com/maps/dir/";
$baseURL2 = "https://www.google.com/maps/dir/";

echo "<br>Ruta óptima de entrega:<br>";
foreach ($rutaOptima as $nodo) {
    echo "$nodo -> (" . $nodos[$nodo]['latitud'] . ", " . $nodos[$nodo]['longitud'] . ")<br>";
    $baseURL1 .= $nodos[$nodo]['latitud'] . "," . $nodos[$nodo]['longitud'] . "/";
}
echo '<br>' . $baseURL1 . '<br>';

echo "<br>Ruta ideal de entrega:<br>";
foreach ($rutaIdeal as $nodo) {
    echo "$nodo -> (" . $nodos[$nodo]['latitud'] . ", " . $nodos[$nodo]['longitud'] . ")<br>";
    $baseURL2 .= $nodos[$nodo]['latitud'] . "," . $nodos[$nodo]['longitud'] . "/";
}
echo '<br>' . $baseURL2 . '<br>';
*/

// Incluir los archivos con las clases Ordenamiento y Repartidor
include_once 'Ordenamiento.php';
include_once 'Repartidor.php';

// Simular la conexión a la base de datos (puedes omitir esto si no necesitas una base de datos real para las pruebas)
$conexion = null; // Coloca aquí la conexión real si necesitas guardar resultados en la base de datos

// Crear una instancia de la clase Ordenamiento
$ordenamiento = new Ordenamiento($conexion);

// Simular un conjunto de nodos (direcciones de pedidos con coordenadas GPS)
$nodos = [
    'pedido1' => ['latitud' => 20.676722, 'longitud' => -103.347447, 'volumen' => 1, 'largo' => 0.5, 'alto' => 0.5, 'ancho' => 0.5],
    'pedido2' => ['latitud' => 20.677945, 'longitud' => -103.338398, 'volumen' => 1.2, 'largo' => 0.6, 'alto' => 0.4, 'ancho' => 0.5],
    'pedido3' => ['latitud' => 20.673788, 'longitud' => -103.361481, 'volumen' => 1.5, 'largo' => 0.7, 'alto' => 0.6, 'ancho' => 0.6],
    'pedido4' => ['latitud' => 20.679511, 'longitud' => -103.335121, 'volumen' => 1.3, 'largo' => 0.5, 'alto' => 0.5, 'ancho' => 0.4],
    'pedido5' => ['latitud' => 20.680033, 'longitud' => -103.340993, 'volumen' => 1.1, 'largo' => 0.6, 'alto' => 0.3, 'ancho' => 0.5],
    'pedido6' => ['latitud' => 20.675217, 'longitud' => -103.344555, 'volumen' => 1.4, 'largo' => 0.7, 'alto' => 0.6, 'ancho' => 0.4],
    'pedido7' => ['latitud' => 20.682050, 'longitud' => -103.330167, 'volumen' => 1.7, 'largo' => 0.8, 'alto' => 0.6, 'ancho' => 0.5],
    'pedido8' => ['latitud' => 20.678732, 'longitud' => -103.353301, 'volumen' => 1.2, 'largo' => 0.5, 'alto' => 0.4, 'ancho' => 0.4],
    'pedido9' => ['latitud' => 20.674540, 'longitud' => -103.337405, 'volumen' => 1.0, 'largo' => 0.4, 'alto' => 0.5, 'ancho' => 0.3],
    'pedido10' => ['latitud' => 20.676345, 'longitud' => -103.355702, 'volumen' => 1.6, 'largo' => 0.6, 'alto' => 0.7, 'ancho' => 0.5],
    'pedido11' => ['latitud' => 20.679803, 'longitud' => -103.332222, 'volumen' => 1.5, 'largo' => 0.7, 'alto' => 0.5, 'ancho' => 0.6],
    'pedido12' => ['latitud' => 20.680596, 'longitud' => -103.347993, 'volumen' => 1.3, 'largo' => 0.5, 'alto' => 0.6, 'ancho' => 0.5],
    'pedido13' => ['latitud' => 20.681901, 'longitud' => -103.342105, 'volumen' => 1.4, 'largo' => 0.6, 'alto' => 0.4, 'ancho' => 0.4],
    'pedido14' => ['latitud' => 20.683245, 'longitud' => -103.333910, 'volumen' => 1.7, 'largo' => 0.8, 'alto' => 0.5, 'ancho' => 0.5],
    'pedido15' => ['latitud' => 20.682350, 'longitud' => -103.346667, 'volumen' => 1.8, 'largo' => 0.7, 'alto' => 0.6, 'ancho' => 0.6]
];

// Definir los repartidores con sus dimensiones
$repartidores = [
    new Repartidor('Matricula 1', 'Nomina 1', 1.5, 1.5, 1.5), 
    new Repartidor('Matricula 2', 'Nomina 2', 1.5, 1.5, 1.5)  
];

// Simular la ubicación de la sede (si queremos descartar nodos cercanos a la sede)
$sede = [
    'latitud' => 20.676722, 
    'longitud' => -103.347447
];

// Mostrar los pedidos antes de ordenarlos
echo "Pedidos antes de ordenar:<br>";
foreach ($nodos as $pedido => $coordenadas) {
    echo "$pedido -> Latitud: " . $coordenadas['latitud'] . ", Longitud: " . $coordenadas['longitud'] . "<br>";
}

echo "<br>";
// Asignar los nodos a los repartidores según la proximidad
$nodosAsignados = $ordenamiento->asignarNodosARepartidores($nodos, $repartidores, $sede);

// Mostrar los nodos asignados a cada repartidor
echo "<br>Nodos asignados a cada repartidor:<br>";
foreach ($nodosAsignados as $nominaRepartidor => $nodosRepartidor) {
    echo "Repartidor $nominaRepartidor:<br>";
    foreach ($nodosRepartidor as $nodo) {
        echo "$nodo -> Latitud: " . $nodos[$nodo]['latitud'] . ", Longitud: " . $nodos[$nodo]['longitud'] . "<br>";
    }
}

// Generar y mostrar rutas óptimas para cada repartidor
foreach ($nodosAsignados as $nominaRepartidor => $nodosRepartidor) {
    echo "<br>Ruta óptima para repartidor $nominaRepartidor:<br>";
    
    // Crear un array temporal solo con los nodos asignados a este repartidor
    $nodosParaRepartidor = [];
    foreach ($nodosRepartidor as $nodo) {
        $nodosParaRepartidor[$nodo] = $nodos[$nodo];
    }

    // Obtener la ruta óptima solo para los nodos asignados a este repartidor
    $rutaOptima = $ordenamiento->generarRutaOptimaVecinoMasCercano($nodosParaRepartidor, array_keys($nodosParaRepartidor)[0]);

    // Generar enlace de Google Maps para visualizar la ruta de este repartidor
    $baseURL = "https://www.google.com/maps/dir/";
    foreach ($rutaOptima as $nodo) {
        $baseURL .= $nodosParaRepartidor[$nodo]['latitud'] . "," . $nodosParaRepartidor[$nodo]['longitud'] . "/";
    }
    echo $baseURL . "<br>";
}
?>
