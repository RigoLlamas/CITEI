function initMap() {
    const map = new google.maps.Map(document.getElementById("mapa"), {
        center: { lat: 19.4326, lng: -99.1332 }, // Coordenadas de ejemplo (Ciudad de México)
        zoom: 12, // Nivel de zoom
    });

    // Ejemplo de marcador en el mapa
    const marker = new google.maps.Marker({
        position: { lat: 19.4326, lng: -99.1332 },
        map: map,
        title: "Ubicación de ejemplo"
    });
}
