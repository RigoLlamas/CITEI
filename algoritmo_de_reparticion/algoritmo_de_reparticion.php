
    <?php

    // Incluir los archivos de conexión y clases
    require_once 'conexion_algoritmo.php';
    require_once 'clases/Ordenamiento.php';

    try {
        // Instanciar la clase Ordenamiento con la conexión existente
        $ordenamiento = new Ordenamiento($conexion);

        

        // Obtener los pedidos desde la base de datos
        $pedidos = $ordenamiento->obtenerPedidosDesdeBD();

        // Crear repartidores disponibles usando Ordenamiento
        $repartidores = $ordenamiento->crearRepartidoresDisponibles();

        // Verificar si hay pedidos y repartidores; si no, detener el flujo
        if (empty($pedidos) || empty($repartidores)) {
            // echo "<h3>No hay pedidos o repartidores disponibles. Proceso detenido.</h3>";
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


        if ($horaActual > $horaLimite) {
            // echo date_default_timezone_get();
            // echo "La hora actual ({$horaActual->format('H:i:s')}) supera la hora límite de asignación (18:00). No se asignarán más pedidos.<br>";
        } else {
            // Asignar los pedidos a los repartidores y registrar los envíos en la base de datos
            $nodosAsignados = $ordenamiento->asignarNodosARepartidores($pedidos, $repartidores, $sede);
        }

        // Verificar y mostrar los resultados
        // echo "<h2>Resultados de la Asignación de Pedidos</h2>";
        foreach ($nodosAsignados as $nominaRepartidor => $pedidosAsignados) {
            // echo "<div class='repartidor'>";
            // echo "<h3>Repartidor: {$nominaRepartidor}</h3>";
            if (!empty($pedidosAsignados)) {
                // echo "<ul>";
                foreach ($pedidosAsignados as $pedido) {
                    // echo "<li>Pedido: {$pedido->getPedido()}, Coordenadas: ({$pedido->getLatitud()}, {$pedido->getLongitud()})</li>";
                }
                // echo "</ul>";
            } else {
                // echo "<p>No hay pedidos asignados.</p>";
            }
            // echo "</div>";
        }

        // Generar y mostrar rutas óptimas para cada repartidor asignado
        foreach ($nodosAsignados as $nominaRepartidor => $pedidosAsignados) {
            // echo "<div class='ruta'>";
            // echo "<h4>Ruta óptima para Repartidor {$nominaRepartidor}:</h4>";

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

            // Construir la secuencia de direcciones para Google Maps
            $rutaGoogleMaps = [$inicio];
            foreach ($rutaOptima as $destino => $ruta) {
                if (!empty($ruta)) {
                    foreach ($ruta as $nodo) {
                        if ($nodo !== 'Sede') {
                            $rutaGoogleMaps[] = $nodo;
                        }
                    }
                }
                $rutaGoogleMaps[] = $destino;
            }

            // Eliminar duplicados y mantener el orden
            $rutaGoogleMaps = array_unique($rutaGoogleMaps, SORT_REGULAR);

            // Construir el enlace de Google Maps
            $baseURL = "https://www.google.com/maps/dir/";
            $direcciones = [];
            foreach ($rutaGoogleMaps as $nodo) {
                if ($nodo === 'Sede') {
                    $lat = $sede['latitud'];
                    $lng = $sede['longitud'];
                } else {
                    $lat = $coordenadasPedidos[$nodo]['latitud'];
                    $lng = $coordenadasPedidos[$nodo]['longitud'];
                }
                $direcciones[] = "{$lat},{$lng}";
            }
            $baseURL .= implode("/", $direcciones);
            // echo "<a href='{$baseURL}' target='_blank'>Ver ruta en Google Maps</a><br><br>";

            // echo "</div>";
        }
    } catch (Exception $e) {
        // echo "<h3>Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
    }

    
    ?>
