<?php
include '../php/conexion.php'; 
session_start();

// Limpia cualquier salida previa
ob_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['error' => 'Sesión no válida.']);
    exit();
}

if (isset($_POST['ofertaId'])) {
    $ofertaId = intval($_POST['ofertaId']);
    $idUsuario = $_SESSION['id_usuario'];

    // Verificar si la oferta ya fue utilizada o está solicitada
    $consultaVerificar = "
        SELECT EstadoUso 
        FROM asignacion_ofertas 
        WHERE Oferta = ? AND Usuario = ? AND (EstadoUso = 'Utilizada' OR EstadoUso = 'Solicitada')
    ";
    $stmtVerificar = $conexion->prepare($consultaVerificar);
    if (!$stmtVerificar) {
        echo json_encode(['error' => 'Error en la preparación de la verificación de la oferta.']);
        exit();
    }
    $stmtVerificar->bind_param('ii', $ofertaId, $idUsuario);
    $stmtVerificar->execute();
    $resultadoVerificar = $stmtVerificar->get_result();

    if ($resultadoVerificar->num_rows > 0) {
        echo json_encode(['error' => 'Ya has utilizado o solicitado esta oferta.']);
        exit();
    }

    // Obtener los detalles de la oferta
    $consultaOferta = "
        SELECT o.Tipo, o.Valor, o.Condicion, o.Producto, o.Estado, c.LimiteTiempo AS Expiracion
        FROM ofertas o
        LEFT JOIN condiciones c ON o.Condicion = c.Condicion
        WHERE o.Oferta = ? AND o.Estado = 'Activada' AND (c.LimiteTiempo IS NULL OR CURDATE() <= c.LimiteTiempo)
    ";
    $stmtOferta = $conexion->prepare($consultaOferta);
    if (!$stmtOferta) {
        echo json_encode(['error' => 'Error en la preparación de la consulta de la oferta.']);
        exit();
    }
    $stmtOferta->bind_param('i', $ofertaId);
    $stmtOferta->execute();
    $resultadoOferta = $stmtOferta->get_result();

    if ($resultadoOferta && $resultadoOferta->num_rows > 0) {
        $oferta = $resultadoOferta->fetch_assoc();
        $tipo = $oferta['Tipo']; // 'Porcentual' o 'Canjeable'
        $valor = floatval($oferta['Valor']); // Asegurarse de que sea un número
        $productoOferta = intval($oferta['Producto']); // 0 para global
        $totalConDescuento = 0;
        $descuentoAplicado = 0;

        if ($productoOferta == 0) {
            // Oferta global
            $consultaCarrito = "
                SELECT SUM(producto.Precio * carrito.Cantidad) AS TotalCarrito
                FROM carrito
                JOIN producto ON carrito.Producto = producto.PK_Producto
                WHERE carrito.Usuario = ?
            ";
            $stmtCarrito = $conexion->prepare($consultaCarrito);
            if (!$stmtCarrito) {
                echo json_encode(['error' => 'Error en la preparación de la consulta del carrito.']);
                exit();
            }
            $stmtCarrito->bind_param('i', $idUsuario);
            $stmtCarrito->execute();
            $resultadoCarrito = $stmtCarrito->get_result();
            $resultadoCarritoData = $resultadoCarrito->fetch_assoc();
            $totalCarrito = isset($resultadoCarritoData['TotalCarrito']) ? floatval($resultadoCarritoData['TotalCarrito']) : 0;

            if ($totalCarrito == 0) {
                echo json_encode(['error' => 'El carrito está vacío.']);
                exit();
            }

            if ($tipo === 'Porcentual') {
                $descuentoAplicado = ($totalCarrito * ($valor / 100));
            } elseif ($tipo === 'Canjeable') {
                $descuentoAplicado = $valor;
            } else {
                echo json_encode(['error' => 'Tipo de oferta desconocido.']);
                exit();
            }

            $totalConDescuento = $totalCarrito - $descuentoAplicado;
        } else {
            // Oferta específica
            $consultaProducto = "
                SELECT producto.Precio, carrito.Cantidad
                FROM carrito
                JOIN producto ON carrito.Producto = producto.PK_Producto
                WHERE carrito.Usuario = ? AND carrito.Producto = ?
            ";
            $stmtProducto = $conexion->prepare($consultaProducto);
            if (!$stmtProducto) {
                echo json_encode(['error' => 'Error en la preparación de la consulta del producto en el carrito.']);
                exit();
            }
            $stmtProducto->bind_param('ii', $idUsuario, $productoOferta);
            $stmtProducto->execute();
            $resultadoProducto = $stmtProducto->get_result();

            if ($resultadoProducto->num_rows > 0) {
                $productoCarrito = $resultadoProducto->fetch_assoc();
                $subtotalProducto = floatval($productoCarrito['Precio']) * intval($productoCarrito['Cantidad']);

                if ($tipo === 'Porcentual') {
                    $descuentoAplicado = ($subtotalProducto * ($valor / 100));
                } elseif ($tipo === 'Canjeable') {
                    $descuentoAplicado = $valor;
                } else {
                    echo json_encode(['error' => 'Tipo de oferta desconocido.']);
                    exit();
                }

                $totalConDescuento = $subtotalProducto - $descuentoAplicado;
            } else {
                echo json_encode(['error' => 'El producto de la oferta no está en el carrito.']);
                exit();
            }
        }

        // Asegurar que el total con descuento no sea negativo
        if ($totalConDescuento < 0) {
            $descuentoAplicado = $totalConDescuento + $descuentoAplicado; // Ajustar el descuento para que el total sea 0
            $totalConDescuento = 0;
        }

        // Actualizar estado de la oferta
        $actualizarEstado = "
            UPDATE asignacion_ofertas 
            SET EstadoUso = 'Solicitada', FechaUso = CURDATE() 
            WHERE Oferta = ? AND Usuario = ?
        ";
        $stmtActualizar = $conexion->prepare($actualizarEstado);
        if (!$stmtActualizar) {
            echo json_encode(['error' => 'Error en la preparación de la actualización del estado de la oferta.']);
            exit();
        }
        $stmtActualizar->bind_param('ii', $ofertaId, $idUsuario);
        $stmtActualizar->execute();

        // Calcular y actualizar el descuento en la sesión
        // Si ya hay un descuento aplicado, sumar el nuevo
        if (!isset($_SESSION['descuento'])) {
            $_SESSION['descuento'] = 0;
        }

        $_SESSION['descuento'] += $descuentoAplicado;

        // Obtener el nuevo total del carrito después del descuento
        if ($productoOferta == 0) {
            // Oferta global
            $nuevoTotalCarrito = $totalConDescuento;
        } else {
            // Oferta específica
            // Calcular el total completo del carrito y restar el descuento aplicado
            $consultaTotalCarrito = "
                SELECT SUM(producto.Precio * carrito.Cantidad) AS TotalCarrito
                FROM carrito
                JOIN producto ON carrito.Producto = producto.PK_Producto
                WHERE carrito.Usuario = ?
            ";
            $stmtTotalCarrito = $conexion->prepare($consultaTotalCarrito);
            if (!$stmtTotalCarrito) {
                echo json_encode(['error' => 'Error en la preparación de la consulta del total del carrito.']);
                exit();
            }
            $stmtTotalCarrito->bind_param('i', $idUsuario);
            $stmtTotalCarrito->execute();
            $resultadoTotalCarrito = $stmtTotalCarrito->get_result();
            $dataTotalCarrito = $resultadoTotalCarrito->fetch_assoc();
            $totalCarritoCompleto = isset($dataTotalCarrito['TotalCarrito']) ? floatval($dataTotalCarrito['TotalCarrito']) : 0;

            $nuevoTotalCarrito = $totalCarritoCompleto - $_SESSION['descuento'];

            if ($nuevoTotalCarrito < 0) {
                $nuevoTotalCarrito = 0;
            }
        }

        echo json_encode([
            'success' => 'Oferta aplicada correctamente.',
            'totalConDescuento' => number_format($nuevoTotalCarrito, 2),
            'descuento' => number_format($_SESSION['descuento'], 2)
        ]);
    } else {
        echo json_encode(['error' => 'La oferta no es válida o ha expirado.']);
    }
} else {
    echo json_encode(['error' => 'No se recibió ninguna oferta.']);
}
?>
