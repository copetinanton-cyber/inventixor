<?php
/**
 * Plantillas de Reportes Predefinidos
 * Sistema de reportes prediseñados para análisis empresarial
 */

require_once 'app/helpers/Database.php';
require_once 'app/helpers/GeneradorReportes.php';

class PlantillasReportes {
    private $db;
    private $generador;
    
    public function __construct() {
        $this->db = new Database();
        $this->generador = new GeneradorReportes();
    }
    
    /**
     * Reporte de Inventario General
     */
    public function reporteInventarioGeneral() {
        $sql = "
            SELECT 
                p.id_prod as 'ID Producto',
                p.nombre as 'Nombre',
                p.modelo as 'Modelo',
                CONCAT(p.talla, ' - ', p.color) as 'Talla/Color',
                p.stock as 'Stock Actual',
                c.nombre as 'Categoría',
                sc.nombre as 'Subcategoría',
                pr.razon_social as 'Proveedor',
                p.fecha_ing as 'Fecha Ingreso',
                p.material as 'Material',
                CASE 
                    WHEN CAST(p.stock AS UNSIGNED) <= 5 THEN 'CRÍTICO'
                    WHEN CAST(p.stock AS UNSIGNED) <= 10 THEN 'BAJO'
                    WHEN CAST(p.stock AS UNSIGNED) <= 50 THEN 'NORMAL'
                    ELSE 'ALTO'
                END as 'Nivel Stock',
                DATEDIFF(CURDATE(), p.fecha_ing) as 'Días en Inventario'
            FROM Productos p
            LEFT JOIN Subcategoria sc2 ON p.id_subcg = sc2.id_subcg LEFT JOIN Categoria c ON sc2.id_categ = c.id_categ
            LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
            LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit
            ORDER BY p.stock ASC, p.nombre
        ";
        
        $result = $this->db->conn->query($sql);
        return [
            'titulo' => 'Inventario General',
            'descripcion' => 'Vista completa del inventario actual con todos los productos y su información detallada',
            'datos' => $result->fetch_all(MYSQLI_ASSOC),
            'tipo' => 'tabla',
            'categoria' => 'Inventario'
        ];
    }
    
    /**
     * Reporte de Productos con Stock Bajo
     */
    public function reporteStockBajo($limite_critico = 5, $limite_bajo = 10) {
        $sql = "
            SELECT 
                p.id_prod as 'ID',
                p.nombre as 'Producto',
                p.modelo as 'Modelo',
                p.talla as 'Talla',
                p.color as 'Color',
                p.stock as 'Stock Actual',
                c.nombre as 'Categoría',
                pr.razon_social as 'Proveedor',
                pr.contacto as 'Contacto Proveedor',
                pr.telefono as 'Teléfono',
                CASE 
                    WHEN p.stock <= ? THEN 'CRÍTICO - REABASTECER YA'
                    WHEN p.stock <= ? THEN 'BAJO - PROGRAMAR COMPRA'
                    ELSE 'NORMAL'
                END as 'Prioridad',
                COALESCE(ventas.promedio_mensual, 0) as 'Promedio Ventas/Mes',
                CASE 
                    WHEN COALESCE(ventas.promedio_mensual, 0) > 0 
                    THEN ROUND(p.stock / ventas.promedio_mensual * 30, 1)
                    ELSE 999
                END as 'Días de Stock Restante'
            FROM Productos p
            LEFT JOIN Subcategoria sc2 ON p.id_subcg = sc2.id_subcg LEFT JOIN Categoria c ON sc2.id_categ = c.id_categ
            LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit
            LEFT JOIN (
                SELECT 
                    id_prod,
                    AVG(cantidad_mensual) as promedio_mensual
                FROM (
                    SELECT 
                        id_prod,
                        DATE_FORMAT(fecha_hora, '%Y-%m') as mes,
                        SUM(cantidad) as cantidad_mensual
                    FROM Salidas 
                    WHERE fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                    GROUP BY id_prod, DATE_FORMAT(fecha_hora, '%Y-%m')
                ) subquery
                GROUP BY id_prod
            ) ventas ON p.id_prod = ventas.id_prod
            WHERE p.stock <= ?
            ORDER BY p.stock ASC, ventas.promedio_mensual DESC
        ";
        
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param('iii', $limite_critico, $limite_bajo, $limite_bajo);
        $stmt->execute();
        
        return [
            'titulo' => 'Productos con Stock Bajo',
            'descripcion' => 'Productos que requieren reabastecimiento inmediato o programado',
            'datos' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
            'tipo' => 'tabla',
            'categoria' => 'Alertas',
            'alertas' => true
        ];
    }
    
