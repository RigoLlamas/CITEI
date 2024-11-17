<?php
// Incluir archivos necesarios
include '../php/conexion.php';
include '../scripts/verificar_dia.php';

// Consulta para obtener lista de promociones
$sql_lista = "
    SELECT o.Oferta, o.Tipo, o.Valor, o.Despliegue, o.Expiracion, IF(o.Producto IS NULL, 'General', p.Nombre) AS ProductoNombre
    FROM ofertas o
    LEFT JOIN producto p ON o.Producto = p.PK_Producto
";
$resultado = mysqli_query($conexion, $sql_lista);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir datos del formulario
    $producto = $_POST['producto'] ?? "NULL";
    $tipo_oferta = $_POST['canjeable_porcentual'];
    $valor_oferta = (float) $_POST['valor'];
    $despliegue = $conexion->real_escape_string(trim($_POST['despliegue']));
    $expiracion = $conexion->real_escape_string(trim($_POST['expiracion']));
    $tipo_condicion = $conexion->real_escape_string(trim($_POST['tipo_condicion']));
    $valor_condicion = (int) $_POST['valor_condicion'];

    // Obtener nombre del producto
    if ($producto != "NULL") {
        $stmt = mysqli_prepare($conexion, "SELECT Nombre FROM producto WHERE PK_Producto = ?");
        mysqli_stmt_bind_param($stmt, 'i', $producto);
        mysqli_stmt_execute($stmt);
        $nombre_producto = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['Nombre'] ?? "Producto desconocido";
        mysqli_stmt_close($stmt);
    } else {
        $nombre_producto = "todos los productos"; // General
    }

    // Generar descripción
    $descripcion_oferta = "En tu siguiente compra recibirás una oferta de $tipo_oferta con un descuento $valor_oferta, del $despliegue hasta el $expiracion en $nombre_producto.";

    // Transacción
    mysqli_begin_transaction($conexion);

    try {
        // Insertar condición
        $sql_condicion = "INSERT INTO condiciones (Tipo, Valor) VALUES (?, ?)";
        $stmt_condicion = mysqli_prepare($conexion, $sql_condicion);
        mysqli_stmt_bind_param($stmt_condicion, 'si', $tipo_condicion, $valor_condicion);
        $condicionInsertada = mysqli_stmt_execute($stmt_condicion);
        if (!mysqli_stmt_execute($stmt_condicion)) {
            throw new Exception("Error al insertar condición: " . mysqli_error($conexion));
        }

        $condicion_id = mysqli_insert_id($conexion);

        // Insertar oferta
        $sql_oferta = "INSERT INTO ofertas (Tipo, Valor, Despliegue, Expiracion, Condicion, Producto, Descripcion) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_oferta = mysqli_prepare($conexion, $sql_oferta);
        mysqli_stmt_bind_param($stmt_oferta, 'sdssiss', $tipo_oferta, $valor_oferta, $despliegue, $expiracion, $condicion_id, $producto, $descripcion_oferta);
        $ofertaInsertada = mysqli_stmt_execute($stmt_oferta);
        if (!mysqli_stmt_execute($stmt_oferta)) {
            throw new Exception("Error al insertar oferta: " . mysqli_error($conexion));
        }

        if ($condicionInsertada && $ofertaInsertada) {
            mysqli_commit($conexion);
            header('Location: gestionar_promociones.php?success=true');
            exit;
        } else {
            throw new Exception('Error al insertar datos');
        }

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        echo "Error: " . $e->getMessage();
    }

    // Cerrar conexiones
    if (isset($stmt_oferta)) mysqli_stmt_close($stmt_oferta);
    if (isset($stmt_condicion)) mysqli_stmt_close($stmt_condicion);
    mysqli_close($conexion);

    if ($insercionExitosa) { // Suponiendo que esta variable indica que la oferta se agregó correctamente
        header('Location: gestionar_promociones.php?success=true');
        exit;
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Gestionar Promociones</title>
    <script src="../js/pie.js" defer></script>
    <script src="../js/navbar.js" defer></script>
    <script src="promociones.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/sweetalert.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectCanjeablePorcentual = document.getElementById('canjeable_porcentual');
            const valorInput = document.getElementById('valor');
            const tipoCondicion = document.getElementById("tipo_condicion");
            const valorCondicion = document.getElementById("valor_condicion");

            // Función para ajustar el valor máximo del input "valor"
            function ajustarValorMaximo() {
                let maxValue = 0;
                if (selectCanjeablePorcentual.value === 'Canjeable') {
                    maxValue = 1000;
                } else if (selectCanjeablePorcentual.value === 'Porcentual') {
                    maxValue = 100;
                }

                // Actualiza el límite máximo y valida el valor actual
                valorInput.oninput = function () {
                    let value = parseFloat(valorInput.value);
                    if (value < 1) valorInput.value = 1;
                    if (value > maxValue) valorInput.value = maxValue;
                };
            }

            // Función para habilitar o deshabilitar el valor de condición
            function ajustarValorCondicion() {
                if (tipoCondicion.value === "Temporada") {
                    valorCondicion.disabled = true;
                    valorCondicion.value = ""; // Limpia el valor si está deshabilitado
                } else {
                    valorCondicion.disabled = false;
                }
            }

            // Inicializar funciones al cargar la página
            ajustarValorMaximo();
            ajustarValorCondicion();

            // Agregar eventos
            selectCanjeablePorcentual.addEventListener('change', ajustarValorMaximo);
            tipoCondicion.addEventListener('change', ajustarValorCondicion);
        });
    </script>
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
    <form action="gestionar_promociones.php" method="POST">

        <!-- Selección de Producto o General -->
        <div style="display: flex; flex-direction: row;">
            <div style="width: 50%;">
                <p style="text-align: left;" for="producto">Oferta para Producto Específico o General:</p>
                <select id="producto" name="producto">
                    <option value="NULL">General</option>
                    <?php
                    // Actualizar consulta para incluir solo productos visibles
                    $query_productos = "SELECT PK_Producto, Nombre FROM producto WHERE Visibilidad = 1";
                    $result_productos = mysqli_query($conexion, $query_productos);
                    if ($result_productos) {
                        while ($row = mysqli_fetch_assoc($result_productos)) {
                            echo "<option value='".$row['PK_Producto']."'>".$row['Nombre']."</option>";
                        }
                    } else {
                        echo "<option value='NULL'>No se encontraron productos</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Selección de tipo de oferta -->
        <div style="display: flex; flex-direction: row; margin-top: 20px;">
            <div style="width: 40%;">
                <p style="text-align: left;" for="canjeable_porcentual">Canjeable o Porcentual:</p>
                <select id="canjeable_porcentual" name="canjeable_porcentual" required>
                    <option value="Canjeable">Canjeable</option>
                    <option value="Porcentual">Porcentual</option>
                </select>

            </div>
            <div style="width: 40%; margin-left: 5%;">
                <p style="text-align: left;" for="valor">Valor:</p>
                <input type="number" id="valor" name="valor" step="0.01" required>
            </div>
        </div>

        <!-- Fechas de despliegue y expiración -->
        <div style="display: flex; flex-direction: row; margin-top: 20px;">
            <div style="width: 40%;">
                <p style="text-align: left;" for="despliegue">Despliegue (Fecha):</p>
                <input type="date" id="despliegue" name="despliegue" required>
            </div>
            <div style="width: 40%; margin-left: 5%;">
                <p style="text-align: left;" for="expiracion">Expiración (Fecha):</p>
                <input type="date" id="expiracion" name="expiracion" required>
            </div>
        </div>

        <!-- Selección de tipo de condición -->
        <div style="display: flex; flex-direction: row; margin-top: 20px;">
            <div style="width: 50%;">
                <p style="text-align: left;" for="tipo_condicion">Tipo de condición:</p>
                <select id="tipo_condicion" name="tipo_condicion" required onchange="ajustarValorCondicion()">
                    <option value="Cantidad de compras">Cantidad de compras</option>
                    <option value="Productos comprados">Productos comprados</option>
                    <option value="Temporada">Temporada</option>
                </select>
            </div>
            <div style="width: 40%; margin-left: 5%;">
                <p style="text-align: left;" for="valor_condicion">Valor de la condición:</p>
                <input type="number" id="valor_condicion" name="valor_condicion" required>
            </div>
        </div>

        <!-- Botón para agregar la oferta -->
        <div class="botones-condiciones" style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <button type="submit">
                Agregar Promoción
            </button>
        </div>
    </form>
     <!-- Lista dinámica de promociones -->
     <div class="lista-promociones">
        <h3>Promociones registradas</h3>
        <ul id="promociones">
            <?php
            if ($resultado && mysqli_num_rows($resultado) > 0) {
                // Generar la lista de promociones con botones
                while ($oferta = mysqli_fetch_assoc($resultado)) {
                    echo "<li>";
                    echo "Producto: " . $oferta['ProductoNombre'] . " - Tipo: " . $oferta['Tipo'] . " (" . $oferta['Valor'] . ") Despliegue: " . $oferta['Despliegue'] . " Expiración: " . $oferta['Expiracion'];
                    // Botones para modificar, desplegar y eliminar
                    echo "<div>";
                    echo " <a href='modificar_oferta.php?oferta=" . $oferta['Oferta'] . "'>Modificar</a> | ";
                    echo "<a href='desplegar_oferta.php?oferta=" . $oferta['Oferta'] . "'>Desplegar</a> | ";
                    echo "<a href='bajar_oferta.php?oferta=" . $oferta['Oferta'] . "' class='confirmar-accion'>Eliminar</a>";
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

</body>
</html>
