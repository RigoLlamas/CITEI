<?php
class Pedido {
    public $pedido;
    public $longitud;
    public $latitud;
    public $municipio;
    public $volumen_total;
    public $estatus;
    public $largo_maximo;
    public $ancho_maximo;
    public $alto_maximo;
    private $direccion;
    private $apiKey;

    public function __construct($pedido, $direccion, $municipio, $estatus, $largo_maximo, $ancho_maximo, $alto_maximo) {
        $this->pedido = $pedido;
        $this->direccion = $direccion;
        $this->municipio = $municipio;
        $this->estatus = $estatus;
        $this->largo_maximo = $largo_maximo;
        $this->ancho_maximo = $ancho_maximo;
        $this->alto_maximo = $alto_maximo;
        $this->volumen_total = $this->calcularVolumenPaquete();

        // Obtener la API key de configuración una sola vez
        $config = include '../../config.php';
        $this->apiKey = $config['api_keys']['google_maps_api_key'];

        // Obtener las coordenadas basado en la dirección
        $this->obtenerCoordenadas();
    }

    private function obtenerCoordenadas() {
        $direccionFormateada = urlencode($this->direccion);
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$direccionFormateada}&key={$this->apiKey}";

        $response = @file_get_contents($url);
        if ($response === false) {
            // Log error aquí si es posible
            echo "Error al intentar obtener datos de la API de Google Maps.<br>";
            return;
        }

        $data = json_decode($response, true);

        if (isset($data['status']) && $data['status'] === 'OK') {
            $coordenadas = $data['results'][0]['geometry']['location'];
            $this->latitud = $coordenadas['lat'];
            $this->longitud = $coordenadas['lng'];
        } else {
            echo "No se pudieron obtener las coordenadas para la dirección: " . htmlspecialchars($this->direccion) . "<br>";
        }
    }

    public function calcularVolumenPaquete() {
        // Asegurar que todas las dimensiones sean numéricas antes de calcular el volumen
        $largo = is_numeric($this->largo_maximo) ? $this->largo_maximo : 0;
        $ancho = is_numeric($this->ancho_maximo) ? $this->ancho_maximo : 0;
        $alto = is_numeric($this->alto_maximo) ? $this->alto_maximo : 0;
    
        return $largo * $ancho * $alto;
    }
}
?>

