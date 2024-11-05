<?php
include_once 'Repartidor.php';
class Ordenamiento {
    private $apiKey;
    private $conexion;

    public function __construct($conexion) {
        $config = include '../../config.php';
        $this->apiKey = $config['api_keys']['google_maps_api_key'];
        $this->conexion = $conexion;
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

    // Método de Haversine para calcular la distancia entre dos coordenadas
    public function calcularDistanciaHaversine($lat1, $lon1, $lat2, $lon2) {
        $radioTierra = 6378; // Kilómetros
        $difLat = deg2rad($lat2 - $lat1);
        $difLon = deg2rad($lon2 - $lon1);
        $a = sin($difLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($difLon / 2) ** 2;
        $c = 2 * asin(sqrt($a));
        return $radioTierra * $c;
    }

    // Generar la ruta óptima para cada repartidor usando el vecino más cercano
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

    

    // Método para asignar los pedidos a los repartidores según la proximidad y capacidad
    public function asignarNodosARepartidores($pedidos, $repartidores, $sede) {
        $nodosAsignados = [];
        
        foreach ($repartidores as $repartidor) {
            $repartidor->actualizarUbicacion($sede['latitud'], $sede['longitud']);
            $repartidor->tiempo = new DateTime('09:00'); 
        }
        
        foreach ($pedidos as $pedido) {
            echo "Procesando {$pedido->pedido} - Volumen: {$pedido->volumen_total}, Dimensiones: {$pedido->largo_maximo} x {$pedido->alto_maximo} x {$pedido->ancho_maximo}<br>";
            
            $nodoAsignado = null;
            $distanciaMinima = INF;
            $tiempoEstimadoLlegada = null;
            $tiempoRegresoSede = null;
        
            foreach ($repartidores as $repartidor) {
                $puedeTransportar = $repartidor->puedeTransportarPedido($pedido->volumen_total, $pedido->largo_maximo, $pedido->alto_maximo, $pedido->ancho_maximo);
                
                if ($puedeTransportar) {
                    // Calcula distancia y tiempo de ida y regreso
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
        
                    // Clona el tiempo actual del repartidor para el cálculo
                    $horaEstimadaLlegada = clone $repartidor->tiempo;
                    $horaEstimadaLlegada->modify("+{$tiempoViajeMinutos} minutes");
                    
                    $horaEstimadaRegreso = clone $horaEstimadaLlegada;
                    $horaEstimadaRegreso->modify("+{$tiempoRegresoMinutos} minutes");
        
                    // Verifica si el repartidor puede completar el pedido y regresar antes de la hora límite
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
                } else {
                    $volumenDisponible = $repartidor->calcularVolumenDisponible();
                    echo "El repartidor {$repartidor->nomina} no puede transportar {$pedido->pedido} debido a:<br>";
                    
                    if ($pedido->calcularVolumenPaquete() > $volumenDisponible) {
                        echo "- Insuficiente volumen disponible. Volumen requerido: {$pedido->calcularVolumenPaquete()}, disponible: {$volumenDisponible}.<br>";
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
                $nodoAsignado->actualizarUbicacion($pedido->latitud, $pedido->longitud);
                $nodoAsignado->tiempo = $tiempoEstimadoLlegada;
        
                echo "Pedido {$pedido->pedido} asignado a repartidor {$nodoAsignado->nomina}. Hora estimada de llegada: {$nodoAsignado->tiempo->format('H:i')}, Hora de regreso a sede: {$tiempoRegresoSede->format('H:i')}<br><br>";
        
                $nodosAsignados[$nodoAsignado->nomina][] = $pedido;
            } else {
                echo "Pedido {$pedido->pedido} no pudo ser asignado a ningún repartidor.<br><br>";
            }
        }
        
        return $nodosAsignados;
    }
    
    

    // Método para verificar y crear un repartidor con vehículo asignado
    public function crearRepartidorDisponible() {
        // Verificar si hay repartidores y vehículos disponibles
        $sqlRepartidorCount = "SELECT COUNT(*) AS totalRepartidores FROM repartidor WHERE Estado = 'Disponible'";
        $resultadoRepartidorCount = $this->conexion->query($sqlRepartidorCount);
        $cantidadRepartidores = $resultadoRepartidorCount->fetch_assoc()['totalRepartidores'];

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
            $datosVehiculo['Placa'],
            $datosRepartidor['Nomina'],
            $datosVehiculo['Largo'],
            $datosVehiculo['Alto'],
            $datosVehiculo['Ancho']
        );

        // Marcar el repartidor como ocupado en la base de datos
        $sqlActualizarRepartidor = "UPDATE repartidor SET Estado = 'Ocupado' WHERE Nomina = '{$datosRepartidor['Nomina']}'";
        $this->conexion->query($sqlActualizarRepartidor);

        // Retornar el objeto repartidor con el vehículo asignado
        return $repartidor;
    }
}
?>
