<?php
session_start();
require_once 'app/helpers/Database.php';
$db = new Database();

$message = '';
$error = '';

// Inicializar filtros por defecto
$filtro_nombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';
$filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$filtro_subcategoria = isset($_GET['subcategoria']) ? $_GET['subcategoria'] : '';
$filtro_proveedor = isset($_GET['proveedor']) ? $_GET['proveedor'] : '';
$filtro_stock = isset($_GET['stock']) ? $_GET['stock'] : '';
$filtro_bajo_stock = isset($_GET['bajo_stock']) ? true : false;

// Inicializar $stats por defecto
$stats = [
    'total_productos' => 0,
    'stock_total' => 0,
    'productos_bajo_stock' => 0,
    'productos_sin_stock' => 0
];

// Crear producto mejorado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_producto'])) {
    $filtro_nombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';
    $filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
    $filtro_subcategoria = isset($_GET['subcategoria']) ? $_GET['subcategoria'] : '';
    $nombre = trim($_POST['nombre'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $stock = intval($_POST['stock'] ?? 0);


    // ===================== Filtros y consulta principal =====================
    // Definir variables de filtro con valores por defecto
    $filtro_nombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';
    $filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
    $filtro_subcategoria = isset($_GET['subcategoria']) ? $_GET['subcategoria'] : '';
    $filtro_proveedor = isset($_GET['proveedor']) ? $_GET['proveedor'] : '';
    $filtro_stock = isset($_GET['stock']) ? $_GET['stock'] : '';
    $filtro_bajo_stock = isset($_GET['bajo_stock']) ? true : false;

    /**
     * Construye los filtros SQL y parámetros para la consulta de productos
     * @return array [$where_sql, $params, $types]
     */
    function construirFiltrosProductos($filtro_nombre, $filtro_categoria, $filtro_subcategoria, $filtro_proveedor, $filtro_stock, $filtro_bajo_stock) {
        $where = [];
        $params = [];
        $types = '';
        // Filtro por nombre
        if (!empty($filtro_nombre)) {
            $where[] = "p.nombre LIKE ?";
            $params[] = "%$filtro_nombre%";
            $types .= 's';
        }
        // Filtro por categoría
        if (!empty($filtro_categoria)) {
            $where[] = "c.id_categ = ?";
            $params[] = $filtro_categoria;
            $types .= 'i';
        }
        // Filtro por subcategoría
        if (!empty($filtro_subcategoria)) {
            $where[] = "sc.id_subcg = ?";
            $params[] = $filtro_subcategoria;
            $types .= 'i';
        }
        // Filtro por proveedor
        if (!empty($filtro_proveedor)) {
            $where[] = "pr.id_nit = ?";
            $params[] = $filtro_proveedor;
            $types .= 'i';
        }
        // Filtro por stock
        if (!empty($filtro_stock)) {
            $where[] = "p.stock <= ?";
            $params[] = $filtro_stock;
            $types .= 'i';
        }
        // Filtro por bajo stock
        if ($filtro_bajo_stock) {
            $where[] = "p.stock <= 10";
        }
        $where_sql = '';
        if (!empty($where)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where);
        }
        return [$where_sql, $params, $types];
    }



    // ===================== Construcción de consulta SQL =====================
    // Consulta por defecto si no hay filtros activos
    $consultaPorDefecto = "SELECT * FROM Productos ORDER BY nombre";

    /**
     * Construye la consulta SQL y parámetros para la consulta de productos
     * @return array [$sql, $params, $types]
     */
    function construirConsultaProductos($db, $filtro_nombre, $filtro_categoria, $filtro_subcategoria, $filtro_proveedor, $filtro_stock, $filtro_bajo_stock) {
        $base = "SELECT p.id_prod, p.nombre, p.modelo, p.stock,
                   sc.id_subcg, sc.nombre as subcategoria_nombre,
                   c.id_categ, c.nombre as categoria_nombre,
                   pr.id_nit, pr.razon_social as proveedor_nombre
            FROM Productos p
            LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
            LEFT JOIN Categoria c ON sc.id_categ = c.id_categ
            LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit";
        $where = [];
        $params = [];
        $types = '';
        // Filtro por nombre
        if (!empty($filtro_nombre)) {
            $where[] = "p.nombre LIKE ?";
            $params[] = "%" . $db->conn->real_escape_string($filtro_nombre) . "%";
            $types .= 's';
        }
        // Filtro por categoría
        if (!empty($filtro_categoria)) {
            $where[] = "c.id_categ = ?";
            $params[] = $db->conn->real_escape_string($filtro_categoria);
            $types .= 'i';
        }
        // Filtro por subcategoría
        if (!empty($filtro_subcategoria)) {
            $where[] = "sc.id_subcg = ?";
            $params[] = $db->conn->real_escape_string($filtro_subcategoria);
            $types .= 'i';
        }
        // Filtro por proveedor
        if (!empty($filtro_proveedor)) {
            $where[] = "pr.id_nit = ?";
            $params[] = $db->conn->real_escape_string($filtro_proveedor);
            $types .= 'i';
        }
        // Filtro por stock
        if (!empty($filtro_stock)) {
            $where[] = "p.stock <= ?";
            $params[] = $db->conn->real_escape_string($filtro_stock);
            $types .= 'i';
        }
        // Filtro por bajo stock
        if ($filtro_bajo_stock) {
            $where[] = "p.stock <= 10";
        }
        $where_sql = '';
        if (!empty($where)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where);
        }
        $sql = $base . $where_sql . " ORDER BY p.nombre";
        return [$sql, $params, $types];
    }


    // ===================== Ejecución de consulta =====================
    // Construir consulta y parámetros
    list($sql, $params, $types) = construirConsultaProductos($db, $filtro_nombre, $filtro_categoria, $filtro_subcategoria, $filtro_proveedor, $filtro_stock, $filtro_bajo_stock);


    // Validar que la consulta SQL esté bien formada y no esté vacía
    if (empty($sql) || !preg_match('/^SELECT.+FROM.+ORDER BY.+$/i', $sql)) {
        // Si la consulta está vacía, usar la consulta por defecto
        $sql = $consultaPorDefecto;
        error_log('Consulta SQL vacía o mal formada, se usó consulta por defecto: ' . $sql);
    }

    if (!empty($sql)) {
        if (empty($params)) {
            $result = $db->conn->query($sql);
            if ($result === false) {
                error_log('Error ejecutando SQL: ' . $db->conn->error . " | SQL: $sql");
                $productos = [];
            } else {
                $productos = $result->fetch_all(MYSQLI_ASSOC);
            }
        } else {
            $stmt = $db->conn->prepare($sql);
            if ($stmt === false) {
                // Crear producto mejorado
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_producto'])) {
                    $nombre = trim($_POST['nombre'] ?? '');
                    $modelo = trim($_POST['modelo'] ?? '');
                    $stock = intval($_POST['stock'] ?? 0);
                    // ...validación e inserción de producto...
                }

                // ===================== Filtros y consulta principal =====================
                $filtro_nombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';
                $filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
                $filtro_subcategoria = isset($_GET['subcategoria']) ? $_GET['subcategoria'] : '';
                $filtro_proveedor = isset($_GET['proveedor']) ? $_GET['proveedor'] : '';
                $filtro_stock = isset($_GET['stock']) ? $_GET['stock'] : '';
                $filtro_bajo_stock = isset($_GET['bajo_stock']) ? true : false;

                /**
                 * Construye los filtros SQL y parámetros para la consulta de productos
                 * @return array [$where_sql, $params, $types]
                 */
                function construirFiltrosProductos($filtro_nombre, $filtro_categoria, $filtro_subcategoria, $filtro_proveedor, $filtro_stock, $filtro_bajo_stock) {
                    $where = [];
                    $params = [];
                    $types = '';
                    // ...filtros...
                    if (!empty($filtro_nombre)) {
                        $where[] = "p.nombre LIKE ?";
                        $params[] = "%$filtro_nombre%";
                        $types .= 's';
                    }
                    if (!empty($filtro_categoria)) {
                        $where[] = "c.id_categ = ?";
                        $params[] = $filtro_categoria;
                        $types .= 'i';
                    }
                    if (!empty($filtro_subcategoria)) {
                        $where[] = "sc.id_subcg = ?";
                        $params[] = $filtro_subcategoria;
                        $types .= 'i';
                    }
                    if (!empty($filtro_proveedor)) {
                        $where[] = "pr.id_nit = ?";
                        $params[] = $filtro_proveedor;
                        $types .= 'i';
                    }
                    if (!empty($filtro_stock)) {
                        $where[] = "p.stock <= ?";
                        $params[] = $filtro_stock;
                        $types .= 'i';
                    }
                    if ($filtro_bajo_stock) {
                        $where[] = "p.stock <= 10";
                    }
                    $where_sql = '';
                    if (!empty($where)) {
                        $where_sql = ' WHERE ' . implode(' AND ', $where);
                    }
                    return [$where_sql, $params, $types];
                }

                // ===================== Construcción de consulta SQL =====================
                $consultaPorDefecto = "SELECT * FROM Productos ORDER BY nombre";

                function construirConsultaProductos($db, $filtro_nombre, $filtro_categoria, $filtro_subcategoria, $filtro_proveedor, $filtro_stock, $filtro_bajo_stock) {
                    $base = "SELECT p.id_prod, p.nombre, p.modelo, p.stock,
                               sc.id_subcg, sc.nombre as subcategoria_nombre,
                               c.id_categ, c.nombre as categoria_nombre,
                               pr.id_nit, pr.razon_social as proveedor_nombre
                        FROM Productos p
                        LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
                        LEFT JOIN Categoria c ON sc.id_categ = c.id_categ
                        LEFT JOIN Proveedores pr ON p.id_nit = pr.id_nit";
                    $where = [];
                    $params = [];
                    $types = '';
                    // ...filtros...
                    if (!empty($filtro_nombre)) {
                        $where[] = "p.nombre LIKE ?";
                        $params[] = "%" . $db->conn->real_escape_string($filtro_nombre) . "%";
                        $types .= 's';
                    }
                    if (!empty($filtro_categoria)) {
                        $where[] = "c.id_categ = ?";
                        $params[] = $db->conn->real_escape_string($filtro_categoria);
                        $types .= 'i';
                    }
                    if (!empty($filtro_subcategoria)) {
                        $where[] = "sc.id_subcg = ?";
                        $params[] = $db->conn->real_escape_string($filtro_subcategoria);
                        $types .= 'i';
                    }
                    if (!empty($filtro_proveedor)) {
                        $where[] = "pr.id_nit = ?";
                        $params[] = $db->conn->real_escape_string($filtro_proveedor);
                        $types .= 'i';
                    }
                    if (!empty($filtro_stock)) {
                        $where[] = "p.stock <= ?";
                        $params[] = $db->conn->real_escape_string($filtro_stock);
                        $types .= 'i';
                    }
                    if ($filtro_bajo_stock) {
                        $where[] = "p.stock <= 10";
                    }
                    $where_sql = '';
                    if (!empty($where)) {
                        $where_sql = ' WHERE ' . implode(' AND ', $where);
                    }
                    $sql = $base . $where_sql . " ORDER BY p.nombre";
                    return [$sql, $params, $types];
                }

                // ===================== Ejecución de consulta =====================
                list($sql, $params, $types) = construirConsultaProductos($db, $filtro_nombre, $filtro_categoria, $filtro_subcategoria, $filtro_proveedor, $filtro_stock, $filtro_bajo_stock);

                if (empty($sql) || !preg_match('/^SELECT.+FROM.+ORDER BY.+$/i', $sql)) {
                    $sql = $consultaPorDefecto;
                    error_log('Consulta SQL vacía o mal formada, se usó consulta por defecto: ' . $sql);
                }

                if (!empty($sql)) {
                    if (empty($params)) {
                        $result = $db->conn->query($sql);
                        if ($result === false) {
                            error_log('Error ejecutando SQL: ' . $db->conn->error . " | SQL: $sql");
                            $productos = [];
                        } else {
                            $productos = $result->fetch_all(MYSQLI_ASSOC);
                        }
                    } else {
                        $stmt = $db->conn->prepare($sql);
                        if ($stmt === false) {
                            error_log('Error preparando SQL: ' . $db->conn->error . " | SQL: $sql");
                            $productos = [];
                        } else {
                            $stmt->bind_param($types, ...$params);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $stmt->close();
                            $productos = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
                        }
                    }
                } else {
                    error_log('Consulta SQL sigue vacía tras corrección.');
                    $productos = [];
                    echo '<div class="alert alert-danger">Error: Consulta SQL vacía. Contacte al administrador.</div>';
                }

                // Datos para selects
                $categorias = $db->conn->query("SELECT id_categ, nombre FROM Categoria ORDER BY nombre");
                $subcategorias = $db->conn->query("SELECT sc.id_subcg, sc.nombre, c.nombre as categoria_nombre FROM Subcategoria sc JOIN Categoria c ON sc.id_categ = c.id_categ ORDER BY c.nombre, sc.nombre");
                $proveedores = $db->conn->query("SELECT id_nit, razon_social FROM Proveedores ORDER BY razon_social");

                // Estadísticas
                $stats = $db->conn->query("SELECT COUNT(*) as total_productos, SUM(stock) as stock_total, COUNT(CASE WHEN stock <= 10 THEN 1 END) as productos_bajo_stock, COUNT(CASE WHEN stock = 0 THEN 1 END) as productos_sin_stock FROM Productos");
                if ($stats && $stats_row = $stats->fetch_assoc()) {
                    $stats = $stats_row;
                } else {
                    $stats = [
                        'total_productos' => 0,
                        'stock_total' => 0,
                        'productos_bajo_stock' => 0,
                        'productos_sin_stock' => 0
                    ];
                }
            }
        }
    }
