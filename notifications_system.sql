-- ================================================
-- SISTEMA DE NOTIFICACIONES AUTOMÁTICAS - INVENTIXOR
-- ================================================
-- Crear tabla para notificaciones del sistema que aparecen a todos los usuarios
-- Estas notificaciones se muestran automáticamente cuando ocurren eventos importantes

CREATE TABLE IF NOT EXISTS `NotificacionesSistema` (
    `id_notificacion` INT AUTO_INCREMENT PRIMARY KEY,
    `tipo_evento` VARCHAR(50) NOT NULL COMMENT 'Tipo de evento: producto_eliminado, stock_bajo, nuevo_producto, etc.',
    `titulo` VARCHAR(255) NOT NULL COMMENT 'Título de la notificación',
    `mensaje` TEXT NOT NULL COMMENT 'Mensaje descriptivo de la notificación',
    `datos_evento` JSON DEFAULT NULL COMMENT 'Datos específicos del evento (ID producto, nombre, cantidad, etc.)',
    `nivel_prioridad` ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media' COMMENT 'Nivel de prioridad de la notificación',
    `icono` VARCHAR(50) DEFAULT 'fas fa-bell' COMMENT 'Ícono FontAwesome para la notificación',
    `color` VARCHAR(20) DEFAULT 'info' COMMENT 'Color de la notificación: success, warning, error, info',
    `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de creación',
    `activa` BOOLEAN DEFAULT TRUE COMMENT 'Si la notificación está activa y debe mostrarse',
    `mostrar_hasta` TIMESTAMP NULL DEFAULT NULL COMMENT 'Fecha hasta la cual debe mostrarse (NULL = permanente)',
    `creado_por` VARCHAR(100) DEFAULT 'sistema' COMMENT 'Usuario o sistema que creó la notificación',
    INDEX idx_tipo_evento (tipo_evento),
    INDEX idx_fecha_creacion (fecha_creacion),
    INDEX idx_activa (activa),
    INDEX idx_nivel_prioridad (nivel_prioridad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notificaciones automáticas del sistema para todos los usuarios';

-- ================================================
-- Tabla para rastrear qué usuarios han visto cada notificación
-- ================================================
CREATE TABLE IF NOT EXISTS `NotificacionesVistas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_notificacion` INT NOT NULL,
    `num_doc_usuario` VARCHAR(20) NOT NULL COMMENT 'Documento del usuario que vio la notificación',
    `fecha_vista` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora en que se vio la notificación',
    `ip_usuario` VARCHAR(45) DEFAULT NULL COMMENT 'IP del usuario cuando vio la notificación',
    FOREIGN KEY (id_notificacion) REFERENCES NotificacionesSistema(id_notificacion) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_notificacion (id_notificacion, num_doc_usuario),
    INDEX idx_usuario (num_doc_usuario),
    INDEX idx_fecha_vista (fecha_vista)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Control de notificaciones vistas por cada usuario';

-- ================================================
-- Configuración de notificaciones automáticas
-- ================================================
CREATE TABLE IF NOT EXISTS `ConfigNotificaciones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tipo_evento` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Tipo de evento a configurar',
    `habilitado` BOOLEAN DEFAULT TRUE COMMENT 'Si este tipo de notificación está habilitado',
    `umbral_stock_bajo` INT DEFAULT 10 COMMENT 'Umbral para considerar stock bajo',
    `template_titulo` VARCHAR(255) NOT NULL COMMENT 'Template del título de la notificación',
    `template_mensaje` TEXT NOT NULL COMMENT 'Template del mensaje de la notificación',
    `nivel_prioridad` ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media',
    `icono` VARCHAR(50) DEFAULT 'fas fa-bell',
    `color` VARCHAR(20) DEFAULT 'info',
    `duracion_horas` INT DEFAULT 24 COMMENT 'Cuántas horas debe mostrarse la notificación (0 = permanente)',
    `fecha_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuración de tipos de notificaciones automáticas';

-- ================================================
-- Insertar configuraciones predeterminadas
-- ================================================
INSERT INTO `ConfigNotificaciones` (
    tipo_evento, habilitado, template_titulo, template_mensaje, nivel_prioridad, icono, color, duracion_horas, umbral_stock_bajo
) VALUES 
-- Productos
('producto_eliminado', TRUE, 'Producto Eliminado', 'El producto "{producto_nombre}" (ID: {producto_id}) ha sido eliminado del sistema por {usuario}.', 'media', 'fas fa-trash', 'warning', 12, NULL),
('nuevo_producto', TRUE, 'Nuevo Producto Agregado', 'Se ha agregado el producto "{producto_nombre}" (ID: {producto_id}) al inventario por {usuario}.', 'baja', 'fas fa-plus-circle', 'success', 6, NULL),
('stock_bajo', TRUE, 'Stock Bajo Detectado', 'ATENCIÓN: El producto "{producto_nombre}" tiene stock bajo ({stock_actual} unidades). Umbral mínimo: {stock_minimo}.', 'alta', 'fas fa-exclamation-triangle', 'warning', 48, 10),
('stock_critico', TRUE, 'Stock Crítico', 'CRÍTICO: El producto "{producto_nombre}" está agotado o en niveles críticos ({stock_actual} unidades).', 'critica', 'fas fa-ban', 'error', 72, NULL),

-- Categorías y Subcategorías  
('nueva_categoria', TRUE, 'Nueva Categoría Creada', 'Se ha creado la categoría "{categoria_nombre}" (ID: {categoria_id}) por {usuario}.', 'baja', 'fas fa-folder-plus', 'success', 4, NULL),
('categoria_eliminada', TRUE, 'Categoría Eliminada', 'La categoría "{categoria_nombre}" ha sido eliminada del sistema por {usuario}.', 'media', 'fas fa-folder-minus', 'warning', 8, NULL),
('nueva_subcategoria', TRUE, 'Nueva Subcategoría Creada', 'Se ha creado la subcategoría "{subcategoria_nombre}" en "{categoria_nombre}" por {usuario}.', 'baja', 'fas fa-tags', 'success', 4, NULL),
('subcategoria_eliminada', TRUE, 'Subcategoría Eliminada', 'La subcategoría "{subcategoria_nombre}" ha sido eliminada por {usuario}.', 'media', 'fas fa-tag', 'warning', 8, NULL),

-- Proveedores
('nuevo_proveedor', TRUE, 'Nuevo Proveedor Registrado', 'Se ha registrado el proveedor "{proveedor_nombre}" (NIT: {proveedor_nit}) por {usuario}.', 'baja', 'fas fa-building', 'success', 6, NULL),
('proveedor_eliminado', TRUE, 'Proveedor Eliminado', 'El proveedor "{proveedor_nombre}" (NIT: {proveedor_nit}) ha sido eliminado por {usuario}.', 'media', 'fas fa-building', 'warning', 12, NULL),

-- Salidas
('salida_eliminada', TRUE, 'Salida de Inventario Eliminada', 'Se eliminó la salida del producto "{producto_nombre}" (Cantidad: {cantidad}) y se restauró el stock por {usuario}.', 'media', 'fas fa-undo', 'info', 8, NULL),
('salida_importante', TRUE, 'Salida Importante de Stock', 'Salida significativa: {cantidad} unidades de "{producto_nombre}" por {motivo}. Realizada por {usuario}.', 'alta', 'fas fa-shipping-fast', 'warning', 24, NULL),

-- Sistema
('usuario_eliminado', TRUE, 'Usuario Eliminado', 'El usuario "{usuario_nombre}" (Doc: {usuario_doc}) ha sido eliminado del sistema por {usuario_admin}.', 'alta', 'fas fa-user-times', 'error', 24, NULL),
('backup_realizado', TRUE, 'Respaldo del Sistema', 'Se ha realizado un respaldo automático del sistema exitosamente.', 'baja', 'fas fa-database', 'success', 2, NULL);

-- ================================================
-- Función para limpiar notificaciones antiguas
-- ================================================
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS LimpiarNotificacionesAntiguas()
BEGIN
    -- Eliminar notificaciones que han expirado según su duración configurada
    DELETE ns FROM NotificacionesSistema ns 
    INNER JOIN ConfigNotificaciones cn ON ns.tipo_evento = cn.tipo_evento
    WHERE cn.duracion_horas > 0 
    AND ns.fecha_creacion < DATE_SUB(NOW(), INTERVAL cn.duracion_horas HOUR);
    
    -- Eliminar notificaciones con fecha de expiración específica
    DELETE FROM NotificacionesSistema 
    WHERE mostrar_hasta IS NOT NULL 
    AND mostrar_hasta < NOW();
    
    -- Eliminar registros de vistas de notificaciones eliminadas
    DELETE nv FROM NotificacionesVistas nv
    LEFT JOIN NotificacionesSistema ns ON nv.id_notificacion = ns.id_notificacion
    WHERE ns.id_notificacion IS NULL;
END //
DELIMITER ;

-- ================================================
-- Evento programado para limpiar notificaciones (ejecutar cada hora)
-- ================================================
SET GLOBAL event_scheduler = ON;

CREATE EVENT IF NOT EXISTS `evt_limpiar_notificaciones`
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
  CALL LimpiarNotificacionesAntiguas();

-- ================================================
-- Vistas útiles para consultas
-- ================================================

-- Vista de notificaciones activas con configuración
CREATE OR REPLACE VIEW `NotificacionesActivas` AS
SELECT 
    ns.id_notificacion,
    ns.tipo_evento,
    ns.titulo,
    ns.mensaje,
    ns.datos_evento,
    ns.nivel_prioridad,
    ns.icono,
    ns.color,
    ns.fecha_creacion,
    ns.creado_por,
    cn.duracion_horas,
    CASE 
        WHEN cn.duracion_horas = 0 THEN NULL
        ELSE DATE_ADD(ns.fecha_creacion, INTERVAL cn.duracion_horas HOUR)
    END as fecha_expiracion
FROM NotificacionesSistema ns
INNER JOIN ConfigNotificaciones cn ON ns.tipo_evento = cn.tipo_evento
WHERE ns.activa = TRUE
  AND cn.habilitado = TRUE
  AND (ns.mostrar_hasta IS NULL OR ns.mostrar_hasta > NOW())
  AND (cn.duracion_horas = 0 OR ns.fecha_creacion > DATE_SUB(NOW(), INTERVAL cn.duracion_horas HOUR))
ORDER BY 
    FIELD(ns.nivel_prioridad, 'critica', 'alta', 'media', 'baja'),
    ns.fecha_creacion DESC;

-- Vista de notificaciones por usuario (no vistas)
CREATE OR REPLACE VIEW `NotificacionesPendientesPorUsuario` AS
SELECT 
    na.*,
    u.num_doc as usuario_num_doc
FROM NotificacionesActivas na
CROSS JOIN (SELECT DISTINCT num_doc FROM usuarios) u
LEFT JOIN NotificacionesVistas nv ON na.id_notificacion = nv.id_notificacion 
    AND u.num_doc = nv.num_doc_usuario
WHERE nv.id_notificacion IS NULL;

-- ================================================
-- Índices adicionales para optimización
-- ================================================
ALTER TABLE NotificacionesSistema ADD INDEX idx_tipo_activa (tipo_evento, activa);
ALTER TABLE NotificacionesSistema ADD INDEX idx_fecha_activa (fecha_creacion, activa);

-- ================================================
-- INSTRUCCIONES DE USO
-- ================================================
/*
EJEMPLOS DE USO:

1. Crear notificación de producto eliminado:
INSERT INTO NotificacionesSistema (tipo_evento, titulo, mensaje, datos_evento, creado_por)
VALUES (
    'producto_eliminado', 
    'Producto Eliminado',
    'El producto "Laptop Dell Inspiron" (ID: 123) ha sido eliminado del sistema por Admin.',
    JSON_OBJECT('producto_id', 123, 'producto_nombre', 'Laptop Dell Inspiron', 'usuario', 'Admin'),
    'Admin'
);

2. Obtener notificaciones pendientes para un usuario:
SELECT * FROM NotificacionesPendientesPorUsuario 
WHERE usuario_num_doc = '1000000001' 
ORDER BY nivel_prioridad DESC, fecha_creacion DESC;

3. Marcar notificación como vista:
INSERT INTO NotificacionesVistas (id_notificacion, num_doc_usuario, ip_usuario) 
VALUES (1, '1000000001', '192.168.1.1');

4. Configurar umbral de stock bajo:
UPDATE ConfigNotificaciones 
SET umbral_stock_bajo = 15 
WHERE tipo_evento = 'stock_bajo';

5. Deshabilitar un tipo de notificación:
UPDATE ConfigNotificaciones 
SET habilitado = FALSE 
WHERE tipo_evento = 'nuevo_producto';
*/