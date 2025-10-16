-- Movimientos de salidas variados para alimentar KPIs y reportes
USE inventixor;

SET @old_fk = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

-- Bajar stock de algunos productos para generar "productos críticos"
UPDATE Productos SET stock = '3'
WHERE id_prod = (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 0,1) t);
UPDATE Productos SET stock = '4'
WHERE id_prod = (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 1,1) t);
UPDATE Productos SET stock = '2'
WHERE id_prod = (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 2,1) t);

-- Inserciones de salidas en los últimos 30 días con distintas cantidades
INSERT IGNORE INTO Salidas (tipo_salida, fecha_hora, cantidad, observacion, id_prod) VALUES
('venta', NOW() - INTERVAL 1 DAY, '2', 'Venta en tienda', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 0,1) t)),
('venta', NOW() - INTERVAL 2 DAY, '1', 'Venta online', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 0,1) t)),
('venta', NOW() - INTERVAL 3 DAY, '3', 'Venta corporativa', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 1,1) t)),
('venta', NOW() - INTERVAL 4 DAY, '1', 'Venta en tienda', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 2,1) t)),
('venta', NOW() - INTERVAL 5 DAY, '2', 'Venta fin de semana', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 3,1) t)),
('venta', NOW() - INTERVAL 6 DAY, '2', 'Venta en tienda', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 4,1) t)),
('venta', NOW() - INTERVAL 7 DAY, '1', 'Venta online', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 5,1) t)),
('venta', NOW() - INTERVAL 8 DAY, '2', 'Venta en tienda', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 6,1) t)),
('venta', NOW() - INTERVAL 9 DAY, '1', 'Venta en tienda', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 7,1) t)),
('venta', NOW() - INTERVAL 10 DAY, '3', 'Venta mayorista', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 0,1) t)),
('producto_dañado', NOW() - INTERVAL 11 DAY, '1', 'Producto con defecto', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 8,1) t)),
('uso_interno', NOW() - INTERVAL 12 DAY, '1', 'Muestra de exhibición', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 3,1) t)),
('venta', NOW() - INTERVAL 13 DAY, '2', 'Venta online', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 9,1) t)),
('venta', NOW() - INTERVAL 14 DAY, '1', 'Venta en tienda', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 10,1) t)),
('venta', NOW() - INTERVAL 15 DAY, '2', 'Venta en tienda', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 11,1) t)),
('venta', NOW() - INTERVAL 16 DAY, '1', 'Venta online', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 1,1) t)),
('venta', NOW() - INTERVAL 17 DAY, '2', 'Venta en tienda', (SELECT idp FROM (SELECT id_prod AS idp FROM Productos ORDER BY id_prod LIMIT 2,1) t));

SET FOREIGN_KEY_CHECKS = @old_fk;
