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

// Obtener categorías para formularios
$categorias = [];
$resCat = $db->conn->query("SELECT id_categ, nombre FROM Categoria ORDER BY nombre");
while($cat = $resCat->fetch_assoc()) {
    $categorias[] = $cat;
}

// Eliminar subcategoría
if (isset($_GET['eliminar'])) {
    $id_subcategoria = intval($_GET['eliminar']);
    
    // Verificar si tiene productos asociados
    $prods = $db->conn->query("SELECT COUNT(*) FROM Productos WHERE id_subcg = $id_subcategoria");
    if ($prods->fetch_row()[0] > 0) {
        $errorMsg = "No se puede eliminar la subcategoría porque tiene productos asociados.";
    } else {
        $subcat = $db->conn->query("SELECT * FROM Subcategoria WHERE id_subcg = $id_subcategoria")->fetch_assoc();
        $stmt = $db->conn->prepare("DELETE FROM Subcategoria WHERE id_subcg = ?");
        $stmt->bind_param('i', $id_subcategoria);
        $stmt->execute();
        $stmt->close();
        // Generar notificación automática para todos los usuarios
        $usuario_nombre = $_SESSION['user']['nombre'] ?? $_SESSION['user']['name'] ?? 'Usuario';
        $sistemaNotificaciones->notificarEliminacionSubcategoria($subcat, $usuario_nombre);
        
        // Redireccionar con información específica de la subcategoría eliminada
        $subcategoria_info = urlencode($subcat['nombre']);
        header("Location: subcategorias.php?msg=eliminado&id_subcg=$id_subcategoria&nombre_subcg=$subcategoria_info");
        exit;
    }
}

// Obtener detalles de subcategoría (AJAX)
if (isset($_GET['detalle'])) {
    $id_subcategoria = intval($_GET['detalle']);
    
    $query = "SELECT s.id_subcg, s.nombre, s.descripcion, c.nombre as categoria_nombre,
                     COUNT(p.id_prod) as productos_count
              FROM Subcategoria s 
              LEFT JOIN Categoria c ON s.id_categ = c.id_categ
              LEFT JOIN Productos p ON s.id_subcg = p.id_subcg
              WHERE s.id_subcg = ?
              GROUP BY s.id_subcg";
    
    $stmt = $db->conn->prepare($query);
    $stmt->bind_param('i', $id_subcategoria);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($subcategoria = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'subcategoria' => $subcategoria
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Subcategoría no encontrada'
        ]);
    }
    exit;
}

// Modificar subcategoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modificar_subcategoria'])) {
    $id = intval($_POST['id_subcg']);
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $id_categ = intval($_POST['id_categ']);
    $old = $db->conn->query("SELECT * FROM Subcategoria WHERE id_subcg = $id")->fetch_assoc();
    $stmt = $db->conn->prepare("UPDATE Subcategoria SET nombre = ?, descripcion = ?, id_categ = ? WHERE id_subcg = ?");
    $stmt->bind_param('ssii', $nombre, $descripcion, $id_categ, $id);
    $stmt->execute();
    $stmt->close();
    header('Location: subcategorias.php?msg=modificado');
    exit;
}

// Crear subcategoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_subcategoria'])) {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $id_categ = intval($_POST['id_categ']);
    $stmt = $db->conn->prepare("INSERT INTO Subcategoria (nombre, descripcion, id_categ) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $nombre, $descripcion, $id_categ);
    $stmt->execute();
    $nuevo_id = $db->conn->insert_id;
    $stmt->close();
    
    // Generar notificación automática para todos los usuarios
    $categoria_nombre = $db->conn->query("SELECT nombre FROM Categoria WHERE id_categ = $id_categ")->fetch_assoc()['nombre'];
    $usuario_nombre = $_SESSION['user']['nombre'] ?? $_SESSION['user']['name'] ?? 'Usuario';
    $sistemaNotificaciones->notificarNuevaSubcategoria($nuevo_id, $nombre, $categoria_nombre, $usuario_nombre);
    
    header('Location: subcategorias.php?msg=creado');
    exit;
}

// Filtros
$filtro = '';
$categoria_filtro = '';

if (isset($_GET['filtro']) && $_GET['filtro'] !== '') {
    $filtro = $_GET['filtro'];
}
if (isset($_GET['categoria']) && $_GET['categoria'] !== '') {
    $categoria_filtro = intval($_GET['categoria']);
}

