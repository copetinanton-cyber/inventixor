-- ========================================
-- INVENTIXOR - BASE DE DATOS COMPLETA
-- Script de recreación con todas las mejoras
-- Fecha: 2025-10-12
-- ========================================

-- Eliminar la base de datos si existe
DROP DATABASE IF EXISTS inventixor;

-- Crear la base de datos
CREATE DATABASE inventixor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventixor;

-- ========================================
-- 1. TABLAS BÁSICAS DEL SISTEMA
-- ========================================

-- Tabla Categoria
CREATE TABLE Categoria (
    id_categ INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla Subcategoria
CREATE TABLE Subcategoria (
    id_subcg INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    id_categ INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_categ) REFERENCES Categoria(id_categ) ON DELETE CASCADE,
    INDEX idx_categoria (id_categ)
);

-- Tabla Proveedores
CREATE TABLE Proveedores (
    id_nit INT AUTO_INCREMENT PRIMARY KEY,
    razon_social VARCHAR(100) NOT NULL,
    contacto VARCHAR(100),
    direccion VARCHAR(255),
    correo VARCHAR(100),
    telefono VARCHAR(20),
    estado VARCHAR(20) DEFAULT 'activo',
    detalles VARCHAR(255),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla Users
CREATE TABLE Users (
    num_doc BIGINT PRIMARY KEY,
    tipo_documento INT DEFAULT 1,
    apellidos VARCHAR(100) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    telefono BIGINT,
    correo VARCHAR(100),
    cargo VARCHAR(50),
    rol VARCHAR(20) DEFAULT 'empleado',
    contrasena VARCHAR(255) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla Productos
CREATE TABLE Productos (
    id_prod INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    modelo VARCHAR(100),
    talla VARCHAR(50),
    color VARCHAR(50),
    stock INT DEFAULT 0,
    stock_minimo INT DEFAULT 10,
    precio_unitario DECIMAL(10,2) DEFAULT 0.00,
    fecha_ing DATE DEFAULT (CURDATE()),
    material VARCHAR(100),
    descripcion TEXT,
    id_subcg INT NOT NULL,
    id_nit INT NOT NULL,
    num_doc BIGINT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_subcg) REFERENCES Subcategoria(id_subcg),
    FOREIGN KEY (id_nit) REFERENCES Proveedores(id_nit),
    FOREIGN KEY (num_doc) REFERENCES Users(num_doc),
    INDEX idx_nombre (nombre),
    INDEX idx_stock (stock),
    INDEX idx_subcategoria (id_subcg)
);

-- ========================================
-- 2. TABLAS DE ALERTAS Y NOTIFICACIONES
-- ========================================

-- Tabla Alertas
CREATE TABLE Alertas (
    id_alerta INT AUTO_INCREMENT PRIMARY KEY,
    tipo_alerta VARCHAR(100) NOT NULL,
    observacion VARCHAR(255),
    nivel_alerta VARCHAR(50) DEFAULT 'media',
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(20) DEFAULT 'pendiente',
    id_prod INT,
    resuelto_por BIGINT NULL,
    fecha_resolucion TIMESTAMP NULL,
    FOREIGN KEY (id_prod) REFERENCES Productos(id_prod),
    FOREIGN KEY (resuelto_por) REFERENCES Users(num_doc),
    INDEX idx_estado (estado),
    INDEX idx_tipo (tipo_alerta),
    INDEX idx_fecha (fecha_generacion)
);

-- Sistema de notificaciones automáticas
CREATE TABLE NotificacionesSistema (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    tipo_evento VARCHAR(50) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensaje TEXT NOT NULL,
    datos_evento JSON DEFAULT NULL,
    nivel_prioridad ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media',
    icono VARCHAR(50) DEFAULT 'fas fa-bell',
    color VARCHAR(20) DEFAULT 'info',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activa BOOLEAN DEFAULT TRUE,
    mostrar_hasta TIMESTAMP NULL,
    creado_por VARCHAR(100) DEFAULT 'sistema',
    INDEX idx_tipo_evento (tipo_evento),
    INDEX idx_fecha_creacion (fecha_creacion),
    INDEX idx_activa (activa)
);

-- Control de notificaciones vistas
CREATE TABLE NotificacionesVistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_notificacion INT NOT NULL,
    num_doc_usuario VARCHAR(20) NOT NULL,
    fecha_vista TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_usuario VARCHAR(45),
    FOREIGN KEY (id_notificacion) REFERENCES NotificacionesSistema(id_notificacion) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_notificacion (id_notificacion, num_doc_usuario)
);

