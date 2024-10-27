<?php
session_start();
include '../php/conexion.php';
include '../scripts/verificar_dia.php';

// Verificar si el usuario está autenticado
if (isset($_SESSION['id_usuario'])) {
    $usuario_id = $_SESSION['id_usuario'];

    // Redirigir al administrador al panel de gestión de promociones
    if ($usuario_id == '1') {
        header('Location: ../promociones/gestionar_promociones.php');
        exit();
    }

} else {
    header('Location: ../login/login.html');
    exit();
}

// Obtener las promociones activas asignadas al usuario
$sql_promociones = "
    SELECT o.Descripcion, o.Valor, o.Tipo, o.Despliegue, o.Expiracion, IFNULL(p.Nombre, 'General') AS ProductoNombre
    FROM asignacion_ofertas ao
    JOIN ofertas o ON ao.Oferta = o.Oferta
    LEFT JOIN producto p ON o.Producto = p.PK_Producto
    WHERE ao.Usuario = ? AND o.Estado = 'Activada' AND CURDATE() BETWEEN o.Despliegue AND o.Expiracion
";
$stmt_promociones = mysqli_prepare($conexion, $sql_promociones);
mysqli_stmt_bind_param($stmt_promociones, 'i', $usuario_id);
mysqli_stmt_execute($stmt_promociones);
$result_promociones = mysqli_stmt_get_result($stmt_promociones);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Promociones</title>
    <script src="../js/pie.js" defer></script>
    <script src="../js/navbar.js" defer></script>
</head>

<body>
    <h2 style="text-align: center;">Promociones disponibles</h2>

    <!-- Contenedor dinámico de promociones -->
    <div class="contenedor-promociones cuadro">
        <?php
        if (mysqli_num_rows($result_promociones) > 0) {
            while ($promocion = mysqli_fetch_assoc($result_promociones)) {
                ?>
                <div class="cuadro">
                    <p><strong>Descripción:</strong> <?php echo $promocion['Descripcion']; ?></p>
                    <p><strong>Válido desde:</strong> <?php echo $promocion['Despliegue']; ?> <strong>hasta:</strong> <?php echo $promocion['Expiracion']; ?></p>
                </div>
                <?php
            }
        } else {
            echo "<p>No tienes promociones disponibles en este momento.</p>";
        }
        ?>
    </div>

</body>
</html>

<?php
// Cerrar la conexión
mysqli_stmt_close($stmt_promociones);
mysqli_close($conexion);
?>
