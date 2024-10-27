<?php
// Incluir archivo de conexión
include '../php/conexion.php';

// Verificar productos con al menos 10 ventas en el mes actual
$sql_ventas = "
    SELECT Producto, COUNT(*) AS VentasMensuales 
    FROM pedidos 
    JOIN detalles ON pedidos.NumVenta = detalles.NumVenta 
    WHERE MONTH(Fecha) = MONTH(CURDATE()) AND YEAR(Fecha) = YEAR(CURDATE())
    GROUP BY Producto 
    HAVING VentasMensuales >= 10";

$resultado_ventas = mysqli_query($conexion, $sql_ventas);

if (mysqli_num_rows($resultado_ventas) > 0) {
    while ($producto = mysqli_fetch_assoc($resultado_ventas)) {
        $producto_id = $producto['Producto'];
        
        // Automatización de producto, despliegue y expiración
        $despliegue = date('Y-m-d'); // Fecha actual como despliegue
        $expiracion = date('Y-m-d', strtotime('+30 days')); // Expiración en 30 días

        // Insertar oferta con campos vacíos para tipo y valor, para que el administrador los complete
        $sql_oferta = "INSERT INTO Ofertas (Tipo, Valor, Despliegue, Expiracion, Producto, Estado) 
                       VALUES (NULL, NULL, ?, ?, ?, 'En revisión')";
        $stmt_oferta = mysqli_prepare($conexion, $sql_oferta);
        mysqli_stmt_bind_param($stmt_oferta, 'ssi', $despliegue, $expiracion, $producto_id);
        mysqli_stmt_execute($stmt_oferta);
        mysqli_stmt_close($stmt_oferta);

        echo "Oferta creada para el producto ID: $producto_id. Pendiente de revisión por el administrador.\n";
    }
} else {
    echo "No hay productos con suficientes ventas para generar ofertas.\n";
}

// Cerrar la conexión a la base de datos
mysqli_close($conexion);
?>
