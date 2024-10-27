<?php
include '../php/conexion.php';

// Obtener el ID de la oferta a modificar desde la URL
if (isset($_GET['oferta'])) {
    $oferta_id = (int)$_GET['oferta'];

    // Obtener los detalles de la oferta desde la base de datos
    $sql_oferta = "SELECT * FROM ofertas WHERE Oferta = ?";
    $stmt_oferta = mysqli_prepare($conexion, $sql_oferta);
    mysqli_stmt_bind_param($stmt_oferta, 'i', $oferta_id);
    mysqli_stmt_execute($stmt_oferta);
    $result_oferta = mysqli_stmt_get_result($stmt_oferta);
    $oferta = mysqli_fetch_assoc($result_oferta);

    if (!$oferta) {
        die('Error: Oferta no encontrada.');
    }
}

// Procesar el formulario de modificación si se ha enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los valores actualizados del formulario
    $tipo_oferta = trim($conexion->real_escape_string($_POST['canjeable_porcentual']));
    $valor_oferta = (float)$_POST['valor'];
    $despliegue = trim($conexion->real_escape_string($_POST['despliegue']));
    $expiracion = trim($conexion->real_escape_string($_POST['expiracion']));
    $producto = $_POST['producto'] === 'NULL' ? 'NULL' : (int)$_POST['producto'];

    // Actualizar la oferta en la base de datos
    $sql_update = "UPDATE ofertas 
                   SET Tipo = ?, Valor = ?, Despliegue = ?, Expiracion = ?, Producto = ?
                   WHERE Oferta = ?";
    $stmt_update = mysqli_prepare($conexion, $sql_update);
    mysqli_stmt_bind_param($stmt_update, 'sdssii', $tipo_oferta, $valor_oferta, $despliegue, $expiracion, $producto, $oferta_id);

    if (mysqli_stmt_execute($stmt_update)) {
        header('Location: ../promociones/gestionar_promociones.php');
    } else {
        echo "Error al modificar la oferta: " . mysqli_error($conexion);
    }

    mysqli_stmt_close($stmt_update);
}

// Obtener la lista de productos
$sql_productos = "SELECT PK_Producto, Nombre FROM producto WHERE Visibilidad = 1";
$result_productos = mysqli_query($conexion, $sql_productos);

mysqli_close($conexion);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Oferta</title>
</head>
<body>

<h2>Modificar Oferta</h2>

<form action="modificar_oferta.php?oferta=<?php echo $oferta_id; ?>" method="POST">
    <div>
        <label for="canjeable_porcentual">Tipo de oferta:</label>
        <select id="canjeable_porcentual" name="canjeable_porcentual">
            <option value="canjeable" <?php echo $oferta['Tipo'] == 'canjeable' ? 'selected' : ''; ?>>Canjeable</option>
            <option value="porcentual" <?php echo $oferta['Tipo'] == 'porcentual' ? 'selected' : ''; ?>>Porcentual</option>
        </select>
    </div>

    <div>
        <label for="valor">Valor de la oferta:</label>
        <input type="number" id="valor" name="valor" step="0.01" value="<?php echo $oferta['Valor']; ?>" required>
    </div>

    <div>
        <label for="despliegue">Fecha de despliegue:</label>
        <input type="date" id="despliegue" name="despliegue" value="<?php echo $oferta['Despliegue']; ?>" required>
    </div>

    <div>
        <label for="expiracion">Fecha de expiración:</label>
        <input type="date" id="expiracion" name="expiracion" value="<?php echo $oferta['Expiracion']; ?>" required>
    </div>

    <div>
        <label for="producto">Producto asociado:</label>
        <select id="producto" name="producto">
            <option value="NULL" <?php echo is_null($oferta['Producto']) ? 'selected' : ''; ?>>General</option>
            <?php while ($producto = mysqli_fetch_assoc($result_productos)) { ?>
                <option value="<?php echo $producto['PK_Producto']; ?>" 
                <?php echo $oferta['Producto'] == $producto['PK_Producto'] ? 'selected' : ''; ?>>
                    <?php echo $producto['Nombre']; ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <button type="submit" id="confirmacion">Guardar Cambios</button>
</form>

</body>
</html>
