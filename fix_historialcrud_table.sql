-- Script para crear las tablas de historial que faltan en la base de datos
-- Estas tablas registran todas las operaciones del sistema para auditoría

-- Tabla para operaciones CRUD generales
CREATE TABLE IF NOT EXISTS `HistorialCRUD` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `entidad` varchar(50) NOT NULL COMMENT 'Nombre de la entidad (Producto, Categoria, Subcategoria, etc.)',
    `id_entidad` int(11) NOT NULL COMMENT 'ID del registro afectado',
    `accion` enum('crear','editar','eliminar','leer') NOT NULL COMMENT 'Tipo de operación realizada',
    `usuario` varchar(100) NOT NULL COMMENT 'Nombre del usuario que realizó la acción',
    `rol` varchar(20) NOT NULL COMMENT 'Rol del usuario (admin, coordinador, auxiliar)',
    `detalles` longtext COMMENT 'Detalles JSON de los cambios realizados',
    `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de la operación',
    PRIMARY KEY (`id`),
    KEY `idx_entidad` (`entidad`),
    KEY `idx_id_entidad` (`id_entidad`),
    KEY `idx_accion` (`accion`),
    KEY `idx_usuario` (`usuario`),
    KEY `idx_rol` (`rol`),
    KEY `idx_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de operaciones CRUD del sistema';

-- Tabla para movimientos específicos de productos (inventario)
CREATE TABLE IF NOT EXISTS `HistorialMovimientos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `id_prod` int(11) NOT NULL COMMENT 'ID del producto afectado',
    `tipo_movimiento` enum('alta','baja','edicion','entrada','salida','ajuste') NOT NULL COMMENT 'Tipo de movimiento de inventario',
    `cantidad` int(11) DEFAULT NULL COMMENT 'Cantidad afectada en el movimiento',
    `stock_anterior` int(11) DEFAULT NULL COMMENT 'Stock antes del movimiento',
    `stock_nuevo` int(11) DEFAULT NULL COMMENT 'Stock después del movimiento',
    `usuario` varchar(100) NOT NULL COMMENT 'Usuario que realizó el movimiento',
    `observaciones` text COMMENT 'Observaciones del movimiento',
    `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del movimiento',
    PRIMARY KEY (`id`),
    KEY `idx_id_prod` (`id_prod`),
    KEY `idx_tipo_movimiento` (`tipo_movimiento`),
    KEY `idx_usuario` (`usuario`),
    KEY `idx_fecha` (`fecha`),
    FOREIGN KEY (`id_prod`) REFERENCES `Productos`(`id_prod`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de movimientos de inventario de productos';