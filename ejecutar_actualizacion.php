<?php
/**
 * EJECUTOR DE ACTUALIZACIONES INVENTIXOR
 * Ejecuta el script de actualización de mejoras
 */

// Configuración de la base de datos
$host = 'localhost';
$username = 'root';
$password = ''; // Cambia si tienes contraseña
$database = 'inventixor';

echo "<html><head><title>Actualizando InventiXor...</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
.container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.info { color: #007bff; }
pre { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🚀 Ejecutando Actualización de InventiXor</h1>";

try {
    // Conectar a la base de datos
    echo "<p class='info'>📡 Conectando a la base de datos...</p>";
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Seleccionar la base de datos
    $pdo->exec("USE inventixor");
    echo "<p class='success'>✅ Conectado exitosamente a la base de datos inventixor</p>";
    
    // Leer el archivo SQL
    echo "<p class='info'>📖 Leyendo el script de actualización...</p>";
    $sql_file = __DIR__ . '/actualizar_mejoras.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("No se encontró el archivo actualizar_mejoras.sql");
    }
    
    $sql_content = file_get_contents($sql_file);
    echo "<p class='success'>✅ Script leído correctamente (" . number_format(strlen($sql_content)) . " caracteres)</p>";
    
    // Dividir el archivo SQL en statements individuales
    echo "<p class='info'>⚙️ Procesando statements SQL...</p>";
    
    // Eliminar comentarios y dividir por delimitador
    $sql_content = preg_replace('/--.*$/m', '', $sql_content); // Eliminar comentarios de línea
    $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content); // Eliminar comentarios de bloque
    
    // Dividir por DELIMITER y procesar
    $parts = preg_split('/DELIMITER\s+(.+)\s*\n/i', $sql_content);
    
    $current_delimiter = ';';
    $statements = [];
    
    for ($i = 0; $i < count($parts); $i++) {
        if ($i % 2 == 1) {
            // Es un nuevo delimitador
            $current_delimiter = trim($parts[$i]);
        } else {
            // Es contenido SQL
            $content = trim($parts[$i]);
            if (!empty($content)) {
                $temp_statements = explode($current_delimiter, $content);
                foreach ($temp_statements as $stmt) {
                    $stmt = trim($stmt);
                    if (!empty($stmt) && $stmt !== 'DELIMITER') {
                        $statements[] = $stmt;
                    }
                }
            }
        }
    }
    
    echo "<p class='success'>✅ " . count($statements) . " statements encontrados</p>";
    
    // Ejecutar cada statement
    $success_count = 0;
    $error_count = 0;
    
    echo "<h3>📋 Ejecutando actualizaciones...</h3>";
    echo "<pre>";
    
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);
        if (empty($statement) || strlen($statement) < 5) continue;
        
        try {
            $pdo->exec($statement);
            $success_count++;
            
            // Mostrar información sobre el statement ejecutado
            if (stripos($statement, 'CREATE TABLE') === 0) {
                preg_match('/CREATE TABLE.*?`?(\w+)`?\s/i', $statement, $matches);
                $table_name = isset($matches[1]) ? $matches[1] : 'desconocida';
                echo "✅ Tabla creada: $table_name\n";
            } elseif (stripos($statement, 'ALTER TABLE') === 0) {
                preg_match('/ALTER TABLE.*?`?(\w+)`?\s/i', $statement, $matches);
                $table_name = isset($matches[1]) ? $matches[1] : 'desconocida';
                echo "✅ Tabla modificada: $table_name\n";
            } elseif (stripos($statement, 'INSERT') === 0) {
                preg_match('/INSERT.*?INTO.*?`?(\w+)`?\s/i', $statement, $matches);
                $table_name = isset($matches[1]) ? $matches[1] : 'desconocida';
                echo "✅ Datos insertados en: $table_name\n";
            } elseif (stripos($statement, 'CREATE TRIGGER') === 0) {
                preg_match('/CREATE TRIGGER.*?`?(\w+)`?\s/i', $statement, $matches);
                $trigger_name = isset($matches[1]) ? $matches[1] : 'desconocido';
                echo "✅ Trigger creado: $trigger_name\n";
            } elseif (stripos($statement, 'CREATE OR REPLACE VIEW') === 0) {
                preg_match('/CREATE OR REPLACE VIEW.*?`?(\w+)`?\s/i', $statement, $matches);
                $view_name = isset($matches[1]) ? $matches[1] : 'desconocida';
                echo "✅ Vista creada: $view_name\n";
            } else {
                echo "✅ Statement ejecutado correctamente\n";
            }
            
        } catch (PDOException $e) {
            $error_count++;
            // Algunos errores son esperados (como si ya existe algo)
            if (stripos($e->getMessage(), 'already exists') !== false || 
                stripos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "ℹ️ Ya existe (omitido): " . substr($statement, 0, 50) . "...\n";
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
                echo "   Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "</pre>";
    
    echo "<h3>📊 Resumen de la Actualización</h3>";
    echo "<p class='success'>✅ Statements ejecutados exitosamente: $success_count</p>";
    if ($error_count > 0) {
        echo "<p class='error'>⚠️ Statements con errores/omitidos: $error_count</p>";
    }
    
    // Verificar que las mejoras se aplicaron
    echo "<h3>🔍 Verificando Mejoras Aplicadas</h3>";
    
    // Verificar columnas de Salidas
    $stmt = $pdo->query("SHOW COLUMNS FROM Salidas LIKE 'estado_salida'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ Columna 'estado_salida' agregada a tabla Salidas</p>";
    }
    
    // Verificar tablas nuevas
    $new_tables = ['ProductosSeguimiento', 'Devoluciones', 'Garantias', 'TiposSalida', 'NotificacionesSistema'];
    foreach ($new_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ Tabla '$table' creada exitosamente</p>";
        }
    }
    
    // Verificar triggers
    $stmt = $pdo->query("SHOW TRIGGERS LIKE 'Salidas'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ Triggers de automatización creados</p>";
    }
    
    echo "<hr>";
    echo "<h2 class='success'>🎉 ¡ACTUALIZACIÓN COMPLETADA EXITOSAMENTE!</h2>";
    echo "<p><strong>El campo motivo ahora funciona como lista desplegable categorizada.</strong></p>";
    echo "<p>📋 <strong>Para probar:</strong> <a href='solucion_definitiva.html' target='_blank'>Abrir página de prueba</a></p>";
    echo "<p>🏠 <strong>Volver al sistema:</strong> <a href='index.php'>InventiXor</a></p>";
    
} catch (Exception $e) {
    echo "<h2 class='error'>❌ Error en la Actualización</h2>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Por favor verifica que:</p>";
    echo "<ul>";
    echo "<li>XAMPP esté ejecutándose</li>";
    echo "<li>MySQL esté activo</li>";
    echo "<li>La base de datos 'inventixor' exista</li>";
    echo "<li>Las credenciales de conexión sean correctas</li>";
    echo "</ul>";
}

echo "</div></body></html>";
?>