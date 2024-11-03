<?php

class Ordenamiento {
    private $conexion;

    // Constante para el radio de la Tierra en kilómetros
    private const RADIO_TIERRA = 6378;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    // Método para calcular la distancia entre dos puntos con la fórmula de Haversine
    public function calcularDistanciaHaversine($lat1, $lon1, $lat2, $lon2) {
        // Convertir grados a radianes
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        // Fórmula de Haversine
        $difLat = $lat2 - $lat1;
        $difLon = $lon2 - $lon1;
        $a = sin($difLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($difLon / 2) ** 2;
        $c = 2 * asin(sqrt($a));

        return self::RADIO_TIERRA * $c;
    }

    public function calcularTiempoViaje($origenLat, $origenLng, $destinoLat, $destinoLng) {
        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins={$origenLat},{$origenLng}&destinations={$destinoLat},{$destinoLng}&key={$this->apiKey}";

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if ($data['status'] == 'OK') {
            $duracionSegundos = $data['rows'][0]['elements'][0]['duration']['value'];
            return $duracionSegundos; // Devuelve el tiempo en segundos
        }
        return null; // Manejo de errores en caso de fallo en la solicitud
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
