<?php
include '../php/conexion.php';
include '../php/solo_admins.php';

try {
    $sqlRepartidor = "
    SELECT 
    f.PK_Form,
    f.Fecha,
    f.Duracion,
    f.Repartidor, 
    r.Nombre,
    r.Apellidos
FROM formulario AS f
INNER JOIN repartidor AS r
    ON f.Repartidor = r.Nomina
    ";
    $result = $conexion->query($sqlRepartidor);

    if (!$result) {
        throw new Exception("Error en la consulta: " . $conexion->error);
    }
} catch (Exception $e) {
    die("Error al obtener formularios: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Formularios Registrados</title>
    <script src="../js/navbar.js"></script>
    <script src="../js/pie.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="formulario.js" defer></script>
</head>

<body>
    <header>
        <h1 class="titulo">Formularios Registrados</h1>
    </header>
    <section>
        <div class="buscador">
            <input type="text" id="busqueda" placeholder="Buscar por ID, repartidor o fecha" onkeyup="buscarFormularios()" maxlength="150">
        </div>
        <table class="tabla-clientes" id="tablaFormularios">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Repartidor</th>
                    <th>Nombre</th>
                    <th>Duración (min)</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['PK_Form']) ?></td>
                            <td><?= htmlspecialchars($row['Fecha']) ?></td>
                            <td><?= htmlspecialchars($row['Repartidor']) ?></td>
                            <td><?= htmlspecialchars($row['Nombre'] . " " . $row['Apellidos']) ?></td>
                            <td><?= htmlspecialchars($row['Duracion']) ?></td>
                            <td style="text-align: center;">
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 10px; border-left: 1px solid #ccc; padding-left: 10px;">
                                    <a class="formulario" href="formulario.php?id=<?= $row['PK_Form'] ?>">Ver Respuestas</a>
                                    <a class="formulario" href="javascript:void(0);" onclick="confirmarEliminacion(<?= $row['PK_Form'] ?>)">Eliminar</a>
                                </div>
                            </td>

                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="sin-resultados">No se encontraron formularios registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
    
    <?php if (isset($_GET['status'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php if ($_GET['status'] === 'success'): ?>
                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'El formulario se eliminó correctamente.',
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    });
                <?php elseif ($_GET['status'] === 'error'): ?>
                    Swal.fire({
                        title: 'Error',
                        text: 'Ocurrió un problema al eliminar el formulario.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>

</body>

</html>