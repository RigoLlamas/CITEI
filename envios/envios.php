<?php
// Conexión a la base de datos
include('../php/conexion.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $codigo = $_POST['textcodigo'];
    $clave = $_POST['textclave'];

    // Consulta para obtener el pedido
    $query = "SELECT pedidos.NumVenta, pedidos.Fecha, pedidos.Estado, pedidos.FK_Usuario, usuarios.Nombres, usuarios.Apellidos, usuarios.Calle, usuarios.Telefono
              FROM pedidos
              JOIN usuarios ON pedidos.FK_Usuario = usuarios.PK_Usuario
              WHERE pedidos.Codigo = ? AND pedidos.Clave = ?";

    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ss", $codigo, $clave); // Evitar inyecciones SQL
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $pedido = $result->fetch_assoc();

        // Obtener los detalles del pedido
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
        } else {
            echo "No se encontraron detalles para este pedido.";
        }

        if (empty($detallesPedido)) {
            echo "No se encontraron detalles para el pedido.";
        }
    } else {
        echo "No se encontró el pedido con el código y clave proporcionados.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>CITEI - Envios</title>
        <script src="../js/navbar.js"></script>
        <script src="../js/pie.js"></script>
    </head>

    <body>
        <div class="dos-columnas-envios">
            <div class="cuadro">
                <form action="" method="POST">
                    <h2>Consultar Envio</h2>
                    <div>
                        <label for="textcodigo">Ingrese el código de su pedido</label>
                        <input class="envios__input" type="text" name="textcodigo" id="textcodigo" required>
                    </div>
                    <div>
                        <label for="textclave">Ingrese la clave de su pedido</label>
                        <input class="envios__input" type="text" name="textclave" id="textclave" required>
                    </div>
                    <button type="submit">Buscar Pedido</button>
                </form>
            </div>
            <?php if (isset($pedido)) { ?>
            <div class="cuadro">
                <h2>Datos del Usuario</h2>
                <div class="dos-columnas">
                    <div>
                        <p><strong>Nombre:</strong> <?php echo $pedido['Nombres'] . ' ' . $pedido['Apellidos']; ?></p>
                        <p><strong>Dirección:</strong> <?php echo $pedido['Calle']; ?></p>
                        <p><strong>Teléfono:</strong> <?php echo $pedido['Telefono']; ?></p>
                    </div>
                    <div>
                    <p><strong>Numero del Pedido:</strong><?php echo $pedido['NumVenta']; ?></p>
                        <p><strong>Estado del Pedido:</strong> <?php echo $pedido['Estado']; ?></p>
                        <p><strong>Fecha del Pedido:</strong> <?php echo $pedido['Fecha']; ?></p>
                    </div>
                </div> 
                    <div class="cuadro ">
                    <?php foreach ($detallesPedido as $detalle): ?>
                        <?php 
                            $rutaImagen = '../productos/imagenes_productos/producto_' . $detalle['PK_Producto'] . '/1.jpg'; 
                        ?>
                        <div class="producto_envios" data-id="<?php echo $detalle['Producto']; ?>">
                            <img src="<?php echo $rutaImagen; ?>" alt="Producto <?php echo $detalle['Producto']; ?>">
                            <div>
                                <p>Producto <?php echo $detalle['Producto']; ?></p>
                                <p>Precio: $<?php echo $detalle['Precio']; ?></p>
                            </div>
                        <hr>
                    <?php endforeach; ?>         
                <?php } ?>
            </div>
    </body>
</html>