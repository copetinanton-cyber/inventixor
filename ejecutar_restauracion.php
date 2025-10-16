<?php
/**
 * EJECUTOR DE RESTAURACIÓN DE DATOS - INVENTIXOR
 * Ejecuta el script de restauración de datos de prueba
 */

// Configuración de la base de datos
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'inventixor';

echo "<html><head><title>Restaurando Datos de Prueba...</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
.container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.info { color: #007bff; }
.warning { color: #856404; background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #ffc107; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; font-size: 14px; max-height: 400px; overflow-y: auto; }
.step { background: #e9ecef; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #6c757d; }
.completed { border-left-color: #28a745; background: #d4edda; }
.progress-bar { width: 100%; background-color: #e9ecef; border-radius: 10px; overflow: hidden; margin: 20px 0; }
.progress-fill { height: 25px; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s ease; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔄 Restaurando Datos de Prueba - InventiXor</h1>";

try {
    // Conectar a la base de datos
    echo "<p class='info'>📡 Conectando a la base de datos...</p>";
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p class='success'>✅ Conectado exitosamente a la base de datos inventixor</p>";
    
    // Leer el archivo SQL
    echo "<p class='info'>📖 Leyendo el script de restauración...</p>";
    $sql_file = __DIR__ . '/restaurar_datos_prueba.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("No se encontró el archivo restaurar_datos_prueba.sql");
    }
    
    $sql_content = file_get_contents($sql_file);
    echo "<p class='success'>✅ Script leído correctamente (" . number_format(strlen($sql_content)) . " caracteres)</p>";
    
    // Progreso visual
    echo "<div class='progress-bar'><div class='progress-fill' id='progressBar' style='width: 10%;'></div></div>";
    echo "<p id='progressText'>Preparando restauración...</p>";
    
    // Dividir el archivo SQL en statements individuales
    echo "<p class='info'>⚙️ Procesando statements SQL...</p>";
    
    // Eliminar comentarios y procesar
    $sql_content = preg_replace('/--.*$/m', '', $sql_content);
    $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);
    
    // Dividir por punto y coma
    $statements = array_filter(array_map('trim', explode(';', $sql_content)));
    
    echo "<p class='success'>✅ " . count($statements) . " statements encontrados</p>";
    
    // Actualizar progreso
    echo "<script>
    document.getElementById('progressBar').style.width = '20%';
    document.getElementById('progressText').textContent = 'Ejecutando inserción de datos...';
    </script>";
    
    // Ejecutar cada statement
    $success_count = 0;
    $error_count = 0;
    $categories_processed = [];
    
    echo "<h3>📋 Ejecutando restauración paso a paso...</h3>";
    
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement) || strlen($statement) < 10) continue;
        
        try {
            $pdo->exec($statement);
            $success_count++;
            
            // Mostrar información sobre el statement ejecutado
            if (stripos($statement, 'INSERT IGNORE INTO Categoria') === 0) {
                if (!in_array('categorias', $categories_processed)) {
                    echo "<div class='step completed'>✅ Categorías de productos insertadas</div>";
                    $categories_processed[] = 'categorias';
                }
            } elseif (stripos($statement, 'INSERT IGNORE INTO Subcategoria') === 0) {
                if (!in_array('subcategorias', $categories_processed)) {
                    echo "<div class='step completed'>✅ Subcategorías de productos insertadas</div>";
                    $categories_processed[] = 'subcategorias';
                }
            } elseif (stripos($statement, 'INSERT IGNORE INTO Proveedores') === 0) {
                if (!in_array('proveedores', $categories_processed)) {
                    echo "<div class='step completed'>✅ Proveedores de prueba insertados</div>";
                    $categories_processed[] = 'proveedores';
                }
            } elseif (stripos($statement, 'INSERT IGNORE INTO Productos') === 0) {
                if (!in_array('productos', $categories_processed)) {
                    echo "<div class='step completed'>✅ Productos de inventario insertados</div>";
                    $categories_processed[] = 'productos';
                }
            } elseif (stripos($statement, 'INSERT IGNORE INTO Salidas') === 0) {
                if (!in_array('salidas', $categories_processed)) {
                    echo "<div class='step completed'>✅ Salidas de ejemplo insertadas</div>";
                    $categories_processed[] = 'salidas';
                }
            } elseif (stripos($statement, 'UPDATE Productos SET stock') === 0) {
                if (!in_array('stock_updates', $categories_processed)) {
                    echo "<div class='step completed'>✅ Stock de productos actualizado</div>";
                    $categories_processed[] = 'stock_updates';
                }
            } elseif (stripos($statement, 'INSERT IGNORE INTO ProductosSeguimiento') === 0) {
                if (!in_array('seguimiento', $categories_processed)) {
                    echo "<div class='step completed'>✅ Seguimiento de productos configurado</div>";
                    $categories_processed[] = 'seguimiento';
                }
            } elseif (stripos($statement, 'INSERT IGNORE INTO Garantias') === 0) {
                if (!in_array('garantias', $categories_processed)) {
                    echo "<div class='step completed'>✅ Garantías de productos registradas</div>";
                    $categories_processed[] = 'garantias';
                }
            } elseif (false) { // Código eliminado - HistorialMovimientos deshabilitado
                if (!in_array('historial', $categories_processed)) {
                    echo "<div class='step completed'>✅ Historial de movimientos creado</div>";
                    $categories_processed[] = 'historial';
                }
            } elseif (stripos($statement, 'INSERT IGNORE INTO NotificacionesSistema') === 0) {
                if (!in_array('notificaciones', $categories_processed)) {
                    echo "<div class='step completed'>✅ Notificaciones del sistema configuradas</div>";
                    $categories_processed[] = 'notificaciones';
                }
            }
            
        } catch (PDOException $e) {
            $error_count++;
            // Algunos errores son esperados (como si ya existe algo)
            if (stripos($e->getMessage(), 'Duplicate entry') !== false) {
                // Ignorar duplicados
            } else {
                echo "<div class='step' style='border-left-color: #ffc107;'>⚠️ Advertencia: " . $e->getMessage() . "</div>";
            }
        }
        
        // Actualizar progreso
        $progress = 20 + (($index + 1) / count($statements)) * 70;
        echo "<script>
        document.getElementById('progressBar').style.width = '{$progress}%';
        document.getElementById('progressText').textContent = 'Procesando... (" . ($index + 1) . "/" . count($statements) . ")';
        </script>";
        
        // Pequeña pausa para mostrar progreso
        usleep(10000); // 0.01 segundos
    }
    
    // Finalizar progreso
    echo "<script>
    document.getElementById('progressBar').style.width = '100%';
    document.getElementById('progressText').textContent = 'Restauración completada exitosamente';
    </script>";
    
    echo "<h3>📊 Resumen de la Restauración</h3>";
    echo "<p class='success'>✅ Statements ejecutados exitosamente: $success_count</p>";
    if ($error_count > 0) {
        echo "<p class='warning'>⚠️ Advertencias menores: $error_count (generalmente por datos existentes)</p>";
    }
    
    // Verificar que los datos se insertaron correctamente
    echo "<h3>🔍 Verificación de Datos Restaurados</h3>";
    
    $verificaciones = [
        'Categoria' => 'Categorías de productos',
        'Subcategoria' => 'Subcategorías de productos', 
        'Proveedores' => 'Proveedores registrados',
        'Productos' => 'Productos en inventario',
        'Salidas' => 'Salidas de ejemplo'
    ];
    
    foreach ($verificaciones as $tabla => $descripcion) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
        $count = $stmt->fetch()['total'];
        
        if ($count > 0) {
            echo "<div class='step completed'>✅ $descripcion: $count registros</div>";
        } else {
            echo "<div class='step' style='border-left-color: #dc3545;'>❌ $descripcion: Sin datos</div>";
        }
    }
    
    // Mostrar algunos productos de ejemplo
    echo "<h3>📦 Algunos Productos Restaurados</h3>";
    $stmt = $pdo->query("SELECT p.nombre, p.modelo, p.stock, p.precio_unitario, pr.razon_social 
                         FROM Productos p 
                         LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit 
                         LIMIT 10");
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($productos)) {
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
        echo "<table style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #e9ecef;'>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Producto</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Modelo</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Stock</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Precio</th>";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Proveedor</th>";
        echo "</tr>";
        
        foreach ($productos as $producto) {
            echo "<tr>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($producto['nombre']) . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($producto['modelo']) . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $producto['stock'] . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>$" . number_format($producto['precio_unitario']) . "</td>";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($producto['razon_social'] ?? 'Sin proveedor') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<div style='background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0;'>";
    echo "<h2>🎉 ¡DATOS DE PRUEBA RESTAURADOS EXITOSAMENTE!</h2>";
    echo "<p><strong>El sistema InventiXor está listo para usar con datos completos de prueba.</strong></p>";
    echo "<p>📋 <strong>Ingresar al sistema:</strong> <a href='index.php' style='color: #fff; text-decoration: underline;'>InventiXor - Login</a></p>";
    echo "<p>🏪 <strong>Ver productos:</strong> <a href='productos.php' style='color: #fff; text-decoration: underline;'>Gestión de Productos</a></p>";
    echo "<p>📦 <strong>Ver salidas:</strong> <a href='salidas.php' style='color: #fff; text-decoration: underline;'>Gestión de Salidas</a></p>";
    echo "<p>🔙 <strong>Probar devoluciones:</strong> <a href='solucion_definitiva.html' style='color: #fff; text-decoration: underline;'>Campo Motivo - Prueba</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 class='error'>❌ Error en la Restauración</h2>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<div class='warning'>";
    echo "<p>Por favor verifica que:</p>";
    echo "<ul>";
    echo "<li>XAMPP esté ejecutándose correctamente</li>";
    echo "<li>MySQL esté activo y funcionando</li>";
    echo "<li>La base de datos 'inventixor' exista</li>";
    echo "<li>Las credenciales de conexión sean correctas</li>";
    echo "</ul>";
    echo "</div>";
}

echo "</div></body></html>";
?>