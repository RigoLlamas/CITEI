document.addEventListener('DOMContentLoaded', function() {
    const menu = document.getElementById('menu');
    const hamburger = document.getElementById('hamburger');

    hamburger.addEventListener('click', function() {
        // Alternamos entre mostrar y ocultar el menú
        menu.classList.toggle('active');
    });
});