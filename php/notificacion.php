<?php

$config = require '../config.php';

function enviarCorreoNotificacion($correoDestino, $mensaje) {
    // URL de la API de EmailJS
    $url = 'https://api.emailjs.com/api/v1.0/email/send';

    // Obtener configuración de EmailJS desde config.php
    global $config;
    $service_id = $config['email_service']['service_id'];
    $template_id = $config['email_service']['template_message'];
    $user_id = $config['email_service']['user_id'];

    // Datos a enviar
    $data = array(
        'service_id' => $service_id,
        'template_id' => $template_id,
        'user_id' => $user_id,
        'template_params' => array(
            'correo' => $correoDestino,
            'mensaje' => $mensaje
        )
    );

    // Opciones de la solicitud cURL
    $options = array(
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
        CURLOPT_POSTFIELDS => json_encode($data)
    );

    // Inicializar y ejecutar cURL
    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $error = curl_error($ch);

    // Cerrar la conexión cURL
    curl_close($ch);

    if ($error) {
        echo "Error al enviar el correo: $error";
        return false; // Retorna falso en caso de error
    } else {
        echo "Correo enviado exitosamente: $response";
        return true; // Retorna verdadero si el correo fue enviado con éxito
    }
}

// Ejemplo de uso de la función
$correoDestino = 'cliente@ejemplo.com';
$nombreCliente = 'Cliente Ejemplo';
$estatusNuevo = 'Enviado'; // Nuevo estatus del pedido
$numPedido = 12345;

enviarCorreoCambioEstatus($correoDestino, $nombreCliente, $estatusNuevo, $numPedido);
?>
