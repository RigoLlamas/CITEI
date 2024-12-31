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
    $descripcion = trim($_POST['descripcion']);
    $precio = isset($_POST['precio']) ? (float)$_POST['precio'] : 0;
    $caracteristicas = trim($_POST['caracteristicas']);
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

            // Procesar nuevas imágenes sin borrar las existentes
            if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
                // Contar cuántas imágenes ya existen en el directorio
                $contador_imagenes = count(glob($directorio_imagenes . "*")) + 1;

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
        <textarea id="descripcion" name="descripcion" rows="4" minlength="10" maxlength="1000" required><?php echo nl2br(htmlspecialchars($producto['Descripcion'])); ?></textarea><br><br>

        <label for="precio">Precio:</label>
        <input type="number" id="precio" name="precio" step="0.01" value="<?php echo htmlspecialchars($producto['Precio']); ?>" min="0.01" max="1000000.00" required><br><br>

        <label for="caracteristicas">Características:</label>
        <textarea id="caracteristicas" name="caracteristicas" rows="4" minlength="10" maxlength="1000" required><?php echo htmlspecialchars(str_replace('<br>', "\n", $producto['Caracteristicas'])); ?></textarea><br><br>

        <div class="contendeor_medidas">
            <div>
                <label for="largo">Largo del paquete (m):</label><br>
                <input type="number" id="largo" name="largo" step="0.01" value="<?php echo htmlspecialchars($producto['Largo']); ?>" min="0.01" max="10" required><br><br>
            </div>
            <div>
                <label for="ancho">Ancho del paquete (m):</label><br>
                <input type="number" id="ancho" name="ancho" step="0.01" value="<?php echo htmlspecialchars($producto['Ancho']); ?>" min="0.01" max="10" required><br><br>
            </div>
            <div>
                <label for="alto">Alto del paquete (m):</label><br>
                <input type="number" id="alto" name="alto" step="0.01" value="<?php echo htmlspecialchars($producto['Alto']); ?>" min="0.01" max="10" required><br><br>
            </div>
        </div>

        <label for="cargar_imagenes" class="boton-cargar">Subir Imágenes</label>
        <input type="file" id="cargar_imagenes" name="imagenes[]" accept="image/png, image/jpeg" multiple>
        <div id="informacionArchivos">
            <p>No se han seleccionado archivos nuevos.</p>
        </div>

        <!-- Mostrar imágenes existentes en el contenedor de vista previa -->
        <div class="contenedor-previa" id="contenedorPrevia">
            <?php
            $directorio_imagenes = "imagenes_productos/producto_" . $producto['PK_Producto'] . "/";
            if (file_exists($directorio_imagenes)) {
                $imagenes_existentes = array_diff(scandir($directorio_imagenes), ['.', '..']);
                foreach ($imagenes_existentes as $imagen) {
                    $ruta_imagen = $directorio_imagenes . $imagen;
                    echo '
            <div class="miniatura">
                <img src="' . $ruta_imagen . '" alt="Imagen guardada">
                <div class="eliminar-imagen" data-imagen="' . $imagen . '">&times;</div>
            </div>';
                }
            }
            ?>
        </div>
        <script>
            const inputCargarImagenes = document.getElementById('cargar_imagenes');
            const informacionArchivos = document.getElementById('informacionArchivos');
            const contenedorPrevia = document.getElementById('contenedorPrevia');

            // Lista de archivos seleccionados
            let archivosSeleccionados = [];

            inputCargarImagenes.addEventListener('change', function() {
                const archivos = Array.from(this.files);

                // Actualizar la lista de archivos seleccionados
                archivosSeleccionados = [...archivos];

                // Mostrar los nombres de los archivos seleccionados
                informacionArchivos.innerHTML = archivosSeleccionados.length > 0 ?
                    "<p>Archivos seleccionados:</p><br>" + archivosSeleccionados.map(file => file.name).join('<br></p>') :
                    "No se han seleccionado archivos.";

                // Limpiar contenedor de vista previa
                contenedorPrevia.innerHTML = "";

                // Crear miniaturas para cada imagen
                archivosSeleccionados.forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const contenedorMiniatura = document.createElement('div');
                            contenedorMiniatura.classList.add('miniatura');

                            const img = document.createElement('img');
                            img.src = e.target.result; // Asignar la URL de la imagen cargada
                            contenedorMiniatura.appendChild(img);

                            // Botón de eliminar
                            const botonEliminar = document.createElement('div');
                            botonEliminar.classList.add('eliminar-imagen');
                            botonEliminar.innerHTML = "&times;";
                            botonEliminar.addEventListener('click', function() {
                                eliminarImagen(index);
                            });
                            contenedorMiniatura.appendChild(botonEliminar);

                            contenedorPrevia.appendChild(contenedorMiniatura);
                        };
                        reader.readAsDataURL(file); // Leer el archivo como URL de datos
                    }
                });
            });

            function eliminarImagen(index) {
                // Eliminar archivo de la lista
                archivosSeleccionados.splice(index, 1);

                // Actualizar la vista previa
                actualizarVistaPrevia();
            }

            function actualizarVistaPrevia() {
                contenedorPrevia.innerHTML = "";

                // Mostrar nombres actualizados
                informacionArchivos.textContent = archivosSeleccionados.length > 0 ?
                    "Archivos seleccionados: " + archivosSeleccionados.map(file => file.name).join('<br>') :
                    "No se han seleccionado archivos.";

                // Renderizar miniaturas nuevamente
                archivosSeleccionados.forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const contenedorMiniatura = document.createElement('div');
                        contenedorMiniatura.classList.add('miniatura');

                        const img = document.createElement('img');
                        img.src = e.target.result;
                        contenedorMiniatura.appendChild(img);

                        const botonEliminar = document.createElement('div');
                        botonEliminar.classList.add('eliminar-imagen');
                        botonEliminar.innerHTML = "&times;";
                        botonEliminar.addEventListener('click', function() {
                            eliminarImagen(index);
                        });
                        contenedorMiniatura.appendChild(botonEliminar);

                        contenedorPrevia.appendChild(contenedorMiniatura);
                    };
                    reader.readAsDataURL(file);
                });
            }
        </script>

        <div class="agregar_productos__accion">
            <button type="button" onclick="history.back()">Cancelar</button>
            <button type="button" id="btnGuardar">Guardar Cambios</button>
        </div>
    </form>

    <div class="preview-container" id="previewContainer"></div>
