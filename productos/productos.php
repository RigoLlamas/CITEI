<?php
session_start();

if (isset($_SESSION['id_usuario'])) {
    $isAdmin = $_SESSION['id_usuario'] == '1';
} else {
    $isAdmin = false;
}
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
                /*
                // Consulta a la base de datos por popular
                $sql = "SELECT PK_Producto, Nombre, Precio, Ventas FROM Producto WHERE Ventas > 10 ORDER BY Ventas DESC LIMIT 3";
                $populares = $conexion->query($sql);

                // Verificar si hay resultados
                if ($populares->num_rows > 0) {
                    echo '<div class="contenedor-productos">';
                    while($row = $populares->fetch_assoc()) {
                        echo '<div class="producto">';
                        echo '<p>' . $row["Nombre"] . '</p>';
                        echo '<p>$' . $row["Precio"] . '</p>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo "No hay productos disponibles.";
                }

                $conexion->close();
                */
                ?>
            </div>
            <div class="cuadro">
                <!-- Campo de búsqueda -->
                <div style="text-align: center; margin-bottom: 2rem; display: flex; flex-direction: row;">
                    <input type="text" id="buscador" placeholder="Buscar producto por nombre" style="padding: 10px; width: 70%; "
                    maxlength="150">
                    <?php 
                    if($isAdmin){
                        echo '<button id="boton-carrito" style="margin-left: 10%; width: 20%;">
                                    Agregar Producto
                                </button>';    
                    } else{
                        if (isset($_SESSION['id_usuario'])) {
                            echo '<button id="boton-carrito" style="margin-left: 10%; width: 20%;" onclick="redirigirAlCarrito()">
                                Ir al Carrito
                            </button>';
                        }
                    }
                    ?>
                    
                </div>

            <?php
                // Incluimos el archivo de conexión a la base de datos
                include '../php/conexion.php';

                // Ruta base de las imágenes
                $ruta_base_imagenes = 'imagenes_productos/';
                $ruta_logo = '../img/logo.png'; // Ruta del logo por defecto

                // Consulta a la base de datos para obtener los productos
                $sql = "SELECT * FROM producto WHERE Visibilidad = 1";
                $productos = $conexion->query($sql);

                // Verificar si hay resultados
                if ($productos->num_rows > 0) {
                    echo '<div class="contenedor-productos" id="contenedor-productos">'; // Añadir el ID para usar en JavaScript
                    while ($row = $productos->fetch_assoc()) {
                        // Ruta a la carpeta de imágenes del producto
                        $carpeta_imagenes = $ruta_base_imagenes . 'producto_' . $row["PK_Producto"] . '/';
                        
                        // Obtener la primera imagen de la carpeta para mostrar en la vista general
                        $ruta_imagen = $carpeta_imagenes . '1.jpg';
                
                        if (!file_exists($ruta_imagen)) {
                            // Si no existe la imagen, usar el logo por defecto
                            $ruta_imagen = $ruta_logo;
                            // En este caso, solo agregamos el logo al array de imágenes
                            $imagenes_json = json_encode([$ruta_logo]); // Pasamos el logo como única imagen en el array JSON
                        } else {
                            // Si existen imágenes, las obtenemos con glob()
                            $imagenes_producto = glob($carpeta_imagenes . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                            $imagenes_json = json_encode($imagenes_producto); // Convertimos las rutas de las imágenes en un array JSON
                        }
                        
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
                                <p>' . $row["Nombre"] . '</p>
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