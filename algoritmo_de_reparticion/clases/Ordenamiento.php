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
        $this->apiKey = 'AIzaSyDTtTA9nfl0phn0i-i4VYbgRbvIB_MzaOs';
        $this->conexion = $conexion;

        if ($this->conexion->connect_error) {
            die("Error en la conexión: " . $conexion->connect_error);
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
            $stmtProducto->close();
            return false;
        }

        // Cerrar la consulta de producto
        $stmtProducto->close();

        // Insertar en la tabla de envíos
        $sql = "INSERT INTO envios (OrdenR, Cantidad, Vehiculo, Producto, Repartidor, NumVenta) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $vehiculoPlaca = $repartidor->getVehiculo() ? $repartidor->getVehiculo()->getPlaca() : null;
        $orden = $repartidor->actualizarOrden();
        $nomina = $repartidor->getNomina();

        $stmt->bind_param("iisiii", $orden, $cantidad, $vehiculoPlaca, $producto, $nomina, $numVenta);

        if ($stmt->execute()) {
            // Actualizar el estado del pedido a 'En camino'
            $this->actualizarEstadoPedido($numVenta, 'En camino');
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }

    // Método para verificar y crear un repartidor con o sin vehículo asignado
    public function crearRepartidoresDisponibles()
    {
        $repartidores = [];
        // Obtener todos los repartidores disponibles y que no están en descanso
        $sqlRepartidores = "SELECT Nomina, Nombre, Apellidos, Estado, Latitud, Longitud, Descanso, HoraBandera FROM repartidor WHERE Estado = 'Disponible'";

        $resultadoRepartidores = $this->conexion->query($sqlRepartidores);

        if (!$resultadoRepartidores) {
            return $repartidores;
        }
        
        // Verificar que haya repartidores disponibles
        if ($resultadoRepartidores->num_rows == 0) {
            
            return $repartidores;
        }

        // Obtener vehículos disponibles
        $sqlVehiculos = "SELECT Placa, Largo, Alto, Ancho, Modelo, Estado, KilometrosRecorridos FROM vehiculo WHERE Estado = 'En circulación'";
        $resultadoVehiculos = $this->conexion->query($sqlVehiculos);

        if (!$resultadoVehiculos) {
            
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
                $vehiculo = array_shift($vehiculosDisponibles);
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
                $this->exitenForaneos = $filaPedido['Foraneo'] === 'Sí' ? true : $this->exitenForaneos;
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
                continue; // O manejar de otra manera según tu lógica
            }

            $objetosPedidos[] = new Pedido(
                $datos['NumVenta'],
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
        $stmt->bind_param("ii", $estadoDescanso, $nomina);
        $stmt->execute();
        $stmt->close();
    }

    // Método para actualizar los kilómetros recorridos de un repartidor en la base de datos
    private function actualizarKilometrosRecorridos($placa, $kilometrosRecorridos)
    {
        $sql = "UPDATE vehiculo SET KilometrosRecorridos = ? WHERE Placa = ?";
        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("ds", $kilometrosRecorridos, $placa);
        $stmt->execute();
        $stmt->close();
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
            $fila = $resultado->fetch_assoc();
            $vehiculo = $this->obtenerVehiculoMayorCapacidad();
            if ($vehiculo) {
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
            return null;
        }
    }

    public function decidirRegresoASedeDistancia($repartidor, $sede, $siguienteNodo = null)
    {
        // Obtener la ubicación actual del repartidor
        $latRepartidor = $repartidor->getLatitud();
        $lonRepartidor = $repartidor->getLongitud();

        // Calcular distancia a la sede
        $distanciaASede = $this->calcularDistanciaHaversine(
            $latRepartidor,
            $lonRepartidor,
            $sede['latitud'],
            $sede['longitud']
        );

        if ($siguienteNodo !== null) {
            // Si se pasó un siguiente nodo (por ejemplo, otro pedido), calculamos la distancia a ese nodo
            $distanciaASiguienteNodo = $this->calcularDistanciaHaversine(
                $latRepartidor,
                $lonRepartidor,
                $siguienteNodo['latitud'],
                $siguienteNodo['longitud']
            );

            // Si la sede está más cerca que el siguiente nodo o si la diferencia es notable y conviene volver.
            if ($distanciaASede < $distanciaASiguienteNodo) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    public function decidirRegresoASedeTiempo($repartidor, $sede, $siguienteNodo = null)
    {
        // Obtener la ubicación actual del repartidor
        $latRepartidor = $repartidor->getLatitud();
        $lonRepartidor = $repartidor->getLongitud();

        // Calcular el tiempo de viaje a la sede
        $tiempoASedeSegundos = $this->calcularTiempoViaje(
            $latRepartidor,
            $lonRepartidor,
            $sede['latitud'],
            $sede['longitud']
        );

        // Si no se pudo calcular el tiempo a la sede, no se puede tomar una decisión basada en tiempo
        if ($tiempoASedeSegundos === null) {
            return false;
        }

        // Si no hay siguiente nodo, es decir, no hay más pedidos
        if ($siguienteNodo === null) {
            return true;
        }

        // Si hay un siguiente nodo, calcular el tiempo hacia ese nodo
        $tiempoANodoSegundos = $this->calcularTiempoViaje(
            $latRepartidor,
            $lonRepartidor,
            $siguienteNodo['latitud'],
            $siguienteNodo['longitud']
        );

        // Si no se pudo obtener el tiempo al siguiente nodo, y sí al a sede, podrías decidir regresar a la sede
        if ($tiempoANodoSegundos === null) {
            return true;
        }

        // Aquí decides la lógica: por ejemplo, regresar a la sede si el tiempo a la sede es menor al tiempo al siguiente nodo.
        if ($tiempoASedeSegundos < $tiempoANodoSegundos) {
            // El tiempo a la sede es menor que el tiempo al siguiente nodo, se prefiere volver a la sede
            return true;
        } else {
            // De lo contrario, continuar al siguiente nodo
            return false;
        }
    }




    // Método para asignar pedidos a repartidores
    public function asignarNodosARepartidores($pedidos, $repartidores, $sede)
    {
        echo "Iniciando asignación de pedidos...<br>";
        $nodosAsignados = [];

        // Obtener el repartidor foráneo si existen
        if ($this->exitenForaneos) {
            echo "Verificando repartidores foráneos...<br>";
            $repartidorForaneo = $this->obtenerRepartidorForaneo();

            if ($repartidorForaneo) {
                echo "Repartidor foráneo encontrado: {$this->foraneo}<br>";
                $this->foraneo = $repartidorForaneo->getNomina();

                // Buscar y eliminar el repartidor foráneo de la lista de repartidores locales
                foreach ($repartidores as $key => $repartidor) {
                    if ($repartidor->getNomina() === $this->foraneo) {
                        unset($repartidores[$key]); // Eliminar repartidor foráneo de la lista
                        echo "Repartidor foráneo eliminado de la lista de locales.<br>";
                    }
                }
                $repartidores = array_values($repartidores);
                $nomina = $repartidorForaneo->getNomina();
                // Inicializar ubicación y tiempo del repartidor foráneo
                $repartidorForaneo->actualizarUbicacion($sede['latitud'], $sede['longitud']);
                $repartidorForaneo->setTiempo(new DateTime()); // Usar la hora actual de la sede
                $repartidorForaneo->setPedidosAsignados(0); // Contador de pedidos asignados
            } else {
                echo "No se encontró repartidor foráneo.<br>";
            }
        }

        // Inicializar ubicación y tiempo de los demás repartidores
        echo "Inicializando repartidores locales...<br>";
        foreach ($repartidores as $repartidor) {
            $repartidor->actualizarUbicacion($sede['latitud'], $sede['longitud']);
            $repartidor->setTiempo(new DateTime()); // Usar la hora actual de la sede
            $repartidor->setPedidosAsignados(0); // Contador de pedidos asignados
            echo "Repartidor local {$repartidor->getNomina()} inicializado.<br>";
            $debeRegresar = $this->decidirRegresoASedeDistancia($repartidor, $sede);
        }

        // Procesar cada pedido
        foreach ($pedidos as $pedido) {
            echo "Procesando pedido {$pedido->getPedido()}...<br>";
            $nodoAsignado = null;
            $distanciaMinima = INF;
            $tiempoEstimadoLlegada = null;
            $tiempoRegresoSede = null;

            // Verificar si el pedido es foráneo
            $esForaneo = ($pedido->getForaneo() == 'Sí');
            echo $esForaneo ? "El pedido es foráneo.<br>" : "El pedido es local.<br>";

            if ($esForaneo) {
                echo "Asignando pedido foráneo...<br>";
                if ($repartidorForaneo && $repartidorForaneo->puedeTransportarPedido(
                    $pedido->getVolumenTotal(),
                    $pedido->getLargoMaximo(),
                    $pedido->getAltoMaximo(),
                    $pedido->getAnchoMaximo()
                )) {
                    // Calcular el tiempo de viaje desde la ubicación actual del repartidor foráneo hasta el pedido
                    $tiempoViajeSegundos = $this->calcularTiempoViaje(
                        $repartidorForaneo->getLatitud(),
                        $repartidorForaneo->getLongitud(),
                        $pedido->getLatitud(),
                        $pedido->getLongitud()
                    );

                    // Tiempo de regreso a la sede
                    $tiempoRegresoSegundos = $this->calcularTiempoViaje(
                        $pedido->getLatitud(),
                        $pedido->getLongitud(),
                        $sede['latitud'],
                        $sede['longitud']
                    );

                    if ($tiempoViajeSegundos === null || $tiempoRegresoSegundos === null) {
                        continue;
                    }

                    // Añadir 10 minutos por imprevistos
                    $tiempoViajeSegundos += 600; // 10 minutos por imprevistos

                    // Convertir a minutos y calcular el tiempo estimado de llegada
                    $tiempoViajeMinutos = (int)($tiempoViajeSegundos / 60);
                    $tiempoRegresoMinutos = (int)($tiempoRegresoSegundos / 60);

                    // Hora estimada de llegada
                    $horaEstimadaLlegada = clone $repartidor->getTiempo();
                    $horaEstimadaLlegada->modify("+{$tiempoViajeMinutos} minutes");

                    // Hora estimada de regreso
                    $horaEstimadaRegreso = clone $horaEstimadaLlegada;
                    $horaEstimadaRegreso->modify("+{$tiempoRegresoMinutos} minutes");

                    // Formatear para depuración
                    echo "Hora estimada llegada: " . $horaEstimadaLlegada->format('H:i:s') . "<br>";
                    echo "Hora estimada regreso: " . $horaEstimadaRegreso->format('H:i:s') . "<br>";
                    echo "Viaje en minutos: " . $tiempoViajeMinutos . "<br>";


                    // Verificar HoraBandera
                    if ($horaEstimadaLlegada >= $repartidorForaneo->getHoraBandera() && $repartidorForaneo->getDescanso() == 0) {
                        $horaEstimadaRegreso->modify('+1 hour');
                        $this->establecerDescanso($repartidorForaneo->getNomina(), 1);
                        $repartidorForaneo->setDescanso(1);
                    }

                    // Verificar la hora límite (ej: 18:00)
                    $horaLimite = new DateTime('18:00:00');
                    if ($horaEstimadaRegreso > $horaLimite) {
                        continue;
                    }

                    // Si pasa todas las validaciones, asignar el pedido
                    if ($this->registrarEnvio($pedido, $repartidorForaneo)) {
                        $repartidorForaneo->actualizarVolumenOcupado($pedido->getVolumenTotal());
                        $repartidorForaneo->actualizarUbicacion($pedido->getLatitud(), $pedido->getLongitud());
                        $repartidorForaneo->setTiempo($horaEstimadaLlegada);
                        $repartidorForaneo->incrementarPedidosAsignados();

                        // Actualizar kilometraje
                        $kilometrosRecorridos = $this->calcularDistanciaHaversine(
                            $sede['latitud'],
                            $sede['longitud'],
                            $pedido->getLatitud(),
                            $pedido->getLongitud()
                        );
                        $repartidorForaneo->agregarKilometrosVehiculo($kilometrosRecorridos);
                        $this->actualizarKilometrosRecorridos(
                            $repartidorForaneo->getVehiculo()->getPlaca(),
                            $repartidorForaneo->getVehiculo()->getKilometrosRecorridos()
                        );

                        $debeRegresarForaneo = $this->decidirRegresoASedeTiempo($repartidorForaneo, $sede);
                        if ($debeRegresarForaneo) {
                            $repartidorForaneo->actualizarUbicacion($sede['latitud'], $sede['longitud']);
                            $repartidorForaneo = null;
                        }

                        $nodoAsignado = $repartidorForaneo;
                    }
                } else {
                    continue;
                }
            } else {
                // Lógica de asignación para pedidos locales
                echo "Asignando pedido local...<br>";
                foreach ($repartidores as $repartidor) {
                    // Verificar si el repartidor puede transportar el pedido
                    echo "Evaluando repartidor {$repartidor->getNomina()} para el pedido...<br>";
                    echo "Hora actual del repartidor: " . $repartidor->getTiempo()->format('H:i:s') . "<br>";

                    if ($repartidor->puedeTransportarPedido(
                        $pedido->getVolumenTotal(),
                        $pedido->getLargoMaximo(),
                        $pedido->getAltoMaximo(),
                        $pedido->getAnchoMaximo()
                    )) {
                        echo "1-";
                        $distanciaARepartidor = $this->calcularDistanciaHaversine(
                            $repartidor->getLatitud(),
                            $repartidor->getLongitud(),
                            $pedido->getLatitud(),
                            $pedido->getLongitud()
                        );
                        echo "2-";
                        $tiempoViajeSegundos = $this->calcularTiempoViaje(
                            $repartidor->getLatitud(),
                            $repartidor->getLongitud(),
                            $pedido->getLatitud(),
                            $pedido->getLongitud()
                        );
                        echo "3-";
                        $tiempoRegresoSegundos = $this->calcularTiempoViaje(
                            $pedido->getLatitud(),
                            $pedido->getLongitud(),
                            $sede['latitud'],
                            $sede['longitud']
                        );
                        echo "4-";
                        if ($tiempoViajeSegundos === null || $tiempoRegresoSegundos === null) {
                            continue;
                        }
                        echo "5-";
                        // Sumar 10 minutos por imprevistos
                        $tiempoViajeSegundos += 600; // 600 segundos = 10 minutos

                        // Convertir a minutos
                        $tiempoViajeMinutos = (int)($tiempoViajeSegundos / 60);
                        $tiempoRegresoMinutos = (int)($tiempoRegresoSegundos / 60);

                        echo "Tiempo de viaje en segundos: {$tiempoViajeSegundos}<br>";
                        echo "Tiempo de regreso en segundos: {$tiempoRegresoSegundos}<br>";
                        echo "Tiempo de viaje en minutos: {$tiempoViajeMinutos}<br>";
                        echo "Tiempo de regreso en minutos: {$tiempoRegresoMinutos}<br>";

                        // Calcular tiempos
                        $horaEstimadaLlegada = clone $repartidor->getTiempo();
                        $horaEstimadaLlegada->modify("+{$tiempoViajeMinutos} minutes");

                        $horaEstimadaRegreso = clone $horaEstimadaLlegada;
                        $horaEstimadaRegreso->modify("+{$tiempoRegresoMinutos} minutes");

                        // Validar los resultados
                        echo "Hora inicial del repartidor: " . $repartidor->getTiempo()->format('H:i:s') . "<br>";
                        echo "Hora estimada llegada después de viaje: " . $horaEstimadaLlegada->format('H:i:s') . "<br>";
                        echo "Hora estimada regreso después de viaje: " . $horaEstimadaRegreso->format('H:i:s') . "<br>";


                        // Verificar HoraBandera
                        if ($horaEstimadaLlegada >= $repartidor->getHoraBandera() && $repartidor->getDescanso() == 0) {
                            $horaEstimadaRegreso->modify('+1 hour'); // Agregar una hora de descanso
                            $this->establecerDescanso($repartidor->getNomina(), 1);
                            $repartidor->setDescanso(1);
                        }

                        $tiempoRegresoSede = $horaEstimadaRegreso;
                        echo "6-";
                        if ($tiempoRegresoSede > new DateTime('18:00:00')) {
                            // Formatear la hora para mostrarla
                            echo "Tiempo regreso sede: " . $tiempoRegresoSede->format('H:i:s') . "<br>";
                            continue; // Saltar este pedido
                        }

                        echo "7-";
                        if ($repartidor->puedeTrabajarHasta($tiempoRegresoSede)) {
                            if ($distanciaARepartidor < $distanciaMinima) {
                                $distanciaMinima = $distanciaARepartidor;
                                $nodoAsignado = $repartidor;
                                $tiempoEstimadoLlegada = $horaEstimadaLlegada;
                            }
                        }
                        echo "Repartidor {$repartidor->getNomina()} - Latitud: {$repartidor->getLatitud()}, Longitud: {$repartidor->getLongitud()}<br>";
                        echo "Pedido {$pedido->getPedido()} - Latitud: {$pedido->getLatitud()}, Longitud: {$pedido->getLongitud()}<br>";
                        echo "Distancia calculada: " . $this->calcularDistanciaHaversine($repartidor->getLatitud(), $repartidor->getLongitud(), $pedido->getLatitud(), $pedido->getLongitud()) . " km<br>";
                        echo "Tiempo de viaje en minutos: {$tiempoViajeMinutos}<br>";
                        echo "Repartidor {$repartidor->getNomina()} puede transportar el pedido.<br>";
                    } else {
                        echo "Repartidor {$repartidor->getNomina()} no puede transportar el pedido.<br>";
                    }
                }
            }

            if ($nodoAsignado) {
                echo "Pedido asignado a repartidor {$nodoAsignado->getNomina()}.<br>";
                if ($this->registrarEnvio($pedido, $nodoAsignado)) {
                    $nodoAsignado->actualizarVolumenOcupado($pedido->getVolumenTotal());
                    $nodoAsignado->actualizarUbicacion($pedido->getLatitud(), $pedido->getLongitud());
                    $nodoAsignado->setTiempo($tiempoEstimadoLlegada);
                    $nodoAsignado->incrementarPedidosAsignados();

                    // Actualizar el kilometraje del vehículo
                    $kilometrosRecorridos = $this->calcularDistanciaHaversine(
                        $sede['latitud'],
                        $sede['longitud'],
                        $pedido->getLatitud(),
                        $pedido->getLongitud()
                    );
                    $nodoAsignado->agregarKilometrosVehiculo($kilometrosRecorridos);
                    $this->actualizarKilometrosRecorridos($nodoAsignado->getVehiculo()->getPlaca(), $nodoAsignado->getVehiculo()->getKilometrosRecorridos());
                }
                $nodosAsignados[$nodoAsignado->getNomina()][] = $pedido;
            } else {
                echo "No se pudo asignar el pedido {$pedido->getPedido()}.<br>";
            }
        }

        // Al final de la asignación, actualizar el estado de los repartidores a "Ocupado"
        echo "Actualizando estado de los repartidores...<br>";
        foreach ($repartidores as $repartidor) {
            if ($repartidor->getPedidosAsignados() > 0) {
                echo "Repartidor {$repartidor->getNomina()} está ocupado.<br>";
                $sqlActualizarRepartidor = "UPDATE repartidor SET Estado = 'Ocupado' WHERE Nomina = ?";
                $stmt = $this->conexion->prepare($sqlActualizarRepartidor);
                if ($stmt) {
                    $nomina =  $repartidor->getNomina();
                    $stmt->bind_param("i", $nomina);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // Actualizar el estado del repartidor foráneo si tiene pedidos asignados
        if (isset($repartidorForaneo) && $repartidorForaneo->getPedidosAsignados() > 0) {
            $sqlActualizarRepartidorForaneo = "UPDATE repartidor SET Estado = 'Ocupado' WHERE Nomina = ?";
            $stmtForaneo = $this->conexion->prepare($sqlActualizarRepartidorForaneo);
            if ($stmtForaneo) {
                $stmtForaneo->bind_param("i", $repartidorForaneo->getNomina());
                $stmtForaneo->execute();
                $stmtForaneo->close();
            }
        }

        // Al final de la asignación, decidir si los repartidores deben regresar a la sede
        foreach ($repartidores as $key => $repartidor) {
            if ($debeRegresar) {
                $repartidor->actualizarUbicacion($sede['latitud'], $sede['longitud']);
                unset($repartidores[$key]);
            }
        }
        echo "Asignaciones completadas.<br>";
        return $nodosAsignados;
    }
}
