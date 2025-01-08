document.addEventListener("DOMContentLoaded", function () {

    // Verificar si el usuario es administrador
    const isAdmin = localStorage.getItem("isAdmin") === "true";

    // Enlaces comunes para todos los usuarios
    const enlacesComunes = `
        <a href="../productos/productos.php">Productos</a>
        <a href="../promociones/promociones.php">Promociones</a>
        <a href="../acerca_de/acerca_de.php">Acerca de</a>
        <a href="../contactanos/contactanos.php">Contáctanos</a>
    `;

    // Enlaces exclusivos para administradores
    const enlacesAdmin = `
        <a href="../cliente/clientes.php">Clientes</a>
        <a href="../pedidos_admin/pedidos.php">Pedidos</a>
        <a href="../vehiculos/gestionar_vehiculos.php">Vehículos</a>
        <a href="../repartidores/gestionar_repartidores.php">Repartidores</a>
        <a href="../rutas/rutas.php">Rutas</a>
    `;

    // Opciones de usuario
    const opcionesUsuario = `
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
    `;

    // Generar la navegación
    const navegacion = `
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://necolas.github.io/normalize.css/8.0.1/normalize.css">
        <link rel="stylesheet" href="../css/style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
        <link rel="icon" href="../img/logo.ico">
        <header>
            <h1 class="titulo">CITEI</h1>
        </header>
        <div class="nav-bg">
            <nav class="navegacion-principal contenedor">
                <div style="display: flex; align-items: center;">
                    <a href="../login/login.html" aria-label="Ir a la página de inicio de sesión" style="text-decoration: none; color: inherit; display: flex; align-items: center;">
                        <img src="../img/logo.png" alt="Logo CITEI - Ir a la página de inicio de sesión" style="margin-right: 1rem; ">
                        <p style="margin: 0; font-size: 16px;">Iniciar sesión</p>
                    </a>
                </div>
                <button class="hamburger" id="hamburger">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="menu" id="menu">
                    ${enlacesComunes}
                    ${isAdmin ? enlacesAdmin : `<a href="../envios/envios.php">Envíos</a>`}
                    ${isAdmin ? `<a href="../formularios/ver_formularios.php">Ver formularios</a>` : ""}
                    ${opcionesUsuario}
                </div>
            </nav>
        </div>
    `;

    // Insertar navegación en el DOM
    document.body.insertAdjacentHTML("afterbegin", navegacion);

    // Alternar menú hamburguesa
    const menu = document.getElementById("menu");
    const hamburger = document.getElementById("hamburger");
    hamburger.addEventListener("click", function () {
        menu.classList.toggle("active");
    });

    // Alternar menú de usuario
    const userIcon = document.querySelector(".user-icon");
    const userOptions = document.getElementById("user-options");
    userIcon.addEventListener("click", function () {
        userOptions.classList.toggle("active");
    });

    // Cerrar el menú de usuario si se hace clic fuera
    document.addEventListener("click", function (event) {
        if (!userIcon.contains(event.target) && !userOptions.contains(event.target)) {
            userOptions.classList.remove("active");
        }
    });

    // Limpieza de localStorage al cerrar sesión
    document.querySelector(".user-options a[href='../php/cerrar_sesion.php']").addEventListener("click", function () {
        localStorage.clear();
    });
});
