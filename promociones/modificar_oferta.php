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
    $tipo_oferta = trim($conexion->real_escape_string($_POST['canjeable_porcentual']));
    $valor_oferta = (float)$_POST['valor'];
    $despliegue = trim($conexion->real_escape_string($_POST['despliegue']));
    $expiracion = trim($conexion->real_escape_string($_POST['expiracion']));

    // Validar los valores ingresados
    if ($tipo_oferta === 'Canjeable' && ($valor_oferta < 1 || $valor_oferta > 1000)) {
        $error = "El valor para 'Canjeable' debe estar entre 1 y 1000.";
    } elseif ($tipo_oferta === 'Porcentual' && ($valor_oferta < 1 || $valor_oferta > 100)) {
        $error = "El valor para 'Porcentual' debe estar entre 1 y 100.";
    } else {
        // Actualizar la oferta en la base de datos
        $sql_update = "UPDATE ofertas 
                       SET Tipo = ?, Valor = ?, Despliegue = ?, Expiracion = ?
                       WHERE Oferta = ?";
        $stmt_update = mysqli_prepare($conexion, $sql_update);
        mysqli_stmt_bind_param($stmt_update, 'sdssi', $tipo_oferta, $valor_oferta, $despliegue, $expiracion, $oferta_id);

        if (mysqli_stmt_execute($stmt_update)) {
            header('Location: gestionar_promociones.php?success=true');
            exit;
        } else {
            $error = "Error al modificar la oferta: " . mysqli_error($conexion);
        }

        mysqli_stmt_close($stmt_update);
    }
}
mysqli_close($conexion);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Oferta</title>
    <script src="../js/pie.js" defer></script>
    <script src="../js/navbar.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php
if (isset($error)) {
    echo "<script>
        Swal.fire({
            title: 'Error',
            text: '$error',
            icon: 'error',
            confirmButtonText: 'Aceptar'
        });
    </script>";
}
?>

<h2>Modificar Oferta</h2>
<div class="contenedor-producto cuadro">
    <form id="formModificarOferta" action="modificar_oferta.php?oferta=<?php echo $oferta_id; ?>" method="POST">
        <!-- Selección de tipo de oferta -->
        <div style="display: flex; flex-direction: row; margin-top: 20px;">
            <div style="width: 40%;">
                <p style="text-align: left;" for="canjeable_porcentual">Tipo de oferta:</p>
                <select id="canjeable_porcentual" name="canjeable_porcentual">
                    <option value="Canjeable" <?php echo $oferta['Tipo'] == 'Canjeable' ? 'selected' : ''; ?>>Canjeable</option>
                    <option value="Porcentual" <?php echo $oferta['Tipo'] == 'Porcentual' ? 'selected' : ''; ?>>Porcentual</option>
                </select>
            </div>
            <div style="width: 40%; margin-left: 5%;">
                <p style="text-align: left;" for="valor">Valor de la oferta:</p>
                <input type="number" id="valor" name="valor" step="0.01" value="<?php echo $oferta['Valor']; ?>" required>
            </div>
        </div>

        <!-- Fechas de despliegue y expiración -->
        <div style="display: flex; flex-direction: row; margin-top: 20px;">
            <div style="width: 40%;">
                <p style="text-align: left;" for="despliegue">Fecha de despliegue:</p>
                <input type="date" id="despliegue" name="despliegue" value="<?php echo $oferta['Despliegue']; ?>" required>
            </div>
            <div style="width: 40%; margin-left: 5%;">
                <p style="text-align: left;" for="expiracion">Fecha de expiración:</p>
                <input type="date" id="expiracion" name="expiracion" value="<?php echo $oferta['Expiracion']; ?>" required>
            </div>
        </div>

        <!-- Botón para guardar cambios -->
        <div class="botones-condiciones" style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <button type="button" id="btnGuardarCambios">Guardar Cambios</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectCanjeablePorcentual = document.getElementById('canjeable_porcentual');
    const valorInput = document.getElementById('valor');

    // Ajustar el valor máximo del input "valor"
    function ajustarValorMaximo() {
        let maxValue = 0;
        if (selectCanjeablePorcentual.value === 'Canjeable') {
            maxValue = 1000;
        } else if (selectCanjeablePorcentual.value === 'Porcentual') {
            maxValue = 100;
        }

        valorInput.oninput = function () {
            let value = parseFloat(valorInput.value);
            if (value < 1) valorInput.value = 1;
            if (value > maxValue) valorInput.value = maxValue;
        };

        // Validar el valor actual
        let currentValue = parseFloat(valorInput.value);
        if (currentValue > maxValue) {
            valorInput.value = maxValue;
        }
    }

    ajustarValorMaximo();
    selectCanjeablePorcentual.addEventListener('change', ajustarValorMaximo);

    // Confirmación antes de enviar el formulario
    document.getElementById('btnGuardarCambios').addEventListener('click', function () {
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
                document.getElementById('formModificarOferta').submit();
            }
        });
    });
});
</script>

</body>
</html>
