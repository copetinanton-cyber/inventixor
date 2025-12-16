-- ========================================
-- DATOS CORRECTOS PARA TIENDA DE ZAPATOS - INVENTIXOR
-- Categorías por USUARIO: Dama, Caballero, Infantil
-- Subcategorías: MARCAS de zapatos
-- ========================================

USE inventixor;

-- ========================================
-- LIMPIAR TODOS LOS DATOS EXISTENTES
-- ========================================

SET FOREIGN_KEY_CHECKS = 0;

-- Limpiar en orden correcto
DELETE FROM Salidas;
DELETE FROM Productos;
DELETE FROM Subcategoria;
DELETE FROM Categoria;

-- Reiniciar contadores
ALTER TABLE Categoria AUTO_INCREMENT = 1;
ALTER TABLE Subcategoria AUTO_INCREMENT = 1;
ALTER TABLE Productos AUTO_INCREMENT = 1;
ALTER TABLE Salidas AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ========================================
-- 1. CATEGORÍAS CORRECTAS (SOLO POR USUARIO)
-- ========================================

INSERT INTO Categoria (id_categ, nombre) VALUES
(1, 'Calzado para Dama'),
(2, 'Calzado para Caballero'), 
(3, 'Calzado Infantil');

-- ========================================
-- 2. SUBCATEGORÍAS CORRECTAS (SOLO MARCAS)
-- ========================================

INSERT INTO Subcategoria (id_subcg, nombre, id_categ) VALUES
-- MARCAS PARA DAMA (id_categ = 1)
(1, 'Nike', 1),
(2, 'Adidas', 1),
(3, 'Puma', 1),
(4, 'Reebok', 1),
(5, 'Converse', 1),
(6, 'Vans', 1),
(7, 'Nine West', 1),
(8, 'Steve Madden', 1),
(9, 'Guess', 1),
(10, 'Michael Kors', 1),

-- MARCAS PARA CABALLERO (id_categ = 2)
(11, 'Nike', 2),
(12, 'Adidas', 2),
(13, 'Puma', 2),
(14, 'Reebok', 2),
(15, 'Converse', 2),
(16, 'Vans', 2),
(17, 'Timberland', 2),
(18, 'Clarks', 2),
(19, 'Florsheim', 2),
(20, 'Cole Haan', 2),

-- MARCAS INFANTILES (id_categ = 3)
(21, 'Nike', 3),
(22, 'Adidas', 3),
(23, 'Puma', 3),
(24, 'Converse', 3),
(25, 'Vans', 3),
(26, 'Sketchers', 3),
(27, 'Crocs', 3),
(28, 'Disney', 3);

-- ========================================
-- 3. PRODUCTOS DE CALZADO CORRECTOS
-- ========================================

INSERT INTO Productos (id_prod, codigo, nombre, descripcion, precio_unitario, stock_actual, stock_minimo, id_subcg, id_nit) VALUES

-- === CALZADO PARA DAMA ===
-- Nike Dama (id_subcg = 1)
(1, 'NK-D001', 'Nike Air Max 270 Dama', 'Zapatillas deportivas para mujer con tecnología Air Max', 180000, 25, 5, 1, 1001),
(2, 'NK-D002', 'Nike Revolution 6 Dama', 'Zapatos para correr cómodos y ligeros para dama', 120000, 30, 8, 1, 1001),
(3, 'NK-D003', 'Nike Court Vision Dama', 'Zapatillas casuales estilo basketball para mujer', 150000, 20, 5, 1, 1001),

-- Adidas Dama (id_subcg = 2)
(4, 'AD-D001', 'Adidas Ultraboost 22 Dama', 'Zapatillas de running para mujer con tecnología Boost', 220000, 15, 5, 2, 1002),
(5, 'AD-D002', 'Adidas Stan Smith Dama', 'Zapatillas clásicas de cuero blanco para mujer', 140000, 35, 10, 2, 1002),
(6, 'AD-D003', 'Adidas Gazelle Dama', 'Zapatillas retro de gamuza para mujer', 160000, 22, 6, 2, 1002),

-- Puma Dama (id_subcg = 3)
(7, 'PM-D001', 'Puma Cali Sport Dama', 'Zapatillas lifestyle para mujer con plataforma', 135000, 28, 8, 3, 1003),
(8, 'PM-D002', 'Puma Suede Classic Dama', 'Zapatillas clásicas de gamuza para mujer', 110000, 40, 12, 3, 1003),

-- Nine West Dama (id_subcg = 7)
(9, 'NW-D001', 'Nine West Tacones Clásicos', 'Zapatos de tacón alto elegantes para mujer', 180000, 12, 3, 7, 1004),
(10, 'NW-D002', 'Nine West Flats Comfort', 'Zapatos planos cómodos para oficina', 120000, 25, 8, 7, 1004),

-- === CALZADO PARA CABALLERO ===
-- Nike Caballero (id_subcg = 11)
(11, 'NK-C001', 'Nike Air Force 1 Caballero', 'Zapatillas clásicas de basketball para hombre', 170000, 20, 6, 11, 1001),
(12, 'NK-C002', 'Nike Pegasus 39 Caballero', 'Zapatos para running de alto rendimiento para hombre', 200000, 18, 5, 11, 1001),

