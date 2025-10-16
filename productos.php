<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

require_once 'app/helpers/Database.php';
require_once 'app/helpers/SistemaNotificaciones.php';

$db = new Database();
$sistemaNotificaciones = new SistemaNotificaciones($db);

// Asegurar que $_SESSION['rol'] esté definido
if (!isset($_SESSION['rol'])) {
    if (isset($_SESSION['user']['num_doc'])) {
        $num_doc = $_SESSION['user']['num_doc'];
        $rolRes = $db->conn->query("SELECT rol FROM users WHERE num_doc = '$num_doc'");
        if ($rolRes && $rolRow = $rolRes->fetch_assoc()) {
            $_SESSION['rol'] = $rolRow['rol'];
        } else {
            $_SESSION['rol'] = '';
        }
    } else {
        $_SESSION['rol'] = '';
    }
}

// Verificar permisos de edición
$puede_editar = $_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'coordinador';

// Eliminar producto
if (isset($_GET['eliminar']) && $puede_editar) {
    $id_producto = intval($_GET['eliminar']);
    
    // Verificar si tiene salidas asociadas
    $salidas = $db->conn->query("SELECT COUNT(*) FROM Salidas WHERE id_prod = $id_producto");
    $alertas = $db->conn->query("SELECT COUNT(*) FROM Alertas WHERE id_prod = $id_producto");
    
    $salidaCount = $salidas->fetch_row()[0];
    $alertaCount = $alertas->fetch_row()[0];
    
    if ($salidaCount > 0 || $alertaCount > 0) {
        $entidades = [];
        if ($salidaCount > 0) $entidades[] = "salidas ($salidaCount)";
        if ($alertaCount > 0) $entidades[] = "alertas ($alertaCount)";
        $errorMsg = "No se puede eliminar el producto porque tiene " . implode(' y ', $entidades) . " asociados.";
    } else {
        $producto_old = $db->conn->query("SELECT * FROM Productos WHERE id_prod = $id_producto")->fetch_assoc();
        $stmt = $db->conn->prepare("DELETE FROM Productos WHERE id_prod = ?");
        $stmt->bind_param('i', $id_producto);
        $stmt->execute();
        $stmt->close();
        
        // Generar notificación automática para todos los usuarios
        $usuario_nombre = $_SESSION['user']['nombre'] ?? $_SESSION['user']['name'] ?? 'Usuario';
        $sistemaNotificaciones->notificarEliminacionProducto($producto_old, $usuario_nombre);
        
        // Redireccionar con información específica del producto eliminado
        $producto_info = urlencode($producto_old['nombre']);
        header("Location: productos.php?msg=eliminado&id_prod=$id_producto&nombre_prod=$producto_info");
        exit;
    }
}

// Modificar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modificar_producto']) && $puede_editar) {
    $id = intval($_POST['id_prod']);
    $nombre = trim($_POST['nombre']);
    $modelo = trim($_POST['modelo']);
    $talla = trim($_POST['talla']);
    $color = trim($_POST['color']);
    $stock = intval($_POST['stock']);
    $material = trim($_POST['material']);
    $id_subcg = intval($_POST['id_subcg']);
    $id_nit = intval($_POST['id_nit']);
    
    $producto_old = $db->conn->query("SELECT * FROM Productos WHERE id_prod = $id")->fetch_assoc();
    
    $stmt = $db->conn->prepare("UPDATE Productos SET nombre = ?, modelo = ?, talla = ?, color = ?, stock = ?, material = ?, id_subcg = ?, id_nit = ? WHERE id_prod = ?");
    $stmt->bind_param('ssssisiii', $nombre, $modelo, $talla, $color, $stock, $material, $id_subcg, $id_nit, $id);
    $stmt->execute();
    $stmt->close();
    
    // Generar notificación automática
    $usuario_nombre = $_SESSION['user']['nombre'] ?? $_SESSION['user']['name'] ?? 'Usuario';
    $sistemaNotificaciones->notificarModificacionProducto($producto_old, [
        'nombre' => $nombre,
        'modelo' => $modelo,
        'stock' => $stock
    ], $usuario_nombre);
    
    header('Location: productos.php?msg=modificado');
    exit;
}

