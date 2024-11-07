<?php

class Pedido {
    public $pedido;
    public $longitud;
    public $latitud;
    public $municipio;
    public $volumen_total; // Asignado directamente desde la función obtenerPedidosDesdeBD
    public $estatus;
    public $largo_maximo;
    public $ancho_maximo;
    public $alto_maximo;
    private $direccion;
    private $apiKey;

    public function __construct($pedido, $direccion, $municipio, $estatus, $largo_maximo, $alto_maximo, $ancho_maximo, $volumen_total) {
        $this->pedido = $pedido;
        $this->direccion = $direccion;
        $this->municipio = $municipio;
        $this->estatus = $estatus;
        $this->largo_maximo = $largo_maximo;
        $this->ancho_maximo = $ancho_maximo;
        $this->alto_maximo = $alto_maximo;
        $this->volumen_total = $volumen_total; // Volumen asignado directamente

        // Obtener la API key desde el archivo de configuración
        $config = include '../config.php';
        $this->apiKey = $config['api_keys']['google_maps_api_key'] ?? null;

        // Verificar si la API key está disponible antes de realizar la petición
        if ($this->apiKey) {
            $this->obtenerCoordenadas();
        } else {
            echo "API key de Google Maps no encontrada.<br>";
        }
    }

    private function obtenerCoordenadas() {
        $direccionFormateada = urlencode($this->direccion);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$direccionFormateada}&key={$this->apiKey}";

        $response = @file_get_contents($url);
        if ($response === false) {
            echo "Error al intentar obtener datos de la API de Google Maps.<br>";
            return;
        }

        $data = json_decode($response, true);

        if (isset($data['status']) && $data['status'] === 'OK' && !empty($data['results'])) {
            $coordenadas = $data['results'][0]['geometry']['location'];
            $this->latitud = $coordenadas['lat'];
            $this->longitud = $coordenadas['lng'];
        } else {
            echo "No se pudieron obtener las coordenadas para la dirección: " . htmlspecialchars($this->direccion) . "<br>";
        }
    }
}

?>
