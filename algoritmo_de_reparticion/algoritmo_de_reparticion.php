<?php
// Incluir los archivos de conexión y funciones
include_once '../php/conexion.php';
include_once 'funciones_de_reparticion.php';
include_once 'clases/Pedido.php';
include_once 'clases/Ordenamiento.php';
include_once 'clases/Nodo.php';

// Proceso principal para la asignación de pedidos
$total_pedidos = obtenerTotalPedidos($conexion);
$dias_restantes = calcularDiasRestantes();
$repartidores = obtenerRepartidoresDisponibles($conexion);
$total_repartidores = count($repartidores);

if ($dias_restantes > 0 && $total_repartidores > 0) {
    $limite_pedidos_diarios = ceil($total_pedidos / $dias_restantes / $total_repartidores);

    echo "<h3>Límite de pedidos por repartidor</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Repartidor ID</th><th>Límite de pedidos por día</th></tr>";
    foreach ($repartidores as $id_repartidor) {
        echo "<tr><td>$id_repartidor</td><td>$limite_pedidos_diarios</td></tr>";
    }
    echo "</table><br>";

    $vehiculo_disponible = obtenerVehiculoDisponible($conexion);
    if (!$vehiculo_disponible) {
        die("No hay vehículos disponibles en circulación.");
    }

    // Instancia de la clase Ordenamiento
    $ordenamiento = new Ordenamiento($conexion);

    // Asignación de pedidos con "Entrega parcial" (prioritarios)
    $pedidos_prioritarios = $ordenamiento->obtenerPedidosEntregaParcial();
    while ($pedido = $pedidos_prioritarios->fetch_assoc()) {
        $numVenta = $pedido['NumVenta'];
        $repartidorId = $ordenamiento->seleccionarRepartidor();
        $vehiculo = $ordenamiento->seleccionarVehiculo();

        if ($repartidorId && $vehiculo) {
            $ordenamiento->asignarPedidoARepartidor($numVenta, $repartidorId, $vehiculo);
        }
    }

    // Asignación de pedidos con "Solicitado"
    $pedidos_solicitados = $ordenamiento->obtenerPedidosSolicitados();
    while ($pedido = $pedidos_solicitados->fetch_assoc()) {
        $numVenta = $pedido['NumVenta'];
        $repartidorId = $ordenamiento->seleccionarRepartidor();
        $vehiculo = $ordenamiento->seleccionarVehiculo();

        if ($repartidorId && $vehiculo) {
            $ordenamiento->asignarPedidoARepartidor($numVenta, $repartidorId, $vehiculo);
        }
    }
} else {
    echo "No hay suficientes días o repartidores para distribuir los pedidos.";
}

// Mostrar las tablas de información
function mostrarTabla($conexion, $sql, $titulo, $columnas) {
    $resultado = $conexion->query($sql);

    echo "<h2>$titulo</h2>";
    echo "<table border='1' cellpadding='10' cellspacing='0'>";
    echo "<tr>";
    foreach ($columnas as $columna) {
        echo "<th>$columna</th>";
    }
    echo "</tr>";

    while ($fila = $resultado->fetch_assoc()) {
        echo "<tr>";
        foreach ($columnas as $columna) {
            echo "<td>{$fila[$columna]}</td>";
        }
        echo "</tr>";
    }

    echo "</table><br>";
}

// Tabla de envíos
$sql_envios = "SELECT OrdenR, Cantidad, Vehiculo, Producto, Repartidor, NumVenta FROM envios";
$columnas_envios = ["OrdenR", "Cantidad", "Vehiculo", "Producto", "Repartidor", "NumVenta"];
mostrarTabla($conexion, $sql_envios, "Tabla de Envíos", $columnas_envios);

// Tabla de pedidos
$sql_pedidos = "SELECT NumVenta, Fecha, Estado, FK_Usuario FROM pedidos";
$columnas_pedidos = ["NumVenta", "Fecha", "Estado", "FK_Usuario"];
mostrarTabla($conexion, $sql_pedidos, "Tabla de Pedidos", $columnas_pedidos);

// Tabla de repartidores
$sql_repartidores = "SELECT Nomina, Nombre, Apellidos, Estado FROM repartidor";
$columnas_repartidores = ["Nomina", "Nombre", "Apellidos", "Estado"];
mostrarTabla($conexion, $sql_repartidores, "Tabla de Repartidores", $columnas_repartidores);

// Tabla de vehículos
$sql_vehiculos = "SELECT Placa, Largo, Alto, Ancho, Modelo, Estado FROM vehiculo";
$columnas_vehiculos = ["Placa", "Largo", "Alto", "Ancho", "Modelo", "Estado"];
mostrarTabla($conexion, $sql_vehiculos, "Tabla de Vehículos", $columnas_vehiculos);

$conexion->close();
?>