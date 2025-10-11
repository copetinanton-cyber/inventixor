<?php
/**
 * Script de configuración e instalación del sistema de notificaciones
 * Este script verifica si las tablas existen y las crea si es necesario
 */

require_once 'app/helpers/Database.php';

try {
    $db = new Database();
    echo "<h2>Configuración del Sistema de Notificaciones - InventiXor</h2>\n";
    
    // Verificar si las tablas de notificaciones existen
    $tables_to_check = [
        'NotificacionesSistema',
        'NotificacionesVistas', 
        'ConfigNotificaciones'
    ];
    
    echo "<h3>1. Verificando tablas existentes...</h3>\n";
    $missing_tables = [];
    
    foreach ($tables_to_check as $table) {
        $result = $db->conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows == 0) {
            $missing_tables[] = $table;
            echo "❌ Tabla '$table' NO existe<br>\n";
        } else {
            echo "✅ Tabla '$table' existe<br>\n";
        }
    }
    
    if (!empty($missing_tables)) {
        echo "<h3>2. Creando tablas faltantes...</h3>\n";
        
        // Crear tabla NotificacionesSistema
        if (in_array('NotificacionesSistema', $missing_tables)) {
            echo "Creando tabla NotificacionesSistema...<br>\n";
            $sql = "CREATE TABLE NotificacionesSistema (
                id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
                tipo_evento VARCHAR(50) NOT NULL,
                titulo VARCHAR(200) NOT NULL,
                mensaje TEXT NOT NULL,
                nivel_prioridad ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media',
                datos_evento JSON,
                icono VARCHAR(50) DEFAULT 'fas fa-bell',
                usuario_creador VARCHAR(100),
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                mostrar_hasta DATETIME NULL,
                activo BOOLEAN DEFAULT TRUE,
                INDEX idx_tipo_evento (tipo_evento),
                INDEX idx_fecha_creacion (fecha_creacion),
                INDEX idx_activo (activo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if ($db->conn->query($sql)) {
                echo "✅ Tabla NotificacionesSistema creada<br>\n";
            } else {
                echo "❌ Error creando NotificacionesSistema: " . $db->conn->error . "<br>\n";
            }
        }
        
        // Crear tabla NotificacionesVistas
        if (in_array('NotificacionesVistas', $missing_tables)) {
            echo "Creando tabla NotificacionesVistas...<br>\n";
            $sql = "CREATE TABLE NotificacionesVistas (
                id_vista INT AUTO_INCREMENT PRIMARY KEY,
                id_notificacion INT NOT NULL,
                usuario_doc VARCHAR(20) NOT NULL,
                fecha_vista TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_notificacion) REFERENCES NotificacionesSistema(id_notificacion) ON DELETE CASCADE,
                UNIQUE KEY unique_user_notification (id_notificacion, usuario_doc),
                INDEX idx_usuario_doc (usuario_doc),
                INDEX idx_fecha_vista (fecha_vista)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if ($db->conn->query($sql)) {
                echo "✅ Tabla NotificacionesVistas creada<br>\n";
            } else {
                echo "❌ Error creando NotificacionesVistas: " . $db->conn->error . "<br>\n";
            }
        }
        
        // Crear tabla ConfigNotificaciones
        if (in_array('ConfigNotificaciones', $missing_tables)) {
            echo "Creando tabla ConfigNotificaciones...<br>\n";
            $sql = "CREATE TABLE ConfigNotificaciones (
                id_config INT AUTO_INCREMENT PRIMARY KEY,
                tipo_evento VARCHAR(50) NOT NULL UNIQUE,
                habilitado BOOLEAN DEFAULT TRUE,
                nivel_prioridad_default ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media',
                template_titulo VARCHAR(200) NOT NULL,
                template_mensaje TEXT NOT NULL,
                icono VARCHAR(50) DEFAULT 'fas fa-bell',
                duracion_horas INT DEFAULT 24,
                fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_tipo_evento (tipo_evento),
                INDEX idx_habilitado (habilitado)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if ($db->conn->query($sql)) {
                echo "✅ Tabla ConfigNotificaciones creada<br>\n";
                
                // Insertar configuraciones predeterminadas
                echo "Insertando configuraciones predeterminadas...<br>\n";
                $configs = [
                    [
                        'tipo_evento' => 'producto_eliminado',
                        'nivel_prioridad_default' => 'alta',
                        'template_titulo' => 'Producto Eliminado',
                        'template_mensaje' => 'El producto "{producto_nombre}" (ID: {producto_id}) ha sido eliminado del sistema por {usuario}',
                        'icono' => 'fas fa-trash-alt'
                    ],
                    [
                        'tipo_evento' => 'nuevo_producto', 
                        'nivel_prioridad_default' => 'media',
                        'template_titulo' => 'Nuevo Producto',
                        'template_mensaje' => 'Se ha agregado el producto "{producto_nombre}" (ID: {producto_id}) por {usuario}',
                        'icono' => 'fas fa-plus-circle'
                    ],
                    [
                        'tipo_evento' => 'stock_bajo',
                        'nivel_prioridad_default' => 'alta', 
                        'template_titulo' => 'Stock Bajo',
                        'template_mensaje' => 'El producto "{producto_nombre}" tiene stock bajo: {stock_actual} unidades',
                        'icono' => 'fas fa-exclamation-triangle'
                    ],
                    [
                        'tipo_evento' => 'nueva_categoria',
                        'nivel_prioridad_default' => 'baja',
                        'template_titulo' => 'Nueva Categoría',
                        'template_mensaje' => 'Se ha creado la categoría "{categoria_nombre}" por {usuario}',
                        'icono' => 'fas fa-folder-plus'
                    ],
                    [
                        'tipo_evento' => 'categoria_eliminada',
                        'nivel_prioridad_default' => 'media',
                        'template_titulo' => 'Categoría Eliminada', 
                        'template_mensaje' => 'La categoría "{categoria_nombre}" ha sido eliminada por {usuario}',
                        'icono' => 'fas fa-folder-minus'
                    ],
                    [
                        'tipo_evento' => 'nueva_subcategoria',
                        'nivel_prioridad_default' => 'baja',
                        'template_titulo' => 'Nueva Subcategoría',
                        'template_mensaje' => 'Se ha creado la subcategoría "{subcategoria_nombre}" en {categoria_nombre} por {usuario}',
                        'icono' => 'fas fa-sitemap'
                    ],
                    [
                        'tipo_evento' => 'subcategoria_eliminada',
                        'nivel_prioridad_default' => 'media',
                        'template_titulo' => 'Subcategoría Eliminada',
                        'template_mensaje' => 'La subcategoría "{subcategoria_nombre}" ha sido eliminada por {usuario}',
                        'icono' => 'fas fa-sitemap'
                    ],
                    [
                        'tipo_evento' => 'nuevo_proveedor',
                        'nivel_prioridad_default' => 'baja',
                        'template_titulo' => 'Nuevo Proveedor',
                        'template_mensaje' => 'Se ha registrado el proveedor "{proveedor_nombre}" por {usuario}',
                        'icono' => 'fas fa-truck'
                    ],
                    [
                        'tipo_evento' => 'proveedor_eliminado',
                        'nivel_prioridad_default' => 'media',
                        'template_titulo' => 'Proveedor Eliminado',
                        'template_mensaje' => 'El proveedor "{proveedor_nombre}" ha sido eliminado por {usuario}',
                        'icono' => 'fas fa-truck'
                    ],
                    [
                        'tipo_evento' => 'salida_eliminada',
                        'nivel_prioridad_default' => 'alta',
                        'template_titulo' => 'Salida Eliminada',
                        'template_mensaje' => 'Se eliminó una salida de {cantidad} unidades del producto "{producto_nombre}" por {usuario}',
                        'icono' => 'fas fa-undo-alt'
                    ]
                ];
                
                foreach ($configs as $config) {
                    $stmt = $db->conn->prepare(
                        "INSERT INTO ConfigNotificaciones (tipo_evento, nivel_prioridad_default, template_titulo, template_mensaje, icono) 
                         VALUES (?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param('sssss', 
                        $config['tipo_evento'],
                        $config['nivel_prioridad_default'], 
                        $config['template_titulo'],
                        $config['template_mensaje'],
                        $config['icono']
                    );
                    $stmt->execute();
                    $stmt->close();
                }
                echo "✅ Configuraciones predeterminadas insertadas<br>\n";
            } else {
                echo "❌ Error creando ConfigNotificaciones: " . $db->conn->error . "<br>\n";
            }
        }
    }
    
    echo "<h3>3. Verificación final...</h3>\n";
    
    // Verificar que las tablas ahora existen
    $all_exist = true;
    foreach ($tables_to_check as $table) {
        $result = $db->conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows == 0) {
            echo "❌ Tabla '$table' aún no existe<br>\n";
            $all_exist = false;
        } else {
            echo "✅ Tabla '$table' confirmada<br>\n";
        }
    }
    
    if ($all_exist) {
        echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0;'>";
        echo "<strong>🎉 ¡Sistema de notificaciones configurado correctamente!</strong><br>";
        echo "Todas las tablas están creadas y configuradas. El sistema está listo para usar.";
        echo "</div>";
        
        // Crear una notificación de prueba
        echo "<h3>4. Creando notificación de prueba...</h3>\n";
        $sql = "INSERT INTO NotificacionesSistema (tipo_evento, titulo, mensaje, nivel_prioridad, datos_evento, icono) 
                VALUES ('sistema_iniciado', 'Sistema de Notificaciones Activo', 'El sistema de notificaciones automáticas ha sido configurado y está funcionando correctamente.', 'media', '{}', 'fas fa-check-circle')";
        
        if ($db->conn->query($sql)) {
            echo "✅ Notificación de prueba creada<br>\n";
            echo "<p><strong>Tip:</strong> Abre cualquier página del sistema y deberías ver la notificación de prueba aparecer automáticamente.</p>\n";
        }
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
        echo "<strong>❌ Error en la configuración</strong><br>";
        echo "Algunas tablas no se pudieron crear. Revisa los errores mostrados arriba.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
h3 { color: #666; margin-top: 20px; }
</style>