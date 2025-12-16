<?php
/**
 * VALIDADOR COMPLETO DE MÓDULOS - INVENTIXOR
 * Prueba exhaustiva de todas las funcionalidades del sistema
 */

// Configuración de la base de datos
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'inventixor';

echo "<html><head><title>Validador Completo - InventiXor</title>";
echo "<style>
body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
.container { max-width: 1400px; margin: 0 auto; }
.header { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); margin-bottom: 20px; text-align: center; }
.module-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; }
.module-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
.module-header { display: flex; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f1f3f4; }
.module-icon { font-size: 28px; margin-right: 15px; width: 50px; text-align: center; }
.module-title { font-size: 20px; font-weight: bold; color: #2c3e50; }
.test-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #ecf0f1; }
.test-name { font-weight: 500; color: #34495e; }
.test-status { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
.status-success { background: #d4edda; color: #155724; }
.status-warning { background: #fff3cd; color: #856404; }
.status-error { background: #f8d7da; color: #721c24; }
.status-info { background: #d1ecf1; color: #0c5460; }
.summary { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); margin-top: 20px; }
.summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
.summary-item { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 10px; }
.summary-number { font-size: 32px; font-weight: bold; margin-bottom: 10px; }
.summary-label { color: #6c757d; font-size: 14px; }
.btn { display: inline-block; padding: 12px 24px; margin: 5px; border-radius: 8px; text-decoration: none; font-weight: bold; transition: all 0.3s; }
.btn-primary { background: #007bff; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-warning { background: #ffc107; color: #212529; }
.btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
.progress-bar { background: #e9ecef; border-radius: 10px; height: 20px; overflow: hidden; margin: 15px 0; }
.progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: bold; }
.alert { padding: 15px; margin: 15px 0; border-radius: 8px; }
.alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
.alert-warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
.alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
</style>
<script>
function updateProgress(percent, text) {
    const bar = document.getElementById('progressBar');
    const label = document.getElementById('progressLabel');
    if (bar && label) {
        bar.style.width = percent + '%';
        bar.textContent = percent + '%';
        label.textContent = text;
    }
}
</script>
</head><body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔍 Validador Completo del Sistema InventiXor</h1>";
echo "<p>Verificación exhaustiva de todos los módulos y funcionalidades</p>";
echo "<div class='progress-bar'>";
echo "<div class='progress-fill' id='progressBar' style='width: 5%;'>5%</div>";
echo "</div>";
echo "<p id='progressLabel'>Iniciando validación...</p>";
echo "</div>";

$validation_results = [];
$total_tests = 0;
$passed_tests = 0;
$warnings = 0;
$errors = 0;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<script>updateProgress(10, 'Conectado a la base de datos...');</script>";
    
    // ========================================
    // MÓDULO 1: SISTEMA DE AUTENTICACIÓN
    // ========================================
    
    echo "<div class='module-grid'>";
    echo "<div class='module-card'>";
    echo "<div class='module-header'>";
    echo "<div class='module-icon'>🔐</div>";
    echo "<div class='module-title'>Sistema de Autenticación</div>";
    echo "</div>";
    
    $auth_tests = [];
    
    // Test 1: Verificar tabla Users
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM Users");
        $users_count = $stmt->fetch()['total'];
        if ($users_count > 0) {
            $auth_tests[] = ['name' => 'Tabla Users', 'status' => 'success', 'details' => "$users_count usuarios"];
            $passed_tests++;
        } else {
            $auth_tests[] = ['name' => 'Tabla Users', 'status' => 'error', 'details' => 'Sin usuarios'];
            $errors++;
        }
    } catch (Exception $e) {
        $auth_tests[] = ['name' => 'Tabla Users', 'status' => 'error', 'details' => $e->getMessage()];
        $errors++;
    }
    $total_tests++;
    
    // Test 2: Verificar estructura de usuarios
    try {
        $stmt = $pdo->query("DESCRIBE Users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $required_columns = ['num_doc', 'nombres', 'apellidos', 'telefono', 'password', 'rol'];
        $missing_columns = array_diff($required_columns, $columns);
        
        if (empty($missing_columns)) {
            $auth_tests[] = ['name' => 'Estructura Users', 'status' => 'success', 'details' => 'Completa'];
            $passed_tests++;
        } else {
            $auth_tests[] = ['name' => 'Estructura Users', 'status' => 'warning', 'details' => 'Faltan: ' . implode(', ', $missing_columns)];
            $warnings++;
        }
    } catch (Exception $e) {
        $auth_tests[] = ['name' => 'Estructura Users', 'status' => 'error', 'details' => $e->getMessage()];
        $errors++;
    }
    $total_tests++;
    
    // Test 3: Verificar archivo login.php
    $login_exists = file_exists(__DIR__ . '/login.php');
    if ($login_exists) {
        $auth_tests[] = ['name' => 'Archivo login.php', 'status' => 'success', 'details' => 'Existe'];
        $passed_tests++;
    } else {
        $auth_tests[] = ['name' => 'Archivo login.php', 'status' => 'error', 'details' => 'No encontrado'];
        $errors++;
    }
    $total_tests++;
    
    foreach ($auth_tests as $test) {
        echo "<div class='test-item'>";
        echo "<span class='test-name'>{$test['name']}</span>";
        echo "<span class='test-status status-{$test['status']}'>{$test['details']}</span>";
        echo "</div>";
    }
    
    echo "</div>";
    
    echo "<script>updateProgress(20, 'Validando gestión de productos...');</script>";
    
    // ========================================
    // MÓDULO 2: GESTIÓN DE PRODUCTOS
    // ========================================
    
    echo "<div class='module-card'>";
    echo "<div class='module-header'>";
    echo "<div class='module-icon'>📦</div>";
    echo "<div class='module-title'>Gestión de Productos</div>";
    echo "</div>";
    
    $products_tests = [];
    
    // Test 1: Verificar tablas relacionadas
    $product_tables = ['Categoria', 'Subcategoria', 'Proveedores', 'Productos'];
    foreach ($product_tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
            $count = $stmt->fetch()['total'];
            if ($count > 0) {
                $products_tests[] = ['name' => "Tabla $table", 'status' => 'success', 'details' => "$count registros"];
                $passed_tests++;
            } else {
                $products_tests[] = ['name' => "Tabla $table", 'status' => 'warning', 'details' => 'Vacía'];
                $warnings++;
            }
        } catch (Exception $e) {
            $products_tests[] = ['name' => "Tabla $table", 'status' => 'error', 'details' => 'No existe'];
            $errors++;
        }
        $total_tests++;
    }
    
    // Test 2: Verificar archivo productos.php
    $productos_exists = file_exists(__DIR__ . '/productos.php');
    if ($productos_exists) {
        $products_tests[] = ['name' => 'Archivo productos.php', 'status' => 'success', 'details' => 'Existe'];
        $passed_tests++;
    } else {
        $products_tests[] = ['name' => 'Archivo productos.php', 'status' => 'error', 'details' => 'No encontrado'];
        $errors++;
    }
    $total_tests++;
    
    // Test 3: Verificar relaciones FK
    try {
        $stmt = $pdo->query("SELECT p.nombre, c.nombre as categoria, sc.nombre as subcategoria, pr.razon_social 
                             FROM Productos p 
                             LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg 
                             LEFT JOIN Categoria c ON sc.id_categ = c.id_categ 
                             LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit 
                             LIMIT 1");
        $relation = $stmt->fetch();
        if ($relation) {
            $products_tests[] = ['name' => 'Relaciones FK', 'status' => 'success', 'details' => 'Funcionando'];
            $passed_tests++;
        } else {
            $products_tests[] = ['name' => 'Relaciones FK', 'status' => 'warning', 'details' => 'Sin datos para probar'];
            $warnings++;
        }
    } catch (Exception $e) {
        $products_tests[] = ['name' => 'Relaciones FK', 'status' => 'error', 'details' => 'Error en consulta'];
        $errors++;
    }
    $total_tests++;
    
    foreach ($products_tests as $test) {
        echo "<div class='test-item'>";
        echo "<span class='test-name'>{$test['name']}</span>";
        echo "<span class='test-status status-{$test['status']}'>{$test['details']}</span>";
        echo "</div>";
    }
    
    echo "</div>";
    
    echo "<script>updateProgress(35, 'Validando gestión de salidas...');</script>";
    
    // ========================================
    // MÓDULO 3: GESTIÓN DE SALIDAS
    // ========================================
    
    echo "<div class='module-card'>";
    echo "<div class='module-header'>";
    echo "<div class='module-icon'>📋</div>";
    echo "<div class='module-title'>Gestión de Salidas</div>";
    echo "</div>";
    
    $salidas_tests = [];
    
    // Test 1: Verificar tabla Salidas
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM Salidas");
        $salidas_count = $stmt->fetch()['total'];
        if ($salidas_count > 0) {
            $salidas_tests[] = ['name' => 'Tabla Salidas', 'status' => 'success', 'details' => "$salidas_count registros"];
            $passed_tests++;
        } else {
            $salidas_tests[] = ['name' => 'Tabla Salidas', 'status' => 'warning', 'details' => 'Sin salidas'];
            $warnings++;
        }
    } catch (Exception $e) {
        $salidas_tests[] = ['name' => 'Tabla Salidas', 'status' => 'error', 'details' => 'No existe'];
        $errors++;
    }
    $total_tests++;
    
    // Test 2: Verificar archivo salidas.php
    $salidas_exists = file_exists(__DIR__ . '/salidas.php');
    if ($salidas_exists) {
        $salidas_tests[] = ['name' => 'Archivo salidas.php', 'status' => 'success', 'details' => 'Existe'];
        $passed_tests++;
    } else {
        $salidas_tests[] = ['name' => 'Archivo salidas.php', 'status' => 'error', 'details' => 'No encontrado'];
        $errors++;
    }
    $total_tests++;
    
    // Test 3: Verificar nuevas columnas de salidas
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM Salidas");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $new_columns = ['estado_salida', 'fecha_entrega', 'cliente_info', 'num_doc_usuario'];
        $existing_new_columns = array_intersect($new_columns, $columns);
        
        if (count($existing_new_columns) >= 2) {
            $salidas_tests[] = ['name' => 'Mejoras Salidas', 'status' => 'success', 'details' => count($existing_new_columns) . '/4 columnas'];
            $passed_tests++;
        } else {
            $salidas_tests[] = ['name' => 'Mejoras Salidas', 'status' => 'warning', 'details' => 'Parciales'];
            $warnings++;
        }
    } catch (Exception $e) {
        $salidas_tests[] = ['name' => 'Mejoras Salidas', 'status' => 'error', 'details' => 'Error verificando'];
        $errors++;
    }
    $total_tests++;
    
    foreach ($salidas_tests as $test) {
        echo "<div class='test-item'>";
        echo "<span class='test-name'>{$test['name']}</span>";
        echo "<span class='test-status status-{$test['status']}'>{$test['details']}</span>";
        echo "</div>";
    }
    
    echo "</div>";
    
    echo "<script>updateProgress(50, 'Validando sistema de devoluciones...');</script>";
    
    // ========================================
    // MÓDULO 4: SISTEMA DE DEVOLUCIONES
    // ========================================
    
    echo "<div class='module-card'>";
    echo "<div class='module-header'>";
    echo "<div class='module-icon'>🔄</div>";
    echo "<div class='module-title'>Sistema de Devoluciones</div>";
    echo "</div>";
    
    $devoluciones_tests = [];
    
    // Test 1: Verificar tabla Devoluciones
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'Devoluciones'");
        if ($stmt->rowCount() > 0) {
            $devoluciones_tests[] = ['name' => 'Tabla Devoluciones', 'status' => 'success', 'details' => 'Existe'];
            $passed_tests++;
        } else {
            $devoluciones_tests[] = ['name' => 'Tabla Devoluciones', 'status' => 'error', 'details' => 'No existe'];
            $errors++;
        }
    } catch (Exception $e) {
        $devoluciones_tests[] = ['name' => 'Tabla Devoluciones', 'status' => 'error', 'details' => 'Error verificando'];
        $errors++;
    }
    $total_tests++;
    
    // Test 2: Verificar modal de devoluciones en salidas.php
    if ($salidas_exists) {
        $salidas_content = file_get_contents(__DIR__ . '/salidas.php');
        if (strpos($salidas_content, 'devolucionModal') !== false) {
            $devoluciones_tests[] = ['name' => 'Modal Devoluciones', 'status' => 'success', 'details' => 'Implementado'];
            $passed_tests++;
        } else {
            $devoluciones_tests[] = ['name' => 'Modal Devoluciones', 'status' => 'error', 'details' => 'No encontrado'];
            $errors++;
        }
    } else {
        $devoluciones_tests[] = ['name' => 'Modal Devoluciones', 'status' => 'error', 'details' => 'Archivo salidas.php no existe'];
        $errors++;
    }
    $total_tests++;
    
    // Test 3: Verificar botón de devoluciones
    if ($salidas_exists) {
        $salidas_content = file_get_contents(__DIR__ . '/salidas.php');
        if (strpos($salidas_content, 'abrirModalDevolucion') !== false) {
            $devoluciones_tests[] = ['name' => 'Botón Devoluciones', 'status' => 'success', 'details' => 'Implementado'];
            $passed_tests++;
        } else {
            $devoluciones_tests[] = ['name' => 'Botón Devoluciones', 'status' => 'error', 'details' => 'No encontrado'];
            $errors++;
        }
    } else {
        $devoluciones_tests[] = ['name' => 'Botón Devoluciones', 'status' => 'error', 'details' => 'Archivo no disponible'];
        $errors++;
    }
    $total_tests++;
    
    // Test 4: Verificar lista desplegable categorizada
    if ($salidas_exists) {
        $salidas_content = file_get_contents(__DIR__ . '/salidas.php');
        if (strpos($salidas_content, 'optgroup') !== false && strpos($salidas_content, 'Problemas de Calidad') !== false) {
            $devoluciones_tests[] = ['name' => 'Lista Categorizada', 'status' => 'success', 'details' => 'Campo motivo OK'];
            $passed_tests++;
        } else {
            $devoluciones_tests[] = ['name' => 'Lista Categorizada', 'status' => 'warning', 'details' => 'Implementación parcial'];
            $warnings++;
        }
    } else {
        $devoluciones_tests[] = ['name' => 'Lista Categorizada', 'status' => 'error', 'details' => 'No verificable'];
        $errors++;
    }
    $total_tests++;
    
    foreach ($devoluciones_tests as $test) {
        echo "<div class='test-item'>";
        echo "<span class='test-name'>{$test['name']}</span>";
        echo "<span class='test-status status-{$test['status']}'>{$test['details']}</span>";
        echo "</div>";
    }
    
    echo "</div>";
    
    echo "<script>updateProgress(65, 'Validando funciones avanzadas...');</script>";
    
    // ========================================
    // MÓDULO 5: FUNCIONES AVANZADAS
    // ========================================
    
    echo "<div class='module-card'>";
    echo "<div class='module-header'>";
    echo "<div class='module-icon'>⚙️</div>";
    echo "<div class='module-title'>Funciones Avanzadas</div>";
    echo "</div>";
    
    $advanced_tests = [];
    
    // Test 1: Verificar tablas avanzadas
    $advanced_tables = ['ProductosSeguimiento', 'Garantias', 'TiposSalida', 'NotificacionesSistema'];
    $existing_advanced = 0;
    foreach ($advanced_tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $existing_advanced++;
            }
        } catch (Exception $e) {
            // Tabla no existe
        }
    }
    
    if ($existing_advanced >= 4) {
        $advanced_tests[] = ['name' => 'Tablas Avanzadas', 'status' => 'success', 'details' => "$existing_advanced/5 tablas"];
        $passed_tests++;
    } elseif ($existing_advanced >= 2) {
        $advanced_tests[] = ['name' => 'Tablas Avanzadas', 'status' => 'warning', 'details' => "$existing_advanced/5 tablas"];
        $warnings++;
    } else {
        $advanced_tests[] = ['name' => 'Tablas Avanzadas', 'status' => 'error', 'details' => "$existing_advanced/5 tablas"];
        $errors++;
    }
    $total_tests++;
    
    // Test 2: Verificar triggers
    try {
        $stmt = $pdo->query("SHOW TRIGGERS");
        $triggers = $stmt->fetchAll();
        if (count($triggers) > 0) {
            $advanced_tests[] = ['name' => 'Triggers Automatización', 'status' => 'success', 'details' => count($triggers) . ' triggers'];
            $passed_tests++;
        } else {
            $advanced_tests[] = ['name' => 'Triggers Automatización', 'status' => 'warning', 'details' => 'Sin triggers'];
            $warnings++;
        }
    } catch (Exception $e) {
        $advanced_tests[] = ['name' => 'Triggers Automatización', 'status' => 'error', 'details' => 'Error verificando'];
        $errors++;
    }
    $total_tests++;
    
    // Test 3: Verificar vistas
    try {
        $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
        $views = $stmt->fetchAll();
        if (count($views) > 0) {
            $advanced_tests[] = ['name' => 'Vistas de Datos', 'status' => 'success', 'details' => count($views) . ' vistas'];
            $passed_tests++;
        } else {
            $advanced_tests[] = ['name' => 'Vistas de Datos', 'status' => 'warning', 'details' => 'Sin vistas'];
            $warnings++;
        }
    } catch (Exception $e) {
        $advanced_tests[] = ['name' => 'Vistas de Datos', 'status' => 'error', 'details' => 'Error verificando'];
        $errors++;
    }
    $total_tests++;
    
    foreach ($advanced_tests as $test) {
        echo "<div class='test-item'>";
        echo "<span class='test-name'>{$test['name']}</span>";
        echo "<span class='test-status status-{$test['status']}'>{$test['details']}</span>";
        echo "</div>";
    }
    
    echo "</div>";
    
    echo "<script>updateProgress(80, 'Validando archivos del sistema...');</script>";
    
    // ========================================
    // MÓDULO 6: ARCHIVOS DEL SISTEMA
    // ========================================
    
    echo "<div class='module-card'>";
    echo "<div class='module-header'>";
    echo "<div class='module-icon'>📁</div>";
    echo "<div class='module-title'>Archivos del Sistema</div>";
    echo "</div>";
    
    $files_tests = [];
    
    // Test archivos principales
    $main_files = [
        'index.php' => 'Página principal',
        'dashboard.php' => 'Dashboard',
        'productos.php' => 'Gestión productos',
        'salidas.php' => 'Gestión salidas',
        'categorias.php' => 'Gestión categorías',
        'proveedores.php' => 'Gestión proveedores',
        'usuarios.php' => 'Gestión usuarios',
        'reportes.php' => 'Sistema reportes'
    ];
    
    foreach ($main_files as $file => $description) {
        if (file_exists(__DIR__ . '/' . $file)) {
            $files_tests[] = ['name' => $description, 'status' => 'success', 'details' => 'Existe'];
            $passed_tests++;
        } else {
            $files_tests[] = ['name' => $description, 'status' => 'warning', 'details' => 'No encontrado'];
            $warnings++;
        }
        $total_tests++;
    }
    
    // Test carpetas importantes
    $directories = ['app/', 'config/', 'public/', 'views/'];
    foreach ($directories as $dir) {
        if (is_dir(__DIR__ . '/' . $dir)) {
            $files_tests[] = ['name' => "Carpeta $dir", 'status' => 'success', 'details' => 'Existe'];
            $passed_tests++;
        } else {
            $files_tests[] = ['name' => "Carpeta $dir", 'status' => 'warning', 'details' => 'No encontrada'];
            $warnings++;
        }
        $total_tests++;
    }
    
    foreach ($files_tests as $test) {
        echo "<div class='test-item'>";
        echo "<span class='test-name'>{$test['name']}</span>";
        echo "<span class='test-status status-{$test['status']}'>{$test['details']}</span>";
        echo "</div>";
    }
    
    echo "</div>";
    echo "</div>"; // Fin module-grid
    
    echo "<script>updateProgress(95, 'Generando reporte final...');</script>";
    
    // ========================================
    // RESUMEN FINAL
    // ========================================
    
    echo "<div class='summary'>";
    echo "<h2>📊 Resumen de Validación Completa</h2>";
    
    $success_rate = $total_tests > 0 ? ($passed_tests / $total_tests) * 100 : 0;
    
    echo "<div class='summary-grid'>";
    
    echo "<div class='summary-item'>";
    echo "<div class='summary-number' style='color: #28a745;'>$passed_tests</div>";
    echo "<div class='summary-label'>Pruebas Exitosas</div>";
    echo "</div>";
    
    echo "<div class='summary-item'>";
    echo "<div class='summary-number' style='color: #ffc107;'>$warnings</div>";
    echo "<div class='summary-label'>Advertencias</div>";
    echo "</div>";
    
    echo "<div class='summary-item'>";
    echo "<div class='summary-number' style='color: #dc3545;'>$errors</div>";
    echo "<div class='summary-label'>Errores</div>";
    echo "</div>";
    
    echo "<div class='summary-item'>";
    echo "<div class='summary-number' style='color: #007bff;'>" . number_format($success_rate, 1) . "%</div>";
    echo "<div class='summary-label'>Tasa de Éxito</div>";
    echo "</div>";
    
    echo "</div>";
    
    // Determinar estado general del sistema
    if ($success_rate >= 90 && $errors == 0) {
        $system_status = 'excellent';
        $status_message = '🎉 ¡EXCELENTE! El sistema está funcionando perfectamente';
        $status_class = 'alert-success';
    } elseif ($success_rate >= 75 && $errors <= 2) {
        $system_status = 'good';
        $status_message = '✅ BUENO - El sistema funciona bien con algunas mejoras menores';
        $status_class = 'alert-success';
    } elseif ($success_rate >= 60) {
        $system_status = 'fair';
        $status_message = '⚠️ REGULAR - El sistema funciona pero necesita atención en algunas áreas';
        $status_class = 'alert-warning';
    } else {
        $system_status = 'poor';
        $status_message = '❌ CRÍTICO - El sistema tiene problemas importantes que requieren atención inmediata';
        $status_class = 'alert-danger';
    }
    
    echo "<div class='alert $status_class'>";
    echo "<h3>$status_message</h3>";
    echo "</div>";
    
    // Recomendaciones específicas
    echo "<h3>💡 Recomendaciones Específicas</h3>";
    
    if ($errors > 0) {
        echo "<div class='alert alert-danger'>";
        echo "<h4>🔴 Errores Críticos a Corregir:</h4>";
        echo "<ul>";
        echo "<li>Revisar archivos faltantes del sistema</li>";
        echo "<li>Verificar configuración de base de datos</li>";
        echo "<li>Comprobar permisos de archivos y carpetas</li>";
        echo "</ul>";
        echo "</div>";
    }
    
    if ($warnings > 0) {
        echo "<div class='alert alert-warning'>";
        echo "<h4>🟡 Mejoras Recomendadas:</h4>";
        echo "<ul>";
        echo "<li>Completar datos de prueba en tablas vacías</li>";
        echo "<li>Implementar funcionalidades avanzadas faltantes</li>";
        echo "<li>Agregar archivos opcionales del sistema</li>";
        echo "</ul>";
        echo "</div>";
    }
    
    // Enlaces de acción
    echo "<h3>🎯 Acciones Rápidas</h3>";
    echo "<div style='text-align: center; margin: 20px 0;'>";
    
    echo "<a href='index.php' class='btn btn-primary'>🏠 Ir al Sistema</a>";
    echo "<a href='productos.php' class='btn btn-success'>📦 Probar Productos</a>";
    echo "<a href='salidas.php' class='btn btn-warning'>📋 Probar Salidas</a>";
    echo "<a href='solucion_definitiva.html' class='btn btn-success'>🔄 Probar Campo Motivo</a>";
    
    echo "</div>";
    
    echo "<script>updateProgress(100, 'Validación completa finalizada');</script>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h2>❌ Error Fatal de Conexión</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>No se pudo conectar a la base de datos. Verifica que XAMPP esté ejecutándose y que MySQL esté activo.</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>