<?php
// Habilitar reporte de errores (solo para desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Conexión a la base de datos
include '../php/conexion.php';
session_start();

// Verificar si el usuario está autenticado
if (isset($_SESSION['id_usuario'])) {
    $usuario_id = $_SESSION['id_usuario'];
} else {
    header('Location: ../login/login.html');
    exit();
}

// Inicializar 'descuento' y 'id_oferta_aplicada' en la sesión si no están definidos
if (!isset($_SESSION['descuento'])) {
    $_SESSION['descuento'] = 0;
}
$descuento = floatval($_SESSION['descuento']);

// Consulta del carrito usando sentencias preparadas
$consultaCarrito = "
    SELECT carrito.Cantidad, producto.Nombre, producto.Precio, producto.Descripcion, producto.PK_Producto
    FROM carrito
    JOIN producto ON carrito.Producto = producto.PK_Producto
    WHERE carrito.Usuario = ?
";
$stmtCarrito = $conexion->prepare($consultaCarrito);
if (!$stmtCarrito) {
    die("Error en la preparación de la consulta del carrito: " . $conexion->error);
}
$stmtCarrito->bind_param('i', $usuario_id);
$stmtCarrito->execute();
$resultadoCarrito = $stmtCarrito->get_result();

// Almacenar los resultados en un arreglo
$productosCarrito = [];
$totalCarrito = 0;

if ($resultadoCarrito && $resultadoCarrito->num_rows > 0) {
    while ($producto = $resultadoCarrito->fetch_assoc()) {
        $productosCarrito[] = $producto;
        // Calcular el total directamente al cargar los productos
        $totalCarrito += floatval($producto['Cantidad']) * floatval($producto['Precio']);
    }
}

// Consulta para obtener los datos de envío usando sentencias preparadas
$consultaEnvio = "
    SELECT usuarios.Nombres, usuarios.Apellidos, usuarios.Calle, usuarios.NumInterior, usuarios.NumExterior, usuarios.CP, usuarios.Telefono, municipio.Municipio
    FROM usuarios
    JOIN municipio ON usuarios.FK_Municipio = municipio.PK_Municipio
    WHERE usuarios.PK_Usuario = ?
";
$stmtEnvio = $conexion->prepare($consultaEnvio);
if (!$stmtEnvio) {
    die("Error en la preparación de la consulta de envío: " . $conexion->error);
}
$stmtEnvio->bind_param('i', $usuario_id);
$stmtEnvio->execute();
$resultadoEnvio = $stmtEnvio->get_result();

// Consulta de promociones activas usando sentencias preparadas
$consultaOfertas = "
    SELECT ao.Oferta, o.Tipo, o.Valor, o.Descripcion, o.Despliegue, o.Producto, c.LimiteTiempo AS Expiracion, ao.EstadoUso, ao.FechaUso
    FROM asignacion_ofertas ao
    JOIN ofertas o ON ao.Oferta = o.Oferta
    LEFT JOIN condiciones c ON o.Condicion = c.Condicion
    WHERE ao.Usuario = ? 
      AND o.Estado = 'Activada' 
      AND (c.LimiteTiempo IS NULL OR CURDATE() <= c.LimiteTiempo)
";
$stmtOfertas = $conexion->prepare($consultaOfertas);
if (!$stmtOfertas) {
    die("Error en la preparación de la consulta de ofertas: " . $conexion->error);
}
$stmtOfertas->bind_param('i', $usuario_id);
$stmtOfertas->execute();
$resultadoOfertas = $stmtOfertas->get_result();

// Calcular el total final
$totalCarritoFinal = $totalCarrito - $descuento;

