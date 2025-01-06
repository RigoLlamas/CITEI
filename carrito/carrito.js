document.addEventListener('DOMContentLoaded', function() {
    // Obtener todos los productos en el carrito
    const productos = document.querySelectorAll('.producto_carrito');
    const totalElement = document.querySelector('p.total'); // Elemento que muestra el total en el HTML
    const paypalButton = document.getElementById('paypal-button-container');
    const botonesEliminar = document.querySelectorAll('.eliminar-carrito');

    // Obtener el descuento aplicado desde PHP
    const descuentoAplicado = parseFloat(document.querySelector('p.descuento').textContent.replace('Descuento aplicado: $', '')) || 0;

    // Función para verificar si el carrito está vacío o el total es cero
    function verificarCarritoVacio() {
        const productosRestantes = document.querySelectorAll('.producto_carrito');
        const totalText = totalElement.textContent;
        const totalNumero = parseFloat(totalText.replace('Costo total: $', ''));
    
        if (productosRestantes.length === 0 || totalNumero <= 0) {
            // Si no hay productos o el total es cero o negativo, ocultar el botón de PayPal
            if (paypalButton) {
                paypalButton.style.display = 'none';
            }
        } else {
            // Si hay productos y el total es positivo, mostrar el botón de PayPal
            if (paypalButton) {
                paypalButton.style.display = 'block';
            }
        }
    }

    verificarCarritoVacio();

    // Verificar si el elemento total existe
    if (!totalElement) {
        console.error('El elemento que muestra el total no se encuentra en el DOM.');
        return;
    }

    // Función para actualizar el total en el cliente
    function actualizarTotal() {
        let totalCarrito = 0;
        document.querySelectorAll('.producto_carrito').forEach(function(producto) {
            const cantidad = parseInt(producto.querySelector('.cantidad span').textContent);
            const precio = parseFloat(producto.querySelector('.info-producto p:nth-of-type(2)').textContent.replace('Precio: $', ''));
            
            // Calcular el subtotal de cada producto
            const subtotal = cantidad * precio;
            totalCarrito += subtotal;
        });

        // Restar el descuento aplicado
        const totalFinal = totalCarrito - descuentoAplicado;

        // Actualizar el total en el HTML
        totalElement.textContent = 'Costo total: $' + totalFinal.toFixed(2);
        console.log('Costo total: $' + totalFinal.toFixed(2));
        
        // Verificar si el carrito está vacío o el total es cero
        verificarCarritoVacio();
    }

    productos.forEach(producto => {
        const productoId = producto.getAttribute('data-id');
        const cantidadDisplay = producto.querySelector('.cantidad span');
        const incrementarBtn = producto.querySelector('.incrementar');
        const decrementarBtn = producto.querySelector('.decrementar');

        // Funcionalidad para incrementar la cantidad
        incrementarBtn.addEventListener('click', function() {
            let cantidadActual = parseInt(cantidadDisplay.textContent);
            cantidadDisplay.textContent = cantidadActual + 1;
            
            // Actualizar el total inmediatamente en la interfaz
            actualizarTotal();

            // Enviar la nueva cantidad al servidor para actualizar la base de datos
            actualizarCantidadEnServidor(productoId, cantidadActual + 1);
        });

        // Funcionalidad para decrementar la cantidad
        decrementarBtn.addEventListener('click', function() {
            let cantidadActual = parseInt(cantidadDisplay.textContent);
            if (cantidadActual > 1) {
                cantidadDisplay.textContent = cantidadActual - 1;
                
                actualizarTotal();
                actualizarCantidadEnServidor(productoId, cantidadActual - 1);
            }
        });
    });

    // Función para enviar la actualización de la cantidad al servidor (sin esperar respuesta para actualizar el total en el cliente)
    function actualizarCantidadEnServidor(productoId, nuevaCantidad) {
        const datos = new FormData();
        datos.append('productoId', productoId);
        datos.append('cantidad', nuevaCantidad);

        fetch('actualizar_cantidad.php', {
            method: 'POST',
            body: datos
        })
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') {
                console.error('Error al actualizar la cantidad:', data.message);
                Swal.fire('Error', 'No se pudo actualizar la cantidad del producto.', 'error');
            }
        })
        .catch(error => {
            console.error('Error al actualizar la cantidad:', error);
            Swal.fire('Error', 'Ocurrió un problema al actualizar la cantidad del producto.', 'error');
        });
    }

    function obtenerProductosDelCarrito() {
        let productos = [];
        document.querySelectorAll('.producto_carrito').forEach(function(producto) {
            const productoId = producto.getAttribute('data-id');
            const cantidad = parseInt(producto.querySelector('.cantidad span').textContent);
            const precio = parseFloat(producto.querySelector('.info-producto p:nth-of-type(2)').textContent.replace('Precio: $', ''));
            const nombreProducto = producto.querySelector('.info-producto p:first-of-type').textContent;

            if (productoId && cantidad && precio) {
                productos.push({
                    productoId: productoId,
                    cantidad: cantidad,
                    precio: precio,
                    nombreProducto: nombreProducto
                });
            }
        });
        return productos;
    }

    botonesEliminar.forEach(boton => {
        boton.addEventListener('click', function() {
            const productoId = boton.getAttribute('data-id');

            // Enviar solicitud al servidor para eliminar el producto del carrito
            fetch('eliminar_producto_carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `productoId=${productoId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log('Producto eliminado correctamente:', data.message);
                    // Remover el producto del DOM
                    const productoElemento = boton.closest('.producto_carrito');
                    productoElemento.remove();
                    actualizarTotal();
                        location.reload();
                } else {
                    console.error('Error al eliminar el producto:', data.message);
                    Swal.fire('Error', 'No se pudo eliminar el producto del carrito.', 'error');
                }
            })
            .catch(error => {
                console.error('Error al eliminar el producto:', error);
                Swal.fire('Error', 'Ocurrió un problema al eliminar el producto del carrito.', 'error');
            });
        });
    });

    // Código botón de PayPal
    paypal.Buttons({
        createOrder: function(data, actions) {
            // Calcular el total del carrito
            let total = 0;
            let productos = obtenerProductosDelCarrito();

            productos.forEach(function(producto) {
                total += producto.cantidad * producto.precio;
            });

            // Restar el descuento aplicado
            total -= descuentoAplicado;
            if (total < 0) total = 0;

            // Actualizar el total en el HTML
            totalElement.textContent = 'Costo total: $' + total.toFixed(2);

            // Crear la orden con el total en pesos mexicanos
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: total.toFixed(2) // Usar el total calculado en MXN
                    }
                }]
            });
        },
        onApprove: function(data, actions) {
            // Captura el pago después de que el usuario apruebe la transacción
            return actions.order.capture().then(function(details) {

                let productos = obtenerProductosDelCarrito();

                // Solicitud al servidor para guardar los detalles del pago
                return fetch('guardar_pedido.php', {
                    method: 'POST',
                    headers: {
                        'content-type': 'application/json'
                    },
                    body: JSON.stringify({
                        orderID: data.orderID,
                        payerID: details.payer.payer_id,
                        estado: details.status,
                        monto: details.purchase_units[0].amount.value,
                        productos: productos
                    })
                }).then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(datos => {
                    if (datos.status === 'success') {
                        Swal.fire('Pago completado', 'Tu compra se ha realizado correctamente.', 'success').then(() => {
                            // Limpiar el carrito y actualizar el total
                            limpiarCarrito();
                            actualizarTotal();
                            verificarCarritoVacio();
                        });

                        // Crear la cadena con la información de los productos
                        const productosInfo = datos.productos.map((producto) => {
                            return `Producto: ${producto.nombreProducto}, Cantidad: ${producto.cantidad}, Precio: $${producto.precio}`;
                        }).join('\n'); // Unir todos los productos en un solo string

                        const datosPedido = {
                            productosInfo: productosInfo,   // Información de todos los productos en un solo campo
                            total: datos.monto,               // Total del pedido (cambiado a totalPago)
                            codigo: datos.codigo,           // Código del pedido
                            clave: datos.clave              // Clave del pedido
                        };

                        const datosUsuario = {
                            correo: datos.usuario.Correo,
                            nombre: datos.usuario.Nombres + ' ' + datos.usuario.Apellidos,
                            calle: datos.usuario.Calle,
                            municipio: datos.usuario.Municipio,
                            codigoPostal: datos.usuario.CP,
                            numInterior: datos.usuario.NumInterior,
                            numExterior: datos.usuario.NumExterior,
                            telefono: datos.usuario.Telefono
                        };

                        enviarCorreoElectronico(datosPedido, datosUsuario);
                        
                    } else {
                        console.error('Error al guardar el pedido:', datos.message);
                        Swal.fire('Error', 'No se pudo completar el pedido.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error en la solicitud al servidor:', error);
                    Swal.fire('Error', 'Ocurrió un problema al procesar tu pedido.', 'error');
                });
            });
        },
        onCancel: function (data) {
            // Manejar cuando el usuario cancela el pago
            Swal.fire('Pago cancelado', 'Tu pago ha sido cancelado.', 'info');
        },
        onError: function (err) {
            // Manejar errores en el pago
            console.error('Error en el pago:', err);
            Swal.fire('Error', 'Ocurrió un error con el pago.', 'error');
        }
    }).render('#paypal-button-container');

    function enviarCorreoElectronico(datosPedido, datosUsuario) {
        // Generar el mensaje del correo
        const mensaje = `
            Hola ${datosUsuario.nombre},
    
            Tu pedido ha sido registrado correctamente.
    
            Detalles del pedido:
            ${datosPedido.productosInfo}
    
            Total: $${datosPedido.total}
            Código del pedido: ${datosPedido.codigo}
            Clave del pedido: ${datosPedido.clave}
    
            Datos de envío:
            - Calle: ${datosUsuario.calle}
            - Municipio: ${datosUsuario.municipio}
            - Código Postal: ${datosUsuario.codigoPostal}
            - Número Interior: ${datosUsuario.numInterior}
            - Número Exterior: ${datosUsuario.numExterior}
            - Teléfono: ${datosUsuario.telefono}
    
            Gracias por tu compra.
        `;
    
        // Obtener configuraciones desde el backend
        fetch('../php/obtener_email_config.php')
            .then(response => response.json())
            .then(config => {
                const templateParams = {
                    to_email: datosUsuario.correo,      // Correo del usuario
                    bcc_email: "citeinotificaciones@gmail.com",
                    message: mensaje                    // Mensaje generado dinámicamente
                };
    
                // Llamar a EmailJS para enviar el correo con el mensaje personalizado
                return emailjs.send(config.service_id, config.template_message, templateParams, config.user_id);
            })
            .then(response => {
                console.log('Correo enviado con éxito', response.status, response.text);
                // Opcional: Mostrar una notificación al usuario
                Swal.fire('Correo enviado', 'Se ha enviado un correo de confirmación a tu dirección de correo.', 'success');
            })
            .catch(error => {
                console.error('Error al enviar el correo:', error);
                Swal.fire('Error', 'Ocurrió un problema al enviar el correo de confirmación.', 'error');
            });
    }

    function limpiarCarrito() {
        // Eliminar todos los productos del DOM
        document.querySelectorAll('.producto_carrito').forEach(producto => producto.remove());
        // Opcional: Enviar una solicitud al servidor para vaciar el carrito en la base de datos
        fetch('vaciar_carrito.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status !== 'success') {
                console.error('Error al vaciar el carrito:', data.message);
                Swal.fire('Error', 'No se pudo vaciar el carrito.', 'error');
            }
        })
        .catch(error => {
            console.error('Error al vaciar el carrito:', error);
            Swal.fire('Error', 'Ocurrió un problema al vaciar el carrito.', 'error');
        });
    }

    // Función para aplicar la oferta
    document.querySelectorAll('.aplicar-oferta').forEach(boton => {
        boton.addEventListener('click', function() {
            const ofertaId = this.getAttribute('data-id');
            
            // Enviar la oferta seleccionada al servidor
            const datos = new FormData();
            datos.append('ofertaId', ofertaId);

            fetch('aplicar_oferta.php', {
                method: 'POST',
                body: datos
            })
            .then(response => response.json())
            .then(json => {
                if (json.error) {
                    // Mostrar mensaje de error con SweetAlert
                    Swal.fire({
                        title: 'Error',
                        text: json.error,
                        icon: 'error',
                        confirmButtonText: 'Aceptar'
                    });
                } else {
                    // Mostrar mensaje de éxito con SweetAlert
                    Swal.fire({
                        title: 'Oferta aplicada',
                        text: json.success,
                        icon: 'success',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        // Actualizar el total en el cliente
                        actualizarTotal();
                        // Opcional: Actualizar visualmente la oferta aplicada
                        location.reload(); // Recargar la página para reflejar cambios
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Ocurrió un problema al aplicar la oferta.', 'error');
            });
        });
    });
});
