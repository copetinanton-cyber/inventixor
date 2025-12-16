-- ========================================
-- VENTAS DE EJEMPLO - TIENDA DE CALZADO
-- ========================================

USE inventixor;

-- Ventas para Caballero
INSERT INTO Salidas (codigo_salida, descripcion, cantidad, precio_unitario, precio_total, fecha_salida, id_prod, motivo, procesada_devolucion) VALUES
('VNT-CAB-001', 'Venta Nike Air Max Caballero Talla 42', 1, 180000, 180000, '2025-10-12 10:00:00', 1, 'Venta en Tienda', 0),
('VNT-CAB-002', 'Venta Adidas Superstar Caballero Talla 41', 2, 150000, 300000, '2025-10-12 11:00:00', 2, 'Venta en Línea', 0);

-- Ventas para Dama
INSERT INTO Salidas (codigo_salida, descripcion, cantidad, precio_unitario, precio_total, fecha_salida, id_prod, motivo, procesada_devolucion) VALUES
('VNT-DAM-001', 'Venta Nike Revolution Dama Talla 38', 1, 170000, 170000, '2025-10-12 12:00:00', 5, 'Venta en Tienda', 0),
('VNT-DAM-002', 'Venta Nine West Tacón Dama Talla 36', 1, 200000, 200000, '2025-10-12 13:00:00', 7, 'Venta en Línea', 0);

-- Ventas para Infantil
INSERT INTO Salidas (codigo_salida, descripcion, cantidad, precio_unitario, precio_total, fecha_salida, id_prod, motivo, procesada_devolucion) VALUES
('VNT-INF-001', 'Venta Nike Kids Infantil Talla 30', 1, 90000, 90000, '2025-10-12 14:00:00', 9, 'Venta en Tienda', 0),
('VNT-INF-002', 'Venta Crocs Classic Infantil Talla 28', 2, 60000, 120000, '2025-10-12 15:00:00', 11, 'Venta en Línea', 0);

-- Devolución y cambio de ejemplo
INSERT INTO Salidas (codigo_salida, descripcion, cantidad, precio_unitario, precio_total, fecha_salida, id_prod, motivo, procesada_devolucion) VALUES
('DEV-001', 'Devolución Adidas Stan Smith Dama por defecto', 1, 140000, 140000, '2025-10-12 16:00:00', 6, 'Producto Defectuoso', 1),
('CAM-001', 'Cambio talla Timberland Classic Caballero', 1, 250000, 250000, '2025-10-12 17:00:00', 4, 'Cambio de Talla', 1);

-- Actualizar stock después de ventas
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 1;
UPDATE Productos SET stock_actual = stock_actual - 2 WHERE id_prod = 2;
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 5;
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 7;
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 9;
UPDATE Productos SET stock_actual = stock_actual - 2 WHERE id_prod = 11;
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 6;
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 4;

SELECT 'Ventas de ejemplo para tienda de calzado insertadas correctamente' AS resultado;
