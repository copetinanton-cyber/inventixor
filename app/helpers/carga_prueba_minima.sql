-- Carga mínima de datos de prueba compatibles con db.sql
USE inventixor;

-- Categorías
INSERT IGNORE INTO Categoria (id_categ, nombre, descripcion) VALUES
(1, 'Calzado', 'Calzado general'),
(2, 'Electrónicos', 'Dispositivos electrónicos');

-- Subcategorías
INSERT IGNORE INTO Subcategoria (id_subcg, nombre, descripcion, id_categ) VALUES
(1, 'Tenis', 'Zapatos deportivos', 1),
(2, 'Smartphones', 'Teléfonos inteligentes', 2);

-- Proveedores (usa columna correo, no email)
INSERT IGNORE INTO Proveedores (id_nit, razon_social, contacto, direccion, correo, telefono, estado, detalles) VALUES
(1001, 'Nike Colombia S.A.S.', 'Juan Pérez', 'Calle 100 #15-20, Bogotá', 'ventas@nike.com.co', '3101234567', 'activo', 'Proveedor de calzado'),
(1002, 'Samsung Electronics', 'Ana López', 'Zona Franca, Bogotá', 'colombia@samsung.com', '3207654321', 'activo', 'Proveedor de electrónicos');

-- Productos (ajustado a columnas de db.sql)
INSERT IGNORE INTO Productos (id_prod, nombre, modelo, talla, color, stock, fecha_ing, material, id_subcg, id_nit, num_doc) VALUES
(1, 'Nike Air Max', 'AM-2024', '42', 'Negro', '25', CURDATE(), 'Sintético y malla', 1, 1001, NULL),
(2, 'Samsung Galaxy S23', 'SM-S911B', 'Único', 'Negro', '8', CURDATE(), 'Aluminio y vidrio', 2, 1002, NULL);

-- Salidas (ajustado a columnas de db.sql)
INSERT IGNORE INTO Salidas (id_salida, tipo_salida, fecha_hora, cantidad, observacion, id_prod) VALUES
(1, 'venta', NOW() - INTERVAL 10 DAY, '2', 'Venta regular', 1),
(2, 'venta', NOW() - INTERVAL 5 DAY, '1', 'Venta smartphone', 2);

-- Alertas opcionales
INSERT IGNORE INTO Alertas (id_alerta, tipo_alerta, observacion, nivel_alerta, fecha_generacion, estado, id_prod) VALUES
(1, 'stock_bajo', 'Stock por debajo del mínimo', 'alta', CURDATE(), 'pendiente', 2);

-- Reportes (estructura de db.sql)
INSERT IGNORE INTO Reportes (id_repor, nombre, descripcion, fecha_hora, num_doc, id_nit, id_prod, id_alerta) VALUES
(1, 'Inventario General', 'Reporte de inventario completo', NOW() - INTERVAL 1 DAY, 1001, NULL, NULL, NULL),
(2, 'Productos Críticos', 'Productos con bajo stock', NOW() - INTERVAL 12 HOUR, 1001, NULL, 2, 1);
