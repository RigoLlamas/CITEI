<?php

function obtenerCoordenadas($direccion)
{
    $config = include '../config.php';
    $googleMapsApiKey = $config['api_keys']['google_maps_api_key'];
    $direccion = urlencode($direccion);
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$direccion&key=$googleMapsApiKey";

    $response = file_get_contents($url);
    $data = json_decode($response, true);

    // Verifica el estado de la respuesta y su respuesta
    if ($data['status'] == 'OK') {
        $latitud = $data['results'][0]['geometry']['location']['lat'];
        $longitud = $data['results'][0]['geometry']['location']['lng'];

        return [
            'latitud' => $latitud,
            'longitud' => $longitud
        ];
    } else {
        return null;
    }
}

?>