<?php
include '../php/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Escapar y validar entradas
    $nombre = $conexion->real_escape_string(trim($_POST['nombre']));
    $descripcion = $conexion->real_escape_string(trim($_POST['descripcion']));
    $precio = isset($_POST['precio']) ? (float)$_POST['precio'] : 0;
    $caracteristicas = $conexion->real_escape_string(trim($_POST['caracteristicas']));
    $largo = isset($_POST['largo']) ? (float)$_POST['largo'] : 0;
    $ancho = isset($_POST['ancho']) ? (float)$_POST['ancho'] : 0;
    $alto = isset($_POST['alto']) ? (float)$_POST['alto'] : 0;

    // Validar que todos los datos estén presentes
    if ($nombre && $descripcion && $precio > 0 && $largo > 0 && $ancho > 0 && $alto > 0) {
        $stmt = $conexion->prepare("INSERT INTO producto (Nombre, Descripcion, Precio, Caracteristicas, Largo, Ancho, Alto) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssss", $nombre, $descripcion, $precio, $caracteristicas, $largo, $ancho, $alto);

        if ($stmt->execute()) {
            $id_producto = $stmt->insert_id;

            // Manejo del directorio de imágenes
            $directorio_imagenes = "imagenes_productos/producto_" . $id_producto . "/";
            if (!file_exists($directorio_imagenes)) {
                mkdir($directorio_imagenes, 0777, true);
            }

            // Manejo de imágenes subidas
            if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
                $contador_imagenes = 1;

                foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {
                    if (is_uploaded_file($tmp_name)) {
                        $extension = pathinfo($_FILES['imagenes']['name'][$key], PATHINFO_EXTENSION);
                        $tipo_mime = mime_content_type($tmp_name);

                        if (in_array($tipo_mime, ['image/jpeg', 'image/png', 'image/jpg'])) {
                            $nombre_imagen = $contador_imagenes . "." . $extension;
                            $ruta_imagen = $directorio_imagenes . $nombre_imagen;

                            if (move_uploaded_file($tmp_name, $ruta_imagen)) {
                                $contador_imagenes++;
                            }
                        }
                    }
                }
            }
            header("Location: ../productos/productos.php?success=1");
            exit;
        } else {
            $error = "Error al guardar el producto: " . $stmt->error;
        }
    } else {
        $error = "Todos los campos son obligatorios y deben tener valores válidos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Agregar Producto</title>
    <script src="../js/pie.js"></script>
    <script src="../js/navbar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <h2 style="text-align: center;">Agregar Producto</h2>

    <form class="cuadro agregar_productos__campos" action="agregar_producto.php" method="POST" enctype="multipart/form-data">
        <label for="nombre">Nombre del Producto:</label><br>
        <input type="text" id="nombre" name="nombre" minlength="1" maxlength="150" autocomplete="off" required><br><br>

        <label for="descripcion">Descripción:</label><br>
        <textarea id="descripcion" name="descripcion" rows="4" minlength="10" maxlength="1000" autocomplete="off" required></textarea><br><br>

        <label for="precio">Precio:</label><br>
        <input type="number" id="precio" name="precio" step="0.01" min="0.01" max="1000000.00" autocomplete="off" required><br><br>

        <label for="caracteristicas">Características:</label><br>
        <textarea id="caracteristicas" name="caracteristicas" rows="4" minlength="10" maxlength="1000" autocomplete="off" required></textarea><br><br>

        <div class="contendeor_medidas">
            <div>
                <label for="largo">Largo del paquete (m):</label><br>
                <input type="number" id="largo" name="largo" step="0.01" min="0.01" max="10" autocomplete="off" required><br><br>
            </div>
            <div>
                <label for="ancho">Ancho del paquete (m):</label><br>
                <input type="number" id="ancho" name="ancho" step="0.01" min="0.01" max="10" autocomplete="off" required><br><br>
            </div>
            <div>
                <label for="alto">Alto del paquete (m):</label><br>
                <input type="number" id="alto" name="alto" step="0.01" min="0.01" max="10" autocomplete="off" required><br><br>
            </div>
        </div>

        <label for="imagen">Subir Imagen:</label><br>
        <input type="file" id="imagenes" name="imagenes[]" accept="image/png, image/jpeg" multiple><br><br>

        <div class="agregar_productos__accion">
            <button type="reset">Cancelar</button>
            <button  type="submit">Agregar</button>
        </div>
    </form>

    <script>
        document.getElementById('imagenes').addEventListener('change', function (event) {
            const maxFiles = 6;
            const maxSize = 5 * 1024 * 1024;
            const input = event.target;
            const numFiles = input.files.length;
            const allowedTypes = ['image/png', 'image/jpeg'];

            if (numFiles > maxFiles) {
                Swal.fire('Error', 'Solo puedes subir un máximo de 6 imágenes.', 'error');
                input.value = '';
                return;
            }

            let validFiles = true;
            for (let file of input.files) {
                if (!allowedTypes.includes(file.type) || file.size > maxSize) {
                    Swal.fire('Error', 'Archivo no válido. Asegúrate de que sean PNG o JPEG menores a 5 MB.', 'error');
                    validFiles = false;
                    break;
                }
            }

            if (!validFiles) input.value = '';
        });
    </script>
</body>

</html>
