-- Datos demo para Alertas
INSERT INTO Alertas (tipo_alerta, observacion, nivel_alerta, fecha_generacion, estado, id_prod) VALUES
-- Alertas críticas
('Stock Bajo', 'Stock crítico en Nike Air Max 90 - Solo quedan 2 unidades', 'Crítico', '2024-01-15', 'Activa', 1),
('Stock Bajo', 'Adidas Ultraboost 22 agotándose - 3 unidades restantes', 'Crítico', '2024-01-14', 'Activa', 5),
('Calidad', 'Defectos reportados en lote de zapatos escolares negros', 'Alto', '2024-01-13', 'Pendiente', 15),

-- Alertas de stock
('Stock Bajo', 'Converse Chuck Taylor necesita reabastecimiento - 8 unidades', 'Medio', '2024-01-12', 'Activa', 9),
('Stock Bajo', 'Zapatos formales hombre en stock bajo - 5 unidades', 'Alto', '2024-01-11', 'Activa', 18),
('Stock Bajo', 'Sandalias infantiles rosa - reposición urgente', 'Medio', '2024-01-10', 'Resuelta', 21),

-- Alertas de proveedores
('Proveedor', 'Retraso en entrega de Distribuidora Zapatos Colombia', 'Alto', '2024-01-09', 'Pendiente', NULL),
('Proveedor', 'Cambio de precios notificado por Calzado Premium SAS', 'Bajo', '2024-01-08', 'Resuelta', NULL),

-- Alertas de sistema
('Sistema', 'Actualización de inventario requerida para categoría Deportivo', 'Medio', '2024-01-07', 'Resuelta', NULL),
('Mantenimiento', 'Revisión mensual del sistema de alertas automáticas', 'Bajo', '2024-01-06', 'Resuelta', NULL),

-- Alertas de calidad
('Calidad', 'Revisión de calidad pendiente en zapatos escolares colegiales', 'Medio', '2024-01-05', 'Activa', 14),
('Vencimiento', 'Promoción especial de temporada por vencer en zapatos deportivos', 'Bajo', '2024-01-04', 'Activa', NULL),

-- Más alertas críticas para pruebas
('Stock Bajo', 'Puma RS-X casi agotado - Solo 1 unidad disponible', 'Crítico', '2024-01-16', 'Activa', 10),
('Stock Bajo', 'Botines casuales café - Reabastecimiento crítico', 'Crítico', '2024-01-17', 'Activa', 19),
('Calidad', 'Devolución masiva reportada en zapatos infantiles azules', 'Alto', '2024-01-18', 'Activa', 20);