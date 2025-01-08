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
                            
                            <td>
                                <a class="formulario" href="formulario.php?id=<?= $row['PK_Form'] ?>" class="btn-ver">Ver Respuestas</a>
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
    <script>
        function buscarFormularios() {
            const busqueda = document.getElementById('busqueda').value.toLowerCase();
            const filas = document.querySelectorAll('#tablaFormularios tbody tr');
            let resultados = false;

            filas.forEach(fila => {
                const texto = fila.innerText.toLowerCase();
                if (texto.includes(busqueda)) {
                    fila.style.display = '';
                    resultados = true;
                } else {
                    fila.style.display = 'none';
                }
            });

            const sinResultados = document.querySelector('.sin-resultados');
            if (!resultados && sinResultados) {
                sinResultados.style.display = 'block';
            } else if (sinResultados) {
                sinResultados.style.display = 'none';
            }
        }
    </script>
</body>

</html>