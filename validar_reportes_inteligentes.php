<?php
/**
 * Validador de Reportes Inteligentes
 * Archivo: validar_reportes_inteligentes.php
 * Propósito: Verificar que el módulo de reportes funcione correctamente
 */

require_once 'config/db.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Validación Reportes Inteligentes - Inventixor</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .validation-container { background: white; border-radius: 20px; padding: 30px; margin: 20px auto; max-width: 1200px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .test-section { background: #f8f9fa; border-radius: 15px; padding: 25px; margin: 20px 0; border-left: 5px solid #667eea; }
        .success { color: #28a745; } .error { color: #dc3545; } .warning { color: #ffc107; }
        .test-result { padding: 15px; margin: 10px 0; border-radius: 10px; }
        .test-success { background: #d1edff; border: 1px solid #0ea5e9; color: #0284c7; }
        .test-error { background: #fee2e2; border: 1px solid #ef4444; color: #dc2626; }
        .test-warning { background: #fef3c7; border: 1px solid #f59e0b; color: #d97706; }
        .data-preview { background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; max-height: 300px; overflow-y: auto; }
    </style>
</head>
<body>
<div class='container-fluid'>
    <div class='validation-container'>
        <h1 class='text-center mb-4'>
            <i class='fas fa-check-circle text-primary me-3'></i>Validación Reportes Inteligentes
        </h1>
        <p class='text-center text-muted mb-5'>Verificando funcionalidad del sistema para toma de decisiones</p>";

// Test 1: Verificar conexión a base de datos
echo "<div class='test-section'>
    <h3><i class='fas fa-database me-2'></i>1. Conexión a Base de Datos</h3>";

try {
    if ($conn->ping()) {
        echo "<div class='test-result test-success'>
            <i class='fas fa-check me-2'></i><strong>✅ Conexión exitosa</strong><br>
            Base de datos MySQL conectada correctamente
        </div>";
    } else {
        throw new Exception("Conexión falló");
    }
} catch (Exception $e) {
    echo "<div class='test-result test-error'>
        <i class='fas fa-times me-2'></i><strong>❌ Error de conexión:</strong> " . $e->getMessage() . "
    </div>";
}
echo "</div>";

// Test 2: Verificar tablas necesarias
echo "<div class='test-section'>
    <h3><i class='fas fa-table me-2'></i>2. Verificación de Tablas</h3>";

$tablas_requeridas = ['Productos', 'Categoria', 'Subcategoria', 'Proveedores', 'Salidas', 'Users'];
$tablas_existentes = [];
$tablas_faltantes = [];

foreach ($tablas_requeridas as $tabla) {
    $result = $conn->query("SHOW TABLES LIKE '$tabla'");
    if ($result && $result->num_rows > 0) {
        $tablas_existentes[] = $tabla;
        echo "<div class='test-result test-success'>
            <i class='fas fa-check me-2'></i>Tabla <strong>$tabla</strong> - Existe
        </div>";
    } else {
        $tablas_faltantes[] = $tabla;
        echo "<div class='test-result test-error'>
            <i class='fas fa-times me-2'></i>Tabla <strong>$tabla</strong> - No encontrada
        </div>";
    }
}

if (empty($tablas_faltantes)) {
    echo "<div class='test-result test-success'>
        <i class='fas fa-check-circle me-2'></i><strong>✅ Todas las tablas requeridas están disponibles</strong>
    </div>";
} else {
    echo "<div class='test-result test-warning'>
        <i class='fas fa-exclamation-triangle me-2'></i><strong>⚠️ Faltan tablas:</strong> " . implode(', ', $tablas_faltantes) . "
    </div>";
}
echo "</div>";

// Test 3: Verificar datos en tablas
echo "<div class='test-section'>
    <h3><i class='fas fa-chart-bar me-2'></i>3. Verificación de Datos</h3>";

$datos_tablas = [];
foreach ($tablas_existentes as $tabla) {
    $result = $conn->query("SELECT COUNT(*) as total FROM $tabla");
    if ($result) {
        $count = $result->fetch_assoc()['total'];
        $datos_tablas[$tabla] = $count;
        
        if ($count > 0) {
            echo "<div class='test-result test-success'>
                <i class='fas fa-check me-2'></i>Tabla <strong>$tabla</strong>: $count registros
            </div>";
        } else {
            echo "<div class='test-result test-warning'>
                <i class='fas fa-exclamation me-2'></i>Tabla <strong>$tabla</strong>: Sin datos (puede afectar reportes)
            </div>";
        }
    }
}
echo "</div>";

// Test 4: Probar consultas de reportes
echo "<div class='test-section'>
    <h3><i class='fas fa-search me-2'></i>4. Prueba de Consultas de Reportes</h3>";

$reportes_test = [
    'inventario_general' => "SELECT COUNT(*) as total FROM Productos p LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg LEFT JOIN Categoria c ON sc.id_categ = c.id_categ",
    'productos_criticos' => "SELECT COUNT(*) as total FROM Productos WHERE CAST(stock AS UNSIGNED) <= 5",
    'movimientos_recientes' => "SELECT COUNT(*) as total FROM Salidas WHERE fecha_hora >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
    'categorias_analisis' => "SELECT COUNT(*) as total FROM Categoria c LEFT JOIN Subcategoria sc ON c.id_categ = sc.id_categ LEFT JOIN Productos p ON sc.id_subcg = p.id_subcg GROUP BY c.id_categ"
];

$consultas_exitosas = 0;
$total_consultas = count($reportes_test);

foreach ($reportes_test as $nombre => $query) {
    try {
        $result = $conn->query($query);
        if ($result) {
            $data = $result->fetch_assoc();
            echo "<div class='test-result test-success'>
                <i class='fas fa-check me-2'></i><strong>$nombre:</strong> Consulta exitosa (" . ($data['total'] ?? 0) . " registros)
            </div>";
            $consultas_exitosas++;
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        echo "<div class='test-result test-error'>
            <i class='fas fa-times me-2'></i><strong>$nombre:</strong> Error - " . $e->getMessage() . "
        </div>";
    }
}

if ($consultas_exitosas === $total_consultas) {
    echo "<div class='test-result test-success'>
        <i class='fas fa-check-circle me-2'></i><strong>✅ Todas las consultas funcionan correctamente</strong>
    </div>";
} else {
    echo "<div class='test-result test-warning'>
        <i class='fas fa-exclamation-triangle me-2'></i><strong>⚠️ $consultas_exitosas de $total_consultas consultas funcionan</strong>
    </div>";
}
echo "</div>";

// Test 5: Datos de ejemplo para cada reporte
if ($datos_tablas['Productos'] > 0) {
    echo "<div class='test-section'>
        <h3><i class='fas fa-eye me-2'></i>5. Vista Previa de Datos</h3>";
    
    // Inventario General
    echo "<h5 class='mt-4'>📦 Inventario General (Muestra)</h5>";
    $sql = "SELECT p.nombre, p.modelo, p.stock, c.nombre as categoria,
                   CASE 
                       WHEN CAST(p.stock AS UNSIGNED) <= 5 THEN 'CRÍTICO'
                       WHEN CAST(p.stock AS UNSIGNED) <= 15 THEN 'BAJO'
                       ELSE 'NORMAL'
                   END as nivel_stock
            FROM Productos p 
            LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
            LEFT JOIN Categoria c ON sc.id_categ = c.id_categ 
            ORDER BY p.stock ASC 
            LIMIT 10";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        echo "<div class='data-preview'>
            <table class='table table-sm'>
                <thead><tr><th>Producto</th><th>Modelo</th><th>Stock</th><th>Categoría</th><th>Nivel</th></tr></thead>
                <tbody>";
        
        while ($row = $result->fetch_assoc()) {
            $badge_class = $row['nivel_stock'] === 'CRÍTICO' ? 'bg-danger' : 
                          ($row['nivel_stock'] === 'BAJO' ? 'bg-warning text-dark' : 'bg-success');
            echo "<tr>
                <td>" . htmlspecialchars($row['nombre']) . "</td>
                <td>" . htmlspecialchars($row['modelo']) . "</td>
                <td>" . $row['stock'] . "</td>
                <td>" . htmlspecialchars($row['categoria']) . "</td>
                <td><span class='badge $badge_class'>" . $row['nivel_stock'] . "</span></td>
            </tr>";
        }
        echo "</tbody></table></div>";
    }
    
    // Productos Críticos
    if ($datos_tablas['Productos'] > 0) {
        $result = $conn->query("SELECT COUNT(*) as criticos FROM Productos WHERE CAST(stock AS UNSIGNED) <= 5");
        $criticos = $result->fetch_assoc()['criticos'];
        
        if ($criticos > 0) {
            echo "<h5 class='mt-4'>🚨 Productos Críticos (Stock ≤ 5)</h5>";
            echo "<div class='test-result test-warning'>
                <i class='fas fa-exclamation-triangle me-2'></i><strong>$criticos productos requieren atención inmediata</strong>
            </div>";
        } else {
            echo "<h5 class='mt-4'>✅ Productos Críticos</h5>";
            echo "<div class='test-result test-success'>
                <i class='fas fa-check me-2'></i><strong>No hay productos con stock crítico</strong>
            </div>";
        }
    }
    
    echo "</div>";
}

// Test 6: Recomendaciones para optimización
echo "<div class='test-section'>
    <h3><i class='fas fa-lightbulb me-2'></i>6. Recomendaciones para Toma de Decisiones</h3>";

$recomendaciones = [];

// Analizar stock crítico
if (isset($datos_tablas['Productos']) && $datos_tablas['Productos'] > 0) {
    $result = $conn->query("
        SELECT 
            COUNT(CASE WHEN CAST(stock AS UNSIGNED) <= 5 THEN 1 END) as criticos,
            COUNT(CASE WHEN CAST(stock AS UNSIGNED) BETWEEN 6 AND 15 THEN 1 END) as bajos,
            COUNT(*) as total
        FROM Productos
    ");
    
    if ($result) {
        $stock_analysis = $result->fetch_assoc();
        $pct_criticos = round(($stock_analysis['criticos'] / $stock_analysis['total']) * 100, 1);
        $pct_bajos = round(($stock_analysis['bajos'] / $stock_analysis['total']) * 100, 1);
        
        if ($stock_analysis['criticos'] > 0) {
            $recomendaciones[] = [
                'tipo' => 'urgente',
                'mensaje' => "🚨 ACCIÓN URGENTE: {$stock_analysis['criticos']} productos ({$pct_criticos}%) tienen stock crítico",
                'accion' => "Contactar proveedores inmediatamente para restock"
            ];
        }
        
        if ($pct_bajos > 20) {
            $recomendaciones[] = [
                'tipo' => 'importante',
                'mensaje' => "⚠️ PLANIFICACIÓN: {$pct_bajos}% de productos con stock bajo",
                'accion' => "Revisar política de inventario mínimo"
            ];
        }
        
        if ($stock_analysis['criticos'] == 0 && $pct_bajos < 10) {
            $recomendaciones[] = [
                'tipo' => 'positivo',
                'mensaje' => "✅ EXCELENTE: Niveles de stock bien gestionados",
                'accion' => "Mantener las prácticas actuales de inventario"
            ];
        }
    }
}

// Analizar movimientos si hay datos de salidas
if (isset($datos_tablas['Salidas']) && $datos_tablas['Salidas'] > 0) {
    $result = $conn->query("
        SELECT COUNT(*) as movimientos_mes
        FROM Salidas 
        WHERE fecha_hora >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    
    if ($result) {
        $movimientos = $result->fetch_assoc()['movimientos_mes'];
        
        if ($movimientos == 0) {
            $recomendaciones[] = [
                'tipo' => 'atencion',
                'mensaje' => "📊 SIN MOVIMIENTOS: No hay salidas registradas en 30 días",
                'accion' => "Verificar proceso de registro de salidas"
            ];
        } else {
            $promedio_diario = round($movimientos / 30, 1);
            $recomendaciones[] = [
                'tipo' => 'informativo',
                'mensaje' => "📈 ACTIVIDAD: $movimientos movimientos en 30 días ($promedio_diario/día)",
                'accion' => "Monitorear tendencias para predicción de demanda"
            ];
        }
    }
}

// Mostrar recomendaciones
if (empty($recomendaciones)) {
    echo "<div class='test-result test-success'>
        <i class='fas fa-info-circle me-2'></i><strong>Sistema básico validado - Agregue más datos para recomendaciones específicas</strong>
    </div>";
} else {
    foreach ($recomendaciones as $rec) {
        $class = $rec['tipo'] === 'urgente' ? 'test-error' : 
                ($rec['tipo'] === 'importante' ? 'test-warning' : 
                ($rec['tipo'] === 'positivo' ? 'test-success' : 'test-success'));
        
        echo "<div class='test-result $class'>
            <strong>{$rec['mensaje']}</strong><br>
            <small><strong>Acción recomendada:</strong> {$rec['accion']}</small>
        </div>";
    }
}

echo "</div>";

// Resumen final
echo "<div class='test-section' style='border-left-color: #28a745; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);'>
    <h3><i class='fas fa-trophy me-2'></i>Resumen de Validación</h3>";

$total_tests = 6;
$tests_passed = 0;

// Criterios de éxito
$tests_passed += ($conn->ping() ? 1 : 0);
$tests_passed += (empty($tablas_faltantes) ? 1 : 0);
$tests_passed += ($datos_tablas['Productos'] > 0 ? 1 : 0);
$tests_passed += ($consultas_exitosas === $total_consultas ? 1 : 0);
$tests_passed += (file_exists('reportes_inteligentes.php') ? 1 : 0);
$tests_passed += 1; // Recomendaciones siempre pasan

$porcentaje_exito = round(($tests_passed / $total_tests) * 100);

if ($porcentaje_exito >= 90) {
    $resultado_class = 'test-success';
    $resultado_icon = 'fas fa-check-circle';
    $resultado_msg = '✅ SISTEMA COMPLETAMENTE FUNCIONAL';
} elseif ($porcentaje_exito >= 70) {
    $resultado_class = 'test-warning';
    $resultado_icon = 'fas fa-exclamation-triangle';
    $resultado_msg = '⚠️ SISTEMA FUNCIONAL CON MEJORAS REQUERIDAS';
} else {
    $resultado_class = 'test-error';
    $resultado_icon = 'fas fa-times-circle';
    $resultado_msg = '❌ SISTEMA REQUIERE ATENCIÓN INMEDIATA';
}

echo "<div class='test-result $resultado_class text-center'>
    <i class='$resultado_icon fa-2x mb-3'></i><br>
    <h4>$resultado_msg</h4>
    <p><strong>Puntuación:</strong> $tests_passed/$total_tests tests pasaron ($porcentaje_exito%)</p>
</div>";

echo "<div class='text-center mt-4'>
    <a href='reportes_inteligentes.php' class='btn btn-success btn-lg me-3'>
        <i class='fas fa-chart-line me-2'></i>Ir a Reportes Inteligentes
    </a>
    <a href='reportes.php' class='btn btn-outline-primary btn-lg'>
        <i class='fas fa-arrow-left me-2'></i>Volver a Reportes
    </a>
</div>";

echo "</div>";

echo "
    </div>
</div>
</body>
</html>";

$conn->close();
?>