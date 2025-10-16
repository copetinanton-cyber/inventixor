<?php
/**
 * Sistema de Notificaciones Automáticas para InventiXor
 * 
 * Esta clase maneja la creación, gestión y entrega de notificaciones
 * automáticas del sistema que aparecen a todos los usuarios cuando
 * ocurren eventos importantes como:
 * - Productos eliminados
 * - Stock bajo/crítico  
 * - Nuevos productos/categorías/proveedores
 * - Salidas importantes de inventario
 * - Etc.
 * 
 * @author Sistema InventiXor
 * @version 2.0
 */

class SistemaNotificaciones {
    private $db;
    private $usuario_actual;
    
    public function __construct($database, $usuario = null) {
        // Si se pasa un objeto Database, usar su conexión
        if (is_object($database) && property_exists($database, 'conn')) {
            $this->db = $database->conn;
        } else {
            // Si se pasa directamente una conexión mysqli
            $this->db = $database;
        }
        $this->usuario_actual = $usuario;
    }
    
    /**
     * Crea una nueva notificación automática del sistema
     * 
     * @param string $tipo_evento Tipo de evento (producto_eliminado, stock_bajo, etc.)
     * @param array $datos_evento Datos específicos del evento
     * @param string $usuario_creador Usuario que causó el evento (opcional)
     * @return int|false ID de la notificación creada o false en caso de error
     */
    public function crearNotificacion($tipo_evento, $datos_evento = [], $usuario_creador = null) {
        try {
            // Obtener configuración del tipo de evento
            $config = $this->obtenerConfiguracion($tipo_evento);
            if (!$config || !$config['habilitado']) {
                return false; // Tipo de notificación deshabilitado
            }
            
            // Procesar templates con datos del evento
            $titulo = $this->procesarTemplate($config['template_titulo'], $datos_evento);
            $mensaje = $this->procesarTemplate($config['template_mensaje'], $datos_evento);
            
            // Calcular fecha de expiración
            $mostrar_hasta = null;
            if ($config['duracion_horas'] > 0) {
                $mostrar_hasta = date('Y-m-d H:i:s', strtotime("+{$config['duracion_horas']} hours"));
            }
            
            // Insertar notificación
            $sql = "INSERT INTO NotificacionesSistema (
                        tipo_evento, titulo, mensaje, datos_evento, 
                        nivel_prioridad, icono, color, mostrar_hasta, creado_por
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $datos_json = json_encode($datos_evento, JSON_UNESCAPED_UNICODE);
            $creador = $usuario_creador ?? $this->usuario_actual['nombre'] ?? 'sistema';
            
            $stmt->bind_param('sssssssss', 
                $tipo_evento, 
                $titulo, 
                $mensaje, 
                $datos_json,
                $config['nivel_prioridad'],
                $config['icono'],
                $config['color'],
                $mostrar_hasta,
                $creador
            );
            
            if ($stmt->execute()) {
                $id_notificacion = $this->db->insert_id;
                $stmt->close();
                
                // Log del evento para auditoría
                error_log("[InventiXor] Notificación creada: ID=$id_notificacion, Tipo=$tipo_evento, Usuario=$creador");
                
                return $id_notificacion;
            } else {
                $stmt->close();
                return false;
            }
            
        } catch (Exception $e) {
            error_log("[InventiXor] Error creando notificación: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener notificaciones pendientes para un usuario específico
     * 
     * @param string $num_doc_usuario Número de documento del usuario
     * @param int $limite Número máximo de notificaciones a retornar
     * @return array Lista de notificaciones pendientes
     */
    public function obtenerNotificacionesPendientes($num_doc_usuario, $limite = 10) {
        try {
            $sql = "SELECT * FROM NotificacionesPendientesPorUsuario 
                    WHERE usuario_num_doc = ? 
                    ORDER BY 
                        FIELD(nivel_prioridad, 'critica', 'alta', 'media', 'baja'),
                        fecha_creacion DESC 
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $num_doc_usuario, $limite);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $notificaciones = [];
            
            while ($row = $result->fetch_assoc()) {
                // Decodificar datos del evento
                if ($row['datos_evento']) {
                    $row['datos_evento'] = json_decode($row['datos_evento'], true);
                }
                $notificaciones[] = $row;
            }
            
            $stmt->close();
            return $notificaciones;
            
        } catch (Exception $e) {
            error_log("[InventiXor] Error obteniendo notificaciones: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Marcar una notificación como vista por un usuario
     * 
     * @param int $id_notificacion ID de la notificación
     * @param string $num_doc_usuario Número de documento del usuario
     * @param string $ip_usuario IP del usuario (opcional)
     * @return bool True si se marcó correctamente
     */
    public function marcarComoVista($id_notificacion, $num_doc_usuario, $ip_usuario = null) {
        try {
            $sql = "INSERT IGNORE INTO NotificacionesVistas 
                    (id_notificacion, num_doc_usuario, ip_usuario) 
                    VALUES (?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $ip_usuario = $ip_usuario ?? $_SERVER['REMOTE_ADDR'] ?? null;
            $stmt->bind_param('iss', $id_notificacion, $num_doc_usuario, $ip_usuario);
            
            $resultado = $stmt->execute();
            $stmt->close();
            
            return $resultado;
            
        } catch (Exception $e) {
            error_log("[InventiXor] Error marcando notificación como vista: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener estadísticas de notificaciones para el dashboard
     * 
     * @return array Estadísticas de notificaciones
     */
    public function obtenerEstadisticas() {
        try {
            $stats = [];
            
            // Total de notificaciones activas
            $result = $this->db->query("SELECT COUNT(*) as total FROM NotificacionesActivas");
            $stats['total_activas'] = $result->fetch_assoc()['total'];
            
            // Notificaciones por prioridad
            $result = $this->db->query("
                SELECT nivel_prioridad, COUNT(*) as cantidad 
                FROM NotificacionesActivas 
                GROUP BY nivel_prioridad
            ");
            $stats['por_prioridad'] = [];
            while ($row = $result->fetch_assoc()) {
                $stats['por_prioridad'][$row['nivel_prioridad']] = $row['cantidad'];
            }
            
            // Notificaciones por tipo en las últimas 24 horas
            $result = $this->db->query("
                SELECT tipo_evento, COUNT(*) as cantidad 
                FROM NotificacionesSistema 
                WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY tipo_evento 
                ORDER BY cantidad DESC 
                LIMIT 5
            ");
            $stats['ultimas_24h'] = [];
            while ($row = $result->fetch_assoc()) {
                $stats['ultimas_24h'][] = $row;
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("[InventiXor] Error obteniendo estadísticas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Métodos específicos para cada tipo de evento del sistema
     */
    
    /**
     * Notificación cuando se elimina un producto
     */
    public function notificarProductoEliminado($producto_id, $producto_nombre, $usuario = null) {
        return $this->crearNotificacion('producto_eliminado', [
            'producto_id' => $producto_id,
            'producto_nombre' => $producto_nombre,
            'usuario' => $usuario ?? $this->usuario_actual['nombres'] ?? 'Usuario'
        ], $usuario);
    }
    
    /**
     * Notificación cuando se agrega un nuevo producto
     */
    public function notificarNuevoProducto($producto_id, $producto_nombre, $usuario = null) {
        return $this->crearNotificacion('nuevo_producto', [
            'producto_id' => $producto_id,
            'producto_nombre' => $producto_nombre,
            'usuario' => $usuario ?? $this->usuario_actual['nombres'] ?? 'Usuario'
        ], $usuario);
    }
    
    /**
     * Notificación cuando se modifica un producto
     */
    public function notificarModificacionProducto($producto_old, $producto_new, $usuario = null) {
        // Método simplificado que no depende de ConfigNotificaciones
        return true; // Por ahora, simplemente retornamos true para evitar errores
    }
    
    /**
     * Notificación de stock bajo
     */
    public function notificarStockBajo($producto_id, $producto_nombre, $stock_actual, $stock_minimo = null) {
        // Evitar spam de notificaciones - solo crear si no existe una reciente
        if ($this->existeNotificacionReciente('stock_bajo', ['producto_id' => $producto_id], 2)) {
            return false;
        }
        
        return $this->crearNotificacion('stock_bajo', [
            'producto_id' => $producto_id,
            'producto_nombre' => $producto_nombre,
            'stock_actual' => $stock_actual,
            'stock_minimo' => $stock_minimo ?? $this->obtenerUmbralStockBajo()
        ]);
    }
    
    /**
     * Notificación de stock crítico
     */
    public function notificarStockCritico($producto_id, $producto_nombre, $stock_actual) {
        // Evitar spam de notificaciones
        if ($this->existeNotificacionReciente('stock_critico', ['producto_id' => $producto_id], 6)) {
            return false;
        }
        
        return $this->crearNotificacion('stock_critico', [
            'producto_id' => $producto_id,
            'producto_nombre' => $producto_nombre,
            'stock_actual' => $stock_actual
        ]);
    }
    
    /**
     * Notificación de nueva categoría
     */
    public function notificarNuevaCategoria($categoria_id, $categoria_nombre, $usuario = null) {
        return $this->crearNotificacion('nueva_categoria', [
            'categoria_id' => $categoria_id,
            'categoria_nombre' => $categoria_nombre,
            'usuario' => $usuario ?? $this->usuario_actual['nombres'] ?? 'Usuario'
        ], $usuario);
    }
    
    /**
     * Notificación de categoría eliminada
     */
    public function notificarCategoriaEliminada($categoria_id, $categoria_nombre, $usuario = null) {
        return $this->crearNotificacion('categoria_eliminada', [
            'categoria_id' => $categoria_id,
            'categoria_nombre' => $categoria_nombre,
            'usuario' => $usuario ?? $this->usuario_actual['nombres'] ?? 'Usuario'
        ], $usuario);
    }
    
    /**
     * Notificación de nueva subcategoría
     */
    public function notificarNuevaSubcategoria($subcategoria_id, $subcategoria_nombre, $categoria_nombre, $usuario = null) {
        return $this->crearNotificacion('nueva_subcategoria', [
            'subcategoria_id' => $subcategoria_id,
            'subcategoria_nombre' => $subcategoria_nombre,
            'categoria_nombre' => $categoria_nombre,
            'usuario' => $usuario ?? $this->usuario_actual['nombres'] ?? 'Usuario'
        ], $usuario);
    }
    
    /**
     * Notificación de subcategoría eliminada
     */
    public function notificarSubcategoriaEliminada($subcategoria_nombre, $usuario = null) {
        return $this->crearNotificacion('subcategoria_eliminada', [
            'subcategoria_nombre' => $subcategoria_nombre,
            'usuario' => $usuario ?? $this->usuario_actual['nombres'] ?? 'Usuario'
        ], $usuario);
    }
    
    /**
     * Notificación de nuevo proveedor
     */
    public function notificarNuevoProveedor($proveedor_nit, $proveedor_nombre, $usuario = null) {
        return $this->crearNotificacion('nuevo_proveedor', [
            'proveedor_nit' => $proveedor_nit,
            'proveedor_nombre' => $proveedor_nombre,
            'usuario' => $usuario ?? $this->usuario_actual['nombres'] ?? 'Usuario'
        ], $usuario);
    }
    
    /**
     * Notificación de proveedor eliminado
     */
    public function notificarProveedorEliminado($proveedor_nit, $proveedor_nombre, $usuario = null) {
        return $this->crearNotificacion('proveedor_eliminado', [
            'proveedor_nit' => $proveedor_nit,
            'proveedor_nombre' => $proveedor_nombre,
            'usuario' => $usuario ?? $this->usuario_actual['nombres'] ?? 'Usuario'
        ], $usuario);
    }
    
    /**
     * Notificación de salida eliminada
     */
    public function notificarSalidaEliminada($producto_nombre, $cantidad, $usuario = null) {
        return $this->crearNotificacion('salida_eliminada', [
            'producto_nombre' => $producto_nombre,
            'cantidad' => $cantidad,
            'usuario' => $usuario ?? $this->usuario_actual['nombres'] ?? 'Usuario'
        ], $usuario);
    }
    
    /**
     * Notificación de salida importante (cantidad alta)
     */
    public function notificarSalidaImportante($producto_nombre, $cantidad, $motivo, $usuario = null) {
        return $this->crearNotificacion('salida_importante', [
            'producto_nombre' => $producto_nombre,
            'cantidad' => $cantidad,
            'motivo' => $motivo,
            'usuario' => $usuario ?? $this->usuario_actual['nombres'] ?? 'Usuario'
        ], $usuario);
    }
    
    /**
     * Notificación de usuario eliminado
     */
    public function notificarUsuarioEliminado($usuario_doc, $usuario_nombre, $usuario_admin = null) {
        return $this->crearNotificacion('usuario_eliminado', [
            'usuario_doc' => $usuario_doc,
            'usuario_nombre' => $usuario_nombre,
            'usuario_admin' => $usuario_admin ?? $this->usuario_actual['nombres'] ?? 'Administrador'
        ], $usuario_admin);
    }
    
    /**
     * Verificar automáticamente el stock bajo de todos los productos
     */
    public function verificarStockBajo() {
        try {
            $umbral = $this->obtenerUmbralStockBajo();
            
            $sql = "SELECT id_prod, nombre, stock 
                    FROM Productos 
                    WHERE stock <= ? AND stock > 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $umbral);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $productos_notificados = 0;
            
            while ($producto = $result->fetch_assoc()) {
                if ($this->notificarStockBajo(
                    $producto['id_prod'], 
                    $producto['nombre'], 
                    $producto['stock'], 
                    $umbral
                )) {
                    $productos_notificados++;
                }
            }
            
            $stmt->close();
            
            // Verificar stock crítico (0 unidades)
            $sql_critico = "SELECT id_prod, nombre, stock 
                           FROM Productos 
                           WHERE stock = 0";
            
            $result_critico = $this->db->query($sql_critico);
            
            while ($producto = $result_critico->fetch_assoc()) {
                if ($this->notificarStockCritico(
                    $producto['id_prod'], 
                    $producto['nombre'], 
                    $producto['stock']
                )) {
                    $productos_notificados++;
                }
            }
            
            return $productos_notificados;
            
        } catch (Exception $e) {
            error_log("[InventiXor] Error verificando stock bajo: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Métodos auxiliares privados
     */
    
    private function obtenerConfiguracion($tipo_evento) {
        try {
            $sql = "SELECT * FROM ConfigNotificaciones WHERE tipo_evento = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $tipo_evento);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $config = $result->fetch_assoc();
            $stmt->close();
            
            return $config;
            
        } catch (Exception $e) {
            error_log("[InventiXor] Error obteniendo configuración: " . $e->getMessage());
            return null;
        }
    }
    
    private function procesarTemplate($template, $datos) {
        $resultado = $template;
        
        foreach ($datos as $clave => $valor) {
            $resultado = str_replace('{' . $clave . '}', $valor, $resultado);
        }
        
        return $resultado;
    }
    
    private function obtenerUmbralStockBajo() {
        try {
            $sql = "SELECT umbral_stock_bajo FROM ConfigNotificaciones WHERE tipo_evento = 'stock_bajo'";
            $result = $this->db->query($sql);
            $row = $result->fetch_assoc();
            
            return $row ? $row['umbral_stock_bajo'] : 10;
            
        } catch (Exception $e) {
            return 10; // Valor por defecto
        }
    }
    
    private function existeNotificacionReciente($tipo_evento, $datos_buscar, $horas = 2) {
        try {
            $sql = "SELECT COUNT(*) as count FROM NotificacionesSistema 
                    WHERE tipo_evento = ? 
                    AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL ? HOUR)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $tipo_evento, $horas);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return $row['count'] > 0;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Limpiar notificaciones antiguas manualmente
     */
    public function limpiarNotificacionesAntiguas() {
        try {
            $this->db->query("CALL LimpiarNotificacionesAntiguas()");
            return true;
        } catch (Exception $e) {
            error_log("[InventiXor] Error limpiando notificaciones: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Métodos de conveniencia que adaptan las llamadas a los métodos existentes
     */
    
    /**
     * Notificar eliminación de producto
     */
    public function notificarEliminacionProducto($producto, $usuario_creador = null) {
        return $this->notificarProductoEliminado(
            $producto['id_prod'] ?? $producto['id'], 
            $producto['nombre'] ?? $producto['nombre_prod'], 
            $usuario_creador
        );
    }
    
    /**
     * Notificar eliminación de categoría
     */
    public function notificarEliminacionCategoria($categoria, $usuario_creador = null) {
        return $this->crearNotificacion('categoria_eliminada', [
            'categoria_id' => $categoria['id_categ'],
            'categoria_nombre' => $categoria['nombre'],
            'usuario' => $usuario_creador
        ], $usuario_creador);
    }
    
    /**
     * Notificar eliminación de subcategoría
     */
    public function notificarEliminacionSubcategoria($subcategoria, $usuario_creador = null) {
        return $this->crearNotificacion('subcategoria_eliminada', [
            'subcategoria_id' => $subcategoria['id_subcg'],
            'subcategoria_nombre' => $subcategoria['nombre'],
            'usuario' => $usuario_creador
        ], $usuario_creador);
    }
    
    /**
     * Notificar eliminación de proveedor
     */
    public function notificarEliminacionProveedor($proveedor, $usuario_creador = null) {
        return $this->crearNotificacion('proveedor_eliminado', [
            'proveedor_id' => $proveedor['id_nit'],
            'proveedor_nombre' => $proveedor['razon_social'],
            'usuario' => $usuario_creador
        ], $usuario_creador);
    }
    
    /**
     * Notificar eliminación de salida
     */
    public function notificarEliminacionSalida($salida, $usuario_creador = null) {
        return $this->crearNotificacion('salida_eliminada', [
            'salida_id' => $salida['id_salida'],
            'producto_nombre' => $salida['nombre'],
            'cantidad' => $salida['cantidad'],
            'usuario' => $usuario_creador
        ], $usuario_creador);
    }
}

/**
 * Función helper para crear instancia del sistema de notificaciones
 */
function obtenerSistemaNotificaciones($db, $usuario = null) {
    return new SistemaNotificaciones($db, $usuario);
}

/**
 * Función helper para verificar stock bajo automáticamente
 * (puede ser llamada desde cron job o script de mantenimiento)
 */
function verificarStockBajoSistema() {
    try {
        require_once __DIR__ . '/Database.php';
        
        $db = new Database();
        $sistema = new SistemaNotificaciones($db);
        
        $productos_notificados = $sistema->verificarStockBajo();
        
        if ($productos_notificados > 0) {
            error_log("[InventiXor] Verificación de stock: $productos_notificados notificaciones creadas");
        }
        
        // También limpiar notificaciones antiguas
        $sistema->limpiarNotificacionesAntiguas();
        
        return $productos_notificados;
        
    } catch (Exception $e) {
        error_log("[InventiXor] Error en verificación automática de stock: " . $e->getMessage());
        return 0;
    }
}
?>
