
<?php
// categorias.php
session_start();
require_once 'app/helpers/Database.php';
require_once 'app/helpers/SistemaNotificaciones.php';
$db = new Database();
$sistemaNotificaciones = new SistemaNotificaciones($db);
$showReportBtn = true;

// Verificar si es administrador
$es_admin = (isset($_SESSION['user']) && $_SESSION['user']['rol'] === 'admin');
if ($showReportBtn) {
    echo '<div class="text-end mb-3"><a href="reportes.php?tabla=categorias" class="btn btn-primary">Reportes de categorías</a></div>';
}
// Asegurar que $_SESSION['rol'] esté definido
if (!isset($_SESSION['rol'])) {
    if (isset($_SESSION['user'])) {
        $num_doc = $_SESSION['user'];
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

// Eliminar categoría
if (isset($_GET['eliminar'])) {
    $id_categoria = intval($_GET['eliminar']);
    $subcats = $db->conn->query("SELECT COUNT(*) FROM Subcategoria WHERE id_categ = $id_categoria");
    if ($subcats->fetch_row()[0] > 0) {
        $errorMsg = "No se puede eliminar la categoría porque tiene subcategorías asociadas.";
    } else {
        $cat = $db->conn->query("SELECT * FROM Categoria WHERE id_categ = $id_categoria")->fetch_assoc();
        $stmt = $db->conn->prepare("DELETE FROM Categoria WHERE id_categ=?");
        $stmt->bind_param('i', $id_categoria);
        $stmt->execute();
        $stmt->close();
        if (in_array($_SESSION['rol'], ['admin', 'coordinador'])) {
            $usuario = $_SESSION['user']['nombre'] ?? 'Desconocido';
            $rol = $_SESSION['rol'];
            $detalles = json_encode($cat);
            $db->conn->query("INSERT INTO HistorialCRUD (entidad, id_entidad, accion, usuario, rol, detalles) VALUES ('Categoria', $id_categoria, 'eliminar', '$usuario', '$rol', '$detalles')");
        }
        
        // Generar notificación automática para todos los usuarios
        $usuario_nombre = $_SESSION['user']['nombre'] ?? $_SESSION['user']['name'] ?? 'Usuario';
        $sistemaNotificaciones->notificarEliminacionCategoria($cat, $usuario_nombre);
        
        // Redireccionar con información específica de la categoría eliminada
        $categoria_info = urlencode($cat['nombre']);
        header("Location: categorias.php?msg=eliminado&id_categ=$id_categoria&nombre_categ=$categoria_info");
        exit;
    }
}

// Modificar categoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modificar_categoria'])) {
    $id = intval($_POST['id_categ']);
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $old = $db->conn->query("SELECT * FROM Categoria WHERE id_categ = $id")->fetch_assoc();
    $stmt = $db->conn->prepare("UPDATE Categoria SET nombre = ?, descripcion = ? WHERE id_categ = ?");
    $stmt->bind_param('ssi', $nombre, $descripcion, $id);
    $stmt->execute();
    $stmt->close();
    if (in_array($_SESSION['rol'], ['admin', 'coordinador'])) {
        $usuario = $_SESSION['user']['nombre'] ?? 'Desconocido';
        $rol = $_SESSION['rol'];
        $detalles = json_encode(['antes'=>$old,'despues'=>compact('nombre','descripcion')]);
        $db->conn->query("INSERT INTO HistorialCRUD (entidad, id_entidad, accion, usuario, rol, detalles) VALUES ('Categoria', $id, 'editar', '$usuario', '$rol', '$detalles')");
    }
    header('Location: categorias.php?msg=modificado');
    exit;
}

// Procesar formulario de creación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_categoria'])) {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $stmt = $db->conn->prepare("INSERT INTO Categoria (nombre, descripcion) VALUES (?, ?)");
    $stmt->bind_param('ss', $nombre, $descripcion);
    $stmt->execute();
    $nuevo_id = $db->conn->insert_id;
    $stmt->close();
    if (in_array($_SESSION['rol'], ['admin', 'coordinador'])) {
        $usuario = $_SESSION['user']['nombre'] ?? 'Desconocido';
        $rol = $_SESSION['rol'];
        $detalles = json_encode(compact('nombre','descripcion'));
        $db->conn->query("INSERT INTO HistorialCRUD (entidad, id_entidad, accion, usuario, rol, detalles) VALUES ('Categoria', $nuevo_id, 'crear', '$usuario', '$rol', '$detalles')");
    }
    
    // Generar notificación automática para todos los usuarios
    $usuario_nombre = $_SESSION['user']['nombre'] ?? $_SESSION['user']['name'] ?? 'Usuario';
    $sistemaNotificaciones->notificarNuevaCategoria($nuevo_id, $nombre, $usuario_nombre);
    
    header('Location: categorias.php?msg=creado');
    exit;
}

