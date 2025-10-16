-- Limpieza total de datos de prueba y configuración base para tienda de calzado
-- Esquema objetivo: db.sql (tablas básicas)

USE inventixor;

SET @old_fk = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

-- 1) Vaciar datos operativos (orden seguro)
DELETE FROM Reportes;
DELETE FROM Alertas;
DELETE FROM Salidas;
DELETE FROM Productos;
DELETE FROM Autorizaciones;

-- 2) Vaciar catálogos (dejar limpio)
DELETE FROM Subcategoria;
DELETE FROM Categoria;
DELETE FROM Proveedores;

-- 3) Reiniciar auto-incrementos
ALTER TABLE Reportes AUTO_INCREMENT = 1;
ALTER TABLE Alertas AUTO_INCREMENT = 1;
ALTER TABLE Salidas AUTO_INCREMENT = 1;
ALTER TABLE Productos AUTO_INCREMENT = 1;
ALTER TABLE Autorizaciones AUTO_INCREMENT = 1;
ALTER TABLE Subcategoria AUTO_INCREMENT = 1;
ALTER TABLE Categoria AUTO_INCREMENT = 1;
ALTER TABLE Proveedores AUTO_INCREMENT = 1;

-- 4) Reconfigurar estructura de negocio para calzado
-- Categorías orientadas a Dama, Caballero y Niños
INSERT INTO Categoria (id_categ, nombre, descripcion) VALUES
(1, 'Dama', 'Calzado para mujer'),
(2, 'Caballero', 'Calzado para hombre'),
(3, 'Niños', 'Calzado infantil');

-- Subcategorías como marcas de calzado (repetidas por categoría)
-- Lista base de marcas: Nike, Adidas, Puma, Reebok, Converse, Vans, Timberland, Clarks, New Balance, Under Armour
INSERT INTO Subcategoria (nombre, descripcion, id_categ) VALUES
-- Dama (id_categ = 1)
('Nike', 'Marca de calzado', 1),
('Adidas', 'Marca de calzado', 1),
('Puma', 'Marca de calzado', 1),
('Reebok', 'Marca de calzado', 1),
('Converse', 'Marca de calzado', 1),
('Vans', 'Marca de calzado', 1),
('Timberland', 'Marca de calzado', 1),
('Clarks', 'Marca de calzado', 1),
('New Balance', 'Marca de calzado', 1),
('Under Armour', 'Marca de calzado', 1),
-- Caballero (id_categ = 2)
('Nike', 'Marca de calzado', 2),
('Adidas', 'Marca de calzado', 2),
('Puma', 'Marca de calzado', 2),
('Reebok', 'Marca de calzado', 2),
('Converse', 'Marca de calzado', 2),
('Vans', 'Marca de calzado', 2),
('Timberland', 'Marca de calzado', 2),
('Clarks', 'Marca de calzado', 2),
('New Balance', 'Marca de calzado', 2),
('Under Armour', 'Marca de calzado', 2),
-- Niños (id_categ = 3)
('Nike', 'Marca de calzado', 3),
('Adidas', 'Marca de calzado', 3),
('Puma', 'Marca de calzado', 3),
('Reebok', 'Marca de calzado', 3),
('Converse', 'Marca de calzado', 3),
('Vans', 'Marca de calzado', 3),
('Timberland', 'Marca de calzado', 3),
('Clarks', 'Marca de calzado', 3),
('New Balance', 'Marca de calzado', 3),
('Under Armour', 'Marca de calzado', 3);

SET FOREIGN_KEY_CHECKS = @old_fk;