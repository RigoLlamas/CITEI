function ajustarValorMaximo() {
    var select = document.getElementById('canjeable_porcentual');
    var valorInput = document.getElementById('valor');
    
    if (select.value === 'canjeable') {
        valorInput.max = 1000; // Límite para "Canjeable"
    } else if (select.value === 'porcentual') {
        valorInput.max = 100; // Límite para "Porcentual"
    } else {
        valorInput.removeAttribute('max'); // Sin límite si no hay selección válida
    }
}

// Función para evitar fechas pasadas
function setMinDate() {
    var today = new Date().toISOString().split('T')[0]; // Obtiene la fecha actual en formato YYYY-MM-DD
    document.getElementById('despliegue').setAttribute('min', today);
    document.getElementById('expiracion').setAttribute('min', today);
}

// Ejecutar al cargar la página para aplicar el límite inicial basado en la opción preseleccionada
document.addEventListener('DOMContentLoaded', function() {
    ajustarValorMaximo();
    setMinDate(); // Establecer la fecha mínima al cargar la página
});
