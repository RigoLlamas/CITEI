<?php 
include '../php/conexion.php';

// Eliminar vehículo si se ha enviado una solicitud para eliminar
if (isset($_POST['eliminar_placa'])) {
    $placa = $conexion->real_escape_string($_POST['eliminar_placa']);
    $sql = "DELETE FROM vehiculo WHERE Placa = '$placa'";
    if (mysqli_query($conexion, $sql)) {
        echo "Vehículo eliminado correctamente.";
    } else {
        echo "Error al eliminar vehículo: " . mysqli_error($conexion);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['placa'])) {
    $placa = trim($conexion->real_escape_string($_POST['placa']));
    $largo = (float) $_POST['largo'];
    $alto = (float) $_POST['alto'];
    $ancho = (float) $_POST['ancho'];
    $modelo = trim($conexion->real_escape_string($_POST['modelo']));
    $estado = trim($conexion->real_escape_string($_POST['estado']));

    // SQL para insertar un nuevo vehículo
    $sql = "INSERT INTO vehiculo (Placa, Largo, Alto, Ancho, Modelo, Estado)
            VALUES ('$placa', $largo, $alto, $ancho, '$modelo', '$estado')";
    mysqli_query($conexion, $sql)
}

// Consulta para obtener la lista de vehículos
$sql_lista = "SELECT Placa, Largo, Alto, Ancho, Modelo, Estado FROM vehiculo";
$resultado = mysqli_query($conexion, $sql_lista);

$sql_contar = "SELECT COUNT(*) as total FROM vehiculo";
$resultado_contar = mysqli_query($conexion, $sql_contar);
$total_vehiculos = mysqli_fetch_assoc($resultado_contar)['total'];

// Si hay 10 o más vehículos, deshabilitar el botón de agregar
$deshabilitar_boton = $total_vehiculos >= 10;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Gestionar Vehículos</title>
    <script src="../js/pie.js"></script>
    <script src="../js/navbar.js"></script>
    <script src="vehiculos.js"></script> 
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/sweetalert.js"></script>
</head>
<body>
    <h2 style="text-align: center;">Gestión de Vehículos</h2>    

    <div class="contenedor-vehiculo cuadro">
        <form action="gestionar_vehiculos.php" method="POST">
            <div style="display: flex; flex-direction: row;">
                <div style="width: 40%;">
                    <p style="text-align: left;" for="placa">Placa:</p>
                    <input type="text" id="placa" name="placa" maxlength="9" required>
                </div>
                <div style="width: 20%; margin-left: 5%;">
                    <p style="text-align: left;" for="largo">Largo (m):</p>
                    <input type="number" id="largo" name="largo" step="0.01" 
                    min = "0.1" max = "20"
                    required>
                </div>
                <div style="width: 20%; margin-left: 5%;">
                    <p style="text-align: left;" for="alto">Alto (m):</p>
                    <input type="number" id="alto" name="alto" step="0.01" 
                    min = "0.1" max = "20"
                    required>
                </div>
                <div style="width: 20%; margin-left: 5%;">
                    <p style="text-align: left;" for="ancho">Ancho (m):</p>
                    <input type="number" id="ancho" name="ancho" step="0.01" 
                    min = "0.1" max = "20"
                    required>
                </div>
            </div>    

            <div style="display: flex; flex-direction: row; margin-top: 20px;">
                <div style="width: 50%;">
                    <p for="modelo">Modelo:</p>
                    <input style="width: 100%;" type="text" id="modelo" name="modelo" 
                    minlength="1" maxlength="50"
                    required>
                </div>
                <div style="width: 20%; margin-left: 5%;">
                    <p style="text-align: left;" for="estado">Estado:</p>
                    <select id="estado" name="estado" required>
                        <option value="En taller">En taller</option>
                        <option value="En circulacion">En circulación</option>
                    </select>
                </div>
            </div>

            <div class="botones-vehiculos" style="margin-top: 20px; display: flex; justify-content: flex-end;">
                <button type="submit" <?php if ($deshabilitar_boton) echo 'disabled'; ?>>
                    Agregar Vehículo
                </button>
            </div>
        </form>

        <!-- Lista dinámica de vehículos -->
        <div class="lista-vehiculos">
            <h3>Vehículos registrados</h3>
            <ul id="vehiculos">
                <?php
                if ($resultado && mysqli_num_rows($resultado) > 0) {
                    // Generar la lista de vehículos con botones
                    while ($vehiculo = mysqli_fetch_assoc($resultado)) {
                        echo "<li>";
                        echo "Placa: " . $vehiculo['Placa'] . " - Modelo: " . $vehiculo['Modelo'] . " (Estado: " . $vehiculo['Estado'] . ")";
                        // Botones para modificar y eliminar
                        echo " <a href='modificar_vehiculo.php?placa=" . $vehiculo['Placa'] . "'>Modificar</a> | ";
                        echo "<a href='eliminar_vehiculo.php?placa=" . $vehiculo['Placa'] . "' class='confirmar-accion'>Eliminar</a>";
                        echo "</li>";
                    }
                } else {
                    echo "<li>No hay vehículos registrados.</li>";
                }
                ?>
            </ul>
        </div>
    </div>
</body>
</html>

<?php 
mysqli_close($conexion);
?>
