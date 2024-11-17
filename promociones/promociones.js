

// Ejecutar al cargar la página para aplicar el límite inicial basado en la opción preseleccionada
document.addEventListener('DOMContentLoaded', function() {
    var today = new Date().toISOString().split('T')[0]; // Obtiene la fecha actual en formato YYYY-MM-DD
    document.getElementById('despliegue').setAttribute('min', today);
    document.getElementById('expiracion').setAttribute('min', today);
});
