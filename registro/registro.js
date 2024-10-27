$(document).ready(function () {
    $('#registroForm').submit(function (event) {
        event.preventDefault();

        var contrasena = $('#contraseña').val();
        var confirmarContrasena = $('#confirmacion').val();
        var emailUsuario = $('#email').val();

        if (contrasena !== confirmarContrasena) {
            $('#contraseña').val('');  
            $('#contraseña').attr('placeholder', 'Las contraseñas no coinciden');  
     
            $('#confirmacion').val('');  
            $('#confirmacion').attr('placeholder', 'Las contraseñas no coinciden');  
            return; 
        }

        $.ajax({
            url: '../php/verificar_email.php',
            type: 'POST',
            data: {email: emailUsuario},
            success: function(response) {
                var data = JSON.parse(response);
                if (data.exists) {
                    $('#email').val('');
                    $('#email').attr('placeholder', 'Este correo ya está registrado');
                    return;
                } else {
                    var codigo = Math.floor(100000 + Math.random() * 900000);
                    localStorage.setItem('codigo_verificacion', codigo);

                    var data = {
                        service_id: 'service_xigafai',
                        template_id: 'template_m0cj8kg',
                        user_id: '7gg6Y8y5zhCzIx7qS',
                        template_params: {
                            to_email: emailUsuario,
                            message: 'Tu código de verificación es: ' + codigo
                        }
                    };

                    $.ajax('https://api.emailjs.com/api/v1.0/email/send', {
                        type: 'POST',
                        data: JSON.stringify(data),
                        contentType: 'application/json'
                    }).done(function () {
                        $.post('guardar_codigo.php', { codigo_verificacion: codigo }, function(response) {
                            $('<input>').attr({
                                type: 'hidden',
                                id: 'codigo_verificacion',
                                name: 'codigo_verificacion',
                                value: codigo
                            }).appendTo('#registroForm');
                            $('#registroForm')[0].submit();
                        });
                    }).fail(function (error) {
                        alert('Hubo un problema al enviar el correo: ' + JSON.stringify(error));
                    });
                }
            }
        });
    });
});
