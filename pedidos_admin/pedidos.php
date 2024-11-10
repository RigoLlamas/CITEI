<?php
// Conexión a la base de datos
include('../php/conexion.php');

// Función para obtener el historial de pedidos por año, mes o día
function obtenerHistorialPedidos($conexion, $filtro = []) {
    $consulta = "SELECT pedidos.NumVenta, pedidos.Fecha, pedidos.Estado, pedidos.Codigo, pedidos.Clave, 
                        usuarios.Correo, usuarios.Nombres, usuarios.Apellidos, usuarios.Calle, usuarios.Telefono, 
                        SUM(detalles.Cantidad * detalles.Precio) AS Total 
                 FROM pedidos
                 JOIN usuarios ON pedidos.FK_Usuario = usuarios.PK_Usuario
                 JOIN detalles ON pedidos.NumVenta = detalles.NumVenta
                 WHERE 1=1";

    // Agregar filtros de año, mes, día si están presentes
    if (!empty($filtro['anio'])) {
        $consulta .= " AND YEAR(pedidos.Fecha) = " . intval($filtro['anio']);
    }
    if (!empty($filtro['mes'])) {
        $consulta .= " AND MONTH(pedidos.Fecha) = " . intval($filtro['mes']);
    }
    if (!empty($filtro['dia'])) {
        $consulta .= " AND DAY(pedidos.Fecha) = " . intval($filtro['dia']);
    }

    $consulta .= " GROUP BY pedidos.NumVenta ORDER BY pedidos.Fecha DESC";

    $resultado = $conexion->query($consulta);
    return $resultado->fetch_all(MYSQLI_ASSOC);
}

