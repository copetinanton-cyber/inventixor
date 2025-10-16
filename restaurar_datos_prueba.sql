-- ========================================
-- RESTAURACIÓN DE DATOS DE PRUEBA - INVENTIXOR
-- Script para restaurar datos perdidos después de actualización
-- Fecha: 2025-10-12
-- ========================================

USE inventixor;

-- ========================================
-- 1. INSERTAR CATEGORÍAS DE PRUEBA
-- ========================================

INSERT IGNORE INTO Categoria (id_categ, nombre) VALUES
(1, 'Calzado'),
(2, 'Ropa'),
(3, 'Electrónicos'),
(4, 'Accesorios'),
(5, 'Deportes'),
(6, 'Hogar'),
(7, 'Oficina'),
(8, 'Automotriz'),
(9, 'Belleza'),
(10, 'Herramientas');

-- ========================================
-- 2. INSERTAR SUBCATEGORÍAS DE PRUEBA
-- ========================================

INSERT IGNORE INTO Subcategoria (id_subcg, nombre, id_categ) VALUES
-- Calzado
(1, 'Tenis Deportivos', 1),
(2, 'Zapatos Casuales', 1),
(3, 'Botas', 1),
(4, 'Sandalias', 1),

-- Ropa
(5, 'Camisas', 2),
(6, 'Pantalones', 2),
(7, 'Vestidos', 2),
(8, 'Chaquetas', 2),

-- Electrónicos
(9, 'Smartphones', 3),
(10, 'Laptops', 3),
(11, 'Audífonos', 3),
(12, 'Tablets', 3),

-- Accesorios
(13, 'Relojes', 4),
(14, 'Gafas', 4),
(15, 'Bolsos', 4),
(16, 'Billeteras', 4),

-- Deportes
(17, 'Balones', 5),
(18, 'Raquetas', 5),
(19, 'Pesas', 5),
(20, 'Bicicletas', 5),

-- Hogar
(21, 'Muebles', 6),
(22, 'Decoración', 6),
(23, 'Cocina', 6),
(24, 'Baño', 6),

-- Oficina
(25, 'Papelería', 7),
(26, 'Computadoras', 7),
(27, 'Sillas', 7),
(28, 'Archivadores', 7),

-- Automotriz
(29, 'Repuestos', 8),
(30, 'Aceites', 8),
(31, 'Llantas', 8),
(32, 'Herramientas Auto', 8),

-- Belleza
(33, 'Maquillaje', 9),
(34, 'Cuidado de Piel', 9),
(35, 'Perfumes', 9),
(36, 'Cabello', 9),

-- Herramientas
(37, 'Herramientas Eléctricas', 10),
(38, 'Herramientas Manuales', 10),
(39, 'Jardín', 10),
(40, 'Construcción', 10);

-- ========================================
-- 3. INSERTAR PROVEEDORES DE PRUEBA
-- ========================================

INSERT IGNORE INTO Proveedores (id_nit, razon_social, telefono, email, direccion) VALUES
(900123456, 'Nike Colombia S.A.S.', 3001234567, 'ventas@nike.co', 'Calle 100 #15-20, Bogotá'),
(900234567, 'Adidas Sports Colombia', 3002345678, 'info@adidas.co', 'Carrera 7 #75-45, Bogotá'),
(900345678, 'Samsung Electronics', 3003456789, 'colombia@samsung.com', 'Zona Franca Bogotá'),
(900456789, 'Apple Colombia', 3004567890, 'soporte@apple.co', 'Centro Comercial Andino'),
(900567890, 'Grupo Éxito', 3005678901, 'proveedores@exito.com', 'Autopista Norte Km 5'),
(900678901, 'Falabella Colombia', 3006789012, 'compras@falabella.co', 'Calle 116 #7-15'),
(900789012, 'Homecenter Sodimac', 3007890123, 'proveedores@homecenter.co', 'Av. 68 #75-50'),
(900890123, 'Tecnoquímicas S.A.', 3008901234, 'ventas@tecnoquimicas.com', 'Calle 23 #57-40 Cali'),
(900901234, 'Cervecería Bavaria', 3009012345, 'distribuidores@bavaria.co', 'Carrera 53A #127-35'),
(901012345, 'Unilever Colombia', 3001023456, 'contacto@unilever.co', 'Calle 100 #8A-55');

-- ========================================
-- 4. INSERTAR PRODUCTOS DE PRUEBA
-- ========================================