-- ========================================
-- 3. SISTEMA AVANZADO DE SALIDAS
-- ========================================

-- Tipos de salida
CREATE TABLE TiposSalida (
    id_tipo INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    requiere_seguimiento BOOLEAN DEFAULT TRUE,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla Salidas mejorada
CREATE TABLE Salidas (
    id_salida INT AUTO_INCREMENT PRIMARY KEY,
    tipo_salida VARCHAR(100) NOT NULL,
    estado_salida VARCHAR(50) DEFAULT 'completada',
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_entrega DATETIME NULL,
    cantidad INT NOT NULL,
    precio_venta DECIMAL(10,2) DEFAULT 0.00,
    observacion VARCHAR(255),
    cliente_info JSON NULL,
    num_doc_usuario BIGINT NOT NULL,
    id_prod INT NOT NULL,
    FOREIGN KEY (id_prod) REFERENCES Productos(id_prod),
    FOREIGN KEY (num_doc_usuario) REFERENCES Users(num_doc),
    INDEX idx_fecha (fecha_hora),
    INDEX idx_tipo (tipo_salida),
    INDEX idx_producto (id_prod)
);

-- Seguimiento post-salida
CREATE TABLE ProductosSeguimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_salida INT NOT NULL,
    estado VARCHAR(50) NOT NULL,
    fecha_estado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observaciones TEXT,
    usuario VARCHAR(100) NOT NULL,
    ubicacion VARCHAR(255) NULL,
    FOREIGN KEY (id_salida) REFERENCES Salidas(id_salida) ON DELETE CASCADE,
    INDEX idx_salida (id_salida),
    INDEX idx_estado (estado)
);

-- Tabla de devoluciones mejorada
CREATE TABLE Devoluciones (
    id_devolucion INT AUTO_INCREMENT PRIMARY KEY,
    id_salida INT NOT NULL,
    id_prod INT NOT NULL,
    cantidad_devuelta INT NOT NULL,
    motivo VARCHAR(100) NOT NULL,
    condicion_producto VARCHAR(50) NOT NULL,
    accion VARCHAR(50) NOT NULL,
    fecha_devolucion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observaciones TEXT,
    usuario_recibe VARCHAR(100) NOT NULL,
    reingresado_stock BOOLEAN DEFAULT FALSE,
    monto_devuelto DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (id_salida) REFERENCES Salidas(id_salida),
    FOREIGN KEY (id_prod) REFERENCES Productos(id_prod),
    INDEX idx_salida (id_salida),
    INDEX idx_producto (id_prod),
    INDEX idx_motivo (motivo)
);

-- Tabla de garantías
CREATE TABLE Garantias (
    id_garantia INT AUTO_INCREMENT PRIMARY KEY,
    id_salida INT NOT NULL,
    id_prod INT NOT NULL,
    tipo_garantia VARCHAR(50) NOT NULL,
    duracion_meses INT DEFAULT 12,
    fecha_inicio DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    estado VARCHAR(30) DEFAULT 'activa',
    terminos TEXT,
    FOREIGN KEY (id_salida) REFERENCES Salidas(id_salida),
    FOREIGN KEY (id_prod) REFERENCES Productos(id_prod),
    INDEX idx_producto (id_prod),
    INDEX idx_estado (estado),
    INDEX idx_vencimiento (fecha_vencimiento)
);

