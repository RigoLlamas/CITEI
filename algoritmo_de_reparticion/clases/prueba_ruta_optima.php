<?php
// Incluir los archivos con las clases.
include_once 'Ordenamiento.php';
include_once 'Repartidor.php';
include_once 'Pedido.php';

// Simular la conexión a la base de datos (puedes omitir esto si no necesitas una base de datos real para las pruebas)
$conexion = null; // Coloca aquí la conexión real si necesitas guardar resultados en la base de datos

// Crear una instancia de la clase Ordenamiento
$ordenamiento = new Ordenamiento($conexion);

// Simular un conjunto de nodos (direcciones de pedidos con coordenadas GPS)
$pedidos = [
    new Pedido('pedido1', 'Catedral de Guadalajara, Guadalajara', 'Guadalajara', 'Pendiente', 0.5, 0.5, 0.5),
    new Pedido('pedido2', 'Museo Cabañas, Guadalajara', 'Guadalajara', 'Pendiente', 0.6, 0.4, 0.5),
    new Pedido('pedido3', 'Parque Revolución, Guadalajara', 'Guadalajara', 'Pendiente', 0.7, 0.6, 0.6),
    new Pedido('pedido4', 'Zapopan Centro, Zapopan', 'Zapopan', 'Pendiente', 1.0, 0.8, 0.6),
    new Pedido('pedido5', 'Tequila, Jalisco', 'Tequila', 'Pendiente', 1.2, 1.0, 0.8),
    new Pedido('pedido6', 'San Juan de los Lagos, Jalisco', 'San Juan de los Lagos', 'Pendiente', 1.5, 1.2, 0.9),
    new Pedido('pedido7', 'Lago de Chapala, Chapala', 'Chapala', 'Pendiente', 1.3, 1.0, 0.7),
    new Pedido('pedido8', 'Ajijic, Jalisco', 'Ajijic', 'Pendiente', 1.1, 0.9, 0.6),
    new Pedido('pedido9', 'Ciudad Guzmán, Jalisco', 'Ciudad Guzmán', 'Pendiente', 1.8, 1.4, 1.0),
];

// Definir los repartidores con sus dimensiones
$repartidores = [
    new Repartidor('Matricula 1', 'Nomina 1', 5, 4, 5), 
    new Repartidor('Matricula 2', 'Nomina 2', 2, 1.5, 3)  
];

// Simular la ubicación de la sede
$sede = [
    'latitud' => 20.676722, 
    'longitud' => -103.347447
];

// Mostrar los pedidos antes de ordenarlos
echo "Pedidos antes de ordenar:<br>";
foreach ($pedidos as $pedido) {
    echo "Pedido {$pedido->pedido} -> Latitud: {$pedido->latitud}, Longitud: {$pedido->longitud}, Volumen: {$pedido->volumen_total}<br>";
}

echo "<br>";

// Asignar los nodos a los repartidores según la proximidad
$nodosAsignados = $ordenamiento->asignarNodosARepartidores($pedidos, $repartidores, $sede);

// Mostrar los nodos asignados a cada repartidor
echo "<br>Nodos asignados a cada repartidor:<br>";
foreach ($nodosAsignados as $nominaRepartidor => $nodosRepartidor) {
    echo "Repartidor $nominaRepartidor:<br>";
    foreach ($nodosRepartidor as $nodo) {
        echo "Pedido {$nodo->pedido} -> Latitud: {$nodo->latitud}, Longitud: {$nodo->longitud}<br>";
    }
}

// Generar y mostrar rutas óptimas para cada repartidor
foreach ($nodosAsignados as $nominaRepartidor => $nodosRepartidor) {
    echo "<br>Ruta óptima para repartidor $nominaRepartidor:<br>";
    
    // Crear un array temporal solo con los nodos asignados a este repartidor
    $nodosParaRepartidor = [];
    foreach ($nodosRepartidor as $nodo) {
        $nodosParaRepartidor[$nodo->pedido] = [
            'latitud' => $nodo->latitud,
            'longitud' => $nodo->longitud
        ];
    }

    // Obtener la ruta óptima solo para los nodos asignados a este repartidor
    $inicio = array_key_first($nodosParaRepartidor);
    if ($inicio) {
        $rutaOptima = $ordenamiento->generarRutaOptimaVecinoMasCercano($nodosParaRepartidor, $inicio);

        // Generar enlace de Google Maps para visualizar la ruta de este repartidor
        $baseURL = "https://www.google.com/maps/dir/" . $sede['latitud'] . "," . $sede['longitud'] . "/"; 

        foreach ($rutaOptima as $nodoKey) {
            $baseURL .= $nodosParaRepartidor[$nodoKey]['latitud'] . "," . $nodosParaRepartidor[$nodoKey]['longitud'] . "/";
        }
        echo $baseURL . "<br>";
    } else {
        echo "No hay nodos asignados para el repartidor $nominaRepartidor.<br>";
    }
}
?>