INSERT IGNORE INTO Productos (id_prod, nombre, modelo, talla, color, stock, stock_minimo, precio_unitario, fecha_ing, material, activo, id_subcg, id_nit, num_doc) VALUES
-- Calzado - Tenis Deportivos
(1, 'Nike Air Max 270', 'AM270-2023', '42', 'Negro', 25, 5, 350000.00, NOW(), 'Cuero sintético y malla', 1, 1, 900123456, 1000000001),
(2, 'Nike Air Max 270', 'AM270-2023', '40', 'Blanco', 20, 5, 350000.00, NOW(), 'Cuero sintético y malla', 1, 1, 900123456, 1000000001),
(3, 'Adidas Ultra Boost', 'UB22-PRO', '41', 'Azul', 15, 3, 420000.00, NOW(), 'Tejido Primeknit', 1, 1, 900234567, 1000000001),
(4, 'Adidas Ultra Boost', 'UB22-PRO', '43', 'Gris', 18, 3, 420000.00, NOW(), 'Tejido Primeknit', 1, 1, 900234567, 1000000001),

-- Calzado - Zapatos Casuales
(5, 'Clarks Desert Boot', 'CDB-Classic', '42', 'Café', 12, 2, 280000.00, NOW(), 'Cuero genuino', 1, 2, 900567890, 1000000001),
(6, 'Converse Chuck Taylor', 'CT-All Star', '39', 'Rojo', 30, 5, 180000.00, NOW(), 'Lona', 1, 2, 900567890, 1000000001),

-- Electrónicos - Smartphones
(7, 'Samsung Galaxy S23', 'SM-S911B', 'Único', 'Negro', 8, 2, 2800000.00, NOW(), 'Aluminio y vidrio', 1, 9, 900345678, 1000000001),
(8, 'Samsung Galaxy S23', 'SM-S911B', 'Único', 'Violeta', 6, 2, 2800000.00, NOW(), 'Aluminio y vidrio', 1, 9, 900345678, 1000000001),
(9, 'iPhone 14 Pro', 'A2890', '128GB', 'Azul', 5, 1, 4200000.00, NOW(), 'Titanio', 1, 9, 900456789, 1000000001),
(10, 'iPhone 14 Pro', 'A2890', '256GB', 'Dorado', 4, 1, 4800000.00, NOW(), 'Titanio', 1, 9, 900456789, 1000000001),

-- Electrónicos - Audífonos
(11, 'AirPods Pro 2', 'MTJV3AM/A', 'Único', 'Blanco', 20, 3, 950000.00, NOW(), 'Plástico premium', 1, 11, 900456789, 1000000001),
(12, 'Samsung Galaxy Buds2 Pro', 'SM-R510N', 'Único', 'Negro', 15, 3, 680000.00, NOW(), 'Plástico y silicona', 1, 11, 900345678, 1000000001),
(13, 'Sony WH-1000XM4', 'WH1000XM4/B', 'Único', 'Negro', 10, 2, 1200000.00, NOW(), 'Plástico y cuero sintético', 1, 11, 900678901, 1000000001),

-- Ropa - Camisas
(14, 'Camisa Polo Ralph Lauren', 'RL-POLO-M', 'M', 'Azul Marino', 25, 5, 180000.00, NOW(), 'Algodón piqué', 1, 5, 900678901, 1000000001),
(15, 'Camisa Polo Ralph Lauren', 'RL-POLO-L', 'L', 'Blanco', 20, 5, 180000.00, NOW(), 'Algodón piqué', 1, 5, 900678901, 1000000001),
(16, 'Camiseta Nike Dri-FIT', 'NK-DRI-FIT', 'XL', 'Negro', 35, 8, 85000.00, NOW(), 'Poliéster técnico', 1, 5, 900123456, 1000000001),

-- Ropa - Pantalones
(17, 'Jeans Levi\'s 501', 'L501-Original', '32x32', 'Azul clásico', 22, 4, 250000.00, NOW(), 'Denim 100% algodón', 1, 6, 900567890, 1000000001),
(18, 'Jeans Levi\'s 511', 'L511-Slim', '30x30', 'Negro', 18, 4, 280000.00, NOW(), 'Denim con elastano', 1, 6, 900567890, 1000000001),

-- Accesorios - Relojes
(19, 'Apple Watch Series 8', 'MNP73LL/A', '45mm', 'Medianoche', 12, 2, 1800000.00, NOW(), 'Aluminio', 1, 13, 900456789, 1000000001),
(20, 'Samsung Galaxy Watch 5', 'SM-R900N', '44mm', 'Plata', 10, 2, 1200000.00, NOW(), 'Aluminio', 1, 13, 900345678, 1000000001),

-- Accesorios - Gafas
(21, 'Ray-Ban Aviator Classic', 'RB3025', 'Único', 'Dorado', 15, 3, 420000.00, NOW(), 'Metal y cristal', 1, 14, 900678901, 1000000001),
(22, 'Oakley Holbrook', 'OO9102', 'Único', 'Negro mate', 12, 2, 380000.00, NOW(), 'Acetato', 1, 14, 900678901, 1000000001),

