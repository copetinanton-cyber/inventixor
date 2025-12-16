-- ========================================
-- MEJORAS PARA EL MÓDULO DE SALIDAS
-- Sistema Intermedio de Gestión Post-Salida
-- ========================================

-- 1. MODIFICAR TABLA SALIDAS para incluir más tipos y estados
ALTER TABLE Salidas 
ADD COLUMN estado_salida VARCHAR(50) DEFAULT 'completada' AFTER tipo_salida,
ADD COLUMN fecha_entrega DATETIME NULL AFTER fecha_hora,
ADD COLUMN cliente_info JSON NULL AFTER observacion;

-- 2. CREAR TABLA DE SEGUIMIENTO POST-SALIDA
CREATE TABLE IF NOT EXISTS ProductosSeguimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_salida INT NOT NULL,
    estado VARCHAR(50) NOT NULL, -- 'entregado', 'en_transito', 'devuelto', 'garantia', 'perdido', 'dañado'
    fecha_estado DATETIME DEFAULT CURRENT_TIMESTAMP,
    observaciones TEXT,
    usuario VARCHAR(100) NOT NULL,
    ubicacion VARCHAR(255) NULL, -- Para productos en tránsito
    FOREIGN KEY (id_salida) REFERENCES Salidas(id_salida) ON DELETE CASCADE
);

-- 3. CREAR TABLA DE DEVOLUCIONES
CREATE TABLE IF NOT EXISTS Devoluciones (
    id_devolucion INT AUTO_INCREMENT PRIMARY KEY,
    id_salida INT NOT NULL,
    id_prod INT NOT NULL,
    cantidad_devuelta INT NOT NULL,
    motivo VARCHAR(100) NOT NULL, -- 'defecto_fabrica', 'no_conforme', 'cambio_talla', 'garantia', 'otro'
    condicion_producto VARCHAR(50) NOT NULL, -- 'nuevo', 'usado_bueno', 'usado_regular', 'dañado', 'no_recuperable'
    accion VARCHAR(50) NOT NULL, -- 'reingresar_inventario', 'devolver_proveedor', 'descartar', 'reparar'
    fecha_devolucion DATETIME DEFAULT CURRENT_TIMESTAMP,
    observaciones TEXT,
    usuario_recibe VARCHAR(100) NOT NULL,
    reingresado_stock BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_salida) REFERENCES Salidas(id_salida),
    FOREIGN KEY (id_prod) REFERENCES Productos(id_prod)
);

-- 4. CREAR TABLA DE GARANTÍAS
CREATE TABLE IF NOT EXISTS Garantias (
    id_garantia INT AUTO_INCREMENT PRIMARY KEY,
    id_salida INT NOT NULL,
    id_prod INT NOT NULL,
    tipo_garantia VARCHAR(50) NOT NULL, -- 'fabricante', 'tienda', 'extendida'
    duracion_meses INT DEFAULT 12,
    fecha_inicio DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    estado VARCHAR(30) DEFAULT 'activa', -- 'activa', 'utilizada', 'vencida'
    terminos TEXT,
    FOREIGN KEY (id_salida) REFERENCES Salidas(id_salida),
    FOREIGN KEY (id_prod) REFERENCES Productos(id_prod)
);

-- 5. CREAR TABLA DE PRODUCTOS EN TRÁNSITO
CREATE TABLE IF NOT EXISTS ProductosTransito (
    id_transito INT AUTO_INCREMENT PRIMARY KEY,
    id_salida INT NOT NULL,
    id_prod INT NOT NULL,
    destino VARCHAR(255) NOT NULL,
    fecha_envio DATETIME,
    fecha_entrega_estimada DATETIME,
    fecha_entrega_real DATETIME NULL,
    estado VARCHAR(50) DEFAULT 'preparando', -- 'preparando', 'enviado', 'en_transito', 'entregado', 'fallido'
    transportista VARCHAR(100),
    numero_guia VARCHAR(100),
    observaciones TEXT,
    FOREIGN KEY (id_salida) REFERENCES Salidas(id_salida),
    FOREIGN KEY (id_prod) REFERENCES Productos(id_prod)
);

-- 6. ACTUALIZAR TIPOS DE SALIDA PERMITIDOS
-- Crear tabla de referencia para tipos de salida
CREATE TABLE IF NOT EXISTS TiposSalida (
    id_tipo INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    requiere_seguimiento BOOLEAN DEFAULT TRUE,
    activo BOOLEAN DEFAULT TRUE
);

