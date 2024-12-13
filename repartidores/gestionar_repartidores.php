<?php
include '../php/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nomina = (float) $_POST['nomina'];
    $nombre = trim($conexion->real_escape_string($_POST['nombre']));
    $apellidos = trim($conexion->real_escape_string($_POST['apellidos']));
    $estado = trim($conexion->real_escape_string($_POST['estado']));
    $clave = trim($conexion->real_escape_string($_POST['clave']));
    $horabandera = trim($conexion->real_escape_string($_POST['horabandera']));

    // Verificar si la nómina ya existe
    $checkNominaSql = "SELECT COUNT(*) AS count FROM repartidor WHERE Nomina = ?";
    $stmt = mysqli_prepare($conexion, $checkNominaSql);
    mysqli_stmt_bind_param($stmt, 'i', $nomina);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    if ($row['count'] > 0) {
        // Redirigir con un mensaje de error si la nómina ya existe
        header('Location: gestionar_repartidores.php?error=duplicate');
        exit();
    } else {
        // La nómina no está registrada, proceder con la inserción
        $sql = "INSERT INTO repartidor (Nomina, Nombre, Apellidos, Estado, Clave, HoraBandera)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, 'isssss', $nomina, $nombre, $apellidos, $estado, $clave, $horabandera);

        if (mysqli_stmt_execute($stmt)) {
            header('Location: gestionar_repartidores.php?success=true');
            exit();
        } else {
            $error = "Error: " . mysqli_error($conexion);
        }
        mysqli_stmt_close($stmt);
    }
}

// Consulta para obtener la lista de repartidores
$sql_lista = "SELECT Nomina, Nombre, Apellidos, Estado FROM repartidor";
$resultado = mysqli_query($conexion, $sql_lista);

$sql_contar = "SELECT COUNT(*) as total FROM repartidor";
$resultado_contar = mysqli_query($conexion, $sql_contar);
$total_repartidores = mysqli_fetch_assoc($resultado_contar)['total'];

// Si hay 10 o más repartidores, deshabilitar el botón de agregar
$deshabilitar_boton = $total_repartidores >= 10;

mysqli_close($conexion);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Gestionar Repartidores</title>
    <script src="../js/pie.js"></script>
    <script src="../js/navbar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <?php
    if (isset($_GET['success']) && $_GET['success'] === 'true') {
        echo "<script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                title: '¡Éxito!',
                text: 'Repartidor agregado correctamente.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
            header('Location: gestionar_repartidores.php');
        });
    </script>";
    }

    if (isset($_GET['error']) && $_GET['error'] === 'duplicate') {
        echo "<script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                title: 'Error',
                text: 'La nómina del repartidor ya está registrada.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        });
    </script>";
    }
    ?>

    <h2 style="text-align: center;">Repartidores</h2>

    <div class="contenedor-repartidor cuadro">
        <form id="formAgregarRepartidor" action="gestionar_repartidores.php" method="POST">
            <div style="display: flex; flex-direction: row;">
                <div style="width: 80%;">
                    <p style="text-align: left;">Nómina:</p>
                    <input type="number" id="nomina" name="nomina" required>
                </div>
                <div style="width: 20%;">
                    <p style="text-align: left;">Estado:</p>
                    <select id="estado" name="estado" required>
                        <option value="Disponible">Disponible</option>
                        <option value="Ocupado">Ocupado</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; flex-direction: row;">
                <div style="width: 50%;">
                    <p>Nombre:</p>
                    <input style="width: 100%;" type="text" id="nombre" name="nombre" minlength="1" maxlength="150" required>
                </div>
                <div style="width: 50%; margin-left: 5%;">
                    <p>Apellidos:</p>
                    <input style="width: 100%;" type="text" id="apellidos" name="apellidos" minlength="1" maxlength="150" required>
                </div>
            </div>

            <div style="display: flex; flex-direction: row;">
                <div style="width: 30%;">
                    <p>Contraseña:</p>
                    <input type="password" id="clave" name="clave" minlength="5" maxlength="50" required>
                </div>
                <div style="width: 30%;">
                    <p>Asignar descanso despues de la hora:</p>
                    <input type="time" id="horabandera" name="horabandera" min="09:00" max="16:00" required>
                </div>
                <div style="width: 40%;" class="botones-repartidores">
                    <button type="button" id="btnAgregarRepartidor" <?php echo $deshabilitar_boton ? 'disabled' : ''; ?>>Agregar <br>Repartidor</button>
                </div>
            </div>
        </form>

        <!-- Lista dinámica de repartidores -->
        <div class="lista-repartidores">
            <h3>Repartidores registrados</h3>
            <ul id="repartidores">
                <?php
                if ($resultado && mysqli_num_rows($resultado) > 0) {
                    while ($repartidor = mysqli_fetch_assoc($resultado)) {
                        echo "<li>";
                        echo "Nómina: " . $repartidor['Nomina'] . " - " . $repartidor['Nombre'] . " " . $repartidor['Apellidos'] . " (Estado: " . $repartidor['Estado'] . ")";
                        echo "<div>";
                        echo "<a href='modificar_repartidor.php?nomina=" . $repartidor['Nomina'] . "'>Modificar</a> | ";
                        echo "<a href='eliminar_repartidor.php?nomina=" . $repartidor['Nomina'] . "' class='btnEliminarRepartidor'>Eliminar</a>";
                        echo "</div>";
                        echo "</li>";
                    }
                } else {
                    echo "<li>No hay repartidores registrados.</li>";
                }
                ?>
            </ul>
        </div>
    </div>

    <script>
        document.getElementById('btnAgregarRepartidor').addEventListener('click', function() {
            const nomina = document.getElementById('nomina').value.trim();
            const estado = document.getElementById('estado').value.trim();
            const nombre = document.getElementById('nombre').value.trim();
            const apellidos = document.getElementById('apellidos').value.trim();
            const clave = document.getElementById('clave').value.trim();
            const horabandera = document.getElementById('horabandera').value.trim();

            // Validar campos obligatorios
            if (!nomina || !estado || !nombre || !apellidos || !clave || !horabandera) {
                Swal.fire({
                    title: 'Error',
                    text: 'Por favor, completa todos los campos obligatorios.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
                return; // Detener el envío del formulario
            }

            // Confirmación de envío
            Swal.fire({
                title: '¿Agregar Repartidor?',
                text: "¿Estás seguro de registrar este repartidor?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, agregar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formAgregarRepartidor').submit();
                }
            });
        });

        // Validación en eliminación
        document.querySelectorAll('.btnEliminarRepartidor').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.href;

                Swal.fire({
                    title: '¿Eliminar Repartidor?',
                    text: "Esta acción no se puede deshacer.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });
    </script>


</body>

</html>