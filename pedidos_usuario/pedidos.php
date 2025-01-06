<?php
// Conexión a la base de datos
include('../php/conexion.php');
session_start();

// Verificar si el usuario está logueado
if (isset($_SESSION['id_usuario'])) {
    $usuario_id = $_SESSION['id_usuario'];
} else {
    header('Location: ../login/login.html');
    exit();
}

// Consulta para obtener los pedidos del usuario logueado junto con Clave y Codigo
$query = "SELECT pedidos.NumVenta, pedidos.Fecha, pedidos.Estado, pedidos.Clave, pedidos.Codigo
          FROM pedidos
          WHERE pedidos.FK_Usuario = ?
          ORDER BY pedidos.Fecha ASC";

$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$pedidos = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = $row;
    }
} else {
    echo "No se encontraron pedidos para este usuario.";
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CITEI - Pedidos</title>
        <script src="../js/navbar.js"></script>
        <script src="../js/pie.js"></script>
    </head>

    <body>
        <div class="dos-columnas-pedidos">
            <?php if (!empty($pedidos)) { ?>
            <?php foreach ($pedidos as $pedido): ?>
                <div class="cuadro">
                    <h2>Pedido Número: <?php echo $pedido['NumVenta']; ?></h2>
                    <table>
                        <tr>
                            <td><strong>Fecha del Pedido:</strong></td>
                            <td><?php echo $pedido['Fecha']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Estado del Pedido:</strong></td>
                            <td><?php echo $pedido['Estado']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Clave del Pedido:</strong></td>
                            <td><?php echo $pedido['Clave']; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Código del Pedido:</strong></td>
                            <td><?php echo $pedido['Codigo']; ?></td>
                        </tr>
                    </table>
                    
                    <?php
                        // Obtener los detalles del pedido actual
                        $numVenta = $pedido['NumVenta'];
                        $queryDetalles = "SELECT producto.PK_Producto, producto.Nombre AS Producto, detalles.Cantidad, detalles.Precio
                                        FROM detalles
                                        JOIN producto ON detalles.Producto = producto.PK_Producto
                                        WHERE detalles.NumVenta = ?";

                        $stmtDetalles = $conexion->prepare($queryDetalles);
                        $stmtDetalles->bind_param("i", $numVenta);
                        $stmtDetalles->execute();
                        $resultDetalles = $stmtDetalles->get_result();
                        $detallesPedido = [];

                        if ($resultDetalles->num_rows > 0) {
                            while ($row = $resultDetalles->fetch_assoc()) {
                                $detallesPedido[] = $row;
                            }
                        }
                    ?>

                    <?php if (!empty($detallesPedido)) { ?>
                    <div class="cuadro producto_pedidos">
                        <?php foreach ($detallesPedido as $detalle): ?>
                            <?php 
                                $rutaImagen = '../productos/imagenes_productos/producto_' . $detalle['PK_Producto'] . '/1.jpg'; 
                            ?>
                            <div>
                                <img src="<?php echo $rutaImagen; ?>" alt="Producto <?php echo $detalle['Producto']; ?>">
                                <div>
                                    <p><?php echo $detalle['Producto']; ?></p>
                                    <p>Precio: $<?php echo $detalle['Precio']; ?></p>
                                    <p>Cantidad: <?php echo $detalle['Cantidad']; ?></p>
                                </div>
                            </div>
                            <hr>
                        <?php endforeach; ?>
                    </div>
                    <?php } else { ?>
                        <p>No se encontraron detalles para este pedido.</p>
                    <?php } ?>
                </div>
            <?php endforeach; ?>
            <?php } else { ?>
                <p>No se encontraron pedidos para este usuario.</p>
            <?php } ?>
        </div>
    </body>
</html>