-- Productos en tránsito
CREATE TABLE ProductosTransito (
    id_transito INT AUTO_INCREMENT PRIMARY KEY,
    id_salida INT NOT NULL,
    id_prod INT NOT NULL,
    destino VARCHAR(255) NOT NULL,
    fecha_envio DATETIME,
    fecha_entrega_estimada DATETIME,
    fecha_entrega_real DATETIME NULL,
    estado VARCHAR(50) DEFAULT 'preparando',
    transportista VARCHAR(100),
    numero_guia VARCHAR(100),
    observaciones TEXT,
    FOREIGN KEY (id_salida) REFERENCES Salidas(id_salida),
    FOREIGN KEY (id_prod) REFERENCES Productos(id_prod),
    INDEX idx_salida (id_salida),
    INDEX idx_estado (estado)
);

-- ========================================
-- 4. HISTORIAL Y AUDITORÍA
-- ========================================

-- Historial de movimientos
CREATE TABLE HistorialMovimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_prod INT NOT NULL,
    tipo_movimiento VARCHAR(50) NOT NULL,
    cantidad INT DEFAULT NULL,
    stock_anterior INT DEFAULT NULL,
    stock_nuevo INT DEFAULT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario VARCHAR(100) NOT NULL,
    observaciones TEXT,
    FOREIGN KEY (id_prod) REFERENCES Productos(id_prod),
    INDEX idx_producto (id_prod),
    INDEX idx_fecha (fecha),
    INDEX idx_tipo (tipo_movimiento)
);

-- Historial CRUD
CREATE TABLE HistorialCRUD (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entidad VARCHAR(50) NOT NULL,
    id_entidad INT NOT NULL,
    accion VARCHAR(20) NOT NULL,
    datos_anteriores JSON NULL,
    datos_nuevos JSON NULL,
    usuario VARCHAR(100) NOT NULL,
    rol VARCHAR(50) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    detalles TEXT,
    INDEX idx_entidad (entidad, id_entidad),
    INDEX idx_usuario (usuario),
    INDEX idx_fecha (fecha)
);

-- ========================================
-- 5. REPORTES Y AUTORIZACIONES
-- ========================================

-- Tabla Reportes
CREATE TABLE Reportes (
    id_repor INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(255),
    tipo_reporte VARCHAR(50) NOT NULL,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    parametros JSON NULL,
    resultado_datos JSON NULL,
    num_doc BIGINT NOT NULL,
    FOREIGN KEY (num_doc) REFERENCES Users(num_doc),
    INDEX idx_tipo (tipo_reporte),
    INDEX idx_fecha (fecha_hora)
);

-- Tabla Autorizaciones
CREATE TABLE Autorizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modulo VARCHAR(50) NOT NULL,
    id_registro INT NOT NULL,
    usuario_solicita BIGINT NOT NULL,
    usuario_autoriza BIGINT,
    estado VARCHAR(20) DEFAULT 'pendiente',
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta TIMESTAMP NULL,
    comentario VARCHAR(255),
    datos_cambio JSON NULL,
    FOREIGN KEY (usuario_solicita) REFERENCES Users(num_doc),
    FOREIGN KEY (usuario_autoriza) REFERENCES Users(num_doc),
    INDEX idx_estado (estado),
    INDEX idx_modulo (modulo)
);

-- ========================================
-- 6. INSERTAR DATOS INICIALES
-- ========================================

