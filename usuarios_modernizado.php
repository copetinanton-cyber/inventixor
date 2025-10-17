<?php
require_once 'config/db.php';
require_once 'app/controllers/AuthController.php';

$authController = new AuthController($pdo);
$authController->verificarSesion();

// Verificar permisos
$usuario = $_SESSION['user'];
$es_admin = $usuario['rol'] === 'admin';
$es_coordinador = $usuario['rol'] === 'coordinador' || $es_admin;

// Solo coordinadores y admins pueden acceder
if ($usuario['rol'] === 'auxiliar') {
    header('Location: dashboard.php');
    exit();
}

// Manejar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'crear':
                if (!$es_coordinador) {
                    throw new Exception('No tiene permisos para crear usuarios');
                }

                $num_doc = $_POST['num_doc'] ?? '';
                $tipo_documento = $_POST['tipo_documento'] ?? '';
                $nombres = $_POST['nombres'] ?? '';
                $apellidos = $_POST['apellidos'] ?? '';
                $telefono = $_POST['telefono'] ?? '';
                $correo = $_POST['correo'] ?? '';
                $cargo = $_POST['cargo'] ?? '';
                $rol = $_POST['rol'] ?? '';
                $contrasena = password_hash($_POST['contrasena'] ?? '', PASSWORD_DEFAULT);

                if ($rol === 'admin' && !$es_admin) {
                    throw new Exception('Solo los administradores pueden crear otros administradores');
                }

                $check_stmt = $pdo->prepare("SELECT num_doc FROM usuarios WHERE num_doc = ?");
                $check_stmt->execute([$num_doc]);
                if ($check_stmt->fetch(PDO::FETCH_ASSOC)) {
                    throw new Exception('Ya existe un usuario con este número de documento');
                }

                $stmt = $pdo->prepare("INSERT INTO usuarios (num_doc, tipo_documento, nombres, apellidos, telefono, correo, cargo, rol, contrasena) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$num_doc, $tipo_documento, $nombres, $apellidos, $telefono, $correo, $cargo, $rol, $contrasena]);

                echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente']);
                break;

            case 'editar':
                if (!$es_coordinador) {
                    throw new Exception('No tiene permisos para editar usuarios');
                }

                $num_doc = $_POST['num_doc'] ?? '';
                $nombres = $_POST['nombres'] ?? '';
                $apellidos = $_POST['apellidos'] ?? '';
                $telefono = $_POST['telefono'] ?? '';
                $correo = $_POST['correo'] ?? '';
                $cargo = $_POST['cargo'] ?? '';
                $rol = $_POST['rol'] ?? '';

                if ($rol === 'admin' && !$es_admin) {
                    throw new Exception('Solo los administradores pueden asignar el rol de administrador');
                }

                $sql = "UPDATE usuarios SET nombres = ?, apellidos = ?, telefono = ?, correo = ?, cargo = ?, rol = ?";
                $params = [$nombres, $apellidos, $telefono, $correo, $cargo, $rol];

                if (!empty($_POST['nueva_contrasena'])) {
                    $sql .= ", contrasena = ?";
                    $params[] = password_hash($_POST['nueva_contrasena'], PASSWORD_DEFAULT);
                }

                $sql .= " WHERE num_doc = ?";
                $params[] = $num_doc;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
                break;

            case 'eliminar':
                if (!$es_admin) {
                    throw new Exception('Solo los administradores pueden eliminar usuarios');
                }

                $num_doc = $_POST['num_doc'] ?? '';
                if ($num_doc == $usuario['num_doc']) {
                    throw new Exception('No puede eliminarse a sí mismo');
                }

                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE num_doc = ?");
                $stmt->execute([$num_doc]);

                echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
                break;

            default:
                throw new Exception('Acción no válida');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Obtener filtros
$filtro_nombre = $_GET['filtro_nombre'] ?? '';
$filtro_rol = $_GET['filtro_rol'] ?? '';
$filtro_cargo = $_GET['filtro_cargo'] ?? '';

// Configuración de paginación
$registros_por_pagina = 20;
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $registros_por_pagina;

// Construir consulta optimizada con JOINs
$sql_base = "FROM usuarios u 
             LEFT JOIN (
                 SELECT num_doc as usuario_doc, COUNT(*) as productos_count 
                 FROM productos 
                 GROUP BY num_doc
             ) p ON u.num_doc = p.usuario_doc
             LEFT JOIN (
                 SELECT usuario as usuario_id, COUNT(*) as reportes_count 
                 FROM reportes 
                 GROUP BY usuario
             ) r ON u.num_doc = r.usuario_id
             WHERE 1=1";

$params = [];

if (!empty($filtro_nombre)) {
    $sql_base .= " AND (u.nombres LIKE ? OR u.apellidos LIKE ?)";
    $params[] = "%$filtro_nombre%";
    $params[] = "%$filtro_nombre%";
}

if (!empty($filtro_rol)) {
    $sql_base .= " AND u.rol = ?";
    $params[] = $filtro_rol;
}

if (!empty($filtro_cargo)) {
    $sql_base .= " AND u.cargo LIKE ?";
    $params[] = "%$filtro_cargo%";
}

// Contar total de registros
$sql_count = "SELECT COUNT(*) " . $sql_base;
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_registros = $stmt_count->fetchColumn();
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta principal con límite
$sql = "SELECT u.*, 
        COALESCE(p.productos_count, 0) as productos_asignados,
        COALESCE(r.reportes_count, 0) as reportes_creados 
        " . $sql_base . " 
        ORDER BY u.nombres, u.apellidos 
        LIMIT $registros_por_pagina OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Obtener estadísticas generales
$stats_sql = "SELECT 
    COUNT(*) as total_usuarios,
    SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) as total_admins,
    SUM(CASE WHEN rol = 'coordinador' THEN 1 ELSE 0 END) as total_coordinadores,
    SUM(CASE WHEN rol = 'auxiliar' THEN 1 ELSE 0 END) as total_auxiliares
    FROM usuarios";
