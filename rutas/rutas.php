<?php
include '../php/conexion.php';
include '../php/solo_admins.php';

$config = include '../config.php';
$googleMapsApiKey = $config['api_keys']['google_maps_api_key'];

// Obtén la nómina del repartidor desde la URL en caso de existir
$nominaRepartidor = isset($_GET['nomina']) ? intval($_GET['nomina']) : 0;

$consultaRepartidores = "
    SELECT Nomina, Nombre, Apellidos, Estado, Longitud, Latitud 
    FROM repartidor 
    WHERE Estado = 'Ocupado'
";
$resultadoRepartidores = $conexion->query($consultaRepartidores);

// Consulta para obtener los envíos asignados al repartidor específico
$consultaEnvios = $conexion->prepare("
    SELECT e.Entrega, e.OrdenR, e.Cantidad, e.Vehiculo, p.Nombre AS Producto, 
           v.Fecha, v.Estado, u.Calle, u.NumInterior, u.NumExterior, u.CP, 
           u.Correo, u.Telefono, m.Municipio, u.Latitud, u.Longitud 
    FROM envios e
    JOIN pedidos v ON e.NumVenta = v.NumVenta
    JOIN producto p ON e.Producto = p.PK_Producto
    JOIN usuarios u ON v.FK_Usuario = u.PK_Usuario
    JOIN municipio m ON u.FK_Municipio = m.PK_Municipio
    WHERE e.Repartidor = ?
");

$consultaEnvios->bind_param("i", $nominaRepartidor);
$consultaEnvios->execute();
$resultadoEnvios = $consultaEnvios->get_result();

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

// Devuelve los datos como JSON si se solicita
if (isset($_GET['json']) && $_GET['json'] == 1) {
    header('Content-Type: application/json');
    echo json_encode([
        'repartidor' => $repartidores,
        'envios'     => $envios
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Rutas</title>
    <script src="../js/pie.js" defer></script>
    <script src="../js/navbar.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="rutas.js" defer></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo $googleMapsApiKey; ?>&callback=initMap" async defer></script>
    <script>
        let map;
        let defaultMarker;
        let intervalId;
        let markers = [];

        const urlParams = new URLSearchParams(window.location.search);
        let nomina = urlParams.get('nomina');

        let currentLat = null;
        let currentLng = null;

        // Llamada inicial al cargar el repartidor seleccionado
        document.addEventListener("DOMContentLoaded", () => {

            if (nomina) {
                startUpdatingCoordinates(nomina, 10000);
            }
        });

        // Inicializa el mapa con un marcador en las coordenadas predeterminadas
        function initMap() {
            const defaultLocation = {
                lat: 20.673290,
                lng: -103.416747
            };

            map = new google.maps.Map(document.getElementById("mapa"), {
                center: defaultLocation,
                zoom: 14,
            });

            // Crear un marcador en la ubicación predeterminada
            defaultMarker = new google.maps.Marker({
                position: defaultLocation,
                map: map,
                title: "CITEI" // Título del marcador
            });

            if (nomina) {
                loadMarkers();
            }
        }

        function loadMarkers() {

            if (!nomina) {
                return;
            }

            fetch(`rutas.php?nomina=${nomina}&json=1`) // Obtén los datos en JSON
                .then((response) => response.json())
                .then((data) => {
                    clearMarkers(); // Limpia marcadores existentes

                    // Agregar marcador para la ubicación del repartidor
                    if (data.repartidor) {
                        const repartidorMarker = new google.maps.Marker({
                            position: {
                                lat: parseFloat(data.repartidor.Latitud),
                                lng: parseFloat(data.repartidor.Longitud),
                            },
                            map: map,
                            icon: {
                                url: "https://maps.google.com/mapfiles/kml/shapes/man.png",
                                scaledSize: new google.maps.Size(40, 40),
                            },
                            title: "Ubicación actual del repartidor",
                        });
                        markers.push(repartidorMarker);
                    }

                    // Agregar marcadores para los envíos
                    if (data.envios && Array.isArray(data.envios)) {
                        data.envios.forEach((envio) => {
                            if (envio.Latitud && envio.Longitud) {
                                const marker = new google.maps.Marker({
                                    position: {
                                        lat: parseFloat(envio.Latitud),
                                        lng: parseFloat(envio.Longitud),
                                    },
                                    map: map,
                                    title: `Pedido: ${envio.Producto}\nCliente: ${envio.Correo}`,
                                });

                                // InfoWindow para cada marcador
                                const infoWindow = new google.maps.InfoWindow({
                                    content: `
                            <div>
                                <p><strong>Producto:</strong> ${envio.Producto}</p>
                                <p><strong>Cliente:</strong> ${envio.Correo}</p>
                                <p><strong>Dirección:</strong> ${envio.Calle} ${envio.NumInterior}, ${envio.Municipio}</p>
                                <p><strong>Teléfono:</strong> ${envio.Telefono}</p>
                            </div>
                        `,
                                });

                                marker.addListener("click", () => {
                                    infoWindow.open(map, marker);
                                });

                                markers.push(marker);
                            }
                        });
                    }

                    // Centrar el mapa en la ubicación del repartidor
                    if (data.repartidor) {
                        map.setCenter({
                            lat: parseFloat(data.repartidor.Latitud),
                            lng: parseFloat(data.repartidor.Longitud),
                        });
                    } else if (data.envios && data.envios.length > 0) {
                        const firstEnvio = data.envios[0];
                        map.setCenter({
                            lat: parseFloat(firstEnvio.Latitud),
                            lng: parseFloat(firstEnvio.Longitud),
                        });
                    }
                })
                .catch((error) => console.error("Error al cargar los datos:", error));
        }        
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
            <button style="margin-top: 5rem; width: auto;" onclick="ejecutarPHP()">Recalcular ruta</button>
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
                                    <strong>Correo:</strong> <?= $envio['Correo'] ?><br>
                                    <strong>Telefono:</strong> <?= $envio['Telefono'] ?>
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
</body>

</html>