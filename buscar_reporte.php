<?php
/**
 * Localizador de Reportes - Sistema Inventixor
 * Busca reportes específicos por fecha y hora
 * Archivo: buscar_reporte.php
 */

require_once 'config/db.php';

// Configurar zona horaria
date_default_timezone_set('America/Bogota');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Localizador de Reportes - Inventixor</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .search-container { background: white; border-radius: 20px; padding: 30px; margin: 20px auto; max-width: 1000px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .result-card { background: #f8f9fa; border-radius: 15px; padding: 20px; margin: 15px 0; border-left: 4px solid #667eea; }
        .no-results { text-align: center; color: #6c757d; padding: 40px; }
        .timestamp { color: #28a745; font-weight: bold; }
        .user-info { color: #17a2b8; }
        .report-actions { margin-top: 15px; }
        .btn-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; }
        .btn-custom:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); color: white; }
    </style>
</head>
<body>
<div class='container-fluid'>
    <div class='search-container'>
        <h1 class='text-center mb-4'>
            <i class='fas fa-search text-primary me-3'></i>Localizador de Reportes
        </h1>";

// Función para buscar reportes por fecha específica
function buscarReportePorFecha($conn, $fecha, $hora = null) {
    if ($hora) {
        // Búsqueda específica por fecha y hora
        $sql = "SELECT r.*, u.nombres, u.apellidos, u.rol,
                       pr.razon_social as proveedor,
                       p.nombre as producto
                FROM Reportes r
                LEFT JOIN Users u ON r.num_doc = u.num_doc
                LEFT JOIN Proveedores pr ON r.id_nit = pr.id_nit
                LEFT JOIN Productos p ON r.id_prod = p.id_prod
                WHERE DATE(r.fecha_hora) = ? 
                AND TIME(r.fecha_hora) BETWEEN ? AND DATE_ADD(?, INTERVAL 1 MINUTE)
                ORDER BY r.fecha_hora DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $fecha, $hora, $hora);
    } else {
        // Búsqueda por fecha completa
        $sql = "SELECT r.*, u.nombres, u.apellidos, u.rol,
                       pr.razon_social as proveedor,
                       p.nombre as producto
                FROM Reportes r
                LEFT JOIN Users u ON r.num_doc = u.num_doc
                LEFT JOIN Proveedores pr ON r.id_nit = pr.id_nit
                LEFT JOIN Productos p ON r.id_prod = p.id_prod
                WHERE DATE(r.fecha_hora) = ?
                ORDER BY r.fecha_hora DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $fecha);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Procesamiento de búsqueda
$busqueda_realizada = false;
$reportes_encontrados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['buscar'])) {
    $busqueda_realizada = true;
    $fecha_busqueda = $_POST['fecha'] ?? $_GET['fecha'] ?? '2025-10-01';
    $hora_busqueda = $_POST['hora'] ?? $_GET['hora'] ?? null;
    
    // Convertir hora si está presente
    if ($hora_busqueda && $hora_busqueda !== '') {
        // Si solo se proporciona HH:MM, agregamos segundos
        if (strlen($hora_busqueda) === 5) {
            $hora_busqueda .= ':00';
        }
    }
    
    $reportes_encontrados = buscarReportePorFecha($conn, $fecha_busqueda, $hora_busqueda);
}

