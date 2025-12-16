<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
require_once 'app/helpers/Database.php';
require_once 'app/helpers/SistemaNotificaciones.php';
require_once 'includes/responsive-helper.php';

$db = new Database();
$sistemaNotificaciones = new SistemaNotificaciones($db);

// Verificar si es administrador
$es_admin = (isset($_SESSION['user']) && $_SESSION['user']['rol'] === 'admin');

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

// Eliminar proveedor
if (isset($_GET['eliminar'])) {
    $id_nit = intval($_GET['eliminar']);
    
    // Verificar si tiene productos o reportes asociados
    $productos = $db->conn->query("SELECT COUNT(*) FROM Productos WHERE id_nit = $id_nit");
    $reportes = $db->conn->query("SELECT COUNT(*) FROM Reportes WHERE id_nit = $id_nit");
    
    $prodCount = $productos->fetch_row()[0];
    $repCount = $reportes->fetch_row()[0];
    
    if ($prodCount > 0 || $repCount > 0) {
        $entidades = [];
        if ($prodCount > 0) $entidades[] = "productos ($prodCount)";
        if ($repCount > 0) $entidades[] = "reportes ($repCount)";
        $errorMsg = "No se puede eliminar el proveedor porque tiene " . implode(' y ', $entidades) . " asociados.";
    } else {
        $old = $db->conn->query("SELECT * FROM Proveedores WHERE id_nit = $id_nit")->fetch_assoc();
        $stmt = $db->conn->prepare("DELETE FROM Proveedores WHERE id_nit = ?");
        $stmt->bind_param('i', $id_nit);
        $stmt->execute();
        $stmt->close();
        // Generar notificación automática para todos los usuarios
        $usuario_nombre = $_SESSION['user']['nombre'] ?? $_SESSION['user']['name'] ?? 'Usuario';
        $sistemaNotificaciones->notificarEliminacionProveedor($old, $usuario_nombre);
        
        $razon_social = $old['razon_social'];
        header("Location: proveedores.php?action=delete&nit=$id_nit&razon=" . urlencode($razon_social));
        exit;
    }
}

// Manejo de acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'agregar':
                $id_nit = $_POST['id_nit'];
                $razon_social = $_POST['razon_social'];
                $contacto = $_POST['contacto'];
                $direccion = $_POST['direccion'] ?? '';
                $correo = $_POST['correo'] ?? '';
                $telefono = $_POST['telefono'] ?? '';
                $estado = $_POST['estado'];
                $detalles = $_POST['detalles'] ?? '';
                
                // Verificar si el NIT ya existe
                $check_stmt = $db->conn->prepare("SELECT id_nit FROM Proveedores WHERE id_nit = ?");
                $check_stmt->bind_param('s', $id_nit);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                
                if ($result->num_rows > 0) {
                    throw new Exception('Ya existe un proveedor con este NIT');
                }
                $check_stmt->close();
                
                $stmt = $db->conn->prepare("INSERT INTO Proveedores (id_nit, razon_social, contacto, direccion, correo, telefono, estado, detalles) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssssss', $id_nit, $razon_social, $contacto, $direccion, $correo, $telefono, $estado, $detalles);
                $stmt->execute();
                $stmt->close();
                
                // Generar notificación automática
                $usuario_nombre = $_SESSION['user']['nombre'] ?? $_SESSION['user']['name'] ?? 'Usuario';
                $sistemaNotificaciones->notificarNuevoProveedor($id_nit, $razon_social, $usuario_nombre);
                
                echo json_encode(['success' => true, 'message' => 'Proveedor agregado exitosamente']);
                break;
                
            case 'editar':
                $id_nit_original = $_POST['id_nit_original'];
                $id_nit = $_POST['id_nit'];
                $razon_social = $_POST['razon_social'];
                $contacto = $_POST['contacto'];
                $direccion = $_POST['direccion'] ?? '';
                $correo = $_POST['correo'] ?? '';
                $telefono = $_POST['telefono'] ?? '';
                $estado = $_POST['estado'];
                $detalles = $_POST['detalles'] ?? '';
                
                // Si cambió el NIT, verificar que el nuevo no exista
                if ($id_nit !== $id_nit_original) {
                    $check_stmt = $db->conn->prepare("SELECT id_nit FROM Proveedores WHERE id_nit = ? AND id_nit != ?");
                    $check_stmt->bind_param('ss', $id_nit, $id_nit_original);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        throw new Exception('Ya existe un proveedor con este NIT');
                    }
                    $check_stmt->close();
                }
                
                $stmt = $db->conn->prepare("UPDATE Proveedores SET id_nit = ?, razon_social = ?, contacto = ?, direccion = ?, correo = ?, telefono = ?, estado = ?, detalles = ? WHERE id_nit = ?");
                $stmt->bind_param('sssssssss', $id_nit, $razon_social, $contacto, $direccion, $correo, $telefono, $estado, $detalles, $id_nit_original);
                $stmt->execute();
                $stmt->close();
                
                echo json_encode(['success' => true, 'message' => 'Proveedor actualizado exitosamente']);
                break;
                
            default:
                throw new Exception('Acción no válida');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
    exit;
}

