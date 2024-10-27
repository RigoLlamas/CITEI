document.addEventListener('DOMContentLoaded', function () {
    // Función para confirmar una operación
    function confirmarOperacion(mensaje = "¿Desea completar esta operación?", callback = null) {
        Swal.fire({
            title: mensaje,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed && callback) {
                callback(); // Si se confirma, ejecutar el callback si se proporciona
            }
        });
    }

    // Función para confirmar eliminación
    function confirmarEliminacion(mensaje = "¿Desea eliminar permanentemente?", callback = null) {
        Swal.fire({
            title: mensaje,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed && callback) {
                callback(); // Si se confirma, ejecutar el callback si se proporciona
            }
        });
    }

    // Captura el botón de confirmar operación
    const botonConfirmar = document.getElementById('confirmacion');
    if (botonConfirmar) { // Validar que el botón existe
        botonConfirmar.addEventListener('click', function (e) {
            e.preventDefault(); // Evitar que el enlace recargue la página
            confirmarOperacion("¿Desea completar esta operación?", function () {
                // Aquí defines lo que sucede cuando se confirma la operación
                console.log("Operación completada.");

                var formulario = botonConfirmar.closest('form');
                if (formulario) {
                    formulario.submit(); // Enviar el formulario
                }

                window.history.back();
            });
        });
    }

    // Captura el botón de eliminar
    const botonEliminar = document.getElementById('eliminar');
    if (botonEliminar) { // Validar que el botón existe
        botonEliminar.addEventListener('click', function (e) {
            e.preventDefault(); // Evitar que el enlace recargue la página
            confirmarEliminacion("¿Desea eliminar permanentemente?", function () {
                // Aquí defines lo que sucede cuando se confirma la eliminación
                console.log("Elemento eliminado.");

                var formulario = botonConfirmar.closest('form');
                if (formulario) {
                    formulario.submit(); // Enviar el formulario
                }
                
                window.history.back();
            });
        });
    }

    // Capturar todos los enlaces con la clase 'confirmar-accion' 
    const enlacesEliminar = document.querySelectorAll('.confirmar-accion');
    
    // Añadir evento a cada enlace
    enlacesEliminar.forEach(function(enlace) {
        enlace.addEventListener('click', function (e) {
            e.preventDefault(); // Evitar la redirección inmediata
            
            const url = this.href; // Obtener la URL del enlace

            // Llamar a la función de confirmación
            confirmarEliminacion("¿Desea eliminar permanentemente?", function () {
                // Si se confirma, redirigir a la URL original

                window.location.href = url;
            });
        });
    });
});