// Filtrar categorías
$filtro = '';
// Context for the main content
if (isset($_GET['filtro']) && $_GET['filtro'] !== '') {
    $filtro = $_GET['filtro'];
    $sql = "SELECT id_categ, nombre, descripcion FROM Categoria WHERE nombre LIKE ? OR descripcion LIKE ?";
    $stmt = $db->conn->prepare($sql);
    $like = "%$filtro%";
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $sql = "SELECT id_categ, nombre, descripcion FROM Categoria";
    $result = $db->conn->query($sql);
}

// Para mostrar el formulario de modificar
$editCategoria = null;
if (isset($_GET['modificar'])) {
    $id = intval($_GET['modificar']);
    $sql = "SELECT id_categ, nombre, descripcion FROM Categoria WHERE id_categ = ?";
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $editCategoria = $res->fetch_assoc();
    $stmt->close();
}

// Mensajes
$show_notification = '';
$categoria_eliminada = [];
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'creado':
            $show_notification = 'created';
            break;
        case 'modificado':
            $show_notification = 'updated';
            break;
        case 'eliminado':
            // Obtener información específica de la categoría eliminada
            $id_eliminado = isset($_GET['id_categ']) ? intval($_GET['id_categ']) : 0;
            $nombre_eliminado = isset($_GET['nombre_categ']) ? urldecode($_GET['nombre_categ']) : 'Desconocido';
            $categoria_eliminada = [
                'id' => $id_eliminado,
                'nombre' => $nombre_eliminado
            ];
            $show_notification = 'deleted';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías - Inventixor</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="public/css/responsive-sidebar.css">
    
    <style>
        
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
                <a href="categorias.php" class="menu-link active">
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

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="main-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-tags me-2"></i>Gestión de Categorías</h2>
                        <p class="mb-0">Administra las categorías de productos</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-light text-dark">
                            Rol: <?= htmlspecialchars($_SESSION['rol']??'') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filtros y estadísticas -->
        <div class="filter-card animate-fade-in">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5><i class="fas fa-filter me-2"></i>Filtros y Búsqueda</h5>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="fas fa-plus me-2"></i>Nueva Categoría
                </button>
            </div>
            <div class="row align-items-end">
                <div class="col-md-6">
                    <label for="filtroInput" class="form-label">Buscar por nombre:</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="filtroInput" class="form-control" placeholder="Filtrar por nombre de categoría" value="<?= htmlspecialchars($filtro) ?>">
                        <button class="btn btn-primary" onclick="aplicarFiltro()">
                            <i class="fas fa-search me-1"></i>Buscar
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-secondary w-100" onclick="limpiarFiltro()">
                        <i class="fas fa-times me-1"></i>Limpiar
                    </button>
                </div>
                <div class="col-md-3">
                    <div class="stats-card text-center">
                        <small class="text-muted">Total Categorías</small>
                        <div class="h4 mb-0"><?php echo $result->num_rows; ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de categorías -->
        <div class="table-card animate-fade-in">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                        <tr>
                            <th><i class="fas fa-hashtag me-1"></i>ID</th>
                            <th><i class="fas fa-tag me-1"></i>Nombre</th>
                            <th><i class="fas fa-align-left me-1"></i>Descripción</th>
                            <th><i class="fas fa-cogs me-1"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge bg-primary"><?= $row['id_categ'] ?></span></td>
                            <td>
                                <strong><?= htmlspecialchars($row['nombre']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($row['descripcion']) ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if (in_array($_SESSION['rol'], ['admin','coordinador'])): ?>
                                    <button type="button" class="btn btn-outline-primary btn-action" 
                                            onclick="editarCategoria(<?= $row['id_categ'] ?>, '<?= addslashes($row['nombre']) ?>', '<?= addslashes($row['descripcion']) ?>')"
                                            title="Editar Categoría">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-action"
                                            onclick="confirmarEliminar(<?= $row['id_categ'] ?>, '<?= addslashes($row['nombre']) ?>')"
                                            title="Eliminar Categoría">
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
        </div>
        </main>
    </div>
    <script>
        document.getElementById('btnMostrarFormCat').onclick = function() {
            document.getElementById('formCrearCat').style.display = 'block';
            this.style.display = 'none';
        };
        document.getElementById('btnCancelarFormCat').onclick = function() {
            document.getElementById('formCrearCat').reset();
            document.getElementById('formCrearCat').style.display = 'none';
            document.getElementById('btnMostrarFormCat').style.display = 'inline-block';
        };
    </script>

    <!-- Modal para crear categoría -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Nueva Categoría
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombreCat" class="form-label">
                                <i class="fas fa-tag me-1"></i>Nombre de la Categoría
                            </label>
                            <input type="text" name="nombre" id="nombreCat" class="form-control" 
                                   placeholder="Ej: Calzado Deportivo" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label for="descripcionCat" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Descripción
                            </label>
                            <textarea name="descripcion" id="descripcionCat" class="form-control" rows="3"
                                    placeholder="Descripción detallada de la categoría" required maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_categoria" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar categoría -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #ffc107, #ff8f00); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Categoría
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="id_categ" id="editId">
                        <div class="mb-3">
                            <label for="editNombre" class="form-label">
                                <i class="fas fa-tag me-1"></i>Nombre de la Categoría
                            </label>
                            <input type="text" name="nombre" id="editNombre" class="form-control" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label for="editDescripcion" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Descripción
                            </label>
                            <textarea name="descripcion" id="editDescripcion" class="form-control" rows="3" required maxlength="255"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="modificar_categoria" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Actualizar
                        </button>
                    </div>
                </form>
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
                        <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                        <p>¿Está seguro de que desea eliminar la categoría?</p>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Categoría:</strong> <span id="categoryName"></span>
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

    <script>
        let categoryToDelete = null;
        
        // Función para aplicar filtro
        function aplicarFiltro() {
            const filtro = document.getElementById('filtroInput').value;
            window.location.href = `categorias.php?filtro=${encodeURIComponent(filtro)}`;
        }
        
        // Función para limpiar filtro
        function limpiarFiltro() {
            window.location.href = 'categorias.php';
        }
        
        // Función para editar categoría
        function editarCategoria(id, nombre, descripcion) {
            document.getElementById('editId').value = id;
            document.getElementById('editNombre').value = nombre;
            document.getElementById('editDescripcion').value = descripcion;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        
        // Función para confirmar eliminación
        function confirmarEliminar(id, nombre) {
            categoryToDelete = id;
            document.getElementById('categoryName').textContent = nombre;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Procesar eliminación
        document.getElementById('confirmDelete').addEventListener('click', function() {
            if (categoryToDelete) {
                window.location.href = `categorias.php?eliminar=${categoryToDelete}`;
            }
        });
        
        // Enter en filtro
        document.getElementById('filtroInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                aplicarFiltro();
            }
        });
        // Sistema de notificaciones automáticas
        <?php if ($show_notification): ?>
        setTimeout(function() {
            const notificationSystem = new NotificationSystem();
            <?php if ($show_notification === 'created'): ?>
            notificationSystem.showCategoryChange('create', 'Categoría creada exitosamente', 'success');
            <?php elseif ($show_notification === 'updated'): ?>
            notificationSystem.showCategoryChange('update', 'Categoría actualizada exitosamente', 'success');
            <?php elseif ($show_notification === 'deleted'): ?>
            notificationSystem.showCategoryChange(
                'delete', 
                'Categoría "<?= htmlspecialchars($categoria_eliminada['nombre']) ?>" (ID: <?= $categoria_eliminada['id'] ?>) eliminada del sistema', 
                'warning',
                {
                    id: <?= $categoria_eliminada['id'] ?>,
                    nombre: '<?= htmlspecialchars($categoria_eliminada['nombre']) ?>'
                }
            );
            <?php endif; ?>
        }, 500);
        <?php endif; ?>
    </script>
    
    <!-- Sistema de Notificaciones -->
    <script src="public/js/notifications.js"></script>
    <script src="public/js/auto-notifications.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sistema Responsive -->
    <script src="public/js/responsive-sidebar.js"></script>
    <script>
        // Marcar como activo el menú de categorías
        setActiveMenuItem('categorias.php');
    </script>
</body>
</html>
