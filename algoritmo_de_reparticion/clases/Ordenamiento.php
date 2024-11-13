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

    // Generar ruta óptima utilizando el algoritmo de Dikstra
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
    
        $ordenRuta = 1;
        $cantidad = $pedido->getCantidad();
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
    
        if (!$resultadoRepartidores) {
            echo "Error en la consulta de repartidores: " . $this->conexion->error . "<br>";
            return $repartidores;
        }
        
        $sqlVehiculos = "SELECT Placa, Largo, Alto, Ancho, Modelo, Estado FROM vehiculo WHERE Estado = 'En circulación'";
        $resultadoVehiculos = $this->conexion->query($sqlVehiculos);
    
        if (!$resultadoVehiculos) {
            echo "Error en la consulta de vehículos: " . $this->conexion->error . "<br>";
            return $repartidores;
        }
    
        // Verificar que haya repartidores y vehículos disponibles
        if ($resultadoRepartidores->num_rows == 0) {
            echo "No hay repartidores disponibles para asignar.<br>";
            return $repartidores;
        }
    
        if ($resultadoVehiculos->num_rows == 0) {
            echo "No hay vehículos en circulación disponibles para asignar.<br>";
            return $repartidores;
        }
    
        // Determinar si hay más de un repartidor
        $esForaneoAsignado = false;
    
        // Crear repartidores con vehículos asignados mientras haya ambos recursos disponibles
        while (($repartidorData = $resultadoRepartidores->fetch_assoc()) && ($vehiculoData = $resultadoVehiculos->fetch_assoc())) {
            // Si aún no se ha asignado un repartidor foráneo y hay más de uno, hacer al primer candidato foráneo
            $esForaneo = !$esForaneoAsignado && $resultadoRepartidores->num_rows > 1;
            
            $repartidor = new Repartidor(
                $vehiculoData['Placa'],
                $repartidorData['Nomina'],
                $vehiculoData['Largo'],
                $vehiculoData['Alto'],
                $vehiculoData['Ancho'],
                '18:30',
                $esForaneo
            );
    
            if ($esForaneo) {
                $esForaneoAsignado = true; // Asegurar que solo un repartidor sea foráneo
            }
    
            // Marcar el repartidor como ocupado en la base de datos
            $sqlActualizarRepartidor = "UPDATE repartidor SET Estado = 'Ocupado' WHERE Nomina = '{$repartidorData['Nomina']}'";
            if (!$this->conexion->query($sqlActualizarRepartidor)) {
                echo "Error al actualizar el estado del repartidor {$repartidorData['Nomina']}: " . $this->conexion->error . "<br>";
                continue;
            }
    
            // Marcar el vehículo como asignado en la base de datos
            $sqlActualizarVehiculo = "UPDATE vehiculo SET Estado = 'Asignado' WHERE Placa = '{$vehiculoData['Placa']}'";
            if (!$this->conexion->query($sqlActualizarVehiculo)) {
                echo "Error al actualizar el estado del vehículo {$vehiculoData['Placa']}: " . $this->conexion->error . "<br>";
                continue;
            }
    
            // Añadir el repartidor al arreglo de repartidores disponibles
            $repartidores[] = $repartidor;
        }
    
        return $repartidores;
    }
    
    
    