-- Hogar - Cocina
(23, 'Licuadora Oster Clásica', 'BLST-465', '1.25L', 'Blanco', 20, 4, 150000.00, NOW(), 'Vidrio y plástico', 1, 23, 900789012, 1000000001),
(24, 'Microondas Samsung', 'MS28J5255UB', '28L', 'Negro', 8, 2, 450000.00, NOW(), 'Acero inoxidable', 1, 23, 900345678, 1000000001),
(25, 'Cafetera Nespresso', 'C45-MX-BK-NE', 'Único', 'Negro', 12, 2, 320000.00, NOW(), 'Plástico ABS', 1, 23, 900678901, 1000000001),

-- Deportes - Balones
(26, 'Balón Nike Premier League', 'NK-PL-2023', 'Talla 5', 'Blanco/Azul', 30, 6, 120000.00, NOW(), 'Cuero sintético', 1, 17, 900123456, 1000000001),
(27, 'Balón Adidas Champions', 'AD-UCL-23', 'Talla 5', 'Blanco/Dorado', 25, 5, 140000.00, NOW(), 'TPU', 1, 17, 900234567, 1000000001),

-- Belleza - Perfumes
(28, 'Hugo Boss Bottled', 'HB-100ML', '100ml', 'Transparente', 18, 3, 280000.00, NOW(), 'Vidrio', 1, 35, 900890123, 1000000001),
(29, 'Chanel No. 5', 'CH5-EDP-100', '100ml', 'Transparente', 8, 2, 850000.00, NOW(), 'Vidrio', 1, 35, 901012345, 1000000001),

-- Herramientas - Herramientas Eléctricas
(30, 'Taladro Black & Decker', 'BD-CD121', '12V', 'Negro/Naranja', 15, 3, 180000.00, NOW(), 'Plástico reforzado', 1, 37, 900789012, 1000000001);

-- ========================================
-- 5. INSERTAR ALGUNAS SALIDAS DE PRUEBA
-- ========================================

INSERT IGNORE INTO Salidas (id_salida, id_prod, cantidad, tipo_salida, estado_salida, observacion, fecha_hora, num_doc_usuario) VALUES
(1, 1, 2, 'venta', 'completada', 'Venta a cliente regular - Pago en efectivo', '2024-10-10 10:30:00', 1000000001),
(2, 7, 1, 'venta', 'completada', 'Venta de smartphone Samsung Galaxy S23', '2024-10-10 14:20:00', 1000000001),
(3, 11, 1, 'venta', 'completada', 'AirPods Pro vendidos con garantía extendida', '2024-10-11 09:15:00', 1000000001),
(4, 14, 3, 'venta', 'completada', 'Venta corporativa - 3 camisas polo', '2024-10-11 16:45:00', 1000000001),
(5, 26, 2, 'venta', 'completada', 'Balones para equipo local de fútbol', '2024-10-12 11:30:00', 1000000001),
(6, 3, 1, 'producto_dañado', 'completada', 'Tenis con defecto de fábrica - No apto para venta', '2024-10-12 08:20:00', 1000000001),
(7, 23, 1, 'uso_interno', 'completada', 'Licuadora para uso en cafetería de la tienda', '2024-10-12 13:10:00', 1000000001);

-- Actualizar stock de productos después de las salidas
UPDATE Productos SET stock = stock - 2 WHERE id_prod = 1;   -- Nike Air Max 270 Negro: 25-2=23
UPDATE Productos SET stock = stock - 1 WHERE id_prod = 7;   -- Samsung Galaxy S23 Negro: 8-1=7
UPDATE Productos SET stock = stock - 1 WHERE id_prod = 11;  -- AirPods Pro: 20-1=19
UPDATE Productos SET stock = stock - 3 WHERE id_prod = 14;  -- Camisa Polo M Azul: 25-3=22
UPDATE Productos SET stock = stock - 2 WHERE id_prod = 26;  -- Balón Nike: 30-2=28
UPDATE Productos SET stock = stock - 1 WHERE id_prod = 3;   -- Adidas Ultra Boost Azul: 15-1=14
UPDATE Productos SET stock = stock - 1 WHERE id_prod = 23;  -- Licuadora Oster: 20-1=19

-- ========================================
-- 6. INSERTAR DATOS EN NUEVAS TABLAS
-- ========================================

