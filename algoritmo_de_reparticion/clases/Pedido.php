<?php
class Pedido {
    public $pedido;
    public $longitud;
    public $latitud;
    public $distancia;
    public $municipio;
    public $volumen_total;
    public $estatus;
    public $largo_maximo;
    public $ancho_maximo;
    public $alto_maximo;

    public function __construct($pedido, $longitud, $latitud, $distancia, $municipio, $volumen_total, $estatus, $largo_maximo, $ancho_maximo, $alto_maximo) {
        $this->pedido = $pedido;
        $this->longitud = $longitud;
        $this->latitud = $latitud;
        $this->distancia = $distancia;
        $this->municipio = $municipio;
        $this->volumen_total = $volumen_total;
        $this->estatus = $estatus;
        $this->largo_maximo = $largo_maximo;
        $this->ancho_maximo = $ancho_maximo;
        $this->alto_maximo = $alto_maximo;
    }

    public function calcularVolumenTotal() {
        return $this->largo_maximo * $this->ancho_maximo * $this->alto_maximo;
    }

    public function actualizarDistancia($nuevaDistancia) {
        $this->distancia = $nuevaDistancia;
    }
}
?>
