<?php

class Ordenamiento {
    private $apiKey;
    private $conexion;

    public function __construct($conexion) {
        $config = include '../../config.php';
        $this->apiKey = $config['api_keys']['google_maps_api_key'];
        $this->conexion = $conexion;
    }

    // Método de Haversine para calcular la distancia entre dos coordenadas
    public function calcularDistanciaHaversine($lat1, $lon1, $lat2, $lon2) {
        $radioTierra = 6378; // Kilómetros
        $difLat = deg2rad($lat2 - $lat1);
        $difLon = deg2rad($lon2 - $lon1);
        $a = sin($difLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($difLon / 2) ** 2;
        $c = 2 * asin(sqrt($a));
        return $radioTierra * $c;
    }

    // Algoritmo de Dijkstra para encontrar la ruta más corta entre los pedidos
    public function calcularRutaOptima($nodos, $inicio) {
        $distancia = [];
        $predecesor = [];
        $visitado = [];

        // Inicializar distancias
        foreach ($nodos as $nodo => $coordenadas) {
            $distancia[$nodo] = INF; // Asignar distancia infinita a cada nodo
            $predecesor[$nodo] = null;
            $visitado[$nodo] = false;
        }
        $distancia[$inicio] = 0;

        while (count($visitado) > 0) {
            $minDistancia = INF;
            $nodoActual = null;

            // Encontrar el nodo no visitado con la menor distancia
            foreach ($distancia as $nodo => $dist) {
                if (!$visitado[$nodo] && $dist < $minDistancia) {
                    $minDistancia = $dist;
                    $nodoActual = $nodo;
                }
            }

            if ($nodoActual === null) {
                break; // Terminar si no quedan nodos no visitados
            }

            $visitado[$nodoActual] = true;

            // Actualizar las distancias a los nodos adyacentes
            foreach ($nodos as $nodoVecino => $coordenadasVecino) {
                if (!$visitado[$nodoVecino]) {
                    $distanciaNueva = $distancia[$nodoActual] + $this->calcularDistanciaHaversine(
                        $nodos[$nodoActual]['latitud'],
                        $nodos[$nodoActual]['longitud'],
                        $coordenadasVecino['latitud'],
                        $coordenadasVecino['longitud']
                    );

                    // Actualizar si la distancia calculada es menor
                    if ($distanciaNueva < $distancia[$nodoVecino]) {
                        $distancia[$nodoVecino] = $distanciaNueva;
                        $predecesor[$nodoVecino] = $nodoActual;
                    }
                }
            }
        }

        return [
            'distancia' => $distancia,
            'predecesor' => $predecesor
        ];
    }

    public function generarRutaOptimaVecinoMasCercano($nodos, $inicio) {
        $ruta = [$inicio];
        $visitado = [$inicio => true];
        $nodoActual = $inicio;
    
        while (count($ruta) < count($nodos)) {
            $nodoMasCercano = null;
            $distanciaMasCorta = INF;
    
            foreach ($nodos as $nodo => $coordenadas) {
                if (!isset($visitado[$nodo])) {
                    $distancia = $this->calcularDistanciaHaversine(
                        $nodos[$nodoActual]['latitud'], 
                        $nodos[$nodoActual]['longitud'], 
                        $coordenadas['latitud'], 
                        $coordenadas['longitud']
                    );
    
                    if ($distancia < $distanciaMasCorta) {
                        $distanciaMasCorta = $distancia;
                        $nodoMasCercano = $nodo;
                    }
                }
            }
    
            $ruta[] = $nodoMasCercano;
            $visitado[$nodoMasCercano] = true;
            $nodoActual = $nodoMasCercano;
        }
    
        return $ruta;
    }
    

    // Método para generar la ruta más corta en orden de entrega
    public function generarRutaOptima($nodos, $inicio) {
        $resultadoDijkstra = $this->calcularRutaOptima($nodos, $inicio);
        $ruta = [];
        $visitado = []; // Para marcar los nodos ya incluidos en la ruta
    
        // Iterar sobre todos los nodos calculados para asegurarnos de incluir cada uno
        $nodoActual = $inicio;
        while ($nodoActual !== null) {
            $ruta[] = $nodoActual;
            $visitado[$nodoActual] = true; // Marcar como visitado
            $nodoActual = null;
    
            // Encontrar el siguiente nodo más cercano no visitado
            foreach ($resultadoDijkstra['distancia'] as $nodo => $distancia) {
                if (!isset($visitado[$nodo]) && ($nodoActual === null || $distancia < $resultadoDijkstra['distancia'][$nodoActual])) {
                    $nodoActual = $nodo;
                }
            }
        }
    
        return $ruta; // Devuelve todos los nodos en el orden correcto
    }

    /*public function asignarNodosARepartidores($nodos, $repartidores, $sede) {
        $nodosAsignados = [];
    
        foreach ($nodos as $nodo => $coordenadasNodo) {
            $nodoAsignado = null;
            $distanciaMinima = INF;
    
            // Valores predeterminados si no están definidos en el nodo
            $volumenPedido = $coordenadasNodo['volumen'] ?? 1.0;
            $largoPedido = $coordenadasNodo['largo'] ?? 1.0;
            $altoPedido = $coordenadasNodo['alto'] ?? 1.0;
            $anchoPedido = $coordenadasNodo['ancho'] ?? 1.0;
    
            foreach ($repartidores as $repartidor) {
                // Verificar si el repartidor puede transportar el pedido
                if ($repartidor->puedeTransportarPedido($volumenPedido, $largoPedido, $altoPedido, $anchoPedido)) {
                    // Calcular distancia del repartidor al nodo
                    $distanciaARepartidor = $this->calcularDistanciaHaversine(
                        $sede['latitud'], 
                        $sede['longitud'], 
                        $coordenadasNodo['latitud'], 
                        $coordenadasNodo['longitud']
                    );
    
                    // Asigna el nodo al repartidor más cercano con suficiente espacio disponible
                    if ($distanciaARepartidor < $distanciaMinima) {
                        $distanciaMinima = $distanciaARepartidor;
                        $nodoAsignado = $repartidor;
                    }
                }
            }
    
            // Asignar el nodo al repartidor seleccionado y actualizar el volumen ocupado
            if ($nodoAsignado) {
                $nodoAsignado->actualizarVolumenOcupado($volumenPedido); // Actualizar volumen ocupado
                $nodosAsignados[$nodoAsignado->nomina][] = $nodo; // Añadir el nodo al array de asignados
                echo "Pedido $nodo asignado a repartidor {$nodoAsignado->nomina}.<br>";
            }
        }
    
        return $nodosAsignados; // Devuelve un arreglo de repartidores con sus respectivos nodos asignados
    }*/

    public function asignarNodosARepartidores($nodos, $repartidores, $sede) {
        $nodosAsignados = [];
    
        foreach ($nodos as $nodo => $coordenadasNodo) {
            $nodoAsignado = null;
            $distanciaMinima = INF;
            $volumenPedido = $coordenadasNodo['volumen'] ?? 1.0;
            $largoPedido = $coordenadasNodo['largo'] ?? 1.0;
            $altoPedido = $coordenadasNodo['alto'] ?? 1.0;
            $anchoPedido = $coordenadasNodo['ancho'] ?? 1.0;
    
            echo "Procesando $nodo - Volumen: $volumenPedido, Dimensiones: $largoPedido x $altoPedido x $anchoPedido<br>";
    
            foreach ($repartidores as $repartidor) {
                if ($repartidor->puedeTransportarPedido($volumenPedido, $largoPedido, $altoPedido, $anchoPedido)) {
                    $distanciaARepartidor = $this->calcularDistanciaHaversine(
                        $sede['latitud'], 
                        $sede['longitud'], 
                        $coordenadasNodo['latitud'], 
                        $coordenadasNodo['longitud']
                    );
    
                    echo "Evaluando $nodo para repartidor {$repartidor->nomina} - Distancia: $distanciaARepartidor<br>";
    
                    if ($distanciaARepartidor < $distanciaMinima) {
                        $distanciaMinima = $distanciaARepartidor;
                        $nodoAsignado = $repartidor;
                    }
                } else {
                    echo "El repartidor {$repartidor->nomina} no puede transportar el pedido $nodo debido a restricciones de volumen o dimensiones.<br>";
                }
            }
    
            if ($nodoAsignado) {
                $nodoAsignado->actualizarVolumenOcupado($volumenPedido);
                echo "Pedido $nodo asignado a repartidor {$nodoAsignado->nomina}.<br><br>";
                $nodosAsignados[$nodoAsignado->nomina][] = $nodo;
            } else {
                echo "Pedido $nodo no pudo ser asignado a ningún repartidor.<br><br>";
            }
        }
    
        return $nodosAsignados;
    }
    
       

    function obtenerCoordenadas($direccion) {
        $direccionFormateada = urlencode($direccion);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$direccionFormateada}&key={$this->apiKey}";
    
        // Realizar la solicitud HTTP
        $response = file_get_contents($url);
        $data = json_decode($response, true);
    
        // Verificar que la solicitud fue exitosa
        if ($data['status'] === 'OK') {
            $coordenadas = $data['results'][0]['geometry']['location'];
            return [
                'latitud' => $coordenadas['lat'],
                'longitud' => $coordenadas['lng']
            ];
        } else {
            return null; // Manejo de errores en caso de fallo en la solicitud
        }
    }
    

