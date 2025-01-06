<?php
include '../php/conexion.php';
include '../php/solo_admins.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Actualizar los datos del vehículo usando consultas preparadas
    $placa = $_POST['placa'];
    $largo = (float)$_POST['largo'];
    $alto = (float)$_POST['alto'];
    $ancho = (float)$_POST['ancho'];
    $modelo = $_POST['modelo'];
    $estado = $_POST['estado'];

    $sql = "UPDATE vehiculo SET Largo = ?, Alto = ?, Ancho = ?, Modelo = ?, Estado = ? WHERE Placa = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("dddsss", $largo, $alto, $ancho, $modelo, $estado, $placa);

    if ($stmt->execute()) {
        header('Location: gestionar_vehiculos.php?success=true');
        exit();
    } else {
        echo "Error al actualizar el vehículo: " . $stmt->error;
    }

    $stmt->close();
    $conexion->close();
}

// Verificar si se ha recibido la placa del vehículo
if (isset($_GET['placa'])) {
    $placa = $_GET['placa'];

    // Consulta para obtener los datos del vehículo
    $sql = "SELECT * FROM vehiculo WHERE Placa = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $placa);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Mostrar el formulario con los datos actuales del vehículo
    if ($vehiculo = $resultado->fetch_assoc()) {
?>
        <!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Vehículo</title>
    <script src="../js/pie.js"></script>
    <script src="../js/navbar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <h2 style="text-align: center;">Modificar Vehículo</h2>
    <div class="contenedor-vehiculo cuadro">
        <form id="vehiculoForm" action="modificar_vehiculo.php" method="POST">
            <!-- Campo de Placa -->
            <input type="hidden" id="placa_hidden" name="placa" value="<?php echo $vehiculo['Placa']; ?>">
            <div style="display: flex; flex-direction: row;">
                <div style="width: 60%;">
                    <label for="placa_display">Placas:</label><br>
                    <input type="text" id="placa_display" value="<?php echo $vehiculo['Placa']; ?>" disabled>
                </div>
                <div style="width: 20%; margin-left: 5%;">
                    <label for="largo">Largo de cabina (m):</label><br>
                    <input type="number" id="largo" name="largo" value="<?php echo $vehiculo['Largo']; ?>" step="0.01" 
                    min="0.1" max="20" required>
                </div>
                <div style="width: 20%; margin-left: 5%;">
                    <label for="alto">Alto de cabina (m):</label><br>
                    <input type="number" id="alto" name="alto" value="<?php echo $vehiculo['Alto']; ?>" step="0.01" min="0.1" max="20" required>
                </div>
                <div style="width: 20%; margin-left: 5%;">
                    <label for="ancho">Ancho de cabina (m):</label><br>
                    <input type="number" id="ancho" name="ancho" value="<?php echo $vehiculo['Ancho']; ?>" step="0.01" min="0.1" max="20" required>
                </div>
            </div>

            <!-- Campos de Modelo y Estado -->
            <div style="display: flex; flex-direction: row; margin-top: 20px;">
                <div style="width: 50%;">
                    <label for="modelo">Modelo:</label>
                    <input style="width: 100%;" type="text" id="modelo" name="modelo" value="<?php echo $vehiculo['Modelo']; ?>" minlength="1" maxlength="50" required>
                </div>
                <div style="width: 20%; margin-left: 5%;">
                    <label for="estado">Estado:</label>
                    <select id="estado" name="estado" required>
                        <option value="En taller" <?php if ($vehiculo['Estado'] == 'En taller') echo 'selected'; ?>>En taller</option>
                        <option value="En circulacion" <?php if ($vehiculo['Estado'] == 'En circulacion') echo 'selected'; ?>>En circulación</option>
                    </select>
                </div>
            </div>

            <!-- Botón de Guardar -->
            <div class="botones-vehiculos" style="margin-top: 20px; display: flex; justify-content: flex-end;">
                <button type="submit" id="confirmacion">Guardar Cambios</button>
            </div>
        </form>
    </div>
</body>

</html>

<?php
    } else {
        echo "No se encontró el vehículo.";
    }
    mysqli_close($conexion);
}
?>