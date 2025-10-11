<?php
/**
 * API de Reportes - Endpoint para generar reportes dinámicos
 */

session_start();
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once '../app/helpers/GeneradorReportes.php';

$generador = new GeneradorReportes();
$usuario = $_SESSION['user'];

// Verificar permisos
$es_admin = $usuario['rol'] === 'admin';
$es_coordinador = $usuario['rol'] === 'coordinador' || $es_admin;

if (!$es_coordinador) {
    http_response_code(403);
    echo json_encode(['error' => 'Permisos insuficientes']);
    exit;
}

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'inventario_avanzado':
            $filtros = [
                'categoria' => $_GET['categoria'] ?? null,
                'nivel_stock' => $_GET['nivel_stock'] ?? null,
                'proveedor' => $_GET['proveedor'] ?? null
            ];
            $datos = $generador->reporteInventarioAvanzado($filtros);
            echo json_encode([
                'success' => true,
                'datos' => $datos,
                'total' => count($datos),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'analisis_rotacion':
            $periodo = intval($_GET['periodo'] ?? 90);
            $datos = $generador->analisisRotacion($periodo);
            echo json_encode([
                'success' => true,
                'datos' => $datos,
                'total' => count($datos),
                'periodo_dias' => $periodo
            ]);
            break;
            
        case 'performance_proveedores':
            $datos = $generador->performanceProveedores();
            echo json_encode([
                'success' => true,
                'datos' => $datos,
                'total' => count($datos)
            ]);
            break;
            
        case 'analisis_tendencias':
            $meses = intval($_GET['meses'] ?? 6);
            $datos = $generador->analisisTendencias($meses);
            echo json_encode([
                'success' => true,
                'datos' => $datos,
                'total' => count($datos),
                'periodo_meses' => $meses
            ]);
            break;
            
        case 'top_productos_demanda':
            $limite = intval($_GET['limite'] ?? 20);
            $dias = intval($_GET['dias'] ?? 30);
            $datos = $generador->topProductosDemanda($limite, $dias);
            echo json_encode([
                'success' => true,
                'datos' => $datos,
                'total' => count($datos),
                'parametros' => ['limite' => $limite, 'dias' => $dias]
            ]);
            break;
            
        case 'analisis_abc':
            $datos = $generador->analisisABC();
            
            // Estadísticas adicionales
            $total_productos = count($datos);
            $categoria_a = count(array_filter($datos, fn($p) => $p['clasificacion_abc'] === 'A'));
            $categoria_b = count(array_filter($datos, fn($p) => $p['clasificacion_abc'] === 'B'));
            $categoria_c = count(array_filter($datos, fn($p) => $p['clasificacion_abc'] === 'C'));
            
            echo json_encode([
                'success' => true,
                'datos' => $datos,
                'estadisticas' => [
                    'total_productos' => $total_productos,
                    'categoria_a' => $categoria_a,
                    'categoria_b' => $categoria_b,
                    'categoria_c' => $categoria_c,
                    'porcentaje_a' => round(($categoria_a / $total_productos) * 100, 1),
                    'porcentaje_b' => round(($categoria_b / $total_productos) * 100, 1),
                    'porcentaje_c' => round(($categoria_c / $total_productos) * 100, 1)
                ]
            ]);
            break;
            
        case 'dashboard_metricas':
            $metricas = $generador->dashboardMetricas();
            echo json_encode([
                'success' => true,
                'metricas' => $metricas,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'alertas':
            $alertas = $generador->reporteAlertas();
            echo json_encode([
                'success' => true,
                'alertas' => $alertas,
                'total_alertas' => count($alertas)
            ]);
            break;
            
        case 'exportar':
            $tipo_reporte = $_GET['tipo'] ?? '';
            $formato = $_GET['formato'] ?? 'csv';
            
            if (!$tipo_reporte) {
                throw new Exception('Tipo de reporte requerido');
            }
            
            // Obtener datos según el tipo de reporte
            $datos = [];
            $nombre_archivo = '';
            
            switch ($tipo_reporte) {
                case 'inventario':
                    $datos = $generador->reporteInventarioAvanzado();
                    $nombre_archivo = 'inventario_' . date('Y-m-d');
                    break;
                case 'rotacion':
                    $datos = $generador->analisisRotacion();
                    $nombre_archivo = 'analisis_rotacion_' . date('Y-m-d');
                    break;
                case 'proveedores':
                    $datos = $generador->performanceProveedores();
                    $nombre_archivo = 'performance_proveedores_' . date('Y-m-d');
                    break;
                case 'top_productos':
                    $datos = $generador->topProductosDemanda();
                    $nombre_archivo = 'top_productos_demanda_' . date('Y-m-d');
                    break;
                case 'abc':
                    $datos = $generador->analisisABC();
                    $nombre_archivo = 'analisis_abc_' . date('Y-m-d');
                    break;
                default:
                    throw new Exception('Tipo de reporte no válido');
            }
            
            // Exportar según formato
            switch ($formato) {
                case 'csv':
                    ExportadorReportes::exportarCSV($datos, $nombre_archivo);
                    break;
                case 'json':
                    ExportadorReportes::exportarJSON($datos, $nombre_archivo);
                    break;
                case 'html':
                    $titulo = ucfirst(str_replace('_', ' ', $tipo_reporte));
                    $html = ExportadorReportes::generarHTML($datos, $titulo);
                    header('Content-Type: text/html; charset=utf-8');
                    echo $html;
                    break;
                default:
                    throw new Exception('Formato de exportación no válido');
            }
            exit;
            
        case 'reporte_personalizado':
            // Validar datos POST para reporte personalizado
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Datos inválidos');
            }
            
            $tabla = $input['tabla'] ?? '';
            $columnas = $input['columnas'] ?? [];
            $filtros = $input['filtros'] ?? [];
            $orden = $input['orden'] ?? '';
            $limite = intval($input['limite'] ?? 1000);
            
            // Validaciones de seguridad
            $tablas_permitidas = ['Productos', 'Salidas', 'Proveedores', 'Categoria', 'Subcategoria', 'usuarios'];
            if (!in_array($tabla, $tablas_permitidas)) {
                throw new Exception('Tabla no permitida');
            }
            
            if (empty($columnas)) {
                throw new Exception('Debe seleccionar al menos una columna');
            }
            
            // Construir consulta SQL segura
            $columnas_escapadas = array_map(function($col) {
                return '`' . str_replace('`', '', $col) . '`';
            }, $columnas);
            
            $sql = "SELECT " . implode(', ', $columnas_escapadas) . " FROM `$tabla` WHERE 1=1";
            $params = [];
            $types = '';
            
            // Aplicar filtros
            foreach ($filtros as $filtro) {
                $campo = str_replace('`', '', $filtro['campo']);
                $operador = $filtro['operador'];
                $valor = $filtro['valor'];
                
                switch ($operador) {
                    case 'igual':
                        $sql .= " AND `$campo` = ?";
                        $params[] = $valor;
                        $types .= 's';
                        break;
                    case 'contiene':
                        $sql .= " AND `$campo` LIKE ?";
                        $params[] = "%$valor%";
                        $types .= 's';
                        break;
                    case 'mayor':
                        $sql .= " AND `$campo` > ?";
                        $params[] = $valor;
                        $types .= 's';
                        break;
                    case 'menor':
                        $sql .= " AND `$campo` < ?";
                        $params[] = $valor;
                        $types .= 's';
                        break;
                    case 'entre':
                        if (isset($filtro['valor_hasta'])) {
                            $sql .= " AND `$campo` BETWEEN ? AND ?";
                            $params[] = $valor;
                            $params[] = $filtro['valor_hasta'];
                            $types .= 'ss';
                        }
                        break;
                }
            }
            
            // Aplicar orden
            if ($orden && strpos($orden, '`') === false) {
                $sql .= " ORDER BY `" . str_replace('`', '', $orden) . "`";
            }
            
            // Aplicar límite
            if ($limite > 0 && $limite <= 10000) {
                $sql .= " LIMIT $limite";
            }
            
            // Ejecutar consulta
            require_once 'app/helpers/Database.php';
            $db = new Database();
            
            $stmt = $db->conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode([
                'success' => true,
                'datos' => $resultado,
                'total' => count($resultado),
                'sql_info' => [
                    'tabla' => $tabla,
                    'columnas' => count($columnas),
                    'filtros' => count($filtros),
                    'limite' => $limite
                ]
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>