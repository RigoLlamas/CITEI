<?php
include '../php/conexion.php';

// Obtener los clientes desde la base de datos
try {
    $query = "SELECT u.Nombres, u.Apellidos, u.Correo, u.Telefono, m.Municipio AS Municipio, 
                     CONCAT(u.Calle, ' ', u.NumExterior, ' ', COALESCE(u.NumInterior, '')) AS Direccion,
                     u.Empresa
              FROM usuarios u
              LEFT JOIN municipio m ON u.FK_Municipio = m.PK_Municipio;";

    $stmt = $conexion->prepare($query);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Verifica si hay resultados y guárdalos en un array
    $clientes = [];
    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $clientes[] = $fila;
        }
    }
} catch (Exception $e) {
    echo "Error en SQL: " . $query . "<br>";
    die("Error al obtener clientes: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes Registrados</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/pie.js"></script>
    <script src="../js/navbar.js"></script>
</head>
<body>
    <header>
        <h1 class="titulo">Clientes Registrados</h1>
    </header>
    <section>
        <div class="buscador">
            <input type="text" id="busqueda" placeholder="Buscar por nombre, correo o municipio" onkeyup="buscarClientes()">
        </div>
        <table class="tabla-clientes" id="tablaClientes">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Teléfono</th>
                    <th>Municipio</th>
                    <th>Dirección</th>
                    <th>Empresa</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($clientes)): ?>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?= htmlspecialchars($cliente['Nombres'] . ' ' . $cliente['Apellidos']) ?></td>
                            <td><?= htmlspecialchars($cliente['Correo']) ?></td>
                            <td><?= htmlspecialchars($cliente['Telefono']) ?></td>
                            <td><?= htmlspecialchars($cliente['Municipio']) ?></td>
                            <td><?= htmlspecialchars($cliente['Direccion']) ?></td>
                            <td><?= htmlspecialchars($cliente['Empresa'] ?? 'N/A') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="sin-resultados">No se encontraron clientes registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
    <script>
        function buscarClientes() {
            const busqueda = document.getElementById('busqueda').value.toLowerCase();
            const filas = document.querySelectorAll('#tablaClientes tbody tr');
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
            if (!resultados) {
                sinResultados.style.display = 'block';
            } else {
                sinResultados.style.display = 'none';
            }
        }
    </script>
</body>
</html>
