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
        // Uso de prepared statement para prevenir inyección y errores por tipos
        if ($stmtRol = $db->conn->prepare("SELECT rol FROM Users WHERE num_doc = ?")) {
            $stmtRol->bind_param('s', $num_doc);
            $stmtRol->execute();
            $resRol = $stmtRol->get_result();
            if ($resRol && ($rolRow = $resRol->fetch_assoc())) {
                $_SESSION['rol'] = $rolRow['rol'] ?? '';
            } else {
                $_SESSION['rol'] = '';
            }
            $stmtRol->close();
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

    // Verificar dependencias (Salidas y Alertas)
    $salidaCount = 0;
    if ($stmt = $db->conn->prepare("SELECT COUNT(*) AS c FROM Salidas WHERE id_prod = ?")) {
        $stmt->bind_param('i', $id_producto);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($row = $res->fetch_assoc())) { $salidaCount = (int)$row['c']; }
        $stmt->close();
    }
    $alertaCount = 0;
    if ($stmt = $db->conn->prepare("SELECT COUNT(*) AS c FROM Alertas WHERE id_prod = ?")) {
        $stmt->bind_param('i', $id_producto);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($row = $res->fetch_assoc())) { $alertaCount = (int)$row['c']; }
        $stmt->close();
    }

    if ($salidaCount > 0 || $alertaCount > 0) {
        $entidades = [];
        if ($salidaCount > 0) $entidades[] = "salidas ($salidaCount)";
        if ($alertaCount > 0) $entidades[] = "alertas ($alertaCount)";
        $error = "No se puede eliminar el producto porque tiene " . implode(' y ', $entidades) . " asociados.";
    } else {
        // Snapshot del producto antes de eliminar
        $producto_old = [];
        if ($stmt = $db->conn->prepare("SELECT * FROM Productos WHERE id_prod = ?")) {
            $stmt->bind_param('i', $id_producto);
            $stmt->execute();
            $res = $stmt->get_result();
            $producto_old = $res ? ($res->fetch_assoc() ?: []) : [];
            $stmt->close();
        }

        // Eliminar el producto
        $stmt = $db->conn->prepare("DELETE FROM Productos WHERE id_prod = ?");
        $stmt->bind_param('i', $id_producto);
        $stmt->execute();
        $stmt->close();

        // Notificar eliminación y redirigir
        $usuario_nombre = $_SESSION['user']['nombre'] ?? $_SESSION['user']['name'] ?? 'Usuario';
        $sistemaNotificaciones->notificarEliminacionProducto($producto_old, $usuario_nombre);
        $producto_info = urlencode($producto_old['nombre'] ?? '');
        header("Location: productos.php?msg=eliminado&id_prod=$id_producto&nombre_prod=$producto_info");
        exit;
    }
}

// Modificar producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modificar_producto'])) {
    if (!$puede_editar) {
        $error = 'No tienes permisos para modificar productos.';
    } else {
        $id = intval($_POST['id_prod']);
        $nombre = trim($_POST['nombre']);
        $modelo = trim($_POST['modelo']);
        $talla = trim($_POST['talla']);
        $color = trim($_POST['color']);
        $stock = intval($_POST['stock']);
        $material = trim($_POST['material']);
        $id_subcg = intval($_POST['id_subcg']);
        $id_nit = intval($_POST['id_nit']);

        // Snapshot del producto antes de actualizar
        $producto_old = [];
        if ($stmt = $db->conn->prepare("SELECT * FROM Productos WHERE id_prod = ?")) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $producto_old = $res ? ($res->fetch_assoc() ?: []) : [];
            $stmt->close();
        }

        // Actualizar (no permitimos cambiar fecha_ing ni num_doc desde el formulario)
        $stmt = $db->conn->prepare("UPDATE Productos SET nombre = ?, modelo = ?, talla = ?, color = ?, stock = ?, material = ?, id_subcg = ?, id_nit = ? WHERE id_prod = ?");
        $stmt->bind_param('ssssisiii', $nombre, $modelo, $talla, $color, $stock, $material, $id_subcg, $id_nit, $id);
        $stmt->execute();
        $stmt->close();

        // Notificaciones
        $usuario_nombre = $_SESSION['user']['nombre'] ?? $_SESSION['user']['name'] ?? 'Usuario';
        $sistemaNotificaciones->notificarModificacionProducto($producto_old, ['nombre' => $nombre, 'modelo' => $modelo, 'stock' => $stock], $usuario_nombre);
        if ($stock <= 10) {
            $sistemaNotificaciones->notificarStockBajo($id, $nombre, $stock);
        }

        header('Location: productos.php?msg=modificado');
        exit;
    }
}

