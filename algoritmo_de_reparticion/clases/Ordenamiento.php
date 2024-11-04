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

    public function asignarNodosARepartidores($pedidos, $repartidores, $sede) {
        $nodosAsignados = []; // Almacena los pedidos asignados a cada repartidor
    
        // Inicializar la posición de cada repartidor en la sede
        foreach ($repartidores as $repartidor) {
            $repartidor->latitud = $sede['latitud'];
            $repartidor->longitud = $sede['longitud'];
        }
    
        foreach ($pedidos as $pedido) {
            echo "Procesando {$pedido->pedido} - Volumen: {$pedido->volumen_total}, Dimensiones: {$pedido->largo_maximo} x {$pedido->alto_maximo} x {$pedido->ancho_maximo}<br>";
            
            $nodoAsignado = null;
            $distanciaMinima = INF;
    
            foreach ($repartidores as $repartidor) {
                $puedeTransportar = $repartidor->puedeTransportarPedido($pedido->volumen_total, $pedido->largo_maximo, $pedido->alto_maximo, $pedido->ancho_maximo);
                
                // Verifica si el repartidor puede transportar el pedido
                if ($puedeTransportar) {
                    // Calcula la distancia desde la ubicación actual del repartidor al pedido
                    $distanciaARepartidor = $this->calcularDistanciaHaversine(
                        $repartidor->latitud,
                        $repartidor->longitud,
                        $pedido->latitud,
                        $pedido->longitud
                    );
    
                    echo "Evaluando {$pedido->pedido} para repartidor {$repartidor->nomina} - Distancia: $distanciaARepartidor<br>";
    
                    // Asigna el nodo al repartidor con menor distancia
                    if ($distanciaARepartidor < $distanciaMinima) {
                        $distanciaMinima = $distanciaARepartidor;
                        $nodoAsignado = $repartidor;
                    }
                } else {
                    // Explicación de por qué el repartidor no puede transportar el pedido
                    $volumenDisponible = $repartidor->calcularVolumenDisponible();
                    $volumenPaquete = $pedido->calcularVolumenPaquete();
                    echo "El repartidor {$repartidor->nomina} no puede transportar {$pedido->pedido} debido a:<br>";
                    
                    if ($volumenPaquete > $volumenDisponible) {
                        echo "- Insuficiente volumen disponible. Volumen requerido: {$volumenPaquete}, disponible: {$volumenDisponible}.<br>";
                    }
                    if ($pedido->largo_maximo > $repartidor->largo) {
                        echo "- El largo del pedido ({$pedido->largo_maximo}) excede el límite del repartidor ({$repartidor->largo}).<br>";
                    }
                    if ($pedido->alto_maximo > $repartidor->alto) {
                        echo "- La altura del pedido ({$pedido->alto_maximo}) excede el límite del repartidor ({$repartidor->alto}).<br>";
                    }
                    if ($pedido->ancho_maximo > $repartidor->ancho) {
                        echo "- El ancho del pedido ({$pedido->ancho_maximo}) excede el límite del repartidor ({$repartidor->ancho}).<br>";
                    }
                    echo "<br>";
                }
            }
    
            if ($nodoAsignado) {
                // Asigna el pedido al repartidor más cercano y actualiza su posición y volumen ocupado
                $nodoAsignado->actualizarVolumenOcupado($pedido->calcularVolumenPaquete());
                $nodoAsignado->latitud = $pedido->latitud;  // Actualizar posición
                $nodoAsignado->longitud = $pedido->longitud; // Actualizar posición
    
                echo "Pedido {$pedido->pedido} asignado a repartidor {$nodoAsignado->nomina}.<br><br>";
    
                // Almacena el pedido en la lista de asignaciones para este repartidor
                $nodosAsignados[$nodoAsignado->nomina][] = $pedido;
            } else {
                echo "Pedido {$pedido->pedido} no pudo ser asignado a ningún repartidor.<br><br>";
            }
        }
    
        return $nodosAsignados;
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

    // Método para verificar si hay suficientes recursos y luego crear un repartidor con vehículo asignado
    public function crearRepartidorDisponible() {
        // Verificar si hay repartidores disponibles
        $sqlRepartidorCount = "SELECT COUNT(*) AS totalRepartidores FROM repartidor WHERE Estado = 'Disponible'";
        $resultadoRepartidorCount = $this->conexion->query($sqlRepartidorCount);
        $cantidadRepartidores = $resultadoRepartidorCount->fetch_assoc()['totalRepartidores'];

        // Verificar si hay vehículos en circulación disponibles
        $sqlVehiculoCount = "SELECT COUNT(*) AS totalVehiculos FROM vehiculo WHERE Estado = 'En circulación'";
        $resultadoVehiculoCount = $this->conexion->query($sqlVehiculoCount);
        $cantidadVehiculos = $resultadoVehiculoCount->fetch_assoc()['totalVehiculos'];

        // Confirmar que haya al menos un repartidor y un vehículo disponibles
        if ($cantidadRepartidores < 1) {
            echo "No hay repartidores disponibles para asignar.<br>";
            return null;
        }
        if ($cantidadVehiculos < 1) {
            echo "No hay vehículos en circulación disponibles para asignar.<br>";
            return null;
        }

        // Si hay recursos suficientes, proceder con la asignación
        // Seleccionar el repartidor disponible de la base de datos
        $sqlRepartidor = "SELECT Nomina, Nombre, Apellidos, Estado FROM repartidor WHERE Estado = 'Disponible' LIMIT 1";
        $resultadoRepartidor = $this->conexion->query($sqlRepartidor);
        $datosRepartidor = $resultadoRepartidor->fetch_assoc();

        // Seleccionar el vehículo en circulación que mejor se ajuste a las necesidades
        $sqlVehiculo = "SELECT Placa, Largo, Alto, Ancho, Modelo, Estado FROM vehiculo WHERE Estado = 'En circulación' ORDER BY Largo * Alto * Ancho DESC LIMIT 1";
        $resultadoVehiculo = $this->conexion->query($sqlVehiculo);
        $datosVehiculo = $resultadoVehiculo->fetch_assoc();

        // Crear el objeto Repartidor con el vehículo asociado
        $repartidor = new Repartidor(
            $datosRepartidor['Nomina'],
            $datosRepartidor['Nombre'] . " " . $datosRepartidor['Apellidos'],
            $datosVehiculo['Largo'],
            $datosVehiculo['Alto'],
            $datosVehiculo['Ancho']
        );

        // Asignar propiedades adicionales del vehículo en el repartidor
        $repartidor->vehiculo = [
            'placa' => $datosVehiculo['Placa'],
            'modelo' => $datosVehiculo['Modelo']
        ];

        echo "Repartidor {$datosRepartidor['Nombre']} con Nómina {$datosRepartidor['Nomina']} asignado al vehículo con Placa {$datosVehiculo['Placa']} ({$datosVehiculo['Modelo']}).<br>";

        // Marcar el repartidor como ocupado en la base de datos
        $sqlActualizarRepartidor = "UPDATE repartidor SET Estado = 'Ocupado' WHERE Nomina = '{$datosRepartidor['Nomina']}'";
        $this->conexion->query($sqlActualizarRepartidor);

        // Retornar el objeto repartidor con el vehículo asignado
        return $repartidor;
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
