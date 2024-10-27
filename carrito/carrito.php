<?php
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


    // Consulta del carrito
    $consultaCarrito = "
        SELECT carrito.Cantidad, producto.Nombre, producto.Precio, producto.Descripcion, producto.PK_Producto
        FROM carrito
        JOIN producto ON carrito.Producto = producto.PK_Producto
        WHERE carrito.Usuario = $usuario_id
    ";
    $resultadoCarrito = $conexion->query($consultaCarrito);

    // Consulta para obtener los datos de envío
    $consultaEnvio = "
        SELECT usuarios.Nombres, usuarios.Apellidos, usuarios.Calle, usuarios.NumInterior, usuarios.NumExterior, usuarios.CP, usuarios.Telefono, municipio.Municipio
        FROM usuarios
        JOIN municipio ON usuarios.FK_Municipio = municipio.PK_Municipio
        WHERE usuarios.PK_Usuario = $usuario_id
    ";
    $resultadoEnvio = $conexion->query($consultaEnvio);

    // Consulta de ofertas activas
    $consultaOfertas = "
    SELECT Oferta, Tipo, Valor, Descripcion 
    FROM ofertas 
    WHERE Estado = 'Activada' AND CURDATE() <= Expiracion
    ";
    $resultadoOfertas = $conexion->query($consultaOfertas);

    // Calcular el total del carrito
    $totalCarrito = 0; // Inicializar el total

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
    <script type="text/javascript"
        src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js">
    </script>
    <script type="text/javascript">
        (function(){
            emailjs.init({
                publicKey: "7gg6Y8y5zhCzIx7qS",
            });
        })();
    </script>


</head>
<body>
    <!-- Contenedor principal del carrito -->
    <div class="contenedor-carrito-envio">
        <div class="contenedor-carrito">
            <h1>CITEI - Carrito de Compras</h1>

            <!-- Listado de productos en el carrito -->
            <div class="lista-productos">
                <?php
                if ($resultadoCarrito->num_rows > 0) {
                    while ($producto = $resultadoCarrito->fetch_assoc()) {
                        // Generar la ruta de la imagen basada en el ID del producto
                        $rutaImagen = '../productos/imagenes_productos/producto_' . $producto['PK_Producto'] . '/1.jpg';
                        echo '<div class="producto_carrito" data-id="' . $producto['PK_Producto'] . '">';
                        echo '<img src="' . $rutaImagen . '" alt="' . $producto['Nombre'] . '">';
                        echo '<div class="info-producto">';
                        echo '<p>' . $producto['Nombre'] . '</p>';
                        echo '<p>Precio: $' . $producto['Precio'] . '</p>';
                        echo '<div class="cantidad">';
                        echo '<button class="decrementar">-</button>';
                        echo '<span>' . $producto['Cantidad'] . '</span>';
                        echo '<button class="incrementar">+</button>';
                        echo '</div>'; // Cierre de cantidad
                        echo '<button class="eliminar-carrito" data-id="' . $producto['PK_Producto'] . '">Eliminar del carrito</button>';
                        echo '</div>'; // Cierre de info-producto
                        echo '</div>'; // Cierre de producto_carrito
                        echo '<hr>';

                        // Calcular el subtotal de este producto
                        $subtotal = $producto['Cantidad'] * $producto['Precio'];
                        $totalCarrito += $subtotal; // Sumar al total del carrito
                    }
                } else {
                    echo "<p>El carrito está vacío.</p>";
                }
                ?>
            </div>

             <!-- Mostrar ofertas debajo de los productos del carrito -->
            <div class="ofertas">
                <h2>Ofertas disponibles</h2>
                <?php
                // Consulta para obtener las ofertas activadas
                $consultaOfertas = "SELECT * FROM ofertas WHERE Estado = 'Activada' AND CURDATE() <= Expiracion";
                $resultadoOfertas = $conexion->query($consultaOfertas);

                if ($resultadoOfertas->num_rows > 0) {
                    while ($oferta = $resultadoOfertas->fetch_assoc()) {
                        echo '<div class="oferta" data-id="' . $oferta['Oferta'] . '">';
                        echo '<p><strong>' . $oferta['Descripcion'] . '</strong></p>';
                        echo '<p>Descuento: ';
                        echo ($oferta['Tipo'] === 'Porcentual') ? $oferta['Valor'] . '% de descuento' : '$' . $oferta['Valor'] . ' de descuento';
                        echo '</p>';
                        echo '<button class="aplicar-oferta" data-id="' . $oferta['Oferta'] . '">Aplicar oferta</button>';
                        echo '</div>';
                        echo '<hr>';
                    }
                } else {
                    echo "<p>No hay ofertas disponibles.</p>";
                }
                ?>
            </div>
        </div>

        <!-- Sección de información de envío -->
        <div class="contenedor-envio">
            <?php
            if ($resultadoEnvio->num_rows > 0) {
                $envio = $resultadoEnvio->fetch_assoc();
                echo "<h2>Datos de Envío</h2>";
                echo "<p><strong>Nombre:</strong> " . $envio['Nombres'] . " " . $envio['Apellidos'] . "</p>";
                echo "<p><strong>Dirección:</strong> " . $envio['Calle'] . " #" . $envio['NumInterior'] . ", " . $envio['NumExterior'] . "</p>";
                echo "<p><strong>Código Postal:</strong> " . $envio['CP'] . " - <strong>Municipio:</strong> " . $envio['Municipio'] . "</p>";
                
                // Mostrar empresa solo si existe
                if (!empty($envio['Empresa'])) {
                    echo "<p><strong>Empresa:</strong> " . $envio['Empresa'] . "</p>";
                }
                
                // Mostrar teléfono
                echo "<p><strong>Teléfono:</strong> " . $envio['Telefono'] . "</p>";
            } else {
                echo "<p>No se encontraron datos de envío.</p>";
            }
            ?>
            <p class='total'><strong>Costo total:</strong> $<?php echo number_format($totalCarrito, 2); ?></p>
            <div id="paypal-button-container"></div>
            
        </div>
    </div>
</body>
<script src="carrito.js"></script> 
</html>


