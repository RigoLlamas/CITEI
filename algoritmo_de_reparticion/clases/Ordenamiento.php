<?php

include 'Repartidor.php';

class Ordenamiento {
    private $apiKey;
    private $conexion;

    public function __construct($conexion) {
        $config = include '../config.php';
        $this->apiKey = $config['api_keys']['google_maps_api_key'];
        $this->conexion = $conexion;

        if ($this->conexion->connect_error) {
            die("Error en la conexión: " . $conexion->connect_error);
        } else {
            echo "Conexión exitosa.<br>";
        }
    }

    // Función para calcular el tiempo de viaje entre dos puntos usando Google Maps API
    public function calcularTiempoViaje($origenLat, $origenLng, $destinoLat, $destinoLng) {
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins={$origenLat},{$origenLng}&destinations={$destinoLat},{$destinoLng}&key={$this->apiKey}";
        
        try {
            $response = file_get_contents($url);
            if ($response === false) throw new Exception("Error al obtener la respuesta de la API.");
            
            $data = json_decode($response, true);
            if ($data['status'] === 'OK') {
                return $data['rows'][0]['elements'][0]['duration']['value'];
            }
        } catch (Exception $e) {
            echo "Error al calcular el tiempo de viaje: " . $e->getMessage() . "<br>";
        }

        return null;  // Retorna null si falla
    }

