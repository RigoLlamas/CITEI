document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('registroForm').addEventListener('submit', function (event) {
        event.preventDefault();

        const contrasena = document.getElementById('contraseña').value;
        const confirmarContrasena = document.getElementById('confirmacion').value;
        const emailUsuario = document.getElementById('email').value;

        // Verificar si las contraseñas coinciden
        if (contrasena !== confirmarContrasena) {
            document.getElementById('contraseña').value = '';  
            document.getElementById('contraseña').placeholder = 'Las contraseñas no coinciden';  
     
            document.getElementById('confirmacion').value = '';  
            document.getElementById('confirmacion').placeholder = 'Las contraseñas no coinciden';  
            return; 
        }

        // Verificar si el correo ya existe
        fetch('../php/verificar_email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email: emailUsuario })
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                document.getElementById('email').value = '';
                document.getElementById('email').placeholder = 'Este correo ya está registrado';
                return;
            } else {
                // Generar y guardar el código de verificación
                const codigo = Math.floor(100000 + Math.random() * 900000);
                localStorage.setItem('codigo_verificacion', codigo);

                // Obtener configuraciones de EmailJS
                fetch('../php/obtener_email_config.php')
                    .then(response => response.json())
                    .then(config => {
                        const emailData = {
                            service_id: config.service_id,
                            template_id: config.template_id,
                            user_id: config.user_id,
                            template_params: {
                                to_email: emailUsuario,
                                message: 'Tu código de verificación es: ' + codigo
                            }
                        };

                        // Enviar el correo con el código de verificación
                        return fetch('https://api.emailjs.com/api/v1.0/email/send', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(emailData)
                        });
                    })
                    .then(() => {
                        // Guardar el código en el servidor y enviar el formulario
                        fetch('guardar_codigo.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ codigo_verificacion: codigo })
                        })
                        .then(() => {
                            // Agregar el código al formulario y enviarlo
                            const inputCodigo = document.createElement('input');
                            inputCodigo.type = 'hidden';
                            inputCodigo.id = 'codigo_verificacion';
                            inputCodigo.name = 'codigo_verificacion';
                            inputCodigo.value = codigo;
                            document.getElementById('registroForm').appendChild(inputCodigo);

                            document.getElementById('registroForm').submit();
                        });
                    })
                    .catch(error => {
                        alert('Hubo un problema al enviar el correo: ' + JSON.stringify(error));
                    });
            }
        })
        .catch(error => {
            console.error('Error al verificar el correo:', error);
        });
    });
});