// Mostrar formulario de búsqueda
echo "
<div class='row'>
    <div class='col-md-6'>
        <div class='card border-0 shadow-sm'>
            <div class='card-header bg-primary text-white'>
                <h5 class='mb-0'><i class='fas fa-calendar-alt me-2'></i>Búsqueda Específica</h5>
            </div>
            <div class='card-body'>
                <form method='POST'>
                    <div class='mb-3'>
                        <label class='form-label'>Fecha del Reporte</label>
                        <input type='date' name='fecha' class='form-control' value='" . ($_POST['fecha'] ?? '2025-10-01') . "' required>
                    </div>
                    <div class='mb-3'>
                        <label class='form-label'>Hora Específica (opcional)</label>
                        <input type='time' name='hora' class='form-control' value='" . ($_POST['hora'] ?? '22:46') . "' 
                               placeholder='Ej: 22:46' step='1'>
                        <small class='text-muted'>Deja vacío para buscar todos los reportes del día</small>
                    </div>
                    <button type='submit' class='btn btn-custom w-100'>
                        <i class='fas fa-search me-2'></i>Buscar Reportes
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class='col-md-6'>
        <div class='card border-0 shadow-sm'>
            <div class='card-header bg-success text-white'>
                <h5 class='mb-0'><i class='fas fa-clock me-2'></i>Búsquedas Rápidas</h5>
            </div>
            <div class='card-body'>
                <div class='d-grid gap-2'>
                    <a href='?buscar=1&fecha=2025-10-01&hora=22:46' class='btn btn-outline-success'>
                        <i class='fas fa-bullseye me-2'></i>1/10/2025 - 22:46:XX
                    </a>
                    <a href='?buscar=1&fecha=2025-10-01' class='btn btn-outline-info'>
                        <i class='fas fa-calendar-day me-2'></i>Todo el 1/10/2025
                    </a>
                    <a href='?buscar=1&fecha=" . date('Y-m-d') . "' class='btn btn-outline-warning'>
                        <i class='fas fa-calendar-day me-2'></i>Hoy (" . date('d/m/Y') . ")
                    </a>
                    <a href='?buscar=1&fecha=" . date('Y-m-d', strtotime('-1 day')) . "' class='btn btn-outline-secondary'>
                        <i class='fas fa-calendar-minus me-2'></i>Ayer
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>";

