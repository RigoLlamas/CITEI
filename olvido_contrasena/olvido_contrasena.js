document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('recuperacionForm').addEventListener('submit', function (event) {
        event.preventDefault(); // Evita el envío por defecto del formulario

        const emailUsuario = document.getElementById('correo').value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        // Validar si el correo no está vacío y tiene un formato válido
        if (emailUsuario === '') {
            return;
        }

        if (!emailRegex.test(emailUsuario)) {
            return;
        }

        // Validar el correo con olvido_contrasena.php
        fetch('olvido_contrasena.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ correo: emailUsuario })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error del servidor: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(response => {
            if (response.status === "success") {
                // Generar un código de verificación de 6 dígitos
                const codigo = Math.floor(100000 + Math.random() * 900000);
                localStorage.setItem('codigo_verificacion', codigo); // Almacena el código en localStorage

                // Obtener configuraciones para EmailJS
                fetch('../php/obtener_email_config.php')
                    .then(configResponse => {
                        if (!configResponse.ok) {
                            throw new Error(`Error al obtener configuración: ${configResponse.statusText}`);
                        }
                        return configResponse.json();
                    })
                    .then(config => {
                        if (!config.service_id || !config.template_code || !config.user_id) {
                            throw new Error("Faltan datos en la configuración.");
                        }

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
            }
        })
        .catch(error => {
            console.error('Error en la validación del correo:', error);
        });
    });
});
