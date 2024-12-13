<?php

include 'Repartidor.php';
include 'Vehiculo.php';
include 'Pedido.php';

class Ordenamiento
{
    private $apiKey;
    private $conexion;
    private $exitenForaneos = false;
    private $foraneo;

    public function __construct($conexion)
    {
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
    public function calcularTiempoViaje($origenLat, $origenLng, $destinoLat, $destinoLng)
    {
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins={$origenLat},{$origenLng}&destinations={$destinoLat},{$destinoLng}&key={$this->apiKey}";

        $response = @file_get_contents($url);
        if ($response === false) {
            throw new Exception("Error al obtener la respuesta de la API.");
        }

        $data = json_decode($response, true);
        if ($data['status'] !== 'OK') {
            throw new Exception("API de Google Maps respondió con estado: " . $data['status']);
        }

        return $data['rows'][0]['elements'][0]['duration']['value'];
    }


    // Método de Haversine para calcular la distancia entre dos coordenadas
    public function calcularDistanciaHaversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        if (is_null($lat1) || is_null($lon1) || is_null($lat2) || is_null($lon2)) {
            echo "Una o más coordenadas son nulas. No se puede calcular la distancia.<br>";
            return null;
        }

        $radioTierra = 6378; // Kilómetros
        $difLat = deg2rad($lat2 - $lat1);
        $difLon = deg2rad($lon2 - $lon1);
        $a = sin($difLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($difLon / 2) ** 2;
        return $radioTierra * 2 * asin(sqrt($a));
    }

    // Implementación del algoritmo de Dijkstra
    public function generarRutaOptimaDijkstra($nodos, $inicio)
    {
        // Inicializar estructuras de datos
        $distancias = [];
        $prev = [];
        $cola = new SplPriorityQueue();

        foreach ($nodos as $nodo => $coordenadas) {
            $distancias[$nodo] = INF;
            $prev[$nodo] = null;
        }
        $distancias[$inicio] = 0;
        $cola->insert($inicio, 0);

        while (!$cola->isEmpty()) {
            $u = $cola->extract();

            foreach ($nodos as $v => $coordenadas) {
                if ($u == $v) continue;
                $distancia = $this->calcularDistanciaHaversine(
                    $nodos[$u]['latitud'],
                    $nodos[$u]['longitud'],
                    $coordenadas['latitud'],
                    $coordenadas['longitud']
                );
                $alt = $distancias[$u] + $distancia;

                if ($alt < $distancias[$v]) {
                    $distancias[$v] = $alt;
                    $prev[$v] = $u;
                    $cola->insert($v, -$alt);
                }
            }
        }

        // Construir la ruta desde el inicio hasta cada nodo
        $rutas = [];
        foreach ($nodos as $nodo => $coordenadas) {
            if ($nodo == $inicio) continue;
            $ruta = [];
            $current = $nodo;
            while ($prev[$current] !== null) {
                array_unshift($ruta, $current);
                $current = $prev[$current];
            }
            $rutas[$nodo] = $ruta;
        }

        return $rutas;
    }

    // Función para asignar un pedido al repartidor y registrar en la base de datos
    private function registrarEnvio($pedido, $repartidor)
    {
        $numVenta = $pedido->getPedido();

        // Consulta para obtener el Producto asociado al pedido
        $sqlProducto = "SELECT Producto, Cantidad FROM detalles WHERE NumVenta = ?";
        $stmtProducto = $this->conexion->prepare($sqlProducto);
        if (!$stmtProducto) {
            echo "Error en la preparación de la consulta para obtener el producto: " . $this->conexion->error . "<br>";
            return false;
        }

        $stmtProducto->bind_param("i", $numVenta);
        $stmtProducto->execute();
        $resultadoProducto = $stmtProducto->get_result();

        if ($resultadoProducto->num_rows > 0) {
            $filaProducto = $resultadoProducto->fetch_assoc();
            $producto = $filaProducto['Producto'];
            $cantidad = $filaProducto['Cantidad'];
        } else {
            echo "No se encontró producto para el pedido {$numVenta}.<br>";
            $stmtProducto->close();
            return false;
        }

        // Cerrar la consulta de producto
        $stmtProducto->close();

        // Insertar en la tabla de envíos
        $sql = "INSERT INTO envios (OrdenR, Cantidad, Vehiculo, Producto, Repartidor, NumVenta) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) {
            echo "Error en la preparación de la consulta: " . $this->conexion->error . "<br>";
            return false;
        }

        // Asumiendo que "Vehiculo" es la Placa del vehículo del repartidor
        $vehiculoPlaca = $repartidor->getVehiculo() ? $repartidor->getVehiculo()->getPlaca() : null;

        $orden = $repartidor->actualizarOrden();
        $nomina = $repartidor->getNomina();

        $stmt->bind_param("iisiii", $orden, $cantidad, $vehiculoPlaca, $producto, $nomina, $numVenta);

        if ($stmt->execute()) {
            echo "Registro en la base de datos del pedido {$numVenta} completado.<br>";

            // Actualizar el estado del pedido a 'En camino'
            if ($this->actualizarEstadoPedido($numVenta, 'En camino')) {
                echo "Estado del pedido {$numVenta} actualizado a 'En camino'.<br>";
            } else {
                echo "Error al actualizar el estado del pedido {$numVenta}.<br>";
            }

            $stmt->close();
            return true;
        } else {
            echo "Error al insertar el pedido {$numVenta} en la tabla de envíos: " . $stmt->error . "<br>";
            $stmt->close();
            return false;
        }
    }

