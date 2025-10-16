-- ========================================
-- RESET TIENDA DE CALZADO - INVENTIXOR
-- Elimina todos los datos y reinicia los IDs
-- Estructura: Categoría = tipo de usuario, Subcategoría = marca
-- ========================================

USE inventixor;

SET FOREIGN_KEY_CHECKS = 0;

-- Eliminar datos de tablas principales
DELETE FROM Salidas;
DELETE FROM Productos;
DELETE FROM Subcategoria;
DELETE FROM Categoria;

-- Reiniciar AUTO_INCREMENT
ALTER TABLE Categoria AUTO_INCREMENT = 1;
ALTER TABLE Subcategoria AUTO_INCREMENT = 1;
ALTER TABLE Productos AUTO_INCREMENT = 1;
ALTER TABLE Salidas AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- Insertar categorías correctas
INSERT INTO Categoria (nombre) VALUES
('Calzado para Caballero'),
('Calzado para Dama'),
('Calzado Infantil');

-- Insertar subcategorías (marcas) correctas
INSERT INTO Subcategoria (nombre, id_categ) VALUES
-- Caballero
('Nike', 1),
('Adidas', 1),
('Puma', 1),
('Reebok', 1),
('Converse', 1),
('Vans', 1),
('Timberland', 1),
('Clarks', 1),
-- Dama
('Nike', 2),
('Adidas', 2),
('Puma', 2),
('Reebok', 2),
('Converse', 2),
('Vans', 2),
('Nine West', 2),
('Steve Madden', 2),
-- Infantil
('Nike', 3),
('Adidas', 3),
('Puma', 3),
('Converse', 3),
('Vans', 3),
('Sketchers', 3),
('Crocs', 3),
('Disney', 3);

-- La base queda lista para ingresar productos y ventas nuevos
SELECT 'Base de datos de tienda de calzado reiniciada correctamente' AS resultado;
