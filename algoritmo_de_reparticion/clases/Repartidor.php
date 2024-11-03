<?php
class Repartidor {
    public $matricula;
    public $nomina;
    public $volumendisponible; // Volumen actualmente disponible en el vehículo
    public $largo;
    public $alto;
    public $ancho;
    public $tiempo;
    public $hora_limite;
    private $hora_inicio;
    public $es_foraneo;

    public function __construct($matricula, $nomina, $largo, $alto, $ancho, $hora_limite = '18:00', $es_foraneo = false, $tiempo = 0) {
        $this->matricula = $matricula;
        $this->nomina = $nomina;
        $this->volumendisponible = $largo * $ancho * $alto; // Volumen total inicial
        $this->largo = $largo;
        $this->alto = $alto;
        $this->ancho = $ancho;
        $this->hora_limite = $hora_limite;
        $this->tiempo = $tiempo;
        $this->hora_inicio = new DateTime('09:00'); 
        $this->es_foraneo = $es_foraneo;
    }

    // Verifica si el pedido cumple tanto con el volumen como con las dimensiones máximas del vehículo
    public function puedeTransportarPedido($volumenPedido, $largoPedido, $altoPedido, $anchoPedido) {
        $dimensionesCorrectas = $largoPedido <= $this->largo && $altoPedido <= $this->alto && $anchoPedido <= $this->ancho;
        return $volumenPedido <= $this->volumendisponible && $dimensionesCorrectas;
    }

    // Método para reducir el volumen disponible cuando se asigna un pedido
    public function asignarVolumenPedido($volumenPedido) {
        $this->volumendisponible -= $volumenPedido;
    }

    public function agregarPedido($volumenPedido, $tiempoEstimado, $tiempoEntreNodos) {
        $tiempoConImprevistos = $tiempoEntreNodos + 10;

        // Agrega tiempo de comida si no es foráneo y aún no se ha agregado
        if (!$this->es_foraneo && !$this->tieneTiempoDeComida()) {
            $this->tiempo += 60; // Añadir una hora para comida
        }

        if ($this->puedeTransportarPedido($volumenPedido, $this->largo, $this->alto, $this->ancho) && $this->puedeTrabajarHasta($tiempoEstimado + $tiempoConImprevistos)) {
            $this->asignarVolumenPedido($volumenPedido); // Restar el volumen del pedido del volumen disponible
            $this->actualizarTiempo($tiempoEstimado + $tiempoConImprevistos);
            return true;
        }
        return false;
    }

    public function actualizarTiempo($incremento) {
        $this->tiempo += $incremento;
    }

    public function puedeTrabajarHasta($tiempoEstimado) {
        $hora_inicio = clone $this->hora_inicio;
        $hora_inicio->modify("+{$this->tiempo} minutes");
        $hora_final = clone $hora_inicio;
        $hora_final->modify("+{$tiempoEstimado} minutes");

        $hora_limite = new DateTime($this->hora_limite);
        return $hora_final <= $hora_limite;
    }

    private function tieneTiempoDeComida() {
        return $this->tiempo >= 60;
    }
}
?>


