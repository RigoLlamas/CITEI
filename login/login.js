document.getElementById("loginForm").addEventListener("submit", function (event) {
    event.preventDefault(); 

    const correo = document.getElementById("correo").value;
    const clave = document.getElementById("clave").value;

    const correoInput = document.getElementById("correo");
    const claveInput = document.getElementById("clave");

    const formData = new FormData();
    formData.append("correo", correo);
    formData.append("clave", clave);

    // Realizar la solicitud AJAX
    fetch("procesar_login.php", {
        method: "POST",
        body: formData
    })
        .then(response => response.text())
        .then(data => {
            return JSON.parse(data);
        })
        .then(data => {
            if (data.success) {
                window.location.href = "../productos/productos.php";
            } else {
                errorMessage.style.display = "block";
                correoInput.value = ""
                correoInput.setAttribute('placeholder', 'Datos incorrectos')
                claveInput.value = ""
            }
        })
        .catch(error => {
            console.error("Error:", error);
        });

});