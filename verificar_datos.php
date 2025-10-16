<?php
/**
 * VERIFICADOR DE DATOS ACTUALES - INVENTIXOR
 * Revisa qué datos existen después de la actualización
 */

// Configuración de la base de datos
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'inventixor';

echo "<html><head><title>Verificando Datos Actuales...</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 40px; background: #f8f9fa; }
.container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
.success { color: #28a745; font-weight: bold; }
.error { color: #dc3545; font-weight: bold; }
.info { color: #007bff; }
.warning { color: #ffc107; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
.section { margin: 30px 0; padding: 20px; border: 1px solid #e9ecef; border-radius: 8px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔍 Verificación de Datos Actuales - InventiXor</h1>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p class='success'>✅ Conectado a la base de datos inventixor</p>";

    // Tablas principales a verificar
    $tablas = [
        'Users' => 'Usuarios del sistema',
        'Categoria' => 'Categorías de productos', 
        'Subcategoria' => 'Subcategorías de productos',
        'Proveedores' => 'Proveedores de productos',
        'Productos' => 'Productos en inventario',
        'Salidas' => 'Salidas de inventario',
        'Devoluciones' => 'Devoluciones procesadas',
        'TiposSalida' => 'Tipos de salida disponibles',
        'NotificacionesSistema' => 'Notificaciones del sistema'
    ];

    foreach ($tablas as $tabla => $descripcion) {
        echo "<div class='section'>";
        echo "<h3>📋 $tabla - $descripcion</h3>";
        
        try {
            // Contar registros
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
            $count = $stmt->fetch()['total'];
            
            if ($count > 0) {
                echo "<p class='success'>✅ $count registros encontrados</p>";
                
                // Mostrar algunos registros de muestra
                $stmt = $pdo->query("SELECT * FROM $tabla LIMIT 5");
                $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($registros)) {
                    echo "<table>";
                    echo "<tr>";
                    foreach (array_keys($registros[0]) as $columna) {
                        echo "<th>$columna</th>";
                    }
                    echo "</tr>";
                    
                    foreach ($registros as $registro) {
                        echo "<tr>";
                        foreach ($registro as $valor) {
                            $valor_mostrar = strlen($valor) > 50 ? substr($valor, 0, 50) . '...' : $valor;
                            echo "<td>" . htmlspecialchars($valor_mostrar) . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } else {
                echo "<p class='error'>❌ Tabla vacía - Sin datos</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Tabla no existe: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }

    // Verificación especial de datos críticos perdidos
    echo "<div class='section'>";
    echo "<h2>⚠️ Análisis de Datos Perdidos</h2>";
    
    $problemas = [];
    
    // Verificar usuarios
    $users = $pdo->query("SELECT COUNT(*) as total FROM Users")->fetch()['total'];
    if ($users == 0) {
        $problemas[] = "❌ No hay usuarios - Necesitas al menos un usuario administrador";
    } else {
        echo "<p class='info'>✓ $users usuarios en el sistema</p>";
    }
    
    // Verificar categorías
    $categorias = $pdo->query("SELECT COUNT(*) as total FROM Categoria")->fetch()['total'];
    if ($categorias == 0) {
        $problemas[] = "❌ No hay categorías - Necesarias para crear productos";
    } else {
        echo "<p class='info'>✓ $categorias categorías disponibles</p>";
    }
    
    // Verificar subcategorías
    $subcategorias = $pdo->query("SELECT COUNT(*) as total FROM Subcategoria")->fetch()['total'];
    if ($subcategorias == 0) {
        $problemas[] = "❌ No hay subcategorías - Necesarias para crear productos";
    } else {
        echo "<p class='info'>✓ $subcategorias subcategorías disponibles</p>";
    }
    
    // Verificar proveedores
    $proveedores = $pdo->query("SELECT COUNT(*) as total FROM Proveedores")->fetch()['total'];
    if ($proveedores == 0) {
        $problemas[] = "❌ No hay proveedores - Necesarios para crear productos";
    } else {
        echo "<p class='info'>✓ $proveedores proveedores registrados</p>";
    }
    
    // Verificar productos
    $productos = $pdo->query("SELECT COUNT(*) as total FROM Productos")->fetch()['total'];
    if ($productos == 0) {
        $problemas[] = "❌ No hay productos - El inventario está vacío";
    } else {
        echo "<p class='info'>✓ $productos productos en inventario</p>";
    }
    
    if (!empty($problemas)) {
        echo "<div class='warning'>";
        echo "<h4>🚨 Problemas Detectados:</h4>";
        echo "<ul>";
        foreach ($problemas as $problema) {
            echo "<li>$problema</li>";
        }
        echo "</ul>";
        echo "<p><strong>Recomendación:</strong> Ejecutar script de datos de prueba para restaurar información básica del sistema.</p>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h4>✅ Sistema con Datos Básicos Completos</h4>";
        echo "<p>El sistema tiene los datos mínimos necesarios para funcionar.</p>";
        echo "</div>";
    }
    
    echo "</div>";

    // Generar recomendaciones
    echo "<div class='section'>";
    echo "<h2>💡 Recomendaciones</h2>";
    
    if (!empty($problemas)) {
        echo "<div class='warning'>";
        echo "<h4>🔧 Acciones Recomendadas:</h4>";
        echo "<ol>";
        echo "<li><strong>Crear script de datos de prueba</strong> - Restaurar usuarios, categorías, proveedores y productos</li>";
        echo "<li><strong>Ejecutar inserción de datos</strong> - Poblar tablas con información de muestra</li>";
        echo "<li><strong>Verificar funcionalidad</strong> - Probar todos los módulos del sistema</li>";
        echo "</ol>";
        echo "<p><a href='#' onclick='location.reload()' class='btn'>🔄 Verificar Nuevamente</a></p>";
        echo "</div>";
    } else {
        echo "<p class='success'>El sistema está listo para usar. Si necesitas más datos de prueba, puedes ejecutar el script de datos adicionales.</p>";
    }
    
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error de Conexión</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>