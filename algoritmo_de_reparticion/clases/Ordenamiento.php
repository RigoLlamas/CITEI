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
            echo "Conexion fallida";
            die("Error en la conexión: " . $conexion->connect_error);
        }
        echo "Conexion exitosa\n";
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
        echo "Registrando envío para el pedido ID: {$numVenta}\n";

        // Consulta para obtener el Producto asociado al pedido
        $sqlProducto = "SELECT Producto, Cantidad FROM detalles WHERE NumVenta = ?";
        echo "Consulta para obtener detalles del producto: {$sqlProducto}\n";

        $stmtProducto = $this->conexion->prepare($sqlProducto);
        if (!$stmtProducto) {
            echo "Error al preparar la consulta para detalles del producto: " . $this->conexion->error . "\n";
            return false;
        }

        $stmtProducto->bind_param("i", $numVenta);
        $stmtProducto->execute();
        $resultadoProducto = $stmtProducto->get_result();

        if ($resultadoProducto->num_rows > 0) {
            $filaProducto = $resultadoProducto->fetch_assoc();
            $producto = $filaProducto['Producto'];
            $cantidad = $filaProducto['Cantidad'];
            echo "Producto obtenido: {$producto}, Cantidad: {$cantidad}\n";
        } else {
            echo "No se encontró un producto asociado al pedido ID: {$numVenta}\n";
            $stmtProducto->close();
            return false;
        }

        // Cerrar la consulta de producto
        $stmtProducto->close();
        echo "Consulta de producto cerrada.\n";

        // Insertar en la tabla de envíos
        $sql = "INSERT INTO envios (OrdenR, Cantidad, Vehiculo, Producto, Repartidor, NumVenta) VALUES (?, ?, ?, ?, ?, ?)";
        echo "Consulta para registrar envío: {$sql}\n";

        $stmt = $this->conexion->prepare($sql);
        if (!$stmt) {
            echo "Error al preparar la consulta para registrar el envío: " . $this->conexion->error . "\n";
            return false;
        }

        // Validar y obtener datos del repartidor y vehículo
        $vehiculoPlaca = $repartidor->getVehiculo() ? $repartidor->getVehiculo()->getPlaca() : null;
        $orden = $repartidor->actualizarOrden();
        $nomina = $repartidor->getNomina();

        echo "Datos para insertar en envíos:\n";
        echo "- Orden: {$orden}\n";
        echo "- Cantidad: {$cantidad}\n";
        echo "- Vehículo: " . ($vehiculoPlaca ?? 'NULL') . "\n";
        echo "- Producto: {$producto}\n";
        echo "- Repartidor: {$nomina}\n";
        echo "- NumVenta: {$numVenta}\n";

        // Bind y ejecutar la consulta
        $stmt->bind_param("iisiii", $orden, $cantidad, $vehiculoPlaca, $producto, $nomina, $numVenta);
        try {
            if ($stmt->execute()) {
                echo "Envío registrado correctamente para el pedido ID: {$numVenta}\n";

                // Actualizar el estado del pedido a 'En camino'
                if ($this->actualizarEstadoPedido($numVenta, 'En camino')) {
                    echo "Estado del pedido ID: {$numVenta} actualizado a 'En camino'.\n";
                } else {
                    echo "Error al actualizar el estado del pedido ID: {$numVenta}.\n";
                }

                $stmt->close();
                return true;
            } else {
                echo "Error al ejecutar la consulta para registrar el envío: " . $stmt->error . "\n";
                $stmt->close();
                return false;
            }
        } catch (\Throwable $th) {
            echo "Error al insertar" . $th;
        }
    }



    // Método para verificar y crear un repartidor con o sin vehículo asignado
    public function crearRepartidoresDisponibles()
    {
        $repartidores = [];
        // Obtener todos los repartidores disponibles y que no están en descanso
        $sqlRepartidores = "SELECT Nomina, Nombre, Apellidos, Estado, Latitud, Longitud, Descanso, HoraBandera FROM repartidor WHERE Estado = 'Disponible'";

        echo "Consulta SQL para repartidores: $sqlRepartidores\n";

        $resultadoRepartidores = $this->conexion->query($sqlRepartidores);

        if (!$resultadoRepartidores) {
            echo "Error al ejecutar la consulta de repartidores: " . $this->conexion->error . "\n";
            return $repartidores;
        }

        // Verificar que haya repartidores disponibles
        if ($resultadoRepartidores->num_rows == 0) {
            echo "No hay repartidores disponibles. Total de filas: " . $resultadoRepartidores->num_rows . "\n";
            return $repartidores;
        } else {
            echo "Repartidores encontrados: " . $resultadoRepartidores->num_rows . "\n";
        }

        // Obtener vehículos disponibles
        $sqlVehiculos = "SELECT Placa, Largo, Alto, Ancho, Modelo, Estado, KilometrosRecorridos FROM vehiculo WHERE Estado = 'En circulación'";
        echo "Consulta SQL para vehículos: $sqlVehiculos\n";

        $resultadoVehiculos = $this->conexion->query($sqlVehiculos);

        if (!$resultadoVehiculos) {
            echo "Error al ejecutar la consulta de vehículos: " . $this->conexion->error . "\n";
            return $repartidores;
        }

        echo "Vehículos encontrados: " . $resultadoVehiculos->num_rows . "\n";

        // Almacenar los vehículos disponibles en un arreglo
        $vehiculosDisponibles = [];
        while ($filaVehiculo = $resultadoVehiculos->fetch_assoc()) {
            echo "Vehículo disponible: " . json_encode($filaVehiculo) . "\n";
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

        // Crear repartidores mientras haya repartidores y vehículos disponibles
        while ($repartidorData = $resultadoRepartidores->fetch_assoc()) {
            // Si no hay más vehículos ni repartidores, detener el proceso
            if (empty($vehiculosDisponibles)) {
                echo "No hay más vehículos disponibles para asignar. Proceso detenido.\n";
                break;
            }

            echo "Repartidor disponible: " . json_encode($repartidorData) . "\n";

            // Asignar un vehículo si está disponible
            $vehiculo = array_shift($vehiculosDisponibles);
            echo "Vehículo asignado: " . json_encode($vehiculo) . "\n";

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

        echo "Total de repartidores creados: " . count($repartidores) . "\n";
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
        echo "Consulta SQL para obtener pedidos: {$sqlPedidos}\n";

        $resultadoPedidos = $this->conexion->query($sqlPedidos);

        if (!$resultadoPedidos) {
            echo "Error en la consulta SQL para obtener pedidos: " . $this->conexion->error . "\n";
            return [];
        }

        echo "Pedidos encontrados: " . $resultadoPedidos->num_rows . "\n";

        if ($resultadoPedidos->num_rows > 0) {
            while ($filaPedido = $resultadoPedidos->fetch_assoc()) {
                echo "Pedido obtenido: " . json_encode($filaPedido) . "\n";

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
            echo "No se encontraron pedidos.\n";
            return []; // Si no hay pedidos, retorna vacío para detener el proceso
        }

        // Procesar detalles para cada pedido
        echo "Procesando detalles para cada pedido...\n";
        foreach ($pedidos as &$pedido) {
            $pedido = $this->agregardetallesAPedido($pedido);
            echo "Detalles añadidos al pedido: " . json_encode($pedido) . "\n";
        }

        // Convertir los datos en objetos de la clase Pedido
        $objetosPedidos = [];
        echo "Convirtiendo datos en objetos de la clase Pedido...\n";
        foreach ($pedidos as $datos) {
            // Validar que las coordenadas no sean null
            if (is_null($datos['Latitud']) || is_null($datos['Longitud'])) {
                echo "Coordenadas nulas para el pedido NumVenta: {$datos['NumVenta']}. Saltando.\n";
                continue; // O manejar de otra manera según tu lógica
            }

            $pedidoObj = new Pedido(
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

            echo "Objeto Pedido creado: " . json_encode($pedidoObj) . "\n";
            $objetosPedidos[] = $pedidoObj;
        }

        echo "Total de objetos Pedido creados: " . count($objetosPedidos) . "\n";

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
        echo "=== Iniciando asignación de pedidos ===\n";
        $nodosAsignados = [];

        // Obtener el repartidor foráneo si existen
        if ($this->exitenForaneos) {
            echo "Verificando disponibilidad de repartidor foráneo...\n";
            $repartidorForaneo = $this->obtenerRepartidorForaneo();

            if ($repartidorForaneo) {
                echo "Repartidor foráneo encontrado: {$repartidorForaneo->getNomina()}\n";
                $this->foraneo = $repartidorForaneo->getNomina();

                // Eliminar el repartidor foráneo de la lista de locales
                foreach ($repartidores as $key => $repartidor) {
                    if ($repartidor->getNomina() === $this->foraneo) {
                        unset($repartidores[$key]);
                        echo "Repartidor foráneo {$this->foraneo} eliminado de la lista de locales.\n";
                    }
                }
                $repartidores = array_values($repartidores); // Reorganizar índices

                // Inicializar ubicaciones y tiempos
                $repartidorForaneo->actualizarUbicacion($sede['latitud'], $sede['longitud']);
                $repartidorForaneo->setTiempo(new DateTime());
                $repartidorForaneo->setPedidosAsignados(0);
            } else {
                echo "No se encontró un repartidor foráneo disponible.\n";
            }
        }

        // Inicializar ubicación y tiempo de los repartidores locales
        echo "Inicializando repartidores locales...\n";
        foreach ($repartidores as $repartidor) {
            $repartidor->actualizarUbicacion($sede['latitud'], $sede['longitud']);
            $repartidor->setTiempo(new DateTime());
            $repartidor->setPedidosAsignados(0);
            echo "Repartidor local {$repartidor->getNomina()} inicializado en la ubicación de la sede.\n";
        }

        // Procesar cada pedido
        foreach ($pedidos as $pedido) {
            echo "Procesando pedido ID: {$pedido->getPedido()}...\n";
            $nodoAsignado = null;
            $distanciaMinima = INF;

            // Verificar si el pedido es foráneo
            $esForaneo = ($pedido->getForaneo() == 'Sí');
            echo $esForaneo ? "El pedido es foráneo.\n" : "El pedido es local.\n";

            if ($esForaneo) {
                echo "Intentando asignar el pedido foráneo al repartidor correspondiente...\n";
                if ($repartidorForaneo && $repartidorForaneo->puedeTransportarPedido(
                    $pedido->getVolumenTotal(),
                    $pedido->getLargoMaximo(),
                    $pedido->getAltoMaximo(),
                    $pedido->getAnchoMaximo()
                )) {
                    // Calcular tiempos de viaje
                    $tiempoViajeSegundos = $this->calcularTiempoViaje(
                        $repartidorForaneo->getLatitud(),
                        $repartidorForaneo->getLongitud(),
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
                        echo "Error calculando tiempos de viaje para el pedido foráneo. Saltando.\n";
                        continue;
                    }

                    // Registrar el envío
                    if ($this->registrarEnvio($pedido, $repartidorForaneo)) {
                        echo "Pedido ID: {$pedido->getPedido()} asignado al repartidor foráneo {$repartidorForaneo->getNomina()}.\n";
                        $repartidorForaneo->actualizarVolumenOcupado($pedido->getVolumenTotal());
                        $repartidorForaneo->actualizarUbicacion($pedido->getLatitud(), $pedido->getLongitud());
                        $repartidorForaneo->setTiempo(new DateTime());
                        $repartidorForaneo->incrementarPedidosAsignados();
                        $nodoAsignado = $repartidorForaneo;
                    } else {
                        echo "Error al registrar el pedido ID: {$pedido->getPedido()} para el repartidor foráneo.\n";
                    }
                } else {
                    echo "Repartidor foráneo no puede transportar el pedido ID: {$pedido->getPedido()}.\n";
                }
            } else {
                // Lógica para pedidos locales
                echo "Buscando repartidor local para el pedido ID: {$pedido->getPedido()}...\n";
                foreach ($repartidores as $repartidor) {
                    if ($repartidor->puedeTransportarPedido(
                        $pedido->getVolumenTotal(),
                        $pedido->getLargoMaximo(),
                        $pedido->getAltoMaximo(),
                        $pedido->getAnchoMaximo()
                    )) {
                        $distanciaARepartidor = $this->calcularDistanciaHaversine(
                            $repartidor->getLatitud(),
                            $repartidor->getLongitud(),
                            $pedido->getLatitud(),
                            $pedido->getLongitud()
                        );

                        if ($distanciaARepartidor < $distanciaMinima) {
                            $distanciaMinima = $distanciaARepartidor;
                            $nodoAsignado = $repartidor;
                        }
                    }
                }

                // Asignar y registrar el pedido al repartidor local
                if ($nodoAsignado) {
                    echo "Pedido ID: {$pedido->getPedido()} asignado al repartidor local {$nodoAsignado->getNomina()}.\n";
                    if ($this->registrarEnvio($pedido, $nodoAsignado)) {
                        $nodoAsignado->actualizarVolumenOcupado($pedido->getVolumenTotal());
                        $nodoAsignado->actualizarUbicacion($pedido->getLatitud(), $pedido->getLongitud());
                        $nodoAsignado->incrementarPedidosAsignados();
                    } else {
                        echo "Error al registrar el pedido ID: {$pedido->getPedido()} para el repartidor local.\n";
                    }
                } else {
                    echo "No se encontró repartidor disponible para el pedido ID: {$pedido->getPedido()}.\n";
                }
            }

            if ($nodoAsignado) {
                $nodosAsignados[$nodoAsignado->getNomina()][] = $pedido;
            }
        }

        echo "=== Asignación de pedidos completada ===\n";
        return $nodosAsignados;
    }
}
