<?php

class Pedido
{
    private $pedido;
    private $longitud;
    private $latitud;
    private $volumen_total;
    private $estatus;
    private $largo_maximo;
    private $ancho_maximo;
    private $alto_maximo;
    private $fecha;
    private $cantidad;
    private $foraneo;

    public function __construct($pedido, $estatus, $largo_maximo, $alto_maximo, $ancho_maximo, $volumen_total, $fecha, $cantidad, $foraneo, $latitud, $longitud)
    {
        $this->pedido = $pedido;
        $this->estatus = $estatus;
        $this->largo_maximo = $largo_maximo;
        $this->ancho_maximo = $ancho_maximo;
        $this->alto_maximo = $alto_maximo;
        $this->volumen_total = $volumen_total;
        $this->fecha = $fecha;
        $this->cantidad = $cantidad;
        $this->foraneo = $foraneo;
        $this->latitud = $latitud;
        $this->longitud = $longitud;
    }

    // Getters
    public function getPedido()
    {
        return $this->pedido;
    }

    public function getLongitud()
    {
        return $this->longitud;
    }

    public function getLatitud()
    {
        return $this->latitud;
    }

    public function getVolumenTotal()
    {
        return $this->volumen_total;
    }

    public function getEstatus()
    {
        return $this->estatus;
    }

    public function getLargoMaximo()
    {
        return $this->largo_maximo;
    }

    public function getAnchoMaximo()
    {
        return $this->ancho_maximo;
    }

    public function getAltoMaximo()
    {
        return $this->alto_maximo;
    }

    public function getFecha()
    {
        return $this->fecha;
    }

    public function getCantidad()
    {
        return $this->cantidad;
    }

    public function getForaneo()
    {
        return $this->foraneo;
    }

    public function setCoordenadas($lat, $lng)
    {
        $this->latitud = $lat;
        $this->longitud = $lng;
    }
}
