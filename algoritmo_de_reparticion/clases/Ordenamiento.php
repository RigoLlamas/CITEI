<?php

class Ordenamiento {
    private $conexion;

    // Constructor para inicializar la conexión a la base de datos
    public function __construct($conexion) {
        $this->conexion = $conexion;
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

    // Método para asignar pedidos a repartidores según criterios específicos
    public function asignarPedidoARepartidor($numVenta, $repartidorId, $vehiculo) {
        $sql_detalles = "SELECT Producto, Cantidad FROM detalles WHERE NumVenta = ?";
        $stmt_detalles = $this->conexion->prepare($sql_detalles);
        $stmt_detalles->bind_param("i", $numVenta);
        $stmt_detalles->execute();
        $resultado_detalles = $stmt_detalles->get_result();

        // Procesar cada producto en el pedido
        while ($detalle = $resultado_detalles->fetch_assoc()) {
            $producto = $detalle['Producto'];
            $cantidad = $detalle['Cantidad'];

            // Registrar el envío en la tabla envios
            $sql_envio = "INSERT INTO envios (OrdenR, Cantidad, Vehiculo, Producto, Repartidor, NumVenta) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_envio = $this->conexion->prepare($sql_envio);
            $orden_ruta = 1; // Placeholder para el orden de ruta
            $stmt_envio->bind_param("iisiii", $orden_ruta, $cantidad, $vehiculo, $producto, $repartidorId, $numVenta);
            $stmt_envio->execute();

            // Actualizar el estado del pedido a "En camino"
            $this->actualizarEstadoPedido($numVenta, 'En camino');
        }
    }

    // Método para actualizar el estado del pedido
    private function actualizarEstadoPedido($numVenta, $estado) {
        $sql_actualizar = "UPDATE pedidos SET Estado = ? WHERE NumVenta = ?";
        $stmt = $this->conexion->prepare($sql_actualizar);
        $stmt->bind_param("si", $estado, $numVenta);
        $stmt->execute();
    }
}
?>
