<?php
// Incluir la conexión a la base de datos
include '../php/conexion.php';

// Obtener el producto si existe un ID pasado por la URL
$producto = null;
if (isset($_GET['id'])) {
    $id_producto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id_producto) {
        $stmt = $conexion->prepare("SELECT * FROM producto WHERE PK_Producto = ?");
        $stmt->bind_param("i", $id_producto);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $producto = $resultado->fetch_assoc();
        $stmt->close();
    }
}

// Procesar la solicitud POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar y escapar entradas
    $nombre = $conexion->real_escape_string(trim($_POST['nombre']));
    $descripcion = $conexion->real_escape_string(trim($_POST['descripcion']));
    $precio = isset($_POST['precio']) ? (float)$_POST['precio'] : 0;
    $caracteristicas = $conexion->real_escape_string(trim($_POST['caracteristicas']));
    $largo = isset($_POST['largo']) ? (float)$_POST['largo'] : 0;
    $ancho = isset($_POST['ancho']) ? (float)$_POST['ancho'] : 0;
    $alto = isset($_POST['alto']) ? (float)$_POST['alto'] : 0;
    $id_producto = isset($_POST['id_producto']) ? (int)$_POST['id_producto'] : null;

    if ($nombre && $descripcion && $precio > 0 && $largo > 0 && $ancho > 0 && $alto > 0) {
        $stmt = $conexion->prepare("UPDATE producto SET Nombre = ?, Descripcion = ?, Precio = ?, Caracteristicas = ?, Largo = ?, Ancho = ?, Alto = ? WHERE PK_Producto = ?");
        $stmt->bind_param("ssdsdddi", $nombre, $descripcion, $precio, $caracteristicas, $largo, $ancho, $alto, $id_producto);

        if ($stmt->execute()) {
            // Manejar imágenes
            $directorio_imagenes = "imagenes_productos/producto_" . $id_producto . "/";
            if (!file_exists($directorio_imagenes)) {
                mkdir($directorio_imagenes, 0777, true);
            }

            // Eliminar imágenes antiguas
            $files = glob($directorio_imagenes . "*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            // Procesar nuevas imágenes
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
            $error = "Error al actualizar el producto: " . $stmt->error;
        }
        $stmt->close();
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
    <title>CITEI - Modificar Producto</title>
    <script src="../js/pie.js"></script>
    <script src="../js/navbar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <h2 style="text-align: center;">Modificar Producto</h2>

    <form class="cuadro agregar_productos__campos" id="formModificarProducto" action="" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_producto" value="<?php echo $producto['PK_Producto']; ?>">

        <label for="nombre">Nombre del Producto:</label>
        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($producto['Nombre']); ?>" minlength="1" maxlength="150" required><br><br>

        <label for="descripcion">Descripción:</label>
        <textarea id="descripcion" name="descripcion" rows="4" minlength="10" maxlength="1000" required><?php echo htmlspecialchars($producto['Descripcion']); ?></textarea><br><br>

        <label for="precio">Precio:</label>
        <input type="number" id="precio" name="precio" step="0.01" value="<?php echo htmlspecialchars($producto['Precio']); ?>" min="0.01" max="1000000.00" required><br><br>

        <label for="caracteristicas">Características:</label>
        <textarea id="caracteristicas" name="caracteristicas" rows="4" minlength="10" maxlength="1000" required><?php echo htmlspecialchars($producto['Caracteristicas']); ?></textarea><br><br>

        <div class="contendeor_medidas">
            <label for="largo">Largo:</label>
            <input type="number" id="largo" name="largo" step="0.01" value="<?php echo htmlspecialchars($producto['Largo']); ?>" min="0.01" max="10" required><br><br>

            <label for="ancho">Ancho:</label>
            <input type="number" id="ancho" name="ancho" step="0.01" value="<?php echo htmlspecialchars($producto['Ancho']); ?>" min="0.01" max="10" required><br><br>

            <label for="alto">Alto:</label>
            <input type="number" id="alto" name="alto" step="0.01" value="<?php echo htmlspecialchars($producto['Alto']); ?>" min="0.01" max="10" required><br><br>
        </div>

        <label for="imagenes">Subir Imágenes:</label>
        <input type="file" id="imagenes" name="imagenes[]" accept="image/png, image/jpeg" multiple><br><br>

        <div class="agregar_productos__accion">
            <button type="reset">Cancelar</button>
            <button type="button" id="btnGuardar">Guardar Cambios</button>
        </div>
    </form>

    <script>
        document.getElementById('imagenes').addEventListener('change', function(event) {
            const maxFiles = 6;
            const maxSize = 5 * 1024 * 1024;
            const input = event.target;

            if (input.files.length > maxFiles) {
                Swal.fire('Error', 'Solo puedes subir un máximo de 6 imágenes.', 'error');
                input.value = '';
                return;
            }

            for (let file of input.files) {
                if (file.size > maxSize) {
                    Swal.fire('Error', 'El archivo ' + file.name + ' excede el tamaño máximo permitido de 5 MB.', 'error');
                    input.value = '';
                    break;
                }
            }
        });

        document.getElementById('btnGuardar').addEventListener('click', function() {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¿Deseas guardar los cambios realizados?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formModificarProducto').submit();
                }
            });
        });
    </script>
</body>
</html>
