<?php

// Función para obtener el total de pedidos en el sistema
function obtenerTotalPedidos($conexion) {
    $sql_pedidos_generales = "SELECT COUNT(*) AS total_pedidos FROM pedidos";
    $resultado = $conexion->query($sql_pedidos_generales);
    return $resultado->fetch_assoc()['total_pedidos'];
}

// Función para calcular los días restantes en la semana
function calcularDiasRestantes() {
    $dia_actual = date("N"); // 1 (Lunes) a 7 (Domingo)
    return 7 - $dia_actual;
}

// Función para obtener IDs de repartidores disponibles
function obtenerRepartidoresDisponibles($conexion) {
    $sql = "SELECT Nomina FROM repartidor WHERE Estado = 'Disponible'";
    $resultado = $conexion->query($sql);
    $repartidores = [];
    while ($repartidor = $resultado->fetch_assoc()) {
        $repartidores[] = $repartidor['Nomina'];
    }
    return $repartidores;
}

// Función para obtener un vehículo disponible en circulación
function obtenerVehiculoDisponible($conexion) {
    $sql = "SELECT Placa FROM vehiculo WHERE Estado = 'En circulación' LIMIT 1";
    $resultado = $conexion->query($sql);
    return $resultado->fetch_assoc()['Placa'];
}
?>
