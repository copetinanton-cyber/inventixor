<?php
/**
 * Generador de Reportes Dinámicos
 * Sistema avanzado para generar reportes personalizados con múltiples formatos
 */

// Incluir Database.php si no está ya incluido
if (!class_exists('Database')) {
    require_once __DIR__ . '/Database.php';
}

class GeneradorReportes {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Genera reporte de inventario con análisis avanzado
     */
    public function reporteInventarioAvanzado($filtros = []) {
        $sql = "
            SELECT 
                p.id_prod,
                p.nombre,
                p.modelo,
                p.talla,
                p.color,
                p.stock,
                p.fecha_ing,
                c.nombre as categoria,
                sc.nombre as subcategoria,
                pr.razon_social as proveedor,
                CASE 
                    WHEN CAST(p.stock AS UNSIGNED) <= 5 THEN 'CRÍTICO'
                    WHEN CAST(p.stock AS UNSIGNED) <= 10 THEN 'BAJO'
                    WHEN CAST(p.stock AS UNSIGNED) <= 50 THEN 'NORMAL'
                    ELSE 'ALTO'
                END as nivel_stock,
                DATEDIFF(CURDATE(), p.fecha_ing) as dias_inventario,
                COALESCE(movimientos.total_movimientos, 0) as total_movimientos,
                COALESCE(movimientos.ultimo_movimiento, 'Nunca') as ultimo_movimiento
            FROM Productos p
            LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
            LEFT JOIN Categoria c ON sc.id_categ = c.id_categ
            LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit
            LEFT JOIN (
                SELECT 
                    id_prod,
                    COUNT(*) as total_movimientos,
                    MAX(fecha_hora) as ultimo_movimiento
                FROM Salidas
                WHERE fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                GROUP BY id_prod
            ) movimientos ON p.id_prod = movimientos.id_prod
            WHERE 1=1
        ";
        
        $params = [];
        
        // Aplicar filtros dinámicos
        if (!empty($filtros['categoria'])) {
            $sql .= " AND c.id_categ = ?";
            $params[] = $filtros['categoria'];
        }
        
        if (!empty($filtros['nivel_stock'])) {
            switch($filtros['nivel_stock']) {
                case 'CRITICO':
                    $sql .= " AND CAST(p.stock AS UNSIGNED) <= 5";
                    break;
                case 'BAJO':
                    $sql .= " AND CAST(p.stock AS UNSIGNED) <= 10";
                    break;
                case 'NORMAL':
                    $sql .= " AND CAST(p.stock AS UNSIGNED) > 10 AND CAST(p.stock AS UNSIGNED) <= 50";
                    break;
                case 'ALTO':
                    $sql .= " AND CAST(p.stock AS UNSIGNED) > 50";
                    break;
            }
        }
        
        if (!empty($filtros['proveedor'])) {
            $sql .= " AND pr.id_nit = ?";
            $params[] = $filtros['proveedor'];
        }
        
        $sql .= " ORDER BY p.stock ASC, movimientos.total_movimientos DESC";
        
        $stmt = $this->db->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Análisis de rotación de inventario
     */
    public function analisisRotacion($periodo_dias = 90) {
        $sql = "
            SELECT 
                p.id_prod,
                p.nombre,
                p.stock as stock_actual,
                COALESCE(SUM(CAST(s.cantidad AS UNSIGNED)), 0) as total_salidas,
                COUNT(s.id_salida) as numero_movimientos,
                CASE 
                    WHEN p.stock > 0 AND COALESCE(SUM(CAST(s.cantidad AS UNSIGNED)), 0) > 0 
                    THEN ROUND((COALESCE(SUM(CAST(s.cantidad AS UNSIGNED)), 0) / p.stock) * (365 / ?), 2)
                    ELSE 0
                END as rotacion_anual,
                CASE 
                    WHEN COALESCE(SUM(CAST(s.cantidad AS UNSIGNED)), 0) > 0 
                    THEN ROUND(p.stock / (COALESCE(SUM(CAST(s.cantidad AS UNSIGNED)), 0) / ?), 1)
                    ELSE 999
                END as dias_inventario,
                MAX(s.fecha_hora) as ultima_salida,
                c.nombre as categoria
            FROM Productos p
            LEFT JOIN Salidas s ON p.id_prod = s.id_prod 
                AND s.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            LEFT JOIN Subcategoria sc2 ON p.id_subcg = sc2.id_subcg LEFT JOIN Categoria c ON sc2.id_categ = c.id_categ
            GROUP BY p.id_prod, p.nombre, p.stock, c.nombre
            HAVING total_salidas > 0 OR stock_actual > 0
            ORDER BY rotacion_anual DESC
        ";
        
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param('iii', $periodo_dias, $periodo_dias, $periodo_dias);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Reporte de performance de proveedores
     */
    public function performanceProveedores() {
        $sql = "
            SELECT 
                pr.id_nit,
                pr.razon_social,
                pr.contacto,
                pr.telefono,
                pr.correo,
                COUNT(DISTINCT p.id_prod) as productos_suministrados,
                SUM(CAST(p.stock AS UNSIGNED)) as stock_total_suministrado,
                AVG(CAST(p.stock AS UNSIGNED)) as promedio_stock,
                COALESCE(SUM(movimientos.total_movimientos), 0) as total_movimientos,
                CASE 
                    WHEN COUNT(DISTINCT p.id_prod) > 0 
                    THEN ROUND(COALESCE(SUM(movimientos.total_movimientos), 0) / COUNT(DISTINCT p.id_prod), 2)
                    ELSE 0
                END as rotacion_promedio,
                COUNT(CASE WHEN CAST(p.stock AS UNSIGNED) <= 10 THEN 1 END) as productos_stock_bajo,
                ROUND(
                    (COUNT(CASE WHEN CAST(p.stock AS UNSIGNED) <= 10 THEN 1 END) * 100.0) / 
                    NULLIF(COUNT(DISTINCT p.id_prod), 0), 2
                ) as porcentaje_stock_bajo
            FROM Proveedores pr
            LEFT JOIN Productos p ON pr.id_nit = p.id_nit
            LEFT JOIN (
                SELECT 
                    p2.id_prod,
                    COUNT(s.id_salida) as total_movimientos
                FROM Productos p2
                LEFT JOIN Salidas s ON p2.id_prod = s.id_prod
                    AND s.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                GROUP BY p2.id_prod
            ) movimientos ON p.id_prod = movimientos.id_prod
            WHERE pr.estado = 'activo'
            GROUP BY pr.id_nit, pr.razon_social, pr.contacto, pr.telefono, pr.correo
            HAVING productos_suministrados > 0
            ORDER BY rotacion_promedio DESC, stock_total_suministrado DESC
        ";
        
        $result = $this->db->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Análisis de tendencias y predicciones
     */
    public function analisisTendencias($periodo_meses = 6) {
        $sql = "
            SELECT 
                DATE_FORMAT(s.fecha_hora, '%Y-%m') as mes,
                COUNT(DISTINCT s.id_salida) as total_operaciones,
                SUM(CAST(s.cantidad AS UNSIGNED)) as total_unidades,
                COUNT(DISTINCT s.id_prod) as productos_diferentes,
                AVG(s.cantidad) as promedio_por_operacion,
                c.nombre as categoria,
                COUNT(DISTINCT c.id_categ) as categorias_activas
            FROM Salidas s
            JOIN Productos p ON s.id_prod = p.id_prod
            JOIN Subcategoria sc2 ON p.id_subcg = sc2.id_subcg JOIN Categoria c ON sc2.id_categ = c.id_categ
            WHERE s.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(s.fecha_hora, '%Y-%m'), c.nombre
            ORDER BY mes DESC, total_unidades DESC
        ";
        
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param('i', $periodo_meses);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Reporte de productos con mayor demanda
     */
    public function topProductosDemanda($limite = 20, $dias = 30) {
        $sql = "
            SELECT 
                p.id_prod,
                p.nombre,
                p.modelo,
                p.talla,
                p.color,
                p.stock as stock_actual,
                SUM(CAST(s.cantidad AS UNSIGNED)) as total_demanda,
                COUNT(s.id_salida) as frecuencia_pedidos,
                AVG(s.cantidad) as promedio_por_pedido,
                MAX(s.fecha_hora) as ultimo_pedido,
                c.nombre as categoria,
                sc.nombre as subcategoria,
                pr.razon_social as proveedor,
                CASE 
                    WHEN p.stock > 0 THEN ROUND(SUM(CAST(s.cantidad AS UNSIGNED)) / p.stock, 2)
                    ELSE 0
                END as ratio_demanda_stock,
                CASE 
                    WHEN SUM(CAST(s.cantidad AS UNSIGNED)) > p.stock * 2 THEN 'ALTA'
                    WHEN SUM(CAST(s.cantidad AS UNSIGNED)) > p.stock THEN 'MEDIA'
                    ELSE 'BAJA'
                END as intensidad_demanda
            FROM Productos p
            JOIN Salidas s ON p.id_prod = s.id_prod
            LEFT JOIN Subcategoria sc2 ON p.id_subcg = sc2.id_subcg LEFT JOIN Categoria c ON sc2.id_categ = c.id_categ
            LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
            LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit
            WHERE s.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY p.id_prod
            ORDER BY total_demanda DESC, frecuencia_pedidos DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param('ii', $dias, $limite);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Análisis ABC de inventario
     */
    public function analisisABC() {
        $sql = "
            SELECT 
                p.id_prod,
                p.nombre,
                p.stock,
                COALESCE(SUM(CAST(s.cantidad AS UNSIGNED)), 0) as total_movimientos,
                p.stock * COALESCE(AVG(s.cantidad), 1) as valor_estimado,
                c.nombre as categoria
            FROM Productos p
            LEFT JOIN Salidas s ON p.id_prod = s.id_prod
                AND s.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
            LEFT JOIN Subcategoria sc2 ON p.id_subcg = sc2.id_subcg LEFT JOIN Categoria c ON sc2.id_categ = c.id_categ
            GROUP BY p.id_prod
            ORDER BY valor_estimado DESC
        ";
        
        $result = $this->db->conn->query($sql);
        $productos = $result->fetch_all(MYSQLI_ASSOC);
        
        // Calcular clasificación ABC
        $total_valor = array_sum(array_column($productos, 'valor_estimado'));
        $acumulado = 0;
        
        foreach ($productos as &$producto) {
            $acumulado += $producto['valor_estimado'];
            $porcentaje_acumulado = ($acumulado / $total_valor) * 100;
            
            if ($porcentaje_acumulado <= 80) {
                $producto['clasificacion_abc'] = 'A';
            } elseif ($porcentaje_acumulado <= 95) {
                $producto['clasificacion_abc'] = 'B';
            } else {
                $producto['clasificacion_abc'] = 'C';
            }
            
            $producto['porcentaje_acumulado'] = round($porcentaje_acumulado, 2);
            $producto['porcentaje_individual'] = round(($producto['valor_estimado'] / $total_valor) * 100, 2);
        }
        
        return $productos;
    }
    
    /**
     * Reporte de alertas y notificaciones
     */
    public function reporteAlertas() {
        $alertas = [];
        
        // Stock crítico
        $sql_critico = "
            SELECT COUNT(*) as total FROM Productos WHERE stock <= 5
        ";
        $result = $this->db->conn->query($sql_critico);
        $stock_critico = $result->fetch_assoc()['total'];
        
        if ($stock_critico > 0) {
            $alertas[] = [
                'tipo' => 'CRÍTICO',
                'mensaje' => "$stock_critico productos en stock crítico (≤5 unidades)",
                'prioridad' => 'alta',
                'accion' => 'Reabastecer inmediatamente'
            ];
        }
        
        // Stock bajo
        $sql_bajo = "
            SELECT COUNT(*) as total FROM Productos WHERE stock > 5 AND stock <= 10
        ";
        $result = $this->db->conn->query($sql_bajo);
        $stock_bajo = $result->fetch_assoc()['total'];
        
        if ($stock_bajo > 0) {
            $alertas[] = [
                'tipo' => 'ADVERTENCIA',
                'mensaje' => "$stock_bajo productos con stock bajo (6-10 unidades)",
                'prioridad' => 'media',
                'accion' => 'Planificar reabastecimiento'
            ];
        }
        
        // Productos sin movimiento
        $sql_sin_movimiento = "
            SELECT COUNT(*) as total 
            FROM Productos p 
            LEFT JOIN Salidas s ON p.id_prod = s.id_prod 
                AND s.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
            WHERE s.id_prod IS NULL AND p.stock > 0
        ";
        $result = $this->db->conn->query($sql_sin_movimiento);
        $sin_movimiento = $result->fetch_assoc()['total'];
        
        if ($sin_movimiento > 0) {
            $alertas[] = [
                'tipo' => 'INFO',
                'mensaje' => "$sin_movimiento productos sin movimiento en 90 días",
                'prioridad' => 'baja',
                'accion' => 'Revisar estrategia comercial'
            ];
        }
        
        // Productos de alta rotación con stock bajo
        $sql_alta_rotacion = "
            SELECT COUNT(DISTINCT p.id_prod) as total
            FROM Productos p
            JOIN Salidas s ON p.id_prod = s.id_prod
            WHERE s.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                AND p.stock <= 20
            GROUP BY p.id_prod
            HAVING COUNT(s.id_salida) >= 5
        ";
        $result = $this->db->conn->query($sql_alta_rotacion);
        $alta_rotacion = $result->num_rows;
        
        if ($alta_rotacion > 0) {
            $alertas[] = [
                'tipo' => 'OPORTUNIDAD',
                'mensaje' => "$alta_rotacion productos de alta rotación necesitan más stock",
                'prioridad' => 'alta',
                'accion' => 'Aumentar stock de seguridad'
            ];
        }
        
        return $alertas;
    }
    
    /**
     * Generar dashboard de métricas clave
     */
    public function dashboardMetricas() {
        $metricas = [];
        
        // Valor total del inventario (estimado)
        $sql = "
            SELECT 
                COUNT(*) as total_productos,
                SUM(stock) as total_unidades,
                AVG(stock) as promedio_stock
            FROM Productos
        ";
        $result = $this->db->conn->query($sql);
        $inventario = $result->fetch_assoc();
        
        // Movimientos del mes actual
        $sql = "
            SELECT 
                COUNT(*) as total_movimientos,
                SUM(cantidad) as total_unidades_movidas
            FROM Salidas 
            WHERE MONTH(fecha_hora) = MONTH(CURDATE()) 
                AND YEAR(fecha_hora) = YEAR(CURDATE())
        ";
        $result = $this->db->conn->query($sql);
        $movimientos_mes = $result->fetch_assoc();
        
        // Productos por categoría
        $sql = "
            SELECT 
                c.nombre as categoria,
                COUNT(p.id_prod) as total_productos,
                SUM(CAST(p.stock AS UNSIGNED)) as stock_categoria
            FROM Categoria c
            LEFT JOIN Productos p ON c.id_categ = p.id_categ
            GROUP BY c.id_categ, c.nombre
            ORDER BY stock_categoria DESC
        ";
        $result = $this->db->conn->query($sql);
        $por_categoria = $result->fetch_all(MYSQLI_ASSOC);
        
        // Top 5 productos más movidos (últimos 30 días)
        $sql = "
            SELECT 
                p.nombre,
                SUM(CAST(s.cantidad AS UNSIGNED)) as total_movido
            FROM Productos p
            JOIN Salidas s ON p.id_prod = s.id_prod
            WHERE s.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY p.id_prod, p.nombre
            ORDER BY total_movido DESC
            LIMIT 5
        ";
        $result = $this->db->conn->query($sql);
        $top_productos = $result->fetch_all(MYSQLI_ASSOC);
        
        return [
            'inventario' => $inventario,
            'movimientos_mes' => $movimientos_mes,
            'por_categoria' => $por_categoria,
            'top_productos' => $top_productos,
            'alertas' => $this->reporteAlertas()
        ];
    }
}

// Clase para exportación de reportes
class ExportadorReportes {
    
    /**
     * Exportar a CSV
     */
    public static function exportarCSV($datos, $nombre_archivo, $cabeceras = null) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '.csv"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeceras
        if ($cabeceras && !empty($datos)) {
            fputcsv($output, $cabeceras);
        } elseif (!empty($datos)) {
            fputcsv($output, array_keys($datos[0]));
        }
        
        // Datos
        foreach ($datos as $fila) {
            fputcsv($output, $fila);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Exportar a JSON
     */
    public static function exportarJSON($datos, $nombre_archivo) {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '.json"');
        
        echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Generar HTML para impresión/PDF
     */
    public static function generarHTML($datos, $titulo, $cabeceras = null) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title><?php echo htmlspecialchars($titulo); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .header h1 { color: #333; margin: 0; }
                .header p { color: #666; margin: 5px 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
                @media print {
                    body { margin: 0; }
                    .header h1 { font-size: 18px; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo htmlspecialchars($titulo); ?></h1>
                <p>Generado el <?php echo date('d/m/Y H:i:s'); ?></p>
                <p>InventiXor - Sistema de Gestión de Inventario</p>
            </div>
            
            <?php if (!empty($datos)): ?>
            <table>
                <thead>
                    <tr>
                        <?php 
                        $cols = $cabeceras ?: array_keys($datos[0]);
                        foreach ($cols as $col): 
                        ?>
                            <th><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $col))); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($datos as $fila): ?>
                    <tr>
                        <?php foreach ($cols as $col): ?>
                            <td><?php echo htmlspecialchars($fila[$col] ?? ''); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #666; margin-top: 50px;">
                No hay datos disponibles para mostrar.
            </p>
            <?php endif; ?>
            
            <div class="footer">
                <p>Reporte generado por InventiXor | Total de registros: <?php echo count($datos); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
?>