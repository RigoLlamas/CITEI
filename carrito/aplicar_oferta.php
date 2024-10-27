<?php
include '../php/conexion.php'; 
session_start();

if (isset($_POST['ofertaId'])) {
    $ofertaId = intval($_POST['ofertaId']);
    $idUsuario = $_SESSION['id_usuario'];

    // Consulta para obtener los detalles de la oferta
    $consultaOferta = "
        SELECT Tipo, Valor, Condicion, Producto, Estado, Expiracion 
        FROM ofertas 
        WHERE Oferta = $ofertaId AND Estado = 'Activada' AND CURDATE() <= Expiracion
    ";
    $resultado = $conexion->query($consultaOferta);

    if ($resultado->num_rows > 0) {
        $oferta = $resultado->fetch_assoc();
        $tipo = $oferta['Tipo'];
        $valor = $oferta['Valor'];
        $condicion = $oferta['Condicion'];
        $productoOferta = $oferta['Producto'];

        // Consulta del total del carrito
        $consultaCarrito = "
            SELECT SUM(producto.Precio * carrito.Cantidad) AS TotalCarrito
            FROM carrito
            JOIN producto ON carrito.Producto = producto.PK_Producto
            WHERE carrito.Usuario = $idUsuario
        ";
        $resultadoCarrito = $conexion->query($consultaCarrito);
        $totalCarrito = $resultadoCarrito->fetch_assoc()['TotalCarrito'];

        // Verificación de la condición (aquí puedes modificarla según tus reglas)
        if ($condicion && $totalCarrito < $condicion) {
            echo "No puedes aplicar esta oferta. El total de la compra debe ser mayor a $condicion.";
        } else {
            // Aplicar el descuento dependiendo del tipo de oferta
            if ($tipo === 'Porcentual') {
                $totalConDescuento = $totalCarrito - ($totalCarrito * ($valor / 100));
                echo "Oferta aplicada. El total con el descuento porcentual es $" . number_format($totalConDescuento, 2);
            } elseif ($tipo === 'Canjeable') {
                $totalConDescuento = $totalCarrito - $valor;
                echo "Oferta aplicada. El total con el descuento canjeable es $" . number_format($totalConDescuento, 2);
            }
        }
    } else {
        echo "La oferta no es válida o ha expirado.";
    }
} else {
    echo "No se recibió ninguna oferta.";
}
?>
