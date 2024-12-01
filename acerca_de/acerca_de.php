<?php
session_start();
$isAdmin = isset($_SESSION['id_usuario']) && $_SESSION['id_usuario'] == '1';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <script>
        console.log("Clave de sesión:", "<?php echo $_SESSION['clave']; ?>");
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Acerca de</title>
    <script src="../js/navbar.js"></script>
    <script src="../js/pie.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/sweetalert.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            fetch('contenido.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('misionText').innerText = data.mision;
                    document.getElementById('visionText').innerText = data.vision;
                    document.getElementById('objetivoText').innerText = data.objetivo;
                })
                .catch(() => alert('Error al cargar el contenido.'));
        });

        <?php if ($isAdmin): ?>

            function saveChanges() {
                const mision = document.getElementById('misionText').innerText;
                const vision = document.getElementById('visionText').innerText;
                const objetivo = document.getElementById('objetivoText').innerText;

                // Validación de 500 caracteres por campo
                if (mision.length > 500 || vision.length > 500 || objetivo.length > 500) {
                    alert('Cada campo debe tener un máximo de 500 caracteres.');
                    return;
                }

                const data = {
                    mision,
                    vision,
                    objetivo
                };

                fetch('guardar.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(result => alert(result.success ? 'Cambios guardados' : 'Error al guardar'))
                    .catch(() => alert('Error de red.'));
            }
        <?php endif; ?>
    </script>
</head>

<body>
    <div class="dos-columnas">
        <div class="cuadro">
            <h2>Acerda de</h2>
            <p id="objetivoText" style="<?php echo $isAdmin ? 'border: 1px solid #000;' : ''; ?>" <?php echo $isAdmin ? 'contenteditable="true"' : ''; ?>>Objetivo</p>


            <h2>Misión</h2>
            <p id="misionText" style="<?php echo $isAdmin ? 'border: 1.5px solid #000;' : ''; ?>" <?php echo $isAdmin ? 'contenteditable="true"' : ''; ?>>Misión</p>

            <h2>Visión</h2>
            <p id="visionText" style="<?php echo $isAdmin ? 'border: 1px solid #000;' : ''; ?>" <?php echo $isAdmin ? 'contenteditable="true"' : ''; ?>>Visión</p>

            
            <?php if ($isAdmin): ?>
                <button onclick="saveChanges()" id="confirmacion">Guardar Cambios</button>
            <?php endif; ?>
        </div>
        <div>
            <img src="../img/logo.png" alt="Logo CITEI">
        </div>
    </div>
</body>

</html>