-- Usuarios del sistema
INSERT INTO Users (num_doc, tipo_documento, apellidos, nombres, telefono, correo, cargo, rol, contrasena) VALUES
(1000000001, 1, 'Administrador', 'Sistema', 3001234567, 'admin@inventixor.com', 'Administrador General', 'admin', '$2y$10$VPQK.fORLHKi7Kyj4ePz/.AX97fol5Kxp2IumfmuNZo/18n098OVS'),
(1002, 1, 'Coordinador', 'Inventario', 3002223344, 'coord@inventixor.com', 'Coordinador de Inventario', 'coordinador', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(1003, 1, 'García', 'María', 3003334455, 'maria@inventixor.com', 'Vendedor', 'empleado', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Categorías
INSERT INTO Categoria (nombre, descripcion) VALUES
('Calzado Deportivo', 'Zapatos para actividades deportivas y ejercicio'),
('Calzado Casual', 'Zapatos para uso diario y ocasiones informales'),
('Calzado Formal', 'Zapatos para eventos formales y oficina'),
('Botas', 'Calzado alto que cubre el tobillo'),
('Sandalias', 'Calzado abierto para clima cálido');

-- Subcategorías
INSERT INTO Subcategoria (nombre, descripcion, id_categ) VALUES
('Running', 'Zapatos para correr y trotar', 1),
('Fútbol', 'Zapatos para fútbol y deportes de campo', 1),
('Basketball', 'Zapatos para baloncesto', 1),
('Tenis Casual', 'Tenis para uso diario', 2),
('Mocasines', 'Zapatos sin cordones casuales', 2),
('Zapatos de Vestir', 'Zapatos formales para hombres', 3),
('Tacones', 'Zapatos formales para mujeres', 3),
('Botas de Trabajo', 'Botas para trabajo pesado', 4),
('Botas de Moda', 'Botas casuales y de moda', 4),
('Sandalias Planas', 'Sandalias sin tacón', 5),
('Sandalias con Tacón', 'Sandalias con elevación', 5);

-- Proveedores
INSERT INTO Proveedores (razon_social, contacto, direccion, correo, telefono, estado) VALUES
('Nike Colombia S.A.S.', 'Juan Pérez', 'Calle 100 #15-20, Bogotá', 'ventas@nike.com.co', '3101234567', 'activo'),
('Adidas Colombia', 'Ana López', 'Carrera 7 #32-16, Medellín', 'colombia@adidas.com', '3207654321', 'activo'),
('Bata Colombia', 'Carlos Ruiz', 'Av. El Dorado #68-90, Bogotá', 'info@bata.com.co', '3156789012', 'activo'),
('Calzado Nacional', 'María González', 'Calle 53 #45-67, Cali', 'ventas@calzadonacional.co', '3189876543', 'activo');

-- Tipos de salida
INSERT INTO TiposSalida (codigo, nombre, descripcion, requiere_seguimiento) VALUES
('venta', 'Venta Regular', 'Venta directa al cliente final', TRUE),
('venta_mayoreo', 'Venta por Mayoreo', 'Venta en grandes cantidades', TRUE),
('devolucion_proveedor', 'Devolución a Proveedor', 'Producto devuelto al proveedor', TRUE),
('producto_dañado', 'Producto Dañado', 'Producto dañado que debe ser descartado', FALSE),
('perdida', 'Pérdida/Robo', 'Producto perdido o robado', FALSE),
('uso_interno', 'Uso Interno', 'Producto para uso interno de la empresa', FALSE),
('prestamo', 'Préstamo', 'Producto prestado temporalmente', TRUE),
('muestra_gratuita', 'Muestra Gratuita', 'Producto entregado como promoción', FALSE),
('transferencia', 'Transferencia', 'Transferencia a otra ubicación', TRUE),
('garantia', 'Salida por Garantía', 'Producto enviado para reparación', TRUE);

-- Productos de ejemplo
INSERT INTO Productos (nombre, modelo, talla, color, stock, stock_minimo, precio_unitario, material, descripcion, id_subcg, id_nit, num_doc) VALUES
('Nike Air Max', 'AM-2024', '42', 'Negro', 25, 5, 350000.00, 'Sintético y malla', 'Zapatos deportivos para running', 1, 1, 1000000001),
('Adidas Ultraboost', 'UB-22', '41', 'Blanco', 15, 3, 420000.00, 'Primeknit y Boost', 'Zapatos de alto rendimiento', 1, 2, 1000000001),
('Bata Mocasín', 'MOC-100', '40', 'Café', 30, 8, 180000.00, 'Cuero genuino', 'Mocasines casuales para hombres', 5, 3, 1002),
('Sandalias Verano', 'SV-2024', '38', 'Rosa', 20, 5, 85000.00, 'Sintético', 'Sandalias cómodas para verano', 10, 4, 1002),
('Botas de Trabajo', 'BT-500', '43', 'Negro', 12, 3, 250000.00, 'Cuero reforzado', 'Botas industriales con punta de acero', 8, 3, 1000000001);

-- ========================================
-- 7. VISTAS ÚTILES
-- ========================================

-- Vista completa de productos con información relacionada
CREATE VIEW vista_productos_completa AS
SELECT 
    p.id_prod,
    p.nombre,
    p.modelo,
    p.talla,
    p.color,
    p.stock,
    p.stock_minimo,
    p.precio_unitario,
    p.fecha_ing,
    p.material,
    p.descripcion,
    p.activo,
    sc.nombre as subcategoria,
    c.nombre as categoria,
    pr.razon_social as proveedor,
    u.nombres as creado_por,
    CASE 
        WHEN p.stock <= 0 THEN 'Sin Stock'
        WHEN p.stock <= p.stock_minimo THEN 'Stock Bajo'
        WHEN p.stock <= (p.stock_minimo * 2) THEN 'Stock Moderado'
        ELSE 'Stock Suficiente'
    END as estado_stock
FROM Productos p
INNER JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
INNER JOIN Categoria c ON sc.id_categ = c.id_categ
INNER JOIN Proveedores pr ON p.id_nit = pr.id_nit
INNER JOIN Users u ON p.num_doc = u.num_doc;

-- Vista de salidas completa
CREATE VIEW vista_salidas_completa AS
SELECT 
    s.id_salida,
    s.tipo_salida,
    s.estado_salida,
    s.cantidad,
    s.precio_venta,
    s.fecha_hora as fecha_salida,
    s.fecha_entrega,
    s.observacion,
    p.id_prod,
    p.nombre as producto_nombre,
    p.modelo,
    p.talla,
    p.color,
    c.nombre as categoria,
    sc.nombre as subcategoria,
    pr.razon_social as proveedor,
    u.nombres as usuario_registra,
    ps.estado as estado_seguimiento,
    ps.fecha_estado as ultima_actualizacion,
    CASE 
        WHEN g.estado = 'activa' AND g.fecha_vencimiento > CURDATE() THEN 'Con Garantía'
        WHEN g.estado = 'activa' AND g.fecha_vencimiento <= CURDATE() THEN 'Garantía Vencida'
        ELSE 'Sin Garantía'
    END as estado_garantia
FROM Salidas s
INNER JOIN Productos p ON s.id_prod = p.id_prod
INNER JOIN Users u ON s.num_doc_usuario = u.num_doc
LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
LEFT JOIN Categoria c ON sc.id_categ = c.id_categ
LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit
LEFT JOIN ProductosSeguimiento ps ON s.id_salida = ps.id_salida AND ps.id = (
    SELECT MAX(ps2.id) FROM ProductosSeguimiento ps2 WHERE ps2.id_salida = s.id_salida
)
LEFT JOIN Garantias g ON s.id_salida = g.id_salida;

-- ========================================
-- 8. TRIGGERS DE AUTOMATIZACIÓN
-- ========================================

DELIMITER //

-- Trigger para historial de productos
CREATE TRIGGER productos_historial_insert 
AFTER INSERT ON Productos
FOR EACH ROW
BEGIN
    INSERT INTO HistorialMovimientos (id_prod, tipo_movimiento, cantidad, stock_nuevo, usuario, observaciones)
    VALUES (NEW.id_prod, 'alta_producto', NEW.stock, NEW.stock, 'SISTEMA', CONCAT('Producto registrado: ', NEW.nombre));
END //

-- Trigger para historial de productos en actualización
CREATE TRIGGER productos_historial_update 
AFTER UPDATE ON Productos
FOR EACH ROW
BEGIN
    IF OLD.stock != NEW.stock THEN
        INSERT INTO HistorialMovimientos (id_prod, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, usuario, observaciones)
        VALUES (NEW.id_prod, 'ajuste_stock', (NEW.stock - OLD.stock), OLD.stock, NEW.stock, 'SISTEMA', 'Ajuste de inventario');
    END IF;
END //

-- Trigger para crear seguimiento automático en salidas
CREATE TRIGGER salidas_seguimiento_insert 
AFTER INSERT ON Salidas
FOR EACH ROW
BEGIN
    DECLARE estado_inicial VARCHAR(50);
    
    CASE NEW.tipo_salida
        WHEN 'venta' THEN SET estado_inicial = 'en_transito';
        WHEN 'prestamo' THEN SET estado_inicial = 'prestado';
        WHEN 'transferencia' THEN SET estado_inicial = 'en_transito';
        WHEN 'devolucion_proveedor' THEN SET estado_inicial = 'devuelto';
        ELSE SET estado_inicial = 'completado';
    END CASE;
    
    INSERT INTO ProductosSeguimiento (id_salida, estado, observaciones, usuario)
    VALUES (NEW.id_salida, estado_inicial, CONCAT('Salida registrada: ', NEW.tipo_salida), 'SISTEMA');
    
    -- Actualizar stock del producto
    UPDATE Productos 
    SET stock = stock - NEW.cantidad 
    WHERE id_prod = NEW.id_prod;
    
    -- Registrar movimiento
    INSERT INTO HistorialMovimientos (id_prod, tipo_movimiento, cantidad, usuario, observaciones)
    VALUES (NEW.id_prod, 'salida', NEW.cantidad, 'SISTEMA', CONCAT('Salida tipo: ', NEW.tipo_salida));
END //

-- Trigger para devoluciones
CREATE TRIGGER devoluciones_stock_update 
AFTER INSERT ON Devoluciones
FOR EACH ROW
BEGIN
    IF NEW.accion = 'reingresar_inventario' THEN
        UPDATE Productos 
        SET stock = stock + NEW.cantidad_devuelta 
        WHERE id_prod = NEW.id_prod;
        
        INSERT INTO HistorialMovimientos (id_prod, tipo_movimiento, cantidad, usuario, observaciones)
        VALUES (NEW.id_prod, 'devolucion', NEW.cantidad_devuelta, NEW.usuario_recibe, 
                CONCAT('Devolución - Motivo: ', NEW.motivo));
                
        UPDATE Devoluciones 
        SET reingresado_stock = TRUE 
        WHERE id_devolucion = NEW.id_devolucion;
    END IF;
END //

DELIMITER ;

-- ========================================
-- 9. CONFIGURACIÓN INICIAL
-- ========================================

-- Notificación de bienvenida
INSERT INTO NotificacionesSistema (tipo_evento, titulo, mensaje, nivel_prioridad, color, creado_por) VALUES
('sistema_iniciado', 
 '¡Sistema InventiXor Actualizado!', 
 'La base de datos ha sido recreada con éxito. Todas las mejoras del sistema de salidas, devoluciones y notificaciones están activas.', 
 'alta', 
 'success', 
 'sistema');

-- Mensaje de finalización
SELECT 
    'Base de datos InventiXor creada exitosamente' as mensaje,
    COUNT(*) as usuarios_creados
FROM Users
UNION ALL
SELECT 
    'Productos de ejemplo registrados' as mensaje,
    COUNT(*) as total
FROM Productos
UNION ALL
SELECT 
    'Sistema listo para usar' as mensaje,
    1 as estado;

-- ========================================
-- FIN DEL SCRIPT
-- ========================================