public function obtenerPedidosDesdeBD() {
    $pedidos = [];
    $sqlPedidos = "
        SELECT NumVenta, Estado, FK_Usuario, Fecha 
        FROM pedidos 
        WHERE Estado IN ('Entrega parcial', 'En almacen') 
        ORDER BY 
            CASE 
                WHEN Estado = 'Entrega parcial' THEN 1 
                WHEN Estado = 'En almacen' THEN 2 
            END, 
            Fecha ASC
    ";
    $resultadoPedidos = $this->conexion->query($sqlPedidos);

    if ($resultadoPedidos->num_rows > 0) {
        while ($filaPedido = $resultadoPedidos->fetch_assoc()) {
            $pedidos[] = [
                'NumVenta' => $filaPedido['NumVenta'],
                'Estado' => $filaPedido['Estado'],
                'FK_Usuario' => $filaPedido['FK_Usuario'],
                'Fecha' => $filaPedido['Fecha'],
            ];
        }
    } else {
        echo "No hay pedidos en estado de 'Entrega parcial' o 'En almacen'.<br>";
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
            $datos['volumen_total'],
            $datos['Fecha'],
            $datos['cantidad']
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
    $cantidadTotal = 0;

    if ($resultadoDetalles->num_rows > 0) {
        // Recorrer cada producto y actualizar el volumen total y las dimensiones máximas
        while ($filaDetalles = $resultadoDetalles->fetch_assoc()) {
            $volumenProducto = $filaDetalles['Largo'] * $filaDetalles['Alto'] * $filaDetalles['Ancho'] * $filaDetalles['Cantidad'];
            $volumenTotal += $volumenProducto;
            $largoMaximo = max($largoMaximo, $filaDetalles['Largo']);
            $altoMaximo = max($altoMaximo, $filaDetalles['Alto']);
            $anchoMaximo = max($anchoMaximo, $filaDetalles['Ancho']);
            $cantidadTotal += $filaDetalles['Cantidad'];
        }
        
        // Asignar valores calculados al pedido
        $pedido['volumen_total'] = $volumenTotal;
        $pedido['largo_maximo'] = $largoMaximo;
        $pedido['alto_maximo'] = $altoMaximo;
        $pedido['ancho_maximo'] = $anchoMaximo;
        $pedido['cantidad'] = $cantidadTotal;
    } else {
        echo "No se encontraron detalles para el pedido {$pedido['NumVenta']}.<br>";
        // Asignar valores predeterminados si no hay detalles
        $pedido['volumen_total'] = 0;
        $pedido['largo_maximo'] = 0;
        $pedido['alto_maximo'] = 0;
        $pedido['ancho_maximo'] = 0;
        $pedido['cantidad'] = 0;
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
        $limitePedidos = $this->limitePedido();
    
        // Inicializar ubicación y tiempo de los repartidores sin cambiar su estado a "Ocupado"
        foreach ($repartidores as $repartidor) {
            $repartidor->actualizarUbicacion($sede['latitud'], $sede['longitud']);
            $repartidor->tiempo = new DateTime('09:00');
            $repartidor->pedidosAsignados = 0; // Contador de pedidos asignados
        }
    
        foreach ($pedidos as $pedido) {
            echo "<br>Procesando {$pedido->pedido} - Volumen: {$pedido->volumen_total}, Dimensiones: {$pedido->largo_maximo} x {$pedido->alto_maximo} x {$pedido->ancho_maximo}<br>";
    
            $nodoAsignado = null;
            $distanciaMinima = INF;
            $tiempoEstimadoLlegada = null;
            $tiempoRegresoSede = null;
    
            // Verificar si el pedido pertenece a un municipio foráneo
            $esForaneo = ($pedido->municipio == 9);
    
            foreach ($repartidores as $repartidor) {
                // Verificar el límite de pedidos para cada repartidor
                if ($repartidor->pedidosAsignados >= $limitePedidos) {
                    echo "El repartidor {$repartidor->nomina} ha alcanzado su límite de $limitePedidos pedidos.<br>";
                    continue;
                }
    
                // Si el pedido es foráneo, solo intentar asignarlo a un repartidor foráneo
                if ($esForaneo && !$repartidor->es_foraneo) {
                    continue;
                }
    
                // Continuar con la lógica de asignación si el repartidor cumple los criterios
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
                    $nodoAsignado->actualizarVolumenOcupado($pedido->volumen_total);
                    $nodoAsignado->actualizarUbicacion($pedido->latitud, $pedido->longitud);
                    $nodoAsignado->tiempo = $tiempoEstimadoLlegada;
                    $nodoAsignado->pedidosAsignados++; // Incrementar el contador de pedidos asignados
                    echo "Pedido {$pedido->pedido} asignado a repartidor {$nodoAsignado->nomina}.<br>";
                }
                $nodosAsignados[$nodoAsignado->nomina][] = $pedido;
            } else {
                echo "Pedido {$pedido->pedido} no pudo ser asignado a ningún repartidor.<br><br>";
            }
        }
    
        // Al final de la asignación, actualizar el estado de los repartidores a "Ocupado" en la base de datos
        foreach ($repartidores as $repartidor) {
            if ($repartidor->pedidosAsignados > 0) { // Solo actualizar si el repartidor tiene pedidos asignados
                $sqlActualizarRepartidor = "UPDATE repartidor SET Estado = 'Ocupado' WHERE Nomina = '{$repartidor->nomina}'";
                $this->conexion->query($sqlActualizarRepartidor);
            }
        }
    
        return $nodosAsignados;
    }
    

    public function limitePedido() {
        // Obtener el número total de pedidos en la base de datos con estado 'En almacen' o 'Entrega parcial'
        $sqlTotalPedidos = "SELECT COUNT(*) AS totalPedidos FROM pedidos WHERE Estado IN ('Entrega parcial', 'En almacen')";
        $resultadoTotalPedidos = $this->conexion->query($sqlTotalPedidos);
        $totalPedidos = 0;
    
        if ($resultadoTotalPedidos && $resultadoTotalPedidos->num_rows > 0) {
            $fila = $resultadoTotalPedidos->fetch_assoc();
            $totalPedidos = $fila['totalPedidos'];
        } else {
            echo "No hay pedidos en estado 'Entrega parcial' o 'En almacen'.<br>";
            return 0; // Retorna 0 si no hay pedidos
        }
    
        // Calcular los días restantes en la semana
        $diaActual = date('N'); // N = 1 (lunes) a 7 (domingo)
        $diasRestantesSemana = 7 - $diaActual;
    
        if ($diasRestantesSemana <= 0) {
            echo "Error: No quedan días restantes en la semana.<br>";
            return 0;
        }
    
        // Obtener la cantidad de repartidores disponibles
        $sqlRepartidoresDisponibles = "SELECT COUNT(*) AS totalRepartidores FROM repartidor WHERE Estado = 'Ocupado'";
        $resultadoRepartidores = $this->conexion->query($sqlRepartidoresDisponibles);
        $cantidadRepartidores = 0;
    
        if ($resultadoRepartidores && $resultadoRepartidores->num_rows > 0) {
            $fila = $resultadoRepartidores->fetch_assoc();
            $cantidadRepartidores = $fila['totalRepartidores'];
        } else {
            echo "No hay repartidores disponibles.<br>";
            return 0; // Retorna 0 si no hay repartidores disponibles
        }
    
        // Verificar que no se divida por cero
        if ($totalPedidos == 0 || $diasRestantesSemana == 0 || $cantidadRepartidores == 0) {
            echo "Error: Parámetros insuficientes para calcular el límite de pedidos (totalPedidos: $totalPedidos, diasRestantesSemana: $diasRestantesSemana, cantidadRepartidores: $cantidadRepartidores).<br>";
            return 0;
        }
    
        // Calcular el límite de pedidos que puede tomar cada repartidor por día
        $pedidosPorDia = $totalPedidos / $diasRestantesSemana;
    
        // Calcular el límite final de pedidos por repartidor
        $limitePorRepartidor = ceil($pedidosPorDia / $cantidadRepartidores);
    
        echo "Límite de pedidos por repartidor: $limitePorRepartidor<br>";
    
        return $limitePorRepartidor;
    }
     
    
}
?>

