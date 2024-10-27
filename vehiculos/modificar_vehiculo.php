<?php
include '../php/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Actualizar los datos del vehículo
    $placa = $conexion->real_escape_string($_POST['placa']);
    $largo = (float)$_POST['largo'];
    $alto = (float)$_POST['alto'];
    $ancho = (float)$_POST['ancho'];
    $modelo = $conexion->real_escape_string($_POST['modelo']);
    $estado = $conexion->real_escape_string($_POST['estado']);

    // Consulta para modificar el vehículo
    $sql = "UPDATE vehiculo SET Largo = $largo, Alto = $alto, Ancho = $ancho, Modelo = '$modelo', Estado = '$estado' WHERE Placa = '$placa'";
    mysqli_query($conexion, $sql);
    mysqli_close($conexion);

    // Redirigir a la página principal después de la modificación
    header("Location: gestionar_vehiculos.php");
    exit();
}

// Verificar si se ha recibido la placa del vehículo
if (isset($_GET['placa'])) {
    $placa = $conexion->real_escape_string($_GET['placa']);

    // Consulta para obtener los datos del vehículo
    $sql = "SELECT * FROM vehiculo WHERE Placa = '$placa'";
    $resultado = mysqli_query($conexion, $sql);

    // Mostrar el formulario con los datos actuales del vehículo
    if ($vehiculo = mysqli_fetch_assoc($resultado)) {
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Modificar Vehículo</title>
            <script src="../js/pie.js"></script>
            <script src="../js/navbar.js"></script>
            <script src="vehiculos.js"></script> 
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script src="../js/sweetalert.js"></script>
        </head>
        <body>
            <h2 style="text-align: center;">Modificar Vehículo</h2>
            <div class="contenedor-vehiculo cuadro">
                <form action="modificar_vehiculo.php" method="POST">
                    <input type="hidden" name="placa" value="<?php echo $vehiculo['Placa']; ?>">
                    
                    <div style="display: flex; flex-direction: row;">
                        <div style="width: 40%;">
                            <p for="largo">Largo (m):</p>
                            <input type="number" name="largo" value="<?php echo $vehiculo['Largo']; ?>" step="0.01" required>
                        </div>
                        <div style="width: 20%; margin-left: 5%;">
                            <p for="alto">Alto (m):</p>
                            <input type="number" name="alto" value="<?php echo $vehiculo['Alto']; ?>" step="0.01" required>
                        </div>
                        <div style="width: 20%; margin-left: 5%;">
                            <p for="ancho">Ancho (m):</p>
                            <input type="number" name="ancho" value="<?php echo $vehiculo['Ancho']; ?>" step="0.01" required>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: row; margin-top: 20px;">
                        <div style="width: 50%;">
                            <p for="modelo">Modelo:</p>
                            <input style="width: 100%;" type="text" name="modelo" value="<?php echo $vehiculo['Modelo']; ?>" minlength="1" maxlength="50" required>
                        </div>
                        <div style="width: 20%; margin-left: 5%;">
                            <p for="estado">Estado:</p>
                            <select name="estado" required>
                                <option value="En taller" <?php if ($vehiculo['Estado'] == 'En taller') echo 'selected'; ?>>En taller</option>
                                <option value="En circulacion" <?php if ($vehiculo['Estado'] == 'En circulacion') echo 'selected'; ?>>En circulación</option>
                            </select>
                        </div>
                    </div>

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