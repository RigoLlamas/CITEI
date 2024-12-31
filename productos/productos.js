// Obtener el input del buscador y el contenedor de productos
const buscador = document.getElementById('buscador');
let productos = document.querySelectorAll('.producto');

// Función para cargar el modal externamente
function cargarModal(callback) {
    fetch('modal/modal.php') // Ruta del archivo modal.html
        .then(response => response.text())
        .then(html => {
            // Insertamos el contenido del modal en el body
            document.body.insertAdjacentHTML('beforeend', html);

            // Asignamos el evento de cierre al botón de cerrar
            const closeModalBtn = document.querySelector(".close");
            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', cerrarModal);
            }
            // Llamamos al callback para asignar los datos del producto al modal
            callback();
        });
}

// Función para abrir el modal
function abrirModal(producto) {
    cargarModal(() => {
        const modal = document.getElementById('modalProducto');
        const modalNombre = document.getElementById('modal-nombre');
        const modalPrecio = document.getElementById('modal-precio');
        const modalDescripcion = document.getElementById('modal-descripcion');
        const modalCaracteristicas = document.getElementById('modal-caracteristicas');

        // Obtener los atributos de data-* del producto
        const productoId = producto.getAttribute('data-id');
        const nombre = producto.getAttribute('data-nombre');
        const precio = producto.getAttribute('data-precio');
        const descripcion = producto.getAttribute('data-descripcion');
        const caracteristicas = producto.getAttribute('data-caracteristicas');
        const imagenes = JSON.parse(producto.getAttribute('data-imgs')); // Parsear el JSON de las imágenes

        // Asignar el id del producto al modal para que esté disponible más tarde
        modal.setAttribute('data-id', productoId);

        // Asignar los datos al modal
        modalNombre.textContent = nombre;
        modalPrecio.textContent = '$' + precio;
        modalDescripcion.textContent = descripcion;
        modalCaracteristicas.textContent = caracteristicas;

        const modalImagenes = document.getElementById('modal-imagenes');
        modalImagenes.innerHTML = ''; // Limpiar imágenes anteriores

        imagenes.forEach((img) => {
            const imgElement = document.createElement('img');
            imgElement.src = img;
            modalImagenes.appendChild(imgElement);
        });

        // Mostrar el modal
        modal.style.display = "flex";


    });
}

// Cerrar el modal
function cerrarModal() {
    const modal = document.getElementById('modalProducto');
    const modalContent = modal.querySelector('.modal-content');

    // Añadir la clase 'cerrar' para reproducir la animación de cierre
    modalContent.classList.add('cerrar');
    modal.classList.add('cerrar');

    // Esperar a que la animación termine antes de eliminar el modal
    modalContent.addEventListener('animationend', function () {
        modal.remove(); // Eliminar el modal del DOM después de la animación
    }, { once: true }); // Escuchar el evento solo una vez
}

// Asignar eventos de clic a los productos
function asignarEventosAProductos() {
    productos = document.querySelectorAll('.producto');
    productos.forEach(producto => {
        producto.addEventListener('click', function () {
            abrirModal(producto);
        });
    });
}

// Filtro de productos por nombre
buscador.addEventListener('input', function () {
    const filtro = buscador.value.toLowerCase();

    // Filtrar los productos según el input
    productos.forEach(producto => {
        const nombreProducto = producto.getAttribute('data-nombre');

        if (nombreProducto.includes(filtro)) {
            producto.style.display = 'block'; // Mostrar el producto si coincide
        } else {
            producto.style.display = 'none'; // Ocultar el producto si no coincide
        }
    });

    // Volver a asignar eventos de clic después del filtrado
    asignarEventosAProductos();
});

// Asignar eventos de clic cuando se carga la página por primera vez
asignarEventosAProductos();


// Redirigir al carrito o agregar producto dependiendo de si el botón está presente
document.addEventListener('DOMContentLoaded', function () {
    const botonCarrito = document.getElementById('boton-carrito');

    // Verificar si el usuario es administrador mediante una solicitud AJAX
    fetch('../php/obtener_usuario.php')
        .then(response => response.json())
        .then(isAdmin => {
            if (isAdmin) {
                // Si es administrador, cambiar el botón de "Ir al Carrito" a "Agregar Producto"
                botonCarrito.textContent = 'Agregar Producto';
                botonCarrito.setAttribute('id', 'boton-agregar-producto');
                // Asignar la funcionalidad para agregar productos
                botonCarrito.addEventListener('click', function () {
                    window.location.href = 'agregar_producto.php'; // Redirige a la página para agregar productos
                });
            } else {
                // Funcionalidad del botón "Ir al Carrito" si no es administrador
                botonCarrito.addEventListener('click', function () {
                    window.location.href = '../carrito/carrito.php'; // Redirige a la página del carrito
                });
            }
        })
        .catch(error => console.error('Error al verificar si es admin:', error));
});

// Función para modificar el producto
function modificarProducto() {
    const modal = document.getElementById('modalProducto');
    var productoId = modal.getAttribute('data-id'); // Obtener el ID del producto desde el modal
    window.location.href = 'gestionar_productos.php?id=' + productoId;
}

// Función para eliminar el producto
function eliminarProducto() {
    const modal = document.getElementById('modalProducto');
    const productoId = modal.getAttribute('data-id'); // Obtener el ID del producto desde el modal

    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            // Enviar la solicitud para eliminar el producto
            fetch('eliminar_producto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${productoId}`
            })
                .then(response => response.text())
                .then(data => {
                    Swal.fire({
                        title: 'Producto eliminado',
                        text: data,
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        // Recargar la página para reflejar los cambios
                        location.reload();
                    });
                })
                .catch(error => {
                    console.error('Error al eliminar el producto:', error);
                    Swal.fire({
                        title: 'Error',
                        text: 'Hubo un problema al eliminar el producto.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                });
        }
    });
}


// Función para agregar al carrito
function agregarAlCarrito() {
    const modal = document.getElementById('modalProducto');
    const cantidad = document.getElementById('cantidad').value;
    const productoId = modal.getAttribute('data-id'); // Obtener el ID del producto desde el modal

    // Crear el cuerpo de los datos que enviaremos
    const datos = new FormData();
    datos.append('productoId', productoId);
    datos.append('cantidad', cantidad);

    // Enviar los datos a PHP mediante fetch (AJAX)
    fetch('../productos/modal/agregar_al_carrito.php', {
        method: 'POST',
        body: datos
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json(); // Asumimos que el servidor responde con JSON
        })
        .then(data => {
            if (data.error) {
                Swal.fire({
                    title: 'Error',
                    text: data.error, // Mostrar el mensaje de error del servidor
                    icon: 'error',
                    confirmButtonText: 'Aceptar'
                });
            } else {
                Swal.fire({
                    title: 'Producto agregado',
                    text: data.success || 'El producto se agregó al carrito.', // Mostrar el mensaje de éxito
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error',
                text: 'Hubo un problema al agregar el producto al carrito.',
                icon: 'error',
                confirmButtonText: 'Aceptar'
            });
        });
}
