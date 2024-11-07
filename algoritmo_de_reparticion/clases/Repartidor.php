<?php
class Repartidor {
    public $matricula;
    public $nomina;
    public $volumenOcupado = 0; // Volumen actualmente ocupado en el vehículo
    public $largo;
    public $alto;
    public $ancho;
    public $tiempo;             // Hora actual del repartidor
    public $hora_limite;        // Hora límite de trabajo
    private $hora_inicio;       // Hora de inicio de trabajo
    public $es_foraneo;         // Indica si el repartidor es foráneo
    public $latitud;            // Latitud actual del repartidor
    public $longitud;           // Longitud actual del repartidor

    public function __construct($matricula, $nomina, $largo, $alto, $ancho, $hora_limite = '18:30', $es_foraneo = false) {
        $this->matricula = $matricula;
        $this->nomina = $nomina;
        $this->volumenOcupado = 0;
        $this->largo = $largo;
        $this->alto = $alto;
        $this->ancho = $ancho;
        $this->hora_limite = $hora_limite;
        $this->hora_inicio = new DateTime('09:00'); 
        $this->tiempo = clone $this->hora_inicio; // Hora actual del repartidor, inicia a las 09:00
        $this->es_foraneo = $es_foraneo;
    }

    // Calcula el volumen total del vehículo basado en sus dimensiones
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
        $volumenAdecuado = $volumenPedido <= $this->calcularVolumenDisponible();
        $dimensionesAdecuadas = $largoPedido <= $this->largo && $altoPedido <= $this->alto && $anchoPedido <= $this->ancho;
        return $volumenAdecuado && $dimensionesAdecuadas;
    }

    // Método para agregar un pedido si cumple con el volumen, dimensiones y tiempo
    public function agregarPedido($volumenPedido, $largoPedido, $altoPedido, $anchoPedido, $tiempoEstimado, $tiempoEntreNodos) {
        $tiempoConImprevistos = $tiempoEstimado + $tiempoEntreNodos + 10; // Añade 10 minutos de imprevistos

        // Agregar tiempo de comida si no es foráneo y aún no se ha añadido
        if (!$this->es_foraneo && !$this->tieneTiempoDeComida()) {
            $this->tiempo->modify('+60 minutes'); // Añadir una hora para la comida
            echo "Tiempo de comida añadido para el repartidor {$this->nomina}.<br>";
        }

        // Verificar volumen y dimensiones antes de agregar
        if (!$this->puedeTransportarPedido($volumenPedido, $largoPedido, $altoPedido, $anchoPedido)) {
            echo "El repartidor {$this->nomina} no puede transportar el pedido debido a restricciones de volumen o dimensiones.<br>";
            return false;
        }

        // Crear una instancia temporal del tiempo estimado de fin del pedido
        $horaEstimadaFin = clone $this->tiempo;
        $horaEstimadaFin->modify("+{$tiempoConImprevistos} minutes");

        // Verificar si el repartidor puede trabajar hasta la hora límite
        if (!$this->puedeTrabajarHasta($horaEstimadaFin)) {
            echo "El repartidor {$this->nomina} no puede trabajar hasta la hora límite con el pedido actual.<br>";
            return false;
        }

        // Asignar el pedido y actualizar tiempo y volumen ocupado
        $this->actualizarVolumenOcupado($volumenPedido);
        $this->tiempo = $horaEstimadaFin; // Actualizar el tiempo del repartidor con la nueva hora estimada
        echo "Pedido asignado al repartidor {$this->nomina}. Volumen ocupado actualizado: {$this->volumenOcupado}.<br>";
        return true;
    }

    // Verificar si el repartidor puede trabajar hasta una hora límite estimada
    public function puedeTrabajarHasta(DateTime $horaEstimadaFin) {
        $horaLimite = new DateTime($this->hora_limite); // Convertir hora límite a DateTime
        return $horaEstimadaFin <= $horaLimite; // Comparar la hora de fin estimada con la hora límite
    }

    // Verificar si el repartidor ya ha tenido tiempo de comida
    private function tieneTiempoDeComida() {
        $horasTrabajadas = $this->hora_inicio->diff($this->tiempo)->h;
        return $horasTrabajadas >= 4;
    }

    // Actualizar la ubicación del repartidor después de asignar un pedido
    public function actualizarUbicacion($latitud, $longitud) {
        $this->latitud = $latitud;
        $this->longitud = $longitud;
        echo "Ubicación del repartidor {$this->nomina} actualizada a: {$this->latitud}, {$this->longitud}.<br>";
    }
}
?>
