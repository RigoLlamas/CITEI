<?php
include '../php/conexion.php';
include '../php/solo_admins.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nomina = (float)$_POST['nomina'];
    $nombre = $_POST['nombre'];
    $apellidos = $_POST['apellidos'];
    $estado = $_POST['estado'];
    $clave = $_POST['clave'];
    $horabandera = $_POST['horabandera'];

    // Preparar la consulta base para actualizar el repartidor
    $sql = "UPDATE repartidor SET Nombre = ?, Apellidos = ?, Estado = ?, HoraBandera = ?";

    // Agregar la clave si no está vacía
    if (!empty($clave)) {
        $sql .= ", Clave = ?";
    }

    $sql .= " WHERE Nomina = ?";

    // Preparar la consulta
    $stmt = $conexion->prepare($sql);

    // Vincular los parámetros según si la clave es enviada o no
    if (!empty($clave)) {
        $stmt->bind_param("sssssi", $nombre, $apellidos, $estado, $horabandera, $clave, $nomina);
    } else {
        $stmt->bind_param("ssssi", $nombre, $apellidos, $estado, $horabandera, $nomina);
    }

    // Ejecutar la consulta
    if ($stmt->execute()) {
        header('Location: gestionar_repartidores.php?success=true');
        exit();
    } else {
        $error = "Error al actualizar el repartidor: " . $stmt->error;
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
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>

        <body>
            <h2 style="text-align: center;">Modificar Repartidor</h2>
            <div class="contenedor-repartidor cuadro">
                <form id="formModificarRepartidor" action="modificar_repartidor.php" method="POST">
                    <input type="hidden" name="nomina" value="<?php echo $repartidor['Nomina']; ?>">

                    <div style="display: flex; flex-direction: row;">
                        <div style="width: 80%;">
                            <p style="text-align: left;">Nómina:</p>
                            <input type="number" value="<?php echo $repartidor['Nomina']; ?>" disabled>
                        </div>
                        <div style="width: 20%;">
                            <p style="text-align: left;">Estado:</p>
                            <select id="estado" name="estado" required>
                                <option value="Disponible" <?php if ($repartidor['Estado'] == 'Disponible') echo 'selected'; ?>>Disponible</option>
                                <option value="Ocupado" <?php if ($repartidor['Estado'] == 'Ocupado') echo 'selected'; ?>>Ocupado</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: row;">
                        <div style="width: 50%;">
                            <p>Nombre:</p>
                            <input type="text" name="nombre" value="<?php echo $repartidor['Nombre']; ?>" minlength="1" maxlength="150" required>
                        </div>
                        <div style="width: 50%; margin-left: 5%;">
                            <p>Apellidos:</p>
                            <input type="text" name="apellidos" value="<?php echo $repartidor['Apellidos']; ?>" minlength="1" maxlength="150" required>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: row;">
                        <div style="width: 30%;">
                            <p>Clave:</p>
                            <input type="password" name="clave" minlength="5" maxlength="50">
                        </div>
                        <div style="margin-top: 30px;">
                            <label for="horabandera">Asignar descanso después de la hora:</label>
                            <input type="time" id="horabandera" name="horabandera" min="09:00" max="16:00" required
                                value="<?php echo isset($repartidor['HoraBandera']) ? $repartidor['HoraBandera'] : ''; ?>">
                        </div>
                        <div style="width: 40%;" class="botones-repartidores">
                            <button type="button" id="btnGuardarCambios">Guardar Cambios</button>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (isset($error)): ?>
                <script>
                    Swal.fire({
                        title: 'Error',
                        text: "<?php echo $error; ?>",
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                </script>
            <?php endif; ?>

            <script>
                document.getElementById('btnGuardarCambios').addEventListener('click', function() {
                    Swal.fire({
                        title: '¿Guardar Cambios?',
                        text: "¿Estás seguro de que deseas guardar los cambios realizados?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, guardar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('formModificarRepartidor').submit();
                        }
                    });
                });
            </script>
        </body>

        </html>
<?php
    } else {
        echo "<script>
            Swal.fire({
                title: 'Error',
                text: 'No se encontró el repartidor.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        </script>";
    }
    mysqli_close($conexion);
}
?>