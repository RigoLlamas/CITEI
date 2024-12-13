<?php
include '../php/conexion.php';
//include '../algoritmo_de_reparticion/algoritmo_de_reparticion.php';

$config = include '../config.php';
$googleMapsApiKey = $config['api_keys']['google_maps_api_key'];

// Obtén la nómina del repartidor desde la URL o una variable predefinida
$nominaRepartidor = isset($_GET['nomina']) ? intval($_GET['nomina']) : 0;

// Consulta para obtener los repartidores ocupados
$consultaRepartidores = "
    SELECT Nomina, Nombre, Apellidos, Estado, Longitud, Latitud 
    FROM repartidor 
    WHERE Estado = 'Ocupado'
";
$resultadoRepartidores = $conexion->query($consultaRepartidores);

// Consulta para obtener los envíos asignados al repartidor específico
$consultaEnvios = "
    SELECT e.Entrega, e.OrdenR, e.Cantidad, e.Vehiculo, p.Nombre AS Producto, v.Fecha, v.Estado, 
           u.Calle, u.NumInterior, u.NumExterior, u.CP, u.Correo, m.Municipio 
    FROM envios e
    JOIN pedidos v ON e.NumVenta = v.NumVenta
    JOIN producto p ON e.Producto = p.PK_Producto
    JOIN usuarios u ON v.FK_Usuario = u.PK_Usuario
    JOIN municipio m ON u.FK_Municipio = m.PK_Municipio
    WHERE e.Repartidor = 3
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
            const defaultLocation = {
                lat: 20.673290,
                lng: -103.416747
            }; // Coordenadas predeterminadas
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
            const position = {
                lat: parseFloat(lat),
                lng: parseFloat(lng)
            };
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
                            </a><br>
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
            <!-- Fila Superior: Lista de Envíos -->
            <div class="fila-envios">
                <h3>Envíos Asignados</h3>
                <ul id="lista-envios">
                    <?php if ($nominaRepartidor == 0): ?>
                        <li>Seleccione un repartidor.</li>
                    <?php elseif (!empty($envios)): ?>
                        <?php foreach ($envios as $envio): ?>
                            <div class="ruta-info" style="display: flex;">
                                <div>
                                    <p style="text-align: left;">
                                        <strong>Entrega #:</strong> <?= $envio['Entrega'] ?><br>
                                        <strong>Orden:</strong> <?= $envio['OrdenR'] ?><br>
                                    </p>
                                </div>
                                <div>
                                    <p style="text-align: left;">
                                        <strong>Estado:</strong> <?= $envio['Estado'] ?><br>
                                        <strong>Vehículo:</strong> <?= $envio['Vehiculo'] ?><br>
                                    </p>
                                </div>
                                <div>
                                    <p style="text-align: left;">
                                        <strong>Producto:</strong> <?= $envio['Producto'] ?><br>
                                        <strong>Cantidad:</strong> <?= $envio['Cantidad'] ?><br>
                                    </p>
                                </div>

                            </div>
                            <div>
                                <p style="text-align: justify;"><strong>Direccion:</strong> <?= $envio['Calle'] ?> <?= $envio['NumInterior'] ?> <?= $envio['NumExterior'] ?><br>
                                    <strong>Municipio:</strong> <?= $envio['Municipio'] ?><br>
                                    <strong>CP:</strong> <?= $envio['CP'] ?><br>
                                    <strong>Correo:</strong> <?= $envio['Correo'] ?>
                                </p><br>
                            </div>
                            <!-- Botón para retirar este pedido -->
                            <button onclick="retirarPedido(<?= $envio['Entrega'] ?>, <?= $nominaRepartidor ?>)"
                                style="width: auto; display: flex;">Retirar Pedido</button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No hay envíos asignados a este repartidor.</li>
                    <?php endif; ?>
                </ul>
                <!-- Botón para cancelar toda la ruta -->
                <?php if ($nominaRepartidor != 0 && !empty($envios)): ?>
                    <button onclick="cancelarRuta(<?= $nominaRepartidor ?>)">Cancelar Ruta</button>
                <?php endif; ?>
            </div>


            <!-- Fila Inferior: Mapa -->
            <div class="fila-mapa cuadro">
                <div id="mapa" style="width: 100%; height: 100%;">
                    <!-- Google Maps se mostrará aquí -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Retirar un pedido de la ruta
        function retirarPedido(entregaId, nomina) {
            if (confirm("¿Estás seguro de que deseas retirar este pedido de la ruta?")) {
                fetch(`retirar_pedido.php?entrega=${entregaId}&nomina=${nomina}`, {
                        method: 'GET'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert("Pedido retirado con éxito.");
                            location.reload(); // Recargar la página para actualizar la lista
                        } else {
                            alert("Error al retirar el pedido: " + data.error);
                        }
                    })
                    .catch(error => console.error('Error al retirar pedido:', error));
            }
        }

        // Cancelar toda la ruta
        function cancelarRuta(nomina) {
            if (confirm("¿Estás seguro de que deseas cancelar toda la ruta de este repartidor?")) {
                fetch(`cancelar_ruta.php?nomina=${nomina}`, {
                        method: 'GET'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert("Ruta cancelada con éxito.");
                            location.reload(); // Recargar la página para actualizar la lista
                        } else {
                            alert("Error al cancelar la ruta: " + data.error);
                        }
                    })
                    .catch(error => console.error('Error al cancelar ruta:', error));
            }
        }
    </script>

</body>

</html>