-- ========================================
-- ACTUALIZACIÓN INVENTIXOR - SOLO MEJORAS
-- Script para agregar mejoras sin perder datos
-- Fecha: 2025-10-12
-- ========================================

USE inventixor;

-- ========================================
-- 1. VERIFICAR Y AGREGAR COLUMNAS FALTANTES
-- ========================================

-- Agregar columnas a la tabla Salidas si no existen
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_NAME = 'Salidas' 
     AND COLUMN_NAME = 'estado_salida' 
     AND TABLE_SCHEMA = DATABASE()) = 0,
    'ALTER TABLE Salidas ADD COLUMN estado_salida VARCHAR(50) DEFAULT "completada" AFTER tipo_salida',
    'SELECT "Columna estado_salida ya existe" as mensaje'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_NAME = 'Salidas' 
     AND COLUMN_NAME = 'fecha_entrega' 
     AND TABLE_SCHEMA = DATABASE()) = 0,
    'ALTER TABLE Salidas ADD COLUMN fecha_entrega DATETIME NULL AFTER fecha_hora',
    'SELECT "Columna fecha_entrega ya existe" as mensaje'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_NAME = 'Salidas' 
     AND COLUMN_NAME = 'cliente_info' 
     AND TABLE_SCHEMA = DATABASE()) = 0,
    'ALTER TABLE Salidas ADD COLUMN cliente_info JSON NULL AFTER observacion',
    'SELECT "Columna cliente_info ya existe" as mensaje'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_NAME = 'Salidas' 
     AND COLUMN_NAME = 'num_doc_usuario' 
     AND TABLE_SCHEMA = DATABASE()) = 0,
    'ALTER TABLE Salidas ADD COLUMN num_doc_usuario BIGINT AFTER cliente_info',
    'SELECT "Columna num_doc_usuario ya existe" as mensaje'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Actualizar num_doc_usuario con el valor por defecto del administrador
UPDATE Salidas SET num_doc_usuario = 1000000001 WHERE num_doc_usuario IS NULL;

-- ========================================
-- 2. CREAR TABLAS NUEVAS SI NO EXISTEN
-- ========================================

-- Tipos de salida
CREATE TABLE IF NOT EXISTS TiposSalida (
    id_tipo INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    requiere_seguimiento BOOLEAN DEFAULT TRUE,
    activo BOOLEAN DEFAULT TRUE
);

