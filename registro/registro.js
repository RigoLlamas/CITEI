document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('registroForm').addEventListener('submit', function (event) {
        event.preventDefault();

        const contrasena = document.getElementById('contraseña').value;
        const confirmarContrasena = document.getElementById('confirmacion').value;
        const emailUsuario = document.getElementById('email').value;

        // Verificar si las contraseñas coinciden
        if (contrasena !== confirmarContrasena) {
            Swal.fire({
                title: "Error",
                text: "Las contraseñas no coinciden. Inténtalo de nuevo.",
                icon: "error",
                confirmButtonText: "Aceptar"
            }).then(() => {
                document.getElementById('contraseña').value = '';
                document.getElementById('contraseña').placeholder = 'Las contraseñas no coinciden';
                document.getElementById('confirmacion').value = '';
                document.getElementById('confirmacion').placeholder = 'Las contraseñas no coinciden';
            });
            return;
        }

        // Verificar si el correo ya existe
        fetch('verificar_email.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email: emailUsuario })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la comunicación con el servidor.');
            }
            return response.json();
        })
        .then(data => {
            if (data.exists) {
                Swal.fire({
                    title: "Correo ya registrado",
                    text: "Este correo ya está registrado. Por favor, utiliza uno diferente.",
                    icon: "error",
                    confirmButtonText: "Aceptar"
                }).then(() => {
                    document.getElementById('email').value = '';
                    document.getElementById('email').placeholder = 'Este correo ya está registrado';
                });
                return;
            }

            // Confirmación antes de proceder con el registro
            Swal.fire({
                title: 'Confirmar Registro',
                text: '¿Deseas completar el registro con los datos proporcionados?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, registrarme',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Generar y guardar el código de verificación
                    const codigo = Math.floor(100000 + Math.random() * 900000);
                    localStorage.setItem('codigo_verificacion', codigo);

                    // Obtener configuraciones de EmailJS
                    fetch('../php/obtener_email_config.php')
                        .then(response => response.json())
                        .then(config => {
                            const emailData = {
                                service_id: config.service_id,
                                template_id: config.template_message,
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
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Error al enviar el correo de verificación.');
                            }
                            return response.text(); // Cambiar a text() en lugar de json()
                        })
                        .then(() => {
                            // Guardar el código en el servidor
                            return fetch('guardar_codigo.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({ codigo_verificacion: codigo })
                            });
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    title: 'Registro exitoso',
                                    text: 'Por favor, verifica tu correo para completar el registro.',
                                    icon: 'success',
                                    confirmButtonText: 'Aceptar'
                                }).then(() => {
                                    // Agregar el código al formulario y enviarlo
                                    const inputCodigo = document.createElement('input');
                                    inputCodigo.type = 'hidden';
                                    inputCodigo.id = 'codigo_verificacion';
                                    inputCodigo.name = 'codigo_verificacion';
                                    inputCodigo.value = codigo;
                                    document.getElementById('registroForm').appendChild(inputCodigo);
                                    document.getElementById('registroForm').submit();
                                });
                            } else {
                                throw new Error('Error al guardar el código en el servidor.');
                            }
                        });
                }
            });
        })
        .catch(error => {
            console.error('Error en el proceso de registro:', error);
            Swal.fire({
                title: 'Error',
                text: 'Hubo un problema al procesar el registro. Por favor, inténtalo de nuevo.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        });
    });
});
