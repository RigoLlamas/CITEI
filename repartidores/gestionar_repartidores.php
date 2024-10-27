<?php 
include '../php/conexion.php';

// Eliminar repartidor si se ha enviado una solicitud para eliminar
if (isset($_POST['eliminar_nomina'])) {
    $nomina = (float)$_POST['eliminar_nomina'];
    $sql = "DELETE FROM repartidor WHERE Nomina = $nomina";
    if (mysqli_query($conexion, $sql)) {
        echo "Repartidor eliminado correctamente.";
    } else {
        echo "Error al eliminar repartidor: " . mysqli_error($conexion);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nomina = (float) $_POST['nomina'];
    $nombre = trim($conexion->real_escape_string($_POST['nombre']));
    $apellidos = trim($conexion->real_escape_string($_POST['apellidos']));
    $estado = trim($conexion->real_escape_string($_POST['estado']));
    $clave = trim($conexion->real_escape_string($_POST['clave']));

    // SQL para insertar un nuevo repartidor
    $sql = "INSERT INTO repartidor (Nomina, Nombre, Apellidos, Estado, Clave)
            VALUES ('$nomina', '$nombre', '$apellidos', '$estado', '$clave')";

    if (mysqli_query($conexion, $sql)) {
        echo "Repartidor agregado exitosamente.";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conexion);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Gestionar Repartidores</title>
    <script src="../js/pie.js"></script>
    <script src="../js/navbar.js"></script>
    <script src="repartidores.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/sweetalert.js"></script>
</head>
<body>
    <h2 style="text-align: center;">Repartidores</h2>    

    <div class="contenedor-repartidor cuadro">
        <form action="gestionar_repartidores.php" method="POST">
            <div style="display: flex; flex-direction: row;">
                <div style="width: 80%;">
                    <p style="text-align: left;" for="nomina">Nómina:</p>
                    <input type="number" id="nomina" name="nomina" required>
                </div>
                <div style="width: 20%;">
                    <p style="text-align: left;" for="estado">Estado:</p>
                    <select id="estado" name="estado" required>
                        <option value="Disponible">Disponible</option>
                        <option value="Ocupado">Ocupado</option>
                    </select>
                </div>
            </div>    
        
            <div style="display: flex; flex-direction: row;">
                <div style="width: 50%;">
                    <p for="nombre">Nombre:</p>
                    <input style="width: 100%;" type="text" id="nombre" name="nombre" 
                    minlength="1" maxlength="150"
                    required>
                </div>
                <div style="width: 50%; margin-left: 5%;">
                    <p for="apellidos">Apellidos:</p>
                    <input style="width: 100%;" type="text" id="apellidos" name="apellidos" 
                    minlength="1" maxlength="150"
                    required>
                </div>
            </div>    

            <div style="display: flex; flex-direction: row;">
                <div style="width: 60%;">
                    <p for="clave">Clave:</p>
                    <input type="password" id="clave" name="clave" 
                    minlength="5" maxlength="50"
                    required>
                </div>
                <div style="width: 40%;" class="botones-repartidores">
                    <button type="submit">Agregar</button>
                </div>
            </div>   
        </form>

        <!-- Lista dinámica de repartidores -->
    <div class="lista-repartidores">
        <h3>Repartidores registrados</h3>
        <ul id="repartidores">
            <?php
            if ($resultado && mysqli_num_rows($resultado) > 0) {
                // Generar la lista de repartidores con botones
                while ($repartidor = mysqli_fetch_assoc($resultado)) {
                    echo "<li>";
                    echo "Nómina: " . $repartidor['Nomina'] . " - " . $repartidor['Nombre'] . " " . $repartidor['Apellidos'] . " (Estado: " . $repartidor['Estado'] . ")";
                    // Botones para modificar y eliminar
                    echo " <a href='modificar_repartidor.php?nomina=" . $repartidor['Nomina'] . "'>Modificar</a> | ";
                    echo "<a href='eliminar_repartidor.php?nomina=" . $repartidor['Nomina'] . "' class='confirmar-accion'>Eliminar</a>";

                    echo "</li>";
                }
            } else {
                echo "<li>No hay repartidores registrados.</li>";
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