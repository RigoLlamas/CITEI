/*
$(document).ready(function () {
    $('#recuperacionForm').submit(function (event) {
        event.preventDefault(); // Evita el envío del formulario por defecto

        // Realiza una solicitud AJAX a obtener_correo.php para obtener el correo de la sesión
        $.ajax({
            url: '../php/obtener_correo.php',
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                // Verifica si la respuesta contiene el correo
                if (response.correo) {
                    var emailUsuario = response.correo;

                    // Genera un código de verificación de 6 dígitos
                    var codigo = Math.floor(100000 + Math.random() * 900000);
                    localStorage.setItem('codigo_verificacion', codigo); // Almacena el código en localStorage

                    // Datos para enviar el correo usando EmailJS
                    var data = {
                        service_id: 'service_xigafai', // Reemplazar con el ID de tu servicio
                        template_id: 'template_92c5pvi', // Reemplazar con el ID de tu plantilla
                        user_id: '7gg6Y8y5zhCzIx7qS', // Reemplazar con tu user_id de EmailJS
                        template_params: {
                            to_email: emailUsuario,
                            message: 'Tu código de verificación es: ' + codigo
                        }
                    };

                    // Realiza el envío del correo
                    $.ajax('https://api.emailjs.com/api/v1.0/email/send', {
                        type: 'POST',
                        data: JSON.stringify(data), // Envía los datos en formato JSON
                        contentType: 'application/json', // Define que el contenido es JSON
                    }).done(function () {
                        // Si el correo se envió correctamente, guarda el código en el servidor
                        $.post('guardar_codigo.php', { codigo_verificacion: codigo }, function (response) {
                            // Añade el código al formulario como campo oculto
                            $('<input>').attr({
                                type: 'hidden',
                                id: 'codigo_verificacion',
                                name: 'codigo_verificacion',
                                value: codigo
                            }).appendTo('#recuperacionForm'); // Asegúrate de que sea el formulario correcto
                            // Envía finalmente el formulario
                            $('#recuperacionForm')[0].submit();
                        }).fail(function (error) {
                            console.log('Error al guardar el código en el servidor: ' + JSON.stringify(error));
                        });
                    }).fail(function (error) {
                        console.log('Hubo un problema al enviar el correo: ' + JSON.stringify(error));
                    });

                } else {
                    // Si no se encontró el correo, muestra un mensaje de error
                    console.log('No se encontró el correo del usuario en la sesión.');
                }
            },
            error: function (xhr, status, error) {
                // Manejo de errores en caso de que la solicitud a obtener_correo.php falle
                console.log('Error al obtener el correo de la sesión: ' + error);
            }
        });
    });
});
*/
$(document).ready(function () {
    $('#recuperacionForm').submit(function (event) {
        event.preventDefault(); // Evita el envío por defecto del formulario

        var emailUsuario = $('#correo').val(); // Obtener el correo ingresado

        // Verificar si el campo de correo no está vacío
        if (emailUsuario.trim() === '') {
            console.log("El campo de correo está vacío.");
            return;
        }

        // Realizar una solicitud AJAX a olvido_contraseña.php para validar el correo
        $.ajax({
            url: 'olvido_contraseña.php', // Archivo PHP que valida el correo y almacena la sesión
            method: 'POST',
            dataType: 'json',
            data: { correo: emailUsuario }, // Pasar el correo al servidor
            success: function (response) {
                // Verificar si la respuesta contiene un estado de éxito
                if (response.status === "success") {
                    console.log("Correo válido. Continuando con el envío...");

                    // Generar un código de verificación de 6 dígitos
                    var codigo = Math.floor(100000 + Math.random() * 900000);
                    localStorage.setItem('codigo_verificacion', codigo); // Almacena el código en localStorage

                    // Datos para enviar el correo usando EmailJS
                    var data = {
                        service_id: 'service_xigafai', // Reemplazar con el ID de tu servicio
                        template_id: 'template_m0cj8kg', // Reemplazar con el ID de tu plantilla
                        user_id: '7gg6Y8y5zhCzIx7qS', // Reemplazar con tu user_id de EmailJS
                        template_params: {
                            to_email: emailUsuario,
                            message: 'Tu código de verificación es: ' + codigo
                        }
                    };

                    // Realizar el envío del correo
                    $.ajax('https://api.emailjs.com/api/v1.0/email/send', {
                        type: 'POST',
                        data: JSON.stringify(data), // Envía los datos en formato JSON
                        contentType: 'application/json', // Define que el contenido es JSON
                    }).done(function () {
                        console.log("Correo enviado exitosamente.");

                        // Redirigir a la página de ingreso de clave
                        window.location.href = 'ingresar_clave.php';
                    }).fail(function (error) {
                        console.log("Hubo un problema al enviar el correo: " + JSON.stringify(error));
                    });

                } else {
                    console.log(response.message); // Mostrar el mensaje de error del servidor
                }
            },
            error: function (xhr, status, error) {
                console.log('Error en la validación del correo: ' + error);
            }
        });
    });
});
