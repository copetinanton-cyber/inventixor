-- Tabla para historial de movimientos de productos
CREATE TABLE IF NOT EXISTS HistorialMovimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_prod INT NOT NULL,
    tipo_movimiento VARCHAR(50) NOT NULL, -- entrada, salida, edición, alta, baja
    cantidad INT DEFAULT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    usuario VARCHAR(100) NOT NULL,
    observaciones TEXT,
    FOREIGN KEY (id_prod) REFERENCES Productos(id_prod)
);

-- Tabla para historial de acciones CRUD por rol
CREATE TABLE IF NOT EXISTS HistorialCRUD (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entidad VARCHAR(50) NOT NULL, -- Producto, Categoria, Subcategoria
    id_entidad INT NOT NULL,
    accion VARCHAR(20) NOT NULL, -- crear, editar, eliminar
    usuario VARCHAR(100) NOT NULL,
    rol VARCHAR(50) NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    detalles TEXT
);
