// Configuración para notificaciones
let userID, serviceID, templateID;

// Cargar configuración desde obtener_gmail_config.php
fetch('../php/obtener_email_config.php')
    .then(response => response.json())
    .then(config => {
        userID = config.user_id;
        serviceID = config.service_id;
        templateID = config.template_message;

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

// Función para actualizar el estado del pedido
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

// Función para actualizar los días según el mes y año seleccionados
function actualizarDias() {
    const mes = document.getElementById("mes").value;
    const anio = document.getElementById("anio").value || new Date().getFullYear();
    const diaSelect = document.getElementById("dia");

    // Limpiar opciones anteriores
    diaSelect.innerHTML = '<option value="">Todos</option>';

    if (mes) {
        const ultimoDia = new Date(anio, mes, 0).getDate();

        for (let i = 1; i <= ultimoDia; i++) {
            const option = document.createElement("option");
            option.value = i;
            option.textContent = i;
            diaSelect.appendChild(option);
        }
    }
}
