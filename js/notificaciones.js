// Variables para la configuración
let userID, serviceID, templateID;

// Cargar la configuración desde obtener_gmail_config.php
fetch('../php/obtener_email_config.php')
    .then(response => response.json())
    .then(config => {
        userID = config.user_id;
        serviceID = config.service_id;
        templateID = config.template_message; // Usa el nombre correcto del template en config.php

        // Inicializa EmailJS con userID después de cargar la configuración
        emailjs.init(userID);
    })
    .catch(error => {
        console.error('Error al cargar la configuración de EmailJS:', error);
    });

// Función para enviar correos con EmailJS
function enviarCorreoNotificacion(correoDestino, mensaje) {
    const templateParams = {
        to_email: correoDestino,
        message: mensaje    
    };

    const options = { user_id: userID };

    // Verificar que la configuración esté cargada antes de enviar el correo
    if (!serviceID || !templateID || !userID) {
        console.error("Configuración de EmailJS no cargada correctamente.");
        return Promise.reject({ success: false, error: "Configuración no cargada" });
    }

    return emailjs.send(serviceID, templateID, templateParams, options)
        .then(response => {
            return { success: true };
        })
        .catch(error => {
            return { success: false, error: error };
        });
}

// Función para actualizar el estado del pedido y manejar el envío de correos
async function actualizarEstado(numVenta, nuevoEstado) {
    try {
        const response = await fetch('actualizar_estado.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ numVenta, nuevoEstado })
        });
        const data = await response.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Estado actualizado',
                text: data.message,
                confirmButtonText: 'OK'
            });

            // Si hay información de correo en la respuesta, envía la notificación
            if (data.email) {
                const { correoDestino, mensaje } = data.email;
                const emailResponse = await enviarCorreoNotificacion(correoDestino, mensaje);
                
                if (!emailResponse.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al enviar el correo',
                        text: 'El estado fue actualizado, pero hubo un error al enviar el correo.',
                        confirmButtonText: 'OK'
                    });
                }
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonText: 'OK'
            });
        }
    } catch (error) {
        console.error("Error en la solicitud de actualización:", error);
        Swal.fire({
            icon: 'error',
            title: 'Error en la solicitud',
            text: 'No se pudo actualizar el estado del pedido.',
            confirmButtonText: 'OK'
        });
    }
}

