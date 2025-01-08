<?php
include '../php/conexion.php';
include '../php/solo_admins.php';

// Verificamos si se ha enviado el formulario de creación de vehículo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['placa'])) {
    $placa = trim($conexion->real_escape_string($_POST['placa']));
    $largo = (float) $_POST['largo'];
    $alto = (float) $_POST['alto'];
    $ancho = (float) $_POST['ancho'];
    $modelo = trim($conexion->real_escape_string($_POST['modelo']));
    $estado = trim($conexion->real_escape_string($_POST['estado']));

    // Nuevo campo KilometrosRecorridos
    $kilometrosRecorridos = (int) $_POST['kilometrosRecorridos'];

    // SQL para insertar un nuevo vehículo (incluyendo KilometrosRecorridos)
    $sql = "INSERT INTO vehiculo (Placa, Largo, Alto, Ancho, Modelo, Estado, KilometrosRecorridos)
            VALUES ('$placa', $largo, $alto, $ancho, '$modelo', '$estado', $kilometrosRecorridos)";
    
    if (mysqli_query($conexion, $sql)) {
        header('Location: gestionar_vehiculos.php?success=true');
        exit();
    } else {
        echo "Error al insertar el vehículo: " . mysqli_error($conexion);
    }
}

// Consulta para obtener la lista de vehículos (incluimos KilometrosRecorridos)
$sql_lista = "SELECT Placa, Largo, Alto, Ancho, Modelo, Estado, KilometrosRecorridos FROM vehiculo";
$resultado = mysqli_query($conexion, $sql_lista);

// Consulta para contar cuántos vehículos hay
$sql_contar = "SELECT COUNT(*) as total FROM vehiculo";
$resultado_contar = mysqli_query($conexion, $sql_contar);
$total_vehiculos = mysqli_fetch_assoc($resultado_contar)['total'];

// Si hay 10 o más vehículos, deshabilitar el botón de agregar
$deshabilitar_boton = $total_vehiculos >= 10;

mysqli_close($conexion);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Gestionar Vehículos</title>
    <script src="../js/pie.js"></script>
    <script src="../js/navbar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php
// Notificación de éxito al agregar vehículo
if (isset($_GET['success']) && $_GET['success'] === 'true') {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                title: '¡Éxito!',
                text: 'Vehículo agregado correctamente.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        });
    </script>";
}
?>

<h2 style="text-align: center;">Gestión de Vehículos</h2>    

<div class="contenedor-vehiculo cuadro">
    <form id="formAgregarVehiculo" action="gestionar_vehiculos.php" method="POST">
        <!-- Fila 1: Placa, Largo, Alto, Ancho -->
        <div style="display: flex; flex-direction: row;">
            <div style="width: 40%;">
                <p style="text-align: left;">Placa:</p>
                <input type="text" id="placa" name="placa" maxlength="9" required>
            </div>
            <div style="width: 20%; margin-left: 5%;">
                <p style="text-align: left;">Largo de cabina (m):</p>
                <input type="number" id="largo" name="largo" step="0.01" min="0.1" max="20" required>
            </div>
            <div style="width: 20%; margin-left: 5%;">
                <p style="text-align: left;">Alto de cabina (m):</p>
                <input type="number" id="alto" name="alto" step="0.01" min="0.1" max="20" required>
            </div>
            <div style="width: 20%; margin-left: 5%;">
                <p style="text-align: left;">Ancho de cabina (m):</p>
                <input type="number" id="ancho" name="ancho" step="0.01" min="0.1" max="20" required>
            </div>
        </div>

        <!-- Fila 2: Modelo, Estado, KilometrosRecorridos -->
        <div style="display: flex; flex-direction: row; margin-top: 20px;">
            <div style="width: 30%;">
                <p>Modelo:</p>
                <input style="width: 100%;" type="text" id="modelo" name="modelo" minlength="1" maxlength="50" required>
            </div>
            <div style="width: 20%; margin-left: 5%;">
                <p style="text-align: left;">Estado:</p>
                <select id="estado" name="estado" required>
                    <option value="En taller">En taller</option>
                    <option value="En circulacion">En circulación</option>
                </select>
            </div>
            <div style="width: 30%; margin-left: 5%;">
                <p style="text-align: left;">Kilómetros Recorridos:</p>
                <input type="number" id="kilometrosRecorridos" name="kilometrosRecorridos"
                       step="1" min="0" required>
            </div>
        </div>

        <!-- Botón Agregar -->
        <div class="botones-vehiculos" style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <button type="button" id="btnAgregarVehiculo" <?php if ($deshabilitar_boton) echo 'disabled'; ?>>
                Agregar <br>Vehículo
            </button>
        </div>
    </form>

    <!-- Lista dinámica de vehículos -->
    <div class="lista-vehiculos">
        <h3>Vehículos registrados</h3>
        <ul id="vehiculos">
            <?php
            if ($resultado && mysqli_num_rows($resultado) > 0) {
                while ($vehiculo = mysqli_fetch_assoc($resultado)) {
                    echo "<li>";
                    // Mostramos KilometrosRecorridos
                    echo "Placa: " . $vehiculo['Placa'] . " - Modelo: " . $vehiculo['Modelo']
                       . " (Estado: " . $vehiculo['Estado'] . ", Kilometraje: " . $vehiculo['KilometrosRecorridos'] . ")";
                    echo "<div>";
                    echo "<a href='modificar_vehiculo.php?placa=" . $vehiculo['Placa'] . "'>Modificar</a> | ";
                    echo "<a href='eliminar_vehiculo.php?placa=" . $vehiculo['Placa'] . "' class='btnEliminarVehiculo'>Eliminar</a>";
                    echo "</div>";
                    echo "</li>";
                }
            } else {
                echo "<li>No hay vehículos registrados.</li>";
            }
            ?>
        </ul>
    </div>
</div>

<script>
    // Confirmación al agregar un vehículo
    document.getElementById('btnAgregarVehiculo').addEventListener('click', function () {
        Swal.fire({
            title: '¿Agregar Vehículo?',
            text: "¿Estás seguro de registrar este vehículo?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, agregar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formAgregarVehiculo').submit();
            }
        });
    });

    // Confirmación al eliminar un vehículo
    document.querySelectorAll('.btnEliminarVehiculo').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const url = this.href;

            Swal.fire({
                title: '¿Eliminar Vehículo?',
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
