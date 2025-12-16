-- ========================================
-- PRODUCTOS DE EJEMPLO - TIENDA DE CALZADO
-- ========================================

USE inventixor;

-- Caballero
INSERT INTO Productos (codigo, nombre, descripcion, precio_unitario, stock_actual, stock_minimo, id_subcg, id_nit) VALUES
('NK-CAB-001', 'Nike Air Max Caballero', 'Zapatillas deportivas para hombre', 180000, 20, 5, 1, 1001),
('AD-CAB-001', 'Adidas Superstar Caballero', 'Zapatillas clásicas para hombre', 150000, 15, 5, 2, 1002),
('PM-CAB-001', 'Puma Smash Caballero', 'Zapatillas casuales para hombre', 120000, 10, 3, 3, 1003),
('TB-CAB-001', 'Timberland Classic Caballero', 'Botas impermeables para hombre', 250000, 8, 2, 7, 1004);

-- Dama
INSERT INTO Productos (codigo, nombre, descripcion, precio_unitario, stock_actual, stock_minimo, id_subcg, id_nit) VALUES
('NK-DAM-001', 'Nike Revolution Dama', 'Zapatillas deportivas para mujer', 170000, 18, 5, 9, 1001),
('AD-DAM-001', 'Adidas Stan Smith Dama', 'Zapatillas clásicas para mujer', 140000, 12, 4, 10, 1002),
('NW-DAM-001', 'Nine West Tacón Dama', 'Zapatos de tacón alto elegantes', 200000, 6, 2, 15, 1005),
('SM-DAM-001', 'Steve Madden Flats Dama', 'Zapatos planos para mujer', 130000, 10, 3, 16, 1006);

-- Infantil
INSERT INTO Productos (codigo, nombre, descripcion, precio_unitario, stock_actual, stock_minimo, id_subcg, id_nit) VALUES
('NK-INF-001', 'Nike Kids Infantil', 'Zapatillas deportivas para niños', 90000, 25, 6, 17, 1001),
('AD-INF-001', 'Adidas Kids Infantil', 'Zapatillas casuales para niños', 85000, 20, 5, 18, 1002),
('CR-INF-001', 'Crocs Classic Infantil', 'Sandalias cómodas para niños', 60000, 30, 8, 23, 1007),
('DS-INF-001', 'Disney Princess Infantil', 'Zapatos temáticos para niñas', 95000, 15, 3, 24, 1008);

-- Puedes agregar más productos siguiendo el mismo formato
SELECT 'Productos de ejemplo para tienda de calzado insertados correctamente' AS resultado;
