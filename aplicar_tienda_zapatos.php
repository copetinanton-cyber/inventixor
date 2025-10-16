<?php
// ========================================
// APLICAR DATOS DE TIENDA DE ZAPATOS - INVENTIXOR
// Script para transformar la base de datos a una tienda especializada en calzado
// ========================================

require_once 'app/helpers/Database.php';

$db = new Database();
$conn = $db->conn;

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Aplicar Datos de Tienda de Zapatos - Inventixor</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-5'>
    <div class='row justify-content-center'>
        <div class='col-md-10'>
            <div class='card shadow'>
                <div class='card-header bg-primary text-white'>
                    <h3 class='mb-0'><i class='fas fa-shoe-prints me-2'></i>Datos Corregidos - Tienda de Zapatos</h3>
                </div>
                <div class='card-body'>";

try {
    // Leer el archivo SQL corregido
    $sqlFile = 'datos_zapatos_corregidos.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("Archivo SQL no encontrado: $sqlFile");
    }
    
    $sqlContent = file_get_contents($sqlFile);
    
    if ($sqlContent === false) {
        throw new Exception("No se pudo leer el archivo SQL");
    }
    
    echo "<div class='alert alert-info'>
            <i class='fas fa-info-circle me-2'></i>
            Iniciando transformación de datos a tienda de zapatos...
          </div>";
    
    // Dividir el contenido en declaraciones SQL individuales
    $statements = array_filter(
        array_map('trim', explode(';', $sqlContent)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt) && !preg_match('/^\s*USE/', $stmt);
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    // Ejecutar cada declaración
    foreach ($statements as $statement) {
        if (trim($statement)) {
            try {
                $result = $conn->query($statement);
                if ($result) {
                    $successCount++;
                } else {
                    $errorCount++;
                    $errors[] = "Error en consulta: " . $conn->error;
                }
            } catch (Exception $e) {
                $errorCount++;
                $errors[] = "Excepción: " . $e->getMessage();
            }
        }
    }
    
    // Mostrar resultados
    if ($errorCount == 0) {
        echo "<div class='alert alert-success'>
                <i class='fas fa-check-circle me-2'></i>
                <strong>¡Transformación completada exitosamente!</strong>
              </div>";
    } else {
        echo "<div class='alert alert-warning'>
                <i class='fas fa-exclamation-triangle me-2'></i>
                <strong>Transformación completada con algunas advertencias</strong>
              </div>";
    }
    
    echo "<div class='row'>
            <div class='col-md-6'>
                <div class='card bg-success text-white mb-3'>
                    <div class='card-body text-center'>
                        <h4><i class='fas fa-check me-2'></i>$successCount</h4>
                        <p class='mb-0'>Consultas Exitosas</p>
                    </div>
                </div>
            </div>
            <div class='col-md-6'>
                <div class='card bg-" . ($errorCount > 0 ? 'warning' : 'secondary') . " text-white mb-3'>
                    <div class='card-body text-center'>
                        <h4><i class='fas fa-exclamation me-2'></i>$errorCount</h4>
                        <p class='mb-0'>Errores/Advertencias</p>
                    </div>
                </div>
            </div>
          </div>";
    
    // Mostrar errores si los hay
    if (!empty($errors)) {
        echo "<div class='alert alert-warning'>
                <h6><i class='fas fa-list me-2'></i>Detalles de errores:</h6>
                <ul class='mb-0'>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul></div>";
    }
    
    // Verificar datos insertados
    echo "<h5 class='mt-4'><i class='fas fa-chart-bar me-2'></i>Resumen de Datos Insertados:</h5>";
    
    $queries = [
        'Categorías' => "SELECT COUNT(*) as count FROM Categoria",
        'Subcategorías (Marcas)' => "SELECT COUNT(*) as count FROM Subcategoria", 
        'Productos' => "SELECT COUNT(*) as count FROM Productos",
        'Salidas/Ventas' => "SELECT COUNT(*) as count FROM Salidas"
    ];
    
    echo "<div class='row'>";
    foreach ($queries as $label => $query) {
        $result = $conn->query($query);
        $count = $result->fetch_assoc()['count'];
        
        echo "<div class='col-md-3 mb-3'>
                <div class='card border-primary'>
                    <div class='card-body text-center'>
                        <h4 class='text-primary'>$count</h4>
                        <p class='mb-0 small'>$label</p>
                    </div>
                </div>
              </div>";
    }
    echo "</div>";
    
    // Mostrar ejemplos de categorías y marcas
    echo "<h5 class='mt-4'><i class='fas fa-tags me-2'></i>Categorías Creadas:</h5>";
    $result = $conn->query("SELECT * FROM Categoria ORDER BY id_categ");
    echo "<div class='row'>";
    while ($row = $result->fetch_assoc()) {
        echo "<div class='col-md-4 mb-2'>
                <div class='card border-info'>
                    <div class='card-body py-2'>
                        <small><strong>{$row['nombre']}</strong></small>
                    </div>
                </div>
              </div>";
    }
    echo "</div>";
    
    echo "<h5 class='mt-4'><i class='fas fa-shoe-prints me-2'></i>Marcas por Categoría:</h5>";
    $result = $conn->query("
        SELECT c.nombre as categoria, GROUP_CONCAT(s.nombre SEPARATOR ', ') as marcas 
        FROM Categoria c 
        LEFT JOIN Subcategoria s ON c.id_categ = s.id_categ 
        GROUP BY c.id_categ, c.nombre 
        ORDER BY c.id_categ
    ");
    
    while ($row = $result->fetch_assoc()) {
        echo "<div class='card mb-3'>
                <div class='card-header bg-light'>
                    <strong>{$row['categoria']}</strong>
                </div>
                <div class='card-body'>
                    <small>{$row['marcas']}</small>
                </div>
              </div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <i class='fas fa-exclamation-circle me-2'></i>
            <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "
          </div>";
}

echo "         <div class='mt-4 text-center'>
                <a href='index.php' class='btn btn-primary me-2'>
                    <i class='fas fa-home me-1'></i>Ir al Dashboard
                </a>
                <a href='productos.php' class='btn btn-success me-2'>
                    <i class='fas fa-shoe-prints me-1'></i>Ver Productos
                </a>
                <a href='categorias.php' class='btn btn-info'>
                    <i class='fas fa-tags me-1'></i>Ver Categorías
                </a>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>";
?>