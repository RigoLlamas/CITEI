<?php
// Incluir el archivo de conexión a la base de datos
include '../php/conexion.php';
include '../scripts/verificar_dia.php';

if (isset($_POST['eliminar_oferta'])) {
    $oferta_id = (int)$_POST['eliminar_oferta'];
    $sql = "DELETE FROM ofertas WHERE Oferta = $oferta_id";
    if (mysqli_query($conexion, $sql)) {
        echo "Promoción eliminada correctamente.";
    } else {
        echo "Error al eliminar promoción: " . mysqli_error($conexion);
    }
}


// Consulta para obtener la lista de promociones
$sql_lista = "
    SELECT o.Oferta, o.Tipo, o.Valor, o.Despliegue, o.Expiracion, IF(o.Producto IS NULL, 'General', p.Nombre) AS ProductoNombre
    FROM ofertas o
    LEFT JOIN producto p ON o.Producto = p.PK_Producto
";
$resultado = mysqli_query($conexion, $sql_lista);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $tipo_oferta = trim($conexion->real_escape_string($_POST['canjeable_porcentual']));
    $valor_oferta = (float) $_POST['valor'];
    $despliegue = trim($conexion->real_escape_string($_POST['despliegue']));
    $expiracion = trim($conexion->real_escape_string($_POST['expiracion']));
    $tipo_condicion = trim($conexion->real_escape_string($_POST['tipo_condicion']));
    $valor_condicion = (int) $_POST['valor_condicion'];

    // Determinar si la oferta es para un producto específico o general
    if ($producto == "NULL") {
        $nombre_producto = "todos los productos";  // General
    } else {
        // Obtener el nombre del producto desde la base de datos
        $query_nombre_producto = "SELECT Nombre FROM producto WHERE PK_Producto = ?";
        $stmt_producto = mysqli_prepare($conexion, $query_nombre_producto);
        mysqli_stmt_bind_param($stmt_producto, 'i', $producto);
        mysqli_stmt_execute($stmt_producto);
        mysqli_stmt_bind_result($stmt_producto, $nombre_producto);
        mysqli_stmt_fetch($stmt_producto);
        mysqli_stmt_close($stmt_producto);
    }

    // Generar la descripción automática
    $descripcion_oferta = "En tu siguiente compra recibirás una oferta de $tipo_oferta con un descuento $valor_oferta, del $despliegue hasta el $expiracion en $nombre_producto.";

    // Iniciar transacción para asegurar integridad
    mysqli_begin_transaction($conexion);

    try {
        // 1. Insertar en la tabla condiciones
        $sql_condicion = "INSERT INTO condiciones (Tipo, Valor) 
                          VALUES (?, ?)";
        $stmt_condicion = mysqli_prepare($conexion, $sql_condicion);
        mysqli_stmt_bind_param($stmt_condicion, 'si', $tipo_condicion, $valor_condicion);
        mysqli_stmt_execute($stmt_condicion);
        $condicion_id = mysqli_insert_id($conexion); // Obtener el ID de la condición insertada

        // 2. Insertar en la tabla ofertas con la descripción generada automáticamente
        $sql_oferta = "INSERT INTO ofertas (Tipo, Valor, Despliegue, Expiracion, Condicion, Producto, Descripcion) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_oferta = mysqli_prepare($conexion, $sql_oferta);
        mysqli_stmt_bind_param($stmt_oferta, 'sdssiss', $tipo_oferta, $valor_oferta, $despliegue, $expiracion, $condicion_id, $producto, $descripcion_oferta);
        mysqli_stmt_execute($stmt_oferta);

        // Si todo es correcto, confirmar la transacción
        mysqli_commit($conexion);
        echo "Oferta y condición agregadas con éxito.";
    } catch (Exception $e) {
        // Si algo falla, revertir los cambios
        mysqli_rollback($conexion);
        echo "Error: No se pudieron insertar los datos. " . $e->getMessage();
    }

    // Cerrar las declaraciones preparadas y la conexión
    mysqli_stmt_close($stmt_condicion);
    mysqli_stmt_close($stmt_oferta);
    mysqli_close($conexion);
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
        // Función para habilitar o deshabilitar el valor de condición basado en el tipo de condición
        function ajustarValorCondicion() {
            const tipoCondicion = document.getElementById("tipo_condicion").value;
            const valorCondicion = document.getElementById("valor_condicion");
            
            if (tipoCondicion === "temporada") {
                valorCondicion.disabled = true;   // Deshabilitar para condición "Temporada"
            } else {
                valorCondicion.disabled = false;  // Habilitar para otros tipos
            }
        }

        // Inicializar el estado del campo valor_condicion
        document.addEventListener('DOMContentLoaded', () => {
            ajustarValorCondicion(); // Ejecutar al cargar
        });
    </script>
</head>
<body>
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
                    <option value="canjeable">Canjeable</option>
                    <option value="porcentual">Porcentual</option>
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
                    <option value="cantidad_compras">Cantidad de compras</option>
                    <option value="productos_comprados">Productos comprados</option>
                    <option value="temporada">Temporada</option>
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
                    echo " <a href='modificar_oferta.php?oferta=" . $oferta['Oferta'] . "'>Modificar</a> | ";
                    echo "<a href='desplegar_oferta.php?oferta=" . $oferta['Oferta'] . "'>Desplegar</a> | ";
                    echo "<a href='bajar_oferta.php?oferta=" . $oferta['Oferta'] . "' class='confirmar-accion'>Eliminar</a>";

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
