<?php
// Incluye el archivo de conexión a la base de datos
include '../php/conexion.php';

// Obtén la nómina del repartidor desde la URL o una variable predefinida
$nominaRepartidor = isset($_GET['nomina']) ? intval($_GET['nomina']) : 0;

// Consulta para obtener los repartidores ocupados
$consultaRepartidores = "SELECT Nomina, Nombre, Apellidos, Estado FROM repartidor WHERE Estado = 'Ocupado'";
$resultadoRepartidores = $conexion->query($consultaRepartidores);

// Consulta para obtener los envíos asignados al repartidor específico
$consultaEnvios = "
    SELECT e.Entrega, e.OrdenR, e.Cantidad, e.Vehiculo, p.Nombre AS Producto, v.Fecha, v.Estado
    FROM envios e
    JOIN pedidos v ON e.NumVenta = v.NumVenta
    JOIN producto p ON e.Producto = p.PK_Producto
    WHERE e.Repartidor = $nominaRepartidor
";
$resultadoEnvios = $conexion->query($consultaEnvios);

// Procesa los datos obtenidos de ambas consultas
$repartidores = [];
if ($resultadoRepartidores && $resultadoRepartidores->num_rows > 0) {
    while ($row = $resultadoRepartidores->fetch_assoc()) {
        $repartidores[] = $row;
    }
}

$envios = [];
if ($resultadoEnvios && $resultadoEnvios->num_rows > 0) {
    while ($row = $resultadoEnvios->fetch_assoc()) {
        $envios[] = $row;
    }
}

// Libera los resultados y cierra la conexión
$resultadoRepartidores->free();
$resultadoEnvios->free();
$conexion->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Rutas</title>
    <script src="../js/pie.js" defer></script>
    <script src="../js/navbar.js" defer></script>
    <link rel="stylesheet" href="ruta-a-tu-archivo.css">
</head>
<body>
    <div class="rutas-contenedor">
        <!-- Columna Izquierda: Lista de Repartidores -->
        <div class="columna-izquierda cuadro">
            <h3>Repartidores Ocupados</h3>
            <ul id="lista-repartidores">
                <?php if (!empty($repartidores)): ?>
                    <?php foreach ($repartidores as $repartidor): ?>
                        <li>
                            <a href="rutas.php?nomina=<?= htmlspecialchars($repartidor['Nomina']) ?>">
                                <strong><?= htmlspecialchars($repartidor['Nombre'] . " " . $repartidor['Apellidos']) ?></strong>
                            </a>
                            (Nómina: <?= htmlspecialchars($repartidor['Nomina']) ?>) - 
                            <span>Estado: <?= htmlspecialchars($repartidor['Estado']) ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>No hay repartidores ocupados en este momento.</li>
                <?php endif; ?>
            </ul>
        </div>
        
        <!-- Columna Derecha -->
        <div class="columna-derecha cuadro">
            <!-- Fila Superior: Lista de Envíos -->
            <div class="fila-envios">
                <h3>Envíos Asignados</h3>
                <ul id="lista-envios">
                    <?php if ($nominaRepartidor == 0): ?>
                        <li>Seleccione un repartidor.</li>
                    <?php elseif (!empty($envios)): ?>
                        <?php foreach ($envios as $envio): ?>
                            <li>
                                <strong>Producto:</strong> <?= htmlspecialchars($envio['Producto']) ?><br>
                                <strong>Entrega #:</strong> <?= htmlspecialchars($envio['Entrega']) ?><br>
                                <strong>Orden:</strong> <?= htmlspecialchars($envio['OrdenR']) ?><br>
                                <strong>Cantidad:</strong> <?= htmlspecialchars($envio['Cantidad']) ?><br>
                                <strong>Vehículo:</strong> <?= htmlspecialchars($envio['Vehiculo']) ?><br>
                                <strong>Fecha:</strong> <?= htmlspecialchars($envio['Fecha']) ?><br>
                                <strong>Estado:</strong> <?= htmlspecialchars($envio['Estado']) ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No hay envíos asignados a este repartidor.</li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Fila Inferior: Mapa -->
            <div class="fila-mapa cuadro">
                <h3>Mapa</h3>
                <div id="mapa">
                    <!-- Espacio reservado para el mapa -->
                </div>
            </div>
        </div>
    </div>
</body>
</html>
