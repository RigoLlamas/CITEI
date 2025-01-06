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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Guardar datos en localStorage
            localStorage.setItem("isAdmin", data.isAdmin); // True si es admin

            Swal.fire({
                title: "¡Bienvenido!",
                text: "Inicio de sesión exitoso. Redirigiendo...",
                icon: "success",
                timer: 1000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = "../productos/productos.php"; // Redirigir a la página de productos
            });
        } else {
            Swal.fire({
                title: "Error",
                text: data.message,
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
