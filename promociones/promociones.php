<?php
session_start();
include '../php/conexion.php';

// Verificar si el usuario está autenticado
if (isset($_SESSION['id_usuario']) && is_numeric($_SESSION['id_usuario'])) {
    $usuario_id = (int)$_SESSION['id_usuario'];

    // Redirigir al administrador al panel de gestión de promociones
    if ($usuario_id === 1) {
        header('Location: ../promociones/gestionar_promociones.php');
        exit();
    }
} else {
    header('Location: ../login/login.html');
    exit();
}

// Consulta SQL ajustada a las tablas compartidas
$sql_promociones = "
    SELECT 
        o.Descripcion, 
        o.Valor, 
        o.Tipo, 
        o.Despliegue, 
        c.LimiteTiempo AS Expiracion, -- Obtenemos la fecha de expiración desde condiciones
        IFNULL(p.Nombre, 'General') AS ProductoNombre,
        ao.EstadoUso
    FROM asignacion_ofertas ao
    JOIN ofertas o ON ao.Oferta = o.Oferta
    LEFT JOIN producto p ON o.Producto = p.PK_Producto
    LEFT JOIN condiciones c ON o.Condicion = c.Condicion -- Vinculación con condiciones para obtener LimiteTiempo
    WHERE ao.Usuario = ? 
      AND o.Estado = 'Activada' 
      AND CURDATE() BETWEEN o.Despliegue AND IFNULL(c.LimiteTiempo, CURDATE() + INTERVAL 1 YEAR)
";


// Preparar y ejecutar la consulta
$stmt_promociones = mysqli_prepare($conexion, $sql_promociones);

if (!$stmt_promociones) {
    die("Error en la preparación de la consulta: " . mysqli_error($conexion));
}

if (!mysqli_stmt_bind_param($stmt_promociones, 'i', $usuario_id)) {
    die("Error al enlazar parámetros: " . mysqli_error($conexion));
}

if (!mysqli_stmt_execute($stmt_promociones)) {
    die("Error al ejecutar la consulta: " . mysqli_error($conexion));
}

$result_promociones = mysqli_stmt_get_result($stmt_promociones);

if (!$result_promociones) {
    die("Error al obtener los resultados: " . mysqli_error($conexion));
}

// Procesar los resultados en el HTML
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Promociones</title>
    <script src="../js/pie.js" defer></script>
    <script src="../js/navbar.js" defer></script>
    <style>


    </style>
</head>

<body>
    <h2 style="text-align: center;">Promociones disponibles</h2>

    <!-- Contenedor dinámico de promociones -->
<?php
if (mysqli_num_rows($result_promociones) > 0) {
    while ($promocion = mysqli_fetch_assoc($result_promociones)) {
        // Determinar el color según el estado
        $colorEstado = '';
        switch ($promocion['EstadoUso']) {
            case 'No utilizada':
                $colorEstado = 'color: blue; font-weight: bold;';
                break;
            case 'Utilizada':
                $colorEstado = 'color: green; font-weight: bold;';
                break;
            case 'Expirada':
                $colorEstado = 'color: red; font-weight: bold;';
                break;
            default:
                $colorEstado = 'color: orange; font-style: italic;';
        }
?>
        <div class="contenedor-promociones cuadro">
            <div class="cuadro">
                <p><strong>Descripción:</strong> <?php echo htmlspecialchars($promocion['Descripcion'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Producto:</strong> <?php echo htmlspecialchars($promocion['ProductoNombre'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Válido desde:</strong> <?php echo htmlspecialchars($promocion['Despliegue'], ENT_QUOTES, 'UTF-8'); ?>
                    <strong>hasta:</strong>
                    <?php echo !empty($promocion['Expiracion']) 
                        ? htmlspecialchars($promocion['Expiracion'], ENT_QUOTES, 'UTF-8') 
                        : '<span style="color: grey;">Hasta agotar existencias</span>'; ?>
                </p>
                <p>
                    <strong>Estado:</strong>
                    <span style="<?php echo $colorEstado; ?>">
                        <?php echo htmlspecialchars($promocion['EstadoUso'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </p>
            </div>
        </div>
<?php
    }
} else {
?>
    <div class="cuadro mensaje-promociones">
        <h3 style="text-align: center;">No tienes promociones disponibles en este momento.</h3>
        <p style="text-align: center;">¡Vuelve pronto para descubrir nuevas ofertas!</p>
    </div>
<?php
}
?>


</body>

</html>
<?php
// Cerrar la conexión
mysqli_stmt_close($stmt_promociones);
mysqli_close($conexion);
?>