    // Método de Haversine para calcular la distancia entre dos coordenadas
    public function calcularDistanciaHaversine($lat1, $lon1, $lat2, $lon2) {
        $radioTierra = 6378; // Kilómetros
        $difLat = deg2rad($lat2 - $lat1);
        $difLon = deg2rad($lon2 - $lon1);
        $a = sin($difLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($difLon / 2) ** 2;
        return $radioTierra * 2 * asin(sqrt($a));
    }

    // Generar ruta óptima utilizando el algoritmo de vecino más cercano
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

    // Función para asignar un pedido al repartidor y registrar en la base de datos
    private function registrarEnvio($pedido, $repartidor, $vehiculo) {
        $sql = "INSERT INTO envios (OrdenR, Cantidad, Vehiculo, Producto, Repartidor, NumVenta) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);   
        if (!$stmt) {
            echo "Error en la preparación de la consulta: " . $this->conexion->error . "<br>";
            return false;
        }

        $ordenRuta = 1; // Esto podría tener lógica personalizada
        $stmt->bind_param("iisiii", $ordenRuta, $pedido->cantidad, $vehiculo, $pedido->producto, $repartidor->nomina, $pedido->numVenta);

        if ($stmt->execute()) {
            echo "Registro en la base de datos del pedido {$pedido->pedido} completado.<br>";
            return $this->actualizarEstadoPedido($pedido->numVenta, 'En camino');
        } else {
            echo "Error al insertar el pedido {$pedido->pedido} en la tabla de envíos: " . $stmt->error . "<br>";
            return false;
        }
    }

    // Método privado para actualizar el estado del pedido
    private function actualizarEstadoPedido($numVenta, $estado) {
        $sql_actualizar = "UPDATE pedidos SET Estado = ? WHERE NumVenta = ?";
        $stmt = $this->conexion->prepare($sql_actualizar);
        
        if (!$stmt) {
            echo "Error en la preparación de la actualización del estado: " . $this->conexion->error . "<br>";
            return false;
        }
        
        $stmt->bind_param("si", $estado, $numVenta);
        $resultado = $stmt->execute();
        $stmt->close();
        
        return $resultado;
    }

    // Método para verificar y crear un repartidor con vehículo asignado
    public function crearRepartidoresDisponibles() {
        $repartidores = [];
    
        // Obtener todos los repartidores y vehículos disponibles
        $sqlRepartidores = "SELECT Nomina, Nombre, Apellidos, Estado FROM repartidor WHERE Estado = 'Disponible'";
        $resultadoRepartidores = $this->conexion->query($sqlRepartidores);
    
        $sqlVehiculos = "SELECT Placa, Largo, Alto, Ancho, Modelo, Estado FROM vehiculo WHERE Estado = 'En circulación'";
        $resultadoVehiculos = $this->conexion->query($sqlVehiculos);
    
        // Verificar que haya repartidores y vehículos disponibles
        if ($resultadoRepartidores->num_rows == 0) {
            echo "No hay repartidores disponibles para asignar.<br>";
            return $repartidores;
        }
    
        if ($resultadoVehiculos->num_rows == 0) {
            echo "No hay vehículos en circulación disponibles para asignar.<br>";
            return $repartidores;
        }
    
        // Crear repartidores con vehículos asignados mientras haya ambos recursos disponibles
        while ($repartidorData = $resultadoRepartidores->fetch_assoc() && $vehiculoData = $resultadoVehiculos->fetch_assoc()) {
            $repartidor = new Repartidor(
                $vehiculoData['Placa'],
                $repartidorData['Nomina'],
                $vehiculoData['Largo'],
                $vehiculoData['Alto'],
                $vehiculoData['Ancho']
            );
    
            // Marcar el repartidor como ocupado en la base de datos
            $sqlActualizarRepartidor = "UPDATE repartidor SET Estado = 'Ocupado' WHERE Nomina = '{$repartidorData['Nomina']}'";
            $this->conexion->query($sqlActualizarRepartidor);
    
            // Marcar el vehículo como asignado en la base de datos
            $sqlActualizarVehiculo = "UPDATE vehiculo SET Estado = 'Asignado' WHERE Placa = '{$vehiculoData['Placa']}'";
            $this->conexion->query($sqlActualizarVehiculo);
    
            // Añadir el repartidor al arreglo de repartidores disponibles
            $repartidores[] = $repartidor;
        }
    
        return $repartidores;
    }
    

public function obtenerPedidosDesdeBD() {
    $pedidos = [];
    $sqlPedidos = "SELECT NumVenta, Estado, FK_Usuario FROM pedidos WHERE Estado = 'En almacen'";
    $resultadoPedidos = $this->conexion->query($sqlPedidos);

    if ($resultadoPedidos->num_rows > 0) {
        while ($filaPedido = $resultadoPedidos->fetch_assoc()) {
            $pedidos[] = [
                'NumVenta' => $filaPedido['NumVenta'],
                'Estado' => $filaPedido['Estado'],
                'FK_Usuario' => $filaPedido['FK_Usuario'],
            ];
        }
    } else {
        echo "No hay pedidos en almacen.<br>";
        return []; // Si no hay pedidos, retorna vacío para detener el proceso
    }

    // Procesar detalles para cada pedido
    foreach ($pedidos as &$pedido) {
        $pedido = $this->agregarDetallesAPedido($pedido);
        $pedido = $this->agregarDireccionAPedido($pedido);
    }

    // Convertir los datos en objetos de la clase Pedido
    $objetosPedidos = [];
    foreach ($pedidos as $datos) {
        $objetosPedidos[] = new Pedido(
            $datos['NumVenta'],
            $datos['direccion'],
            $datos['municipio'],
            $datos['Estado'],
            $datos['largo_maximo'],
            $datos['alto_maximo'],
            $datos['ancho_maximo'],
            $datos['volumen_total'] // Pasar el volumen calculado
        );
    }

    return $objetosPedidos;
}

private function agregarDetallesAPedido($pedido) {
    $sqlDetalles = "
        SELECT pr.Largo, pr.Alto, pr.Ancho, d.Cantidad
        FROM detalles d
        JOIN producto pr ON d.Producto = pr.PK_Producto
        WHERE d.NumVenta = {$pedido['NumVenta']}
    ";
    
    $resultadoDetalles = $this->conexion->query($sqlDetalles);
    
    // Variables para calcular el volumen total y dimensiones máximas
    $volumenTotal = 0;
    $largoMaximo = 0;
    $altoMaximo = 0;
    $anchoMaximo = 0;

    if ($resultadoDetalles->num_rows > 0) {
        // Recorrer cada producto y actualizar el volumen total y las dimensiones máximas
        while ($filaDetalles = $resultadoDetalles->fetch_assoc()) {
            $volumenProducto = $filaDetalles['Largo'] * $filaDetalles['Alto'] * $filaDetalles['Ancho'] * $filaDetalles['Cantidad'];
            $volumenTotal += $volumenProducto;
            $largoMaximo = max($largoMaximo, $filaDetalles['Largo']);
            $altoMaximo = max($altoMaximo, $filaDetalles['Alto']);
            $anchoMaximo = max($anchoMaximo, $filaDetalles['Ancho']);
        }
        
        // Asignar valores calculados al pedido
        $pedido['volumen_total'] = $volumenTotal;
        $pedido['largo_maximo'] = $largoMaximo;
        $pedido['alto_maximo'] = $altoMaximo;
        $pedido['ancho_maximo'] = $anchoMaximo;
    } else {
        echo "No se encontraron detalles para el pedido {$pedido['NumVenta']}.<br>";
        // Asignar valores predeterminados si no hay detalles
        $pedido['volumen_total'] = 0;
        $pedido['largo_maximo'] = 0;
        $pedido['alto_maximo'] = 0;
        $pedido['ancho_maximo'] = 0;
    }

    return $pedido;
}
    
    private function agregarDireccionAPedido($pedido) {
        $sqlDireccion = "
            SELECT CONCAT(Calle, ' ', NumExterior, ', ', CP) AS direccion, FK_Municipio AS municipio
            FROM usuarios
            WHERE PK_Usuario = {$pedido['FK_Usuario']}
            LIMIT 1";
        
        $resultadoDireccion = $this->conexion->query($sqlDireccion);
    
        if ($resultadoDireccion->num_rows > 0) {
            $filaDireccion = $resultadoDireccion->fetch_assoc();
            $pedido['direccion'] = $filaDireccion['direccion'];
            $pedido['municipio'] = $filaDireccion['municipio'];
        } else {
            echo "No se encontró dirección para el usuario {$pedido['FK_Usuario']}.<br>";
            $pedido['direccion'] = "Dirección no disponible";
            $pedido['municipio'] = "Municipio no disponible";
        }
    
        return $pedido;
    }

    // Asigna pedidos a los repartidores disponibles y registra cada asignación en la base de datos
    public function asignarNodosARepartidores($pedidos, $repartidores, $sede) {
        $nodosAsignados = [];
    
        foreach ($repartidores as $repartidor) {
            $repartidor->actualizarUbicacion($sede['latitud'], $sede['longitud']);
            $repartidor->tiempo = new DateTime('09:00');
        }
    
        foreach ($pedidos as $pedido) {
            echo "<br>Procesando {$pedido->pedido} - Volumen: {$pedido->volumen_total}, Dimensiones: {$pedido->largo_maximo} x {$pedido->alto_maximo} x {$pedido->ancho_maximo}<br>";
            
            $nodoAsignado = null;
            $distanciaMinima = INF;
            $tiempoEstimadoLlegada = null;
            $tiempoRegresoSede = null;
    
            foreach ($repartidores as $repartidor) {
                if ($repartidor->puedeTransportarPedido($pedido->volumen_total, $pedido->largo_maximo, $pedido->alto_maximo, $pedido->ancho_maximo)) {
                    $distanciaARepartidor = $this->calcularDistanciaHaversine(
                        $repartidor->latitud,
                        $repartidor->longitud,
                        $pedido->latitud,
                        $pedido->longitud
                    );
                    
                    $tiempoViajeSegundos = $this->calcularTiempoViaje(
                        $repartidor->latitud,
                        $repartidor->longitud,
                        $pedido->latitud,
                        $pedido->longitud
                    );
                    $tiempoRegresoSegundos = $this->calcularTiempoViaje(
                        $pedido->latitud,
                        $pedido->longitud,
                        $sede['latitud'],
                        $sede['longitud']
                    );

                    if ($tiempoViajeSegundos === null || $tiempoRegresoSegundos === null) {
                        echo "No se pudo obtener el tiempo de viaje para {$pedido->pedido} con el repartidor {$repartidor->nomina}.<br>";
                        continue;
                    }

                    $tiempoViajeMinutos = (int)($tiempoViajeSegundos / 60);
                    $tiempoRegresoMinutos = (int)($tiempoRegresoSegundos / 60);
    
                    $horaEstimadaLlegada = clone $repartidor->tiempo;
                    $horaEstimadaLlegada->modify("+{$tiempoViajeMinutos} minutes");
                    $horaEstimadaRegreso = clone $horaEstimadaLlegada;
                    $horaEstimadaRegreso->modify("+{$tiempoRegresoMinutos} minutes");
    
                    if ($repartidor->puedeTrabajarHasta($horaEstimadaRegreso)) {
                        echo "Evaluando {$pedido->pedido} para repartidor {$repartidor->nomina} - Distancia: $distanciaARepartidor, Tiempo de ida: $tiempoViajeMinutos min, Tiempo de regreso: $tiempoRegresoMinutos min<br>";
                        
                        if ($distanciaARepartidor < $distanciaMinima) {
                            $distanciaMinima = $distanciaARepartidor;
                            $nodoAsignado = $repartidor;
                            $tiempoEstimadoLlegada = $horaEstimadaLlegada;
                            $tiempoRegresoSede = $horaEstimadaRegreso;
                        }
                    } else {
                        echo "Repartidor {$repartidor->nomina} no puede completar el pedido {$pedido->pedido} y regresar a la sede a tiempo.<br>";
                    }
                }
            }
    
            if ($nodoAsignado) {
                $vehiculo = $nodoAsignado->matricula;
                if ($this->registrarEnvio($pedido, $nodoAsignado, $vehiculo)) {
                    $nodoAsignado->actualizarVolumenOcupado($pedido->calcularVolumenPaquete());
                    $nodoAsignado->actualizarUbicacion($pedido->latitud, $pedido->longitud);
                    $nodoAsignado->tiempo = $tiempoEstimadoLlegada;
                    echo "Pedido {$pedido->pedido} asignado a repartidor {$nodoAsignado->nomina}.<br>";
                }
            } else {
                echo "Pedido {$pedido->pedido} no pudo ser asignado a ningún repartidor.<br><br>";
            }
        }
    
        return $nodosAsignados;
    }
}
?>