// Consulta con JOIN y filtros
$sql = "SELECT s.id_subcg, s.nombre, s.descripcion, s.id_categ, c.nombre AS categoria_nombre, 
               COUNT(p.id_prod) AS total_productos
        FROM Subcategoria s 
        LEFT JOIN Categoria c ON s.id_categ = c.id_categ 
        LEFT JOIN Productos p ON s.id_subcg = p.id_subcg";

$where_conditions = [];
$params = [];
$types = '';

if ($filtro) {
    $where_conditions[] = "(s.nombre LIKE ? OR s.descripcion LIKE ?)";
    $params[] = "%$filtro%";
    $params[] = "%$filtro%";
    $types .= 'ss';
}

if ($categoria_filtro) {
    $where_conditions[] = "s.id_categ = ?";
    $params[] = $categoria_filtro;
    $types .= 'i';
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}

$sql .= " GROUP BY s.id_subcg, s.nombre, s.descripcion, s.id_categ, c.nombre ORDER BY c.nombre, s.nombre";

if (!empty($params)) {
    $stmt = $db->conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    $result = $db->conn->query($sql);
}

// Mensajes
$show_notification = '';
$subcategoria_eliminada = [];
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'creado':
            $show_notification = 'created';
            break;
        case 'modificado':
            $show_notification = 'updated';
            break;
        case 'eliminado':
            // Obtener información específica de la subcategoría eliminada
            $id_eliminado = isset($_GET['id_subcg']) ? intval($_GET['id_subcg']) : 0;
            $nombre_eliminado = isset($_GET['nombre_subcg']) ? urldecode($_GET['nombre_subcg']) : 'Desconocido';
            $subcategoria_eliminada = [
                'id' => $id_eliminado,
                'nombre' => $nombre_eliminado
            ];
            $show_notification = 'deleted';
            break;
    }
}