-- Insertar tipos de salida estándar
INSERT INTO TiposSalida (codigo, nombre, descripcion, requiere_seguimiento) VALUES
('venta', 'Venta Regular', 'Venta directa al cliente final', TRUE),
('venta_mayoreo', 'Venta por Mayoreo', 'Venta en grandes cantidades', TRUE),
('devolucion_proveedor', 'Devolución a Proveedor', 'Producto devuelto al proveedor por defecto o cambio', TRUE),
('producto_dañado', 'Producto Dañado', 'Producto dañado que debe ser descartado', FALSE),
('perdida', 'Pérdida/Robo', 'Producto perdido o robado', FALSE),
('uso_interno', 'Uso Interno', 'Producto utilizado internamente por la empresa', FALSE),
('prestamo', 'Préstamo', 'Producto prestado temporalmente', TRUE),
('muestra_gratuita', 'Muestra Gratuita', 'Producto entregado como muestra o promoción', FALSE),
('transferencia', 'Transferencia', 'Transferencia a otra sucursal o almacén', TRUE),
('garantia', 'Salida por Garantía', 'Producto enviado para reparación bajo garantía', TRUE)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- 7. CREAR ÍNDICES PARA OPTIMIZAR CONSULTAS
CREATE INDEX idx_productos_seguimiento_salida ON ProductosSeguimiento(id_salida);
CREATE INDEX idx_productos_seguimiento_estado ON ProductosSeguimiento(estado);
CREATE INDEX idx_devoluciones_salida ON Devoluciones(id_salida);
CREATE INDEX idx_devoluciones_producto ON Devoluciones(id_prod);
CREATE INDEX idx_garantias_producto ON Garantias(id_prod);
CREATE INDEX idx_garantias_estado ON Garantias(estado);
CREATE INDEX idx_transito_salida ON ProductosTransito(id_salida);
CREATE INDEX idx_transito_estado ON ProductosTransito(estado);

-- 8. CREAR VISTAS ÚTILES PARA REPORTES
CREATE VIEW IF NOT EXISTS vista_salidas_completa AS
SELECT 
    s.id_salida,
    s.tipo_salida,
    s.estado_salida,
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
    ps.estado as estado_seguimiento,
    ps.fecha_estado as ultima_actualizacion,
    CASE 
        WHEN g.estado = 'activa' AND g.fecha_vencimiento > CURDATE() THEN 'Con Garantía'
        WHEN g.estado = 'activa' AND g.fecha_vencimiento <= CURDATE() THEN 'Garantía Vencida'
        ELSE 'Sin Garantía'
    END as estado_garantia
FROM Salidas s
INNER JOIN Productos p ON s.id_prod = p.id_prod
LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
LEFT JOIN Categoria c ON sc.id_categ = c.id_categ
LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit
LEFT JOIN ProductosSeguimiento ps ON s.id_salida = ps.id_salida AND ps.id = (
    SELECT MAX(ps2.id) FROM ProductosSeguimiento ps2 WHERE ps2.id_salida = s.id_salida
)
LEFT JOIN Garantias g ON s.id_salida = g.id_salida;

-- 9. CREAR TRIGGERS PARA AUTOMATIZACIÓN
DELIMITER //

-- Trigger para crear seguimiento automático al registrar salida
CREATE TRIGGER after_salida_insert 
AFTER INSERT ON Salidas
FOR EACH ROW
BEGIN
    DECLARE tipo_seguimiento VARCHAR(50);
    
    -- Determinar estado inicial según tipo de salida
    CASE NEW.tipo_salida
        WHEN 'venta' THEN SET tipo_seguimiento = 'en_transito';
        WHEN 'prestamo' THEN SET tipo_seguimiento = 'prestado';
        WHEN 'transferencia' THEN SET tipo_seguimiento = 'en_transito';
        WHEN 'devolucion_proveedor' THEN SET tipo_seguimiento = 'devuelto';
        ELSE SET tipo_seguimiento = 'completado';
    END CASE;
    
    -- Insertar registro de seguimiento inicial
    INSERT INTO ProductosSeguimiento (id_salida, estado, observaciones, usuario)
    VALUES (NEW.id_salida, tipo_seguimiento, CONCAT('Salida registrada: ', NEW.tipo_salida), 'SISTEMA');
END //

-- Trigger para actualizar stock al registrar devolución
CREATE TRIGGER after_devolucion_insert 
AFTER INSERT ON Devoluciones
FOR EACH ROW
BEGIN
    -- Si la acción es reingresar al inventario, actualizar stock
    IF NEW.accion = 'reingresar_inventario' AND NEW.reingresado_stock = TRUE THEN
        UPDATE Productos 
        SET stock = stock + NEW.cantidad_devuelta 
        WHERE id_prod = NEW.id_prod;
        
        -- Registrar movimiento en historial
        INSERT INTO HistorialMovimientos (id_prod, tipo_movimiento, cantidad, usuario, observaciones)
        VALUES (NEW.id_prod, 'devolucion', NEW.cantidad_devuelta, NEW.usuario_recibe, 
                CONCAT('Devolución reingresada - Motivo: ', NEW.motivo));
    END IF;
END //

DELIMITER ;

-- 10. INSERTAR DATOS DE EJEMPLO PARA TESTING
-- (Solo si no existen salidas)
-- INSERT INTO ProductosSeguimiento (id_salida, estado, observaciones, usuario) 
-- SELECT id_salida, 'entregado', 'Migración de datos existentes', 'SISTEMA'
-- FROM Salidas 
-- WHERE tipo_salida = 'venta';