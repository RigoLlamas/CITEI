document.addEventListener("DOMContentLoaded", function () {

    fetch('../php/obtener_usuario.php')
        .then(response => response.json())
        .then(isAdmin => {
            var navegacionUsuario = `
            <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="https://necolas.github.io/normalize.css/8.0.1/normalize.css">
            <link rel="stylesheet" href="../css/style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
            <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

            <header>
                <h1 class="titulo">CITEI</h1>
            </header>
            <div class="nav-bg">
                <nav class="navegacion-principal contenedor">
                    <a href="../login/login.html" aria-label="Ir a la página de inicio de sesión">
                        <img src="../img/logo.png" alt="Logo CITEI - Ir a la página de inicio de sesión">
                    </a>


                    <button class="hamburger" id="hamburger">
                        <i class="fa-solid fa-bars"></i>
                    </button>

                    <div class="menu" id="menu">
                        <a href="../productos/productos.php">Productos</a>
                        <a href="../promociones/promociones.php">Promociones</a>
                        <a href="../envios/envios.php">Envios</a>
                        <a href="../acerca_de/acerca_de.php">Acerca de</a>
                        <a href="../contactanos/contactanos.php">Contactanos</a>
                        <div class="user-menu-container">
                            <div class="user-icon">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div class="user-options" id="user-options">
                                <a href="../pedidos_usuario/pedidos.php">Pedidos</a>
                                <a href="../perfil/perfil_usuario.php">Perfil</a>
                                <a href="../php/cerrar_sesion.php">Cerrar Sesión</a>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
            `;

            var navegacionAdmin = `
            <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="https://necolas.github.io/normalize.css/8.0.1/normalize.css">
            <link rel="stylesheet" href="../css/style.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
            <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

            <header>
                <h1 class="titulo">CITEI</h1>
            </header>
            <div class="nav-bg">
                <nav class="navegacion-principal contenedor">
                    <a href="../login/login.html" aria-label="Ir a la página de inicio de sesión">
                        <img src="../img/logo.png" alt="Logo CITEI - Ir a la página de inicio de sesión">
                    </a>

                    <button class="hamburger" id="hamburger">
                        <i class="fa-solid fa-bars"></i>
                    </button>

                    <div class="menu" id="menu">
                        <a href="../productos/productos.php">Productos</a>
                        <a href="../promociones/promociones.php">Promociones</a>
                        <a href="../cliente/clientes.php">Clientes</a>
                        <a href="../pedidos_admin/pedidos.php">Pedidos</a>
                        <a href="../acerca_de/acerca_de.php">Acerca de</a>
                        <a href="../contactanos/contactanos.php">Contactanos</a>
                        <a href="../vehiculos/gestionar_vehiculos.php">Vehiculos</a>
                        <a href="../repartidores/gestionar_repartidores.php">Repartidores</a>
                        <a href="../rutas/rutas.php">Rutas</a>
                        <div class="user-menu-container">
                            <div class="user-icon">
                            <i class="fa-solid fa-user"></i>
                            </div>
                            <div class="user-options" id="user-options">
                            <a href="../formularios/ver_formularios.php">Ver formularios</a>
                            <a href="../php/cerrar_sesion.php">Cerrar Sesión</a>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
            `;

            var navegacion;
            var script = document.createElement('script');

            navegacion = isAdmin ? navegacionAdmin : navegacionUsuario;

            // Inserta el HTML dinámicamente
            document.body.insertAdjacentHTML('afterbegin', navegacion);

            // Ahora que el HTML está en el DOM, añadimos el comportamiento del menú
            const menu = document.getElementById('menu');
            const hamburger = document.getElementById('hamburger');

            // Añadir evento al botón de hamburguesa
            hamburger.addEventListener('click', function () {
                // Alternamos entre mostrar y ocultar el menú
                menu.classList.toggle('active');
            });

            // Comportamiento del menú de usuario
            const userIcon = document.querySelector('.user-icon');
            const userOptions = document.getElementById('user-options');

            userIcon.addEventListener('click', function () {
                userOptions.classList.toggle('active');
            });

            // Opcional: Cerrar el menú de usuario si se hace clic fuera
            document.addEventListener('click', function (event) {
                if (!userIcon.contains(event.target) && !userOptions.contains(event.target)) {
                    userOptions.classList.remove('active');
                }
            });
        });
});