-- Adidas Caballero (id_subcg = 12)
(13, 'AD-C001', 'Adidas Superstar Caballero', 'Zapatillas icónicas con tres rayas para hombre', 150000, 32, 8, 12, 1002),
(14, 'AD-C002', 'Adidas NMD R1 Caballero', 'Zapatillas urbanas con tecnología Boost para hombre', 190000, 16, 5, 12, 1002),

-- Timberland Caballero (id_subcg = 17)
(15, 'TB-C001', 'Timberland 6-Inch Premium', 'Botas clásicas impermeables para hombre', 280000, 10, 3, 17, 1005),
(16, 'TB-C002', 'Timberland Earthkeepers', 'Zapatos casuales ecológicos para hombre', 220000, 14, 4, 17, 1005),

-- Clarks Caballero (id_subcg = 18)
(17, 'CL-C001', 'Clarks Desert Boot', 'Botas desert clásicas de gamuza', 250000, 8, 2, 18, 1006),
(18, 'CL-C002', 'Clarks Wallabee', 'Zapatos casuales de cuero premium', 280000, 6, 2, 18, 1006),

-- === CALZADO INFANTIL ===
-- Nike Kids (id_subcg = 21)
(19, 'NK-K001', 'Nike Air Max SC Niños', 'Zapatillas deportivas para niños y niñas', 90000, 30, 10, 21, 1001),
(20, 'NK-K002', 'Nike Revolution 6 Niños', 'Zapatos cómodos para actividades infantiles', 80000, 25, 8, 21, 1001),

-- Adidas Kids (id_subcg = 22)
(21, 'AD-K001', 'Adidas Advantage Niños', 'Zapatillas casuales para niños y niñas', 85000, 28, 9, 22, 1002),
(22, 'AD-K002', 'Adidas Grand Court Niños', 'Zapatos estilo tenis clásico para niños', 75000, 35, 12, 22, 1002),

-- Converse Kids (id_subcg = 24)
(23, 'CV-K001', 'Converse Chuck Taylor Niños', 'Zapatillas clásicas de lona para niños', 70000, 40, 15, 24, 1009),
(24, 'CV-K002', 'Converse One Star Kids', 'Zapatillas casuales para niños y niñas', 85000, 20, 8, 24, 1009),

-- Crocs Kids (id_subcg = 27)
(25, 'CR-K001', 'Crocs Classic Clog Niños', 'Sandalias cómodas para niños', 60000, 50, 20, 27, 1010),
(26, 'CR-K002', 'Crocs Literide Niños', 'Sandalias deportivas para niños', 75000, 30, 12, 27, 1010);

-- ========================================
-- 4. SALIDAS DE EJEMPLO (VENTAS CORRECTAS)
-- ========================================

INSERT INTO Salidas (codigo_salida, descripcion, cantidad, precio_unitario, precio_total, fecha_salida, id_prod, motivo, procesada_devolucion) VALUES
('VNT-001', 'Venta Nike Air Max 270 Dama Talla 37', 1, 180000, 180000, '2024-10-01 10:30:00', 1, 'Venta en Tienda', 0),
('VNT-002', 'Venta Adidas Stan Smith Dama Talla 38', 2, 140000, 280000, '2024-10-01 14:15:00', 5, 'Venta en Línea', 0),
('VNT-003', 'Venta Timberland Caballero Talla 42', 1, 280000, 280000, '2024-10-02 09:45:00', 15, 'Venta en Tienda', 0),
('VNT-004', 'Venta Nike Kids Talla 32', 1, 90000, 90000, '2024-10-02 16:20:00', 19, 'Venta en Tienda', 0),
('VNT-005', 'Venta Converse Chuck Taylor Niños Talla 30', 2, 70000, 140000, '2024-10-03 11:10:00', 23, 'Venta en Tienda', 0),
('DEV-001', 'Devolución Nike Air Force 1 defectuoso', 1, 170000, 170000, '2024-10-05 10:15:00', 11, 'Producto Defectuoso', 1),
('CAM-001', 'Cambio talla Adidas Superstar', 1, 150000, 150000, '2024-10-05 14:45:00', 13, 'Cambio de Talla', 1);

-- ========================================
-- 5. ACTUALIZAR STOCK DESPUÉS DE VENTAS
-- ========================================

UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 1;  -- Nike Air Max 270 Dama
UPDATE Productos SET stock_actual = stock_actual - 2 WHERE id_prod = 5;  -- Adidas Stan Smith Dama
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 15; -- Timberland
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 19; -- Nike Kids
UPDATE Productos SET stock_actual = stock_actual - 2 WHERE id_prod = 23; -- Converse Kids
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 11; -- Nike Air Force 1
UPDATE Productos SET stock_actual = stock_actual - 1 WHERE id_prod = 13; -- Adidas Superstar

-- ========================================
-- RESUMEN CORRECTO DE DATOS
-- ========================================
-- Categorías: 3 (Dama, Caballero, Infantil)
-- Subcategorías: 28 (Solo marcas, sin duplicar por categoría)
-- Productos: 26 (Calzado específico por categoría y marca)
-- Salidas: 7 (Ventas y devoluciones realistas)
-- ========================================

SELECT 'Datos corregidos para tienda de zapatos insertados correctamente' AS resultado;