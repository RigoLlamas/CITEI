<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Promoción</title>
    <script src="../js/pie.js" defer></script>
    <script src="../js/navbar.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php
    // Incluir archivos necesarios
    include '../php/conexion.php';

    // Obtener el ID de la oferta desde la URL
    if (!isset($_GET['oferta']) || !is_numeric($_GET['oferta'])) {
        echo "<script>
        Swal.fire({
            title: 'Error',
            text: 'No se especificó una oferta válida.',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        }).then(() => {
            window.location.href = 'gestionar_promociones.php';
        });
    </script>";
        exit;
    }
    $oferta_id = (int)$_GET['oferta'];

    if (!$oferta_id) {
        echo "<script>
        Swal.fire({
            title: 'Error',
            text: 'La oferta especificada no existe.',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        }).then(() => {
            window.location.href = 'gestionar_promociones.php';
        });
    </script>";
        exit;
    }


    // Consultar los detalles de la oferta
    $sql_detalle = "
    SELECT o.Oferta, 
       o.Tipo AS TipoOferta, 
       o.Valor AS ValorOferta, 
       o.Despliegue, 
       o.Condicion, 
       o.Producto,
       c.Tipo AS TipoCondicion, 
       c.Valor AS ValorCondicion, 
       c.LimiteTiempo AS Expiracion, 
       c.CantidadUsos,
       IF(o.Producto IS NULL, 'General', p.Nombre) AS ProductoNombre