$stats_stmt = $pdo->query($stats_sql);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - InventiXor</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --info-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            --card-shadow: 0 10px 25px rgba(0,0,0,0.1);
            --hover-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: var(--card-shadow);
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            position: sticky;
            top: 2rem;
            height: fit-content;
        }

        .sidebar h4 {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: bold;
            margin-bottom: 1.5rem;
        }

        .nav-link {
            border-radius: 10px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            color: #666;
            font-weight: 500;
        }

        .nav-link:hover, .nav-link.active {
            background: var(--primary-gradient);
            color: white;
            transform: translateX(5px);
        }

        .main-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            margin: 2rem 0;
            overflow: hidden;
        }

        .header-section {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header-section h2 {
            margin: 0;
            font-weight: bold;
        }

        .stats-section {
            padding: 2rem;
            background: rgba(248, 249, 250, 0.8);
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: none;
            margin-bottom: 1rem;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .stat-card .icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .stat-card.admin .icon { color: #dc3545; }
        .stat-card.coordinador .icon { color: #fd7e14; }
        .stat-card.auxiliar .icon { color: #198754; }
        .stat-card.total .icon { color: #0d6efd; }

        .filters-section {
            padding: 2rem;
            background: white;
        }

        .table-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin: 2rem;
        }

        .btn-action {
            border-radius: 8px;
            padding: 0.5rem 0.8rem;
            font-size: 0.875rem;
            border: 2px solid;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-create {
            background: var(--success-gradient);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-create:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
            color: white;
        }

        .badge-rol {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .badge-admin {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .badge-coordinador {
            background: linear-gradient(135deg, #fd7e14, #e55a00);
            color: white;
        }

        .badge-auxiliar {
            background: linear-gradient(135deg, #198754, #146c43);
            color: white;
        }

        .table th {
            background: rgba(102, 126, 234, 0.1);
            border: none;
            font-weight: 600;
            color: #495057;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: scale(1.02);
        }

        .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .modal-header {
            border-bottom: 1px solid rgba(0,0,0,0.1);
            border-radius: 20px 20px 0 0;
        }

        .modal-footer {
            border-top: 1px solid rgba(0,0,0,0.1);
            border-radius: 0 0 20px 20px;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }

        .animate-fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }

        /* Loading Spinner */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Table Skeleton Loading */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .skeleton-row {
            height: 60px;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: static;
                margin: 1rem;
            }
            
            .main-content {
                margin: 1rem;
            }
            
            .header-section {
                padding: 1.5rem;
            }
            
            .stats-section {
                padding: 1rem;
            }
            
            .table-card {
                margin: 1rem;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="sidebar">
                    <h4><i class="fas fa-cogs me-2"></i>Panel de Control</h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="usuarios.php">
                                <i class="fas fa-users me-2"></i>Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="productos.php">
                                <i class="fas fa-box me-2"></i>Productos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="salidas.php">
                                <i class="fas fa-shipping-fast me-2"></i>Salidas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reportes.php">
                                <i class="fas fa-chart-bar me-2"></i>Reportes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categorias.php">
                                <i class="fas fa-tags me-2"></i>Categorías
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="small text-muted mb-2">Sesión Actual</p>
                        <p class="fw-bold"><?php echo htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']); ?></p>
                        <span class="badge badge-<?php echo $usuario['rol']; ?> mb-3"><?php echo ucfirst($usuario['rol']); ?></span>
                        <br>
                        <a href="logout.php" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="main-content">
                    <!-- Header Section -->
                    <div class="header-section animate-fade-in">
                        <h2><i class="fas fa-users me-3"></i>Gestión de Usuarios</h2>
                        <p class="mb-0">Administra usuarios, roles y permisos del sistema</p>
                    </div>

                    <!-- Stats Section -->
                    <div class="stats-section animate-fade-in">
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-card total">
                                    <div class="icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <h3><?php echo $stats['total_usuarios']; ?></h3>
                                    <p class="text-muted mb-0">Total Usuarios</p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-card admin">
                                    <div class="icon">
                                        <i class="fas fa-user-shield"></i>
                                    </div>
                                    <h3><?php echo $stats['total_admins']; ?></h3>
                                    <p class="text-muted mb-0">Administradores</p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-card coordinador">
                                    <div class="icon">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <h3><?php echo $stats['total_coordinadores']; ?></h3>
                                    <p class="text-muted mb-0">Coordinadores</p>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="stat-card auxiliar">
                                    <div class="icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <h3><?php echo $stats['total_auxiliares']; ?></h3>
                                    <p class="text-muted mb-0">Auxiliares</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters Section -->
                    <div class="filters-section animate-fade-in">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5><i class="fas fa-filter me-2"></i>Filtros y Acciones</h5>
                            <div class="btn-toolbar" role="toolbar">
                                <?php if ($es_coordinador): ?>
                                <button type="button" class="btn btn-create me-2" onclick="crearUsuario()">
                                    <i class="fas fa-user-plus me-2"></i>Nuevo Usuario
                                </button>
                                <?php endif; ?>
                                <a href="reportes.php" class="btn btn-info">
                                    <i class="fas fa-chart-bar me-2"></i>Ver Reportes
                                </a>
                            </div>
                        </div>
                        
                        <form method="GET" class="row align-items-end">
                            <div class="col-md-4">
                                <label for="filtro_nombre" class="form-label">
                                    <i class="fas fa-search me-1"></i>Buscar por Nombre
                                </label>
                                <input type="text" name="filtro_nombre" id="filtro_nombre" class="form-control" 
                                       placeholder="Nombre o apellido..." value="<?php echo htmlspecialchars($filtro_nombre); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="filtro_rol" class="form-label">
                                    <i class="fas fa-user-tag me-1"></i>Filtrar por Rol
                                </label>
                                <select name="filtro_rol" id="filtro_rol" class="form-select">
                                    <option value="">Todos los roles</option>
                                    <option value="admin" <?php echo $filtro_rol === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                                    <option value="coordinador" <?php echo $filtro_rol === 'coordinador' ? 'selected' : ''; ?>>Coordinador</option>
                                    <option value="auxiliar" <?php echo $filtro_rol === 'auxiliar' ? 'selected' : ''; ?>>Auxiliar</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filtro_cargo" class="form-label">
                                    <i class="fas fa-briefcase me-1"></i>Filtrar por Cargo
                                </label>
                                <input type="text" name="filtro_cargo" id="filtro_cargo" class="form-control" 
                                       placeholder="Cargo..." value="<?php echo htmlspecialchars($filtro_cargo); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tabla de Usuarios Section -->
                    <div class="table-card animate-fade-in">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5><i class="fas fa-table me-2"></i>Lista de Usuarios</h5>
                                <small class="text-muted">
                                    Mostrando <?php echo min($stmt->rowCount(), $registros_por_pagina); ?> de <?php echo $total_registros; ?> usuarios
                                    (Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?>)
                                </small>
                            </div>
                            <div>
                                <span class="badge bg-info fs-6"><?php echo $total_registros; ?> usuarios total</span>
                                <span class="badge bg-secondary fs-6 ms-2">Página <?php echo $pagina; ?>/<?php echo $total_paginas; ?></span>
                            </div>
                        </div>
                        
                        <?php if ($stmt->rowCount() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-id-card me-1"></i>Documento</th>
                                        <th><i class="fas fa-user me-1"></i>Nombre Completo</th>
                                        <th><i class="fas fa-envelope me-1"></i>Contacto</th>
                                        <th><i class="fas fa-briefcase me-1"></i>Cargo</th>
                                        <th><i class="fas fa-user-tag me-1"></i>Rol</th>
                                        <th><i class="fas fa-chart-bar me-1"></i>Estadísticas</th>
                                        <th><i class="fas fa-cogs me-1"></i>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($usuario_row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo number_format($usuario_row['num_doc']); ?></strong>
                                            <br><small class="text-muted">
                                                <?php 
                                                $tipos_doc = [1 => 'CC', 2 => 'CE', 3 => 'PP', 4 => 'TI'];
                                                echo $tipos_doc[$usuario_row['tipo_documento']] ?? 'N/A';
                                                ?>
                                            </small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($usuario_row['nombres'] . ' ' . $usuario_row['apellidos']); ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <i class="fas fa-phone me-1"></i>
                                                <?php echo htmlspecialchars($usuario_row['telefono']); ?>
                                            </div>
                                            <div>
                                                <i class="fas fa-envelope me-1"></i>
                                                <small><?php echo htmlspecialchars($usuario_row['correo']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($usuario_row['cargo']); ?></td>
                                        <td>
                                            <span class="badge badge-rol badge-<?php echo $usuario_row['rol']; ?>">
                                                <?php echo ucfirst($usuario_row['rol']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-center">
                                                <small class="d-block">
                                                    <i class="fas fa-box me-1"></i>
                                                    <?php echo $usuario_row['productos_asignados']; ?> productos
                                                </small>
                                                <small class="d-block">
                                                    <i class="fas fa-file-alt me-1"></i>
                                                    <?php echo $usuario_row['reportes_creados']; ?> reportes
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <!-- Botón Ver Detalles - Todos pueden ver -->
                                                <button type="button" class="btn btn-outline-info btn-action" 
                                                        onclick="verDetallesUsuario(<?php echo $usuario_row['num_doc']; ?>, '<?php echo addslashes($usuario_row['nombres'] . ' ' . $usuario_row['apellidos']); ?>', '<?php echo addslashes($usuario_row['correo']); ?>', '<?php echo addslashes($usuario_row['telefono']); ?>', '<?php echo addslashes($usuario_row['cargo']); ?>', '<?php echo $usuario_row['rol']; ?>', <?php echo $usuario_row['productos_asignados']; ?>, <?php echo $usuario_row['reportes_creados']; ?>)"
                                                        title="Ver Detalles">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($es_coordinador): ?>
                                                <!-- Botón Editar - Solo coordinadores y admins -->
                                                <button type="button" class="btn btn-outline-warning btn-action" 
                                                        onclick="editarUsuario(<?php echo $usuario_row['num_doc']; ?>, '<?php echo addslashes($usuario_row['nombres']); ?>', '<?php echo addslashes($usuario_row['apellidos']); ?>', '<?php echo addslashes($usuario_row['telefono']); ?>', '<?php echo addslashes($usuario_row['correo']); ?>', '<?php echo addslashes($usuario_row['cargo']); ?>', '<?php echo $usuario_row['rol']; ?>')"
                                                        title="Editar Usuario">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($es_admin && $usuario_row['num_doc'] != $_SESSION['user']['num_doc']): ?>
                                                <!-- Botón Eliminar - Solo admin y no puede eliminarse a sí mismo -->
                                                <button type="button" class="btn btn-outline-danger btn-action"
                                                        onclick="confirmarEliminar(<?php echo $usuario_row['num_doc']; ?>, '<?php echo addslashes($usuario_row['nombres'] . ' ' . $usuario_row['apellidos']); ?>')"
                                                        title="Eliminar Usuario">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginación -->
                        <?php if ($total_paginas > 1): ?>
                        <nav aria-label="Paginación de usuarios" class="mt-4">
                            <ul class="pagination pagination-lg justify-content-center">
                                <!-- Botón Anterior -->
                                <li class="page-item <?php echo ($pagina <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>&filtro_nombre=<?php echo urlencode($filtro_nombre); ?>&filtro_rol=<?php echo urlencode($filtro_rol); ?>&filtro_cargo=<?php echo urlencode($filtro_cargo); ?>">
                                        <i class="fas fa-chevron-left"></i> Anterior
                                    </a>
                                </li>
                                
                                <!-- Números de página -->
                                <?php 
                                $rango_inicio = max(1, $pagina - 2);
                                $rango_fin = min($total_paginas, $pagina + 2);
                                
                                if ($rango_inicio > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?pagina=1&filtro_nombre=<?php echo urlencode($filtro_nombre); ?>&filtro_rol=<?php echo urlencode($filtro_rol); ?>&filtro_cargo=<?php echo urlencode($filtro_cargo); ?>">1</a>
                                    </li>
                                    <?php if ($rango_inicio > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $rango_inicio; $i <= $rango_fin; $i++): ?>
                                    <li class="page-item <?php echo ($i == $pagina) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?pagina=<?php echo $i; ?>&filtro_nombre=<?php echo urlencode($filtro_nombre); ?>&filtro_rol=<?php echo urlencode($filtro_rol); ?>&filtro_cargo=<?php echo urlencode($filtro_cargo); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($rango_fin < $total_paginas): ?>
                                    <?php if ($rango_fin < $total_paginas - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?pagina=<?php echo $total_paginas; ?>&filtro_nombre=<?php echo urlencode($filtro_nombre); ?>&filtro_rol=<?php echo urlencode($filtro_rol); ?>&filtro_cargo=<?php echo urlencode($filtro_cargo); ?>"><?php echo $total_paginas; ?></a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- Botón Siguiente -->
                                <li class="page-item <?php echo ($pagina >= $total_paginas) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>&filtro_nombre=<?php echo urlencode($filtro_nombre); ?>&filtro_rol=<?php echo urlencode($filtro_rol); ?>&filtro_cargo=<?php echo urlencode($filtro_cargo); ?>">
                                        Siguiente <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                        
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No se encontraron usuarios</h5>
                            <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales -->
    
    <!-- Modal Crear Usuario -->
    <div class="modal fade" id="crearUsuarioModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #27ae60, #2ecc71); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="crearUsuarioForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="num_doc" class="form-label">
                                        <i class="fas fa-id-card me-1"></i>Número de Documento
                                    </label>
                                    <input type="number" name="num_doc" id="num_doc" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tipo_documento" class="form-label">
                                        <i class="fas fa-file-alt me-1"></i>Tipo de Documento
                                    </label>
                                    <select name="tipo_documento" id="tipo_documento" class="form-select" required>
                                        <option value="1">Cédula de Ciudadanía</option>
                                        <option value="2">Cédula de Extranjería</option>
                                        <option value="3">Pasaporte</option>
                                        <option value="4">Tarjeta de Identidad</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombres" class="form-label">
                                        <i class="fas fa-user me-1"></i>Nombres
                                    </label>
                                    <input type="text" name="nombres" id="nombres" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="apellidos" class="form-label">
                                        <i class="fas fa-user me-1"></i>Apellidos
                                    </label>
                                    <input type="text" name="apellidos" id="apellidos" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telefono" class="form-label">
                                        <i class="fas fa-phone me-1"></i>Teléfono
                                    </label>
                                    <input type="tel" name="telefono" id="telefono" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="correo" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Correo Electrónico
                                    </label>
                                    <input type="email" name="correo" id="correo" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cargo" class="form-label">
                                        <i class="fas fa-briefcase me-1"></i>Cargo
                                    </label>
                                    <input type="text" name="cargo" id="cargo" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="rol" class="form-label">
                                        <i class="fas fa-user-tag me-1"></i>Rol
                                    </label>
                                    <select name="rol" id="rol" class="form-select" required>
                                        <option value="auxiliar">Auxiliar</option>
                                        <option value="coordinador">Coordinador</option>
                                        <?php if ($es_admin): ?>
                                        <option value="admin">Administrador</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="contrasena" class="form-label">
                                <i class="fas fa-lock me-1"></i>Contraseña
                            </label>
                            <input type="password" name="contrasena" id="contrasena" class="form-control" required>
                            <div class="form-text">La contraseña debe tener al menos 6 caracteres.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ver Detalles Usuario -->
    <div class="modal fade" id="detallesUsuarioModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #17a2b8, #138496); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>Detalles del Usuario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6><i class="fas fa-user me-2"></i>Información Personal</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Nombre Completo:</strong></td>
                                    <td id="detailNombreCompleto"></td>
                                </tr>
                                <tr>
                                    <td><strong>Documento:</strong></td>
                                    <td id="detailDocumento"></td>
                                </tr>
                                <tr>
                                    <td><strong>Teléfono:</strong></td>
                                    <td id="detailTelefono"></td>
                                </tr>
                                <tr>
                                    <td><strong>Correo:</strong></td>
                                    <td id="detailCorreo"></td>
                                </tr>
                                <tr>
                                    <td><strong>Cargo:</strong></td>
                                    <td id="detailCargo"></td>
                                </tr>
                                <tr>
                                    <td><strong>Rol:</strong></td>
                                    <td><span id="detailRol" class="badge"></span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="fas fa-chart-bar me-2"></i>Estadísticas</h6>
                            <div class="text-center">
                                <div class="mb-3">
                                    <h4 id="detailProductos" class="text-primary"></h4>
                                    <small class="text-muted">Productos asignados</small>
                                </div>
                                <div class="mb-3">
                                    <h4 id="detailReportes" class="text-success"></h4>
                                    <small class="text-muted">Reportes creados</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div class="modal fade" id="editarUsuarioModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #ffc107, #ff8f00); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Usuario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="editarUsuarioForm">
                    <div class="modal-body">
                        <input type="hidden" name="num_doc" id="editNumDoc">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editNombres" class="form-label">
                                        <i class="fas fa-user me-1"></i>Nombres
                                    </label>
                                    <input type="text" name="nombres" id="editNombres" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editApellidos" class="form-label">
                                        <i class="fas fa-user me-1"></i>Apellidos
                                    </label>
                                    <input type="text" name="apellidos" id="editApellidos" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editTelefono" class="form-label">
                                        <i class="fas fa-phone me-1"></i>Teléfono
                                    </label>
                                    <input type="tel" name="telefono" id="editTelefono" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editCorreo" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>Correo Electrónico
                                    </label>
                                    <input type="email" name="correo" id="editCorreo" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editCargo" class="form-label">
                                        <i class="fas fa-briefcase me-1"></i>Cargo
                                    </label>
                                    <input type="text" name="cargo" id="editCargo" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editRol" class="form-label">
                                        <i class="fas fa-user-tag me-1"></i>Rol
                                    </label>
                                    <select name="rol" id="editRol" class="form-select" required>
                                        <option value="auxiliar">Auxiliar</option>
                                        <option value="coordinador">Coordinador</option>
                                        <?php if ($es_admin): ?>
                                        <option value="admin">Administrador</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editNuevaContrasena" class="form-label">
                                <i class="fas fa-lock me-1"></i>Nueva Contraseña (opcional)
                            </label>
                            <input type="password" name="nueva_contrasena" id="editNuevaContrasena" class="form-control">
                            <div class="form-text">Deja en blanco si no quieres cambiar la contraseña.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Usuario -->
    <div class="modal fade" id="eliminarUsuarioModal" tabindex="-1">
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
                        <i class="fas fa-user-slash fa-3x text-danger mb-3"></i>
                        <h5>¿Estás seguro de eliminar este usuario?</h5>
                        <p class="text-muted">Esta acción no se puede deshacer.</p>
                        <div class="alert alert-warning">
                            <strong>Usuario:</strong> <span id="eliminarUsuarioNombre"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="eliminarUsuarioForm" style="display: inline;">
                        <input type="hidden" name="num_doc" id="eliminarNumDoc">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Eliminar Usuario
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    
    <!-- Sistema de Notificaciones -->
    <script src="public/js/notifications.js"></script>
    
    <script>
        // Función para ver detalles del usuario
        function verDetallesUsuario(numDoc, nombreCompleto, correo, telefono, cargo, rol, productos, reportes) {
            document.getElementById('detailNombreCompleto').textContent = nombreCompleto;
            document.getElementById('detailDocumento').textContent = numDoc;
            document.getElementById('detailTelefono').textContent = telefono;
            document.getElementById('detailCorreo').textContent = correo;
            document.getElementById('detailCargo').textContent = cargo;
            document.getElementById('detailProductos').textContent = productos;
            document.getElementById('detailReportes').textContent = reportes;
            
            // Configurar badge del rol
            const rolBadge = document.getElementById('detailRol');
            rolBadge.textContent = rol.charAt(0).toUpperCase() + rol.slice(1);
            rolBadge.className = `badge badge-${rol}`;
            
            new bootstrap.Modal(document.getElementById('detallesUsuarioModal')).show();
        }

        // Función para editar usuario
        function editarUsuario(numDoc, nombres, apellidos, telefono, correo, cargo, rol) {
            document.getElementById('editNumDoc').value = numDoc;
            document.getElementById('editNombres').value = nombres;
            document.getElementById('editApellidos').value = apellidos;
            document.getElementById('editTelefono').value = telefono;
            document.getElementById('editCorreo').value = correo;
            document.getElementById('editCargo').value = cargo;
            document.getElementById('editRol').value = rol;
            document.getElementById('editNuevaContrasena').value = '';
            
            new bootstrap.Modal(document.getElementById('editarUsuarioModal')).show();
        }

        // Función para confirmar eliminación
        function confirmarEliminar(numDoc, nombreCompleto) {
            document.getElementById('eliminarNumDoc').value = numDoc;
            document.getElementById('eliminarUsuarioNombre').textContent = nombreCompleto;
            
            new bootstrap.Modal(document.getElementById('eliminarUsuarioModal')).show();
        }

        // Función para crear usuario
        function crearUsuario() {
            new bootstrap.Modal(document.getElementById('crearUsuarioModal')).show();
        }

        // Manejo del formulario de crear usuario
        document.getElementById('crearUsuarioForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'crear');
            
            fetch('usuarios_modernizado.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('crearUsuarioModal')).hide();
                    
                    // Obtener datos del formulario para la notificación
                    const formData = new FormData(document.getElementById('crearUsuarioForm'));
                    const userData = {
                        num_doc: formData.get('num_doc'),
                        nombres: formData.get('nombres') + ' ' + formData.get('apellidos')
                    };
                    
                    showAlert('Usuario creado exitosamente en el sistema', 'success', userData, 'create');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showAlert(data.message || 'Error al crear usuario', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error de conexión al crear usuario', 'danger');
            });
        });

        // Manejo del formulario de editar usuario
        document.getElementById('editarUsuarioForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'editar');
            
            fetch('usuarios_modernizado.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editarUsuarioModal')).hide();
                    
                    // Obtener datos del formulario para la notificación
                    const formData = new FormData(document.getElementById('editarUsuarioForm'));
                    const userData = {
                        num_doc: formData.get('num_doc'),
                        nombres: formData.get('nombres') + ' ' + formData.get('apellidos')
                    };
                    
                    showAlert('Información del usuario actualizada correctamente', 'success', userData, 'update');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showAlert(data.message || 'Error al actualizar usuario', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error de conexión al actualizar usuario', 'danger');
            });
        });

        // Manejo del formulario de eliminar usuario
        document.getElementById('eliminarUsuarioForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'eliminar');
            
            fetch('usuarios_modernizado.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('eliminarUsuarioModal')).hide();
                    
                    // Obtener el nombre del usuario desde el modal
                    const nombreUsuario = document.getElementById('eliminarUsuarioNombre').textContent;
                    const numDoc = document.getElementById('eliminarNumDoc').value;
                    const userData = {
                        num_doc: numDoc,
                        nombres: nombreUsuario
                    };
                    
                    showAlert('Usuario eliminado permanentemente del sistema', 'success', userData, 'delete');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showAlert(data.message || 'Error al eliminar usuario', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error de conexión al eliminar usuario', 'danger');
            });
        });

        // Función para mostrar alertas con notificaciones emergentes
        function showAlert(message, type, userData = null, action = null) {
            // Sistema de notificaciones emergentes
            const notificationSystem = new NotificationSystem();
            
            // Mapear tipos de Bootstrap a tipos de notificación
            const typeMapping = {
                'success': 'success',
                'danger': 'error',
                'warning': 'warning',
                'info': 'info'
            };
            
            const notificationType = typeMapping[type] || 'info';
            
            // Si es una operación de usuario específica, usar el método específico
            if (action && userData) {
                notificationSystem.showUserChange(action, message, notificationType, userData);
            } else {
                // Usar notificación genérica
                notificationSystem.show(
                    type === 'success' ? 'Operación Exitosa' : 
                    type === 'danger' ? 'Error de Sistema' : 
                    type === 'warning' ? 'Advertencia' : 'Información',
                    message,
                    notificationType,
                    5000
                );
            }
        }

        // Manejo de filtros en tiempo real
        function filterTable() {
            const searchNombre = document.getElementById('filtro_nombre').value.toLowerCase();
            const filterRol = document.getElementById('filtro_rol').value;
            const filterCargo = document.getElementById('filtro_cargo').value.toLowerCase();
            
            const rows = document.querySelectorAll('tbody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const nombreCompleto = row.cells[1].textContent.toLowerCase();
                const cargo = row.cells[3].textContent.toLowerCase();
                const rolBadge = row.querySelector('.badge-rol');
                const rol = rolBadge ? rolBadge.textContent.toLowerCase() : '';
                
                const matchesNombre = searchNombre === '' || nombreCompleto.includes(searchNombre);
                const matchesRol = filterRol === '' || rol === filterRol.toLowerCase();
                const matchesCargo = filterCargo === '' || cargo.includes(filterCargo);
                
                if (matchesNombre && matchesRol && matchesCargo) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Actualizar contador
            const badge = document.querySelector('.badge.bg-info');
            if (badge) {
                badge.textContent = `${visibleCount} usuarios encontrados`;
            }
        }

        // Eventos de filtros
        document.getElementById('filtro_nombre')?.addEventListener('input', filterTable);
        document.getElementById('filtro_rol')?.addEventListener('change', filterTable);
        document.getElementById('filtro_cargo')?.addEventListener('input', filterTable);

        // Sistema de Loading
        function showLoading() {
            const loadingOverlay = document.createElement('div');
            loadingOverlay.className = 'loading-overlay';
            loadingOverlay.id = 'loadingOverlay';
            loadingOverlay.innerHTML = '<div class="loading-spinner"></div>';
            document.body.appendChild(loadingOverlay);
        }

        function hideLoading() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.remove();
            }
        }

        // Mostrar skeleton para la tabla mientras carga
        function showTableSkeleton() {
            const tbody = document.querySelector('tbody');
            if (tbody) {
                tbody.innerHTML = '';
                for (let i = 0; i < 5; i++) {
                    const skeletonRow = document.createElement('tr');
                    skeletonRow.innerHTML = `
                        <td><div class="skeleton skeleton-row"></div></td>
                        <td><div class="skeleton skeleton-row"></div></td>
                        <td><div class="skeleton skeleton-row"></div></td>
                        <td><div class="skeleton skeleton-row"></div></td>
                        <td><div class="skeleton skeleton-row"></div></td>
                        <td><div class="skeleton skeleton-row"></div></td>
                    `;
                    tbody.appendChild(skeletonRow);
                }
            }
        }

        // Interceptar links de paginación para mostrar loading
        function setupPaginationLoading() {
            document.querySelectorAll('.pagination a').forEach(link => {
                link.addEventListener('click', function(e) {
                    showLoading();
                    showTableSkeleton();
                });
            });
        }

        // Animaciones de entrada y inicialización del sistema de notificaciones
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar sistema de notificaciones
            window.notificationSystem = new NotificationSystem();
            
            // Configurar loading para paginación
            setupPaginationLoading();
            
            // Optimización de carga inicial
            const startTime = performance.now();
            
            // Animaciones de entrada
            const animatedElements = document.querySelectorAll('.animate-fade-in');
            animatedElements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Mostrar tiempo de carga en consola para debugging
            const loadTime = performance.now() - startTime;
            console.log(`Página de usuarios cargada en ${loadTime.toFixed(2)}ms`);
            
            // Lazy load para elementos no críticos
            setTimeout(() => {
                // Precargar próxima página si es necesario
                const nextPageLink = document.querySelector('.pagination .page-item:last-child a');
                if (nextPageLink && nextPageLink.textContent.includes('Siguiente')) {
                    const nextHref = nextPageLink.href;
                    const link = document.createElement('link');
                    link.rel = 'prefetch';
                    link.href = nextHref;
                    document.head.appendChild(link);
                }
            }, 2000);
        });

        // Validaciones en tiempo real para crear usuario
        document.getElementById('num_doc')?.addEventListener('input', function() {
            const value = this.value;
            if (value.length > 0 && value.length < 6) {
                this.setCustomValidity('El número de documento debe tener al menos 6 dígitos');
            } else {
                this.setCustomValidity('');
            }
        });

        document.getElementById('contrasena')?.addEventListener('input', function() {
            const value = this.value;
            if (value.length > 0 && value.length < 6) {
                this.setCustomValidity('La contraseña debe tener al menos 6 caracteres');
            } else {
                this.setCustomValidity('');
            }
        });

        // Limpiar formularios al cerrar modales
        ['crearUsuarioModal', 'editarUsuarioModal'].forEach(modalId => {
            const modal = document.getElementById(modalId);
            modal?.addEventListener('hidden.bs.modal', function() {
                const form = this.querySelector('form');
                if (form) {
                    form.reset();
                    // Limpiar validaciones personalizadas
                    const inputs = form.querySelectorAll('input');
                    inputs.forEach(input => input.setCustomValidity(''));
                }
            });
        });
    </script>
</body>
</html>