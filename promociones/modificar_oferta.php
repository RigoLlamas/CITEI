<?php
// Incluir archivos necesarios
include '../php/conexion.php';

// Obtener el ID de la oferta desde la URL
if (!isset($_GET['oferta']) || !is_numeric($_GET['oferta'])) {
    die("Error: No se especificó una oferta válida.");
}
$oferta_id = (int)$_GET['oferta'];

// Consultar los detalles de la oferta
$sql_detalle = "
    SELECT o.Oferta, o.Tipo AS TipoOferta, o.Valor AS ValorOferta, o.Despliegue, o.Condicion, o.Producto,
           c.Tipo AS TipoCondicion, c.Valor AS ValorCondicion, c.LimiteTiempo AS Expiracion, c.CantidadUsos,
           IF(o.Producto IS NULL, 'General', p.Nombre) AS ProductoNombre
    FROM ofertas o
    JOIN condiciones c ON o.Condicion = c.Condicion
    LEFT JOIN producto p ON o.Producto = p.PK_Producto
    WHERE o.Oferta = ?
";
$stmt = mysqli_prepare($conexion, $sql_detalle);
mysqli_stmt_bind_param($stmt, 'i', $oferta_id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$oferta = mysqli_fetch_assoc($resultado);

if (!$oferta) {
    die("Error: No se encontró la oferta especificada.");
}

// Actualizar la oferta si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_oferta = $conexion->real_escape_string($_POST['tipo_oferta']);
    $valor_oferta = floatval($_POST['valor_oferta']);
    $tipo_condicion = $conexion->real_escape_string($_POST['tipo_condicion']);
    $despliegue = $conexion->real_escape_string($_POST['despliegue']);
    $expiracion = !empty($_POST['expiracion']) ? $conexion->real_escape_string($_POST['expiracion']) : null;
    $cantidad_usos = isset($_POST['cantidad_usos']) ? intval($_POST['cantidad_usos']) : null;

    // Actualizar la condición
    $query_condicion = "UPDATE condiciones SET Tipo = ?, Valor = ?, LimiteTiempo = ?, CantidadUsos = ? WHERE Condicion = ?";
    $stmt_condicion = mysqli_prepare($conexion, $query_condicion);
    mysqli_stmt_bind_param($stmt_condicion, 'sisii', $tipo_condicion, $valor_oferta, $expiracion, $cantidad_usos, $oferta['Condicion']);

    if (mysqli_stmt_execute($stmt_condicion)) {
        // Actualizar la oferta
        $query_oferta = "UPDATE ofertas SET Tipo = ?, Valor = ?, Despliegue = ? WHERE Oferta = ?";
        $stmt_oferta = mysqli_prepare($conexion, $query_oferta);
        mysqli_stmt_bind_param($stmt_oferta, 'sdsi', $tipo_oferta, $valor_oferta, $despliegue, $oferta_id);

        if (mysqli_stmt_execute($stmt_oferta)) {
            echo "<script>
                Swal.fire({
                    title: '¡Éxito!',
                    text: 'La promoción ha sido modificada correctamente.',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    window.location.href = 'gestionar_promociones.php';
                });
            </script>";
            exit;
        } else {
            echo "<p style='color: red;'>Error al actualizar la oferta: " . mysqli_error($conexion) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Error al actualizar la condición: " . mysqli_error($conexion) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Promoción</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <h1>Modificar Promoción</h1>
    <form action="modificar_promocion.php?oferta=<?php echo $oferta_id; ?>" method="POST">
        <!-- Producto (Solo Lectura) -->
        <div>
            <label>Producto:</label>
            <input type="text" value="<?php echo $oferta['ProductoNombre']; ?>" readonly style="width: 100%;">
        </div>

        <!-- Tipo de oferta y valor -->
        <div>
            <label for="tipo_oferta">Tipo de Oferta:</label>
            <select id="tipo_oferta" name="tipo_oferta" required>
                <option value="Canjeable" <?php echo ($oferta['TipoOferta'] === 'Canjeable') ? 'selected' : ''; ?>>Dinero Canjeable</option>
                <option value="Porcentual" <?php echo ($oferta['TipoOferta'] === 'Porcentual') ? 'selected' : ''; ?>>Descuento Porcentual</option>
            </select>
        </div>
        <div>
            <label for="valor_oferta">Valor de la Oferta:</label>
            <input type="number" id="valor_oferta" name="valor_oferta" value="<?php echo $oferta['ValorOferta']; ?>" required>
        </div>

        <!-- Condición -->
        <div>
            <label for="tipo_condicion">Tipo de Condición:</label>
            <select id="tipo_condicion" name="tipo_condicion" required>
                <option value="Temporada" <?php echo ($oferta['TipoCondicion'] === 'Temporada') ? 'selected' : ''; ?>>Temporada</option>
                <option value="Cantidad de compras" <?php echo ($oferta['TipoCondicion'] === 'Cantidad de compras') ? 'selected' : ''; ?>>Cantidad de Compras</option>
                <option value="Productos comprados" <?php echo ($oferta['TipoCondicion'] === 'Productos comprados') ? 'selected' : ''; ?>>Productos Comprados</option>
            </select>
        </div>
        <div>
            <label for="despliegue">Fecha de Despliegue:</label>
            <input type="date" id="despliegue" name="despliegue" value="<?php echo $oferta['Despliegue']; ?>" required>
        </div>
        <div>
            <label for="expiracion">Fecha de Expiración (Opcional):</label>
            <input type="date" id="expiracion" name="expiracion" value="<?php echo $oferta['Expiracion']; ?>">
        </div>
        <div>
            <label for="cantidad_usos">Cantidad de Usos (Opcional):</label>
            <input type="number" id="cantidad_usos" name="cantidad_usos" value="<?php echo $oferta['CantidadUsos']; ?>">
        </div>

        <!-- Botón de guardar -->
        <button type="submit">Guardar Cambios</button>
    </form>

    <script>
        // Mostrar u ocultar las condiciones según el tipo seleccionado
        document.getElementById('tipo_condicion').addEventListener('change', function() {
            const tipoCondicion = this.value;
            const isTemporada = tipoCondicion === 'Temporada';
            const isCantidadCompras = tipoCondicion === 'Cantidad de compras';
            const isProductosComprados = tipoCondicion === 'Productos comprados';

            document.getElementById('condiciones_temporada').style.display = isTemporada ? 'block' : 'none';
            document.getElementById('condiciones_cantidad_compras').style.display = isCantidadCompras ? 'block' : 'none';
            document.getElementById('condiciones_productos_comprados').style.display = isProductosComprados ? 'block' : 'none';
        });

        // Validación y envío del formulario
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevenir el envío directo

            const tipoOferta = document.getElementById('tipo_oferta').value;
            const valorOferta = parseFloat(document.getElementById('valor_oferta').value) || 0;
            const tipoCondicion = document.getElementById('tipo_condicion').value;

            // Valores específicos según la condición seleccionada
            const despliegue = document.getElementById('despliegue') ? document.getElementById('despliegue').value : null;
            const expiracion = document.getElementById('expiracion') ? document.getElementById('expiracion').value : null;
            const cantidadUsos = document.getElementById('cantidad_usos') ? parseInt(document.getElementById('cantidad_usos').value) || null : null;

            console.log('Valores obtenidos del formulario:');
            console.log('Tipo de oferta:', tipoOferta);
            console.log('Valor de oferta:', valorOferta);
            console.log('Tipo de condición:', tipoCondicion);
            console.log('Rango de validación:', despliegue);
            console.log('Expiración:', expiracion);
            console.log('Cantidad de usos:', cantidadUsos);

            // Validaciones generales
            if (valorOferta <= 0) {
                Swal.fire({
                    title: 'Error en el valor de la oferta',
                    text: 'El valor de la oferta debe ser mayor que 0.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            // Validación por tipo de condición
            if (tipoCondicion === 'Temporada') {
                if (!despliegue || !expiracion) {
                    Swal.fire({
                        title: 'Error en las fechas de la temporada',
                        text: 'Debes ingresar el rango de validación y la fecha de expiración.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }
                if (new Date(despliegue) >= new Date(expiracion)) {
                    Swal.fire({
                        title: 'Error en las fechas',
                        text: 'La fecha de expiración debe ser posterior al rango de validación.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }
            }

            if (tipoCondicion === 'Cantidad de compras' || tipoCondicion === 'Productos comprados') {
                if (cantidadUsos !== null && cantidadUsos <= 0) {
                    Swal.fire({
                        title: 'Error en la cantidad de usos',
                        text: 'La cantidad de usos debe ser mayor que 0.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }
            }

            // Confirmación final y envío del formulario
            Swal.fire({
                title: '¿Guardar Cambios?',
                text: "¿Estás seguro de que deseas guardar los cambios en esta promoción?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    e.target.submit(); // Enviar el formulario
                }
            });
        });
    </script>


</body>

</html>