    /**
     * Reporte de Movimientos Mensuales
     */
    public function reporteMovimientosMensuales($mes = null, $anio = null) {
        $mes = $mes ?: date('m');
        $anio = $anio ?: date('Y');
        
        $sql = "
            SELECT 
                s.id_salida as 'ID Movimiento',
                s.fecha_hora as 'Fecha',
                p.nombre as 'Producto',
                p.modelo as 'Modelo',
                CONCAT(p.talla, ' - ', p.color) as 'Variante',
                s.cantidad as 'Cantidad',
                s.destino as 'Destino',
                c.nombre as 'Categoría',
                s.observaciones as 'Observaciones',
                DAYOFWEEK(s.fecha_hora) as 'Día Semana Num',
                CASE DAYOFWEEK(s.fecha_hora)
                    WHEN 1 THEN 'Domingo'
                    WHEN 2 THEN 'Lunes'
                    WHEN 3 THEN 'Martes'
                    WHEN 4 THEN 'Miércoles'
                    WHEN 5 THEN 'Jueves'
                    WHEN 6 THEN 'Viernes'
                    WHEN 7 THEN 'Sábado'
                END as 'Día de la Semana'
            FROM Salidas s
            JOIN Productos p ON s.id_prod = p.id_prod
            LEFT JOIN Subcategoria sc2 ON p.id_subcg = sc2.id_subcg LEFT JOIN Categoria c ON sc2.id_categ = c.id_categ
            WHERE MONTH(s.fecha_hora) = ? AND YEAR(s.fecha_hora) = ?
            ORDER BY s.fecha_hora DESC, s.cantidad DESC
        ";
        
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param('ii', $mes, $anio);
        $stmt->execute();
        
        // Estadísticas adicionales
        $resumen_sql = "
            SELECT 
                COUNT(*) as total_movimientos,
                SUM(CAST(s.cantidad AS UNSIGNED)) as total_unidades,
                COUNT(DISTINCT s.id_prod) as productos_diferentes,
                COUNT(DISTINCT DATE(s.fecha_hora)) as dias_con_actividad,
                AVG(CAST(s.cantidad AS UNSIGNED)) as promedio_por_movimiento
            FROM Salidas s
            WHERE MONTH(s.fecha_hora) = ? AND YEAR(s.fecha_hora) = ?
        ";
        
        $stmt_resumen = $this->db->conn->prepare($resumen_sql);
        $stmt_resumen->bind_param('ii', $mes, $anio);
        $stmt_resumen->execute();
        $resumen = $stmt_resumen->get_result()->fetch_assoc();
        
        return [
            'titulo' => "Movimientos de " . $this->getNombreMes($mes) . " $anio",
            'descripcion' => 'Análisis detallado de todos los movimientos del mes',
            'datos' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
            'resumen' => $resumen,
            'tipo' => 'tabla',
            'categoria' => 'Movimientos'
        ];
    }
    