// Filtros
$filtro = '';
$estado_filtro = '';

if (isset($_GET['filtro']) && $_GET['filtro'] !== '') {
    $filtro = $_GET['filtro'];
}
if (isset($_GET['estado']) && $_GET['estado'] !== '') {
    $estado_filtro = $_GET['estado'];
}

// Consulta con JOIN para obtener estadísticas
$sql = "SELECT p.id_nit, p.razon_social, p.contacto, p.direccion, p.correo, p.telefono, p.estado, p.detalles,
               COUNT(DISTINCT pr.id_prod) AS total_productos,
               COUNT(DISTINCT r.id_repor) AS total_reportes
        FROM Proveedores p 
        LEFT JOIN Productos pr ON p.id_nit = pr.id_nit 
        LEFT JOIN Reportes r ON p.id_nit = r.id_nit";

$where_conditions = [];
$params = [];
$types = '';

if ($filtro) {
    $where_conditions[] = "(p.razon_social LIKE ? OR p.contacto LIKE ? OR p.correo LIKE ?)";
    $params[] = "%$filtro%";
    $params[] = "%$filtro%";
    $params[] = "%$filtro%";
    $types .= 'sss';
}

if ($estado_filtro) {
    $where_conditions[] = "p.estado = ?";
    $params[] = $estado_filtro;
    $types .= 's';
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " GROUP BY p.id_nit ORDER BY p.razon_social";

if (!empty($params)) {
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $db->conn->query($sql);
}

// Generar estadísticas generales
$stats_result = $db->conn->query("SELECT 
    COUNT(*) AS total_proveedores,
    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS activos,
    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) AS inactivos
    FROM Proveedores");
$stats = $stats_result->fetch_assoc();

// Configuración de la página responsiva
$pageConfig = array_merge(ResponsivePageHelper::setActiveModule('proveedores'), [
    'MODULE_TITLE' => 'Gestión de Proveedores',
    'MODULE_DESCRIPTION' => 'Administración de proveedores del sistema Inventixor',
    'MODULE_ICON' => 'fas fa-truck',
    'MODULE_SUBTITLE' => 'Administrar proveedores y su información de contacto',
    'ADDITIONAL_STYLES' => ResponsivePageHelper::getModuleStyles('proveedores'),
    'USER_MENU' => ResponsivePageHelper::getUserMenu($_SESSION['rol'] ?? ''),
    'NOTIFICATION_SCRIPT' => ResponsivePageHelper::getNotificationScript(),
    'ADDITIONAL_SCRIPTS' => ResponsivePageHelper::getTableScripts('proveedoresTable') . ResponsivePageHelper::getFormScripts()
]);

// Capturar el contenido del módulo
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores - Inventixor</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="public/css/responsive-sidebar.css">
    
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
        
        .provider-card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .provider-card:hover {
            transform: translateY(-2px);
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
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
                <a href="productos.php" class="menu-link">
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
                <a href="proveedores.php" class="menu-link active">
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
            <?php if ($es_admin): ?>
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

<!-- Stats Cards -->
<div class="container-fluid mb-4">
    <div class="row g-3">
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card stat-card bg-primary text-white animate-fade-in" style="animation-delay: 0.1s">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3">
                        <i class="fas fa-truck fa-2x"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="mb-0"><?php echo $stats['total_proveedores']; ?></h3>
                        <p class="mb-0">Total Proveedores</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card stat-card bg-success text-white animate-fade-in" style="animation-delay: 0.2s">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="mb-0"><?php echo $stats['activos']; ?></h3>
                        <p class="mb-0">Activos</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card stat-card bg-warning text-white animate-fade-in" style="animation-delay: 0.3s">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3">
                        <i class="fas fa-pause-circle fa-2x"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="mb-0"><?php echo $stats['inactivos']; ?></h3>
                        <p class="mb-0">Inactivos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros y acciones -->
<div class="container-fluid mb-4">
    <div class="card animate-slide-up" style="animation-delay: 0.4s">
        <div class="card-header bg-light">
            <div class="row align-items-center">
                <div class="col-12 col-md-6">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros y Búsqueda</h5>
                </div>
                <div class="col-12 col-md-6 text-md-end mt-2 mt-md-0">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#agregarProveedorModal">
                        <i class="fas fa-plus me-1"></i>Nuevo Proveedor
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="filtro" class="form-label">Buscar:</label>
                    <input type="text" class="form-control" id="filtro" name="filtro" 
                           value="<?php echo htmlspecialchars($filtro); ?>" 
                           placeholder="Razón social, contacto, correo...">
                </div>
                <div class="col-12 col-md-4">
                    <label for="estado" class="form-label">Estado:</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos los estados</option>
                        <option value="activo" <?php echo $estado_filtro === 'activo' ? 'selected' : ''; ?>>Activos</option>
                        <option value="inactivo" <?php echo $estado_filtro === 'inactivo' ? 'selected' : ''; ?>>Inactivos</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <div class="btn-group w-100" role="group">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search me-1"></i>Filtrar
                        </button>
                        <a href="proveedores.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabla de proveedores -->
<div class="container-fluid">
    <div class="card animate-slide-up" style="animation-delay: 0.5s">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-table me-2"></i>Lista de Proveedores</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="proveedoresTable">
                    <thead class="table-dark">
                        <tr>
                            <th>NIT</th>
                            <th>Razón Social</th>
                            <th>Contacto</th>
                            <th>Teléfono</th>
                            <th>Correo</th>
                            <th>Estado</th>
                            <th>Productos</th>
                            <th>Reportes</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['id_nit']); ?></strong>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($row['razon_social']); ?></strong>
                                    <?php if (!empty($row['direccion'])): ?>
                                    <br><small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($row['direccion']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['contacto']); ?></td>
                            <td>
                                <?php if (!empty($row['telefono'])): ?>
                                <a href="tel:<?php echo $row['telefono']; ?>" class="text-decoration-none">
                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($row['telefono']); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">No disponible</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['correo'])): ?>
                                <a href="mailto:<?php echo $row['correo']; ?>" class="text-decoration-none">
                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($row['correo']); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">No disponible</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $row['estado'] === 'activo' ? 'success' : 'warning'; ?>">
                                    <i class="fas fa-<?php echo $row['estado'] === 'activo' ? 'check-circle' : 'pause-circle'; ?> me-1"></i>
                                    <?php echo ucfirst($row['estado']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info">
                                    <?php echo $row['total_productos']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary">
                                    <?php echo $row['total_reportes']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-info" 
                                            onclick="verDetalles(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                            title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="editarProveedor(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($row['total_productos'] == 0 && $row['total_reportes'] == 0): ?>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="confirmarEliminar(<?php echo $row['id_nit']; ?>, '<?php echo htmlspecialchars($row['razon_social']); ?>')"
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-outline-secondary" 
                                            disabled title="No se puede eliminar: tiene productos o reportes asociados">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Completar el contenido del módulo y generar la página
$moduleContent = ob_get_clean();
$pageConfig['MODULE_CONTENT'] = $moduleContent;
?>

<!-- Modales responsivos -->
<div class="modal fade" id="agregarProveedorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-truck me-2"></i>Agregar Nuevo Proveedor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="agregarProveedorForm" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="id_nit" class="form-label">
                                <i class="fas fa-id-badge me-1"></i>NIT/Identificación
                            </label>
                            <input type="text" name="id_nit" id="id_nit" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="razon_social" class="form-label">
                                <i class="fas fa-building me-1"></i>Razón Social
                            </label>
                            <input type="text" name="razon_social" id="razon_social" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="contacto" class="form-label">
                                <i class="fas fa-user me-1"></i>Persona de Contacto
                            </label>
                            <input type="text" name="contacto" id="contacto" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">
                                <i class="fas fa-phone me-1"></i>Teléfono
                            </label>
                            <input type="tel" name="telefono" id="telefono" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="correo" class="form-label">
                                <i class="fas fa-envelope me-1"></i>Correo Electrónico
                            </label>
                            <input type="email" name="correo" id="correo" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="estado" class="form-label">
                                <i class="fas fa-toggle-on me-1"></i>Estado
                            </label>
                            <select name="estado" id="estado" class="form-select" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="direccion" class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i>Dirección
                            </label>
                            <textarea name="direccion" id="direccion" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label for="detalles" class="form-label">
                                <i class="fas fa-info-circle me-1"></i>Detalles Adicionales
                            </label>
                            <textarea name="detalles" id="detalles" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar Proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editarProveedorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Editar Proveedor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editarProveedorForm" class="needs-validation" novalidate>
                <input type="hidden" name="id_nit_original" id="editIdNitOriginal">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editIdNit" class="form-label">
                                <i class="fas fa-id-badge me-1"></i>NIT/Identificación
                            </label>
                            <input type="text" name="id_nit" id="editIdNit" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editRazonSocial" class="form-label">
                                <i class="fas fa-building me-1"></i>Razón Social
                            </label>
                            <input type="text" name="razon_social" id="editRazonSocial" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editContacto" class="form-label">
                                <i class="fas fa-user me-1"></i>Persona de Contacto
                            </label>
                            <input type="text" name="contacto" id="editContacto" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editTelefono" class="form-label">
                                <i class="fas fa-phone me-1"></i>Teléfono
                            </label>
                            <input type="tel" name="telefono" id="editTelefono" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="editCorreo" class="form-label">
                                <i class="fas fa-envelope me-1"></i>Correo Electrónico
                            </label>
                            <input type="email" name="correo" id="editCorreo" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="editEstado" class="form-label">
                                <i class="fas fa-toggle-on me-1"></i>Estado
                            </label>
                            <select name="estado" id="editEstado" class="form-select" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="editDireccion" class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i>Dirección
                            </label>
                            <textarea name="direccion" id="editDireccion" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label for="editDetalles" class="form-label">
                                <i class="fas fa-info-circle me-1"></i>Detalles Adicionales
                            </label>
                            <textarea name="detalles" id="editDetalles" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="detallesProveedorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>Detalles del Proveedor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <h6><i class="fas fa-building me-2"></i>Información de la Empresa</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>NIT:</strong></td>
                                <td id="detailNit"></td>
                            </tr>
                            <tr>
                                <td><strong>Razón Social:</strong></td>
                                <td id="detailRazonSocial"></td>
                            </tr>
                            <tr>
                                <td><strong>Dirección:</strong></td>
                                <td id="detailDireccion"></td>
                            </tr>
                            <tr>
                                <td><strong>Estado:</strong></td>
                                <td id="detailEstado"></td>
                            </tr>
                        </table>

                        <h6><i class="fas fa-user me-2"></i>Información de Contacto</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Contacto:</strong></td>
                                <td id="detailContacto"></td>
                            </tr>
                            <tr>
                                <td><strong>Teléfono:</strong></td>
                                <td id="detailTelefono"></td>
                            </tr>
                            <tr>
                                <td><strong>Correo:</strong></td>
                                <td id="detailCorreo"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <h6><i class="fas fa-chart-bar me-2"></i>Estadísticas</h6>
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h4 class="text-primary" id="detailProductos">0</h4>
                                <small>Productos</small>
                            </div>
                        </div>
                        <div class="card bg-light mt-2">
                            <div class="card-body text-center">
                                <h4 class="text-secondary" id="detailReportes">0</h4>
                                <small>Reportes</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3" id="detailDetallesRow" style="display: none;">
                    <div class="col-12">
                        <h6><i class="fas fa-info-circle me-2"></i>Detalles Adicionales</h6>
                        <div class="card bg-light">
                            <div class="card-body">
                                <p id="detailDetallesText" class="mb-0"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Generar la página completa usando el sistema responsivo
renderResponsivePage($pageConfig);
?>

<script>
// Funciones específicas del módulo de proveedores
function verDetalles(proveedor) {
    // Llenar información básica
    document.getElementById('detailNit').textContent = proveedor.id_nit;
    document.getElementById('detailRazonSocial').textContent = proveedor.razon_social;
    document.getElementById('detailDireccion').textContent = proveedor.direccion || 'No especificada';
    document.getElementById('detailContacto').textContent = proveedor.contacto;
    document.getElementById('detailTelefono').textContent = proveedor.telefono || 'No especificado';
    document.getElementById('detailCorreo').textContent = proveedor.correo || 'No especificado';
    document.getElementById('detailProductos').textContent = proveedor.total_productos;
    document.getElementById('detailReportes').textContent = proveedor.total_reportes;
    
    // Estado con badge
    const estadoBadge = `<span class="badge bg-${proveedor.estado === 'activo' ? 'success' : 'warning'}">
        ${proveedor.estado.charAt(0).toUpperCase() + proveedor.estado.slice(1)}
    </span>`;
    document.getElementById('detailEstado').innerHTML = estadoBadge;
    
    // Detalles adicionales
    const detallesRow = document.getElementById('detailDetallesRow');
    const detallesText = document.getElementById('detailDetallesText');
    
    if (proveedor.detalles && proveedor.detalles.trim() !== '') {
        detallesText.textContent = proveedor.detalles;
        detallesRow.style.display = 'block';
    } else {
        detallesRow.style.display = 'none';
    }
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('detallesProveedorModal'));
    modal.show();
}

function editarProveedor(proveedor) {
    // Llenar el formulario de edición
    document.getElementById('editIdNitOriginal').value = proveedor.id_nit;
    document.getElementById('editIdNit').value = proveedor.id_nit;
    document.getElementById('editRazonSocial').value = proveedor.razon_social;
    document.getElementById('editContacto').value = proveedor.contacto;
    document.getElementById('editTelefono').value = proveedor.telefono || '';
    document.getElementById('editCorreo').value = proveedor.correo || '';
    document.getElementById('editEstado').value = proveedor.estado;
    document.getElementById('editDireccion').value = proveedor.direccion || '';
    document.getElementById('editDetalles').value = proveedor.detalles || '';
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('editarProveedorModal'));
    modal.show();
}

function confirmarEliminar(id_nit, razonSocial) {
    if (confirm(`¿Está seguro de que desea eliminar al proveedor "${razonSocial}"?\n\nEsta acción no se puede deshacer.`)) {
        window.location.href = `proveedores.php?eliminar=${id_nit}`;
    }
}

// Event listeners para formularios
document.addEventListener('DOMContentLoaded', function() {
    // Formulario agregar proveedor
    const agregarForm = document.getElementById('agregarProveedorForm');
    agregarForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        if (this.checkValidity()) {
            const formData = new FormData(this);
            enviarFormularioProveedor(formData, 'agregar');
        }
        this.classList.add('was-validated');
    });

    // Formulario editar proveedor  
    const editarForm = document.getElementById('editarProveedorForm');
    editarForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        if (this.checkValidity()) {
            const formData = new FormData(this);
            enviarFormularioProveedor(formData, 'editar');
        }
        this.classList.add('was-validated');
    });

    // Validaciones en tiempo real
    const nitInput = document.getElementById('id_nit');
    if (nitInput) {
        nitInput.addEventListener('input', function() {
            // Permitir solo números y guiones
            this.value = this.value.replace(/[^0-9-]/g, '');
        });
    }

    const telefonoInputs = document.querySelectorAll('input[type="tel"]');
    telefonoInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Permitir solo números, espacios, paréntesis y guiones
            this.value = this.value.replace(/[^0-9\s\-\(\)]/g, '');
        });
    });
});

async function enviarFormularioProveedor(formData, action) {
    try {
        formData.append('action', action);
        
        const response = await fetch('proveedores.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            if (typeof ResponsiveUtils !== 'undefined') {
                ResponsiveUtils.showNotification(`Proveedor ${action === 'agregar' ? 'agregado' : 'actualizado'} exitosamente`, 'success');
            } else if (typeof showToast === 'function') {
                showToast(`Proveedor ${action === 'agregar' ? 'agregado' : 'actualizado'} exitosamente`, 'success');
            }
            setTimeout(() => location.reload(), 1500);
        } else {
            throw new Error('Error en la respuesta del servidor');
        }
    } catch (error) {
        if (typeof ResponsiveUtils !== 'undefined') {
            ResponsiveUtils.showNotification('Error al procesar la solicitud: ' + error.message, 'error');
        } else if (typeof showToast === 'function') {
            showToast('Error al procesar la solicitud: ' + error.message, 'error');
        }
    }
}
    </script>
    <script src="public/js/notifications.js"></script>
