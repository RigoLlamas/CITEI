<?php
include '../php/conexion.php';

session_start(); // Para usar las sesiones si el usuario está logueado

// Obtener el PK_Usuario de la sesión o de la URL
if (isset($_SESSION['id_usuario'])) {
    $pk_usuario = $_SESSION['id_usuario'];
} else {
    header('Location: ../login/login.html');
}

// Consulta para obtener los datos del usuario
$sql = "SELECT * FROM usuarios WHERE PK_Usuario = $pk_usuario";
$resultado = mysqli_query($conexion, $sql);

if (!$resultado || mysqli_num_rows($resultado) == 0) {
    die('Error: Usuario no encontrado o consulta fallida.');
}

// Obtener los datos del usuario
$usuario = mysqli_fetch_assoc($resultado);

// Consulta para obtener la lista de municipios
$query_municipios = "SELECT PK_Municipio, Municipio FROM municipio";
$result_municipios = mysqli_query($conexion, $query_municipios);

if (!$result_municipios) {
    die('Error en la consulta de municipios: ' . mysqli_error($conexion));
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Perfil de Usuario</title>
    <script src="../js/navbar.js"></script>
    <script src="../js/pie.js"></script>
</head>

<body>
    <h2 style="text-align: center;">Actualizar Perfil</h2>
    <form id="perfilForm" action="procesar_perfil.php" method="POST" autocomplete="off">
        <div class="dos-columnas cuadro">
            <div class="texto_registro">
                <h3>Datos personales</h3>
                <p>Nombre:
                    <input type="text" name="nombre" id="nombre" value="<?php echo $usuario['Nombres']; ?>" minlength="1" maxlength="150" required>
                </p>
                <p>Apellidos:
                    <input type="text" name="apellidos" id="apellidos" value="<?php echo $usuario['Apellidos']; ?>" minlength="1" maxlength="150" required>
                </p>
                <p>Correo electrónico:
                    <input type="email" name="email" id="email" value="<?php echo $usuario['Correo']; ?>" minlength="1" maxlength="150" required disabled>
                </p>
                <p>Número de teléfono:
                    <input type="tel" name="numero" id="numero" value="<?php echo $usuario['Telefono']; ?>" 
                    pattern="[0-9]{10}" 
                    title="Debe ser un número de 10 dígitos" 
                    required>
                </p>
                <p>Nombre de la empresa si existe:
                    <input type="text" name="empresa" id="empresa" value="<?php echo $usuario['Empresa']; ?>" 
                    minlength="1" maxlength="150">
                </p>
            </div>

            <div class="texto_registro">
                <h3>Datos de dirección</h3>
                <p>Selecciona tu municipio:
                    <select name="municipio" id="municipio" required>
                        <option value=""></option>
                        <?php
                        while ($row = mysqli_fetch_assoc($result_municipios)) {
                            $selected = ($usuario['FK_Municipio'] == $row['PK_Municipio']) ? 'selected' : '';
                            echo '<option value="' . $row['PK_Municipio'] . '" ' . $selected . '>' . $row['Municipio'] . '</option>';
                        }
                        ?>
                    </select>
                </p>
                <p>Calle o dirección:
                    <input type="text" name="calle" id="calle" value="<?php echo $usuario['Calle']; ?>" minlength="1" maxlength="150" required>
                </p>
                
                <div class="dos-columnas">
                    <div>
                        <p>Número exterior:
                            <input autocomplete="off" style="width: 80%;" type="text" name="num_exterior"
                                id="num_exterior" 
                                placeholder="1234" 
                                minlength="1" maxlength="6"
                                value="<?php echo $usuario['NumExterior']; ?>" 
                                required>
                        </p>
                    </div>
                    <div>
                        <p>Número interior:
                            <input autocomplete="off" style="width: 80%;" type="text" name="num_interior"
                                id="num_interior"
                                minlength="1" maxlength="6"
                                value="<?php echo $usuario['NumInterior']; ?>">
                        </p>
                    </div>
                </div>

                <div>
                    <p>¿Desea recibir notificaciones?</p>
                    <label for="notificacion_si" style="display: inline-block;">Sí:
                        <input type="radio" id="notificacion_si" name="notificacion" value="1" style="display: inline;" 
                        <?php if ($usuario['Notificaciones'] == 1) echo 'checked'; ?>>
                    </label>
                    <label for="notificacion_no" style="display: inline-block; margin-left: 10px;">No:
                        <input type="radio" id="notificacion_no" name="notificacion" value="0" style="display: inline;" 
                        <?php if ($usuario['Notificaciones'] == 0) echo 'checked'; ?>>
                    </label>
                </div>
                <button type="submit">Actualizar Perfil</button>
            </div>
        </div>
    </form>
</body>

</html>

<?php
mysqli_close($conexion);
?>