    /**
     * Reporte de Performance de Proveedores
     */
    public function reportePerformanceProveedores() {
        $datos = $this->generador->performanceProveedores();
        
        // Calcular rankings
        usort($datos, function($a, $b) {
            return $b['rotacion_promedio'] <=> $a['rotacion_promedio'];
        });
        
        foreach ($datos as $index => &$proveedor) {
            $proveedor['ranking'] = $index + 1;
            
            // Clasificación de performance
            if ($proveedor['porcentaje_stock_bajo'] <= 10) {
                $proveedor['clasificacion'] = 'EXCELENTE';
            } elseif ($proveedor['porcentaje_stock_bajo'] <= 20) {
                $proveedor['clasificacion'] = 'BUENO';
            } elseif ($proveedor['porcentaje_stock_bajo'] <= 35) {
                $proveedor['clasificacion'] = 'REGULAR';
            } else {
                $proveedor['clasificacion'] = 'NECESITA MEJORA';
            }
        }
        
        return [
            'titulo' => 'Performance de Proveedores',
            'descripcion' => 'Análisis del rendimiento y eficiencia de todos los proveedores',
            'datos' => $datos,
            'tipo' => 'tabla',
            'categoria' => 'Proveedores',
            'ranking' => true
        ];
    }
    
    /**
     * Reporte de Top Productos Más Movidos
     */
    public function reporteTopProductos($limite = 20, $dias = 30) {
        $datos = $this->generador->topProductosDemanda($limite, $dias);
        
        return [
            'titulo' => "Top $limite Productos Más Movidos (Últimos $dias días)",
            'descripcion' => 'Productos con mayor rotación y demanda en el período',
            'datos' => $datos,
            'tipo' => 'tabla',
            'categoria' => 'Análisis',
            'destacado' => true
        ];
    }
    
    /**
     * Reporte de Pronóstico de Demanda
     */
    public function reportePronosticoDemanda() {
        $sql = "
            SELECT 
                p.id_prod,
                p.nombre as 'Producto',
                p.stock as 'Stock Actual',
                historico.promedio_mensual as 'Demanda Promedio/Mes',
                historico.tendencia as 'Tendencia',
                CASE 
                    WHEN historico.promedio_mensual > 0 
                    THEN ROUND(p.stock / historico.promedio_mensual, 1)
                    ELSE 999
                END as 'Meses de Stock',
                CASE 
                    WHEN historico.promedio_mensual > 0 
                    THEN GREATEST(0, ROUND(historico.promedio_mensual * 2 - p.stock, 0))
                    ELSE 0
                END as 'Stock Recomendado',
                c.nombre as 'Categoría',
                pr.razon_social as 'Proveedor'
            FROM Productos p
            LEFT JOIN (
                SELECT 
                    s.id_prod,
                    AVG(monthly.cantidad_mes) as promedio_mensual,
                    CASE 
                        WHEN COUNT(monthly.mes) >= 3 THEN
                            CASE 
                                WHEN AVG(CASE WHEN monthly.orden <= 3 THEN monthly.cantidad_mes END) > 
                                     AVG(CASE WHEN monthly.orden > 3 THEN monthly.cantidad_mes END) 
                                THEN 'DECRECIENTE'
                                WHEN AVG(CASE WHEN monthly.orden <= 3 THEN monthly.cantidad_mes END) < 
                                     AVG(CASE WHEN monthly.orden > 3 THEN monthly.cantidad_mes END) 
                                THEN 'CRECIENTE'
                                ELSE 'ESTABLE'
                            END
                        ELSE 'INSUFICIENTES DATOS'
                    END as tendencia
                FROM Salidas s
                JOIN (
                    SELECT 
                        id_prod,
                        DATE_FORMAT(fecha_hora, '%Y-%m') as mes,
                        SUM(cantidad) as cantidad_mes,
                        ROW_NUMBER() OVER (PARTITION BY id_prod ORDER BY DATE_FORMAT(fecha_hora, '%Y-%m') DESC) as orden
                    FROM Salidas 
                    WHERE fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                    GROUP BY id_prod, DATE_FORMAT(fecha_hora, '%Y-%m')
                ) monthly ON s.id_prod = monthly.id_prod
                GROUP BY s.id_prod
            ) historico ON p.id_prod = historico.id_prod
            LEFT JOIN Subcategoria sc2 ON p.id_subcg = sc2.id_subcg LEFT JOIN Categoria c ON sc2.id_categ = c.id_categ
            LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit
            WHERE historico.promedio_mensual IS NOT NULL
            ORDER BY historico.promedio_mensual DESC
        ";
        
        $result = $this->db->conn->query($sql);
        
        return [
            'titulo' => 'Pronóstico de Demanda',
            'descripcion' => 'Predicción de demanda futura basada en histórico de movimientos',
            'datos' => $result->fetch_all(MYSQLI_ASSOC),
            'tipo' => 'tabla',
            'categoria' => 'Predicción',
            'pronostico' => true
        ];
    }
    