// Asegurar que el total final no sea negativo
if ($totalCarritoFinal < 0) {
    $totalCarritoFinal = 0;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de compras</title>
    <script src="../js/pie.js"></script>
    <script src="../js/navbar.js"></script>
    <script src="https://www.paypal.com/sdk/js?client-id=AaoThmU6Z2xJw6dIYXzMf4y9zVZfJG70_Juv4FIog_hrVbLxVex50GZbW3qh3mXkg7yFKily5W9DIPKC&currency=MXN&intent=capture"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Incluye tu archivo JavaScript principal -->
    <script src="carrito.js"></script>
</head>

<body>
    <!-- Contenedor principal del carrito -->
    <div class="contenedor-carrito-envio">
        <div class="contenedor-carrito">
            <h1>CITEI - Carrito de Compras</h1>

            <!-- Listado de productos en el carrito -->
            <div class="lista-productos">
                <?php if (!empty($productosCarrito)) { ?>
                    <?php foreach ($productosCarrito as $producto) {
                        $rutaImagen = '../productos/imagenes_productos/producto_' . $producto['PK_Producto'] . '/1.jpg';
                        if (!file_exists($rutaImagen)) {
                            $rutaImagen = '../img/logo.png';
                        } ?>
                        <div class="producto_carrito" data-id="<?= htmlspecialchars($producto['PK_Producto']) ?>">
                            <img src="<?= htmlspecialchars($rutaImagen) ?>" alt="<?= htmlspecialchars($producto['Nombre']) ?>">
                            <div class="info-producto">
                                <p><?= htmlspecialchars($producto['Nombre']) ?></p>
                                <p>Precio: $<?= number_format(floatval($producto['Precio']), 2) ?></p>
                                <div class="cantidad">
                                    <button class="decrementar">-</button>
                                    <span><?= intval($producto['Cantidad']) ?></span>
                                    <button class="incrementar">+</button>
                                </div>
                                <button class="eliminar-carrito" data-id="<?= htmlspecialchars($producto['PK_Producto']) ?>">Eliminar del carrito</button>
                            </div>
                        </div>
                        <hr>
                    <?php } ?>
                <?php } else { ?>
                    <p>El carrito está vacío.</p>
                <?php } ?>
            </div>

            <!-- Mostrar ofertas debajo de los productos del carrito -->
            <?php if (!isset($_SESSION['id_oferta_aplicada'])) { ?>
                <div class="ofertas">
                    <h2>Ofertas disponibles</h2>
                    <?php
                    if ($resultadoOfertas && $resultadoOfertas->num_rows > 0) {
                        while ($oferta = $resultadoOfertas->fetch_assoc()) {
                            $productoOferta = intval($oferta['Producto']);
                            // Si es oferta global (productoOferta == 0) o el producto está en el carrito
                            if ($productoOferta === 0 || in_array($productoOferta, array_column($productosCarrito, 'PK_Producto'))) { ?>
                                <div class="oferta" data-id="<?= htmlspecialchars($oferta['Oferta']) ?>">
                                    <p><strong><?= htmlspecialchars($oferta['Descripcion']) ?></strong></p>
                                    <p>Descuento: <?= ($oferta['Tipo'] === 'Porcentual') ? htmlspecialchars($oferta['Valor']) . '% de descuento' : '$' . htmlspecialchars($oferta['Valor']) . ' de descuento' ?></p>
                                    <p>Válido hasta: <?= !empty($oferta['Expiracion']) ? htmlspecialchars($oferta['Expiracion']) : 'Hasta agotar existencias' ?></p>

                                    <?php if ($oferta['EstadoUso'] === 'Solicitada') { ?>
                                        <button class="desactivar-oferta" data-id="<?= htmlspecialchars($oferta['Oferta']) ?>">Cancelar oferta</button>
                                    <?php } else { ?>
                                        <button class="aplicar-oferta" data-id="<?= htmlspecialchars($oferta['Oferta']) ?>">Aplicar oferta</button>
                                    <?php } ?>
                                </div>
                                <hr>
                    <?php }
                        }
                    } else {
                        echo "<p>No hay ofertas disponibles.</p>";
                    }
                    ?>
                </div>
            <?php } else { ?>
                <div class="ofertas">
                    <h2>Oferta Aplicada</h2>
                    <?php
                    // Obtener detalles de la oferta aplicada
                    $idOfertaAplicada = $_SESSION['id_oferta_aplicada'];
                    $consultaOfertaAplicada = "
                        SELECT o.Descripcion, o.Tipo, o.Valor
                        FROM ofertas o
                        WHERE o.Oferta = ?
                    ";
                    $stmtOfertaAplicada = $conexion->prepare($consultaOfertaAplicada);
                    if ($stmtOfertaAplicada) {
                        $stmtOfertaAplicada->bind_param('i', $idOfertaAplicada);
                        $stmtOfertaAplicada->execute();
                        $resultadoOfertaAplicada = $stmtOfertaAplicada->get_result();
                        if ($resultadoOfertaAplicada && $resultadoOfertaAplicada->num_rows > 0) {
                            $ofertaAplicada = $resultadoOfertaAplicada->fetch_assoc();
                            ?>
                            <div class="oferta-aplicada">
                                <p><strong>Descripción:</strong> <?= htmlspecialchars($ofertaAplicada['Descripcion']) ?></p>
                                <p><strong>Tipo:</strong> <?= htmlspecialchars($ofertaAplicada['Tipo']) ?></p>
                                <p><strong>Valor:</strong> <?= ($ofertaAplicada['Tipo'] === 'Porcentual') ? htmlspecialchars($ofertaAplicada['Valor']) . '% de descuento' : '$' . htmlspecialchars($ofertaAplicada['Valor']) . ' de descuento' ?></p>
                                <button class="desactivar-oferta" data-id="<?= htmlspecialchars($idOfertaAplicada) ?>">Cancelar oferta</button>
                            </div>
                    <?php
                        }
                        $stmtOfertaAplicada->close();
                    }
                    ?>
                </div>
            <?php } ?>
        </div>

        <!-- Sección de información de envío y resumen del carrito -->
        <div class="contenedor-envio">
            <?php if ($resultadoEnvio && $resultadoEnvio->num_rows > 0) {
                $envio = $resultadoEnvio->fetch_assoc(); ?>
                <h2>Datos de Envío</h2>
                <p><strong>Nombre:</strong> <?= htmlspecialchars($envio['Nombres'] . ' ' . $envio['Apellidos']) ?></p>
                <p><strong>Dirección:</strong> <?= htmlspecialchars($envio['Calle'] . ' #' . $envio['NumInterior'] . ', ' . $envio['NumExterior']) ?></p>
                <p><strong>Código Postal:</strong> <?= htmlspecialchars($envio['CP'] . ' - Municipio: ' . $envio['Municipio']) ?></p>
                <p><strong>Teléfono:</strong> <?= htmlspecialchars($envio['Telefono']) ?></p>
            <?php } else { ?>
                <p>No se encontraron datos de envío.</p>
            <?php } ?>
            <!-- Mostrar siempre el total y el descuento -->
            <p class='total'><strong>Costo total:</strong> $<?= number_format($totalCarritoFinal, 2) ?></p>
            <p class='descuento'><strong>Descuento aplicado:</strong> $<?= $descuento > 0 ? number_format($descuento, 2) : '0.00' ?></p>
            
            <!-- Contenedor del botón de PayPal -->
            <div id="paypal-button-container"></div>
        </div>

    </div>
</body>

</html>

<script>
    document.querySelectorAll('.aplicar-oferta').forEach((button) => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Evitar comportamiento predeterminado

            const ofertaId = this.getAttribute('data-id');

            Swal.fire({
                title: '¿Aplicar esta oferta?',
                text: "¿Seguro que desea aplicar la oferta?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, aplicar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                // Esperar confirmación antes de proceder
                if (result.isConfirmed) {
                    // Enviar la oferta al servidor para aplicarla
                    fetch('aplicar_oferta.php', {
                            method: 'POST',
                            body: new URLSearchParams({
                                ofertaId
                            }),
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            }
                        })
                        .then(response => response.json())
                        .then(json => {
                            if (json.error) {
                                Swal.fire('Error', json.error, 'error');
                            } else {
                                Swal.fire('Éxito', json.success + ' Total: $' + json.totalConDescuento, 'success')
                                .then(() => {
                                    location.reload();
                                });;
                            }
                        })
                        .catch(error => {
                            console.error('Error al procesar la oferta:', error);
                            Swal.fire('Error', 'Ocurrió un problema al aplicar la oferta.', 'error');
                        });
                }
            });
        });
    });

    document.querySelectorAll('.desactivar-oferta').forEach(button => {
        button.addEventListener('click', function() {
            const ofertaId = this.dataset.id;

            Swal.fire({
                title: '¿Cancelar la oferta?',
                text: 'Esta acción desactivará la oferta aplicada.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, cancelar',
                cancelButtonText: 'No'
            }).then(result => {
                if (result.isConfirmed) {
                    fetch('desactivar_oferta.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                ofertaId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Oferta cancelada',
                                    text: data.message,
                                    icon: 'success'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error',
                                    text: data.message,
                                    icon: 'error'
                                });
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                title: 'Error',
                                text: 'Ocurrió un error al cancelar la oferta.',
                                icon: 'error'
                            });
                        });
                }
            });
        });
    });
</script>

