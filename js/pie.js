document.addEventListener("DOMContentLoaded", function () {
    var pie = `
    <div class=" columnas pie">
        <div>
            <img src="../img/logo.png" alt="Logo CITEI">
        </div>

        <div>
            <p>Av Manuel J. Clouthier 173, Prados Vallarta, 45020 Zapopan, Jal.</p>
            <p>Teléfono: 33 3629 6652</p>
        </div>
        <div>
            <p>Nuestras redes sociales</p>
            <div class="iconos">
                <a href="https://www.facebook.com/Citei.GDL/?locale=es_LA"><i class="fab fa-facebook-f"></i></a>
                <a href="https://x.com/i/flow/login?redirect_after_login=%2Fcongresocitei" target="_blank"><i
                        class="fab fa-twitter"></i></a>
                <a href="https://www.instagram.com/citei.gdl/?hl=es"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </div>
    <footer class="pie">
        <p>&copy; 2024 CITEI. Todos los derechos reservados.</p>
    </footer>
    `;
    document.body.insertAdjacentHTML("afterend", pie);
});