    // Método para verificar y crear un repartidor con o sin vehículo asignado
    public function crearRepartidoresDisponibles()
    {
        $repartidores = [];

        // Obtener todos los repartidores disponibles y que no están en descanso
        $sqlRepartidores = "SELECT Nomina, Nombre, Apellidos, Estado, Latitud, Longitud, Descanso, HoraBandera FROM repartidor WHERE Estado = 'Disponible' AND (Descanso = 0 OR Descanso IS NULL) ";
    
        $resultadoRepartidores = $this->conexion->query($sqlRepartidores);

        if (!$resultadoRepartidores) {
            echo "Error en la consulta de repartidores: " . $this->conexion->error . "<br>";
            return $repartidores;
        }

        // Verificar que haya repartidores disponibles
        if ($resultadoRepartidores->num_rows == 0) {
            echo "No hay repartidores disponibles para asignar.<br>";
            return $repartidores;
        }

        // Obtener vehículos disponibles
        $sqlVehiculos = "SELECT Placa, Largo, Alto, Ancho, Modelo, Estado, KilometrosRecorridos FROM vehiculo WHERE Estado = 'En circulación'";
        $resultadoVehiculos = $this->conexion->query($sqlVehiculos);

        if (!$resultadoVehiculos) {
            echo "Error en la consulta de vehículos: " . $this->conexion->error . "<br>";
            return $repartidores;
        }

        // Almacenar los vehículos disponibles en un arreglo
        $vehiculosDisponibles = [];
        while ($filaVehiculo = $resultadoVehiculos->fetch_assoc()) {
            $vehiculosDisponibles[] = new Vehiculo(
                $filaVehiculo['Placa'],
                $filaVehiculo['Largo'],
                $filaVehiculo['Alto'],
                $filaVehiculo['Ancho'],
                $filaVehiculo['Modelo'],
                $filaVehiculo['Estado'],
                $filaVehiculo['KilometrosRecorridos']
            );
        }

        // Crear repartidores mientras haya repartidores disponibles
        while ($repartidorData = $resultadoRepartidores->fetch_assoc()) {
            // Asignar un vehículo si está disponible
            $vehiculo = null;
            if (!empty($vehiculosDisponibles)) {
                $vehiculo = array_shift($vehiculosDisponibles); // Tomar el primer vehículo disponible
            }

            // Crear una instancia de Repartidor
            $repartidor = new Repartidor(
                $repartidorData['Nomina'],
                $repartidorData['Nombre'],
                $repartidorData['Apellidos'],
                $repartidorData['Latitud'],
                $repartidorData['Longitud'],
                $repartidorData['Estado'],
                $repartidorData['Descanso'],
                $repartidorData['HoraBandera'],
                $vehiculo
            );

            // Añadir el repartidor al arreglo de repartidores disponibles
            $repartidores[] = $repartidor;
        }

        return $repartidores;
    }


