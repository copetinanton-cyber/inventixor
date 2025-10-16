<?php
// Verificar estado actual de la base de datos después de los cambios
require_once 'app/helpers/Database.php';

$db = new Database();
$conn = $db->conn;

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Diagnóstico de Base de Datos - Inventixor</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <div class='row justify-content-center'>
        <div class='col-md-10'>
            <div class='card shadow'>
                <div class='card-header bg-info text-white'>
                    <h3 class='mb-0'><i class='fas fa-search me-2'></i>Diagnóstico de Base de Datos</h3>
                </div>
                <div class='card-body'>";

try {
    // 1. Verificar conexión a la base de datos
    echo "<div class='alert alert-success'>
            <i class='fas fa-check-circle me-2'></i>
            <strong>✅ Conexión exitosa</strong> a la base de datos 'inventixor'
          </div>";
    
    // 2. Contar registros en cada tabla
    echo "<h4><i class='fas fa-table me-2'></i>Estado de las Tablas:</h4>";
    
    $tablas = [
        'Categoria' => 'SELECT COUNT(*) as count FROM Categoria',
        'Subcategoria' => 'SELECT COUNT(*) as count FROM Subcategoria', 
        'Productos' => 'SELECT COUNT(*) as count FROM Productos',
        'Salidas' => 'SELECT COUNT(*) as count FROM Salidas'
    ];
    
    echo "<div class='row mb-4'>";
    foreach ($tablas as $tabla => $query) {
        $result = $conn->query($query);
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            $color = $count > 0 ? 'success' : 'danger';
            echo "<div class='col-md-3 mb-3'>
                    <div class='card border-$color'>
                        <div class='card-body text-center'>
                            <h3 class='text-$color'>$count</h3>
                            <p class='mb-0'>$tabla</p>
                        </div>
                    </div>
                  </div>";
        } else {
            echo "<div class='col-md-3 mb-3'>
                    <div class='card border-danger'>
                        <div class='card-body text-center'>
                            <h3 class='text-danger'>ERROR</h3>
                            <p class='mb-0'>$tabla</p>
                        </div>
                    </div>
                  </div>";
        }
    }
    echo "</div>";
    
    // 3. Mostrar categorías actuales
    echo "<h4><i class='fas fa-tags me-2'></i>Categorías Actuales:</h4>";
    $result = $conn->query("SELECT * FROM Categoria ORDER BY id_categ");
    
    if ($result && $result->num_rows > 0) {
        echo "<div class='table-responsive mb-4'>
                <table class='table table-striped'>
                    <thead class='table-dark'>
                        <tr>
                            <th>ID</th>
                            <th>Nombre de Categoría</th>
                        </tr>
                    </thead>
                    <tbody>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td><strong>{$row['id_categ']}</strong></td>
                    <td>{$row['nombre']}</td>
                  </tr>";
        }
        echo "</tbody></table></div>";
    } else {
        echo "<div class='alert alert-warning'>
                <i class='fas fa-exclamation-triangle me-2'></i>
                <strong>No hay categorías</strong> en la base de datos
              </div>";
    }
    
    // 4. Mostrar subcategorías actuales
    echo "<h4><i class='fas fa-shoe-prints me-2'></i>Subcategorías (Marcas) Actuales:</h4>";
    $result = $conn->query("
        SELECT s.id_subcg, s.nombre as marca, c.nombre as categoria 
        FROM Subcategoria s 
        LEFT JOIN Categoria c ON s.id_categ = c.id_categ 
        ORDER BY c.id_categ, s.nombre
    ");
    
    if ($result && $result->num_rows > 0) {
        echo "<div class='table-responsive mb-4'>
                <table class='table table-striped'>
                    <thead class='table-dark'>
                        <tr>
                            <th>ID</th>
                            <th>Marca</th>
                            <th>Categoría</th>
                        </tr>
                    </thead>
                    <tbody>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td><strong>{$row['id_subcg']}</strong></td>
                    <td>{$row['marca']}</td>
                    <td><span class='badge bg-primary'>{$row['categoria']}</span></td>
                  </tr>";
        }
        echo "</tbody></table></div>";
    } else {
        echo "<div class='alert alert-warning'>
                <i class='fas fa-exclamation-triangle me-2'></i>
                <strong>No hay subcategorías</strong> en la base de datos
              </div>";
    }
    
    // 5. Mostrar algunos productos
    echo "<h4><i class='fas fa-box me-2'></i>Productos Actuales (Primeros 10):</h4>";
    $result = $conn->query("
        SELECT p.codigo, p.nombre, s.nombre as marca, c.nombre as categoria, p.stock_actual
        FROM Productos p
        LEFT JOIN Subcategoria s ON p.id_subcg = s.id_subcg
        LEFT JOIN Categoria c ON s.id_categ = c.id_categ
        ORDER BY p.id_prod
        LIMIT 10
    ");
    
    if ($result && $result->num_rows > 0) {
        echo "<div class='table-responsive mb-4'>
                <table class='table table-striped'>
                    <thead class='table-dark'>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Marca</th>
                            <th>Categoría</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td><code>{$row['codigo']}</code></td>
                    <td>{$row['nombre']}</td>
                    <td><span class='badge bg-info'>{$row['marca']}</span></td>
                    <td><span class='badge bg-success'>{$row['categoria']}</span></td>
                    <td><strong>{$row['stock_actual']}</strong></td>
                  </tr>";
        }
        echo "</tbody></table></div>";
    } else {
        echo "<div class='alert alert-warning'>
                <i class='fas fa-exclamation-triangle me-2'></i>
                <strong>No hay productos</strong> en la base de datos
              </div>";
    }
    
    // 5.1 Mostrar ventas recientes
    echo "<h4><i class='fas fa-shopping-cart me-2'></i>Ventas Recientes (Primeros 10):</h4>";
    $result = $conn->query("
        SELECT s.codigo_salida, s.descripcion, s.cantidad, s.precio_unitario, s.precio_total, s.fecha_salida, p.nombre as producto, sc.nombre as marca, c.nombre as categoria, s.motivo, s.procesada_devolucion
        FROM Salidas s
        LEFT JOIN Productos p ON s.id_prod = p.id_prod
        LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
        LEFT JOIN Categoria c ON sc.id_categ = c.id_categ
        ORDER BY s.fecha_salida DESC, s.codigo_salida DESC
        LIMIT 10
    ");
    if ($result && $result->num_rows > 0) {
        echo "<div class='table-responsive mb-4'>
                <table class='table table-striped'>
                    <thead class='table-dark'>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Marca</th>
                            <th>Categoría</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th>Motivo</th>
                            <th>Devolución</th>
                        </tr>
                    </thead>
                    <tbody>";
        while ($row = $result->fetch_assoc()) {
            $devolucion = $row['procesada_devolucion'] ? '<span class="badge bg-danger">Sí</span>' : '<span class="badge bg-success">No</span>';
            echo "<tr>
                    <td><code>{$row['codigo_salida']}</code></td>
                    <td>{$row['producto']}</td>
                    <td><span class='badge bg-info'>{$row['marca']}</span></td>
                    <td><span class='badge bg-success'>{$row['categoria']}</span></td>
                    <td>{$row['cantidad']}</td>
                    <td>", number_format($row['precio_unitario'], 0, ',', '.'), "</td>
                    <td>", number_format($row['precio_total'], 0, ',', '.'), "</td>
                    <td>{$row['fecha_salida']}</td>
                    <td>{$row['motivo']}</td>
                    <td>$devolucion</td>
                  </tr>";
        }
        echo "</tbody></table></div>";
    } else {
        echo "<div class='alert alert-warning'>
                <i class='fas fa-exclamation-triangle me-2'></i>
                <strong>No hay ventas</strong> en la base de datos
              </div>";
    }
    // 6. Verificar si el archivo SQL existe
    echo "<h4><i class='fas fa-file-code me-2'></i>Verificación de Archivos:</h4>";
    $archivos = [
        'datos_zapatos_corregidos.sql' => 'Archivo de datos corregidos',
        'aplicar_tienda_zapatos.php' => 'Script de aplicación'
    ];
    
    foreach ($archivos as $archivo => $descripcion) {
        $existe = file_exists($archivo);
        $color = $existe ? 'success' : 'danger';
        $icono = $existe ? 'check' : 'times';
        echo "<div class='alert alert-$color'>
                <i class='fas fa-$icono me-2'></i>
                <strong>$descripcion:</strong> " . ($existe ? 'Encontrado' : 'NO encontrado') . " ($archivo)
              </div>";
    }
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>
            <i class='fas fa-exclamation-circle me-2'></i>
            <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "
          </div>";
}

echo "         <div class='mt-4 text-center'>
                <a href='aplicar_tienda_zapatos.php' class='btn btn-primary me-2'>
                    <i class='fas fa-play me-1'></i>Ejecutar Aplicación de Datos
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