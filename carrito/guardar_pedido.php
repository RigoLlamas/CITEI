<?php
    header('Content-Type: application/json');
    error_reporting(E_ALL); // Mostrar todos los errores (solo en desarrollo)
    ini_set('display_errors', 1);

    include '../php/conexion.php';
    session_start();

    try{

    
    //Obtener los detalles del pago y pedido desde el cliente
    $datosPedido = json_decode(file_get_contents('php://input'), true);

    if (!$datosPedido) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se recibieron datos del pedido.'
        ]);
        exit();
    }

    //Extraer los datos importantes
    $orderID = $datosPedido['orderID'];
    $payerID = $datosPedido['payerID'];
    $estado = $datosPedido['estado'];
    $monto = $datosPedido['monto'];
    $productos = $datosPedido['productos'];
    $usuarioId = $_SESSION['id_usuario'];

    //Verificar si hay productos
    if (!is_array($productos) || empty($productos)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se recibieron productos válidos.'
        ]);
        exit();
    }

    //Insertar el pedido en la tabla `pedidos`
    $queryPedido = "INSERT INTO pedidos (Fecha, Estado, Codigo, Clave, FK_Usuario) VALUES (NOW(), ?, ?, ?, ?)";
    $stmtPedido = $conexion->prepare($queryPedido);
    $estadoPedido = 'Pagado';
    $codigo = str_pad(random_int(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
    $clave = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    
    if (!$stmtPedido->bind_param('sssi', $estadoPedido, $codigo, $clave, $usuarioId)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error en la preparación del pedido: ' . $stmtPedido->error
        ]);
        exit();
    }

    if (!$stmtPedido->execute()) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error al insertar el pedido: ' . $stmtPedido->error
        ]);
        exit();
    }

    $pedidoId = $stmtPedido->insert_id; // Obtener el ID del pedido

    //Insertar los productos en la tabla `detalles`
    $queryDetalle = "INSERT INTO detalles (Cantidad, Precio, NumVenta, Producto) VALUES (?, ?, ?, ?)";
    $stmtDetalle = $conexion->prepare($queryDetalle);

    foreach ($productos as $producto) {
        $cantidad = $producto['cantidad'];
        $precio = $producto['precio'];
        $productoId = $producto['productoId'];

        if (!$stmtDetalle->bind_param('idii', $cantidad, $precio, $pedidoId, $productoId)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al preparar el detalle del producto: ' . $stmtDetalle->error
            ]);
            exit();
        }

        if (!$stmtDetalle->execute()) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al insertar detalle del producto: ' . $stmtDetalle->error
            ]);
            exit();
        }
    }
    
    // Obtener los datos del usuario
    $queryUsuario = "
        SELECT usuarios.Nombres, usuarios.Apellidos, usuarios.Correo, usuarios.Calle, usuarios.CP, usuarios.NumInterior, usuarios.NumExterior, usuarios.Telefono, municipio.Municipio 
        FROM usuarios
        JOIN municipio ON usuarios.FK_Municipio = municipio.PK_Municipio
        WHERE usuarios.PK_Usuario = ?
    ";
    $stmtUsuario = $conexion->prepare($queryUsuario);
    $stmtUsuario->bind_param('i', $usuarioId);
    $stmtUsuario->execute();
    $resultUsuario = $stmtUsuario->get_result();
    $usuario = $resultUsuario->fetch_assoc(); // Obtener los datos del usuario en un array

    //Vaciar el carrito del usuario
    $queryVaciarCarrito = "DELETE FROM carrito WHERE Usuario = ?";
    $stmtVaciar = $conexion->prepare($queryVaciarCarrito);
    $stmtVaciar->bind_param('i', $usuarioId);
    $stmtVaciar->execute();
    
    $stmtUsuario->close();
    $stmtDetalle->close();
    $stmtVaciar->close();
    $stmtPedido->close();

    // Responder con éxito al cliente
    echo json_encode([
        'status' => 'success',
        'message' => 'Pedido guardado correctamente y carrito vaciado.',
        'codigo' => $codigo, 
        'clave' => $clave, 
        'monto' => $monto,
        'productos' => $productos,
        'usuario' => $usuario 
    ]);
}catch (Exception $e) {
    // Enviar una respuesta de error al cliente
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>

