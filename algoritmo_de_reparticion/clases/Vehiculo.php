<?php

class Vehiculo
{
    private $placa;
    private $largo;
    private $alto;
    private $ancho;
    private $modelo;
    private $estado;
    private $kilometrosRecorridos;

    public function __construct($placa, $largo, $alto, $ancho, $modelo, $estado, $kilometrosRecorridos)
    {
        $this->placa = $placa;
        $this->largo = $largo;
        $this->alto = $alto;
        $this->ancho = $ancho;
        $this->modelo = $modelo;
        $this->estado = $estado;
        $this->kilometrosRecorridos = $kilometrosRecorridos;
    }

    // Getters
    public function getPlaca()
    {
        return $this->placa;
    }

    public function getLargo()
    {
        return $this->largo;
    }

    public function getAlto()
    {
        return $this->alto;
    }

    public function getAncho()
    {
        return $this->ancho;
    }

    public function getModelo()
    {
        return $this->modelo;
    }

    public function getEstado()
    {
        return $this->estado;
    }

    public function getKilometrosRecorridos()
    {
        return $this->kilometrosRecorridos;
    }

    // Setters
    public function setEstado($estado)
    {
        $allowed_states = ['En circulación', 'Fuera de servicio'];
        if (in_array($estado, $allowed_states)) {
            $this->estado = $estado;
        } else {
            throw new Exception("Estado inválido para vehículo: {$estado}");
        }
    }

    public function setKilometrosRecorridos($kilometros)
    {
        if (is_numeric($kilometros) && $kilometros >= 0) {
            $this->kilometrosRecorridos = $kilometros;
        } else {
            throw new Exception("Kilómetros recorridos inválidos: {$kilometros}");
        }
    }

    // Método para agregar kilómetros
    public function agregarKilometros($kilometros)
    {
        if (is_numeric($kilometros) && $kilometros >= 0) {
            $this->kilometrosRecorridos += $kilometros;
        } else {
            throw new Exception("Kilómetros a agregar inválidos: {$kilometros}");
        }
    }

    /**
     * Calcula la capacidad total del vehículo en volumen.
     *
     * @return float Capacidad total en unidades cúbicas.
     */
    public function getCapacidad()
    {
        if ($this->largo <= 0 || $this->alto <= 0 || $this->ancho <= 0) {
            echo "Dimensiones inválidas para el vehículo con placa {$this->placa}.\n";
            return 0;
        }
        return $this->largo * $this->alto * $this->ancho;
    }
    
}
