<?php
include '../php/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar y obtener los datos enviados
    $nomina = (float)$_POST['nomina'];
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $estado = $_POST['estado'];
    $clave = $_POST['clave'];

    // Preparar la consulta base para actualizar el repartidor
    $sql = "UPDATE repartidor SET Nombre = ?, Apellidos = ?, Estado = ?";

    // Agregar la clave si no está vacía o nula
    if (!empty($clave)) {
        $sql .= ", Clave = ?";
    }

    $sql .= " WHERE Nomina = ?";

    // Preparar la consulta
    $stmt = $conexion->prepare($sql);

    // Vincular los parámetros según si la clave es enviada o no
    if (!empty($clave)) {
        $stmt->bind_param("sssisi", $nombre, $apellidos, $estado, $clave, $nomina);
    } else {
        $stmt->bind_param("sssi", $nombre, $apellidos, $estado, $nomina);
    }

    // Ejecutar la consulta
    if ($stmt->execute()) {
        header('Location: gestionar_repartidores.php?success=true');
        exit();
    } else {
        echo "Error al actualizar el repartidor: " . $stmt->error;
    }

    // Cerrar la declaración y la conexión
    $stmt->close();
    $conexion->close();
}


if (isset($_GET['nomina'])) {
    $nomina = (float)$_GET['nomina'];

    // Consulta para obtener los datos del repartidor
    $sql = "SELECT * FROM repartidor WHERE Nomina = $nomina";
    $resultado = mysqli_query($conexion, $sql);

    // Mostrar el formulario de modificación con los datos actuales del repartidor
    if ($repartidor = mysqli_fetch_assoc($resultado)) {
?>
        <!DOCTYPE html>
        <html lang="es">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Modificar Repartidor</title>
            <script src="../js/pie.js"></script>
            <script src="../js/navbar.js"></script>
            <script src="repartidores.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script src="../js/sweetalert.js"></script>
        </head>

        <body>
            <h2 style="text-align: center;">Modificar Repartidor</h2>
            <div class="contenedor-repartidor cuadro">
                <form action="modificar_repartidor.php" method="POST">
                <input type="hidden" id="nomina" name="nomina" value="<?php echo $repartidor['Nomina']; ?>">

                    <div style="display: flex; flex-direction: row;">
                        <div style="width: 80%;">
                            <p style="text-align: left;" for="nomina">Nómina:</p>
                            <input type="number" id="nomina" name="nomina" value="<?php echo $repartidor['Nomina']; ?>" disabled>
                        </div>
                        <div style="width: 20%;">
                            <p style="text-align: left;" for="estado">Estado:</p>
                            <select id="estado" name="estado" required>
                                <option value="Disponible" <?php if ($repartidor['Estado'] == 'Disponible') echo 'selected'; ?>>Disponible</option>
                                <option value="Ocupado" <?php if ($repartidor['Estado'] == 'Ocupado') echo 'selected'; ?>>Ocupado</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: row;">
                        <div style="width: 50%;">
                            <p for="nombre">Nombre:</p>
                            <input style="width: 100%;" type="text" id="nombre" name="nombre" value="<?php echo $repartidor['Nombre']; ?>" minlength="1" maxlength="150" required>
                        </div>
                        <div style="width: 50%; margin-left: 5%;">
                            <p for="apellidos">Apellidos:</p>
                            <input style="width: 100%;" type="text" id="apellidos" name="apellidos" value="<?php echo $repartidor['Apellidos']; ?>" minlength="1" maxlength="150" required>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: row;">
                        <div style="width: 60%;">
                            <p for="clave">Clave:</p>
                            <input type="password" id="clave" name="clave" minlength="5" maxlength="50" required>
                        </div>
                        <div style="width: 40%;" class="botones-repartidores">
                            <button type="submit" id="confirmacion">Guardar Cambios</button>
                        </div>
                    </div>
                </form>
            </div>
        </body>

        </html>
<?php
    } else {
        echo "No se encontró el repartidor.";
    }
    mysqli_close($conexion);
}
?>