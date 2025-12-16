-- Carga de ejemplo realista para Inventixor - Calzado
-- Requiere que Categoria y Subcategoria ya existan como:
-- Categoria: 1=Dama, 2=Caballero, 3=Niños
-- Subcategoria: Marcas repetidas por categoría (Nike, Adidas, Puma, Reebok, Converse, Vans, Timberland, Clarks, New Balance, Under Armour)

USE inventixor;

SET @old_fk = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

-- Proveedores base (usa columnas de db.sql)
INSERT IGNORE INTO Proveedores (id_nit, razon_social, contacto, direccion, correo, telefono, estado, detalles) VALUES
(5001, 'Nike Colombia S.A.S.', 'Juan Pérez', 'Calle 100 #15-20, Bogotá', 'ventas@nike.com.co', '3101234567', 'activo', 'Distribuidor oficial'),
(5002, 'Adidas Colombia', 'Ana López', 'Carrera 7 #32-16, Medellín', 'colombia@adidas.com', '3207654321', 'activo', 'Distribuidor oficial'),
(5003, 'Puma LATAM', 'Carlos Ruiz', 'Av. El Dorado #68-90, Bogotá', 'latam@puma.com', '3156789012', 'activo', 'Distribuidor regional'),
(5004, 'Reebok Colombia', 'Laura Gómez', 'Calle 72 #10-07, Bogotá', 'ventas@reebok.co', '3189876543', 'activo', 'Retail'),
(5005, 'Converse Retail', 'Miguel Torres', 'Cr 15 #93-60, Bogotá', 'info@converse.co', '3132223344', 'activo', 'Retail'),
(5006, 'Vans Colombia', 'Paula Sierra', 'Zona Franca, Bogotá', 'ventas@vans.co', '3015566778', 'activo', 'Retail'),
(5007, 'Timberland Import', 'Diego León', 'Calle 116 #7-15, Bogotá', 'import@timberland.co', '3123344556', 'activo', 'Importador'),
(5008, 'Clarks Andina', 'Natalia Ríos', 'CC Andino, Bogotá', 'ventas@clarks.co', '3004455667', 'activo', 'Retail'),
(5009, 'New Balance Co', 'Jorge Peña', 'Cr 7 #75-45, Bogotá', 'ventas@newbalance.co', '3025566778', 'activo', 'Retail'),
(5010, 'Under Armour Co', 'Sofía Vivas', 'Av 68 #75-50, Bogotá', 'ventas@underarmour.co', '3046677889', 'activo', 'Retail');

-- Helper: obtener id_subcg por (marca, categoria)
DROP TEMPORARY TABLE IF EXISTS tmp_subs;
CREATE TEMPORARY TABLE tmp_subs AS
SELECT s.id_subcg, s.nombre as marca, c.id_categ
FROM Subcategoria s
JOIN Categoria c ON c.id_categ = s.id_categ
WHERE s.nombre IN ('Nike','Adidas','Puma','Reebok','Converse','Vans','Timberland','Clarks','New Balance','Under Armour');

-- Inserción de productos realistas (stock como VARCHAR por esquema actual)
-- Dama (cat 1): Nike, Adidas, Puma
INSERT IGNORE INTO Productos (nombre, modelo, talla, color, stock, fecha_ing, material, id_subcg, id_nit, num_doc)
SELECT 'Nike Air Max 270 Dama', 'AM270-W', '37', 'Negro', '15', CURDATE(), 'Malla/Sintético', ts.id_subcg, 5001, NULL FROM tmp_subs ts WHERE ts.marca='Nike' AND ts.id_categ=1 UNION ALL
SELECT 'Nike Court Vision Dama', 'NCV-W', '38', 'Blanco', '20', CURDATE(), 'Cuero', ts.id_subcg, 5001, NULL FROM tmp_subs ts WHERE ts.marca='Nike' AND ts.id_categ=1 UNION ALL
SELECT 'Adidas Ultraboost 22 W', 'UB22-W', '37', 'Blanco', '12', CURDATE(), 'Textil/Boost', ts.id_subcg, 5002, NULL FROM tmp_subs ts WHERE ts.marca='Adidas' AND ts.id_categ=1 UNION ALL
SELECT 'Puma Cali Sport W', 'CALISP-W', '36', 'Rosa', '18', CURDATE(), 'Gamuza', ts.id_subcg, 5003, NULL FROM tmp_subs ts WHERE ts.marca='Puma' AND ts.id_categ=1;

-- Caballero (cat 2): Nike, Adidas, Timberland, Clarks
INSERT IGNORE INTO Productos (nombre, modelo, talla, color, stock, fecha_ing, material, id_subcg, id_nit, num_doc)
SELECT 'Nike Air Force 1 M', 'AF1-M', '42', 'Blanco', '22', CURDATE(), 'Cuero', ts.id_subcg, 5001, NULL FROM tmp_subs ts WHERE ts.marca='Nike' AND ts.id_categ=2 UNION ALL
SELECT 'Adidas Superstar M', 'SS-M', '41', 'Negro', '25', CURDATE(), 'Cuero', ts.id_subcg, 5002, NULL FROM tmp_subs ts WHERE ts.marca='Adidas' AND ts.id_categ=2 UNION ALL
SELECT 'Timberland 6-Inch Premium', 'TB-6IN', '43', 'Miel', '10', CURDATE(), 'Cuero/Impermeable', ts.id_subcg, 5007, NULL FROM tmp_subs ts WHERE ts.marca='Timberland' AND ts.id_categ=2 UNION ALL
SELECT 'Clarks Desert Boot', 'CDB-M', '42', 'Café', '14', CURDATE(), 'Cuero', ts.id_subcg, 5008, NULL FROM tmp_subs ts WHERE ts.marca='Clarks' AND ts.id_categ=2;

-- Niños (cat 3): Converse, Vans, New Balance, Under Armour
INSERT IGNORE INTO Productos (nombre, modelo, talla, color, stock, fecha_ing, material, id_subcg, id_nit, num_doc)
SELECT 'Converse Chuck Taylor Kids', 'CT-K', '32', 'Rojo', '30', CURDATE(), 'Lona', ts.id_subcg, 5005, NULL FROM tmp_subs ts WHERE ts.marca='Converse' AND ts.id_categ=3 UNION ALL
SELECT 'Vans Old Skool Kids', 'OS-K', '33', 'Negro', '28', CURDATE(), 'Lona/Suela Goma', ts.id_subcg, 5006, NULL FROM tmp_subs ts WHERE ts.marca='Vans' AND ts.id_categ=3 UNION ALL
SELECT 'New Balance 574 Kids', 'NB574-K', '31', 'Gris', '16', CURDATE(), 'Textil/Sintético', ts.id_subcg, 5009, NULL FROM tmp_subs ts WHERE ts.marca='New Balance' AND ts.id_categ=3 UNION ALL
SELECT 'Under Armour Assert Kids', 'UA-ASK', '30', 'Azul', '18', CURDATE(), 'Malla', ts.id_subcg, 5010, NULL FROM tmp_subs ts WHERE ts.marca='Under Armour' AND ts.id_categ=3;

-- Salidas de ejemplo (coherentes con db.sql)
INSERT IGNORE INTO Salidas (tipo_salida, fecha_hora, cantidad, observacion, id_prod)
SELECT 'venta', NOW() - INTERVAL 5 DAY, '2', 'Venta tienda', p.id_prod FROM Productos p ORDER BY p.id_prod LIMIT 1;

SET FOREIGN_KEY_CHECKS = @old_fk;
