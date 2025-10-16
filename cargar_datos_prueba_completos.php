<?php
require_once 'app/helpers/Database.php';
$db = new Database();
$conn = $db->conn;
$msg = '';
try {
    // 1. Proveedores
    $proveedores = [
        ['1001', 'Nike Colombia S.A.', 'Juan Pérez', 'Cra 10 #20-30', 'nike@proveedor.com', '3101234567', 'activo', 'Proveedor oficial de Nike'],
        ['1002', 'Adidas S.A.S.', 'Ana Gómez', 'Av 15 #45-67', 'adidas@proveedor.com', '3209876543', 'activo', 'Proveedor oficial de Adidas'],
        ['1003', 'Puma Ltda.', 'Carlos Ruiz', 'Calle 8 #12-34', 'puma@proveedor.com', '3004567890', 'activo', 'Proveedor oficial de Puma'],
        ['1004', 'Timberland S.A.', 'Laura Torres', 'Cra 7 #33-21', 'timberland@proveedor.com', '3112345678', 'activo', 'Proveedor oficial de Timberland'],
    ];
    foreach ($proveedores as $p) {
        $stmt = $conn->prepare("INSERT IGNORE INTO Proveedores (id_nit, razon_social, contacto, direccion, correo, telefono, estado, detalles) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssssss', $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7]);
        $stmt->execute();
        $stmt->close();
    }
    // 2. Usuarios
    $usuarios = [
        ['101010', 1, 'Martínez', 'Sofía', '3101112233', 'sofia@tienda.com', 'Gerente', 'admin', '1234', 1],
        ['202020', 1, 'García', 'Luis', '3202223344', 'luis@tienda.com', 'Vendedor', 'empleado', '1234', 1],
        ['303030', 1, 'López', 'María', '3003334455', 'maria@tienda.com', 'Inventarios', 'empleado', '1234', 1],
    ];
    foreach ($usuarios as $u) {
        $stmt = $conn->prepare("INSERT IGNORE INTO Users (num_doc, tipo_documento, apellidos, nombres, telefono, correo, cargo, rol, contrasena, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iississssi', $u[0], $u[1], $u[2], $u[3], $u[4], $u[5], $u[6], $u[7], $u[8], $u[9]);
        $stmt->execute();
        $stmt->close();
    }
    // 3. Productos (ejemplo para cada marca y categoría)
    $productos = [
        // Caballero
        ['Nike Air Max Caballero', 'AMAX', '42', 'Negro', 20, 5, 180000, 'Sintético', 'Zapatillas deportivas para hombre', 1, 1001, 101010],
        ['Adidas Superstar Caballero', 'SUPERSTAR', '41', 'Blanco', 15, 5, 150000, 'Cuero', 'Zapatillas clásicas para hombre', 2, 1002, 202020],
        // Dama
        ['Nike Revolution Dama', 'REV', '38', 'Rosa', 18, 5, 170000, 'Sintético', 'Zapatillas deportivas para mujer', 9, 1001, 303030],
        ['Adidas Stan Smith Dama', 'STANSMITH', '37', 'Blanco', 12, 4, 140000, 'Cuero', 'Zapatillas clásicas para mujer', 10, 1002, 101010],
        // Infantil
        ['Nike Kids Infantil', 'KIDS', '30', 'Azul', 25, 6, 90000, 'Sintético', 'Zapatillas deportivas para niños', 17, 1001, 202020],
        ['Crocs Classic Infantil', 'CROC', '28', 'Verde', 30, 8, 60000, 'Plástico', 'Sandalias cómodas para niños', 23, 1003, 303030],
    ];
    foreach ($productos as $p) {
        $stmt = $conn->prepare("INSERT INTO Productos (nombre, modelo, talla, color, stock, stock_minimo, precio_unitario, material, descripcion, id_subcg, id_nit, num_doc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssiidssiis', $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], $p[9], $p[10], $p[11]);
        $stmt->execute();
        $stmt->close();
    }
    // 4. Salidas (ventas, devoluciones, cambios)
    $salidas = [
        // Ventas
        ['venta', 'completada', '2025-10-12 10:00:00', null, 1, 180000, 'Venta Nike Air Max Caballero', null, 101010, 1],
        ['venta', 'completada', '2025-10-12 11:00:00', null, 1, 150000, 'Venta Adidas Superstar Caballero', null, 202020, 2],
        ['venta', 'completada', '2025-10-12 12:00:00', null, 1, 170000, 'Venta Nike Revolution Dama', null, 303030, 3],
        // Devoluciones
        ['devolucion', 'completada', '2025-10-12 13:00:00', null, 1, 140000, 'Devolución Adidas Stan Smith Dama', null, 101010, 4],
        // Cambios
        ['cambio', 'completada', '2025-10-12 14:00:00', null, 1, 90000, 'Cambio Nike Kids Infantil', null, 202020, 5],
    ];
    foreach ($salidas as $s) {
        $stmt = $conn->prepare("INSERT INTO Salidas (tipo_salida, estado_salida, fecha_hora, fecha_entrega, cantidad, precio_venta, observacion, cliente_info, num_doc_usuario, id_prod) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssidsisi', $s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9]);
        $stmt->execute();
        $stmt->close();
    }
    // 5. Alertas
    $alertas = [
        ['stock bajo', 'Producto con stock menor al mínimo', 'alta', 'pendiente', 1, null],
        ['producto defectuoso', 'Devolución por defecto', 'media', 'pendiente', 4, 101010],
    ];
    foreach ($alertas as $a) {
        $stmt = $conn->prepare("INSERT INTO Alertas (tipo_alerta, observacion, nivel_alerta, estado, id_prod, resuelto_por) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssii', $a[0], $a[1], $a[2], $a[3], $a[4], $a[5]);
        $stmt->execute();
        $stmt->close();
    }
    // 6. Notificaciones
    $notificaciones = [
        ['venta', 'Venta registrada', 'Se ha registrado una venta de calzado', null, 'media', 'fas fa-shopping-cart', 'success', null, 1, 'sistema'],
        ['devolucion', 'Devolución registrada', 'Se ha registrado una devolución de calzado', null, 'alta', 'fas fa-undo', 'danger', null, 1, 'sistema'],
    ];
    foreach ($notificaciones as $n) {
        $stmt = $conn->prepare("INSERT INTO NotificacionesSistema (tipo_evento, titulo, mensaje, datos_evento, nivel_prioridad, icono, color, mostrar_hasta, activa, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssssss', $n[0], $n[1], $n[2], $n[3], $n[4], $n[5], $n[6], $n[7], $n[8], $n[9]);
        $stmt->execute();
        $stmt->close();
    }
    $msg = "<div class='alert alert-success'><strong>¡Datos de prueba completos cargados!</strong> Todos los módulos tienen datos de ejemplo para pruebas.</div>";
} catch (Exception $e) {
    $msg = "<div class='alert alert-danger'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Cargar Datos de Prueba Completos - Inventixor</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-5'>
    <div class='row justify-content-center'>
        <div class='col-md-8'>
            <div class='card shadow'>
                <div class='card-header bg-success text-white'>
                    <h3 class='mb-0'><i class='fas fa-database me-2'></i>Cargar Datos de Prueba Completos</h3>
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
