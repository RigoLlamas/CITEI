function buscarFormularios() {
    const busqueda = document.getElementById('busqueda').value.toLowerCase();
    const filas = document.querySelectorAll('#tablaFormularios tbody tr');
    let resultados = false;

    filas.forEach(fila => {
        const texto = fila.innerText.toLowerCase();
        if (texto.includes(busqueda)) {
            fila.style.display = '';
            resultados = true;
        } else {
            fila.style.display = 'none';
        }
    });

    const sinResultados = document.querySelector('.sin-resultados');
    if (!resultados && sinResultados) {
        sinResultados.style.display = 'block';
    } else if (sinResultados) {
        sinResultados.style.display = 'none';
    }
}

function confirmarEliminacion(id) {
Swal.fire({
    title: '¿Estás seguro?',
    text: "No podrás revertir esta acción.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
}).then((result) => {
    if (result.isConfirmed) {
        // Redirigir al archivo de eliminación con el ID del formulario
        window.location.href = `eliminar_formulario.php?id=${id}`;
    }
});
}