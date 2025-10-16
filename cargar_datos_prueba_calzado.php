<?php
// ========================================
// CARGAR DATOS DE PRUEBA - TIENDA DE CALZADO
// ========================================
require_once 'app/helpers/Database.php';
$db = new Database();
$conn = $db->conn;

$msg = '';
try {
    // Productos de ejemplo por categoría y marca
    $productos = [
        // Caballero
        ['NK-CAB-001', 'Nike Air Max Caballero', 'Zapatillas deportivas para hombre', 180000, 20, 5, 1, 1001],
        ['AD-CAB-001', 'Adidas Superstar Caballero', 'Zapatillas clásicas para hombre', 150000, 15, 5, 2, 1002],
        ['PM-CAB-001', 'Puma Smash Caballero', 'Zapatillas casuales para hombre', 120000, 10, 3, 3, 1003],
        ['TB-CAB-001', 'Timberland Classic Caballero', 'Botas impermeables para hombre', 250000, 8, 2, 7, 1004],
        ['CV-CAB-001', 'Converse All Star Caballero', 'Zapatillas de lona para hombre', 95000, 12, 3, 5, 1005],
        ['CL-CAB-001', 'Clarks Desert Boot Caballero', 'Botas de gamuza para hombre', 210000, 6, 2, 8, 1006],
        // Dama
        ['NK-DAM-001', 'Nike Revolution Dama', 'Zapatillas deportivas para mujer', 170000, 18, 5, 9, 1001],
        ['AD-DAM-001', 'Adidas Stan Smith Dama', 'Zapatillas clásicas para mujer', 140000, 12, 4, 10, 1002],
        ['NW-DAM-001', 'Nine West Tacón Dama', 'Zapatos de tacón alto elegantes', 200000, 6, 2, 15, 1007],
        ['SM-DAM-001', 'Steve Madden Flats Dama', 'Zapatos planos para mujer', 130000, 10, 3, 16, 1008],
        ['CV-DAM-001', 'Converse All Star Dama', 'Zapatillas de lona para mujer', 95000, 14, 4, 13, 1009],
        ['VW-DAM-001', 'Vans Old Skool Dama', 'Zapatillas urbanas para mujer', 110000, 8, 2, 14, 1010],
        // Infantil
        ['NK-INF-001', 'Nike Kids Infantil', 'Zapatillas deportivas para niños', 90000, 25, 6, 17, 1001],
        ['AD-INF-001', 'Adidas Kids Infantil', 'Zapatillas casuales para niños', 85000, 20, 5, 18, 1002],
        ['CR-INF-001', 'Crocs Classic Infantil', 'Sandalias cómodas para niños', 60000, 30, 8, 23, 1011],
        ['DS-INF-001', 'Disney Princess Infantil', 'Zapatos temáticos para niñas', 95000, 15, 3, 24, 1012],
        ['SC-INF-001', 'Sketchers Go Run Infantil', 'Zapatillas deportivas para niños', 80000, 18, 4, 22, 1013],
        ['VN-INF-001', 'Vans Kids Infantil', 'Zapatillas urbanas para niños', 85000, 16, 3, 21, 1014],
    ];
    foreach ($productos as $p) {
        $stmt = $conn->prepare("INSERT INTO Productos (codigo, nombre, descripcion, precio_unitario, stock_actual, stock_minimo, id_subcg, id_nit) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssiiiii', $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7]);
        $stmt->execute();
        $stmt->close();
    }

    // Ventas, devoluciones y cambios de ejemplo
    $ventas = [
        // Caballero
        ['VNT-CAB-001', 'Venta Nike Air Max Caballero Talla 42', 1, 180000, 180000, '2025-10-12 10:00:00', 1, 'Venta en Tienda', 0],
        ['VNT-CAB-002', 'Venta Adidas Superstar Caballero Talla 41', 2, 150000, 300000, '2025-10-12 11:00:00', 2, 'Venta en Línea', 0],
        ['DEV-CAB-001', 'Devolución Puma Smash Caballero por defecto', 1, 120000, 120000, '2025-10-12 12:00:00', 3, 'Producto Defectuoso', 1],
        ['CAM-CAB-001', 'Cambio talla Timberland Classic Caballero', 1, 250000, 250000, '2025-10-12 13:00:00', 4, 'Cambio de Talla', 1],
        // Dama
        ['VNT-DAM-001', 'Venta Nike Revolution Dama Talla 38', 1, 170000, 170000, '2025-10-12 14:00:00', 7, 'Venta en Tienda', 0],
        ['VNT-DAM-002', 'Venta Nine West Tacón Dama Talla 36', 1, 200000, 200000, '2025-10-12 15:00:00', 9, 'Venta en Línea', 0],
        ['DEV-DAM-001', 'Devolución Converse All Star Dama por defecto', 1, 95000, 95000, '2025-10-12 16:00:00', 11, 'Producto Defectuoso', 1],
        ['CAM-DAM-001', 'Cambio talla Vans Old Skool Dama', 1, 110000, 110000, '2025-10-12 17:00:00', 12, 'Cambio de Talla', 1],
        // Infantil
        ['VNT-INF-001', 'Venta Nike Kids Infantil Talla 30', 1, 90000, 90000, '2025-10-12 18:00:00', 13, 'Venta en Tienda', 0],
        ['VNT-INF-002', 'Venta Crocs Classic Infantil Talla 28', 2, 60000, 120000, '2025-10-12 19:00:00', 15, 'Venta en Línea', 0],
        ['DEV-INF-001', 'Devolución Disney Princess Infantil por defecto', 1, 95000, 95000, '2025-10-12 20:00:00', 16, 'Producto Defectuoso', 1],
        ['CAM-INF-001', 'Cambio talla Sketchers Go Run Infantil', 1, 80000, 80000, '2025-10-12 21:00:00', 17, 'Cambio de Talla', 1],
    ];
    foreach ($ventas as $v) {
        $stmt = $conn->prepare("INSERT INTO Salidas (codigo_salida, descripcion, cantidad, precio_unitario, precio_total, fecha_salida, id_prod, motivo, procesada_devolucion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssiiisiis', $v[0], $v[1], $v[2], $v[3], $v[4], $v[5], $v[6], $v[7], $v[8]);
        $stmt->execute();
        $stmt->close();
    }

    $msg = "<div class='alert alert-success'><strong>¡Datos de prueba cargados correctamente!</strong><br>Productos y ventas de ejemplo listos para pruebas.</div>";
} catch (Exception $e) {
    $msg = "<div class='alert alert-danger'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Cargar Datos de Prueba - Inventixor</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-5'>
    <div class='row justify-content-center'>
        <div class='col-md-8'>
            <div class='card shadow'>
                <div class='card-header bg-success text-white'>
                    <h3 class='mb-0'><i class='fas fa-database me-2'></i>Cargar Datos de Prueba</h3>
                </div>
                <div class='card-body'>
                    <?php echo $msg; ?>
                    <div class='mt-4 text-center'>
                        <a href='diagnostico_bd.php' class='btn btn-primary me-2'>Ver Diagnóstico</a>
                        <a href='productos.php' class='btn btn-success me-2'>Ver Productos</a>
                        <a href='categorias.php' class='btn btn-info'>Ver Categorías</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
