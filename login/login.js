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
            Swal.fire({
                title: "¡Bienvenido!",
                text: "Inicio de sesión exitoso. Redirigiendo...",
                icon: "success",
                timer: 1000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = "../productos/productos.php";
            });
        } else {
            Swal.fire({
                title: "Error",
                text: "Datos incorrectos. Inténtalo de nuevo.",
                icon: "error",
                confirmButtonText: "Aceptar"
            }).then(() => {
                correoInput.value = "";
                claveInput.value = "";
            });
        }
    })
    .catch(error => {
        console.error("Error:", error);
        Swal.fire({
            title: "Error",
            text: "Ocurrió un error al procesar la solicitud. Inténtalo más tarde.",
            icon: "error",
            confirmButtonText: "Aceptar"
        });
    });
});
