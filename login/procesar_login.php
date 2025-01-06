<?php
session_start();
require '../php/conexion.php';

header('Content-Type: application/json');

try {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $_SESSION['id_usuario'] = null;
        $correoIngresado = $_POST['correo'];
        $claveIngresada = $_POST['clave'];

        // Usar sentencias preparadas para prevenir inyección SQL
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE correo = ?");
        $stmt->bind_param("s", $correoIngresado);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Verificar si la contraseña ingresada coincide
            if ($claveIngresada == $row['Clave']) {
                $_SESSION['id_usuario'] = $row['PK_Usuario'];

                // Determinar si el usuario es administrador
                $isAdmin = ($row['PK_Usuario'] == 1);

                // Responder con éxito, PK_Usuario e indicador de administrador
                echo json_encode([
                    "success" => true,
                    "isAdmin" => $isAdmin,
                ]);
                exit();
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Usuario o contraseña incorrecta."
                ]);
                exit();
            }
        } else {
            $_SESSION['id_usuario'] = NULL;
            echo json_encode([
                "success" => false,
                "message" => "Usuario o contraseña incorrecta."
            ]);
            exit();
        }
    }

    $conexion->close();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
    exit();
}
?>
