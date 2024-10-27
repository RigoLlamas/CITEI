function actualizarDias() {
    const mes = document.getElementById("mes").value;
    const anio = document.getElementById("anio").value || new Date().getFullYear();
    const diaSelect = document.getElementById("dia");

    // Limpiar opciones anteriores
    diaSelect.innerHTML = '<option value="">Todos</option>';

    if (mes) {
        // Obtener el último día del mes seleccionado
        const ultimoDia = new Date(anio, mes, 0).getDate();

        // Generar las opciones de día según el último día del mes
        for (let i = 1; i <= ultimoDia; i++) {
            const option = document.createElement("option");
            option.value = i;
            option.textContent = i;
            diaSelect.appendChild(option);
        }
    }
}