-- Insertar seguimiento para las salidas
INSERT IGNORE INTO ProductosSeguimiento (id_salida, estado, observaciones, usuario, fecha_estado) VALUES
(1, 'entregado', 'Cliente satisfecho con la compra', 'SISTEMA', '2024-10-10 11:00:00'),
(2, 'entregado', 'Smartphone entregado con accesorios completos', 'SISTEMA', '2024-10-10 15:00:00'),
(3, 'entregado', 'AirPods configurados y probados con el cliente', 'SISTEMA', '2024-10-11 09:45:00'),
(4, 'entregado', 'Entrega corporativa completada exitosamente', 'SISTEMA', '2024-10-11 17:15:00'),
(5, 'entregado', 'Balones entregados al entrenador del equipo', 'SISTEMA', '2024-10-12 12:00:00'),
(6, 'completado', 'Producto retirado del inventario por defecto', 'SISTEMA', '2024-10-12 08:30:00'),
(7, 'en_uso', 'Licuadora instalada en área de cafetería', 'SISTEMA', '2024-10-12 13:30:00');

-- Insertar algunas garantías
INSERT IGNORE INTO Garantias (id_salida, id_prod, tipo_garantia, duracion_meses, fecha_inicio, fecha_vencimiento, estado, terminos) VALUES
(2, 7, 'fabricante', 24, '2024-10-10', '2026-10-10', 'activa', 'Garantía Samsung 2 años - Cubre defectos de fabricación'),
(3, 11, 'extendida', 36, '2024-10-11', '2027-10-11', 'activa', 'Garantía Apple extendida 3 años - Incluye soporte técnico');

-- Insertar historial de movimientos
INSERT IGNORE INTO HistorialMovimientos (id_prod, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, usuario, observaciones, fecha) VALUES
(1, 'salida', 2, 25, 23, 'SISTEMA', 'Venta regular - Cliente satisfecho', '2024-10-10 10:30:00'),
(7, 'salida', 1, 8, 7, 'SISTEMA', 'Venta de smartphone con garantía', '2024-10-10 14:20:00'),
(11, 'salida', 1, 20, 19, 'SISTEMA', 'Venta de AirPods Pro con garantía extendida', '2024-10-11 09:15:00'),
(14, 'salida', 3, 25, 22, 'SISTEMA', 'Venta corporativa múltiple', '2024-10-11 16:45:00'),
(26, 'salida', 2, 30, 28, 'SISTEMA', 'Venta deportiva - Equipo de fútbol', '2024-10-12 11:30:00'),
(3, 'salida', 1, 15, 14, 'SISTEMA', 'Retiro por defecto de fábrica', '2024-10-12 08:20:00'),
(23, 'salida', 1, 20, 19, 'SISTEMA', 'Traslado a uso interno de la empresa', '2024-10-12 13:10:00');

-- ========================================
-- 7. INSERTAR NOTIFICACIONES DE BIENVENIDA
-- ========================================

INSERT IGNORE INTO NotificacionesSistema (tipo_evento, titulo, mensaje, datos_evento, nivel_prioridad, color, fecha_creacion, creado_por) VALUES
('bienvenida_sistema', 
 '¡Bienvenido a InventiXor Mejorado!', 
 'Los datos de prueba han sido restaurados exitosamente. El sistema incluye ahora gestión avanzada de devoluciones, seguimiento de productos y mucho más.', 
 '{"version": "2.0", "nuevas_funciones": ["devoluciones", "seguimiento", "garantias", "historial"]}',
 'alta', 
 'success', 
 NOW(), 
 'sistema'),

('datos_restaurados',
 'Datos de Prueba Restaurados',
 'Se han insertado 30 productos, 10 proveedores, 40 subcategorías y 7 salidas de ejemplo para que puedas probar todas las funcionalidades del sistema.',
 '{"productos": 30, "proveedores": 10, "categorias": 10, "subcategorias": 40, "salidas": 7}',
 'media',
 'info',
 NOW(),
 'sistema');

-- ========================================
-- 8. MENSAJE DE FINALIZACIÓN
-- ========================================

SELECT 
    'Datos de prueba restaurados exitosamente' as estado,
    COUNT(DISTINCT p.id_prod) as productos_restaurados,
    COUNT(DISTINCT pr.id_nit) as proveedores_restaurados,
    COUNT(DISTINCT c.id_categ) as categorias_restauradas,
    COUNT(DISTINCT sc.id_subcg) as subcategorias_restauradas,
    COUNT(DISTINCT s.id_salida) as salidas_ejemplo
FROM Productos p
LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit
LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg  
LEFT JOIN Categoria c ON sc.id_categ = c.id_categ
LEFT JOIN Salidas s ON p.id_prod = s.id_prod;

SELECT 'Acceso al sistema:' as instruccion, 'http://localhost/inventixor/index.php' as url_login;
SELECT 'Usuario administrador:' as tipo_usuario, '1000000001' as num_doc, 'tu_contraseña_actual' as password;

-- ========================================
-- FIN DE LA RESTAURACIÓN
-- ========================================