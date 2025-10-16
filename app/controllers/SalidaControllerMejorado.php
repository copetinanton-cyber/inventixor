<?php
require_once __DIR__ . '/../models/SalidaMejorada.php';
require_once __DIR__ . '/../helpers/Database.php';

class SalidaControllerMejorado {
    private $salidaModel;
    private $db;
    
    public function __construct() {
        $this->db = new Database();
        $this->salidaModel = new SalidaMejorada($this->db);
    }
    
    /**
     * Procesar registro de salida mejorada
     */
    public function registrarSalida($datos) {
        try {
            // Validar datos básicos
            $errores = $this->validarDatosSalida($datos);
            if (!empty($errores)) {
                return ['success' => false, 'errores' => $errores];
            }
            
            // Verificar stock disponible
            if (!$this->verificarStock($datos['id_prod'], $datos['cantidad'])) {
                return ['success' => false, 'error' => 'Stock insuficiente'];
            }
            
            // Registrar salida
            $id_salida = $this->salidaModel->registrarSalidaCompleta($datos);
            
            return [
                'success' => true, 
                'id_salida' => $id_salida,
                'message' => 'Salida registrada exitosamente con seguimiento automático'
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Actualizar estado de seguimiento
     */
    public function actualizarSeguimiento($id_salida, $datos) {
        try {
            $success = $this->salidaModel->actualizarSeguimiento(
                $id_salida,
                $datos['nuevo_estado'],
                $datos['observaciones'] ?? '',
                $datos['usuario'] ?? ''
            );
            
            if ($success) {
                // Si el estado es 'entregado', actualizar fecha de entrega
                if ($datos['nuevo_estado'] === 'entregado') {
                    $this->actualizarFechaEntrega($id_salida);
                }
                
                return ['success' => true, 'message' => 'Estado actualizado correctamente'];
            } else {
                return ['success' => false, 'error' => 'Error al actualizar el estado'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Procesar devolución
     */
    public function procesarDevolucion($datos) {
        try {
            // Validar datos de devolución
            $errores = $this->validarDatosDevolucion($datos);
            if (!empty($errores)) {
                return ['success' => false, 'errores' => $errores];
            }
            
            // Registrar devolución
            $id_devolucion = $this->salidaModel->registrarDevolucion($datos);
            
            $message = 'Devolución registrada correctamente';
            if ($datos['accion'] === 'reingresar_inventario') {
                $message .= ' - Stock actualizado';
            }
            
            return [
                'success' => true,
                'id_devolucion' => $id_devolucion,
                'message' => $message
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener dashboard de salidas
     */
    public function getDashboardSalidas() {
        try {
            $stats = [];
            
            // Salidas del día
            $stmt = $this->db->conn->prepare("
                SELECT COUNT(*) as total, COALESCE(SUM(CAST(cantidad AS UNSIGNED)), 0) as cantidad_total
                FROM Salidas 
                WHERE DATE(fecha_hora) = CURDATE()
            ");
            $stmt->execute();
            $stats['hoy'] = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            // Productos en tránsito
            $stats['en_transito'] = count($this->salidaModel->getProductosEnTransito());
            
            // Garantías activas
            $stats['garantias_activas'] = count($this->salidaModel->getProductosConGarantia());
            
            // Devoluciones del mes
            $stmt = $this->db->conn->prepare("
                SELECT COUNT(*) as total 
                FROM Devoluciones 
                WHERE MONTH(fecha_devolucion) = MONTH(CURDATE()) 
                AND YEAR(fecha_devolucion) = YEAR(CURDATE())
            ");
            $stmt->execute();
            $stats['devoluciones_mes'] = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();
            
            // Salidas por tipo (últimos 30 días)
            $stmt = $this->db->conn->prepare("
                SELECT tipo_salida, COUNT(*) as cantidad
                FROM Salidas 
                WHERE fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY tipo_salida
                ORDER BY cantidad DESC
            ");
            $stmt->execute();
            $stats['tipos_salida'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return ['success' => true, 'stats' => $stats];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener salidas con filtros
     */
    public function getSalidasFiltradas($filtros) {
        try {
            $salidas = $this->salidaModel->getSalidasConSeguimiento($filtros);
            
            // Enriquecer datos con información adicional
            foreach ($salidas as &$salida) {
                $salida['historial_seguimiento'] = $this->salidaModel->getHistorialSeguimiento($salida['id_salida']);
                $salida['puede_devolver'] = $this->puedeDevolver($salida);
                $salida['tiempo_transcurrido'] = $this->calcularTiempoTranscurrido($salida['fecha_salida']);
            }
            
            return ['success' => true, 'salidas' => $salidas];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Validar datos de salida
     */
    private function validarDatosSalida($datos) {
        $errores = [];
        
        if (empty($datos['id_prod'])) {
            $errores[] = 'Debe seleccionar un producto';
        }
        
        if (empty($datos['cantidad']) || $datos['cantidad'] <= 0) {
            $errores[] = 'La cantidad debe ser mayor a cero';
        }
        
        if (empty($datos['tipo_salida'])) {
            $errores[] = 'Debe seleccionar un tipo de salida';
        }
        
        // Validar tipo de salida válido
        $tipos_validos = array_column($this->salidaModel->getTiposSalida(), 'codigo');
        if (!in_array($datos['tipo_salida'], $tipos_validos)) {
            $errores[] = 'Tipo de salida no válido';
        }
        
        return $errores;
    }
    
    /**
     * Validar datos de devolución
     */
    private function validarDatosDevolucion($datos) {
        $errores = [];
        
        if (empty($datos['id_salida'])) {
            $errores[] = 'ID de salida requerido';
        }
        
        if (empty($datos['cantidad_devuelta']) || $datos['cantidad_devuelta'] <= 0) {
            $errores[] = 'Cantidad a devolver debe ser mayor a cero';
        }
        
        if (empty($datos['motivo'])) {
            $errores[] = 'Debe especificar el motivo de la devolución';
        }
        
        if (empty($datos['condicion_producto'])) {
            $errores[] = 'Debe especificar la condición del producto';
        }
        
        if (empty($datos['accion'])) {
            $errores[] = 'Debe especificar la acción a tomar';
        }
        
        return $errores;
    }
    
    /**
     * Verificar stock disponible
     */
    private function verificarStock($id_prod, $cantidad_solicitada) {
        $stmt = $this->db->conn->prepare("SELECT stock FROM Productos WHERE id_prod = ?");
        $stmt->bind_param('i', $id_prod);
        $stmt->execute();
        $result = $stmt->get_result();
        $producto = $result->fetch_assoc();
        $stmt->close();
        
        if (!$producto) {
            return false;
        }
        
        return (int)$producto['stock'] >= $cantidad_solicitada;
    }
    
    /**
     * Actualizar fecha de entrega
     */
    private function actualizarFechaEntrega($id_salida) {
        $stmt = $this->db->conn->prepare("
            UPDATE Salidas 
            SET fecha_entrega = NOW() 
            WHERE id_salida = ? AND fecha_entrega IS NULL
        ");
        $stmt->bind_param('i', $id_salida);
        $stmt->execute();
        $stmt->close();
        
        // También actualizar en ProductosTransito si existe
        $stmt2 = $this->db->conn->prepare("
            UPDATE ProductosTransito 
            SET fecha_entrega_real = NOW(), estado = 'entregado'
            WHERE id_salida = ? AND fecha_entrega_real IS NULL
        ");
        $stmt2->bind_param('i', $id_salida);
        $stmt2->execute();
        $stmt2->close();
    }
    
    /**
     * Determinar si una salida puede ser devuelta
     */
    private function puedeDevolver($salida) {
        // Reglas de negocio para devoluciones
        $tipos_no_devolvibles = ['producto_dañado', 'perdida', 'uso_interno', 'muestra_gratuita'];
        
        if (in_array($salida['tipo_salida'], $tipos_no_devolvibles)) {
            return false;
        }
        
        // No se puede devolver si ya fue devuelto
        if ($salida['estado_seguimiento'] === 'devuelto') {
            return false;
        }
        
        // Verificar si ya existe una devolución
        $stmt = $this->db->conn->prepare("SELECT COUNT(*) as count FROM Devoluciones WHERE id_salida = ?");
        $stmt->bind_param('i', $salida['id_salida']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $result['count'] == 0;
    }
    
    /**
     * Calcular tiempo transcurrido desde la salida
     */
    private function calcularTiempoTranscurrido($fecha_salida) {
        $fecha = new DateTime($fecha_salida);
        $ahora = new DateTime();
        $intervalo = $ahora->diff($fecha);
        
        if ($intervalo->days == 0) {
            if ($intervalo->h == 0) {
                return $intervalo->i . ' minuto(s)';
            }
            return $intervalo->h . ' hora(s)';
        } elseif ($intervalo->days < 30) {
            return $intervalo->days . ' día(s)';
        } else {
            $meses = floor($intervalo->days / 30);
            return $meses . ' mes(es)';
        }
    }
    
    /**
     * Generar reporte de salidas
     */
    public function generarReporte($tipo_reporte, $parametros = []) {
        try {
            switch ($tipo_reporte) {
                case 'productos_transito':
                    $datos = $this->salidaModel->getProductosEnTransito();
                    break;
                    
                case 'garantias_activas':
                    $datos = $this->salidaModel->getProductosConGarantia();
                    break;
                    
                case 'devoluciones':
                    $datos = $this->getReporteDevoluciones($parametros);
                    break;
                    
                case 'salidas_periodo':
                    $datos = $this->getReporteSalidasPeriodo($parametros);
                    break;
                    
                default:
                    throw new Exception("Tipo de reporte no válido: $tipo_reporte");
            }
            
            return ['success' => true, 'datos' => $datos];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener reporte de devoluciones
     */
    private function getReporteDevoluciones($parametros) {
        $sql = "
            SELECT d.*, p.nombre as producto_nombre, s.tipo_salida, s.fecha_hora as fecha_salida
            FROM Devoluciones d
            INNER JOIN Productos p ON d.id_prod = p.id_prod
            INNER JOIN Salidas s ON d.id_salida = s.id_salida
            WHERE 1=1
        ";
        
        $params = [];
        $types = '';
        
        if (!empty($parametros['fecha_desde'])) {
            $sql .= " AND d.fecha_devolucion >= ?";
            $params[] = $parametros['fecha_desde'];
            $types .= 's';
        }
        
        if (!empty($parametros['fecha_hasta'])) {
            $sql .= " AND d.fecha_devolucion <= ?";
            $params[] = $parametros['fecha_hasta'] . ' 23:59:59';
            $types .= 's';
        }
        
        $sql .= " ORDER BY d.fecha_devolucion DESC";
        
        if ($types) {
            $stmt = $this->db->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $datos = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $result = $this->db->conn->query($sql);
            $datos = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        return $datos;
    }
    
    /**
     * Obtener reporte de salidas por período
     */
    private function getReporteSalidasPeriodo($parametros) {
        return $this->salidaModel->getSalidasConSeguimiento($parametros);
    }
}
?>