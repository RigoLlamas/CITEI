<?php
session_start();
$isAdmin = isset($_SESSION['id_usuario']) && $_SESSION['id_usuario'] == '1';

$config = include '../config.php';
$googleMapsApiKey = $config['api_keys']['google_maps_api_key'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CITEI - Contáctanos</title>
    <script src="../js/navbar.js"></script>
    <script src="../js/pie.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://maps.googleapis.com/maps/api/js?key=<?php echo $googleMapsApiKey; ?>&libraries=marker&v=beta&map_ids=DEMO_MAP_ID"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Cargar contenido dinámico
            fetch('contenido.php')
                .then(response => {
                    if (!response.ok) throw new Error('Error al obtener contenido');
                    return response.json();
                })
                .then(data => {
                    document.getElementById('contactanosText').innerText = data.contactanos;
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un problema al cargar el contenido.'
                    });
                });
        });

        <?php if ($isAdmin): ?>
        function saveChanges() {
            const contactanos = document.getElementById('contactanosText').innerText.trim();

            // Validación de longitud
            if (contactanos.length > 500) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Texto demasiado largo',
                    text: 'El texto no puede tener más de 500 caracteres.'
                });
                return;
            }

            const data = { contactanos };

            Swal.fire({
                title: '¿Guardar cambios?',
                text: 'Se sobrescribirá el contenido actual.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('guardar.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Error al guardar cambios');
                        return response.json();
                    })
                    .then(result => {
                        if (result.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Guardado',
                                text: 'Los cambios se han guardado exitosamente.'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Hubo un problema al guardar los cambios.'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de red',
                            text: 'No se pudo conectar con el servidor. Inténtalo más tarde.'
                        });
                    });
                }
            });
        }
        <?php endif; ?>
    </script>
</head>

<body>
    <div class="cuadro">
        <h2 style="text-align: center;">Contáctanos</h2>
        <p id="contactanosText" 
           style="<?php echo $isAdmin ? 'margin: 20px; border: 1.5px solid #000000;' : 'margin: 20px;'; ?>" 
           <?php echo $isAdmin ? 'contenteditable="true"' : ''; ?>>
        </p>

        <?php if ($isAdmin): ?>
            <button style="max-width: 200px; display: block; margin: 0 auto;" onclick="saveChanges()">Guardar Cambios</button>
        <?php endif; ?>

        <h2 style="text-align: center;">Nuestras redes sociales</h2>
        <div class="iconos">
            <a href="https://www.facebook.com/Citei.GDL/?locale=es_LA"><i class="fab fa-facebook-f"></i></a>
            <a href="https://x.com/i/flow/login?redirect_after_login=%2Fcongresocitei" target="_blank"><i class="fab fa-twitter"></i></a>
            <a href="https://www.instagram.com/citei.gdl/?hl=es"><i class="fab fa-instagram"></i></a>
        </div>
    </div>

    <div class="cuadro">
        <gmp-map center="20.673290, -103.416747" zoom="16" map-id="DEMO_MAP_ID">
            <gmp-advanced-marker position="20.673290, -103.416747" title="My location"></gmp-advanced-marker>
        </gmp-map>
    </div>
</body>

</html>
