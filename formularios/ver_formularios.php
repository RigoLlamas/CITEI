<?php
include '../php/conexion.php'; // Archivo de conexión a la base de datos

// Consultar los formularios
try {
    $query = "SELECT PK_Form, Fecha, Duracion, Repartidor FROM formulario";
    $result = $conexion->query($query);

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
    <title>Formularios Registrados</title>
    <link rel="stylesheet" href="../css/style.css"> <!-- Usamos el CSS del archivo proporcionado -->
    <script src="../js/pie.js"></script>
    <script src="../js/navbar.js"></script>
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
                    <th>Duración (min)</th>
                    <th>Repartidor</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['PK_Form']) ?></td>
                            <td><?= htmlspecialchars($row['Fecha']) ?></td>
                            <td><?= htmlspecialchars($row['Duracion']) ?></td>
                            <td><?= htmlspecialchars($row['Repartidor']) ?></td>
                            <td>
                                <a href="formulario.php?id=<?= $row['PK_Form'] ?>" class="btn-ver">Ver Respuestas</a>
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

            // Mostrar mensaje si no hay resultados
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
