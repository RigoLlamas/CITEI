// Limpia los marcadores existentes
function clearMarkers() {
    markers.forEach((marker) => marker.setMap(null));
    markers = [];
}

// Función para actualizar la ubicación del marcador en el mapa
function updateMarker(lat, lng, title) {
    const position = {
        lat: parseFloat(lat),
        lng: parseFloat(lng)
    };
    defaultMarker.setPosition(position);
    defaultMarker.setTitle(title);
    map.setCenter(position);
}

// Función para obtener las coordenadas actualizadas del repartidor
function fetchUpdatedCoordinates(nomina) {
    fetch(`actualizar_coordenadas.php?nomina=${nomina}`)
        .then(response => response.json())
        .then(data => {
            if (!data.error) {
                // Convertir a número (float) para evitar problemas de tipos
                let newLat = parseFloat(data.Latitud);
                let newLng = parseFloat(data.Longitud);

                // Validamos si las coordenadas han cambiado
                if (currentLat !== newLat || currentLng !== newLng) {
                    currentLat = newLat;
                    currentLng = newLng;
                    updateMarker(newLat, newLng, `Repartidor ${nomina}`);
                }
            } else {
                console.error(data.error);
            }
        })
        .catch(error => console.error('Error al obtener coordenadas:', error));
}

// Configura el intervalo para actualizar la ubicación cada X tiempo
function startUpdatingCoordinates(nomina, interval) {
    if (intervalId) clearInterval(intervalId);
    intervalId = setInterval(() => fetchUpdatedCoordinates(nomina), interval);
}

// Detiene la actualización de coordenadas
function stopUpdatingCoordinates() {
    if (intervalId) clearInterval(intervalId);
}

// Función para retirar un pedido de la ruta
function retirarPedido(entregaId, nomina) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¿Deseas retirar este pedido de la ruta?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, retirar pedido',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`retirar_pedido.php?entrega=${entregaId}&nomina=${nomina}`, {
                method: 'GET'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire(
                            'Pedido retirado',
                            'El pedido ha sido retirado con éxito.',
                            'success'
                        ).then(() => location.reload());
                    } else {
                        Swal.fire(
                            'Error',
                            `Error al retirar el pedido: ${data.error}`,
                            'error'
                        );
                    }
                })
                .catch(error => {
                    Swal.fire(
                        'Error',
                        'Hubo un problema al procesar tu solicitud.',
                        'error'
                    );
                });
        }
    });
}

// Función para cancelar toda la ruta
function cancelarRuta(nomina) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¿Deseas cancelar toda la ruta de este repartidor?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, cancelar ruta',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`cancelar_ruta.php?nomina=${nomina}`, {
                method: 'GET'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire(
                            'Ruta cancelada',
                            'La ruta ha sido cancelada con éxito.',
                            'success'
                        ).then(() => window.location.href = `rutas.php`);
                    } else {
                        Swal.fire(
                            'Error',
                            `Error al cancelar la ruta: ${data.error}`,
                            'error'
                        );
                    }
                })
                .catch(error => {
                    Swal.fire(
                        'Error',
                        'Hubo un problema al procesar tu solicitud.',
                        'error'
                    );
                });
        }
    });
}

function ejecutarPHP() {
    Swal.fire({
        title: 'Calculando nuevas rutas...',
        text: 'Por favor, espera mientras procesamos los datos.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    fetch('../algoritmo_de_reparticion/algoritmo_de_reparticion.php')
        .then(response => response.json())
        .then(data => {
            Swal.close();

            if (data.status === 'success') {
                Swal.fire({
                    title: '¡Éxito!',
                    text: data.message,
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.message,
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    location.reload();
                });
            }
        })
        .catch(error => {
            Swal.close();
            Swal.fire({
                title: 'Error',
                text: 'Error:'.data.message,
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        });
}