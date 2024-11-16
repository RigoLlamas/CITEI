<?php
session_start();
include '../php/conexion.php'; 
include '../php/verificar_existencia.php'; 



// Procesar el formulario solo si es una solicitud POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $response = array("status" => "error", "message" => "Correo no válido");
    header('Content-Type: application/json');
    try {
        // Obtener y decodificar los datos JSON
        $data = json_decode(file_get_contents("php://input"), true);

        if (isset($data['correo'])) {
            $email = $data['correo'];

            // Validar si el correo tiene un formato válido
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $sql = "SELECT * FROM usuarios WHERE Correo = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $resultado = $stmt->get_result();

                if ($resultado->num_rows > 0) {
                    // Guardar el correo en la sesión
                    $_SESSION['correo'] = $email;

                    $response['status'] = "success";
                    $response['message'] = "Correo válido";
                } else {
                    $response['message'] = "Correo no encontrado";
                }
            } else {
                $response['message'] = "Correo con formato inválido";
            }
        } else {
            $response['message'] = "Campo de correo no enviado";
        }
    } catch (Exception $e) {
        $response['message'] = "Error interno del servidor";
        error_log($e->getMessage());
    }

    echo json_encode($response);
    exit();
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Olvide mi contraseña</title>
    <script src="../js/navbar.js"></script>
    <script src="../js/pie.js"></script>
</head>
<body>
    <div class="login__contenedor">
        <form id="recuperacionForm" class="login__formulario">
            <p>Ingrese su correo electrónico</p>
            <div class="login__datos" style="margin-bottom: 5.84rem;">
                <label for="correo">Correo Electrónico</label>
                <input id="correo" type="email" name="correo" autocomplete="off" required>
            </div>
            <div class="login__botones">
                <button class="login__boton" type="submit">Ingresar</button>
            </div>
        </form>
    </div>
    <script src="correo.js"></script>
</body>
</html>