    public function obtenerPedidosDesdeBD()
    {
        $pedidos = [];
        $sqlPedidos = "
            SELECT p.NumVenta, p.Estado, p.FK_Usuario, p.Fecha, p.Foraneo, u.Latitud, u.Longitud
            FROM pedidos p
            JOIN usuarios u ON p.FK_Usuario = u.PK_Usuario
            WHERE p.Estado IN ('Entrega parcial', 'En almacen') 
            ORDER BY 
                CASE 
                    WHEN p.Estado = 'Entrega parcial' THEN 1 
                    WHEN p.Estado = 'En almacen' THEN 2 
                END, 
                p.Fecha ASC
        ";
        $resultadoPedidos = $this->conexion->query($sqlPedidos);

        if ($resultadoPedidos->num_rows > 0) {
            while ($filaPedido = $resultadoPedidos->fetch_assoc()) {
                $this->exitenForaneos = $filaPedido['Foraneo'] === 'Sí'? true : $this->exitenForaneos;
                $pedidos[] = [
                    'NumVenta' => $filaPedido['NumVenta'],
                    'Estado' => $filaPedido['Estado'],
                    'FK_Usuario' => $filaPedido['FK_Usuario'],
                    'Fecha' => $filaPedido['Fecha'],
                    'Foraneo' => $filaPedido['Foraneo'],
                    'Latitud' => $filaPedido['Latitud'],
                    'Longitud' => $filaPedido['Longitud']
                ];
            }
        } else {
            echo "No hay pedidos en estado de 'Entrega parcial' o 'En almacen'.<br>";
            return []; // Si no hay pedidos, retorna vacío para detener el proceso
        }

        // Procesar detalles para cada pedido
        foreach ($pedidos as &$pedido) {
            $pedido = $this->agregardetallesAPedido($pedido);
        }

        // Convertir los datos en objetos de la clase Pedido
        $objetosPedidos = [];
        foreach ($pedidos as $datos) {
            // Validar que las coordenadas no sean null
            if (is_null($datos['Latitud']) || is_null($datos['Longitud'])) {
                echo "Pedido {$datos['NumVenta']} tiene coordenadas nulas. Saltando asignación.<br>";
                continue; // O manejar de otra manera según tu lógica
            }

            $objetosPedidos[] = new Pedido(
                $datos['NumVenta'],
                '', // Dirección no es necesaria ya que usamos coordenadas
                '', // Municipio no es necesario si 'Foraneo' es suficiente, pero si lo usas, puedes mantenerlo
                $datos['Estado'],
                $datos['largo_maximo'] ?? 0,
                $datos['alto_maximo'] ?? 0,
                $datos['ancho_maximo'] ?? 0,
                $datos['volumen_total'] ?? 0,
                $datos['Fecha'],
                $datos['cantidad'] ?? 0,
                $datos['Foraneo'],
                floatval($datos['Latitud']),
                floatval($datos['Longitud'])
            );
        }

        return $objetosPedidos;
    }