// Procesar formulario de creación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_producto']) && $puede_editar) {
    $nombre = trim($_POST['nombre']);
    $modelo = trim($_POST['modelo']);
    $talla = trim($_POST['talla']);
    $color = trim($_POST['color']);
    $stock = intval($_POST['stock']);
    $material = trim($_POST['material']);
    $id_subcg = intval($_POST['id_subcg']);
    $id_nit = intval($_POST['id_nit']);
    $num_doc = $_SESSION['user']['num_doc'];
    $fecha_ing = date('Y-m-d');
    
    $stmt = $db->conn->prepare("INSERT INTO Productos (nombre, modelo, talla, color, stock, fecha_ing, material, id_subcg, id_nit, num_doc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssisisii', $nombre, $modelo, $talla, $color, $stock, $fecha_ing, $material, $id_subcg, $id_nit, $num_doc);
    $stmt->execute();
    $nuevo_id = $db->conn->insert_id;
    $stmt->close();
    
    // Generar notificación automática para todos los usuarios
    $usuario_nombre = $_SESSION['user']['nombre'] ?? $_SESSION['user']['name'] ?? 'Usuario';
    $sistemaNotificaciones->notificarNuevoProducto($nuevo_id, $nombre, $usuario_nombre);
    
    header('Location: productos.php?msg=creado');
    exit;
}

// Filtrar productos
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '';
$filtro_categoria = isset($_GET['filtro_categoria']) ? $_GET['filtro_categoria'] : '';
$filtro_subcategoria = isset($_GET['filtro_subcategoria']) ? $_GET['filtro_subcategoria'] : '';
$filtro_proveedor = isset($_GET['filtro_proveedor']) ? $_GET['filtro_proveedor'] : '';

// Construir consulta con filtros dinámicos
$sql_base = "SELECT p.*, sc.nombre as subcategoria_nombre, c.nombre as categoria_nombre, pr.razon_social as proveedor_nombre, u.nombres as usuario_nombre 
            FROM Productos p 
            LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg 
            LEFT JOIN Categoria c ON sc.id_categ = c.id_categ 
            LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit 
            LEFT JOIN Users u ON p.num_doc = u.num_doc";

$where_conditions = [];
$params = [];
$types = '';

// Filtro de texto general
if (!empty($filtro)) {
    $where_conditions[] = "(p.nombre LIKE ? OR p.modelo LIKE ? OR p.talla LIKE ? OR p.color LIKE ? OR p.material LIKE ?)";
    $like = "%$filtro%";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
    $types .= 'sssss';
}

// Filtro por categoría
if (!empty($filtro_categoria)) {
    $where_conditions[] = "c.id_categ = ?";
    $params[] = $filtro_categoria;
    $types .= 'i';
}

// Filtro por proveedor
if (!empty($filtro_proveedor)) {
    $where_conditions[] = "pr.id_nit = ?";
    $params[] = $filtro_proveedor;
    $types .= 'i';
}

// Construir consulta final
$sql = $sql_base;
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}
$sql .= " ORDER BY p.nombre";

// Ejecutar consulta
if (!empty($params)) {
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $db->conn->query($sql);
}

// Obtener datos para los formularios
$categorias = [];
$resCat = $db->conn->query("SELECT id_categ, nombre FROM Categoria ORDER BY nombre");
while($cat = $resCat->fetch_assoc()) {
    $categorias[] = $cat;
}

$subcategorias = [];
$resSubcat = $db->conn->query("SELECT sc.id_subcg, sc.nombre, sc.id_categ, c.nombre as categoria_nombre FROM Subcategoria sc LEFT JOIN Categoria c ON sc.id_categ = c.id_categ ORDER BY c.nombre, sc.nombre");
while($subcat = $resSubcat->fetch_assoc()) {
    $subcategorias[] = $subcat;
}

