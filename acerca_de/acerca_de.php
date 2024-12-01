<?php
session_start();
$isAdmin = isset($_SESSION['id_usuario']) && $_SESSION['id_usuario'] == '1';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Acerca de</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/navbar.js"></script>
    <script src="../js/pie.js"></script>
    <style>
        /* Estilos básicos para administrador */
        [contenteditable="true"] {
            outline: none;
        }
        [contenteditable="true"]:focus {
            border: 2px dashed #3085d6;
        }
    </style>
</head>

<body>
    <div class="dos-columnas">
        <div class="cuadro">
            <h2>Acerca de</h2>
            <p id="objetivoText" style="<?php echo $isAdmin ? 'border: 1px solid #000;' : ''; ?>" <?php echo $isAdmin ? 'contenteditable="true"' : ''; ?>>Objetivo</p>

            <h2>Misión</h2>
            <p id="misionText" style="<?php echo $isAdmin ? 'border: 1.5px solid #000;' : ''; ?>" <?php echo $isAdmin ? 'contenteditable="true"' : ''; ?>>Misión</p>

            <h2>Visión</h2>
            <p id="visionText" style="<?php echo $isAdmin ? 'border: 1px solid #000;' : ''; ?>" <?php echo $isAdmin ? 'contenteditable="true"' : ''; ?>>Visión</p>

            <?php if ($isAdmin): ?>
                <button id="guardarCambios">Guardar Cambios</button>
            <?php endif; ?>
        </div>
        <div>
            <img src="../img/logo.png" alt="Logo CITEI">
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Cargar contenido dinámico
            fetch('contenido.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('misionText').innerText = data.mision;
                    document.getElementById('visionText').innerText = data.vision;
                    document.getElementById('objetivoText').innerText = data.objetivo;
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo cargar el contenido.'
                    });
                });

            <?php if ($isAdmin): ?>
            // Guardar cambios (solo para administrador)
            const guardarBtn = document.getElementById('guardarCambios');
            guardarBtn.addEventListener('click', () => {
                const mision = document.getElementById('misionText').innerText.trim();
                const vision = document.getElementById('visionText').innerText.trim();
                const objetivo = document.getElementById('objetivoText').innerText.trim();

                // Validar longitud máxima
                if (mision.length > 500 || vision.length > 500 || objetivo.length > 500) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Campos demasiado largos',
                        text: 'Cada campo debe tener un máximo de 500 caracteres.'
                    });
                    return;
                }

                const data = { mision, vision, objetivo };

                Swal.fire({
                    title: '¿Guardar cambios?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, guardar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('guardar.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(data)
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Cambios guardados',
                                    text: 'Los cambios han sido guardados correctamente.'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'No se pudieron guardar los cambios.'
                                });
                            }
                        })
                        .catch(() => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error de red',
                                text: 'No se pudo conectar con el servidor.'
                            });
                        });
                    }
                });
            });
            <?php endif; ?>
        });
    </script>
</body>

</html>