    private function agregardetallesAPedido($pedido)
    {
        $sqldetalles = "
            SELECT pr.Largo, pr.Alto, pr.Ancho, d.Cantidad
            FROM detalles d
            JOIN producto pr ON d.Producto = pr.PK_Producto
            WHERE d.NumVenta = ?
        ";

        $stmtdetalles = $this->conexion->prepare($sqldetalles);
        if (!$stmtdetalles) {
            echo "Error en la preparación de la consulta de detalles para el pedido {$pedido['NumVenta']}: " . $this->conexion->error . "<br>";
            // Asignar valores predeterminados si no se pueden obtener detalles
            $pedido['volumen_total'] = 0;
            $pedido['largo_maximo'] = 0;
            $pedido['alto_maximo'] = 0;
            $pedido['ancho_maximo'] = 0;
            $pedido['cantidad'] = 0;
            return $pedido;
        }

        $stmtdetalles->bind_param("i", $pedido['NumVenta']);
        $stmtdetalles->execute();
        $resultadodetalles = $stmtdetalles->get_result();

        // Variables para calcular el volumen total y dimensiones máximas
        $volumenTotal = 0;
        $largoMaximo = 0;
        $altoMaximo = 0;
        $anchoMaximo = 0;
        $cantidadTotal = 0;

        if ($resultadodetalles->num_rows > 0) {
            // Recorrer cada producto y actualizar el volumen total y las dimensiones máximas
            while ($filadetalles = $resultadodetalles->fetch_assoc()) {
                $volumenProducto = $filadetalles['Largo'] * $filadetalles['Alto'] * $filadetalles['Ancho'] * $filadetalles['Cantidad'];
                $volumenTotal += $volumenProducto;
                $largoMaximo = max($largoMaximo, $filadetalles['Largo']);
                $altoMaximo = max($altoMaximo, $filadetalles['Alto']);
                $anchoMaximo = max($anchoMaximo, $filadetalles['Ancho']);
                $cantidadTotal += $filadetalles['Cantidad'];
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

        $stmtdetalles->close();

        return $pedido;
    }

    // Método para actualizar el estado del pedido
    private function actualizarEstadoPedido($numVenta, $estado)
    {
        $sql_actualizar = "UPDATE pedidos SET Estado = ? WHERE NumVenta = ?";
        $stmt = $this->conexion->prepare($sql_actualizar);

        if (!$stmt) {
            echo "Error en la preparación de la actualización del estado: " . $this->conexion->error . "<br>";
            return false;
        }

        $stmt->bind_param("si", $estado, $numVenta);
        $resultado = $stmt->execute();
        $stmt->close();

        return $resultado; // Retorna true si la actualización fue exitosa, false en caso contrario
    }

    // Método para establecer el estado de descanso de un repartidor
    public function establecerDescanso($nomina, $estadoDescanso)
    {
        $sql = "UPDATE repartidor SET Descanso = ? WHERE Nomina = ?";
        $stmt = $this->conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $estadoDescanso, $nomina);
            if ($stmt->execute()) {
                echo "Estado de descanso del repartidor {$nomina} actualizado a {$estadoDescanso}.<br>";
            } else {
                echo "Error al actualizar el estado de descanso del repartidor {$nomina}: " . $stmt->error . "<br>";
            }
            $stmt->close();
        } else {
            echo "Error en la preparación de la consulta para establecer descanso: " . $this->conexion->error . "<br>";
        }
    }

