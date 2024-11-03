document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('recuperacionForm').addEventListener('submit', function (event) {
        event.preventDefault(); // Evita el envío por defecto del formulario

        const emailUsuario = document.getElementById('correo').value.trim();

        // Verificar si el campo de correo no está vacío
        if (emailUsuario === '') {
            console.log("El campo de correo está vacío.");
            return;
        }

        // Validar el correo con olvido_contraseña.php
        fetch('olvido_contraseña.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ correo: emailUsuario })
        })
        .then(response => response.json())
        .then(response => {
            if (response.status === "success") {
                console.log("Correo válido. Continuando con el envío...");

                // Generar un código de verificación de 6 dígitos
                const codigo = Math.floor(100000 + Math.random() * 900000);
                localStorage.setItem('codigo_verificacion', codigo); // Almacena el código en localStorage

                // Obtener configuraciones para EmailJS
                fetch('../php/obtener_email_config.php')
                    .then(response => response.json())
                    .then(config => {
                        // Preparar datos para enviar el correo con EmailJS
                        const data = {
                            service_id: config.service_id,
                            template_id: config.template_code,
                            user_id: config.user_id,
                            template_params: {
                                to_email: emailUsuario,
                                message: 'Tu código de verificación es: ' + codigo
                            }
                        };

                        // Enviar correo usando EmailJS
                        fetch('https://api.emailjs.com/api/v1.0/email/send', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(data)
                        })
                        .then(() => {
                            console.log("Correo enviado exitosamente.");

                            // Redirigir a la página de ingreso de clave
                            window.location.href = 'ingresar_clave.php';
                        })
                        .catch(error => {
                            console.error("Hubo un problema al enviar el correo:", error);
                        });
                    })
                    .catch(error => {
                        console.error("Error al obtener la configuración del servicio de email:", error);
                    });
            } else {
                console.log(response.message); // Mostrar mensaje de error del servidor
            }
        })
        .catch(error => {
            console.log('Error en la validación del correo:', error);
        });
    });
});