// ...existing code...
    }
// Cierre del bloque if principal
// ...existing code...
// ...existing code...
?>
<script>
    // Notificación visual al crear producto
    window.onload = function() {
        var notif = document.getElementById('notificacion-producto');
        if (notif) {
            setTimeout(function() { notif.style.display = 'none'; }, 3000);
        }
    };
</script>
</body>
</html>
// ...existing code...
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --sidebar-width: 280px;
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
            text-align: center;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stats-card .stats-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .stats-card .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .filter-card, .form-card {
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
        
        .stock-badge {
            font-size: 0.9rem;
            padding: 0.35rem 0.7rem;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .product-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .product-card:hover {
            transform: translateY(-2px);
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
                    <i class="fas fa-exclamation-triangle me-2"></i> Alertas
                </a>
            </li>
            <li class="menu-item">
                <a href="usuarios.php" class="menu-link">
                    <i class="fas fa-users me-2"></i> Usuarios
                </a>
            </li>
            <li class="menu-item">
                <a href="ia_ayuda.php" class="menu-link">
                    <i class="fas fa-robot me-2"></i> Asistente IA
                </a>
            </li>
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
        <div class="main-header gradient-bg shadow-sm mb-4">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="fw-bold"><i class="fas fa-box me-2"></i>Gestión de Productos</h2>
                        <p class="mb-0 text-light">Administra el catálogo completo de productos del inventario</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-white text-primary fs-6 px-3 py-2 shadow-sm">
                            <i class="fas fa-user me-1"></i>Rol: <?= htmlspecialchars($_SESSION['rol']??'') ?>
                        </span>
                        <?php if (in_array($_SESSION['rol'], ['admin', 'coordinador', 'auxiliar'])): ?>
                        <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                            <i class="fas fa-plus me-2"></i>Nuevo Producto
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4 animate-fade-in">
            <div class="col-md-3">
                <div class="stats-card border-primary border-2">
                    <div class="stats-icon text-primary">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="stats-number text-primary display-6 fw-bold"><?= $stats['total_productos'] ?></div>
                    <div class="text-muted">Total Productos</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card border-info border-2">
                    <div class="stats-icon text-info">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stats-number text-info display-6 fw-bold"><?= number_format($stats['stock_total']) ?></div>
                    <div class="text-muted">Stock Total</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card border-warning border-2">
                    <div class="stats-icon text-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stats-number text-warning display-6 fw-bold"><?= $stats['productos_bajo_stock'] ?></div>
                    <div class="text-muted">Stock Bajo (≤10)</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card border-danger border-2">
                    <div class="stats-icon text-danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stats-number text-danger display-6 fw-bold"><?= $stats['productos_sin_stock'] ?></div>
                    <div class="text-muted">Sin Stock</div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show animate-fade-in" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show animate-fade-in" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="filter-card animate-fade-in shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-primary"><i class="fas fa-filter me-2"></i>Filtros y Búsqueda</h5>
                <a href="reportes.php?tabla=productos" class="btn btn-outline-primary shadow-sm">
                    <i class="fas fa-chart-line me-2"></i>Ver Reportes
                </a>
            </div>
            <div class="row align-items-end g-2">
                <div class="col-md-3">
                    <label for="filtroNombre" class="form-label fw-bold">Buscar por nombre:</label>
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white"><i class="fas fa-search"></i></span>
                        <input type="text" id="filtroNombre" class="form-control border-primary" 
                               placeholder="Nombre del producto..." value="<?= htmlspecialchars($filtro_nombre) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label for="filtroCategoria" class="form-label fw-bold">Categoría:</label>
                    <select id="filtroCategoria" class="form-select border-info">
                        <option value="">Todas</option>
                        <?php while($cat = $categorias->fetch_assoc()): ?>
                        <option value="<?= $cat['id_categ'] ?>" <?= $filtro_categoria == $cat['id_categ'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nombre']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filtroSubcategoria" class="form-label fw-bold">Subcategoría:</label>
                    <select id="filtroSubcategoria" class="form-select border-info">
                        <option value="">Todas</option>
                        <?php 
                        $subcats_filtro = $db->conn->query("SELECT sc.id_subcg, sc.nombre, c.nombre as categoria_nombre FROM Subcategoria sc JOIN Categoria c ON sc.id_categ = c.id_categ ORDER BY c.nombre, sc.nombre");
                        while($subcat = $subcats_filtro->fetch_assoc()): ?>
                        <option value="<?= $subcat['id_subcg'] ?>" <?= (isset($_GET['subcategoria']) && $_GET['subcategoria'] == $subcat['id_subcg']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($subcat['categoria_nombre']) ?> - <?= htmlspecialchars($subcat['nombre']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filtroProveedor" class="form-label fw-bold">Proveedor:</label>
                    <select id="filtroProveedor" class="form-select border-info">
                        <option value="">Todos</option>
                        <?php while($prov = $proveedores->fetch_assoc()): ?>
                        <option value="<?= $prov['id_nit'] ?>" <?= $filtro_proveedor == $prov['id_nit'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prov['razon_social']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filtroStock" class="form-label fw-bold">Stock máximo:</label>
                    <input type="number" id="filtroStock" class="form-control border-warning" 
                           placeholder="Ej: 50" value="<?= htmlspecialchars($filtro_stock) ?>">
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary shadow-sm" onclick="aplicarFiltros()">
                            <i class="fas fa-search me-1"></i>Filtrar
                        </button>
                        <button class="btn btn-warning shadow-sm" onclick="filtrarBajoStock()">
                            <i class="fas fa-exclamation-triangle me-1"></i>Stock Bajo
                        </button>
                        <button class="btn btn-outline-secondary shadow-sm" onclick="limpiarFiltros()">
                            <i class="fas fa-times me-1"></i>Limpiar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Productos -->
        <div class="table-card animate-fade-in shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold text-primary">Productos filtrados: <span id="productosFiltrados"></span> / Total: <span id="productosTotal"></span></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0 align-middle">
                    <thead class="gradient-bg text-white">
                        <tr>
                            <th class="text-center"><i class="fas fa-hashtag me-1"></i>ID</th>
                            <th><i class="fas fa-box me-1"></i>Producto</th>
                            <th><i class="fas fa-info me-1"></i>Detalles</th>
                            <th><i class="fas fa-tags me-1"></i>Categoría</th>
                            <th><i class="fas fa-truck me-1"></i>Proveedor</th>
                            <th><i class="fas fa-user me-1"></i>Responsable</th>
                            <th class="text-center"><i class="fas fa-layer-group me-1"></i>Stock</th>
                            <th class="text-center"><i class="fas fa-calendar me-1"></i>Ingreso</th>
                            <th class="text-center"><i class="fas fa-chart-line me-1"></i>Actividad</th>
                            <th class="text-center"><i class="fas fa-cogs me-1"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    $filtrados = 0;
                    $total = 0;
                    $allRows = $db->conn->query("SELECT COUNT(*) as total FROM Productos")->fetch_assoc();
                    $total = $allRows['total'];
                    
                    if ($result && $result->num_rows > 0) {
                        $rowIndex = 0;
                        while($row = $result->fetch_assoc()): 
                        $filtrados++;
                        $rowClass = ($rowIndex % 2 == 0) ? 'table-light' : 'table-white';
                        $rowIndex++;
                        $stockClass = '';
                        $stockIcon = '';
                        if ($row['stock'] == 0) {
                            $stockClass = 'bg-danger text-white';
                            $stockIcon = '<i class="fas fa-times-circle me-1"></i>';
                        } elseif ($row['stock'] <= 10) {
                            $stockClass = 'bg-warning text-dark';
                            $stockIcon = '<i class="fas fa-exclamation-triangle me-1"></i>';
                        } else {
                            $stockClass = 'bg-success text-white';
                            $stockIcon = '<i class="fas fa-check-circle me-1"></i>';
                        }
                    ?>
                        <tr class="animate-fade-in <?= $rowClass ?>">
                            <td class="text-center"><span class="badge bg-primary fs-6 px-2 py-1 shadow-sm"><?= $row['id_prod'] ?></span></td>
                            <td>
                                <strong class="text-dark fs-6"><?= htmlspecialchars($row['nombre']) ?></strong>
                                <small class="d-block text-muted">Modelo: <?= htmlspecialchars($row['modelo']) ?></small>
                            </td>
                            <td>
                                <small>
                                    <span class="badge bg-secondary"><i class="fas fa-resize-arrows-alt me-1"></i>Talla: <?= htmlspecialchars($row['talla']) ?></span><br>
                                    <span class="badge bg-info text-dark"><i class="fas fa-palette me-1"></i>Color: <?= htmlspecialchars($row['color']) ?></span><br>
                                    <span class="badge bg-light text-dark"><i class="fas fa-cube me-1"></i>Material: <?= htmlspecialchars($row['material']) ?></span>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark fs-6 px-2 py-1 shadow-sm"><?= htmlspecialchars($row['categoria_nombre'] ?? 'N/A') ?></span>
                                <small class="d-block text-muted">Sub: <?= htmlspecialchars($row['subcategoria_nombre'] ?? 'N/A') ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark px-2 py-1 shadow-sm"><?= htmlspecialchars($row['proveedor_nombre'] ?? 'Sin proveedor') ?></span></td>
                            <td><span class="badge bg-light text-dark px-2 py-1 shadow-sm"><?= htmlspecialchars($row['usuario_nombre'] ?? 'Sin responsable') ?></span></td>
                            <td class="text-center">
                                <span class="badge <?= $stockClass ?> stock-badge fs-6 px-2 py-1 shadow-sm"><?= $stockIcon ?><?= $row['stock'] ?></span>
                            </td>
                            <td class="text-center">
                                <small><?= date('d/m/Y', strtotime($row['fecha_ing'])) ?></small>
                            </td>
                            <td class="text-center">
                                <small>
                                    <span class="badge bg-secondary"><i class="fas fa-sign-out-alt me-1"></i><?= $row['total_salidas'] ?> salidas</span><br>
                                    <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i><?= $row['total_alertas'] ?> alertas</span>
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-info btn-action" 
                                            onclick="verDetalles(<?= $row['id_prod'] ?>, '<?= addslashes($row['nombre']) ?>', '<?= addslashes($row['modelo']) ?>', '<?= addslashes($row['talla']) ?>', '<?= addslashes($row['color']) ?>', <?= $row['stock'] ?>, '<?= $row['fecha_ing'] ?>', '<?= addslashes($row['material']) ?>', '<?= addslashes($row['categoria_nombre'] ?? '') ?>', '<?= addslashes($row['subcategoria_nombre'] ?? '') ?>', '<?= addslashes($row['proveedor_nombre'] ?? '') ?>', '<?= addslashes($row['usuario_nombre'] ?? '') ?>')"
                                            title="Ver Detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (in_array($_SESSION['rol'], ['admin', 'coordinador', 'auxiliar'])): ?>
                                    <button type="button" class="btn btn-outline-warning btn-action" 
                                            onclick="editarProducto(<?= $row['id_prod'] ?>, '<?= addslashes($row['nombre']) ?>', '<?= addslashes($row['modelo']) ?>', '<?= addslashes($row['talla']) ?>', '<?= addslashes($row['color']) ?>', <?= $row['stock'] ?>, '<?= $row['fecha_ing'] ?>', '<?= addslashes($row['material']) ?>', <?= $row['id_subcg'] ?? 0 ?>, <?= $row['id_nit'] ?? 0 ?>, <?= $row['num_doc'] ?? 0 ?>)"
                                            title="Editar Producto">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if (in_array($_SESSION['rol'], ['admin', 'coordinador'])): ?>
                                    <button type="button" class="btn btn-outline-danger btn-action"
                                            onclick="confirmarEliminar(<?= $row['id_prod'] ?>, '<?= addslashes($row['nombre']) ?>')"
                                            title="Eliminar Producto">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; 
                    } else { 
                        echo '<tr><td colspan="10" class="text-center">No se encontraron productos</td></tr>';
                    } ?>
                    <script>
                        document.getElementById('productosFiltrados').textContent = "<?= $filtrados ?>";
                        document.getElementById('productosTotal').textContent = "<?= $total ?>";
                    </script>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para crear producto -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header gradient-bg">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-plus-circle me-2"></i>Nuevo Producto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label fw-bold">
                                        <i class="fas fa-box me-1"></i>Nombre del Producto
                                    </label>
                                    <input type="text" name="nombre" id="nombre" class="form-control border-primary" 
                                           placeholder="Ej: Camiseta Polo" required maxlength="100">
                                    <div class="invalid-feedback">Ingrese el nombre del producto.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="modelo" class="form-label fw-bold">
                                        <i class="fas fa-barcode me-1"></i>Modelo
                                    </label>
                                    <input type="text" name="modelo" id="modelo" class="form-control border-info" 
                                           placeholder="Ej: POL-001" required maxlength="50">
                                    <div class="invalid-feedback">Ingrese el modelo.</div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="talla" class="form-label fw-bold">
                                        <i class="fas fa-resize-arrows-alt me-1"></i>Talla
                                    </label>
                                    <input type="text" name="talla" id="talla" class="form-control border-secondary" 
                                           placeholder="Ej: M, L, XL" required maxlength="20">
                                    <div class="invalid-feedback">Ingrese la talla.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="color" class="form-label fw-bold">
                                        <i class="fas fa-palette me-1"></i>Color
                                    </label>
                                    <input type="text" name="color" id="color" class="form-control border-info" 
                                           placeholder="Ej: Azul" required maxlength="30">
                                    <div class="invalid-feedback">Ingrese el color.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="stock" class="form-label fw-bold">
                                        <i class="fas fa-layer-group me-1"></i>Stock Inicial
                                    </label>
                                    <input type="number" name="stock" id="stock" class="form-control border-warning" 
                                           placeholder="Ej: 100" required min="0">
                                    <div class="invalid-feedback">Ingrese el stock inicial.</div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_ing" class="form-label fw-bold">
                                        <i class="fas fa-calendar me-1"></i>Fecha de Ingreso
                                    </label>
                                    <input type="date" name="fecha_ing" id="fecha_ing" class="form-control border-info" 
                                           value="<?= date('Y-m-d') ?>" required>
                                    <div class="invalid-feedback">Seleccione la fecha de ingreso.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="material" class="form-label fw-bold">
                                        <i class="fas fa-cube me-1"></i>Material
                                    </label>
                                    <input type="text" name="material" id="material" class="form-control border-secondary" 
                                           placeholder="Ej: Algodón 100%" required maxlength="100">
                                    <div class="invalid-feedback">Ingrese el material.</div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="id_subcg" class="form-label fw-bold">
                                        <i class="fas fa-tag me-1"></i>Subcategoría
                                    </label>
                                    <select name="id_subcg" id="id_subcg" class="form-select border-info" required>
                                        <option value="">Seleccione...</option>
                                        <?php 
                                        $subcats = $db->conn->query("SELECT sc.id_subcg, sc.nombre, c.nombre as categoria_nombre 
                                                                   FROM Subcategoria sc 
                                                                   JOIN Categoria c ON sc.id_categ = c.id_categ 
                                                                   ORDER BY c.nombre, sc.nombre");
                                        while($subcat = $subcats->fetch_assoc()): 
                                        ?>
                                        <option value="<?= $subcat['id_subcg'] ?>">
                                            <?= htmlspecialchars($subcat['categoria_nombre']) ?> - <?= htmlspecialchars($subcat['nombre']) ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="invalid-feedback">Seleccione la subcategoría.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="id_nit" class="form-label fw-bold">
                                        <i class="fas fa-truck me-1"></i>Proveedor
                                    </label>
                                    <select name="id_nit" id="id_nit" class="form-select border-info" required>
                                        <option value="">Seleccione...</option>
                                        <?php 
                                        $provs = $db->conn->query("SELECT id_nit, razon_social FROM Proveedores ORDER BY razon_social");
                                        while($prov = $provs->fetch_assoc()): 
                                        ?>
                                        <option value="<?= $prov['id_nit'] ?>">
                                            <?= htmlspecialchars($prov['razon_social']) ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <div class="invalid-feedback">Seleccione el proveedor.</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="num_doc" class="form-label fw-bold">
                                        <i class="fas fa-user me-1"></i>Usuario Responsable
                                    </label>
                                    <input type="hidden" name="num_doc" id="num_doc" value="<?= htmlspecialchars($_SESSION['user']['num_doc']) ?>">
                                    <span class="form-control bg-light border-secondary fw-bold"><i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['user']['nombres']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_producto" class="btn btn-success" id="btnCrearProducto">
                            <i class="fas fa-save me-2"></i>Guardar Producto
                        </button>
                    </div>
                </form>
                <script>
                // Validación visual y lógica del formulario
                (function () {
                  'use strict';
                  var forms = document.querySelectorAll('.needs-validation');
                  Array.prototype.slice.call(forms).forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                      if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                      }
                      form.classList.add('was-validated');
                    }, false);
                  });
                })();
                </script>
            </div>
        </div>
    </div>

    <!-- Modal para editar producto -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #ffc107, #ff8f00); color: white;">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-edit me-2"></i>Editar Producto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <!-- Secciones agrupadas -->
                <form method="POST" id="editForm" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="id_prod" id="editId">
                        <!-- Sección General -->
                        <div class="mb-3 border-bottom pb-2">
                            <h6 class="fw-bold text-primary mb-2"><i class="fas fa-info-circle me-1"></i>Datos Generales</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label for="editNombre" class="form-label fw-bold"><i class="fas fa-box me-1"></i>Nombre</label>
                                    <input type="text" name="nombre" id="editNombre" class="form-control border-primary" required maxlength="100">
                                    <div class="invalid-feedback">Ingrese el nombre del producto.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="editModelo" class="form-label fw-bold"><i class="fas fa-barcode me-1"></i>Modelo</label>
                                    <input type="text" name="modelo" id="editModelo" class="form-control border-info" required maxlength="50">
                                    <div class="invalid-feedback">Ingrese el modelo.</div>
                                </div>
                            </div>
                            <div class="row g-2 mt-2">
                                <div class="col-md-4">
                                    <label for="editTalla" class="form-label fw-bold"><i class="fas fa-resize-arrows-alt me-1"></i>Talla</label>
                                    <input type="text" name="talla" id="editTalla" class="form-control border-secondary" required maxlength="20">
                                    <div class="invalid-feedback">Ingrese la talla.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="editColor" class="form-label fw-bold"><i class="fas fa-palette me-1"></i>Color</label>
                                    <input type="text" name="color" id="editColor" class="form-control border-info" required maxlength="30">
                                    <div class="invalid-feedback">Ingrese el color.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="editMaterial" class="form-label fw-bold"><i class="fas fa-cube me-1"></i>Material</label>
                                    <input type="text" name="material" id="editMaterial" class="form-control border-secondary" required maxlength="100">
                                    <div class="invalid-feedback">Ingrese el material.</div>
                                </div>
                            </div>
                        </div>
                        <!-- Sección Stock -->
                        <div class="mb-3 border-bottom pb-2">
                            <h6 class="fw-bold text-warning mb-2"><i class="fas fa-layer-group me-1"></i>Stock y Fecha</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label for="editStock" class="form-label fw-bold"><i class="fas fa-layer-group me-1"></i>Stock</label>
                                    <input type="number" name="stock" id="editStock" class="form-control border-warning" required min="0">
                                    <div class="invalid-feedback">Ingrese el stock.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="editFechaIng" class="form-label fw-bold"><i class="fas fa-calendar me-1"></i>Fecha de Ingreso</label>
                                    <input type="date" name="fecha_ing" id="editFechaIng" class="form-control border-info" required>
                                    <div class="invalid-feedback">Seleccione la fecha de ingreso.</div>
                                </div>
                            </div>
                        </div>
                        <!-- Sección Proveedor -->
                        <div class="mb-3">
                            <h6 class="fw-bold text-info mb-2"><i class="fas fa-truck me-1"></i>Proveedor y Usuario</h6>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label for="editSubcat" class="form-label fw-bold"><i class="fas fa-tag me-1"></i>Subcategoría</label>
                                    <select name="id_subcg" id="editSubcat" class="form-select border-info" required>
                                        <option value="">Seleccione...</option>
                                        <!-- Se llenará dinámicamente -->
                                    </select>
                                    <div class="invalid-feedback">Seleccione la subcategoría.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="editProveedor" class="form-label fw-bold"><i class="fas fa-truck me-1"></i>Proveedor</label>
                                    <select name="id_nit" id="editProveedor" class="form-select border-info" required>
                                        <option value="">Seleccione...</option>
                                        <!-- Se llenará dinámicamente -->
                                    </select>
                                    <div class="invalid-feedback">Seleccione el proveedor.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="editUsuario" class="form-label fw-bold"><i class="fas fa-user me-1"></i>Usuario Responsable</label>
                                    <select name="num_doc" id="editUsuario" class="form-select border-secondary" required>
                                        <option value="">Seleccione...</option>
                                        <!-- Se llenará dinámicamente -->
                                    </select>
                                    <div class="invalid-feedback">Seleccione el usuario responsable.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="modificar_producto" class="btn btn-warning" id="btnEditarProducto">
                            <span id="spinnerEditar" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            <i class="fas fa-save me-2"></i>Actualizar
                        </button>
                    </div>
                </form>
                <script>
                // --- AJAX para cargar datos del producto seleccionado ---
                // Documentación: Esta función obtiene los datos del producto vía AJAX y los carga en el modal de edición.
                function cargarDatosProducto(id) {
                  fetch('api/productos.php?id=' + id)
                    .then(response => response.json())
                    .then(data => {
                      // Cargar campos
                      document.getElementById('editId').value = data.id_prod;
                      document.getElementById('editNombre').value = data.nombre;
                      document.getElementById('editModelo').value = data.modelo;
                      document.getElementById('editTalla').value = data.talla;
                      document.getElementById('editColor').value = data.color;
                      document.getElementById('editMaterial').value = data.material;
                      document.getElementById('editStock').value = data.stock;
                      document.getElementById('editFechaIng').value = data.fecha_ing;
                      // Subcategoría, proveedor, usuario
                      document.getElementById('editSubcat').value = data.id_subcg;
                      document.getElementById('editProveedor').value = data.id_nit;
                      document.getElementById('editUsuario').value = data.num_doc;
                    });
                }

                // --- Validación visual y lógica del formulario de edición ---
                // Documentación: Valida los campos antes de enviar y muestra spinner en el botón.
                (function () {
                  'use strict';
                  var form = document.getElementById('editForm');
                  var btnEditar = document.getElementById('btnEditarProducto');
                  var spinner = document.getElementById('spinnerEditar');
                  form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                      event.preventDefault();
                      event.stopPropagation();
                    } else {
                      spinner.classList.remove('d-none');
                      btnEditar.disabled = true;
                      // Simular envío y mostrar mensaje de éxito
                      setTimeout(function() {
                        spinner.classList.add('d-none');
                        btnEditar.disabled = false;
                        alert('Producto actualizado correctamente.');
                        // Resaltar campos modificados
                        Array.from(form.elements).forEach(function(el) {
                          if (el.value !== el.defaultValue && el.type !== 'hidden') {
                            el.classList.add('border-success');
                          }
                        });
                      }, 1200);
                    }
                    form.classList.add('was-validated');
                  }, false);
                })();
                // --- Fin de optimización edición ---
                </script>
            </div>
        </div>
    </div>

    <!-- Modal para ver detalles -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #17a2b8, #138496); color: white;">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-eye me-2"></i>Detalles del Producto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Información general -->
                    <div class="row g-2 mb-2">
                        <div class="col-md-8">
                            <h6 class="fw-bold text-primary mb-2"><i class="fas fa-box me-2"></i>Información del Producto</h6>
                            <table class="table table-bordered table-sm">
                                <tr><td><i class="fas fa-box"></i> <strong>Nombre:</strong></td><td id="detailNombre"></td></tr>
                                <tr><td><i class="fas fa-barcode"></i> <strong>Modelo:</strong></td><td id="detailModelo"></td></tr>
                                <tr><td><i class="fas fa-resize-arrows-alt"></i> <strong>Talla:</strong></td><td id="detailTalla"></td></tr>
                                <tr><td><i class="fas fa-palette"></i> <strong>Color:</strong></td><td id="detailColor"></td></tr>
                                <tr><td><i class="fas fa-cube"></i> <strong>Material:</strong></td><td id="detailMaterial"></td></tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold text-warning mb-2"><i class="fas fa-layer-group me-2"></i>Stock y Fechas</h6>
                            <div class="text-center">
                                <div class="mb-3">
                                    <h3 id="detailStock" class="fw-bold"></h3>
                                    <small class="text-muted">Unidades en stock</small>
                                </div>
                                <p><strong>Fecha de ingreso:</strong><br><span id="detailFecha"></span></p>
                            </div>
                        </div>
                    </div>
                    <!-- Categorización, proveedor y usuario -->
                    <div class="row g-2 mt-2">
                        <div class="col-md-4">
                            <h6 class="fw-bold text-info mb-2"><i class="fas fa-tags me-2"></i>Categorización</h6>
                            <p><strong>Categoría:</strong> <span id="detailCategoria"></span></p>
                            <p><strong>Subcategoría:</strong> <span id="detailSubcategoria"></span></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold text-info mb-2"><i class="fas fa-truck me-2"></i>Proveedor</h6>
                            <p id="detailProveedor"></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-bold text-secondary mb-2"><i class="fas fa-user me-2"></i>Usuario Responsable</h6>
                            <p id="detailUsuario"></p>
                        </div>
                    </div>
                    <!-- Botón duplicar producto -->
                    <div class="row mt-3">
                        <div class="col text-end">
                            <button type="button" class="btn btn-success" id="btnDuplicarProducto">
                                <i class="fas fa-copy me-1"></i>Duplicar producto
                            </button>
                        </div>
                    </div>
                </div>
                <script>
                // --- Mostrar colores según estado de stock ---
                // Documentación: Aplica color al stock según cantidad
                function actualizarColorStock(stock) {
                  var el = document.getElementById('detailStock');
                  if (stock == 0) {
                    el.className = 'text-danger fw-bold';
                  } else if (stock <= 10) {
                    el.className = 'text-warning fw-bold';
                  } else {
                    el.className = 'text-success fw-bold';
                  }
                }

                // --- Botón duplicar producto ---
                // Documentación: Prellena el formulario de creación con los datos del producto mostrado
                document.getElementById('btnDuplicarProducto').onclick = function() {
                  document.getElementById('nombre').value = document.getElementById('detailNombre').textContent;
                  document.getElementById('modelo').value = document.getElementById('detailModelo').textContent;
                  document.getElementById('talla').value = document.getElementById('detailTalla').textContent;
                  document.getElementById('color').value = document.getElementById('detailColor').textContent;
                  document.getElementById('material').value = document.getElementById('detailMaterial').textContent;
                  document.getElementById('stock').value = document.getElementById('detailStock').textContent;
                  document.getElementById('fecha_ing').value = document.getElementById('detailFecha').textContent;
                  // Subcategoría, proveedor, usuario si están disponibles
                  // document.getElementById('id_subcg').value = ...
                  // document.getElementById('id_nit').value = ...
                  // document.getElementById('num_doc').value = ...
                  // Abrir modal de creación
                  var modal = new bootstrap.Modal(document.getElementById('createModal'));
                  modal.show();
                };
                // --- Fin de optimización detalles ---
                </script>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="fas fa-box fa-3x text-danger mb-3"></i>
                        <p>¿Está seguro de que desea eliminar este producto?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Producto:</strong> <span id="productName"></span>
                        </div>
                        <p class="text-muted">Esta acción no se puede deshacer.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fas fa-trash me-2"></i>Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sistema de Notificaciones -->
    <script src="public/js/notifications.js"></script>
    <script src="public/js/auto-notifications.js"></script>
    
    <script>
        let productToDelete = null;
        
        // Aplicar filtros automáticamente al cambiar los selects y el campo de stock
        ['filtroCategoria', 'filtroSubcategoria', 'filtroProveedor', 'filtroStock'].forEach(function(id) {
            var el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', function() {
                    aplicarFiltros();
                });
            }
        });

        function aplicarFiltros() {
            var params = [];
            var nombre = document.getElementById('filtroNombre').value;
            var categoria = document.getElementById('filtroCategoria').value;
            var subcategoria = document.getElementById('filtroSubcategoria').value;
            var proveedor = document.getElementById('filtroProveedor').value;
            var stock = document.getElementById('filtroStock').value;
            if (nombre) params.push('nombre=' + encodeURIComponent(nombre));
            if (categoria) params.push('categoria=' + encodeURIComponent(categoria));
            if (subcategoria) params.push('subcategoria=' + encodeURIComponent(subcategoria));
            if (proveedor) params.push('proveedor=' + encodeURIComponent(proveedor));
            if (stock) params.push('stock=' + encodeURIComponent(stock));
            window.location.href = 'productos.php' + (params.length ? '?' + params.join('&') : '');
        }
        
        // Filtrar productos con stock bajo
        function filtrarBajoStock() {
            window.location.href = 'productos.php?bajo_stock=1';
        }
        
        // Limpiar filtros
        function limpiarFiltros() {
            window.location.href = 'productos.php';
        }
        
        // Ver detalles
        function verDetalles(id, nombre, modelo, talla, color, stock, fecha, material, categoria, subcategoria, proveedor, usuario) {
            document.getElementById('detailNombre').textContent = nombre;
            document.getElementById('detailModelo').textContent = modelo;
            document.getElementById('detailTalla').textContent = talla;
            document.getElementById('detailColor').textContent = color;
            document.getElementById('detailMaterial').textContent = material;
            document.getElementById('detailStock').textContent = stock;
            document.getElementById('detailFecha').textContent = new Date(fecha).toLocaleDateString('es-ES');
            document.getElementById('detailCategoria').textContent = categoria || 'N/A';
            document.getElementById('detailSubcategoria').textContent = subcategoria || 'N/A';
            document.getElementById('detailProveedor').textContent = proveedor || 'Sin proveedor';
            document.getElementById('detailUsuario').textContent = usuario || 'Sin asignar';
            
            // Color del stock
            const stockElement = document.getElementById('detailStock');
            stockElement.className = 'text-' + (stock === 0 ? 'danger' : stock <= 10 ? 'warning' : 'success');
            
            new bootstrap.Modal(document.getElementById('detailsModal')).show();
        }
        
        // Editar producto
        function editarProducto(id, nombre, modelo, talla, color, stock, fecha, material, subcat, proveedor, usuario) {
            document.getElementById('editId').value = id;
            document.getElementById('editNombre').value = nombre;
            document.getElementById('editModelo').value = modelo;
            document.getElementById('editTalla').value = talla;
            document.getElementById('editColor').value = color;
            document.getElementById('editStock').value = stock;
            document.getElementById('editFechaIng').value = fecha;
            document.getElementById('editMaterial').value = material;
            
            // Cargar opciones dinámicamente
            cargarOpcionesEdicion(subcat, proveedor, usuario);
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        // Cargar opciones para el modal de edición
        function cargarOpcionesEdicion(selectedSubcat, selectedProveedor, selectedUsuario) {
            // Aquí podrías hacer llamadas AJAX para cargar las opciones actualizadas
            // Por simplicidad, usaremos los datos que ya tenemos
            const subcatSelect = document.getElementById('editSubcat');
            const proveedorSelect = document.getElementById('editProveedor');
            const usuarioSelect = document.getElementById('editUsuario');
            
            // Limpiar opciones actuales
            subcatSelect.innerHTML = '<option value="">Seleccione...</option>';
            proveedorSelect.innerHTML = '<option value="">Seleccione...</option>';
            usuarioSelect.innerHTML = '<option value="">Seleccione...</option>';
            
            // Cargar subcategorías (esto debería ser dinámico en una implementación real)
            <?php
            $subcats = $db->conn->query("SELECT sc.id_subcg, sc.nombre, c.nombre as categoria_nombre FROM Subcategoria sc JOIN Categoria c ON sc.id_categ = c.id_categ ORDER BY c.nombre, sc.nombre");
            while($subcat = $subcats->fetch_assoc()): 
            ?>
            subcatSelect.innerHTML += '<option value="<?= $subcat['id_subcg'] ?>"><?= addslashes($subcat['categoria_nombre']) ?> - <?= addslashes($subcat['nombre']) ?></option>';
            <?php endwhile; ?>
            
            // Cargar proveedores
            <?php
            $provs = $db->conn->query("SELECT id_nit, razon_social FROM Proveedores ORDER BY razon_social");
            while($prov = $provs->fetch_assoc()): 
            ?>
            proveedorSelect.innerHTML += '<option value="<?= $prov['id_nit'] ?>"><?= addslashes($prov['razon_social']) ?></option>';
            <?php endwhile; ?>
            
            // Cargar usuarios
            <?php
            $users = $db->conn->query("SELECT num_doc, nombres FROM users ORDER BY nombres");
            while($user = $users->fetch_assoc()): 
            ?>
            usuarioSelect.innerHTML += '<option value="<?= $user['num_doc'] ?>"><?= addslashes($user['nombres']) ?></option>';
            <?php endwhile; ?>
            
            // Usar setTimeout para asegurar que las opciones se carguen antes de seleccionar
            setTimeout(function() {
                // Debug: mostrar valores recibidos
                console.log('=== DEBUG EDICIÓN PRODUCTO ===');
                console.log('Subcategoría recibida:', selectedSubcat, typeof selectedSubcat);
                console.log('Proveedor recibido:', selectedProveedor, typeof selectedProveedor);
                console.log('Usuario recibido:', selectedUsuario, typeof selectedUsuario);
                
                // Seleccionar valores actuales con validación mejorada
                if (selectedSubcat && selectedSubcat != '0' && selectedSubcat != '' && selectedSubcat != 'null') {
                    subcatSelect.value = selectedSubcat;
                    console.log('Subcategoría establecida a:', subcatSelect.value);
                    // Verificar si realmente se seleccionó
                    if (subcatSelect.value != selectedSubcat) {
                        console.warn('ERROR: No se pudo seleccionar la subcategoría', selectedSubcat);
                        // Intentar encontrar la opción
                        for (let i = 0; i < subcatSelect.options.length; i++) {
                            if (subcatSelect.options[i].value == selectedSubcat) {
                                subcatSelect.selectedIndex = i;
                                console.log('Subcategoría seleccionada por índice:', i);
                                break;
                            }
                        }
                    }
                } else {
                    console.log('Subcategoría no válida o vacía:', selectedSubcat);
                }
                
                if (selectedProveedor && selectedProveedor != '0' && selectedProveedor != '' && selectedProveedor != 'null') {
                    proveedorSelect.value = selectedProveedor;
                    console.log('Proveedor establecido a:', proveedorSelect.value);
                }
                
                if (selectedUsuario && selectedUsuario != '0' && selectedUsuario != '' && selectedUsuario != 'null') {
                    usuarioSelect.value = selectedUsuario;
                    console.log('Usuario establecido a:', usuarioSelect.value);
                }
                
                // Verificación final
                console.log('=== ESTADO FINAL ===');
                console.log('Subcategoría final:', subcatSelect.value);
                console.log('Proveedor final:', proveedorSelect.value);
                console.log('Usuario final:', usuarioSelect.value);
                console.log('========================');
            }, 150);
        }
        
        // Confirmar eliminación
        function confirmarEliminar(id, nombre) {
            productToDelete = id;
            document.getElementById('productName').textContent = nombre;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Procesar eliminación
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (productToDelete) {
                window.location.href = `productos.php?eliminar=${productToDelete}`;
            }
        });
        
        // Enter en filtros
        document.getElementById('filtroNombre').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                aplicarFiltros();
            }
        });
        
        // Animaciones al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.animate-fade-in');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });

            // Protección contra doble envío del formulario
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                let isSubmitting = false;
                form.addEventListener('submit', function(e) {
                    if (isSubmitting) {
                        e.preventDefault();
                        return false;
                    }
                    
                    isSubmitting = true;
                    const submitButtons = form.querySelectorAll('button[type="submit"]');
                    submitButtons.forEach(button => {
                        button.disabled = true;
                        const originalText = button.innerHTML;
                        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
                        
                        // Restaurar el botón después de 10 segundos en caso de error
                        setTimeout(() => {
                            button.disabled = false;
                            button.innerHTML = originalText;
                            isSubmitting = false;
                        }, 10000);
                    });
                });
            });

            // Sistema de notificaciones automáticas
            // Notificaciones automáticas (solo si está definido)
            <?php if (isset($show_notification) && $show_notification): ?>
            console.log('DEBUG: Mostrando notificación tipo: <?= $show_notification ?>');
            setTimeout(function() {
                const notificationSystem = new NotificationSystem();
                <?php if ($show_notification === 'created'): ?>
                notificationSystem.showProductChange('create', 'Producto creado exitosamente', 'success');
                <?php elseif ($show_notification === 'updated'): ?>
                notificationSystem.showProductChange('update', 'Producto actualizado exitosamente', 'success');
                <?php elseif ($show_notification === 'deleted'): ?>
                notificationSystem.showProductChange(
                    'delete', 
                    'Producto "<?= htmlspecialchars($producto_eliminado['nombre']) ?>" (ID: <?= $producto_eliminado['id'] ?>) eliminado del sistema', 
                    'warning',
                    {
                        id: <?= $producto_eliminado['id'] ?>,
                        nombre: '<?= htmlspecialchars($producto_eliminado['nombre']) ?>'
                    }
                );
                <?php endif; ?>
            }, 1000);
            <?php endif; ?>
        });
    </script>
</body>
</html>