// Crear producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_producto'])) {
    if (in_array($_SESSION['rol'], ['admin', 'coordinador', 'auxiliar'])) {
        $nombre = trim($_POST['nombre']);
        $modelo = trim($_POST['modelo']);
        $talla = trim($_POST['talla']);
        $color = trim($_POST['color']);
        $stock = intval($_POST['stock']);
        $material = trim($_POST['material']);
        $id_subcg = intval($_POST['id_subcg']);
        $id_nit = intval($_POST['id_nit']);

        // Derivar desde sesión/sistema
        $num_doc = isset($_SESSION['user']['num_doc']) ? intval($_SESSION['user']['num_doc']) : 0;
        $fecha_ing = date('Y-m-d');

        $stmt = $db->conn->prepare("INSERT INTO Productos (nombre, modelo, talla, color, stock, fecha_ing, material, id_subcg, id_nit, num_doc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssissiii', $nombre, $modelo, $talla, $color, $stock, $fecha_ing, $material, $id_subcg, $id_nit, $num_doc);
        $stmt->execute();
        $nuevo_id = $db->conn->insert_id;
        $stmt->close();

        // Notificar creación
        $usuario_nombre = $_SESSION['user']['nombre'] ?? $_SESSION['user']['name'] ?? 'Usuario';
        $sistemaNotificaciones->notificarNuevoProducto($nuevo_id, $nombre, $usuario_nombre);

        header('Location: productos.php?msg=creado');
        exit;
    } else {
        $error = 'No tienes permisos para crear productos.';
    }
}

// Filtrar productos
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '';
$filtro_categoria = isset($_GET['filtro_categoria']) ? $_GET['filtro_categoria'] : '';
$filtro_subcategoria = isset($_GET['filtro_subcategoria']) ? $_GET['filtro_subcategoria'] : '';
$filtro_talla = isset($_GET['filtro_talla']) ? $_GET['filtro_talla'] : '';
$filtro_color = isset($_GET['filtro_color']) ? $_GET['filtro_color'] : '';
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

// Filtro por subcategoría
if (!empty($filtro_subcategoria)) {
    $where_conditions[] = "sc.id_subcg = ?";
    $params[] = $filtro_subcategoria;
    $types .= 'i';
}

// Filtro por talla
if (!empty($filtro_talla)) {
    $where_conditions[] = "p.talla LIKE ?";
    $params[] = "%$filtro_talla%";
    $types .= 's';
}

// Filtro por color
if (!empty($filtro_color)) {
    $where_conditions[] = "p.color LIKE ?";
    $params[] = "%$filtro_color%";
    $types .= 's';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=5.0">
    <meta name="theme-color" content="#667eea">
    <meta name="description" content="Sistema de gestión de productos - Inventixor">
    <title>Gestión de Productos - Inventixor</title>
    
    <!-- Preload de fuentes críticas -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Estilos del sistema -->
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="public/css/responsive.css">
</head>
<body>
    <!-- Botón hamburguesa para móviles -->
    <button class="mobile-menu-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Overlay para móviles -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
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
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-box me-2"></i>Gestión de Productos</h2>
                        <p class="mb-0">Administra el catálogo completo de productos del inventario</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-light text-dark">
                            Rol: <?= htmlspecialchars($_SESSION['rol']??'') ?>
                        </span>
                        <?php if (in_array($_SESSION['rol'], ['admin', 'coordinador', 'auxiliar'])): ?>
                        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#modalCrear">
                            <i class="fas fa-plus me-2"></i>Nuevo Producto
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
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
        <div class="filter-card animate-fade-in" style="animation-delay: 0.6s; background: linear-gradient(90deg, #e0e7ff 0%, #f0f4ff 100%); border-radius: 1rem; box-shadow: 0 2px 12px rgba(80,80,160,0.08);">
            <h5 class="mb-3 text-primary"><i class="fas fa-filter me-2"></i>Filtros y Búsqueda</h5>
            <form method="GET" action="productos.php">
                <div class="row g-2">
                    <div class="col-md-3 mb-2">
                        <input type="text" class="form-control" name="filtro" 
                               placeholder="Buscar por nombre, modelo, material..." 
                               value="<?php echo htmlspecialchars($filtro); ?>">
                    </div>
                    <div class="col-md-2 mb-2">
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
                    <div class="col-md-2 mb-2">
                        <select class="form-select" name="filtro_subcategoria">
                            <option value="">Todas las subcategorías</option>
                            <?php foreach($subcategorias as $subcat): ?>
                            <option value="<?php echo $subcat['id_subcg']; ?>" 
                                    <?php echo ($filtro_subcategoria == $subcat['id_subcg']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subcat['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1 mb-2">
                        <input type="text" class="form-control" name="filtro_talla" placeholder="Talla">
                    </div>
                    <div class="col-md-1 mb-2">
                        <input type="text" class="form-control" name="filtro_color" placeholder="Color">
                    </div>
                    <div class="col-md-2 mb-2">
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
                    <div class="col-md-1 mb-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
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
                            if (!empty($editProducto['id_subcg'])) {
                                if ($stmtCat = $db->conn->prepare("SELECT id_categ FROM Subcategoria WHERE id_subcg = ?")) {
                                    $stmtCat->bind_param('i', $editProducto['id_subcg']);
                                    $stmtCat->execute();
                                    $resCat = $stmtCat->get_result();
                                    if ($resCat && ($cat_row = $resCat->fetch_assoc())) {
                                        $categoria_actual_id = $cat_row['id_categ'] ?? '';
                                    }
                                    $stmtCat->close();
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sistema de Notificaciones -->
    <script src="public/js/notifications.js"></script>
    <script src="public/js/auto-notifications.js"></script>

    <!-- Sistema Responsive -->
    <script src="public/js/responsive-sidebar.js"></script>
    <script>
        // Marcar como activo el menú de productos
        if (typeof setActiveMenuItem === 'function') {
            setActiveMenuItem('productos.php');
        }
    </script>

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
    
    <!-- Scripts responsivos -->
    <script src="public/js/responsive.js"></script>
</body>
</html>