    // Método para asignar pedidos a repartidores
    public function asignarNodosARepartidores($pedidos, $repartidores, $sede)
    {
        $nodosAsignados = [];
        $limitePedidos = $this->limitePedido();

        // Obtener el repartidor foráneo si existen
    if ($this->exitenForaneos) {
        $repartidorForaneo = $this->obtenerRepartidorForaneo();
        
        if ($repartidorForaneo) {
            $this->foraneo = $repartidorForaneo->getNomina();
            
            // Buscar y eliminar el repartidor foráneo de la lista de repartidores locales
            foreach ($repartidores as $key => $repartidor) {
                if ($repartidor->getNomina() === $this->foraneo) {
                    unset($repartidores[$key]); // Eliminar repartidor foráneo de la lista
                    echo "Repartidor foráneo con nómina {$this->foraneo} eliminado de la lista local.<br>";
                }
            }
            $repartidores = array_values($repartidores);
        }
    }
        
        if ($repartidorForaneo) {
            $nomina = $repartidorForaneo->getNomina();
            echo "<br>Repartidor disponible para pedidos foráneos: $nomina ";
            // Inicializar ubicación y tiempo del repartidor foráneo
            $repartidorForaneo->actualizarUbicacion($sede['latitud'], $sede['longitud']);
            echo "<br>actualizarUbicacion";
            $repartidorForaneo->setTiempo(new DateTime()); // Usar la hora actual de la sede
            echo "<br>setTiempo";
            $repartidorForaneo->setPedidosAsignados(0); // Contador de pedidos asignados
            echo "<br>setPedidosAsignados";
        }

        // Inicializar ubicación y tiempo de los demás repartidores
        foreach ($repartidores as $repartidor) {
            $repartidor->actualizarUbicacion($sede['latitud'], $sede['longitud']);
            $repartidor->setTiempo(new DateTime()); // Usar la hora actual de la sede
            $repartidor->setPedidosAsignados(0); // Contador de pedidos asignados
        }

        // Procesar cada pedido
        foreach ($pedidos as $pedido) {
            echo "<br>Procesando Pedido {$pedido->getPedido()} - Volumen: {$pedido->getVolumenTotal()}, Dimensiones: {$pedido->getLargoMaximo()} x {$pedido->getAltoMaximo()} x {$pedido->getAnchoMaximo()}<br>";

            $nodoAsignado = null;
            $distanciaMinima = INF;
            $tiempoEstimadoLlegada = null;
            $tiempoRegresoSede = null;

            // Verificar si el pedido es foráneo
            $esForaneo = ($pedido->getForaneo() == 'Sí');

            if ($esForaneo) {
                if ($repartidorForaneo && $repartidorForaneo->puedeTransportarPedido(
                    $pedido->getVolumenTotal(),
                    $pedido->getLargoMaximo(),
                    $pedido->getAltoMaximo(),
                    $pedido->getAnchoMaximo()
                )) {
                    $nodoAsignado = $repartidorForaneo;
                    echo "Pedido {$pedido->getPedido()} asignado al repartidor foráneo.<br>";
                } else {
                    echo "No hay repartidor foráneo disponible para el pedido {$pedido->getPedido()}.<br>";
                    continue;
                }
            } else {
                // Lógica de asignación para pedidos dentro de la zona metropolitana
                foreach ($repartidores as $repartidor) {
                    echo "<br>";
                    // Verificar el límite de pedidos para cada repartidor
                    if ($repartidor->getPedidosAsignados() >= $limitePedidos) {
                        echo "El repartidor {$repartidor->getNomina()} ha alcanzado su límite de $limitePedidos pedidos.<br>";
                        continue;
                    }

                    // Verificar si el repartidor puede transportar el pedido
                    if ($repartidor->puedeTransportarPedido($pedido->getVolumenTotal(), $pedido->getLargoMaximo(), $pedido->getAltoMaximo(), $pedido->getAnchoMaximo())) {
                        $distanciaARepartidor = $this->calcularDistanciaHaversine(
                            $repartidor->getLatitud(),
                            $repartidor->getLongitud(),
                            $pedido->getLatitud(),
                            $pedido->getLongitud()
                        );

                        $tiempoViajeSegundos = $this->calcularTiempoViaje(
                            $repartidor->getLatitud(),
                            $repartidor->getLongitud(),
                            $pedido->getLatitud(),
                            $pedido->getLongitud()
                        );
                        $tiempoRegresoSegundos = $this->calcularTiempoViaje(
                            $pedido->getLatitud(),
                            $pedido->getLongitud(),
                            $sede['latitud'],
                            $sede['longitud']
                        );

                        if ($tiempoViajeSegundos === null || $tiempoRegresoSegundos === null) {
                            echo "No se pudo obtener el tiempo de viaje para Pedido {$pedido->getPedido()} con el repartidor {$repartidor->getNomina()}.<br>";
                            continue;
                        }

                        // Sumar 10 minutos por imprevistos
                        $tiempoViajeSegundos += 600; // 600 segundos = 10 minutos

                        // Convertir a minutos
                        $tiempoViajeMinutos = (int)($tiempoViajeSegundos / 60);
                        $tiempoRegresoMinutos = (int)($tiempoRegresoSegundos / 60);

                        // Mostrar la hora actual del repartidor
                        echo "Hora actual del repartidor {$repartidor->getNomina()}: {$repartidor->getTiempo()->format('H:i')}<br>";

                        // Calcular tiempos
                        $horaEstimadaLlegada = clone $repartidor->getTiempo();
                        $horaEstimadaLlegada->modify("+{$tiempoViajeMinutos} minutes");
                        echo "Hora estimada de llegada al pedido: {$horaEstimadaLlegada->format('H:i')}<br>";

                        $horaEstimadaRegreso = clone $horaEstimadaLlegada;
                        $horaEstimadaRegreso->modify("+{$tiempoRegresoMinutos} minutes");

                        // Verificar si el recorrido cruza la HoraBandera
                        if ($horaEstimadaLlegada >= $repartidor->getHoraBandera() && $repartidor->getDescanso() == 0) {
                            $horaEstimadaRegreso->modify('+1 hour'); // Agregar una hora de descanso
                            echo "El recorrido cruza la HoraBandera ({$repartidor->getHoraBandera()->format('H:i')}). Se ha añadido 1 hora de descanso.<br>";
                            $this->establecerDescanso($repartidor->getNomina(), 1);
                            $repartidor->setDescanso(1);
                        }

                        // Mostrar hora estimada de regreso
                        echo "Hora estimada de regreso a la sede: {$horaEstimadaRegreso->format('H:i')}<br>";

                        if ($tiempoRegresoSede > new DateTime('18:00:00')) {
                            echo "Repartidor {$repartidor->getNomina()} no puede regresar a la sede antes de las 18:00. Pedido {$pedido->getPedido()} no asignado.<br>";
                            continue; // Saltar este pedido
                        }

                        // Si pasa la validación, se asigna el pedido
                        if ($repartidor->puedeTrabajarHasta($tiempoRegresoSede)) {
                            echo "Evaluando Pedido {$pedido->getPedido()} para repartidor {$repartidor->getNomina()} - Distancia: $distanciaARepartidor km, Tiempo de ida: $tiempoViajeMinutos min, Tiempo de regreso: $tiempoRegresoMinutos min<br>";

                            if ($distanciaARepartidor < $distanciaMinima) {
                                $distanciaMinima = $distanciaARepartidor;
                                $nodoAsignado = $repartidor;
                                $tiempoEstimadoLlegada = $horaEstimadaLlegada;
                            }
                        } else {
                            echo "Repartidor {$repartidor->getNomina()} no puede completar el Pedido {$pedido->getPedido()} y regresar a la sede a tiempo.<br>";
                        }
                    }
                }
            }

            if ($nodoAsignado) {
                if ($this->registrarEnvio($pedido, $nodoAsignado)) {
                    $nodoAsignado->actualizarVolumenOcupado($pedido->getVolumenTotal());
                    $nodoAsignado->actualizarUbicacion($pedido->getLatitud(), $pedido->getLongitud());
                    $nodoAsignado->setTiempo($tiempoEstimadoLlegada);
                    $nodoAsignado->incrementarPedidosAsignados(); // Incrementar el contador de pedidos asignados
                    echo "Pedido {$pedido->getPedido()} asignado a repartidor {$nodoAsignado->getNomina()}.<br>";

                    // Actualizar el kilometraje del vehículo (calculando la distancia desde la sede hasta el pedido)
                    $kilometrosRecorridos = $this->calcularDistanciaHaversine(
                        $sede['latitud'],
                        $sede['longitud'],
                        $pedido->getLatitud(),
                        $pedido->getLongitud()
                    );
                    $nodoAsignado->agregarKilometrosVehiculo($kilometrosRecorridos);
                    $this->actualizarKilometrosRecorridos($nodoAsignado->getVehiculo()->getPlaca(), $nodoAsignado->getVehiculo()->getKilometrosRecorridos());
                    echo "Kilometraje del vehículo del repartidor {$nodoAsignado->getNomina()} actualizado a {$nodoAsignado->getVehiculo()->getKilometrosRecorridos()} km.<br>";
                }
                $nodosAsignados[$nodoAsignado->getNomina()][] = $pedido;
            } else {
                echo "Pedido {$pedido->getPedido()} no pudo ser asignado a ningún repartidor.<br><br>";
            }
        }

        // Al final de la asignación, actualizar el estado de los repartidores a "Ocupado" en la base de datos
        foreach ($repartidores as $repartidor) {
            if ($repartidor->getPedidosAsignados() > 0) { // Solo actualizar si el repartidor tiene pedidos asignados
                $sqlActualizarRepartidor = "UPDATE repartidor SET Estado = 'Ocupado' WHERE Nomina = ?";
                $stmt = $this->conexion->prepare($sqlActualizarRepartidor);
                if ($stmt) {
                    $nomina =  $repartidor->getNomina();
                    $stmt->bind_param("i", $nomina);
                    if (!$stmt->execute()) {
                        echo "Error al actualizar el estado del repartidor {$repartidor->getNomina()}: " . $stmt->error . "<br>";
                    }
                    $stmt->close();
                } else {
                    echo "Error en la preparación de la consulta para actualizar el repartidor {$repartidor->getNomina()}: " . $this->conexion->error . "<br>";
                }
            }
        }

        // Actualizar el estado del repartidor foráneo si tiene pedidos asignados
        if ($repartidorForaneo && $repartidorForaneo->getPedidosAsignados() > 0) {
            $sqlActualizarRepartidorForaneo = "UPDATE repartidor SET Estado = 'Ocupado' WHERE Nomina = ?";
            $stmtForaneo = $this->conexion->prepare($sqlActualizarRepartidorForaneo);
            if ($stmtForaneo) {
                $stmtForaneo->bind_param("i", $repartidorForaneo->getNomina());
                if (!$stmtForaneo->execute()) {
                    echo "Error al actualizar el estado del repartidor foráneo {$repartidorForaneo->getNomina()}: " . $stmtForaneo->error . "<br>";
                }
                $stmtForaneo->close();
            } else {
                echo "Error en la preparación de la consulta para actualizar el repartidor foráneo {$repartidorForaneo->getNomina()}: " . $this->conexion->error . "<br>";
            }
        }

        return $nodosAsignados;
    }