-- Seguimiento post-salida
CREATE TABLE IF NOT EXISTS ProductosSeguimiento (
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

-- Devoluciones mejoradas
CREATE TABLE IF NOT EXISTS Devoluciones (
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

-- Garantías
CREATE TABLE IF NOT EXISTS Garantias (
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
CREATE TABLE IF NOT EXISTS ProductosTransito (
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

-- Historial de movimientos
CREATE TABLE IF NOT EXISTS HistorialMovimientos (
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

-- Sistema de notificaciones
CREATE TABLE IF NOT EXISTS NotificacionesSistema (
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
CREATE TABLE IF NOT EXISTS NotificacionesVistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_notificacion INT NOT NULL,
    num_doc_usuario VARCHAR(20) NOT NULL,
    fecha_vista TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_usuario VARCHAR(45),
    FOREIGN KEY (id_notificacion) REFERENCES NotificacionesSistema(id_notificacion) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_notificacion (id_notificacion, num_doc_usuario)
);

-- ========================================
-- 3. INSERTAR DATOS INICIALES SI NO EXISTEN
-- ========================================

-- Tipos de salida
INSERT IGNORE INTO TiposSalida (codigo, nombre, descripcion, requiere_seguimiento) VALUES
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

-- ========================================
-- 4. CREAR VISTAS ÚTILES
-- ========================================

-- Vista completa de productos (reemplazar si existe)
CREATE OR REPLACE VIEW vista_productos_completa AS
SELECT 
    p.id_prod,
    p.nombre,
    p.modelo,
    p.talla,
    p.color,
    p.stock,
    COALESCE(p.stock_minimo, 10) as stock_minimo,
    COALESCE(p.precio_unitario, 0) as precio_unitario,
    p.fecha_ing,
    p.material,
    COALESCE(p.activo, 1) as activo,
    sc.nombre as subcategoria,
    c.nombre as categoria,
    pr.razon_social as proveedor,
    u.nombres as creado_por,
    CASE 
        WHEN p.stock <= 0 THEN 'Sin Stock'
        WHEN p.stock <= COALESCE(p.stock_minimo, 10) THEN 'Stock Bajo'
        WHEN p.stock <= (COALESCE(p.stock_minimo, 10) * 2) THEN 'Stock Moderado'
        ELSE 'Stock Suficiente'
    END as estado_stock
FROM Productos p
LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
LEFT JOIN Categoria c ON sc.id_categ = c.id_categ
LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit
LEFT JOIN Users u ON p.num_doc = u.num_doc;

-- Vista de salidas completa
CREATE OR REPLACE VIEW vista_salidas_completa AS
SELECT 
    s.id_salida,
    s.tipo_salida,
    COALESCE(s.estado_salida, 'completada') as estado_salida,
    s.cantidad,
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
LEFT JOIN Users u ON s.num_doc_usuario = u.num_doc
LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
LEFT JOIN Categoria c ON sc.id_categ = c.id_categ
LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit
LEFT JOIN ProductosSeguimiento ps ON s.id_salida = ps.id_salida AND ps.id = (
    SELECT MAX(ps2.id) FROM ProductosSeguimiento ps2 WHERE ps2.id_salida = s.id_salida
)
LEFT JOIN Garantias g ON s.id_salida = g.id_salida;

-- ========================================
-- 5. TRIGGERS DE AUTOMATIZACIÓN
-- ========================================

DELIMITER //

-- Verificar si el trigger ya existe antes de crearlo
DROP TRIGGER IF EXISTS salidas_seguimiento_insert //

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
    
    -- Registrar movimiento en historial si la tabla existe
    INSERT INTO HistorialMovimientos (id_prod, tipo_movimiento, cantidad, usuario, observaciones)
    VALUES (NEW.id_prod, 'salida', NEW.cantidad, 'SISTEMA', CONCAT('Salida tipo: ', NEW.tipo_salida));
END //

-- Trigger para devoluciones
DROP TRIGGER IF EXISTS devoluciones_stock_update //

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
-- 6. NOTIFICACIÓN DE ÉXITO
-- ========================================

-- Insertar notificación de actualización exitosa
INSERT INTO NotificacionesSistema (tipo_evento, titulo, mensaje, nivel_prioridad, color, creado_por) VALUES
('actualizacion_sistema', 
 '¡Sistema InventiXor Actualizado!', 
 'Las mejoras del sistema han sido aplicadas exitosamente. El campo motivo ahora funciona como lista desplegable categorizada en las devoluciones.', 
 'alta', 
 'success', 
 'sistema')
ON DUPLICATE KEY UPDATE 
mensaje = 'Las mejoras del sistema han sido aplicadas exitosamente. El campo motivo ahora funciona como lista desplegable categorizada en las devoluciones.';

-- Mensaje de finalización
SELECT 
    'Actualización completada exitosamente' as estado,
    'Campo motivo funcionando como lista desplegable' as mejora_principal,
    COUNT(*) as tablas_nuevas_creadas
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('ProductosSeguimiento', 'Devoluciones', 'Garantias', 'ProductosTransito', 'TiposSalida', 'NotificacionesSistema');

SELECT 
    'Para probar el campo motivo:' as instruccion,
    'http://localhost/inventixor/solucion_definitiva.html' as url_prueba;

-- ========================================
-- FIN DE LA ACTUALIZACIÓN
-- ========================================