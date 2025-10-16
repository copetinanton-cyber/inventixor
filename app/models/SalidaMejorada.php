<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../helpers/Database.php';

class SalidaMejorada {
    private $db;
    
    public function __construct($database = null) {
        $this->db = $database ?: new Database();
    }
    
    /**
     * Registrar una nueva salida con seguimiento automático
     */
    public function registrarSalidaCompleta($datos) {
        $this->db->conn->begin_transaction();
        
        try {
            // 1. Registrar la salida
            $stmt = $this->db->conn->prepare("
                INSERT INTO Salidas (id_prod, cantidad, tipo_salida, estado_salida, observacion, fecha_hora, cliente_info) 
                VALUES (?, ?, ?, 'procesando', ?, NOW(), ?)
            ");
            
            $cliente_json = json_encode($datos['cliente_info'] ?? null);
            $stmt->bind_param('iisss', 
                $datos['id_prod'], 
                $datos['cantidad'], 
                $datos['tipo_salida'], 
                $datos['observacion'],
                $cliente_json
            );
            $stmt->execute();
            $id_salida = $this->db->conn->insert_id;
            $stmt->close();
            
            // 2. Actualizar stock
            $stmt = $this->db->conn->prepare("UPDATE Productos SET stock = stock - ? WHERE id_prod = ?");
            $stmt->bind_param('ii', $datos['cantidad'], $datos['id_prod']);
            $stmt->execute();
            $stmt->close();
            
            // 3. Crear garantía si aplica
            if (!empty($datos['garantia'])) {
                $this->crearGarantia($id_salida, $datos['id_prod'], $datos['garantia']);
            }
            
            // 4. Crear registro de tránsito si es necesario
            if (in_array($datos['tipo_salida'], ['venta', 'transferencia', 'prestamo'])) {
                $this->crearRegistroTransito($id_salida, $datos);
            }
            
            $this->db->conn->commit();
            return $id_salida;
            
        } catch (Exception $e) {
            $this->db->conn->rollback();
            throw new Exception("Error al registrar salida: " . $e->getMessage());
        }
    }
    
    /**
     * Crear registro de garantía
     */
    private function crearGarantia($id_salida, $id_prod, $garantia_info) {
        $duracion = $garantia_info['duracion_meses'] ?? 12;
        $fecha_inicio = date('Y-m-d');
        $fecha_vencimiento = date('Y-m-d', strtotime("+{$duracion} months"));
        
        $stmt = $this->db->conn->prepare("
            INSERT INTO Garantias (id_salida, id_prod, tipo_garantia, duracion_meses, fecha_inicio, fecha_vencimiento, terminos)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param('iisisss', 
            $id_salida, 
            $id_prod, 
            $garantia_info['tipo'] ?? 'tienda',
            $duracion,
            $fecha_inicio,
            $fecha_vencimiento,
            $garantia_info['terminos'] ?? ''
        );
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Crear registro de tránsito
     */
    private function crearRegistroTransito($id_salida, $datos) {
        if (empty($datos['destino'])) return;
        
        $stmt = $this->db->conn->prepare("
            INSERT INTO ProductosTransito (id_salida, id_prod, destino, fecha_envio, fecha_entrega_estimada, observaciones)
            VALUES (?, ?, ?, NOW(), ?, ?)
        ");
        
        $fecha_estimada = $datos['fecha_entrega_estimada'] ?? date('Y-m-d H:i:s', strtotime('+3 days'));
        $observaciones = $datos['observaciones_transito'] ?? '';
        
        $stmt->bind_param('iisss', 
            $id_salida, 
            $datos['id_prod'], 
            $datos['destino'],
            $fecha_estimada,
            $observaciones
        );
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Actualizar estado de seguimiento
     */
    public function actualizarSeguimiento($id_salida, $nuevo_estado, $observaciones = '', $usuario = '') {
        $stmt = $this->db->conn->prepare("
            INSERT INTO ProductosSeguimiento (id_salida, estado, observaciones, usuario)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->bind_param('isss', $id_salida, $nuevo_estado, $observaciones, $usuario);
        $success = $stmt->execute();
        $stmt->close();
        
        // Actualizar estado principal de la salida
        if ($success) {
            $estado_salida = $this->mapearEstadoSalida($nuevo_estado);
            $stmt2 = $this->db->conn->prepare("UPDATE Salidas SET estado_salida = ? WHERE id_salida = ?");
            $stmt2->bind_param('si', $estado_salida, $id_salida);
            $stmt2->execute();
            $stmt2->close();
        }
        
        return $success;
    }
    
    /**
     * Mapear estado de seguimiento a estado de salida
     */
    private function mapearEstadoSalida($estado_seguimiento) {
        $mapeo = [
            'preparando' => 'procesando',
            'enviado' => 'en_transito',
            'en_transito' => 'en_transito',
            'entregado' => 'completada',
            'devuelto' => 'devuelto',
            'perdido' => 'perdido',
            'dañado' => 'dañado'
        ];
        
        return $mapeo[$estado_seguimiento] ?? 'procesando';
    }
    
    /**
     * Registrar devolución
     */
    public function registrarDevolucion($datos) {
        $this->db->conn->begin_transaction();
        
        try {
            // Insertar devolución
            $stmt = $this->db->conn->prepare("
                INSERT INTO Devoluciones 
                (id_salida, id_prod, cantidad_devuelta, motivo, condicion_producto, accion, observaciones, usuario_recibe, reingresado_stock)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $reingresado = ($datos['accion'] === 'reingresar_inventario') ? 1 : 0;
            
            $stmt->bind_param('iiisssssi',
                $datos['id_salida'],
                $datos['id_prod'],
                $datos['cantidad_devuelta'],
                $datos['motivo'],
                $datos['condicion_producto'],
                $datos['accion'],
                $datos['observaciones'],
                $datos['usuario_recibe'],
                $reingresado
            );
            
            $stmt->execute();
            $id_devolucion = $this->db->conn->insert_id;
            $stmt->close();
            
            // Actualizar seguimiento
            $this->actualizarSeguimiento(
                $datos['id_salida'], 
                'devuelto', 
                "Devolución registrada: " . $datos['motivo'],
                $datos['usuario_recibe']
            );
            
            $this->db->conn->commit();
            return $id_devolucion;
            
        } catch (Exception $e) {
            $this->db->conn->rollback();
            throw new Exception("Error al registrar devolución: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener salidas con seguimiento completo
     */
    public function getSalidasConSeguimiento($filtros = []) {
        $sql = "SELECT * FROM vista_salidas_completa WHERE 1=1";
        $params = [];
        $types = '';
        
        if (!empty($filtros['producto'])) {
            $sql .= " AND producto_nombre LIKE ?";
            $params[] = "%" . $filtros['producto'] . "%";
            $types .= 's';
        }
        
        if (!empty($filtros['tipo_salida'])) {
            $sql .= " AND tipo_salida = ?";
            $params[] = $filtros['tipo_salida'];
            $types .= 's';
        }
        
        if (!empty($filtros['estado'])) {
            $sql .= " AND estado_seguimiento = ?";
            $params[] = $filtros['estado'];
            $types .= 's';
        }
        
        if (!empty($filtros['fecha_desde'])) {
            $sql .= " AND fecha_salida >= ?";
            $params[] = $filtros['fecha_desde'];
            $types .= 's';
        }
        
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= " AND fecha_salida <= ?";
            $params[] = $filtros['fecha_hasta'] . ' 23:59:59';
            $types .= 's';
        }
        
        $sql .= " ORDER BY fecha_salida DESC";
        
        if ($types) {
            $stmt = $this->db->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $salidas = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $result = $this->db->conn->query($sql);
            $salidas = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        return $salidas;
    }
    
    /**
     * Obtener historial de seguimiento de una salida
     */
    public function getHistorialSeguimiento($id_salida) {
        $stmt = $this->db->conn->prepare("
            SELECT * FROM ProductosSeguimiento 
            WHERE id_salida = ? 
            ORDER BY fecha_estado DESC
        ");
        $stmt->bind_param('i', $id_salida);
        $stmt->execute();
        $result = $stmt->get_result();
        $historial = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $historial;
    }
    
    /**
     * Obtener productos con garantía activa
     */
    public function getProductosConGarantia() {
        $sql = "
            SELECT g.*, p.nombre as producto_nombre, s.fecha_hora as fecha_salida
            FROM Garantias g
            INNER JOIN Productos p ON g.id_prod = p.id_prod
            INNER JOIN Salidas s ON g.id_salida = s.id_salida
            WHERE g.estado = 'activa' AND g.fecha_vencimiento > CURDATE()
            ORDER BY g.fecha_vencimiento ASC
        ";
        
        $result = $this->db->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Obtener productos en tránsito
     */
    public function getProductosEnTransito() {
        $sql = "
            SELECT pt.*, p.nombre as producto_nombre, s.tipo_salida
            FROM ProductosTransito pt
            INNER JOIN Productos p ON pt.id_prod = p.id_prod  
            INNER JOIN Salidas s ON pt.id_salida = s.id_salida
            WHERE pt.estado IN ('preparando', 'enviado', 'en_transito')
            ORDER BY pt.fecha_entrega_estimada ASC
        ";
        
        $result = $this->db->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Obtener tipos de salida disponibles
     */
    public function getTiposSalida() {
        $result = $this->db->conn->query("
            SELECT * FROM TiposSalida 
            WHERE activo = TRUE 
            ORDER BY nombre ASC
        ");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>