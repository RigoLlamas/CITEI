<?php
// Incluir la conexión a la base de datos
include '../php/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Escapar las entradas para evitar inyecciones SQL
    $nombre = $conexion->real_escape_string($_POST['nombre']);
    $descripcion = $conexion->real_escape_string($_POST['descripcion']);
    $precio = (float)$_POST['precio'];
    $caracteristicas = $conexion->real_escape_string($_POST['caracteristicas']);
    $largo = (float)$_POST['largo'];
    $ancho = (float)$_POST['ancho'];
    $alto = (float)$_POST['alto'];

    // Actualizar los datos del producto en la base de datos
    $sql = "INSERT INTO producto 
            (Nombre, Descripcion, Precio, Caracteristicas, Largo, Ancho, Alto) 
        VALUES 
            ('$nombre', '$descripcion', '$precio', '$caracteristicas', '$largo', '$ancho', '$alto')";


    if ($conexion->query($sql) === TRUE) {
        $id_producto = mysqli_insert_id($conexion);
        // Ruta del directorio de imágenes del producto
        $directorio_imagenes = "imagenes_productos/producto_" . $id_producto . "/"; //Optener la ip de la insercion

        // Verificar si el directorio de imágenes existe
        if (file_exists($directorio_imagenes)) {
            // Eliminar las imágenes existentes en el directorio
            $files = glob($directorio_imagenes . "*"); // Obtiene todos los archivos en el directorio
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file); // Elimina cada archivo
                }
            }
        } else {
            // Crear el directorio si no existe
            mkdir($directorio_imagenes, 0777, true);
        }

        // Verificar si se subieron nuevas imágenes
        if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
            // Inicializar el contador de imágenes
            $contador_imagenes = 1;

            // Procesar las nuevas imágenes
            foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {
                if (is_uploaded_file($tmp_name)) {
                    // Obtener la extensión del archivo
                    $extension = pathinfo($_FILES['imagenes']['name'][$key], PATHINFO_EXTENSION);

                    // Verificar el tipo MIME del archivo
                    $tipo_mime = mime_content_type($tmp_name);
                    if (in_array($tipo_mime, ['image/jpeg', 'image/png', 'image/jpg'])) {
                        // Renombrar la imagen con un número secuencial
                        $nombre_imagen = $contador_imagenes . "." . $extension;
                        $ruta_imagen = $directorio_imagenes . $nombre_imagen;

                        // Mover el archivo a la carpeta del producto
                        if (move_uploaded_file($tmp_name, $ruta_imagen)) {
                            echo "Imagen subida correctamente: " . $ruta_imagen . "<br>";
                            $contador_imagenes++; // Incrementar el contador de imágenes
                        } else {
                            echo "Error al mover la imagen: " . $_FILES['imagenes']['name'][$key] . "<br>";
                        }
                    } else {
                        echo "Error: Tipo de archivo no permitido (" . $tipo_mime . ").<br>";
                    }
                } else {
                    echo "Error: No se subió correctamente el archivo " . $_FILES['imagenes']['name'][$key] . ".<br>";
                }
            }
        } else {
            echo "No se subieron nuevas imágenes.";
        }
    } else {
        echo "Error al actualizar el producto: " . $conexion->error;
    }
    header("Location: ../productos/productos.php");

    // Cerrar la conexión
    $conexion->close();
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Agregar producto</title>
    <script src="../js/pie.js"></script>
    <script src="../js/navbar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/sweetalert.js"></script>
</head>
<body>
<h2 style="text-align: center;">Agregar Producto</h2>

<form class="cuadro agregar_productos__campos" action="agregar_producto.php" method="POST" enctype="multipart/form-data">
    <label for="nombre">Nombre del Producto:</label><br>
    <input autocomplete="off" type="text" id="nombre" name="nombre" 
    minlength="1" maxlength="150"
    required><br><br>

    <label for="descripcion">Descripción:</label><br>
    <textarea autocomplete="off" id="descripcion" name="descripcion" rows="4" 
    minlength="10" maxlength="1000"
    required></textarea><br><br>

    <label for="precio">Precio:</label><br>
    <input autocomplete="off" type="number" id="precio" name="precio" step="0.01" 
    min="0.01" max="1000000.00"
    required><br><br>

    <label for="caracteristicas">Características:</label><br>
    <textarea autocomplete="off" id="caracteristicas" name="caracteristicas" rows="4" 
    minlength="10" maxlength="1000"
    required></textarea><br><br>

    <div class="contendeor_medidas">
        <div>
            <label for="largo">Largo (m):</label><br>
            <input autocomplete="off" type="number" id="largo" name="largo" step="0.01" 
            min ="0.01" max="10"
            required><br><br>
        </div>
        <div>
            <label for="ancho">Ancho (m):</label><br>
            <input autocomplete="off" type="number" id="ancho" name="ancho" step="0.01" 
            min ="0.01" max="10"
            required><br><br>
        </div>
        <div>
            <label for="alto">Alto (m):</label><br>
            <input autocomplete="off" type="number" id="alto" name="alto" step="0.01" 
            min ="0.01" max="10"
            required><br><br>
        </div>
    </div>

    <label for="imagen">Subir Imagen:</label><br>
    <label for="imagen">Solo permite imágenes en formato PNG y JPEG, tamaño máximo 5MB.</label><br>
    <input type="file" id="imagenes" name="imagenes[]" accept="image/png, image/jpeg" multiple><br><br>

    <script>
        document.getElementById('imagenes').addEventListener('change', function(event) {
        const maxFiles = 6;  // Límite de cantidad de archivos
        const maxSize = 5 * 1024 * 1024;  // Tamaño máximo por archivo en bytes (5 MB)
        const input = event.target;
        const numFiles = input.files.length;
        const allowedTypes = ['image/png', 'image/jpeg'];

        // Verificar cantidad de archivos
        if (numFiles > maxFiles) {
            alert('Solo puedes subir un máximo de ' + maxFiles + ' imágenes.');
            input.value = ''; // Resetea el campo
            return;
        }

        let validFiles = true;
        for (let i = 0; i < numFiles; i++) {
            const file = input.files[i];

            // Verificar tipo de archivo
            if (!allowedTypes.includes(file.type)) {
                alert('Solo se permiten imágenes en formato PNG y JPEG.');
                validFiles = false;
            }

            // Verificar tamaño de archivo
            if (file.size > maxSize) {
                alert('El archivo ' + file.name + ' excede el tamaño máximo permitido de 5 MB.');
                validFiles = false;
            }
        }

        // Si algún archivo no es válido, reinicia el input completo
        if (!validFiles) {
            input.value = ''; // Resetea el campo si hay archivos no válidos
        }
    });

    </script>

    <div class="agregar_productos__accion">
        <div style="margin-right: 2rem; width: 20%;">
            <button type="reset">Cancelar</button>
        </div>
        <div style="margin-right: 2rem; width: 20%;">
            <button type="submit">Agregar</button>
        </div>
    </div>
</form>
</body>
</html>