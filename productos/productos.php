<?php
session_start();
include '../php/conexion.php';

if (isset($_SESSION['id_usuario'])) {
    $isAdmin = $_SESSION['id_usuario'] == '1';
} else {
    $isAdmin = false;
}

$titulo_limite = 40;

// Consultar la cantidad de productos visibles
$sql_cantidad = "SELECT COUNT(*) AS total_productos FROM producto WHERE Visibilidad = 1 AND PK_Producto > 0";
$result_cantidad = $conexion->query($sql_cantidad);
$total_productos = $result_cantidad->fetch_assoc()['total_productos'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Productos</title>
    <script src="../js/pie.js"></script>
    <script src="../js/navbar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <h2 style="text-align: center;">Productos</h2>
    <div class="dos-columnas-productos">
        <div class="cuadro">
            <p>Populares</p>
            <?php
            // Consulta a la base de datos por productos populares
            $sql_populares = "
                        SELECT 
                            p.PK_Producto, 
                            p.Nombre, 
                            p.Precio, 
                            p.Descripcion, 
                            p.Caracteristicas, 
                            SUM(d.Cantidad) AS Ventas
                        FROM 
                            producto p
                        JOIN 
                            detalles d ON p.PK_Producto = d.Producto
                        JOIN 
                            pedidos ped ON d.NumVenta = ped.NumVenta
                        WHERE 
                            p.Visibilidad = 1
                        GROUP BY 
                            p.PK_Producto, 
                            p.Nombre, 
                            p.Precio, 
                            p.Descripcion, 
                            p.Caracteristicas
                        HAVING 
                            Ventas > 10
                        ORDER BY 
                            Ventas DESC
                        LIMIT 3
                        ";
            $populares = $conexion->query($sql_populares);

            // Verificar si hay resultados para productos populares
            if ($populares->num_rows > 0) {
                echo '<div class="contenedor-productos-populares">';
                while ($row = $populares->fetch_assoc()) {
                    // Ruta base de las imágenes
                    $ruta_base_imagenes = 'imagenes_productos/';
                    $ruta_logo = '../img/logo.png'; // Ruta del logo por defecto

                    // Ruta a la carpeta de imágenes del producto
                    $carpeta_imagenes = $ruta_base_imagenes . 'producto_' . $row["PK_Producto"] . '/';

                    // Verificar si existe una imagen con nombres "1.jpg" o "1.png" en la carpeta del producto
                    $ruta_imagen_jpg = $carpeta_imagenes . '1.jpg';
                    $ruta_imagen_png = $carpeta_imagenes . '1.png';

                    if (file_exists($ruta_imagen_jpg)) {
                        $ruta_imagen = $ruta_imagen_jpg;
                    } elseif (file_exists($ruta_imagen_png)) {
                        $ruta_imagen = $ruta_imagen_png;
                    } else {
                        // Si no existe ninguna de las imágenes, usar el logo por defecto
                        $ruta_imagen = $ruta_logo;
                        $imagenes_json = json_encode([$ruta_logo]); // Pasamos el logo como única imagen en el array JSON
                    }

                    // Si existen imágenes en la carpeta del producto, las obtenemos con glob()
                    if (is_dir($carpeta_imagenes)) {
                        $imagenes_producto = glob($carpeta_imagenes . '*.{jpg,jpeg,png}', GLOB_BRACE);
                        $imagenes_json = json_encode($imagenes_producto); // Convertimos las rutas de las imágenes en un array JSON
                    } else {
                        $imagenes_json = json_encode([$ruta_logo]); // Si no hay carpeta o imágenes, usamos el logo como única imagen
                    }

                    $titulo_corto = (mb_strlen($row["Nombre"]) > $titulo_limite)
                        ? mb_substr($row["Nombre"], 0, $titulo_limite) . "..."
                        : $row["Nombre"];

                    echo '<div class="producto" 
                                    data-id="' . $row["PK_Producto"] . '" 
                                    data-descripcion="' . $row["Descripcion"] . '" 
                                    data-nombre="' . strtolower($row["Nombre"]) . '" 
                                    data-precio="' . $row["Precio"] . '" 
                                    data-caracteristicas="' . $row["Caracteristicas"] . '" 
                                    data-imgs=\'' . $imagenes_json . '\'>';

                    // Mostramos la imagen del producto en la vista general (solo la primera o el logo)
                    echo '<img src="' . $ruta_imagen . '" alt="' . $row["Nombre"] . '" style="max-width: 100%; height: auto;">';

                    // Mostramos los datos visibles del producto
                    echo '<div class="texto_productos"> 
                                    <p>' . $titulo_corto . '</p>
                                    <p>$' . $row["Precio"] . '</p>
                                </div> 
                                </div>';
                }
                echo '</div>';
            } else {
                echo "No hay productos populares disponibles.";
            }
            ?>
        </div>
        <div class="cuadro">
            <!-- Campo de búsqueda -->
            <div style="text-align: center; margin-bottom: 2rem; display: flex; flex-direction: row;">
                <input type="text" id="buscador" placeholder="Buscar producto por nombre" style="padding: 10px; width: 70%; "
                    maxlength="150">
                <?php
                if ($isAdmin) {
                    if ($total_productos <= 60) {
                        echo '<button id="boton-carrito" style="margin-left: 10%; width: 20%;">
                            Agregar Producto' . $total_productos . '
                        </button>';
                    }
                } else {
                    if (isset($_SESSION['id_usuario'])) {
                        echo '<button id="boton-carrito" style="margin-left: 10%; width: 20%;" onclick="redirigirAlCarrito()">
                                Ir al Carrito
                            </button>';
                    }
                }
                ?>
            </div>
            <?php
            // Ruta base de las imágenes
            $ruta_base_imagenes = 'imagenes_productos/';
            $ruta_logo = '../img/logo.png';

            // Consulta a la base de datos para obtener los productos
            $sql = "SELECT * FROM producto WHERE Visibilidad = 1 AND PK_Producto > 0";
            $productos = $conexion->query($sql);

            // Verificar si hay resultados
            if ($productos->num_rows > 0) {
                echo '<div class="contenedor-productos" id="contenedor-productos">'; // Añadir el ID para usar en JavaScript
                while ($row = $productos->fetch_assoc()) {
                    // Ruta a la carpeta de imágenes del producto
                    $carpeta_imagenes = $ruta_base_imagenes . 'producto_' . $row["PK_Producto"] . '/';

                    $ruta_imagen = $ruta_logo; // Por defecto, usamos el logo

                    // Verificar si existe alguna imagen válida en los formatos soportados
                    if (is_dir($carpeta_imagenes)) {
                        $imagenes_producto = glob($carpeta_imagenes . '*.{jpg,jpeg,png}', GLOB_BRACE);
                        if (!empty($imagenes_producto)) {
                            // Si se encontraron imágenes, usar la primera en la lista
                            $ruta_imagen = $imagenes_producto[0];
                        }
                    }

                    $titulo_corto = (mb_strlen($row["Nombre"]) > $titulo_limite)
                        ? mb_substr($row["Nombre"], 0, $titulo_limite) . "..."
                        : $row["Nombre"];

                    // Convertimos las rutas de las imágenes en un array JSON
                    $imagenes_json = json_encode($imagenes_producto ?: [$ruta_logo]);

                    // Aquí agregamos los data-* para que el JavaScript pueda acceder a estos datos, incluyendo el nombre en minúsculas para el filtro
                    echo '<div class="producto" 
                    data-id="' . $row["PK_Producto"] . '" 
                    data-nombre="' . strtolower($row["Nombre"]) . '" 
                    data-descripcion="' . $row["Descripcion"] . '" 
                    data-precio="' . $row["Precio"] . '" 
                    data-caracteristicas="' . $row["Caracteristicas"] . '" 
                    data-imgs=\'' . $imagenes_json . '\'>';

                    // Mostramos la imagen del producto en la vista general (solo la primera o el logo)
                    echo '<img src="' . $ruta_imagen . '" alt="' . $row["Nombre"] . '" style="max-width: 100%; height: auto;">';

                    // Mostramos los datos visibles del producto
                    echo '<div class="texto_productos"> 
                    <p>' . $titulo_corto . '</p>
                    <p>$' . $row["Precio"] . '</p>
                </div> 
                </div>';
                }
                echo '</div>';
            } else {
                echo "No hay productos disponibles.";
            }
            // Cerramos la conexión a la base de datos
            $conexion->close();
            ?>
        </div>
    </div>
    <script src="productos.js" defer></script>
</body>

</html>