</body>
<script>
    document.getElementById('cargar_imagenes').addEventListener('change', function(event) {
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
        const formulario = document.getElementById('formModificarProducto');

        // Verificar la validez del formulario
        if (!formulario.checkValidity()) {
            // Mostrar los errores del navegador si el formulario no es válido
            formulario.reportValidity();
            return; // Salir sin mostrar SweetAlert
        }

        // Si el formulario es válido, mostrar SweetAlert
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
                formulario.submit();
            }
        });
    });


    document.querySelectorAll('.eliminar-imagen').forEach(button => {
        button.addEventListener('click', function() {
            const nombreImagen = this.dataset.imagen;

            Swal.fire({
                title: '¿Eliminar imagen?',
                text: 'Esta acción es irreversible.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(result => {
                if (result.isConfirmed) {
                    fetch('eliminar_imagen.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id_producto: <?php echo json_encode($producto['PK_Producto']); ?>,
                                imagen: nombreImagen
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Eliminada', 'Imagen eliminada correctamente.', 'success');
                                // Acceder al contenedor padre y eliminarlo
                                this.parentElement.remove();
                            } else {
                                Swal.fire('Error', data.message || 'No se pudo eliminar la imagen.', 'error');
                            }
                        })
                        .catch(err => {
                            Swal.fire('Error', 'Ocurrió un error al procesar la solicitud.', 'error');
                            console.error('Error:', err);
                        });
                }
            });
        });
    });
</script>

</html>