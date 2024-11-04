<?php
class Repartidor {
    public $matricula;
    public $nomina;
    public $volumenOcupado = 0; // Volumen actualmente ocupado en el vehículo
    public $largo;
    public $alto;
    public $ancho;
    public $tiempo;
    public $hora_limite;
    private $hora_inicio;
    public $es_foraneo;
    public $latitud;   // Latitud actual del repartidor
    public $longitud;  // Longitud actual del repartidor

    public function __construct($matricula, $nomina, $largo, $alto, $ancho, $hora_limite = '18:00', $es_foraneo = false) {
        $this->matricula = $matricula;
        $this->nomina = $nomina;
        $this->volumenOcupado = 0;
        $this->largo = $largo;
        $this->alto = $alto;
        $this->ancho = $ancho;
        $this->hora_limite = $hora_limite;
        $this->tiempo = 0;
        $this->hora_inicio = new DateTime('09:00'); 
        $this->es_foraneo = $es_foraneo;
    }

    // Calcula el volumen total del vehículo basado en las dimensiones de la cabina
    public function calcularVolumenTotalVehiculo() {
        return $this->largo * $this->alto * $this->ancho;
    }

    // Calcula el volumen disponible en el vehículo
    public function calcularVolumenDisponible() {
        return $this->calcularVolumenTotalVehiculo() - $this->volumenOcupado;
    }

    // Actualiza el volumen ocupado del vehículo al asignar un pedido
    public function actualizarVolumenOcupado($volumenPedido) {
        $this->volumenOcupado += $volumenPedido;
    }


    // Verifica si el pedido cumple con el volumen y dimensiones del vehículo
    public function puedeTransportarPedido($volumenPedido, $largoPedido, $altoPedido, $anchoPedido) {
        $volumenDisponible = $this->calcularVolumenDisponible();
        $volumenAdecuado = $volumenPedido <= $volumenDisponible;
        $dimensionesAdecuadas = $largoPedido <= $this->largo && $altoPedido <= $this->alto && $anchoPedido <= $this->ancho;
        return $volumenAdecuado && $dimensionesAdecuadas;
    }

    // Método para agregar un pedido si cumple con el volumen, dimensiones y tiempo
    public function agregarPedido($volumenPedido, $largoPedido, $altoPedido, $anchoPedido, $tiempoEstimado, $tiempoEntreNodos) {
        $tiempoConImprevistos = $tiempoEntreNodos + 10;

        // Agrega tiempo de comida si no es foráneo y aún no se ha agregado
        if (!$this->es_foraneo && !$this->tieneTiempoDeComida()) {
            $this->tiempo += 60; // Añadir una hora para comida
        }

        // Verificar volumen y dimensiones antes de agregar
        if (!$this->puedeTransportarPedido($volumenPedido, $largoPedido, $altoPedido, $anchoPedido)) {
            return false; // No se puede asignar debido a restricciones de volumen o dimensiones
        }

        // Verificar si el repartidor puede trabajar hasta la hora límite
        if (!$this->puedeTrabajarHasta($tiempoEstimado + $tiempoConImprevistos)) {
            return false; // No se puede asignar debido a restricciones de tiempo
        }

        // Asignar el pedido y actualizar tiempo y volumen ocupado
        $this->actualizarVolumenOcupado($volumenPedido);
        $this->actualizarTiempo($tiempoEstimado + $tiempoConImprevistos);
        return true;
    }

    // Actualizar el tiempo total trabajado por el repartidor
    public function actualizarTiempo($incremento) {
        $this->tiempo += $incremento;
    }

    // Verificar si el repartidor puede trabajar hasta una hora límite estimada
    public function puedeTrabajarHasta($tiempoEstimado) {
        $hora_inicio = clone $this->hora_inicio;
        $hora_inicio->modify("+{$this->tiempo} minutes");
        $hora_final = clone $hora_inicio;
        $hora_final->modify("+{$tiempoEstimado} minutes");

        $hora_limite = new DateTime($this->hora_limite);
        return $hora_final <= $hora_limite;
    }

    // Verificar si el repartidor ya ha tenido tiempo de comida
    private function tieneTiempoDeComida() {
        return $this->tiempo >= 60;
    }
}