// Generar estadísticas generales
$stats_result = $db->conn->query("SELECT 
    COUNT(*) AS total_subcategorias,
    COUNT(DISTINCT s.id_categ) AS total_categorias_con_sub,
    (SELECT COUNT(*) FROM Categoria) AS total_categorias
    FROM Subcategoria s");
$stats = $stats_result->fetch_assoc();

// Configuración de la página responsiva
$pageConfig = array_merge(ResponsivePageHelper::setActiveModule('subcategorias'), [
    'MODULE_TITLE' => 'Gestión de Subcategorías',
    'MODULE_DESCRIPTION' => 'Administración de subcategorías del sistema Inventixor',
    'MODULE_ICON' => 'fas fa-tag',
    'MODULE_SUBTITLE' => 'Administrar subcategorías y su relación con categorías principales',
    'ADDITIONAL_STYLES' => ResponsivePageHelper::getModuleStyles('subcategorias'),
    'USER_MENU' => ResponsivePageHelper::getUserMenu($_SESSION['rol'] ?? ''),
    'NOTIFICATION_SCRIPT' => ResponsivePageHelper::getNotificationScript(),
    'ADDITIONAL_SCRIPTS' => ResponsivePageHelper::getTableScripts('subcategoriasTable') . ResponsivePageHelper::getFormScripts()
]);

// Capturar el contenido del módulo
ob_start();
?>

<!-- Stats Cards -->
<div class="container-fluid mb-4">
    <div class="row g-3">
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card stat-card bg-light border-0 shadow-sm animate-fade-in" style="animation-delay: 0.1s">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 rounded-circle bg-primary bg-opacity-10 p-3">
                        <i class="fas fa-tag fa-2x text-primary"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="mb-0 text-primary"><?php echo $stats['total_subcategorias']; ?></h3>
                        <p class="mb-0 text-secondary">Total Subcategorías</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card stat-card bg-light border-0 shadow-sm animate-fade-in" style="animation-delay: 0.2s">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 rounded-circle bg-success bg-opacity-10 p-3">
                        <i class="fas fa-tags fa-2x text-success"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="mb-0 text-success"><?php echo $stats['total_categorias']; ?></h3>
                        <p class="mb-0 text-secondary">Categorías Principales</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-4">
            <div class="card stat-card bg-light border-0 shadow-sm animate-fade-in" style="animation-delay: 0.3s">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon me-3 rounded-circle bg-info bg-opacity-10 p-3">
                        <i class="fas fa-sitemap fa-2x text-info"></i>
                    </div>
                    <div class="stat-info">
                        <h3 class="mb-0 text-info"><?php echo $stats['total_categorias_con_sub']; ?></h3>
                        <p class="mb-0 text-secondary">Categorías con Subcategorías</p>
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
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#crearSubcategoriaModal">
                        <i class="fas fa-plus me-1"></i>Nueva Subcategoría
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-12 col-md-5">
                    <label for="filtro" class="form-label">Buscar:</label>
                    <input type="text" class="form-control" id="filtro" name="filtro" 
                           value="<?php echo htmlspecialchars($filtro); ?>" 
                           placeholder="Nombre o descripción...">
                </div>
                <div class="col-12 col-md-4">
                    <label for="categoria_filtro" class="form-label">Categoría:</label>
                    <select class="form-select" id="categoria_filtro" name="categoria_filtro">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id_categ']; ?>" 
                                <?php echo $categoria_filtro == $cat['id_categ'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nombre']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex align-items-end">
                    <div class="btn-group w-100" role="group">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search me-1"></i>Filtrar
                        </button>
                        <a href="subcategorias.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabla de subcategorías -->
<div class="container-fluid">
    <div class="card animate-slide-up" style="animation-delay: 0.5s">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-table me-2"></i>Lista de Subcategorías</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="subcategoriasTable">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripción</th>
                            <th>Categoría Principal</th>
                            <th>Productos</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border border-secondary"><?php echo $row['id_subcg']; ?></span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['nombre']); ?></strong>
                            </td>
                            <td>
                                <span class="text-muted">
                                    <?php echo !empty($row['descripcion']) ? htmlspecialchars($row['descripcion']) : 'Sin descripción'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary">
                                    <i class="fas fa-tags me-1"></i>
                                    <?php echo htmlspecialchars($row['categoria_nombre']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info bg-opacity-10 text-info">
                                    <?php echo $row['total_productos']; ?> productos
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="editarSubcategoria(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($row['total_productos'] == 0): ?>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="confirmarEliminar(<?php echo $row['id_subcg']; ?>, '<?php echo htmlspecialchars($row['nombre']); ?>')"
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-outline-secondary" 
                                            disabled title="No se puede eliminar: tiene productos asociados">
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
<div class="modal fade" id="crearSubcategoriaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Crear Nueva Subcategoría
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="crearSubcategoriaForm" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-tag me-1"></i>Nombre de la Subcategoría
                            </label>
                            <input type="text" name="nombre" id="nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="id_categ" class="form-label">
                                <i class="fas fa-tags me-1"></i>Categoría Principal
                            </label>
                            <select name="id_categ" id="id_categ" class="form-select" required>
                                <option value="">Seleccionar categoría</option>
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id_categ']; ?>">
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="descripcion" class="form-label">
                                <i class="fas fa-info-circle me-1"></i>Descripción
                            </label>
                            <textarea name="descripcion" id="descripcion" class="form-control" rows="3" 
                                      placeholder="Descripción de la subcategoría (opcional)"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Crear Subcategoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editarSubcategoriaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Editar Subcategoría
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editarSubcategoriaForm" class="needs-validation" novalidate>
                <input type="hidden" name="id_subcg" id="editIdSubcg">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editNombre" class="form-label">
                                <i class="fas fa-tag me-1"></i>Nombre de la Subcategoría
                            </label>
                            <input type="text" name="nombre" id="editNombre" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editIdCateg" class="form-label">
                                <i class="fas fa-tags me-1"></i>Categoría Principal
                            </label>
                            <select name="id_categ" id="editIdCateg" class="form-select" required>
                                <option value="">Seleccionar categoría</option>
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id_categ']; ?>">
                                    <?php echo htmlspecialchars($cat['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="editDescripcion" class="form-label">
                                <i class="fas fa-info-circle me-1"></i>Descripción
                            </label>
                            <textarea name="descripcion" id="editDescripcion" class="form-control" rows="3"></textarea>
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

<script src="public/js/notifications.js"></script>
<script>
// Funciones específicas del módulo de subcategorías
function editarSubcategoria(subcategoria) {
    // Llenar el formulario de edición
    document.getElementById('editIdSubcg').value = subcategoria.id_subcg;
    document.getElementById('editNombre').value = subcategoria.nombre;
    document.getElementById('editIdCateg').value = subcategoria.id_categ;
    document.getElementById('editDescripcion').value = subcategoria.descripcion || '';
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('editarSubcategoriaModal'));
    modal.show();
}

function confirmarEliminar(id_subcg, nombre) {
    if (confirm(`¿Está seguro de que desea eliminar la subcategoría "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
        window.location.href = `subcategorias.php?eliminar=${id_subcg}`;
    }
}

// Event listeners para formularios
document.addEventListener('DOMContentLoaded', function() {
    // Formulario crear subcategoría
    const crearForm = document.getElementById('crearSubcategoriaForm');
    crearForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        if (this.checkValidity()) {
            const formData = new FormData(this);
            enviarFormularioSubcategoria(formData, 'crear');
        }
        this.classList.add('was-validated');
    });

    // Formulario editar subcategoría  
    const editarForm = document.getElementById('editarSubcategoriaForm');
    editarForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        if (this.checkValidity()) {
            const formData = new FormData(this);
            enviarFormularioSubcategoria(formData, 'editar');
        }
        this.classList.add('was-validated');
    });
});

async function enviarFormularioSubcategoria(formData, action) {
    try {
        formData.append('action', action);
        
        const response = await fetch('subcategorias.php', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            if (typeof ResponsiveUtils !== 'undefined') {
                ResponsiveUtils.showNotification(`Subcategoría ${action === 'crear' ? 'creada' : 'actualizada'} exitosamente`, 'success');
            } else if (typeof showToast === 'function') {
                showToast(`Subcategoría ${action === 'crear' ? 'creada' : 'actualizada'} exitosamente`, 'success');
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

<?php
// Procesamiento AJAX para operaciones CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create':
            case 'update':
                $id = $_POST['id'] ?? null;
                $nombre = trim($_POST['nombre'] ?? '');
                $descripcion = trim($_POST['descripcion'] ?? '');
                $id_categoria = intval($_POST['id_categoria'] ?? 0);
                
                // Validaciones
                if (empty($nombre)) {
                    throw new Exception('El nombre es obligatorio');
                }
                
                if ($id_categoria <= 0) {
                    throw new Exception('Debe seleccionar una categoría válida');
                }
                
                // Verificar que la categoría existe
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE id = ?");
                $stmt->execute([$id_categoria]);
                if ($stmt->fetchColumn() == 0) {
                    throw new Exception('La categoría seleccionada no existe');
                }
                
                // Verificar duplicados
                if ($action === 'create') {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subcategorias WHERE nombre = ? AND id_categoria = ?");
                    $stmt->execute([$nombre, $id_categoria]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Ya existe una subcategoría con ese nombre en la categoría seleccionada');
                    }
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subcategorias WHERE nombre = ? AND id_categoria = ? AND id != ?");
                    $stmt->execute([$nombre, $id_categoria, $id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Ya existe una subcategoría con ese nombre en la categoría seleccionada');
                    }
                }
                
                if ($action === 'create') {
                    $stmt = $pdo->prepare("INSERT INTO subcategorias (nombre, descripcion, id_categoria, fecha_creacion) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$nombre, $descripcion, $id_categoria]);
                    $mensaje = 'Subcategoría creada exitosamente';
                } else {
                    $stmt = $pdo->prepare("UPDATE subcategorias SET nombre = ?, descripcion = ?, id_categoria = ?, fecha_actualizacion = NOW() WHERE id = ?");
                    $stmt->execute([$nombre, $descripcion, $id_categoria, $id]);
                    $mensaje = 'Subcategoría actualizada exitosamente';
                }
                
                if (isset($notificaciones)) {
                    $notificaciones->agregar($mensaje, 'success');
                }
                
                echo json_encode(['success' => true, 'message' => $mensaje]);
                exit;
                
            case 'delete':
                $id = intval($_POST['id'] ?? 0);
                
                if ($id <= 0) {
                    throw new Exception('ID inválido');
                }
                
                // Verificar si la subcategoría tiene productos asociados
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM productos WHERE id_subcategoria = ?");
                $stmt->execute([$id]);
                $productCount = $stmt->fetchColumn();
                
                if ($productCount > 0) {
                    throw new Exception('No se puede eliminar la subcategoría porque tiene ' . $productCount . ' producto(s) asociado(s)');
                }
                
                $stmt = $pdo->prepare("DELETE FROM subcategorias WHERE id = ?");
                $stmt->execute([$id]);
                
                $mensaje = 'Subcategoría eliminada exitosamente';
                if (isset($notificaciones)) {
                    $notificaciones->agregar($mensaje, 'success');
                }
                
                echo json_encode(['success' => true, 'message' => $mensaje]);
                exit;
                
            case 'get':
                $id = intval($_POST['id'] ?? 0);
                
                if ($id <= 0) {
                    throw new Exception('ID inválido');
                }
                
                $stmt = $pdo->prepare("SELECT * FROM subcategorias WHERE id = ?");
                $stmt->execute([$id]);
                $subcategoria = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$subcategoria) {
                    throw new Exception('Subcategoría no encontrada');
                }
                
                echo json_encode(['success' => true, 'data' => $subcategoria]);
                exit;
                
            default:
                throw new Exception('Acción no válida');
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
?>

<?php include 'includes/footer.php'; ?>
