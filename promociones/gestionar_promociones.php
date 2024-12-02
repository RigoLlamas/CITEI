<?php
// Incluir archivos necesarios
include '../php/conexion.php';
//include '../scripts/verificar_dia.php';

// Consulta para obtener lista de promociones
$sql_lista = "
    SELECT o.Estado, o.Oferta, o.Tipo, o.Valor, o.Despliegue, IF(o.Producto IS NULL, 'General', p.Nombre) AS ProductoNombre
    FROM ofertas o
    LEFT JOIN producto p ON o.Producto = p.PK_Producto
";
$resultado = mysqli_query($conexion, $sql_lista);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto = $_POST['producto'];
    $tipo_oferta = $conexion->real_escape_string($_POST['tipo_oferta']);
    $valor_oferta = floatval($conexion->real_escape_string($_POST['valor_oferta']));
    $tipo_condicion = $conexion->real_escape_string($_POST['tipo_condicion']);
    $despliegue = !empty($_POST['despliegue']) ? $conexion->real_escape_string($_POST['despliegue']) : date('Y-m-d');
    $cantidad_usos = isset($_POST['cantidad_usos']) ? intval($_POST['cantidad_usos']) : 0; // Nuevo campo

    if ($tipo_condicion === 'Temporada') {
        $expiracion = $conexion->real_escape_string($_POST['expiracion']);
        $query_condicion = "INSERT INTO condiciones (Tipo, LimiteTiempo)
                            VALUES (?, ?, ?)";
        $stmt_cond = mysqli_prepare($conexion, $query_condicion);
        mysqli_stmt_bind_param($stmt_cond, 'ssi', $tipo_condicion, $expiracion);
    } else {
        if ($tipo_condicion === 'Productos comprados') {
            $valor_condicion = $conexion->real_escape_string($_POST['cantidad_productos']);
        }
        if ($tipo_condicion === 'Cantidad de compras') {
            $valor_condicion = $conexion->real_escape_string($_POST['cantidad_compras']);
        }

        $query_condicion = "INSERT INTO condiciones (Tipo, Valor, CantidadUsos)
                            VALUES (?, ?, ?)";
        $stmt_cond = mysqli_prepare($conexion, $query_condicion);
        mysqli_stmt_bind_param($stmt_cond, 'sii', $tipo_condicion, $valor_condicion, $cantidad_usos);
    }

    if (mysqli_stmt_execute($stmt_cond)) {
        // Obtener el ID de la condición insertada
        $condicion_id = mysqli_insert_id($conexion);

        $query_oferta = "INSERT INTO ofertas (Tipo, Valor, Producto, Descripcion, Estado, Despliegue, Condicion)
                         VALUES (?, ?, ?, ?, 'En revisión', ?, ?)";
        $stmt = mysqli_prepare($conexion, $query_oferta);
        $descripcion = "En tu siguiente compra recibirás una oferta de $tipo_oferta con un descuento de $valor_oferta, a partir de $despliegue en productos $producto.";
        mysqli_stmt_bind_param($stmt, 'sdisss', $tipo_oferta, $valor_oferta, $producto, $descripcion, $despliegue, $condicion_id);

        if (mysqli_stmt_execute($stmt)) {
            header('Location: gestionar_promociones.php?success=true');
            exit;
        } else {
            echo "<p style='color: red;'>Error al insertar la oferta: " . mysqli_error($conexion) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Error al insertar la condición: " . mysqli_error($conexion) . "</p>";
    }
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Gestionar Promociones</title>
    <script src="../js/pie.js" defer></script>
    <script src="../js/navbar.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <?php
    if (isset($_GET['success']) && $_GET['success'] === 'true') {
        echo "<script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                title: '¡Éxito!',
                text: 'Lista de promociones actualizada.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        });
    </script>";
    }
    ?>

    <div class="contenedor-producto cuadro">
        <form id="formPromocion" action="gestionar_promociones.php" method="POST">
            <!-- Selección de Producto o General -->
            <div style="margin-bottom: 1rem;">
                <label for="producto">Oferta para Producto Específico o General:</label>
                <select id="producto" name="producto" style="width: 100%;">
                    <?php
                    $query_productos = "SELECT PK_Producto, Nombre FROM producto WHERE Visibilidad = 1";
                    $result_productos = mysqli_query($conexion, $query_productos);
                    if ($result_productos) {
                        while ($row = mysqli_fetch_assoc($result_productos)) {
                            echo "<option value='" . $row['PK_Producto'] . "'>" . $row['Nombre'] . "</option>";
                        }
                    } else {
                        echo "<option value='NULL'>No se encontraron productos</option>";
                    }
                    ?>
                </select>
            </div>

            <!-- Tipo de oferta y valor de la oferta -->
            <div class="dos-columnas" style="margin-bottom: 1rem;">
                <div>
                    <label for="tipo_oferta">Tipo de Oferta:</label>
                    <select id="tipo_oferta" name="tipo_oferta" style="width: 100%;">
                        <option value="Canjeable">Dinero Canjeable</option>
                        <option value="Porcentual">Descuento en el Siguiente Pedido</option>
                    </select>
                </div>
                <div>
                    <label for="valor_oferta">Valor de la Oferta:</label>
                    <input type="number" id="valor_oferta" name="valor_oferta" style="width: 100%;" required>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label>Selecciona el tipo de condición:</label>
                <div>
                    <input type="radio" id="condicion_temporada" name="tipo_condicion" value="Temporada" checked>
                    <label for="condicion_temporada">Condición de Temporada (Rango de Validación y Tiempo de Expiración)</label>
                </div>
                <div>
                    <input type="radio" id="condicion_cantidad_compras" name="tipo_condicion" value="Cantidad de compras">
                    <label for="condicion_cantidad_compras">Condición de Cantidad de Compras</label>
                </div>
                <div>
                    <input type="radio" id="condicion_productos_comprados" name="tipo_condicion" value="Productos comprados">
                    <label for="condicion_productos_comprados">Condición de Productos Comprados</label>
                </div>
            </div>

            <!-- Condiciones de Temporada -->
            <div id="condiciones_temporada" style="margin-bottom: 1rem;">
                <div class="dos-columnas" style="margin-bottom: 1rem;">
                    <div>
                        <label for="despliegue">Tiempo de Despliegue:</label>
                        <input type="date" id="despliegue" name="despliegue" style="width: 100%;">
                    </div>
                    <div>
                        <label for="expiracion">Tiempo de Expiración:</label>
                        <input type="date" id="expiracion" name="expiracion" style="width: 100%;">
                    </div>
                </div>
            </div>

            <!-- Condición de Cantidad de Compras -->
            <div id="condiciones_cantidad_compras" style="display: none; margin-bottom: 1rem;">
                <div class="dos-columnas">
                    <div>
                        <label for="cantidad_compras">Cantidad de Costo de Compra:</label>
                        <input type="number" id="cantidad_compras" name="cantidad_compras" style="width: 100%;">
                    </div>
                    <div>
                        <label for="limite_uso_compras">Límite de Uso:</label>
                        <input type="number" id="limite_uso_compras" name="limite_uso_compras" style="width: 100%;" placeholder="Límite de uso para esta condición">
                    </div>
                </div>

            </div>

            <!-- Condición de Productos Comprados -->
            <div id="condiciones_productos_comprados" style="display: none; margin-bottom: 1rem;">
                <div class="dos-columnas">
                    <div>
                        <label for="cantidad_productos">Cantidad de Productos al Comprar:</label>
                        <input type="number" id="cantidad_productos" name="cantidad_productos" style="width: 100%;">
                    </div>
                    <div>
                        <label for="limite_uso_compras">Límite de Uso:</label>
                        <input type="number" id="limite_uso_compras" name="limite_uso_compras" style="width: 100%;" placeholder="Límite de uso para esta condición">
                    </div>
                </div>
            </div>

            <!-- Botón de agregar promoción -->
            <div class="agregar_productos__accion" style="margin-bottom: 1rem;">
                <button type="button" id="btnAgregarPromocion">Agregar Promoción</button>
            </div>
        </form>

        <div class="lista-promociones">
            <h3>Promociones registradas</h3>
            <ul id="promociones">
                <?php
                if ($resultado && mysqli_num_rows($resultado) > 0) {
                    while ($oferta = mysqli_fetch_assoc($resultado)) {
                        echo "<li>";
                        echo "Estado: " . $oferta['Estado'] . "--> Producto: " . $oferta['ProductoNombre'] . " - Tipo: " . $oferta['Tipo'] . " (" . $oferta['Valor'] . ") Despliegue: " . $oferta['Despliegue'];
                        echo "<div>";
                        echo "<a href='modificar_oferta.php?oferta=" . $oferta['Oferta'] . "'>Modificar</a> | ";
                        echo "<a href='desplegar_oferta.php?oferta=" . $oferta['Oferta'] . "'>Desplegar</a> | ";
                        echo "<a href='bajar_oferta.php?oferta=" . $oferta['Oferta'] . "' class='btnEliminarPromocion'>Eliminar</a>";
                        echo "</div>";
                        echo "</li>";
                    }
                } else {
                    echo "<li>No hay promociones registradas.</li>";
                }
                ?>
            </ul>
        </div>
    </div>

    <script>
        // Mostrar u ocultar las condiciones según el tipo seleccionado
        document.querySelectorAll('input[name="tipo_condicion"]').forEach((input) => {
            input.addEventListener('change', function() {
                const isTemporada = this.value === 'Temporada';
                const isCantidadCompras = this.value === 'Cantidad de compras';
                const isProductosComprados = this.value === 'Productos comprados';

                document.getElementById('condiciones_temporada').style.display = isTemporada ? 'block' : 'none';
                document.getElementById('condiciones_cantidad_compras').style.display = isCantidadCompras ? 'block' : 'none';
                document.getElementById('condiciones_productos_comprados').style.display = isProductosComprados ? 'block' : 'none';
            });
        });

        document.getElementById('btnAgregarPromocion').addEventListener('click', function() {
            const form = document.getElementById('formPromocion');
            const tipoOferta = document.getElementById('tipo_oferta').value;
            const valorOferta = parseFloat(document.getElementById('valor_oferta').value) || 0;
            const tipoCondicion = document.querySelector('input[name="tipo_condicion"]:checked').value;

            // Valores específicos según la condición seleccionada
            const despliegue = document.getElementById('despliegue') ? document.getElementById('despliegue').value : null;
            const expiracion = document.getElementById('expiracion') ? document.getElementById('expiracion').value : null;
            const cantidadCompras = document.getElementById('cantidad_compras') ? parseInt(document.getElementById('cantidad_compras').value) || null : null;
            const cantidadProductos = document.getElementById('cantidad_productos') ? parseInt(document.getElementById('cantidad_productos').value) || null : null;

            console.log('Valores obtenidos del formulario:');
            console.log('Tipo de oferta:', tipoOferta);
            console.log('Valor de oferta:', valorOferta);
            console.log('Tipo de condición:', tipoCondicion);
            console.log('Rango de validación:', despliegue);
            console.log('Expiración:', expiracion);
            console.log('Cantidad de compras:', cantidadCompras);
            console.log('Cantidad de productos:', cantidadProductos);

            // Validación de que los valores sean mayores que 0
            if (valorOferta <= 0) {
                Swal.fire({
                    title: 'Error en el valor de la oferta',
                    text: 'El valor de la oferta debe ser mayor que 0.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            if (tipoCondicion === 'Cantidad de compras' && cantidadCompras <= 0) {
                Swal.fire({
                    title: 'Error en la cantidad de compras',
                    text: 'La cantidad de compras debe ser mayor que 0.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            if (tipoCondicion === 'Productos comprados' && cantidadProductos <= 0) {
                Swal.fire({
                    title: 'Error en la cantidad de productos',
                    text: 'La cantidad de productos debe ser mayor que 0.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            // Validación del valor de la oferta
            if ((tipoOferta === 'Canjeable' && (valorOferta < 50 || valorOferta > 1000)) ||
                (tipoOferta === 'Porcentual' && (valorOferta < 5 || valorOferta > 70))) {
                Swal.fire({
                    title: 'Error en el valor de la oferta',
                    text: tipoOferta === 'Canjeable' ?
                        'El valor debe estar entre 50 y 1,000 MXN.' : 'El porcentaje de descuento debe estar entre 5% y 70%.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            // Validación del tipo de condición
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
            } else if (tipoCondicion === 'Cantidad de compras') {
                if (cantidadCompras < 200 || cantidadCompras > 200000) {
                    Swal.fire({
                        title: 'Error en la cantidad de compras',
                        text: 'La cantidad debe estar entre 200 y 200,000.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }
            } else if (tipoCondicion === 'Productos comprados') {
                if (cantidadProductos < 1 || cantidadProductos > 100) {
                    Swal.fire({
                        title: 'Error en la cantidad de productos',
                        text: 'La cantidad debe estar entre 1 y 100.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }
            }

            // Confirmación final
            if (form.checkValidity()) {
                Swal.fire({
                    title: '¿Agregar Promoción?',
                    text: "¿Deseas registrar esta promoción?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, agregar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else {
                form.reportValidity();
            }
        });



        document.querySelectorAll('.btnEliminarPromocion').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.href;

                Swal.fire({
                    title: '¿Eliminar Promoción?',
                    text: "Esta acción no se puede deshacer.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });
    </script>
</body>

</html>