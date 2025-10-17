<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/db.php';
require_once 'includes/responsive-helper.php';

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
                $check_stmt = $conn->prepare("SELECT num_doc FROM users WHERE num_doc = ?");
                $check_stmt->bind_param("i", $num_doc);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                if ($result->fetch_assoc()) {
                    throw new Exception('Ya existe un usuario con este número de documento');
                }
                
                $stmt = $conn->prepare("INSERT INTO users (num_doc, tipo_documento, nombres, apellidos, telefono, correo, cargo, rol, contrasena) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
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
                    $stmt = $conn->prepare("UPDATE users SET nombres = ?, apellidos = ?, telefono = ?, correo = ?, cargo = ?, rol = ?, contrasena = ? WHERE num_doc = ?");
                    $stmt->bind_param("sssssssi", $nombres, $apellidos, $telefono, $correo, $cargo, $rol, $nueva_contrasena, $num_doc);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET nombres = ?, apellidos = ?, telefono = ?, correo = ?, cargo = ?, rol = ? WHERE num_doc = ?");
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
                $stmt = $conn->prepare("DELETE FROM users WHERE num_doc = ?");
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
        (SELECT COUNT(*) FROM productos p WHERE p.num_doc = u.num_doc) as productos_asignados,
        (SELECT COUNT(*) FROM reportes r WHERE r.num_doc = u.num_doc) as reportes_creados
        FROM users u WHERE 1=1";

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
    FROM users";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();
?>

<?php
// Configuración de la página responsiva
$pageConfig = array_merge(ResponsivePageHelper::setActiveModule('usuarios'), [
    'MODULE_TITLE' => 'Gestión de Usuarios',
    'MODULE_DESCRIPTION' => 'Administración de usuarios del sistema Inventixor',
    'MODULE_ICON' => 'fas fa-users',
    'MODULE_SUBTITLE' => 'Administrar usuarios, roles y permisos del sistema',
    'ADDITIONAL_STYLES' => ResponsivePageHelper::getModuleStyles('usuarios'),
    'USER_MENU' => ResponsivePageHelper::getUserMenu($usuario['rol']),
    'NOTIFICATION_SCRIPT' => ResponsivePageHelper::getNotificationScript(),
    'ADDITIONAL_SCRIPTS' => ResponsivePageHelper::getTableScripts('usuariosTable') . ResponsivePageHelper::getFormScripts()
]);

// Capturar el contenido del módulo
ob_start();
?>

<!-- Stats Cards -->
<div class="container-fluid mb-4">
    <div class="row g-3">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card stat-card bg-primary text-white animate-fade-in" style="animation-delay: 0.1s">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="mb-0"><?php echo $stats['total_usuarios']; ?></h3>
                        <p class="mb-0">Total Usuarios</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card stat-card bg-success text-white animate-fade-in" style="animation-delay: 0.2s">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3">
                        <i class="fas fa-crown fa-2x"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="mb-0"><?php echo $stats['total_admins']; ?></h3>
                        <p class="mb-0">Administradores</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card stat-card bg-info text-white animate-fade-in" style="animation-delay: 0.3s">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3">
                        <i class="fas fa-user-tie fa-2x"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="mb-0"><?php echo $stats['total_coordinadores']; ?></h3>
                        <p class="mb-0">Coordinadores</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card stat-card bg-warning text-white animate-fade-in" style="animation-delay: 0.4s">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3">
                        <i class="fas fa-user fa-2x"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="mb-0"><?php echo $stats['total_auxiliares']; ?></h3>
                        <p class="mb-0">Auxiliares</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros y acciones -->
<div class="container-fluid mb-4">
    <div class="card animate-slide-up" style="animation-delay: 0.5s">
        <div class="card-header bg-light">
            <div class="row align-items-center">
                <div class="col-12 col-md-6">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros y Búsqueda</h5>
                </div>
                <div class="col-12 col-md-6 text-md-end mt-2 mt-md-0">
                    <?php if ($es_coordinador): ?>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#crearUsuarioModal">
                        <i class="fas fa-plus me-1"></i>Nuevo Usuario
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-12 col-md-3">
                    <label for="buscar" class="form-label">Buscar:</label>
                    <input type="text" class="form-control" id="buscar" name="buscar" 
                           value="<?php echo htmlspecialchars($filtro_buscar); ?>" 
                           placeholder="Nombre, documento...">
                </div>
                <div class="col-12 col-md-3">
                    <label for="filtro_rol" class="form-label">Rol:</label>
                    <select class="form-select" id="filtro_rol" name="filtro_rol">
                        <option value="">Todos los roles</option>
                        <option value="admin" <?php echo $filtro_rol === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                        <option value="coordinador" <?php echo $filtro_rol === 'coordinador' ? 'selected' : ''; ?>>Coordinador</option>
                        <option value="auxiliar" <?php echo $filtro_rol === 'auxiliar' ? 'selected' : ''; ?>>Auxiliar</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label for="filtro_cargo" class="form-label">Cargo:</label>
                    <input type="text" class="form-control" id="filtro_cargo" name="filtro_cargo" 
                           value="<?php echo htmlspecialchars($filtro_cargo); ?>" 
                           placeholder="Filtrar por cargo...">
                </div>
                <div class="col-12 col-md-3 d-flex align-items-end">
                    <div class="btn-group w-100" role="group">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search me-1"></i>Filtrar
                        </button>
                        <a href="usuarios.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabla de usuarios -->
<div class="container-fluid">
    <div class="card animate-slide-up" style="animation-delay: 0.6s">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-table me-2"></i>Lista de Usuarios</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="usuariosTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Documento</th>
                            <th>Nombre Completo</th>
                            <th>Teléfono</th>
                            <th>Correo</th>
                            <th>Cargo</th>
                            <th>Rol</th>
                            <th class="text-center">Estado</th>
                            <?php if ($es_coordinador): ?>
                            <th class="text-center">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $usuarios_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-2 bg-primary text-white d-flex align-items-center justify-content-center">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted"><?php echo ucfirst($user['tipo_documento']); ?></small><br>
                                        <strong><?php echo htmlspecialchars($user['num_doc']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($user['nombres'] . ' ' . $user['apellidos']); ?></strong>
                                </div>
                            </td>
                            <td>
                                <a href="tel:<?php echo $user['telefono']; ?>" class="text-decoration-none">
                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($user['telefono']); ?>
                                </a>
                            </td>
                            <td>
                                <a href="mailto:<?php echo $user['correo']; ?>" class="text-decoration-none">
                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($user['correo']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($user['cargo']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $user['rol'] === 'admin' ? 'danger' : 
                                        ($user['rol'] === 'coordinador' ? 'primary' : 'secondary'); 
                                ?>">
                                    <?php echo ucfirst($user['rol']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="user-status active" title="Activo"></span>
                            </td>
                            <?php if ($es_coordinador): ?>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($es_admin): ?>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="confirmarEliminar(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['nombres'] . ' ' . $user['apellidos']); ?>')"
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
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
<div class="modal fade" id="crearUsuarioModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="createUserForm" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="tipo_documento" class="form-label">
                                <i class="fas fa-id-card me-1"></i>Tipo de Documento
                            </label>
                            <select name="tipo_documento" id="tipo_documento" class="form-select" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="cedula">Cédula de Ciudadanía</option>
                                <option value="cedula_extranjeria">Cédula de Extranjería</option>
                                <option value="pasaporte">Pasaporte</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="num_doc" class="form-label">
                                <i class="fas fa-hashtag me-1"></i>Número de Documento
                            </label>
                            <input type="text" name="num_doc" id="num_doc" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="nombres" class="form-label">
                                <i class="fas fa-user me-1"></i>Nombres
                            </label>
                            <input type="text" name="nombres" id="nombres" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="apellidos" class="form-label">
                                <i class="fas fa-user me-1"></i>Apellidos
                            </label>
                            <input type="text" name="apellidos" id="apellidos" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">
                                <i class="fas fa-phone me-1"></i>Teléfono
                            </label>
                            <input type="tel" name="telefono" id="telefono" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="correo" class="form-label">
                                <i class="fas fa-envelope me-1"></i>Correo Electrónico
                            </label>
                            <input type="email" name="correo" id="correo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="cargo" class="form-label">
                                <i class="fas fa-briefcase me-1"></i>Cargo
                            </label>
                            <input type="text" name="cargo" id="cargo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="rol" class="form-label">
                                <i class="fas fa-user-tag me-1"></i>Rol
                            </label>
                            <select name="rol" id="rol" class="form-select" required>
                                <option value="">Seleccionar rol</option>
                                <option value="auxiliar">Auxiliar</option>
                                <option value="coordinador">Coordinador</option>
                                <?php if ($es_admin): ?>
                                <option value="admin">Administrador</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="contrasena" class="form-label">
                                <i class="fas fa-lock me-1"></i>Contraseña
                            </label>
                            <input type="password" name="contrasena" id="contrasena" class="form-control" required>
                            <div class="form-text">La contraseña debe tener al menos 6 caracteres.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editarUsuarioModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit me-2"></i>Editar Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm" class="needs-validation" novalidate>
                <input type="hidden" name="id" id="editUserId">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editTipoDocumento" class="form-label">
                                <i class="fas fa-id-card me-1"></i>Tipo de Documento
                            </label>
                            <select name="tipo_documento" id="editTipoDocumento" class="form-select" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="cedula">Cédula de Ciudadanía</option>
                                <option value="cedula_extranjeria">Cédula de Extranjería</option>
                                <option value="pasaporte">Pasaporte</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editNumDoc" class="form-label">
                                <i class="fas fa-hashtag me-1"></i>Número de Documento
                            </label>
                            <input type="text" name="num_doc" id="editNumDoc" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editNombres" class="form-label">
                                <i class="fas fa-user me-1"></i>Nombres
                            </label>
                            <input type="text" name="nombres" id="editNombres" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editApellidos" class="form-label">
                                <i class="fas fa-user me-1"></i>Apellidos
                            </label>
                            <input type="text" name="apellidos" id="editApellidos" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editTelefono" class="form-label">
                                <i class="fas fa-phone me-1"></i>Teléfono
                            </label>
                            <input type="tel" name="telefono" id="editTelefono" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editCorreo" class="form-label">
                                <i class="fas fa-envelope me-1"></i>Correo Electrónico
                            </label>
                            <input type="email" name="correo" id="editCorreo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editCargo" class="form-label">
                                <i class="fas fa-briefcase me-1"></i>Cargo
                            </label>
                            <input type="text" name="cargo" id="editCargo" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editRol" class="form-label">
                                <i class="fas fa-user-tag me-1"></i>Rol
                            </label>
                            <select name="rol" id="editRol" class="form-select" required>
                                <option value="">Seleccionar rol</option>
                                <option value="auxiliar">Auxiliar</option>
                                <option value="coordinador">Coordinador</option>
                                <?php if ($es_admin): ?>
                                <option value="admin">Administrador</option>
                                <?php endif; ?>
                            </select>
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

<?php
// Generar la página completa usando el sistema responsivo
renderResponsivePage($pageConfig);
?>

<!-- Notificaciones reutilizables -->
<script src="public/js/notifications.js"></script>
<script>
// Funciones específicas del módulo de usuarios
async function crearUsuario(formData) {
    try {
        const response = await fetch('usuarios.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (typeof ResponsiveUtils !== 'undefined') {
                ResponsiveUtils.showNotification(result.message, 'success');
            } else if (typeof showToast === 'function') {
                showToast(result.message, 'success');
            }
            setTimeout(() => location.reload(), 1500);
        } else {
            if (typeof ResponsiveUtils !== 'undefined') {
                ResponsiveUtils.showNotification(result.message, 'error');
            } else if (typeof showToast === 'function') {
                showToast(result.message || 'Error al crear usuario', 'error');
            }
        }
    } catch (error) {
        if (typeof ResponsiveUtils !== 'undefined') {
            ResponsiveUtils.showNotification('Error de conexión: ' + error.message, 'error');
        } else if (typeof showToast === 'function') {
            showToast('Error de conexión: ' + error.message, 'error');
        }
    }
}

function editarUsuario(usuario) {
    document.getElementById('editUserId').value = usuario.id;
    document.getElementById('editNumDoc').value = usuario.num_doc;
    document.getElementById('editTipoDocumento').value = usuario.tipo_documento;
    document.getElementById('editNombres').value = usuario.nombres;
    document.getElementById('editApellidos').value = usuario.apellidos;
    document.getElementById('editTelefono').value = usuario.telefono;
    document.getElementById('editCorreo').value = usuario.correo;
    document.getElementById('editCargo').value = usuario.cargo;
    document.getElementById('editRol').value = usuario.rol;
    
    const modal = new bootstrap.Modal(document.getElementById('editarUsuarioModal'));
    modal.show();
}

function confirmarEliminar(id, nombre) {
    if (confirm(`¿Está seguro de que desea eliminar al usuario "${nombre}"?`)) {
        eliminarUsuario(id);
    }
}

async function eliminarUsuario(id) {
    try {
        const formData = new FormData();
        formData.append('action', 'eliminar');
        formData.append('id', id);
        
        const response = await fetch('usuarios.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (typeof ResponsiveUtils !== 'undefined') {
                ResponsiveUtils.showNotification(result.message, 'success');
            } else if (typeof showToast === 'function') {
                showToast(result.message, 'success');
            }
            setTimeout(() => location.reload(), 1500);
        } else {
            if (typeof ResponsiveUtils !== 'undefined') {
                ResponsiveUtils.showNotification(result.message, 'error');
            } else if (typeof showToast === 'function') {
                showToast(result.message || 'Error al eliminar', 'error');
            }
        }
    } catch (error) {
        if (typeof ResponsiveUtils !== 'undefined') {
            ResponsiveUtils.showNotification('Error de conexión: ' + error.message, 'error');
        } else if (typeof showToast === 'function') {
            showToast('Error de conexión: ' + error.message, 'error');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const createForm = document.getElementById('createUserForm');
    createForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'crear');
        crearUsuario(formData);
    });

    const editForm = document.getElementById('editUserForm');
    editForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'editar');
        crearUsuario(formData);
    });

    // Validaciones básicas
    const numDocInput = document.getElementById('num_doc');
    if (numDocInput) {
        numDocInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 0 && this.value.length < 6) {
                this.setCustomValidity('El documento debe tener al menos 6 dígitos');
            } else {
                this.setCustomValidity('');
            }
        });
    }

    const telefonoInput = document.getElementById('telefono');
    if (telefonoInput) {
        telefonoInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
        });
    }
});
</script>
