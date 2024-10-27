// Función para eliminar repartidor
function eliminarRepartidor(nomina) {
    if (confirm('¿Seguro que deseas eliminar este repartidor?')) {
        window.location.href = 'eliminar_repartidor.php?nomina=' + nomina;
    }
}

// Función para modificar repartidor
function modificarRepartidor(nomina) {
    window.location.href = 'modificar_repartidor.php?nomina=' + nomina;
}
