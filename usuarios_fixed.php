<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/db.php';

// Verificar permisos
$usuario = $_SESSION['user'];
$es_admin = $usuario['rol'] === 'admin';
$es_coordinador = $usuario['rol'] === 'coordinador' || $es_admin;

// Solo coordinadores y admins pueden acceder
if (!$es_coordinador) {
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
                
                $num_doc = $_POST['num_doc'];
                $tipo_documento = $_POST['tipo_documento'];
                $nombres = $_POST['nombres'];
                $apellidos = $_POST['apellidos'];
                $telefono = $_POST['telefono'];
                $correo = $_POST['correo'];
                $cargo = $_POST['cargo'];
                $rol = $_POST['rol'];
                $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
                
                // Solo admin puede crear otros admins
                if ($rol === 'admin' && !$es_admin) {
                    throw new Exception('Solo los administradores pueden crear otros administradores');
                }
                
                // Verificar si el usuario ya existe
                $check_stmt = $conn->prepare("SELECT num_doc FROM usuarios WHERE num_doc = ?");
                $check_stmt->bind_param("i", $num_doc);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()) {
                    throw new Exception('Ya existe un usuario con este número de documento');
                }
                
                $stmt = $conn->prepare("INSERT INTO usuarios (num_doc, tipo_documento, nombres, apellidos, telefono, correo, cargo, rol, contrasena) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssssss", $num_doc, $tipo_documento, $nombres, $apellidos, $telefono, $correo, $cargo, $rol, $contrasena);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente']);
                break;
                
            case 'editar':
                if (!$es_coordinador) {
                    throw new Exception('No tiene permisos para editar usuarios');
                }
                
                $num_doc = $_POST['num_doc'];
                $nombres = $_POST['nombres'];
                $apellidos = $_POST['apellidos'];
                $telefono = $_POST['telefono'];
                $correo = $_POST['correo'];
                $cargo = $_POST['cargo'];
                $rol = $_POST['rol'];
                
                // Solo admin puede editar roles de admin
                if ($rol === 'admin' && !$es_admin) {
                    throw new Exception('Solo los administradores pueden asignar el rol de administrador');
                }
                
                if (!empty($_POST['nueva_contrasena'])) {
                    $nueva_contrasena = password_hash($_POST['nueva_contrasena'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE usuarios SET nombres = ?, apellidos = ?, telefono = ?, correo = ?, cargo = ?, rol = ?, contrasena = ? WHERE num_doc = ?");
                    $stmt->bind_param("sssssssi", $nombres, $apellidos, $telefono, $correo, $cargo, $rol, $nueva_contrasena, $num_doc);
                } else {
                    $stmt = $conn->prepare("UPDATE usuarios SET nombres = ?, apellidos = ?, telefono = ?, correo = ?, cargo = ?, rol = ? WHERE num_doc = ?");
                    $stmt->bind_param("ssssssi", $nombres, $apellidos, $telefono, $correo, $cargo, $rol, $num_doc);
                }
                
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
                break;
                
            case 'eliminar':
                if (!$es_admin) {
                    throw new Exception('Solo los administradores pueden eliminar usuarios');
                }
                
                $num_doc = $_POST['num_doc'];
                
                // No permitir eliminar a sí mismo
                if ($num_doc == $usuario['num_doc']) {
                    throw new Exception('No puede eliminarse a sí mismo');
                }
                
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE num_doc = ?");
                $stmt->bind_param("i", $num_doc);
                $stmt->execute();
                
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

// Construir consulta con estadísticas
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM productos p WHERE p.usuario_asignado = u.num_doc) as productos_asignados,
        (SELECT COUNT(*) FROM reportes r WHERE r.usuario = u.num_doc) as reportes_creados
        FROM usuarios u WHERE 1=1";

$where_conditions = [];
$bind_types = "";
$bind_params = [];

if (!empty($filtro_nombre)) {
    $sql .= " AND (u.nombres LIKE ? OR u.apellidos LIKE ?)";
    $bind_types .= "ss";
    $bind_params[] = "%$filtro_nombre%";
    $bind_params[] = "%$filtro_nombre%";
}

if (!empty($filtro_rol)) {
    $sql .= " AND u.rol = ?";
    $bind_types .= "s";
    $bind_params[] = $filtro_rol;
}

if (!empty($filtro_cargo)) {
    $sql .= " AND u.cargo LIKE ?";
    $bind_types .= "s";
    $bind_params[] = "%$filtro_cargo%";
}

$sql .= " ORDER BY u.nombres, u.apellidos";

$stmt = $conn->prepare($sql);
if (!empty($bind_params)) {
    $stmt->bind_param($bind_types, ...$bind_params);
}
$stmt->execute();
$usuarios_result = $stmt->get_result();

// Obtener estadísticas generales
$stats_sql = "SELECT 
    COUNT(*) as total_usuarios,
    SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) as total_admins,
    SUM(CASE WHEN rol = 'coordinador' THEN 1 ELSE 0 END) as total_coordinadores,
    SUM(CASE WHEN rol = 'auxiliar' THEN 1 ELSE 0 END) as total_auxiliares
    FROM usuarios";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
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
                            <h5><i class="fas fa-table me-2"></i>Lista de Usuarios</h5>
                            <span class="badge bg-info fs-6"><?php echo $usuarios_result->num_rows; ?> usuarios encontrados</span>
                        </div>
                        
                        <?php if ($usuarios_result->num_rows > 0): ?>
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
                                    <?php while($usuario_row = $usuarios_result->fetch_assoc()): ?>
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
            
            fetch('usuarios.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('crearUsuarioModal')).hide();
                    showAlert('Usuario creado exitosamente', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
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
            
            fetch('usuarios.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editarUsuarioModal')).hide();
                    showAlert('Usuario actualizado exitosamente', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
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
            
            fetch('usuarios.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('eliminarUsuarioModal')).hide();
                    showAlert('Usuario eliminado exitosamente', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert(data.message || 'Error al eliminar usuario', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error de conexión al eliminar usuario', 'danger');
            });
        });

        // Función para mostrar alertas
        function showAlert(message, type) {
            // Remover alertas existentes
            const existingAlerts = document.querySelectorAll('.custom-alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Crear nueva alerta
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show custom-alert`;
            alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
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

        // Animaciones de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const animatedElements = document.querySelectorAll('.animate-fade-in');
            animatedElements.forEach((element, index) => {
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            });
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