    /**
     * Reporte de Análisis ABC
     */
    public function reporteAnalisisABC() {
        $datos = $this->generador->analisisABC();
        
        return [
            'titulo' => 'Análisis ABC de Inventario',
            'descripcion' => 'Clasificación de productos por importancia económica (Pareto 80/20)',
            'datos' => $datos,
            'tipo' => 'tabla',
            'categoria' => 'Análisis',
            'abc' => true
        ];
    }
    
    /**
     * Reporte de Productos Sin Movimiento
     */
    public function reporteProductosSinMovimiento($dias = 90) {
        $sql = "
            SELECT 
                p.id_prod as 'ID',
                p.nombre as 'Producto',
                p.modelo as 'Modelo',
                CONCAT(p.talla, ' - ', p.color) as 'Variante',
                p.stock as 'Stock',
                p.fecha_ing as 'Fecha Ingreso',
                DATEDIFF(CURDATE(), p.fecha_ing) as 'Días sin Vender',
                c.nombre as 'Categoría',
                pr.razon_social as 'Proveedor',
                p.stock * 1000 as 'Valor Estimado Inmovilizado',
                CASE 
                    WHEN DATEDIFF(CURDATE(), p.fecha_ing) > 365 THEN 'REVISAR ELIMINACIÓN'
                    WHEN DATEDIFF(CURDATE(), p.fecha_ing) > 180 THEN 'PROMOCIONAR'
                    ELSE 'MONITOREAR'
                END as 'Acción Recomendada'
            FROM Productos p
            LEFT JOIN Subcategoria sc2 ON p.id_subcg = sc2.id_subcg LEFT JOIN Categoria c ON sc2.id_categ = c.id_categ
            LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit
            LEFT JOIN Salidas s ON p.id_prod = s.id_prod 
                AND s.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            WHERE s.id_prod IS NULL AND p.stock > 0
            ORDER BY DATEDIFF(CURDATE(), p.fecha_ing) DESC, p.stock DESC
        ";
        
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param('i', $dias);
        $stmt->execute();
        
        return [
            'titulo' => "Productos Sin Movimiento (Últimos $dias días)",
            'descripcion' => 'Productos que no han tenido salidas y pueden requerir acción comercial',
            'datos' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
            'tipo' => 'tabla',
            'categoria' => 'Alertas',
            'sin_movimiento' => true
        ];
    }
    
