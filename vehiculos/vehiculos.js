// Función para eliminar vehículo
function eliminarVehiculo(placa) {
    if (confirm('¿Seguro que deseas eliminar este vehículo?')) {
        window.location.href = 'eliminar_vehiculo.php?placa=' + placa;
    }
}

// Función para modificar vehículo
function modificarVehiculo(placa) {
    window.location.href = 'modificar_vehiculo.php?placa=' + placa;
}
