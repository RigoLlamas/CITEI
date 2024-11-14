<?php
session_start();
include '../php/conexion.php';

$query = "SELECT PK_Municipio, Municipio FROM municipio";
$result = mysqli_query($conexion, $query);

if (!$result) {
    die('Error en la consulta: ' . mysqli_error($conexion));
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Registro</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="../js/navbar.js"></script>
    <script src="../js/pie.js"></script>
    <script src="registro.js" defer></script>
</head>

<body>
    <h2 style="text-align: center;">Registro</h2>
    <form id="registroForm" action="procesar_registro.php" method="POST" autocomplete="off">
        <div class="dos-columnas cuadro">
            <div class="texto_registro">
                <h3>Ingresa tus datos personales</h3>
                <p>Nombre:
                    <input autocomplete="off" type="text" name="nombre" id="nombre" 
                        placeholder="Escribe tu nombre"
                        minlength="1" maxlength="150"
                        required>
                </p>
                <p>Apellidos:
                    <input autocomplete="off" type="text" name="apellidos" id="apellidos" 
                        placeholder="Escribe tu apellido" 
                        minlength="1" maxlength="150"
                        required>
                </p>
                <p>Correo electrónico:
                    <input autocomplete="off" type="email" name="email" id="email"
                        placeholder="Escribe tu correo electrónico" 
                        minlength="1" maxlength="150"
                        required>
                </p>
                <p>Contraseña:
                    <input autocomplete="off" type="password" name="contraseña" id="contraseña"
                        placeholder="Escribe tu contraseña" 
                        minlength="5" maxlength="50"
                        required>
                </p>
                <p>Confirma tu contraseña:
                    <input autocomplete="off" type="password" name="confirmacion" id="confirmacion"
                        placeholder="Confirma tu contraseña" 
                        minlength="5" maxlength="50"
                        required>
                </p>
                <p>Número de teléfono:
                    <input autocomplete="off" type="tel" name="numero" id="numero" pattern="[0-9]{10}"
                        title="Debe ser un número de 10 dígitos" placeholder="Escribe tu numero celular" 
                        required>
                </p>
                <p>Nombre de la empresa si existe:
                    <input type="text" name="empresa" id="empresa"
                    minlength="1" maxlength="150">
                </p>
            </div>
            <div class="texto_registro">
                <h3>Ingresa tus datos de dirección</h3>

                <p>Selecciona tu municipio:
                    <select name="municipio" id="municipio" required autocomplete="no">
                    <option value=""></option>
                        <?php
                            while ($row = mysqli_fetch_assoc($result)) {
                            echo '<option value="' . $row['PK_Municipio'] . '">' . $row['Municipio'] . '</option>';
                        }
                        ?>
                    </select>
                    <p>Ingrese su calle o dirección en caso de no encontrar su municipio:
                        <input autocomplete="off" type="text" name="calle" id="calle" placeholder="Escribe tu calle"
                        minlength="1" maxlength="150"
                        required>
                    </p>
                    <p>Código postal:
                        <input autocomplete="off" type="number" name="codigo" id="codigo"
                            placeholder="Escribe tu codigo postal" 
                            min="10000" max="99999"
                            required>
                    </p>
                    <div class="dos-columnas">
                        <div>
                            <p>Número exterior:
                                <input autocomplete="off" style="width: 80%;" type="text" name="num_exterior"
                                    id="num_exterior" 
                                    placeholder="1234" 
                                    minlength="1" maxlength="6"
                                    required>
                            </p>
                        </div>
                        <div>
                            <p>Número interior:
                                <input autocomplete="off" style="width: 80%;" type="text" name="num_interior"
                                    id="num_interior"
                                    minlength="1" maxlength="6" >
                            </p>
                        </div>
                    </div>
                    <div>
                        <p>¿Desea recibir notificaciones?</p>
                        <label for="notificacion_si" style="display: inline-block;">Sí:
                            <input type="radio" id="notificacion_si" name="notificacion" value="1" style="display: inline;">
                        </label>
                        <label for="notificacion_no" style="display: inline-block; margin-left: 10px;">No:
                            <input type="radio" id="notificacion_no" name="notificacion" value="0" style="display: inline;" checked>
                        </label>
                    </div>
                    <button type="submit">Registrarme</button>
            </div>
        </div>
    </form>
</body>

</html>