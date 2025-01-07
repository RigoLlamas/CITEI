<?php

class Repartidor
{
    private $nomina;
    private $nombre;
    private $apellidos;
    private $estado;
    private $clave;
    private $latitud;
    private $longitud;
    private $descanso;
    private $horaBandera;
    private $vehiculo;
    private $pedidosAsignados;
    private $volumenOcupado;
    private $tiempo;
    private $numOrdenActual;
    private $esForaneo;
    private $horaLimite;

    public function __construct(
        $nomina,
        $nombre,
        $apellidos,
        $latitud,
        $longitud,
        $estado = 'Disponible',
        $descanso = 0,
        $horaBandera = null,
        $vehiculo = null,
        $esForaneo = false,
        $horaLimite = null
    ) {
        $this->nomina = $nomina;
        $this->nombre = $nombre;
        $this->apellidos = $apellidos;
        $this->latitud = $latitud;
        $this->longitud = $longitud;
        $this->estado = $estado;
        $this->descanso = $descanso;

        // Validar y asignar HoraBandera
        if ($horaBandera instanceof DateTime) {
            $this->horaBandera = $horaBandera;
        } elseif (is_string($horaBandera)) {
            try {
                $this->horaBandera = new DateTime($horaBandera);
            } catch (Exception $e) {
                throw new Exception("Formato inválido para HoraBandera: {$horaBandera}");
            }
        } else {
            $this->horaBandera = new DateTime('12:00:00'); // Valor predeterminado
        }

        // Validar y asignar HoraLimite
        if ($horaLimite instanceof DateTime) {
            $this->horaLimite = $horaLimite;
        } elseif (is_string($horaLimite)) {
            try {
                $this->horaLimite = new DateTime($horaLimite);
            } catch (Exception $e) {
                throw new Exception("Formato inválido para HoraLimite: {$horaLimite}");
            }
        } else {
            $this->horaLimite = new DateTime('18:00:00'); // Valor predeterminado
        }

        $this->vehiculo = $vehiculo;
        $this->pedidosAsignados = 0;
        $this->volumenOcupado = 0;
        $this->tiempo = new DateTime('09:00:00'); // Tiempo inicial
        $this->esForaneo = $esForaneo;
        $this->numOrdenActual = 0;
    }



    // Getters
    public function getNomina()
    {
        return $this->nomina;
    }

    public function getNombre()
    {
        return $this->nombre;
    }

    public function getApellidos()
    {
        return $this->apellidos;
    }

    public function getEstado()
    {
        return $this->estado;
    }

    public function getClave()
    {
        return $this->clave;
    }

    public function getLatitud()
    {
        return $this->latitud;
    }

    public function getLongitud()
    {
        return $this->longitud;
    }

    public function getDescanso()
    {
        return $this->descanso;
    }

    public function getHoraBandera()
    {
        return $this->horaBandera;
    }

    public function getVehiculo()
    {
        return $this->vehiculo;
    }

    public function getPedidosAsignados()
    {
        return $this->pedidosAsignados;
    }

    public function setPedidosAsignados($cantidad)
    {
        $this->pedidosAsignados = $cantidad;
    }

    public function getVolumenOcupado()
    {
        return $this->volumenOcupado;
    }

    public function getTiempo()
    {
        if (!$this->tiempo) {
            $this->tiempo = new DateTime();
        }
        return $this->tiempo;
    }


    // Setters
    public function setEstado($estado)
    {
        $allowed_states = ['Disponible', 'Ocupado'];
        if (in_array($estado, $allowed_states)) {
            $this->estado = $estado;
        } else {
            throw new Exception("Estado inválido para repartidor: {$estado}");
        }
    }

    public function setDescanso($descanso)
    {
        if ($descanso === 0 || $descanso === 1) {
            $this->descanso = $descanso;
        } else {
            throw new Exception("Valor de descanso inválido: {$descanso}");
        }
    }

    public function setHoraBandera($horaBandera)
    {
        try {
            $this->horaBandera = new DateTime($horaBandera);
        } catch (Exception $e) {
            throw new Exception("Formato de HoraBandera inválido: {$horaBandera}");
        }
    }

    public function setVehiculo($vehiculo)
    {
        if ($vehiculo instanceof Vehiculo) {
            $this->vehiculo = $vehiculo;
        } else {
            throw new Exception("Vehiculo debe ser una instancia de la clase Vehiculo");
        }
    }

    public function setTiempo($tiempo)
    {
        if ($tiempo instanceof DateTime) {
            $this->tiempo = $tiempo;
        } else {
            throw new Exception("Tiempo debe ser una instancia de DateTime");
        }
    }

    public function puedeTrabajarHasta($horaEstimadaRegreso)
    {
        return $horaEstimadaRegreso <= $this->horaLimite;
    }

    public function esHoraDeDescanso($horaActual)
    {
        if (!$horaActual instanceof DateTime || !$this->horaBandera instanceof DateTime) {
            throw new InvalidArgumentException("Los tiempos deben ser instancias de DateTime.");
        }
        return $horaActual >= $this->horaBandera;
    }

    public function agregarKilometrosVehiculo($kilometros)
    {
        if ($this->vehiculo !== null) {
            $this->vehiculo->agregarKilometros($kilometros);
        } else {
            echo "Repartidor {$this->nomina} no tiene vehículo asignado. No se pueden agregar kilómetros.\n";
        }
    }


    public function incrementarPedidosAsignados()
    {
        $this->pedidosAsignados++;
    }

    public function actualizarVolumenOcupado($volumen)
    {
        $this->volumenOcupado += $volumen;
    }

    public function actualizarUbicacion($latitud, $longitud)
    {
        $this->latitud = $latitud;
        $this->longitud = $longitud;
    }

    public function asignarVehiculo($vehiculo)
    {
        $this->vehiculo = $vehiculo;
    }

    public function actualizarOrden()
    {
        return $this->numOrdenActual += 1;
    }

    public function puedeTransportarPedido($volumenTotal, $largoMaximo, $altoMaximo, $anchoMaximo)
    {
        // Verificar si el repartidor tiene un vehículo asignado
        if ($this->vehiculo === null) {
            echo "El repartidor {$this->nomina} no tiene vehiculon\n";
            return false;
        }

        // Calcular el volumen total después de asignar este pedido
        $volumenDespues = $this->volumenOcupado + $volumenTotal;

        // Obtener la capacidad del vehículo
        $capacidadVehiculo = $this->vehiculo->getCapacidad();

        if ($volumenDespues > $capacidadVehiculo) {
            echo "El repartidor {$this->nomina} no le queda espacio\n";
            return false;
        }

        // Verificar las dimensiones del pedido contra las dimensiones del vehículo
        $largoVehiculo = $this->vehiculo->getLargo();
        $altoVehiculo = $this->vehiculo->getAlto();
        $anchoVehiculo = $this->vehiculo->getAncho();

        if ($largoMaximo > $largoVehiculo || $altoMaximo > $altoVehiculo || $anchoMaximo > $anchoVehiculo) {
            echo "El repartidor {$this->nomina} no puede trasportaar, carga maxima\n";
            return false;
        }

        return true;
    }
}
