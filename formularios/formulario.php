<?php
include '../php/conexion.php';
include '../php/solo_admins.php';

// Validar que se haya recibido un ID de formulario
$idFormulario = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($idFormulario <= 0) {
    die("ID de formulario no válido.");
}

// Consultar las respuestas asociadas al formulario
try {
    $query = "SELECT Placas, Antic, AceM, AceT, LiqF, LiqD, Kilometraje, Gasolina, 
                     Comentario, SonidosE, Llantas, Golpes, Interiores 
              FROM respuestas
              WHERE Respuesta = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $idFormulario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Verificar si hay resultados
    $respuesta = $resultado->fetch_assoc();
    if (!$respuesta) {
        die("No se encontraron respuestas para este formulario.");
    }
} catch (Exception $e) {
    die("Error al consultar las respuestas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respuestas del Formulario</title>
    <script src="../js/pie.js"></script>
    <script src="../js/navbar.js"></script>
</head>
<body>
    <header>
        <h1 class="titulo">Respuestas del Formulario</h1>
    </header>
    <section>
        <table class="tabla-clientes">
            <thead>
                <tr>
                    <th>Campo</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Placas</td>
                    <td><?= htmlspecialchars($respuesta['Placas']) ?></td>
                </tr>
                <tr>
                    <td>Anticongelante</td>
                    <td><?= htmlspecialchars($respuesta['Antic']) ?></td>
                </tr>
                <tr>
                    <td>Aceite Motor</td>
                    <td><?= htmlspecialchars($respuesta['AceM']) ?></td>
                </tr>
                <tr>
                    <td>Aceite Transmisión</td>
                    <td><?= htmlspecialchars($respuesta['AceT']) ?></td>
                </tr>
                <tr>
                    <td>Líquido Frenos</td>
                    <td><?= htmlspecialchars($respuesta['LiqF']) ?></td>
                </tr>
                <tr>
                    <td>Líquido Dirección</td>
                    <td><?= htmlspecialchars($respuesta['LiqD']) ?></td>
                </tr>
                <tr>
                    <td>Kilometraje</td>
                    <td><?= htmlspecialchars($respuesta['Kilometraje']) ?></td>
                </tr>
                <tr>
                    <td>Gasolina</td>
                    <td><?= htmlspecialchars($respuesta['Gasolina']) ?></td>
                </tr>
                <tr>
                    <td>Comentario</td>
                    <td><?= htmlspecialchars($respuesta['Comentario']) ?></td>
                </tr>
                <tr>
                    <td>Sonidos Exteriores</td>
                    <td><?= htmlspecialchars($respuesta['SonidosE']) ?></td>
                </tr>
                <tr>
                    <td>Llantas</td>
                    <td><?= htmlspecialchars($respuesta['Llantas']) ?></td>
                </tr>
                <tr>
                    <td>Golpes</td>
                    <td><?= htmlspecialchars($respuesta['Golpes']) ?></td>
                </tr>
                <tr>
                    <td>Interiores</td>
                    <td><?= htmlspecialchars($respuesta['Interiores']) ?></td>
                </tr>
            </tbody>
        </table>
        <div class="boton-volver">
            <a href="ver_formulario.php" class="btn-volver">Volver a Formularios</a>
        </div>
    </section>
</body>
</html>
