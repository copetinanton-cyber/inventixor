-- ========================================
-- DATOS DE PRUEBA PARA TIENDA DE ZAPATOS - INVENTIXOR
-- Script especializado para calzado por categoría y marcas
-- Fecha: 2025-10-12
-- ========================================

USE inventixor;

-- Limpiar datos existentes
DELETE FROM Productos;
DELETE FROM Subcategoria;
DELETE FROM Categoria;
DELETE FROM Salidas;

-- Reiniciar AUTO_INCREMENT
ALTER TABLE Categoria AUTO_INCREMENT = 1;
ALTER TABLE Subcategoria AUTO_INCREMENT = 1;
ALTER TABLE Productos AUTO_INCREMENT = 1;
ALTER TABLE Salidas AUTO_INCREMENT = 1;

-- ========================================
-- 1. CATEGORÍAS POR TIPO DE USUARIO
-- ========================================

INSERT INTO Categoria (id_categ, nombre) VALUES
(1, 'Calzado para Dama'),
(2, 'Calzado para Caballero'),
(3, 'Calzado Infantil'),
(4, 'Calzado Deportivo'),
(5, 'Calzado Formal');

-- ========================================
-- 2. SUBCATEGORÍAS (MARCAS DE ZAPATOS)
-- ========================================

INSERT INTO Subcategoria (id_subcg, nombre, id_categ) VALUES
-- Marcas para Dama
(1, 'Nike Dama', 1),
(2, 'Adidas Dama', 1),
(3, 'Puma Dama', 1),
(4, 'Reebok Dama', 1),
(5, 'Converse Dama', 1),
(6, 'Vans Dama', 1),
(7, 'Nine West', 1),
(8, 'Steve Madden', 1),

-- Marcas para Caballero
(9, 'Nike Caballero', 2),
(10, 'Adidas Caballero', 2),
(11, 'Puma Caballero', 2),
(12, 'Reebok Caballero', 2),
(13, 'Converse Caballero', 2),
(14, 'Vans Caballero', 2),
(15, 'Timberland', 2),
(16, 'Clarks', 2),

-- Marcas Infantiles
(17, 'Nike Kids', 3),
(18, 'Adidas Kids', 3),
(19, 'Puma Kids', 3),
(20, 'Converse Kids', 3),
(21, 'Vans Kids', 3),
(22, 'Sketchers Kids', 3),

-- Marcas Deportivas (Unisex)
(23, 'Nike Running', 4),
(24, 'Adidas Sport', 4),
(25, 'Puma Training', 4),
(26, 'Under Armour', 4),
(27, 'New Balance', 4),

-- Marcas Formales
(28, 'Florsheim', 5),
(29, 'Cole Haan', 5),
(30, 'Kenneth Cole', 5),
(31, 'Guess Formal', 5);

-- ========================================
-- 3. PRODUCTOS DE CALZADO
-- ========================================

INSERT INTO Productos (id_prod, codigo, nombre, descripcion, precio_unitario, stock_actual, stock_minimo, id_subcg, id_nit) VALUES

-- === CALZADO PARA DAMA ===
-- Nike Dama
(1, 'NK-D001', 'Nike Air Max 270 Dama', 'Zapatillas deportivas para mujer con tecnología Air Max', 180000, 25, 5, 1, 1001),
(2, 'NK-D002', 'Nike Revolution 6 Dama', 'Zapatos para correr cómodos y ligeros', 120000, 30, 8, 1, 1001),
(3, 'NK-D003', 'Nike Court Vision Dama', 'Zapatillas casuales estilo basketball', 150000, 20, 5, 1, 1001),

-- Adidas Dama
(4, 'AD-D001', 'Adidas Ultraboost 22 Dama', 'Zapatillas de running con tecnología Boost', 220000, 15, 5, 2, 1002),
(5, 'AD-D002', 'Adidas Stan Smith Dama', 'Zapatillas clásicas de cuero blanco', 140000, 35, 10, 2, 1002),
(6, 'AD-D003', 'Adidas Gazelle Dama', 'Zapatillas retro de gamuza', 160000, 22, 6, 2, 1002),

-- Puma Dama
(7, 'PM-D001', 'Puma Cali Sport Dama', 'Zapatillas lifestyle con plataforma', 135000, 28, 8, 3, 1003),
(8, 'PM-D002', 'Puma Suede Classic Dama', 'Zapatillas clásicas de gamuza', 110000, 40, 12, 3, 1003),

-- Nine West
(9, 'NW-D001', 'Nine West Tacones Clásicos', 'Zapatos de tacón alto elegantes', 180000, 12, 3, 7, 1004),
(10, 'NW-D002', 'Nine West Flats Comfort', 'Zapatos planos cómodos para oficina', 120000, 25, 8, 7, 1004),

-- === CALZADO PARA CABALLERO ===
-- Nike Caballero
(11, 'NK-C001', 'Nike Air Force 1 Caballero', 'Zapatillas clásicas de basketball', 170000, 20, 6, 9, 1001),
(12, 'NK-C002', 'Nike Pegasus 39 Caballero', 'Zapatos para running de alto rendimiento', 200000, 18, 5, 9, 1001),

-- Adidas Caballero
(13, 'AD-C001', 'Adidas Superstar Caballero', 'Zapatillas icónicas con tres rayas', 150000, 32, 8, 10, 1002),
(14, 'AD-C002', 'Adidas NMD R1 Caballero', 'Zapatillas urbanas con tecnología Boost', 190000, 16, 5, 10, 1002),

-- Timberland
(15, 'TB-C001', 'Timberland 6-Inch Premium', 'Botas clásicas impermeables', 280000, 10, 3, 15, 1005),
(16, 'TB-C002', 'Timberland Earthkeepers', 'Zapatos casuales ecológicos', 220000, 14, 4, 15, 1005),

