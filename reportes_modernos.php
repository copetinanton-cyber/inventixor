<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

require_once 'app/helpers/Database.php';
require_once 'app/helpers/SistemaNotificaciones.php';
require_once 'app/helpers/PlantillasReportes.php';

$db = new Database();
$sistemaNotificaciones = new SistemaNotificaciones($db);
$plantillasReportes = new PlantillasReportes();

// Verificar permisos
$usuario = $_SESSION['user'];
$es_admin = $usuario['rol'] === 'admin';
$es_coordinador = $usuario['rol'] === 'coordinador' || $es_admin;

// Manejar solicitudes AJAX
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_GET['action']) {
            case 'dashboard_data':
                // Datos para el dashboard ejecutivo
                $data = [];
                
                // Resumen de inventario
                $inventario = $db->conn->query("
                    SELECT 
                        COUNT(*) as total_productos,
                        SUM(stock) as total_stock,
                        AVG(stock) as promedio_stock,
                        COUNT(CASE WHEN stock <= 10 THEN 1 END) as productos_stock_bajo,
                        COUNT(CASE WHEN stock <= 5 THEN 1 END) as productos_stock_critico
                    FROM Productos
                ")->fetch_assoc();
                
                $data['inventario'] = $inventario;
                
                // Movimientos últimos 30 días
                $movimientos = $db->conn->query("
                    SELECT 
                        DATE(fecha_hora) as fecha,
                        SUM(CAST(cantidad AS UNSIGNED)) as total_salidas
                    FROM Salidas 
                    WHERE fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY DATE(fecha_hora)
                    ORDER BY fecha DESC
                    LIMIT 30
                ")->fetch_all(MYSQLI_ASSOC);
                
                $data['movimientos'] = $movimientos;
                
                // Top productos por movimientos
                $top_productos = $db->conn->query("
                    SELECT 
                        p.nombre,
                        SUM(CAST(s.cantidad AS UNSIGNED)) as total_movido,
                        COUNT(s.id_salida) as num_movimientos
                    FROM Productos p
                    LEFT JOIN Salidas s ON p.id_prod = s.id_prod
                    WHERE s.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    GROUP BY p.id_prod, p.nombre
                    ORDER BY total_movido DESC
                    LIMIT 10
                ")->fetch_all(MYSQLI_ASSOC);
                
                $data['top_productos'] = $top_productos;
                
                // Proveedores más activos
                $proveedores = $db->conn->query("
                    SELECT 
                        pr.razon_social,
                        COUNT(p.id_prod) as productos_suministrados,
                        SUM(p.stock) as stock_total
                    FROM Proveedores pr
                    LEFT JOIN Productos p ON pr.id_nit = p.id_nit
                    GROUP BY pr.id_nit, pr.razon_social
                    ORDER BY productos_suministrados DESC
                    LIMIT 5
                ")->fetch_all(MYSQLI_ASSOC);
                
                $data['proveedores'] = $proveedores;
                
                echo json_encode(['success' => true, 'data' => $data]);
                break;
                
            case 'ejecutar_reporte_predefinido':
                $reporte_id = $_POST['reporte_id'] ?? '';
                $parametros = $_POST['parametros'] ?? [];
                
                if (!$reporte_id) {
                    throw new Exception('ID de reporte requerido');
                }
                
                $resultado = $plantillasReportes->ejecutarReporte($reporte_id, $parametros);
                echo json_encode(['success' => true, 'reporte' => $resultado]);
                break;
                
            case 'listar_reportes_predefinidos':
                $reportes = $plantillasReportes->getReportesDisponibles();
                echo json_encode(['success' => true, 'reportes' => $reportes]);
                break;
                
            case 'generar_reporte_personalizado':
                $tabla = $_POST['tabla'] ?? '';
                $columnas = $_POST['columnas'] ?? [];
                $filtros = $_POST['filtros'] ?? [];
                $orden = $_POST['orden'] ?? '';
                $limite = intval($_POST['limite'] ?? 1000);
                
                if (!$tabla || !$columnas) {
                    throw new Exception('Tabla y columnas son requeridas');
                }
                
                // Validar tabla permitida
                $tablas_permitidas = ['Productos', 'Salidas', 'Proveedores', 'Categoria', 'Subcategoria', 'usuarios'];
                if (!in_array($tabla, $tablas_permitidas)) {
                    throw new Exception('Tabla no permitida');
                }
                
                // Construir consulta
                $sql = "SELECT " . implode(', ', $columnas) . " FROM $tabla WHERE 1=1";
                $params = [];
                
                // Aplicar filtros
                foreach ($filtros as $filtro) {
                    $campo = $filtro['campo'];
                    $operador = $filtro['operador'];
                    $valor = $filtro['valor'];
                    
                    switch ($operador) {
                        case 'igual':
                            $sql .= " AND $campo = ?";
                            $params[] = $valor;
                            break;
                        case 'contiene':
                            $sql .= " AND $campo LIKE ?";
                            $params[] = "%$valor%";
                            break;
                        case 'mayor':
                            $sql .= " AND $campo > ?";
                            $params[] = $valor;
                            break;
                        case 'menor':
                            $sql .= " AND $campo < ?";
                            $params[] = $valor;
                            break;
                    }
                }
                
                // Aplicar orden
                if ($orden) {
                    $sql .= " ORDER BY $orden";
                }
                
                // Aplicar límite
                $sql .= " LIMIT $limite";
                
                $stmt = $db->conn->prepare($sql);
                if (!empty($params)) {
                    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
                }
                $stmt->execute();
                $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                echo json_encode(['success' => true, 'datos' => $resultado, 'total' => count($resultado)]);
                break;
                
            default:
                throw new Exception('Acción no válida');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Obtener reportes predefinidos disponibles
$reportes_disponibles = $plantillasReportes->getReportesDisponibles();
$reportes_populares = [];

foreach ($reportes_disponibles as $id => $reporte) {
    $reportes_populares[] = [
        'id' => $id,
        'titulo' => $reporte['nombre'],
        'descripcion' => $reporte['descripcion'],
        'icono' => $reporte['icono'],
        'categoria' => $reporte['categoria']
    ];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Inteligentes - InventiXor</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js">
    <link rel="stylesheet" href="public/css/reportes-modernos.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --info-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            --card-shadow: 0 10px 25px rgba(0,0,0,0.1);
            --hover-shadow: 0 15px 35px rgba(0,0,0,0.15);
            --glass-bg: rgba(255, 255, 255, 0.95);
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        .main-container {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            margin: 2rem auto;
            padding: 0;
            overflow: hidden;
            max-width: 95vw;
        }

        .header-section {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255,255,255,0.05) 10px,
                rgba(255,255,255,0.05) 20px
            );
            animation: float 20s infinite linear;
        }

        @keyframes float {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .header-section h1 {
            margin: 0;
            font-weight: bold;
            font-size: 2.5rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            position: relative;
            z-index: 1;
        }

        .header-section p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .nav-tabs {
            background: rgba(248, 249, 250, 0.8);
            border-bottom: none;
            padding: 1rem 2rem 0 2rem;
        }

        .nav-tabs .nav-link {
            border: 2px solid transparent;
            border-radius: 15px 15px 0 0;
            margin-right: 0.5rem;
            padding: 1rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            background: white;
            color: #666;
        }

        .nav-tabs .nav-link:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-3px);
        }

        .nav-tabs .nav-link.active {
            background: var(--primary-gradient);
            color: white;
            border-color: transparent;
        }

        .tab-content {
            padding: 2rem;
            background: white;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .metric-card .metric-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .metric-card .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .metric-card .metric-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .report-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .report-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .report-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: var(--hover-shadow);
            border-color: rgba(102, 126, 234, 0.3);
        }

        .report-card:hover::before {
            transform: scaleX(1);
        }

        .report-card .report-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .report-card .report-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .report-card .report-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .report-card .report-category {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--primary-gradient);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .custom-report-builder {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }

        .btn-gradient {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
        }

        .filter-section {
            background: rgba(102, 126, 234, 0.05);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #667eea;
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .table th {
            background: var(--primary-gradient);
            color: white;
            border: none;
            font-weight: 600;
            text-align: center;
        }

        .table td {
            vertical-align: middle;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .animate-fade-in {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-radius: 50%;
            border-top-color: #667eea;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .sidebar-return {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }

        .sidebar-return .btn {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(102, 126, 234, 0.3);
            color: #667eea;
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .sidebar-return .btn:hover {
            background: var(--primary-gradient);
            color: white;
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 1rem;
                max-width: calc(100vw - 2rem);
            }
            
            .header-section {
                padding: 1.5rem;
            }
            
            .header-section h1 {
                font-size: 2rem;
            }
            
            .dashboard-grid,
            .report-grid {
                grid-template-columns: 1fr;
            }
            
            .tab-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar-return animate-fade-in">
        <a href="dashboard.php" class="btn">
            <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
        </a>
    </div>

    <div class="container-fluid">
        <div class="main-container animate-fade-in">
            <!-- Header -->
            <div class="header-section">
                <h1><i class="fas fa-chart-line me-3"></i>Reportes Inteligentes</h1>
                <p>Centro de análisis y toma de decisiones empresariales</p>
            </div>

            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs" id="reportTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard Ejecutivo
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="predefinidos-tab" data-bs-toggle="tab" data-bs-target="#predefinidos" type="button" role="tab">
                        <i class="fas fa-chart-bar me-2"></i>Reportes Predefinidos
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="personalizado-tab" data-bs-toggle="tab" data-bs-target="#personalizado" type="button" role="tab">
                        <i class="fas fa-cogs me-2"></i>Generador Personalizado
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="analisis-tab" data-bs-toggle="tab" data-bs-target="#analisis" type="button" role="tab">
                        <i class="fas fa-brain me-2"></i>Análisis Avanzado
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="reportTabContent">
                <!-- Dashboard Ejecutivo -->
                <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3><i class="fas fa-tachometer-alt me-2"></i>Dashboard Ejecutivo</h3>
                        <button class="btn btn-gradient" onclick="actualizarDashboard()">
                            <i class="fas fa-sync-alt me-2"></i>Actualizar
                        </button>
                    </div>

                    <div class="dashboard-grid" id="metricsContainer">
                        <!-- Las métricas se cargarán aquí dinámicamente -->
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="chart-container">
                                <h5><i class="fas fa-chart-line me-2"></i>Tendencia de Movimientos (30 días)</h5>
                                <canvas id="movimientosChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="chart-container">
                                <h5><i class="fas fa-chart-pie me-2"></i>Estado del Inventario</h5>
                                <canvas id="inventarioChart" width="200" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h5><i class="fas fa-trophy me-2"></i>Top Productos</h5>
                                <div id="topProductosContainer"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h5><i class="fas fa-truck me-2"></i>Proveedores Activos</h5>
                                <div id="proveedoresContainer"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reportes Predefinidos -->
                <div class="tab-pane fade" id="predefinidos" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3><i class="fas fa-chart-bar me-2"></i>Reportes Predefinidos</h3>
                        <div class="input-group" style="max-width: 300px;">
                            <input type="text" class="form-control" placeholder="Buscar reporte..." id="buscarReporte">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    <div class="report-grid">
                        <?php foreach ($reportes_populares as $index => $reporte): ?>
                        <div class="report-card animate-fade-in" style="animation-delay: <?php echo $index * 0.1; ?>s" 
                             onclick="ejecutarReportePredefinido('<?php echo $reporte['id']; ?>')">
                            <div class="report-category"><?php echo $reporte['categoria']; ?></div>
                            <div class="report-icon">
                                <i class="<?php echo $reporte['icono']; ?>"></i>
                            </div>
                            <div class="report-title"><?php echo $reporte['titulo']; ?></div>
                            <div class="report-description"><?php echo $reporte['descripcion']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div id="reporteResultado" class="mt-4" style="display: none;">
                        <div class="custom-report-builder">
                            <h5><i class="fas fa-table me-2"></i>Resultado del Reporte</h5>
                            <div id="reporteContenido"></div>
                        </div>
                    </div>
                </div>

                <!-- Generador Personalizado -->
                <div class="tab-pane fade" id="personalizado" role="tabpanel">
                    <div class="custom-report-builder">
                        <h3><i class="fas fa-cogs me-2"></i>Generador de Reportes Personalizado</h3>
                        <p class="text-muted">Crea reportes a medida según tus necesidades específicas</p>

                        <form id="customReportForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-table me-1"></i>Tabla Principal</label>
                                        <select class="form-select" id="tablaSelect" required>
                                            <option value="">Seleccionar tabla...</option>
                                            <option value="Productos">Productos</option>
                                            <option value="Salidas">Movimientos/Salidas</option>
                                            <option value="Proveedores">Proveedores</option>
                                            <option value="Categoria">Categorías</option>
                                            <option value="Subcategoria">Subcategorías</option>
                                            <option value="usuarios">Usuarios</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-sort me-1"></i>Ordenar por</label>
                                        <select class="form-select" id="ordenSelect">
                                            <option value="">Sin orden específico</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-columns me-1"></i>Columnas a Incluir</label>
                                <div id="columnasContainer" class="row">
                                    <!-- Se llenarán dinámicamente -->
                                </div>
                            </div>

                            <div class="filter-section">
                                <h6><i class="fas fa-filter me-2"></i>Filtros Avanzados</h6>
                                <div id="filtrosContainer">
                                    <!-- Filtros dinámicos -->
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="agregarFiltro()">
                                    <i class="fas fa-plus me-1"></i>Agregar Filtro
                                </button>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-hashtag me-1"></i>Límite de Registros</label>
                                        <select class="form-select" id="limiteSelect">
                                            <option value="100">100 registros</option>
                                            <option value="500" selected>500 registros</option>
                                            <option value="1000">1,000 registros</option>
                                            <option value="5000">5,000 registros</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-file-export me-1"></i>Formato de Exportación</label>
                                        <select class="form-select" id="formatoExport">
                                            <option value="table">Ver en tabla</option>
                                            <option value="excel">Exportar a Excel</option>
                                            <option value="pdf">Exportar a PDF</option>
                                            <option value="csv">Exportar a CSV</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-gradient btn-lg">
                                    <i class="fas fa-play me-2"></i>Generar Reporte
                                </button>
                            </div>
                        </form>
                    </div>

                    <div id="customReportResult" class="mt-4" style="display: none;">
                        <div class="custom-report-builder">
                            <h5><i class="fas fa-table me-2"></i>Resultado del Reporte Personalizado</h5>
                            <div id="customReportContent"></div>
                        </div>
                    </div>
                </div>

                <!-- Análisis Avanzado -->
                <div class="tab-pane fade" id="analisis" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3><i class="fas fa-brain me-2"></i>Análisis Avanzado</h3>
                        <div class="btn-group" role="group">

                            <button class="btn btn-outline-success" onclick="generarRecomendaciones()">
                                <i class="fas fa-lightbulb me-2"></i>Recomendaciones
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h5><i class="fas fa-chart-area me-2"></i>Análisis de Tendencias</h5>
                                <canvas id="tendenciasChart" width="400" height="300"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container">
                                <h5><i class="fas fa-bullseye me-2"></i>Indicadores Clave (KPIs)</h5>
                                <div id="kpisContainer">
                                    <div class="row">
                                        <div class="col-6 text-center mb-3">
                                            <h4 class="text-primary">85%</h4>
                                            <small class="text-muted">Eficiencia Inventario</small>
                                        </div>
                                        <div class="col-6 text-center mb-3">
                                            <h4 class="text-success">12.5</h4>
                                            <small class="text-muted">Rotación Promedio</small>
                                        </div>
                                        <div class="col-6 text-center">
                                            <h4 class="text-warning">15%</h4>
                                            <small class="text-muted">Stock de Seguridad</small>
                                        </div>
                                        <div class="col-6 text-center">
                                            <h4 class="text-info">7.2 días</h4>
                                            <small class="text-muted">Tiempo Reposición</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="custom-report-builder">
                        <h5><i class="fas fa-lightbulb me-2"></i>Insights y Recomendaciones</h5>
                        <div id="insightsContainer">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Insight:</strong> Los productos de la categoría "Calzado Deportivo" muestran una tendencia de crecimiento del 23% en los últimos 3 meses.
                            </div>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Alerta:</strong> 15 productos están por debajo del stock mínimo recomendado y requieren reposición inmediata.
                            </div>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Oportunidad:</strong> El proveedor "Nike Colombia" tiene el mejor ratio precio/calidad según el análisis de performance.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <script src="public/js/notifications.js"></script>
    <script src="public/js/auto-notifications.js"></script>
    <script src="public/js/reportes-modernos.js"></script>

    <script>
        let dashboardData = null;
        let movimientosChart = null;
        let inventarioChart = null;
        
        // Configuración de columnas por tabla
        const columnasPorTabla = {
            'Productos': ['id_prod', 'nombre', 'modelo', 'talla', 'color', 'stock', 'fecha_ing', 'material'],
            'Salidas': ['id_salida', 'id_prod', 'cantidad', 'fecha_salida', 'destino', 'observaciones'],
            'Proveedores': ['id_nit', 'razon_social', 'contacto', 'direccion', 'correo', 'telefono', 'estado'],
            'Categoria': ['id_categ', 'nombre', 'descripcion'],
            'Subcategoria': ['id_subcg', 'nombre', 'descripcion', 'id_categ'],
            'usuarios': ['num_doc', 'nombres', 'apellidos', 'telefono', 'correo', 'cargo', 'rol']
        };

        // Inicializar al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            actualizarDashboard();
            
            // Configurar el generador personalizado
            document.getElementById('tablaSelect').addEventListener('change', function() {
                actualizarColumnas(this.value);
                actualizarOrdenamiento(this.value);
            });
            
            // Configurar el formulario personalizado
            document.getElementById('customReportForm').addEventListener('submit', function(e) {
                e.preventDefault();
                generarReportePersonalizado();
            });
            
            // Animaciones de entrada
            const elements = document.querySelectorAll('.animate-fade-in');
            elements.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });
        });

        // Actualizar dashboard ejecutivo
        async function actualizarDashboard() {
            try {
                const response = await fetch('reportes_modernos.php?action=dashboard_data');
                const result = await response.json();
                
                if (result.success) {
                    dashboardData = result.data;
                    renderizarMetricas();
                    renderizarGraficos();
                } else {
                    console.error('Error al cargar datos del dashboard:', result.error);
                }
            } catch (error) {
                console.error('Error de conexión:', error);
            }
        }

        // Renderizar métricas principales
        function renderizarMetricas() {
            const container = document.getElementById('metricsContainer');
            const inventario = dashboardData.inventario;
            
            container.innerHTML = `
                <div class="metric-card animate-fade-in">
                    <div class="metric-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="metric-value">${parseInt(inventario.total_productos).toLocaleString()}</div>
                    <div class="metric-label">Total Productos</div>
                </div>
                
                <div class="metric-card animate-fade-in" style="animation-delay: 0.1s">
                    <div class="metric-icon">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div class="metric-value">${parseInt(inventario.total_stock).toLocaleString()}</div>
                    <div class="metric-label">Stock Total</div>
                </div>
                
                <div class="metric-card animate-fade-in" style="animation-delay: 0.2s">
                    <div class="metric-icon">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                    </div>
                    <div class="metric-value">${inventario.productos_stock_bajo}</div>
                    <div class="metric-label">Stock Bajo</div>
                </div>
                
                <div class="metric-card animate-fade-in" style="animation-delay: 0.3s">
                    <div class="metric-icon">
                        <i class="fas fa-bell text-danger"></i>
                    </div>
                    <div class="metric-value">${inventario.productos_stock_critico}</div>
                    <div class="metric-label">Stock Crítico</div>
                </div>
            `;
        }

        // Renderizar gráficos
        function renderizarGraficos() {
            // Gráfico de movimientos
            const movimientosCtx = document.getElementById('movimientosChart').getContext('2d');
            
            if (movimientosChart) {
                movimientosChart.destroy();
            }
            
            movimientosChart = new Chart(movimientosCtx, {
                type: 'line',
                data: {
                    labels: dashboardData.movimientos.map(m => m.fecha),
                    datasets: [{
                        label: 'Salidas por día',
                        data: dashboardData.movimientos.map(m => m.total_salidas),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Gráfico de inventario (pie chart)
            const inventarioCtx = document.getElementById('inventarioChart').getContext('2d');
            
            if (inventarioChart) {
                inventarioChart.destroy();
            }
            
            const inventario = dashboardData.inventario;
            inventarioChart = new Chart(inventarioCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Stock Normal', 'Stock Bajo', 'Stock Crítico'],
                    datasets: [{
                        data: [
                            inventario.total_productos - inventario.productos_stock_bajo,
                            inventario.productos_stock_bajo - inventario.productos_stock_critico,
                            inventario.productos_stock_critico
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#ffc107',
                            '#dc3545'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
            
            // Renderizar top productos
            const topProductosContainer = document.getElementById('topProductosContainer');
            let topProductosHtml = '<div class="list-group list-group-flush">';
            
            dashboardData.top_productos.slice(0, 5).forEach((producto, index) => {
                topProductosHtml += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${producto.nombre}</strong>
                            <br><small class="text-muted">${producto.num_movimientos} movimientos</small>
                        </div>
                        <span class="badge bg-primary rounded-pill">${producto.total_movido}</span>
                    </div>
                `;
            });
            
            topProductosHtml += '</div>';
            topProductosContainer.innerHTML = topProductosHtml;
            
            // Renderizar proveedores
            const proveedoresContainer = document.getElementById('proveedoresContainer');
            let proveedoresHtml = '<div class="list-group list-group-flush">';
            
            dashboardData.proveedores.forEach((proveedor, index) => {
                proveedoresHtml += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${proveedor.razon_social}</strong>
                            <br><small class="text-muted">${proveedor.productos_suministrados} productos</small>
                        </div>
                        <span class="badge bg-success rounded-pill">${proveedor.stock_total}</span>
                    </div>
                `;
            });
            
            proveedoresHtml += '</div>';
            proveedoresContainer.innerHTML = proveedoresHtml;
        }

        // Actualizar columnas según tabla seleccionada
        function actualizarColumnas(tabla) {
            const container = document.getElementById('columnasContainer');
            const columnas = columnasPorTabla[tabla] || [];
            
            container.innerHTML = '';
            
            columnas.forEach(columna => {
                const div = document.createElement('div');
                div.className = 'col-md-4 col-sm-6 mb-2';
                div.innerHTML = `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="${columna}" id="col_${columna}" checked>
                        <label class="form-check-label" for="col_${columna}">
                            ${columna.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                        </label>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        // Actualizar opciones de ordenamiento
        function actualizarOrdenamiento(tabla) {
            const select = document.getElementById('ordenSelect');
            const columnas = columnasPorTabla[tabla] || [];
            
            select.innerHTML = '<option value="">Sin orden específico</option>';
            
            columnas.forEach(columna => {
                const option1 = document.createElement('option');
                option1.value = `${columna} ASC`;
                option1.textContent = `${columna.replace('_', ' ')} (A-Z)`;
                select.appendChild(option1);
                
                const option2 = document.createElement('option');
                option2.value = `${columna} DESC`;
                option2.textContent = `${columna.replace('_', ' ')} (Z-A)`;
                select.appendChild(option2);
            });
        }

        // Agregar filtro dinámico
        function agregarFiltro() {
            const container = document.getElementById('filtrosContainer');
            const tabla = document.getElementById('tablaSelect').value;
            
            if (!tabla) {
                alert('Primero selecciona una tabla');
                return;
            }
            
            const columnas = columnasPorTabla[tabla] || [];
            const filtroId = Date.now();
            
            const filtroDiv = document.createElement('div');
            filtroDiv.className = 'row mb-2 filter-row';
            filtroDiv.innerHTML = `
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="filtros[${filtroId}][campo]">
                        ${columnas.map(col => `<option value="${col}">${col.replace('_', ' ')}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="filtros[${filtroId}][operador]">
                        <option value="igual">Igual a</option>
                        <option value="contiene">Contiene</option>
                        <option value="mayor">Mayor que</option>
                        <option value="menor">Menor que</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control form-control-sm" name="filtros[${filtroId}][valor]" placeholder="Valor...">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.filter-row').remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            
            container.appendChild(filtroDiv);
        }

        // Generar reporte personalizado
        async function generarReportePersonalizado() {
            const form = document.getElementById('customReportForm');
            const formData = new FormData(form);
            
            // Obtener columnas seleccionadas
            const columnasSeleccionadas = [];
            document.querySelectorAll('#columnasContainer input[type="checkbox"]:checked').forEach(checkbox => {
                columnasSeleccionadas.push(checkbox.value);
            });
            
            if (columnasSeleccionadas.length === 0) {
                alert('Selecciona al menos una columna');
                return;
            }
            
            // Obtener filtros
            const filtros = [];
            document.querySelectorAll('.filter-row').forEach(row => {
                const campo = row.querySelector('select[name*="[campo]"]').value;
                const operador = row.querySelector('select[name*="[operador]"]').value;
                const valor = row.querySelector('input[name*="[valor]"]').value;
                
                if (campo && operador && valor) {
                    filtros.push({ campo, operador, valor });
                }
            });
            
            // Preparar datos
            const data = new FormData();
            data.append('action', 'generar_reporte_personalizado');
            data.append('tabla', formData.get('tabla'));
            data.append('orden', formData.get('orden'));
            data.append('limite', formData.get('limite'));
            
            columnasSeleccionadas.forEach(col => {
                data.append('columnas[]', col);
            });
            
            filtros.forEach((filtro, index) => {
                data.append(`filtros[${index}][campo]`, filtro.campo);
                data.append(`filtros[${index}][operador]`, filtro.operador);
                data.append(`filtros[${index}][valor]`, filtro.valor);
            });
            
            try {
                // Mostrar loading
                const btn = form.querySelector('button[type="submit"]');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<span class="loading-spinner me-2"></span>Generando...';
                btn.disabled = true;
                
                const response = await fetch('reportes_modernos.php', {
                    method: 'POST',
                    body: data
                });
                
                const result = await response.json();
                
                if (result.success) {
                    mostrarResultadoPersonalizado(result.datos, columnasSeleccionadas, result.total);
                } else {
                    alert('Error: ' + result.error);
                }
                
                // Restaurar botón
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
                
                // Restaurar botón
                const btn = form.querySelector('button[type="submit"]');
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        }

        // Mostrar resultado del reporte personalizado
        function mostrarResultadoPersonalizado(datos, columnas, total) {
            const container = document.getElementById('customReportContent');
            const resultDiv = document.getElementById('customReportResult');
            
            if (datos.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No se encontraron resultados</h5>
                        <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                    </div>
                `;
                resultDiv.style.display = 'block';
                return;
            }
            
            // Crear tabla
            let html = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6><i class="fas fa-table me-2"></i>Resultados: ${total} registros</h6>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-success" onclick="exportarReporte('excel')">
                            <i class="fas fa-file-excel me-1"></i>Excel
                        </button>
                        <button class="btn btn-outline-danger" onclick="exportarReporte('pdf')">
                            <i class="fas fa-file-pdf me-1"></i>PDF
                        </button>
                        <button class="btn btn-outline-info" onclick="exportarReporte('csv')">
                            <i class="fas fa-file-csv me-1"></i>CSV
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
            `;
            
            // Cabeceras
            columnas.forEach(col => {
                html += `<th>${col.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</th>`;
            });
            
            html += '</tr></thead><tbody>';
            
            // Filas de datos
            datos.forEach(fila => {
                html += '<tr>';
                columnas.forEach(col => {
                    const valor = fila[col] || '';
                    html += `<td>${valor}</td>`;
                });
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
            
            container.innerHTML = html;
            resultDiv.style.display = 'block';
            
            // Scroll al resultado
            resultDiv.scrollIntoView({ behavior: 'smooth' });
        }

        // Ejecutar reporte predefinido
        function ejecutarReportePredefinido(reporteId) {
            const reporteResultado = document.getElementById('reporteResultado');
            const reporteContenido = document.getElementById('reporteContenido');
            
            // Simulación de diferentes reportes
            let contenido = '';
            
            switch (reporteId) {
                case 'inventario_general':
                    contenido = `
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Reporte generado el ${new Date().toLocaleString()}
                        </div>
                        <p>Este reporte muestra el estado actual de todo el inventario...</p>
                        <!-- Aquí iría la implementación específica del reporte -->
                    `;
                    break;
                    
                case 'stock_bajo':
                    contenido = `
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Productos que requieren atención inmediata
                        </div>
                        <p>Los siguientes productos tienen stock por debajo del mínimo recomendado...</p>
                        <!-- Implementación específica -->
                    `;
                    break;
                    
                default:
                    contenido = `
                        <div class="alert alert-primary mb-3">
                            <i class="fas fa-chart-bar me-2"></i>
                            Reporte: ${reporteId}
                        </div>
                        <p>Reporte en desarrollo. Funcionalidad próximamente disponible.</p>
                    `;
            }
            
            reporteContenido.innerHTML = contenido;
            reporteResultado.style.display = 'block';
            reporteResultado.scrollIntoView({ behavior: 'smooth' });
        }

        // Exportar reporte (placeholder)
        function exportarReporte(formato) {
            alert(`Exportando reporte en formato ${formato.toUpperCase()}...`);
            // Aquí se implementaría la lógica de exportación
        }

        // Generar análisis con IA (simulado)
        function generarAnalisisIA() {
            alert('Generando análisis con Inteligencia Artificial...');
            // Implementación futura de IA
        }

        // Generar recomendaciones
        function generarRecomendaciones() {
            alert('Generando recomendaciones basadas en datos históricos...');
            // Implementación futura de recomendaciones
        }

        // Filtro de búsqueda para reportes predefinidos
        document.getElementById('buscarReporte')?.addEventListener('input', function() {
            const termino = this.value.toLowerCase();
            const tarjetas = document.querySelectorAll('.report-card');
            
            tarjetas.forEach(tarjeta => {
                const titulo = tarjeta.querySelector('.report-title').textContent.toLowerCase();
                const descripcion = tarjeta.querySelector('.report-description').textContent.toLowerCase();
                
                if (titulo.includes(termino) || descripcion.includes(termino)) {
                    tarjeta.style.display = 'block';
                } else {
                    tarjeta.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>