<?php
include '../php/conexion.php';
$config = include '../config.php';
$googleMapsApiKey = $config['api_keys']['google_maps_api_key'];

// Obtén la nómina del repartidor desde la URL o una variable predefinida
$nominaRepartidor = isset($_GET['nomina']) ? intval($_GET['nomina']) : 0;

// Consulta para obtener los repartidores ocupados
$consultaRepartidores = "
    SELECT Nomina, Nombre, Apellidos, Estado, 
           ST_X(Ubicacion) AS Longitud, 
           ST_Y(Ubicacion) AS Latitud 
    FROM repartidor 
    WHERE Estado = 'Ocupado'
";
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
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $googleMapsApiKey; ?>&callback=initMap" async defer></script>
    <script>
        let map;
        let marker;
        let intervalId;

        // Inicializa el mapa con un marcador en las coordenadas predeterminadas
        function initMap() {
            const defaultLocation = { lat: 20.673290, lng: -103.416747 }; // Coordenadas predeterminadas
            map = new google.maps.Map(document.getElementById("mapa"), {
                center: defaultLocation,
                zoom: 16,
            });

            // Crear un marcador en la ubicación predeterminada
            marker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                title: "Mi ubicación inicial" // Título del marcador
            });
        }

        // Función para actualizar la ubicación del marcador en el mapa
        function updateMarker(lat, lng, title) {
            const position = { lat: parseFloat(lat), lng: parseFloat(lng) };
            marker.setPosition(position);
            marker.setTitle(title);
            map.setCenter(position);
            map.setZoom(16);
        }

        // Función para obtener las coordenadas actualizadas del repartidor
        function fetchUpdatedCoordinates(nomina) {
            fetch(`actualizar_coordenadas.php?nomina=${nomina}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.error) {
                        console.log(`Coordenadas actuales del repartidor ${nomina}: Latitud: ${data.Latitud}, Longitud: ${data.Longitud}`);
                        updateMarker(data.Latitud, data.Longitud, `Repartidor ${nomina}`);
                    } else {
                        console.error(data.error);
                    }
                })
                .catch(error => console.error('Error al obtener coordenadas:', error));
        }

        // Configura el intervalo para actualizar la ubicación cada X tiempo
        function startUpdatingCoordinates(nomina, interval = 5000) {
            if (intervalId) clearInterval(intervalId);
            intervalId = setInterval(() => fetchUpdatedCoordinates(nomina), interval);
        }

        // Detiene la actualización de coordenadas
        function stopUpdatingCoordinates() {
            if (intervalId) clearInterval(intervalId);
        }

        // Llamada inicial al cargar el repartidor seleccionado
        document.addEventListener("DOMContentLoaded", () => {
            const urlParams = new URLSearchParams(window.location.search);
            const nomina = urlParams.get('nomina');
            if (nomina) {
                startUpdatingCoordinates(nomina);
            }
        });
    </script>
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
                <div id="mapa" style="width: 100%; height: 100%;">
                    <!-- Google Maps se mostrará aquí -->
                </div>
            </div>
        </div>
    </div>
</body>
</html>