    public function calcularTiempoViaje($origenLat, $origenLng, $destinoLat, $destinoLng) {
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins={$origenLat},{$origenLng}&destinations={$destinoLat},{$destinoLng}&key={$this->apiKey}";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if ($data['status'] == 'OK') {
            $duracionSegundos = $data['rows'][0]['elements'][0]['duration']['value'];
            return $duracionSegundos;
        }
        return null;
    }



    // Método para obtener los pedidos en estado de "Entrega parcial" (prioritarios)
    public function obtenerPedidosEntregaParcial() {
        $sql = "SELECT NumVenta, Fecha FROM pedidos WHERE Estado = 'Entrega parcial' ORDER BY Fecha ASC";
        return $this->conexion->query($sql);
    }

    // Método para obtener los pedidos en estado "Solicitado"
    public function obtenerPedidosSolicitados() {
        $sql = "SELECT NumVenta, Fecha FROM pedidos WHERE Estado = 'Solicitado' ORDER BY Fecha ASC";
        return $this->conexion->query($sql);
    }

    // Método para seleccionar un repartidor disponible basado en criterios específicos
    public function seleccionarRepartidor() {
        $sql = "SELECT Nomina FROM repartidor WHERE Estado = 'Disponible' LIMIT 1";
        $resultado = $this->conexion->query($sql);
        $repartidor = $resultado->fetch_assoc();
        return $repartidor ? $repartidor['Nomina'] : null;
    }

    // Método para seleccionar un vehículo en circulación
    public function seleccionarVehiculo() {
        $sql = "SELECT Placa FROM vehiculo WHERE Estado = 'En circulación' LIMIT 1";
        $resultado = $this->conexion->query($sql);
        $vehiculo = $resultado->fetch_assoc();
        return $vehiculo ? $vehiculo['Placa'] : null;
    }

    // Método para asignar un pedido a un repartidor y actualizar el estado
    public function asignarPedidoARepartidor($pedido, $repartidor, $vehiculo) {
        if (!$repartidor->puedeTransportarPedido($pedido->volumen_total, $pedido->largo_maximo, $pedido->alto_maximo, $pedido->ancho_maximo)) {
            echo "El pedido excede el volumen o las dimensiones del vehículo. No se puede asignar.";
            return false;
        }

        $sql_detalles = "SELECT Producto, Cantidad FROM detalles WHERE NumVenta = ?";
        $stmt_detalles = $this->conexion->prepare($sql_detalles);
        $stmt_detalles->bind_param("i", $pedido->pedido);
        $stmt_detalles->execute();
        $resultado_detalles = $stmt_detalles->get_result();

        while ($detalle = $resultado_detalles->fetch_assoc()) {
            $producto = $detalle['Producto'];
            $cantidad = $detalle['Cantidad'];

            $sql_envio = "INSERT INTO envios (OrdenR, Cantidad, Vehiculo, Producto, Repartidor, NumVenta) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_envio = $this->conexion->prepare($sql_envio);
            $orden_ruta = 1;
            $stmt_envio->bind_param("iisiii", $orden_ruta, $cantidad, $vehiculo, $producto, $repartidor->nomina, $pedido->pedido);
            $stmt_envio->execute();

            $this->actualizarEstadoPedido($pedido->pedido, 'En camino');
        }
        return true;
    }

    // Método privado para actualizar el estado del pedido
    private function actualizarEstadoPedido($numVenta, $estado) {
        $sql_actualizar = "UPDATE pedidos SET Estado = ? WHERE NumVenta = ?";
        $stmt = $this->conexion->prepare($sql_actualizar);
        $stmt->bind_param("si", $estado, $numVenta);
        $stmt->execute();
    }
}

?>