    /**
     * Reporte de Resumen Ejecutivo
     */
    public function reporteResumenEjecutivo() {
        $metricas = $this->generador->dashboardMetricas();
        
        // KPIs adicionales
        $kpis_sql = "
            SELECT 
                (SELECT COUNT(*) FROM Productos WHERE stock > 0) as productos_activos,
                (SELECT COUNT(*) FROM Proveedores WHERE estado = 'activo') as proveedores_activos,
                (SELECT COUNT(*) FROM Categoria) as categorias_total,
                (SELECT SUM(cantidad) FROM Salidas WHERE fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as movimientos_mes,
                (SELECT AVG(stock) FROM Productos WHERE stock > 0) as stock_promedio,
                (SELECT COUNT(*) FROM Productos WHERE stock <= 10) as productos_atencion
        ";
        
        $kpis = $this->db->conn->query($kpis_sql)->fetch_assoc();
        
        return [
            'titulo' => 'Resumen Ejecutivo',
            'descripcion' => 'Vista gerencial con los indicadores clave del negocio',
            'metricas' => $metricas,
            'kpis' => $kpis,
            'tipo' => 'dashboard',
            'categoria' => 'Ejecutivo'
        ];
    }
    
    /**
     * Obtener todos los reportes disponibles
     */
    public function getReportesDisponibles() {
        return [
            'inventario_general' => [
                'nombre' => 'Inventario General',
                'descripcion' => 'Vista completa del inventario actual',
                'icono' => 'fas fa-warehouse',
                'categoria' => 'Inventario',
                'metodo' => 'reporteInventarioGeneral'
            ],
            'stock_bajo' => [
                'nombre' => 'Productos con Stock Bajo',
                'descripcion' => 'Productos que requieren reabastecimiento',
                'icono' => 'fas fa-exclamation-triangle',
                'categoria' => 'Alertas',
                'metodo' => 'reporteStockBajo'
            ],
            'movimientos_mensuales' => [
                'nombre' => 'Movimientos del Mes',
                'descripcion' => 'Análisis de entradas y salidas mensuales',
                'icono' => 'fas fa-chart-line',
                'categoria' => 'Movimientos',
                'metodo' => 'reporteMovimientosMensuales'
            ],
            'performance_proveedores' => [
                'nombre' => 'Performance de Proveedores',
                'descripcion' => 'Análisis de rendimiento de proveedores',
                'icono' => 'fas fa-truck',
                'categoria' => 'Proveedores',
                'metodo' => 'reportePerformanceProveedores'
            ],
            'top_productos' => [
                'nombre' => 'Top Productos Más Movidos',
                'descripcion' => 'Productos con mayor rotación',
                'icono' => 'fas fa-trophy',
                'categoria' => 'Análisis',
                'metodo' => 'reporteTopProductos'
            ],
            'pronostico_demanda' => [
                'nombre' => 'Pronóstico de Demanda',
                'descripcion' => 'Predicción basada en histórico',
                'icono' => 'fas fa-crystal-ball',
                'categoria' => 'Predicción',
                'metodo' => 'reportePronosticoDemanda'
            ],
            'analisis_abc' => [
                'nombre' => 'Análisis ABC',
                'descripcion' => 'Clasificación por importancia económica',
                'icono' => 'fas fa-sort-amount-down',
                'categoria' => 'Análisis',
                'metodo' => 'reporteAnalisisABC'
            ],
            'sin_movimiento' => [
                'nombre' => 'Productos Sin Movimiento',
                'descripcion' => 'Stock inmovilizado que requiere atención',
                'icono' => 'fas fa-pause-circle',
                'categoria' => 'Alertas',
                'metodo' => 'reporteProductosSinMovimiento'
            ],
            'resumen_ejecutivo' => [
                'nombre' => 'Resumen Ejecutivo',
                'descripcion' => 'Vista gerencial con KPIs principales',
                'icono' => 'fas fa-chart-pie',
                'categoria' => 'Ejecutivo',
                'metodo' => 'reporteResumenEjecutivo'
            ]
        ];
    }
    
    /**
     * Ejecutar reporte específico
     */
    public function ejecutarReporte($reporte_id, $parametros = []) {
        $reportes = $this->getReportesDisponibles();
        
        if (!isset($reportes[$reporte_id])) {
            throw new Exception("Reporte no encontrado: $reporte_id");
        }
        
        $metodo = $reportes[$reporte_id]['metodo'];
        
        if (!method_exists($this, $metodo)) {
            throw new Exception("Método no implementado: $metodo");
        }
        
        // Ejecutar con parámetros si los hay
        if (!empty($parametros)) {
            return call_user_func_array([$this, $metodo], $parametros);
        } else {
            return $this->$metodo();
        }
    }
    
    /**
     * Utilidad: Obtener nombre del mes
     */
    private function getNombreMes($numero_mes) {
        $meses = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
        ];
        
        return $meses[str_pad($numero_mes, 2, '0', STR_PAD_LEFT)] ?? 'Mes Desconocido';
    }
}
?>