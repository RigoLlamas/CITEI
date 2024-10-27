<?php
session_start();

if (isset($_SESSION['id_usuario'])) {
    $isAdmin = $_SESSION['id_usuario'] == '1';
} else {
    $isAdmin = false;
}
?>

<div id="modalProducto" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        
        <div class="modal-body">
            <!-- Columna de imágenes -->
            <div class="modal-imagenes-columna">
                <div id="modal-imagenes" class="modal-imagenes"></div> <!-- Contenedor dinámico de imágenes -->
            </div>
            
            <!-- Columna de texto -->
            <div class="modal-texto-columna">
                <h2 id="modal-nombre"></h2>
                <p id="modal-precio"></p>
                <p>Descripción:</p>
                <p id="modal-descripcion"></p>
                <p>Características:</p>
                <p id="modal-caracteristicas"></p>

                <!-- Aquí va el botón que cambia según el rol -->
                <div class="contenedor-boton">
                    <?php 
                    if ($isAdmin){
                        echo '<button class="boton-modal" onclick="modificarProducto()">Modificar Producto</button>';
                    } else{
                        echo '

                            <div class="boton-modal">
                                <div> 
                                    <label for="cantidad">Cantidad:</label>
                                    <input type="number" id="cantidad" name="cantidad" value="1" min="1" class="input-cantidad">
                                </div>
                            <button  onclick="agregarAlCarrito()">Agregar al Carrito</button>
                            </div>';

                            
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
