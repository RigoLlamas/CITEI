<?php
class Repartidor {
    public $matricula;
    public $nomina;
    public $volumen;
    public $largo;
    public $alto;
    public $ancho;
    public $tiempo;

    public function __construct($matricula, $nomina, $volumen, $largo, $alto, $ancho, $tiempo = 0) {
        $this->matricula = $matricula;
        $this->nomina = $nomina;
        $this->volumen = $volumen;
        $this->largo = $largo;
        $this->alto = $alto;
        $this->ancho = $ancho;
        $this->tiempo = $tiempo;
    }

    // Método para calcular el volumen total disponible en el vehículo del repartidor
    public function calcularVolumenDisponible() {
        return ($this->largo * $this->ancho * $this->alto) - $this->volumen;
    }

    // Método para verificar si un pedido cabe en el volumen disponible
    public function puedeTransportar($volumenPedido) {
        return $volumenPedido <= $this->calcularVolumenDisponible();
    }

    // Método para agregar el volumen de un pedido y reducir el espacio disponible
    public function agregarPedido($volumenPedido, $tiempoEstimado) {
        if ($this->puedeTransportar($volumenPedido)) {
            $this->volumen += $volumenPedido;  // Incrementa el volumen ocupado
            $this->actualizarTiempo($tiempoEstimado);  // Agrega tiempo estimado de entrega
            return true;
        }
        return false;
    }

    // Método para actualizar el tiempo estimado de entrega
    public function actualizarTiempo($incremento) {
        $this->tiempo += $incremento;
    }
}
?>