-- === CALZADO INFANTIL ===
-- Nike Kids
(17, 'NK-K001', 'Nike Air Max SC Niños', 'Zapatillas deportivas para niños', 90000, 30, 10, 17, 1001),
(18, 'NK-K002', 'Nike Revolution 6 Niños', 'Zapatos cómodos para actividades', 80000, 25, 8, 17, 1001),

-- Adidas Kids
(19, 'AD-K001', 'Adidas Advantage Niños', 'Zapatillas casuales para niños', 85000, 28, 9, 18, 1002),
(20, 'AD-K002', 'Adidas Grand Court Niños', 'Zapatos estilo tenis clásico', 75000, 35, 12, 18, 1002),

-- === CALZADO DEPORTIVO ===
-- Nike Running
(21, 'NK-R001', 'Nike ZoomX Vaporfly', 'Zapatos profesionales para maratón', 350000, 8, 2, 23, 1001),
(22, 'NK-R002', 'Nike React Infinity Run', 'Zapatos para running diario', 240000, 15, 4, 23, 1001),

-- Under Armour
(23, 'UA-001', 'Under Armour HOVR Phantom', 'Zapatos inteligentes con sensor', 280000, 12, 3, 26, 1006),
(24, 'UA-002', 'Under Armour Charged Assert', 'Zapatos para entrenamiento', 180000, 20, 6, 26, 1006),

-- === CALZADO FORMAL ===
-- Florsheim
(25, 'FL-001', 'Florsheim Oxford Clásico', 'Zapatos formales de cuero genuino', 320000, 8, 2, 28, 1007),
(26, 'FL-002', 'Florsheim Loafers Premium', 'Mocasines elegantes sin cordones', 280000, 10, 3, 28, 1007),

-- Cole Haan
(27, 'CH-001', 'Cole Haan Grand Crosscourt', 'Zapatos híbridos formal-casual', 380000, 6, 2, 29, 1008),
(28, 'CH-002', 'Cole Haan Original Grand', 'Zapatos Oxford con tecnología', 420000, 5, 1, 29, 1008),

-- Marcas adicionales para variedad
-- Converse
(29, 'CV-001', 'Converse Chuck Taylor All Star', 'Zapatillas clásicas de lona', 95000, 50, 15, 5, 1009),
(30, 'CV-002', 'Converse One Star Pro', 'Zapatillas de skate profesionales', 110000, 25, 8, 5, 1009);

-- ========================================
-- 4. SALIDAS DE EJEMPLO (VENTAS)
-- ========================================

INSERT INTO Salidas (codigo_salida, descripcion, cantidad, precio_unitario, precio_total, fecha_salida, id_prod, motivo, procesada_devolucion) VALUES
('VNT-001', 'Venta zapatillas Nike Air Max 270 Dama Talla 37', 1, 180000, 180000, '2024-10-01 10:30:00', 1, 'Venta en Tienda', 0),
('VNT-002', 'Venta zapatos Adidas Stan Smith Dama Talla 38', 2, 140000, 280000, '2024-10-01 14:15:00', 5, 'Venta en Línea', 0),
('VNT-003', 'Venta botas Timberland Caballero Talla 42', 1, 280000, 280000, '2024-10-02 09:45:00', 15, 'Venta en Tienda', 0),
('VNT-004', 'Venta zapatillas Nike Kids Talla 32', 1, 90000, 90000, '2024-10-02 16:20:00', 17, 'Venta en Tienda', 0),
('VNT-005', 'Venta zapatos Under Armour Training Talla 41', 1, 180000, 180000, '2024-10-03 11:10:00', 24, 'Venta Mayorista', 0),
('VNT-006', 'Venta Converse Chuck Taylor Talla 39', 2, 95000, 190000, '2024-10-03 15:30:00', 29, 'Venta en Tienda', 0),
('VNT-007', 'Venta zapatos formales Cole Haan Talla 40', 1, 380000, 380000, '2024-10-04 12:00:00', 27, 'Venta Corporativa', 0),
('DEV-001', 'Devolución Nike Air Force 1 defectuoso', 1, 170000, 170000, '2024-10-05 10:15:00', 11, 'Producto Defectuoso', 1),
('CAM-001', 'Cambio talla Adidas Superstar', 1, 150000, 150000, '2024-10-05 14:45:00', 13, 'Cambio de Talla', 1),
('GAR-001', 'Garantía zapatos Florsheim Oxford', 1, 320000, 320000, '2024-10-06 09:30:00', 25, 'Reclamo de Garantía', 1);

-- ========================================
-- 5. ACTUALIZAR STOCK DESPUÉS DE LAS SALIDAS
-- ========================================

UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 1;  -- Nike Air Max 270 Dama
UPDATE Productos SET stock_actual = stock_actual - 2 WHERE id_prod = 5;  -- Adidas Stan Smith Dama
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 15; -- Timberland
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 17; -- Nike Kids
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 24; -- Under Armour
UPDATE Productos SET stock_actual = stock_actual - 2 WHERE id_prod = 29; -- Converse
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 27; -- Cole Haan
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 11; -- Nike Air Force 1
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 13; -- Adidas Superstar
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 25; -- Florsheim

-- ========================================
-- RESUMEN DE DATOS CREADOS
-- ========================================
-- Categorías: 5 (Dama, Caballero, Infantil, Deportivo, Formal)
-- Subcategorías: 31 (Marcas especializadas por categoría)
-- Productos: 30 (Variedad de calzado por marca y tipo)
-- Salidas: 10 (Ventas, devoluciones, cambios, garantías)
-- ========================================

SELECT 'Datos de tienda de zapatos insertados correctamente' AS resultado;