    // Método para actualizar los kilómetros recorridos de un repartidor en la base de datos
    private function actualizarKilometrosRecorridos($placa, $kilometrosRecorridos)
    {
        $sql = "UPDATE vehiculo SET KilometrosRecorridos = ? WHERE Placa = ?";
        $stmt = $this->conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ds", $kilometrosRecorridos, $placa);
            if ($stmt->execute()) {
                echo "KilometrosRecorridos del vehículo con placa {$placa} actualizado a {$kilometrosRecorridos} km.<br>";
            } else {
                echo "Error al actualizar KilometrosRecorridos del vehículo con placa {$placa}: " . $stmt->error . "<br>";
            }
            $stmt->close();
        } else {
            echo "Error en la preparación de la consulta para actualizar KilometrosRecorridos: " . $this->conexion->error . "<br>";
        }
    }

    public function limitePedido()
    {
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
        $sqlRepartidoresDisponibles = "SELECT COUNT(*) AS totalRepartidores FROM repartidor WHERE Estado = 'Disponible' AND Descanso = 0";
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

    // Obtener el repartidor designado para pedidos foráneos (al azar)
    private function obtenerRepartidorForaneo()
    {
        // Seleccionar un repartidor al azar que esté disponible
        $sql = "SELECT Nomina, Nombre, Apellidos, Estado, Latitud, Longitud, Descanso, HoraBandera 
                FROM repartidor 
                WHERE Estado = 'Disponible' 
                ORDER BY RAND() 
                LIMIT 1";
        $resultado = $this->conexion->query($sql);
        
        if ($resultado && $resultado->num_rows > 0) {
            echo "<br>Procesando repartidor foraneo";
            $fila = $resultado->fetch_assoc();
            $vehiculo = $this->obtenerVehiculoMayorCapacidad();
            if ($vehiculo) {
                echo "<br>Se encontro un vehiculo";
                return new Repartidor(
                    $fila['Nomina'],
                    $fila['Nombre'],
                    $fila['Apellidos'],
                    $fila['Latitud'],
                    $fila['Longitud'],
                    $fila['Estado'],
                    $fila['Descanso'],
                    $fila['HoraBandera'],
                    $vehiculo,
                    true
                );
            }
            
        } else {
            echo "No se encontró un repartidor disponible para pedidos foráneos.<br>";
            return null;
        }
    }

    // Seleccionar el vehículo con mayor capacidad
    private function obtenerVehiculoMayorCapacidad()
    {
        $sql = "SELECT Placa, Largo, Alto, Ancho, Modelo, Estado, KilometrosRecorridos 
                FROM vehiculo 
                WHERE Estado = 'En circulación' 
                ORDER BY (Largo * Alto * Ancho) DESC 
                LIMIT 1";
        $resultado = $this->conexion->query($sql);

        if ($resultado && $resultado->num_rows > 0) {
            $fila = $resultado->fetch_assoc();
            return new Vehiculo(
                $fila['Placa'],
                $fila['Largo'],
                $fila['Alto'],
                $fila['Ancho'],
                $fila['Modelo'],
                $fila['Estado'],
                $fila['KilometrosRecorridos']
            );
        } else {
            echo "No se encontró ningún vehículo disponible para pedidos foráneos.<br>";
            return null;
        }
    }
}