// Mostrar resultados
if ($busqueda_realizada) {
    $fecha_mostrar = $_POST['fecha'] ?? $_GET['fecha'] ?? '2025-10-01';
    $hora_mostrar = $_POST['hora'] ?? $_GET['hora'] ?? null;
    
    echo "<hr><h3 class='mt-4 mb-3'>
        <i class='fas fa-list-alt me-2'></i>Resultados de Búsqueda
        <small class='text-muted'>(" . date('d/m/Y', strtotime($fecha_mostrar));
    
    if ($hora_mostrar) {
        echo " - $hora_mostrar)";
    } else {
        echo " - Todo el día)";
    }
    echo "</small></h3>";
    
    if (empty($reportes_encontrados)) {
        echo "<div class='no-results'>
            <i class='fas fa-search fa-3x text-muted mb-3'></i>
            <h4>No se encontraron reportes</h4>
            <p>No hay reportes registrados para la fecha y hora especificadas.</p>
            <p class='text-muted'>Intenta buscar con una fecha diferente o sin especificar la hora.</p>
        </div>";
    } else {
        echo "<div class='alert alert-success'>
            <i class='fas fa-check-circle me-2'></i>
            <strong>Encontrados " . count($reportes_encontrados) . " reporte(s)</strong>
        </div>";
        
        foreach ($reportes_encontrados as $reporte) {
            $fecha_formateada = date('d/m/Y H:i:s', strtotime($reporte['fecha_hora']));
            $usuario_completo = ($reporte['nombres'] ? $reporte['nombres'] . ' ' . $reporte['apellidos'] : 'Usuario ' . $reporte['num_doc']);
            
            echo "<div class='result-card'>
                <div class='d-flex justify-content-between align-items-start'>
                    <div class='flex-grow-1'>
                        <h5 class='mb-2'>
                            <i class='fas fa-file-alt text-primary me-2'></i>
                            " . htmlspecialchars($reporte['nombre']) . "
                        </h5>
                        <p class='mb-2 text-muted'>" . htmlspecialchars($reporte['descripcion']) . "</p>
                        
                        <div class='row mb-2'>
                            <div class='col-md-6'>
                                <small><strong>📅 Fecha y Hora:</strong> <span class='timestamp'>$fecha_formateada</span></small><br>
                                <small><strong>👤 Usuario:</strong> <span class='user-info'>$usuario_completo</span></small><br>
                                <small><strong>🎭 Rol:</strong> " . ucfirst($reporte['rol'] ?? 'N/A') . "</small>
                            </div>
                            <div class='col-md-6'>
                                <small><strong>🏢 Proveedor:</strong> " . ($reporte['proveedor'] ?? 'N/A') . "</small><br>
                                <small><strong>📦 Producto:</strong> " . ($reporte['producto'] ?? 'N/A') . "</small><br>
                                <small><strong>🆔 ID Reporte:</strong> " . $reporte['id_repor'] . "</small>
                            </div>
                        </div>
                    </div>
                    <div class='ms-3'>
                        <span class='badge bg-success fs-6'>#" . $reporte['id_repor'] . "</span>
                    </div>
                </div>
                
                <div class='report-actions'>
                    <a href='reportes.php?ver=" . $reporte['id_repor'] . "' class='btn btn-sm btn-outline-primary'>
                        <i class='fas fa-eye me-1'></i>Ver en Sistema
                    </a>
                    <button class='btn btn-sm btn-outline-success' onclick='exportarReporte(" . $reporte['id_repor'] . ", \"csv\")'>
                        <i class='fas fa-download me-1'></i>Exportar CSV
                    </button>
                    <button class='btn btn-sm btn-outline-info' onclick='mostrarDetalles(" . $reporte['id_repor'] . ")'>
                        <i class='fas fa-info-circle me-1'></i>Más Detalles
                    </button>
                </div>
            </div>";
        }
    }
}

// Estadísticas generales
$sql_stats = "SELECT 
    COUNT(*) as total_reportes,
    COUNT(DISTINCT DATE(fecha_hora)) as dias_con_reportes,
    MAX(fecha_hora) as ultimo_reporte,
    MIN(fecha_hora) as primer_reporte
FROM Reportes";
$result_stats = $conn->query($sql_stats);
$stats = $result_stats->fetch_assoc();

echo "<hr>
<div class='row mt-4'>
    <div class='col-md-12'>
        <h4><i class='fas fa-chart-bar me-2'></i>Estadísticas del Sistema</h4>
        <div class='row'>
            <div class='col-md-3'>
                <div class='card text-center border-primary'>
                    <div class='card-body'>
                        <i class='fas fa-file-alt fa-2x text-primary mb-2'></i>
                        <h5>" . number_format($stats['total_reportes']) . "</h5>
                        <small>Total Reportes</small>
                    </div>
                </div>
            </div>
            <div class='col-md-3'>
                <div class='card text-center border-success'>
                    <div class='card-body'>
                        <i class='fas fa-calendar-check fa-2x text-success mb-2'></i>
                        <h5>" . $stats['dias_con_reportes'] . "</h5>
                        <small>Días con Actividad</small>
                    </div>
                </div>
            </div>
            <div class='col-md-3'>
                <div class='card text-center border-info'>
                    <div class='card-body'>
                        <i class='fas fa-clock fa-2x text-info mb-2'></i>
                        <h5>" . date('d/m/Y', strtotime($stats['ultimo_reporte'])) . "</h5>
                        <small>Último Reporte</small>
                    </div>
                </div>
            </div>
            <div class='col-md-3'>
                <div class='card text-center border-warning'>
                    <div class='card-body'>
                        <i class='fas fa-calendar-alt fa-2x text-warning mb-2'></i>
                        <h5>" . date('d/m/Y', strtotime($stats['primer_reporte'])) . "</h5>
                        <small>Primer Reporte</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>";

echo "
<div class='text-center mt-4'>
    <a href='reportes.php' class='btn btn-custom me-2'>
        <i class='fas fa-arrow-left me-2'></i>Volver a Reportes
    </a>
    <a href='index.php' class='btn btn-outline-secondary'>
        <i class='fas fa-home me-2'></i>Ir al Dashboard
    </a>
</div>

</div>
</div>

<script>
function exportarReporte(id, formato) {
    window.location.href = 'reportes.php?exportar=individual&id=' + id + '&formato=' + formato;
}

function mostrarDetalles(id) {
    alert('Funcionalidad de detalles en desarrollo. ID: ' + id);
}
</script>

</body>
</html>";

$conn->close();
?>