FROM ofertas o
LEFT JOIN condiciones c ON o.Condicion = c.Condicion
LEFT JOIN producto p ON o.Producto = p.PK_Producto
WHERE o.Oferta = ?
";
    $stmt = mysqli_prepare($conexion, $sql_detalle);
    mysqli_stmt_bind_param($stmt, 'i', $oferta_id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $oferta = mysqli_fetch_assoc($resultado);

    // Actualizar la oferta si se envió el formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre_producto = $conexion->real_escape_string($_POST['nombre_producto']);
        $tipo_oferta = $conexion->real_escape_string($_POST['tipo_oferta']);
        $valor_oferta = floatval($_POST['valor_oferta']);
        $tipo_condicion = $conexion->real_escape_string($_POST['tipo_condicion']);
        $despliegue = !empty($_POST['despliegue']) ? $conexion->real_escape_string($_POST['despliegue']) : date('Y-m-d');
        $expiracion = !empty($_POST['expiracion']) ? $conexion->real_escape_string($_POST['expiracion']) : null;
        $cantidad_usos = isset($_POST['cantidad_usos']) ? intval($_POST['cantidad_usos']) : null;

        if (empty($tipo_oferta) || empty($valor_oferta) || empty($despliegue)) {
            echo "<script>
            Swal.fire({
                title: 'Error',
                text: 'Faltan campos obligatorios.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        </script>";
            exit;
        }

        $stmt_condicion = null;
        // Actualizar la condición o crear una nueva
        if ($oferta['Condicion'] != null) {
            $query_condicion = "UPDATE condiciones SET Tipo = ?, Valor = ?, LimiteTiempo = ?, CantidadUsos = ? WHERE Condicion = ?";
            $stmt_condicion = mysqli_prepare($conexion, $query_condicion);
            mysqli_stmt_bind_param($stmt_condicion, 'sisii', $tipo_condicion, $valor_oferta, $expiracion, $cantidad_usos, $oferta['Condicion']);
        } else {
            if ($tipo_condicion === 'Temporada') {
                $expiracion = $conexion->real_escape_string($_POST['expiracion']);
                $query_condicion = "INSERT INTO condiciones (Tipo, LimiteTiempo)
                                VALUES (?, ?)";
                $stmt_condicion = mysqli_prepare($conexion, $query_condicion);
                mysqli_stmt_bind_param($stmt_condicion, 'ss', $tipo_condicion, $expiracion);
            } else {
                if ($tipo_condicion === 'Productos comprados') {
                    $valor_condicion = isset($_POST['cantidad_productos']) ? intval($_POST['cantidad_productos']) : null;
                } elseif ($tipo_condicion === 'Cantidad de compras') {
                    $valor_condicion = isset($_POST['cantidad_compras']) ? intval($_POST['cantidad_compras']) : null;
                } else {
                    $valor_condicion = null;
                }
                $query_condicion = "INSERT INTO condiciones (Tipo, Valor, CantidadUsos)
                                VALUES (?, ?, ?)";
                $stmt_condicion = mysqli_prepare($conexion, $query_condicion);
                mysqli_stmt_bind_param($stmt_condicion, 'sii', $tipo_condicion, $valor_condicion, $cantidad_usos);
            }
        }

        if ($stmt_condicion && mysqli_stmt_execute($stmt_condicion)) {

            $condicion_id = $oferta['Condicion'] ?? mysqli_insert_id($conexion);

            // Generar descripción automática
            $producto = $oferta['Producto'];
            $descripcion = "En tu siguiente compra recibirás una oferta de $tipo_oferta con un descuento de $valor_oferta, a partir de $despliegue en productos $nombre_producto.";

            // Actualizar la oferta 
            $query_oferta = "UPDATE ofertas SET Tipo = ?, Valor = ?, Despliegue = ?, Condicion = ?, Descripcion = ? WHERE Oferta = ?";
            $stmt_oferta = mysqli_prepare($conexion, $query_oferta);
            mysqli_stmt_bind_param($stmt_oferta, 'sdsisi', $tipo_oferta, $valor_oferta, $despliegue, $condicion_id, $descripcion, $oferta_id);


            if ($stmt_oferta && mysqli_stmt_execute($stmt_oferta)) {
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




    <h1 style="text-align: center;">Modificar Promoción</h1>
    <div class="contenedor-producto cuadro">
        <form id="formPromocion" action="modificar_oferta.php?oferta=<?php echo $oferta_id; ?>" method="POST">
            <!-- Producto (Solo Lectura) -->
            <div>
                <label for="nombre_producto">Producto:</label>
                <input type="hidden" id="nombre_producto" name="nombre_producto" value="<?php echo $oferta['ProductoNombre'] ?>">
                <input type="text" value="<?php echo $oferta['ProductoNombre'] ?>" disabled>
            </div>
            <!-- Tipo de oferta y valor -->
            <div class="dos-columnas" style="margin-bottom: 1rem;">
                <div>
                    <label for="tipo_oferta">Tipo de Oferta:</label>
                    <select id="tipo_oferta" name="tipo_oferta" required>
                        <option value="Canjeable" <?php echo ($oferta['TipoOferta'] === 'Canjeable') ? 'selected' : ''; ?>>Dinero Canjeable</option>
                        <option value="Porcentual" <?php echo ($oferta['TipoOferta'] === 'Porcentual') ? 'selected' : ''; ?>>Descuento Porcentual</option>
                    </select>
                </div>
                <div>
                    <label for="valor_oferta">Valor de la Oferta:</label>
                    <input type="number" id="valor_oferta" name="valor_oferta" value="<?php echo $oferta['ValorOferta'] ?>" required>
                </div>
            </div>

            <!-- Selección de tipo de condición -->
            <div style="margin-bottom: 1rem;">
                <label>Selecciona el tipo de condición:</label>
                <div>
                    <input type="radio" id="condicion_temporada" name="tipo_condicion" value="Temporada"
                        <?php echo ($oferta['TipoCondicion'] === 'Temporada') ? 'checked' : ''; ?>>
                    <label for="condicion_temporada">Condición de Temporada</label>
                </div>
                <div>
                    <input type="radio" id="condicion_cantidad_compras" name="tipo_condicion" value="Cantidad de compras"
                        <?php echo ($oferta['TipoCondicion'] === 'Cantidad de compras') ? 'checked' : ''; ?>>
                    <label for="condicion_cantidad_compras">Condición de Cantidad de Compras</label>
                </div>
                <div>
                    <input type="radio" id="condicion_productos_comprados" name="tipo_condicion" value="Productos comprados"
                        <?php echo ($oferta['TipoCondicion'] === 'Productos comprados') ? 'checked' : ''; ?>>
                    <label for="condicion_productos_comprados">Condición de Productos Comprados</label>
                </div>
            </div>

            <!-- Campos dinámicos según la condición -->
            <div id="condiciones_temporada" style="display: <?php echo ($oferta['TipoCondicion'] === 'Temporada') ? 'block' : 'none'; ?>;" require>
                <div class="dos-columnas">
                    <div>
                        <label for="despliegue">Tiempo de Despliegue:</label>
                        <input type="date" id="despliegue" name="despliegue"
                            value="<?php echo htmlspecialchars($oferta['Despliegue'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div>
                        <label for="expiracion">Tiempo de Expiración:</label>
                        <input type="date" id="expiracion" name="expiracion"
                            value="<?php echo $oferta['Expiracion'] ?>">
                    </div>
                </div>
            </div>

            <div id="condiciones_cantidad_compras" style="display: <?php echo ($oferta['TipoCondicion'] === 'Cantidad de compras') ? 'block' : 'none'; ?>;" require>
                <div class="dos-columnas">
                    <div>
                        <label for="cantidad_compras">Cantidad de Compra:</label>
                        <input type="number" id="cantidad_compras" name="cantidad_compras"
                            value="<?php echo $oferta['ValorCondicion'] ?>">
                    </div>
                    <div>
                        <label for="cantidad_usos">Límite de Uso:</label>
                        <input type="number" id="cantidad_usos" name="cantidad_usos" style="width: 100%;" placeholder="Límite de uso para esta condición">
                    </div>
                </div>
            </div>

            <div id="condiciones_productos_comprados" style="display: <?php echo ($oferta['TipoCondicion'] === 'Productos comprados') ? 'block' : 'none'; ?>;" require>
                <div class="dos-columnas">
                    <div>
                        <label for="cantidad_productos">Cantidad de Productos:</label>
                        <input type="number" id="cantidad_productos" name="cantidad_productos"
                            value="<?php echo $oferta['ValorCondicion'] ?>">
                    </div>
                    <div>
                        <label for="cantidad_usos">Límite de Uso:</label>
                        <input type="number" id="cantidad_usos" name="cantidad_usos" style="width: 100%;" placeholder="Límite de uso para esta condición">
                    </div>
                </div>
            </div>

            <!-- Botón de guardar -->
            <div class="agregar_productos__accion" style="margin-bottom: 1rem;">
                <button type="submit">Guardar Cambios</button>
                <button type="button" class="btnBajarOferta" data-url="bajar_oferta.php?oferta=<?php echo $oferta['Oferta']; ?>">
                    Bajar Oferta
                </button>

            </div>
        </form>
    </div>

    <script>
        document.querySelectorAll('.btnBajarOferta').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                const url = this.getAttribute('data-url'); // Obtener la URL del atributo data-url

                Swal.fire({
                    title: '¿Bajar Oferta?',
                    text: "¿Estás seguro de que deseas bajar esta oferta? Esta acción no se puede deshacer.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, bajar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url; // Redirigir a la URL si se confirma
                    }
                });
            });
        });


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

        // Validar antes de enviar el formulario
        document.getElementById('formPromocion').addEventListener('submit', function(e) {
            e.preventDefault(); // Prevenir envío inmediato del formulario

            // Verificar si hay alguna condición seleccionada
            const selecciono = document.querySelector('input[name="tipo_condicion"]:checked');
            if (!selecciono) {
                Swal.fire({
                    title: 'Error',
                    text: 'Debes seleccionar una condición antes de enviar el formulario.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
                return; // Salir del flujo si no hay selección
            }

            // Obtener valores de los campos
            const tipoOferta = document.getElementById('tipo_oferta').value;
            const valorOferta = parseFloat(document.getElementById('valor_oferta').value) || 0;
            const tipoCondicion = document.querySelector('input[name="tipo_condicion"]:checked').value;

            // Valores específicos según la condición seleccionada
            const despliegue = document.getElementById('despliegue')?.value || null;
            const expiracion = document.getElementById('expiracion')?.value || null;
            const cantidadCompras = document.getElementById('cantidad_compras')?.value || null;
            const cantidadProductos = document.getElementById('cantidad_productos')?.value || null;

            // Validaciones básicas
            if (valorOferta <= 0) {
                Swal.fire({
                    title: 'Error en el Valor de la Oferta',
                    text: 'El valor de la oferta debe ser mayor que 0.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            // Validaciones según el tipo de condición
            if (tipoCondicion === 'Temporada') {
                if (!despliegue || !expiracion) {
                    Swal.fire({
                        title: 'Error en las Fechas',
                        text: 'Debes completar tanto el tiempo de despliegue como la fecha de expiración.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }
                if (new Date(despliegue) >= new Date(expiracion)) {
                    Swal.fire({
                        title: 'Error en las Fechas',
                        text: 'La fecha de expiración debe ser posterior al tiempo de despliegue.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }
            }

            if (tipoCondicion === 'Cantidad de compras' && (!cantidadCompras || cantidadCompras <= 0)) {
                Swal.fire({
                    title: 'Error en la Cantidad de Compras',
                    text: 'La cantidad de compras debe ser mayor que 0.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            if (tipoCondicion === 'Productos comprados' && (!cantidadProductos || cantidadProductos <= 0)) {
                Swal.fire({
                    title: 'Error en la Cantidad de Productos',
                    text: 'La cantidad de productos debe ser mayor que 0.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            // Confirmación con SweetAlert
            Swal.fire({
                title: '¿Guardar Cambios?',
                text: '¿Estás seguro de que deseas guardar los cambios en esta promoción?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    e.target.submit(); // Enviar el formulario si se confirma
                }
            });
        });
    </script>

</body>

</html>