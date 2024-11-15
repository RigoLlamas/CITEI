<?php

$config = require '../config.php';

function enviarCorreoNotificacion($correoDestino, $mensaje) {
    // URL de la API de EmailJS
    $url = 'https://api.emailjs.com/api/v1.0/email/send';

    // Obtener configuración de EmailJS desde config.php
    global $config;
    $service_id = $config['email_service']['service_id'];
    $template_id = $config['email_service']['template_id'];
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

    // Configuración del contexto de la solicitud
    $options = array(
        'http' => array(
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
            'timeout' => 10
        )
    );
    $context  = stream_context_create($options);

    // Ejecutar la solicitud
    $result = file_get_contents($url, false, $context);

    // Manejo de errores
    if ($result === FALSE) {
        error_log("Error al enviar el correo");
        return false;
    }

    $response_data = json_decode($result, true);
    if (isset($response_data['status']) && $response_data['status'] === 'OK') {
        error_log("Correo enviado exitosamente: " . print_r($response_data, true));
        return true;
    } else {
        error_log("Error en la respuesta de EmailJS: " . print_r($response_data, true));
        return false;
    }
}
?>

