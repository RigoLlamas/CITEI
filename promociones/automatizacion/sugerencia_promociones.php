<?php
// Asegúrate de que $conexion está definido
if (!isset($conexion)) {
    die("Error: La conexión a la base de datos no está disponible.");
}

// Fecha actual
$fecha_actual = date('Y-m-d');

// Verificar si es el primer día del mes
if (date('j') == 1) {
    // Obtener productos con al menos 10 ventas mensuales
    $query_mas_vendidos = "
        SELECT Producto, COUNT(*) AS VentasMensuales
        FROM pedidos
        JOIN detalles ON pedidos.NumVenta = detalles.NumVenta
        WHERE MONTH(Fecha) = MONTH(CURDATE() - INTERVAL 1 MONTH)
        AND YEAR(Fecha) = YEAR(CURDATE())
        GROUP BY Producto
        HAVING VentasMensuales >= 10
    ";
    $resultado_mas_vendidos = $conexion->query($query_mas_vendidos);

    // Procesar productos más vendidos
    while ($producto = $resultado_mas_vendidos->fetch_assoc()) {
        $id_producto = $producto['Producto'];

        // Verificar si ya existe una oferta activa para este producto
        $query_verificar = "
            SELECT COUNT(*) AS Total
            FROM ofertas
            WHERE Producto = ? AND Estado = 'Activada'
        ";
        $stmt_verificar = $conexion->prepare($query_verificar);
        $stmt_verificar->bind_param('i', $id_producto);
        $stmt_verificar->execute();
        $resultado_verificar = $stmt_verificar->get_result();
        $fila_verificar = $resultado_verificar->fetch_assoc();
        $stmt_verificar->close();

        // Si no tiene una oferta activa, generar una nueva
        if ($fila_verificar['Total'] == 0) {
            $query_insertar_oferta = "
                INSERT INTO ofertas (Tipo, Valor, Producto, Descripcion, Estado, Despliegue, Expiracion, Condicion)
                VALUES ('Porcentual', 10.00, ?, 'Oferta automática para producto más vendido', 'En revisión', ?, ?)
            ";
            $stmt = $conexion->prepare($query_insertar_oferta);
            $fecha_despliegue = date('Y-m-d'); // Fecha actual
            $fecha_expiracion = date('Y-m-d', strtotime('+1 month')); // Expira en un mes
            $condicion = null; // Puedes definir una condición si aplica
            $stmt->bind_param('iss', $id_producto, $fecha_despliegue, $fecha_expiracion);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Repetir la misma lógica para productos menos vendidos
    $query_menos_vendidos = "
        SELECT Producto, COUNT(*) AS VentasMensuales
        FROM pedidos
        JOIN detalles ON pedidos.NumVenta = detalles.NumVenta
        WHERE MONTH(Fecha) = MONTH(CURDATE() - INTERVAL 1 MONTH)
        AND YEAR(Fecha) = YEAR(CURDATE())
        GROUP BY Producto
        HAVING VentasMensuales < 10
    ";
    $resultado_menos_vendidos = $conexion->query($query_menos_vendidos);

    while ($producto = $resultado_menos_vendidos->fetch_assoc()) {
        $id_producto = $producto['Producto'];

        // Verificar si ya existe una oferta activa para este producto
        $query_verificar = "
            SELECT COUNT(*) AS Total
            FROM ofertas
            WHERE Producto = ? AND Estado = 'Activada'
        ";
        $stmt_verificar = $conexion->prepare($query_verificar);
        $stmt_verificar->bind_param('i', $id_producto);
        $stmt_verificar->execute();
        $resultado_verificar = $stmt_verificar->get_result();
        $fila_verificar = $resultado_verificar->fetch_assoc();
        $stmt_verificar->close();

        // Si no tiene una oferta activa, generar una nueva
        if ($fila_verificar['Total'] == 0) {
            $query_insertar_oferta = "
                INSERT INTO ofertas (Tipo, Valor, Producto, Descripcion, Estado, Despliegue, Expiracion, Condicion)
                VALUES ('Porcentual', 5.00, ?, 'Oferta automática para producto menos vendido', 'En revisión', ?, ?)
            ";
            $stmt = $conexion->prepare($query_insertar_oferta);
            $fecha_despliegue = date('Y-m-d'); // Fecha actual
            $fecha_expiracion = date('Y-m-d', strtotime('+1 month')); // Expira en un mes
            $condicion = null; // Puedes definir una condición si aplica
            $stmt->bind_param('iss', $id_producto, $fecha_despliegue, $fecha_expiracion);
            $stmt->execute();
            $stmt->close();
        }
    }
}
?>