// Función para obtener detalles de un pedido específico por código y clave
function obtenerDetallesPedido($conexion, $codigo, $clave) {
    $consulta = "SELECT pedidos.NumVenta, pedidos.Fecha, pedidos.Estado, pedidos.Codigo, pedidos.Clave,
                        usuarios.Correo, usuarios.Nombres, usuarios.Apellidos, usuarios.Calle, usuarios.Telefono,
                        producto.Nombre AS Producto, detalles.Cantidad, detalles.Precio
                 FROM pedidos
                 JOIN usuarios ON pedidos.FK_Usuario = usuarios.PK_Usuario
                 JOIN detalles ON pedidos.NumVenta = detalles.NumVenta
                 JOIN producto ON detalles.Producto = producto.PK_Producto
                 WHERE pedidos.Codigo = ? AND pedidos.Clave = ?";

    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("ss", $codigo, $clave);
    $stmt->execute();
    $resultado = $stmt->get_result();
    return $resultado->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['textcodigo'], $_POST['textclave'])) {
    // Si el administrador ingresó código y clave
    $codigo = $_POST['textcodigo'];
    $clave = $_POST['textclave'];
    $detallesPedido = obtenerDetallesPedido($conexion, $codigo, $clave);
} else {
    // Historial de pedidos por año, mes o día
    $filtro = [
        'anio' => $_GET['anio'] ?? null,
        'mes' => $_GET['mes'] ?? null,
        'dia' => $_GET['dia'] ?? null
    ];
    $historialPedidos = obtenerHistorialPedidos($conexion, $filtro);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Administrador</title>
    <script src="../js/navbar.js"></script>
    <script src="../js/pie.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    
</head>
<body>
    <div class="dos-columnas-envios">
        <!-- Formulario para buscar un pedido específico o filtrar el historial -->
        <div class="cuadro">
            <form action="" method="POST">
                <h2>Buscar Pedido</h2>
                <div>
                    <label for="textcodigo">Ingrese el código del pedido</label>
                    <input class="envios__input" type="text" name="textcodigo" id="textcodigo">
                </div>
                <div>
                    <label for="textclave">Ingrese la clave del pedido</label>
                    <input class="envios__input" type="text" name="textclave" id="textclave">
                </div>
                <button type="submit">Buscar Pedido</button>
            </form>

            <!-- Formulario de filtro por año, mes y día -->
            <form action="" method="GET">
                <h2>Filtrar Historial de Pedidos</h2>
                    <div>
                        <label for="anio">Año<br></label>
                        <select name="anio" id="anio">
                            <option value="">Todos</option>
                            <?php
                            for ($i = date('Y'); $i >= date('Y') - 5; $i--) {
                                echo "<option value=\"$i\">$i</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="mes">Mes<br></label>
                        <select name="mes" id="mes" onchange="actualizarDias()">
                            <option value="">Todos</option>
                            <?php
                            $meses = [
                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 
                                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 
                                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                            ];
                            foreach ($meses as $num => $nombreMes) {
                                echo "<option value=\"$num\">$nombreMes</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="dia">Día<br></label>
                        <select name="dia" id="dia">
                            <option value="">Todos</option>
                        </select>
                    </div>
                <button type="submit">Filtrar</button>
            </form>
        </div>


        <?php if (isset($detallesPedido) && !empty($detallesPedido)): ?>
            <!-- Mostrar detalles de un pedido específico -->
            <div class="cuadro">
                <h2>Detalles del Pedido</h2>
                <p><strong>Código:</strong> <?php echo $detallesPedido[0]['Codigo']; ?></p>
                <p><strong>Clave:</strong> <?php echo $detallesPedido[0]['Clave']; ?></p>
                <p><strong>Fecha:</strong> <?php echo $detallesPedido[0]['Fecha']; ?></p>
                <p><strong>Estado:</strong> <?php echo $detallesPedido[0]['Estado']; ?></p>
                <p><strong>Cliente:</strong> <?php echo $detallesPedido[0]['Nombres'] . ' ' . $detallesPedido[0]['Apellidos']; ?></p>
                <p><strong>Correo:</strong> <?php echo $detallesPedido[0]['Correo']; ?></p>
                <p><strong>Dirección:</strong> <?php echo $detallesPedido[0]['Calle']; ?></p>
                <p><strong>Teléfono:</strong> <?php echo $detallesPedido[0]['Telefono']; ?></p>

                <h3>Productos</h3>
                <?php foreach ($detallesPedido as $detalle): ?>
                    <div class="producto_envios">
                        <p><strong>Producto:</strong> <?php echo $detalle['Producto']; ?></p>
                        <p>Cantidad: <?php echo $detalle['Cantidad']; ?></p>
                        <p>Precio: $<?php echo $detalle['Precio']; ?></p>
                    </div>
                    <hr>
                <?php endforeach; ?>
            </div>

        <?php elseif (!empty($historialPedidos)): ?>
            <!-- Mostrar historial de pedidos -->
            <div class="cuadro">
                <h2>Historial de Pedidos</h2>
                <div class="historial-pedidos-scroll">
                    <?php foreach ($historialPedidos as $pedido): ?>
                        <div class="pedido_historial">
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                                <tr>
                                    <td><strong>NumVenta:</strong></td>
                                    <td><?php echo $pedido['NumVenta']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Fecha:</strong></td>
                                    <td><?php echo $pedido['Fecha']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        <select name="estado_<?php echo $pedido['NumVenta']; ?>" onchange="actualizarEstado(<?php echo $pedido['NumVenta']; ?>, this.value)">
                                            <option value="Pendiente" <?php echo $pedido['Estado'] == 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                            <option value="En almacen" <?php echo $pedido['Estado'] == 'En almacen' ? 'selected' : ''; ?>>En almacen</option>
                                            <option value="Entrega parcial" <?php echo $pedido['Estado'] == 'Entrega parcial' ? 'selected' : ''; ?>>Entrega parcial</option>
                                            <option value="Cancelado" <?php echo $pedido['Estado'] == 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                            <option value="Completado" <?php echo $pedido['Estado'] == 'Completado' ? 'selected' : ''; ?>>Completado</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Total:</strong></td>
                                    <td>$<?php echo $pedido['Total']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Código:</strong></td>
                                    <td><?php echo $pedido['Codigo']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Clave:</strong></td>
                                    <td><?php echo $pedido['Clave']; ?></td>
                                </tr>
                            </table>
                            <hr style="border: 1px solid #ddd;">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <p>No se encontraron pedidos con los filtros aplicados.</p>
        <?php endif; ?>
    </div>
    <script>
        function actualizarEstado(numVenta, nuevoEstado) {
            // Alerta de confirmación
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¿Deseas cambiar el estado del pedido?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cambiar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Si el usuario confirma, realizar la solicitud fetch
                    fetch('actualizar_estado.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ numVenta, nuevoEstado })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Estado actualizado',
                                text: data.message,
                                confirmButtonText: 'OK'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message,
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error en la solicitud',
                            text: 'No se pudo actualizar el estado del pedido.',
                            confirmButtonText: 'OK'
                        });
                    });
                }
            });
        }
    </script>
</body>
</html>