$proveedores = [];
$resProv = $db->conn->query("SELECT id_nit, razon_social FROM Proveedores ORDER BY razon_social");
while($prov = $resProv->fetch_assoc()) {
    $proveedores[] = $prov;
}

// Para mostrar el formulario de modificar
$editProducto = null;
if (isset($_GET['modificar'])) {
    $id = intval($_GET['modificar']);
    $sql = "SELECT * FROM Productos WHERE id_prod = ?";
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $editProducto = $res->fetch_assoc();
    $stmt->close();
}

// Mensajes
$show_notification = '';
$producto_eliminado = [];
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'creado':
            $show_notification = 'created';
            break;
        case 'modificado':
            $show_notification = 'updated';
            break;
        case 'eliminado':
            $id_eliminado = isset($_GET['id_prod']) ? intval($_GET['id_prod']) : 0;
            $nombre_eliminado = isset($_GET['nombre_prod']) ? urldecode($_GET['nombre_prod']) : 'Desconocido';
            $producto_eliminado = [
                'id' => $id_eliminado,
                'nombre' => $nombre_eliminado
            ];
            $show_notification = 'deleted';
            break;
    }
}

// Calcular estadísticas
$stats_productos = $db->conn->query("SELECT COUNT(*) as total FROM Productos")->fetch_assoc()['total'];
$stats_bajo_stock = $db->conn->query("SELECT COUNT(*) as bajo_stock FROM Productos WHERE CAST(stock AS UNSIGNED) <= 10")->fetch_assoc()['bajo_stock'];
$stats_sin_stock = $db->conn->query("SELECT COUNT(*) as sin_stock FROM Productos WHERE CAST(stock AS UNSIGNED) = 0")->fetch_assoc()['sin_stock'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - Inventixor</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .sidebar-menu {
            padding: 0;
            margin: 0;
            list-style: none;
        }
        
        .menu-item {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .menu-link {
            display: block;
            padding: 1rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .menu-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            padding-left: 2rem;
        }
        
        .menu-link.active {
            background: rgba(255,255,255,0.2);
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }
        
        .main-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 15px;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .table-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .btn-action {
            margin: 0 2px;
            padding: 0.25rem 0.5rem;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .stock-alert {
            background-color: #fff3cd;
            border: 1px solid #ffecb5;
            color: #664d03;
        }
        
        .stock-critical {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-boxes"></i> Inventixor</h3>
            <p class="mb-0">Sistema de Inventario</p>
        </div>
        
        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="dashboard.php" class="menu-link">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            <li class="menu-item">
                <a href="productos.php" class="menu-link active">
                    <i class="fas fa-box me-2"></i> Productos
                </a>
            </li>
            <li class="menu-item">
                <a href="categorias.php" class="menu-link">
                    <i class="fas fa-tags me-2"></i> Categorías
                </a>
            </li>
            <li class="menu-item">
                <a href="subcategorias.php" class="menu-link">
                    <i class="fas fa-tag me-2"></i> Subcategorías
                </a>
            </li>
            <li class="menu-item">
                <a href="proveedores.php" class="menu-link">
                    <i class="fas fa-truck me-2"></i> Proveedores
                </a>
            </li>
            <li class="menu-item">
                <a href="salidas.php" class="menu-link">
                    <i class="fas fa-sign-out-alt me-2"></i> Salidas
                </a>
            </li>
            <li class="menu-item">
                <a href="reportes.php" class="menu-link">
                    <i class="fas fa-chart-bar me-2"></i> Reportes
                </a>
            </li>
            <li class="menu-item">
                <a href="alertas.php" class="menu-link">
                    <i class="fas fa-bell me-2"></i> Alertas
                </a>
            </li>
            <?php if ($_SESSION['rol'] === 'admin' || $_SESSION['rol'] === 'coordinador'): ?>
            <li class="menu-item">
                <a href="usuarios.php" class="menu-link">
                    <i class="fas fa-users me-2"></i> Usuarios
                </a>
            </li>
            <?php endif; ?>
            <li class="menu-item">
                <a href="logout.php" class="menu-link">
                    <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="main-header text-center">
            <div class="container">
                <h1><i class="fas fa-box me-3"></i>Gestión de Productos</h1>
                <p class="mb-0">Administra el inventario de productos del sistema</p>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stats-card text-center animate-fade-in">
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="me-3">
                            <i class="fas fa-boxes fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h3 class="mb-1"><?php echo $stats_productos; ?></h3>
                            <p class="text-muted mb-0">Total Productos</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card text-center animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="me-3">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                        </div>
                        <div>
                            <h3 class="mb-1"><?php echo $stats_bajo_stock; ?></h3>
                            <p class="text-muted mb-0">Stock Bajo</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card text-center animate-fade-in" style="animation-delay: 0.4s;">
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="me-3">
                            <i class="fas fa-times-circle fa-2x text-danger"></i>
                        </div>
                        <div>
                            <h3 class="mb-1"><?php echo $stats_sin_stock; ?></h3>
                            <p class="text-muted mb-0">Sin Stock</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros y Búsqueda -->
        <div class="filter-card animate-fade-in" style="animation-delay: 0.6s;">
            <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filtros y Búsqueda</h5>
            <form method="GET" action="productos.php">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <input type="text" class="form-control" name="filtro" 
                               placeholder="Buscar por nombre, modelo, talla, color o material..." 
                               value="<?php echo htmlspecialchars($filtro); ?>">
                    </div>
                    <div class="col-md-2 mb-3">
                        <select class="form-select" name="filtro_categoria">
                            <option value="">Todas las categorías</option>
                            <?php foreach($categorias as $cat): ?>
                            <option value="<?php echo $cat['id_categ']; ?>" 
                                    <?php echo ($filtro_categoria == $cat['id_categ']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <select class="form-select" name="filtro_proveedor">
                            <option value="">Todos los proveedores</option>
                            <?php foreach($proveedores as $prov): ?>
                            <option value="<?php echo $prov['id_nit']; ?>" 
                                    <?php echo ($filtro_proveedor == $prov['id_nit']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prov['razon_social']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                    </div>
                    <div class="col-md-2 mb-3">
                        <a href="productos.php" class="btn btn-secondary w-100">
                            <i class="fas fa-times me-2"></i>Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Botones de Acción -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Lista de Productos</h4>
            <div>
                <a href="reportes.php?tabla=productos" class="btn btn-info me-2">
                    <i class="fas fa-chart-bar me-2"></i>Reportes
                </a>
                <?php if ($puede_editar): ?>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrear">
                    <i class="fas fa-plus me-2"></i>Nuevo Producto
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Error Message -->
        <?php if (isset($errorMsg)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $errorMsg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Tabla de Productos -->
        <div class="table-card animate-fade-in" style="animation-delay: 0.8s;">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Modelo</th>
                            <th>Talla</th>
                            <th>Color</th>
                            <th>Stock</th>
                            <th>Material</th>
                            <th>Categoría</th>
                            <th>Subcategoría</th>
                            <th>Proveedor</th>
                            <th>Fecha Ingreso</th>
                            <?php if ($puede_editar): ?>
                            <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): 
                                $stock_num = intval($row['stock']);
                                $stock_class = '';
                                if ($stock_num == 0) {
                                    $stock_class = 'stock-critical';
                                } elseif ($stock_num <= 10) {
                                    $stock_class = 'stock-alert';
                                }
                            ?>
                            <tr>
                                <td><?php echo $row['id_prod']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['modelo'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['talla'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['color'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $stock_class; ?>">
                                        <?php echo htmlspecialchars($row['stock']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['material'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($row['categoria_nombre']): ?>
                                    <span class="badge bg-primary">
                                        <i class="fas fa-tags me-1"></i>
                                        <?php echo htmlspecialchars($row['categoria_nombre']); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-light text-dark">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['subcategoria_nombre']): ?>
                                    <span class="badge bg-info">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo htmlspecialchars($row['subcategoria_nombre']); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-light text-dark">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['proveedor_nombre'] ?? 'Sin asignar'); ?></td>
                                <td><?php echo $row['fecha_ing'] ? date('d/m/Y', strtotime($row['fecha_ing'])) : '-'; ?></td>
                                <?php if ($puede_editar): ?>
                                <td>
                                    <a href="productos.php?modificar=<?php echo $row['id_prod']; ?>" 
                                       class="btn btn-warning btn-action" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="productos.php?eliminar=<?php echo $row['id_prod']; ?>" 
                                       class="btn btn-danger btn-action" title="Eliminar"
                                       onclick="return confirm('¿Está seguro de eliminar este producto?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?php echo $puede_editar ? '12' : '11'; ?>" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No hay productos registrados</p>
                                    <?php if ($puede_editar): ?>
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrear">
                                        <i class="fas fa-plus me-2"></i>Agregar Primer Producto
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Crear Producto -->
    <?php if ($puede_editar): ?>
    <div class="modal fade" id="modalCrear" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Nuevo Producto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="nombre" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Modelo</label>
                                <input type="text" class="form-control" name="modelo">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Talla</label>
                                <input type="text" class="form-control" name="talla">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Color</label>
                                <input type="text" class="form-control" name="color">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Stock Inicial *</label>
                                <input type="number" class="form-control" name="stock" min="0" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Material</label>
                                <input type="text" class="form-control" name="material">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoría *</label>
                                <select class="form-select" id="categoria_crear" name="id_categ" required>
                                    <option value="">Seleccione una categoría</option>
                                    <?php foreach($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id_categ']; ?>">
                                        <?php echo htmlspecialchars($cat['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subcategoría *</label>
                                <select class="form-select" id="subcategoria_crear" name="id_subcg" required>
                                    <option value="">Primero seleccione una categoría</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Proveedor *</label>
                                <select class="form-select" name="id_nit" required>
                                    <option value="">Seleccione un proveedor</option>
                                    <?php foreach($proveedores as $prov): ?>
                                    <option value="<?php echo $prov['id_nit']; ?>">
                                        <?php echo htmlspecialchars($prov['razon_social']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_producto" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Crear Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Producto -->
    <?php if ($editProducto): ?>
    <div class="modal fade show" id="modalEditar" tabindex="-1" style="display: block;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Producto
                    </h5>
                    <a href="productos.php" class="btn-close"></a>
                </div>
                <form method="POST">
                    <input type="hidden" name="id_prod" value="<?php echo $editProducto['id_prod']; ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" name="nombre" 
                                       value="<?php echo htmlspecialchars($editProducto['nombre']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Modelo</label>
                                <input type="text" class="form-control" name="modelo" 
                                       value="<?php echo htmlspecialchars($editProducto['modelo']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Talla</label>
                                <input type="text" class="form-control" name="talla" 
                                       value="<?php echo htmlspecialchars($editProducto['talla']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Color</label>
                                <input type="text" class="form-control" name="color" 
                                       value="<?php echo htmlspecialchars($editProducto['color']); ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Stock *</label>
                                <input type="number" class="form-control" name="stock" min="0" 
                                       value="<?php echo $editProducto['stock']; ?>" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Material</label>
                                <input type="text" class="form-control" name="material" 
                                       value="<?php echo htmlspecialchars($editProducto['material']); ?>">
                            </div>
                            <?php 
                            // Obtener la categoría del producto actual
                            $categoria_actual_id = '';
                            if ($editProducto['id_subcg']) {
                                $cat_result = $db->conn->query("SELECT id_categ FROM Subcategoria WHERE id_subcg = " . $editProducto['id_subcg']);
                                if ($cat_row = $cat_result->fetch_assoc()) {
                                    $categoria_actual_id = $cat_row['id_categ'];
                                }
                            }
                            ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Categoría *</label>
                                <select class="form-select" id="categoria_editar" name="id_categ" required>
                                    <option value="">Seleccione una categoría</option>
                                    <?php foreach($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id_categ']; ?>" 
                                            <?php echo ($cat['id_categ'] == $categoria_actual_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subcategoría *</label>
                                <select class="form-select" id="subcategoria_editar" name="id_subcg" required>
                                    <option value="">Seleccione una subcategoría</option>
                                    <?php foreach($subcategorias as $subcat): ?>
                                    <option value="<?php echo $subcat['id_subcg']; ?>" 
                                            data-categoria="<?php echo $subcat['id_categ']; ?>"
                                            <?php echo ($subcat['id_subcg'] == $editProducto['id_subcg']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subcat['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Proveedor *</label>
                                <select class="form-select" name="id_nit" required>
                                    <option value="">Seleccione un proveedor</option>
                                    <?php foreach($proveedores as $prov): ?>
                                    <option value="<?php echo $prov['id_nit']; ?>" 
                                            <?php echo ($prov['id_nit'] == $editProducto['id_nit']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prov['razon_social']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="productos.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" name="modificar_producto" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para filtrar subcategorías por categoría -->
    <script>
        // Array con todas las subcategorías
        const subcategorias = <?php echo json_encode($subcategorias); ?>;
        
        // Función para filtrar subcategorías
        function filtrarSubcategorias(categoriaSelect, subcategoriaSelect) {
            const categoriaId = categoriaSelect.value;
            const subcategoriaEl = document.getElementById(subcategoriaSelect);
            
            // Limpiar opciones
            subcategoriaEl.innerHTML = '<option value="">Seleccione una subcategoría</option>';
            
            if (categoriaId) {
                // Filtrar subcategorías por categoría
                const subcategoriasFiltradas = subcategorias.filter(sub => sub.id_categ == categoriaId);
                
                subcategoriasFiltradas.forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub.id_subcg;
                    option.textContent = sub.nombre;
                    subcategoriaEl.appendChild(option);
                });
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Event listeners para crear producto
            const categoriaCrear = document.getElementById('categoria_crear');
            if (categoriaCrear) {
                categoriaCrear.addEventListener('change', function() {
                    filtrarSubcategorias(this, 'subcategoria_crear');
                });
            }
            
            // Event listeners para editar producto
            const categoriaEditar = document.getElementById('categoria_editar');
            if (categoriaEditar) {
                categoriaEditar.addEventListener('change', function() {
                    filtrarSubcategorias(this, 'subcategoria_editar');
                });
                
                // Cargar subcategorías al abrir modal de edición
                const categoriaActual = categoriaEditar.value;
                if (categoriaActual) {
                    filtrarSubcategorias(categoriaEditar, 'subcategoria_editar');
                    
                    // Restaurar la subcategoría seleccionada
                    setTimeout(() => {
                        const subcategoriaEditar = document.getElementById('subcategoria_editar');
                        const subcategoriaActual = '<?php echo $editProducto['id_subcg'] ?? ''; ?>';
                        if (subcategoriaActual) {
                            subcategoriaEditar.value = subcategoriaActual;
                        }
                    }, 100);
                }
            }
        });
    </script>
    
    <!-- Notificaciones -->
    <?php if ($show_notification): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let message = '';
            let type = 'success';
            
            <?php if ($show_notification === 'created'): ?>
                message = 'Producto creado exitosamente';
            <?php elseif ($show_notification === 'updated'): ?>
                message = 'Producto modificado exitosamente';
            <?php elseif ($show_notification === 'deleted'): ?>
                message = 'Producto "<?php echo htmlspecialchars($producto_eliminado['nombre']); ?>" eliminado exitosamente';
            <?php endif; ?>
            
            // Mostrar toast notification
            if (message) {
                const toast = document.createElement('div');
                toast.className = 'toast align-items-center text-white bg-' + type + ' border-0';
                toast.style.position = 'fixed';
                toast.style.top = '20px';
                toast.style.right = '20px';
                toast.style.zIndex = '9999';
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-check-circle me-2"></i>${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                document.body.appendChild(toast);
                
                const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
                bsToast.show();
                
                toast.addEventListener('hidden.bs.toast', function() {
                    document.body.removeChild(toast);
                });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>