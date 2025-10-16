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
$pdo = $db->getConnection();
$notificaciones = new SistemaNotificaciones($db);

// Asegurar que $_SESSION['rol'] esté definido
if (!isset($_SESSION['rol'])) {
    if (isset($_SESSION['user']['num_doc'])) {
        $num_doc = $_SESSION['user']['num_doc'];
        $stmt = $pdo->prepare("SELECT rol FROM users WHERE num_doc = ?");
        $stmt->execute([$num_doc]);
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
            $_SESSION['rol'] = $row['rol'];
        } else {
            $_SESSION['rol'] = '';
        }
    } else {
        $_SESSION['rol'] = '';
    }
}

// Obtener estadísticas para el dashboard
try {
    // Total de subcategorías
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM subcategorias");
    $row = $stmt->fetch_assoc();
    $totalSubcategorias = $row ? $row['total'] : 0;
    
    // Categorías con subcategorías
    $stmt = $pdo->query("SELECT COUNT(DISTINCT id_categoria) as total FROM subcategorias");
    $row = $stmt->fetch_assoc();
    $categoriasActivas = $row ? $row['total'] : 0;
    
    // Total de productos en subcategorías
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM productos WHERE id_subcategoria IS NOT NULL");
    $row = $stmt->fetch_assoc();
    $productosAsociados = $row ? $row['total'] : 0;
    
    // Obtener categorías para el formulario
    $stmt = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre");
    $categorias = $stmt->fetch_all(MYSQLI_ASSOC);
    
    // Obtener subcategorías con información de categoría
    $stmt = $pdo->query("
        SELECT s.*, c.nombre as categoria_nombre, 
               COUNT(p.id) as productos_count
        FROM subcategorias s
        LEFT JOIN categorias c ON s.id_categoria = c.id
        LEFT JOIN productos p ON s.id = p.id_subcategoria
        GROUP BY s.id, s.nombre, s.descripcion, s.id_categoria, c.nombre
        ORDER BY s.nombre
    ");
    $subcategorias = $stmt->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $totalSubcategorias = 0;
    $categoriasActivas = 0;
    $productosAsociados = 0;
    $categorias = [];
    $subcategorias = [];
}

// Configuración de la página responsiva
$config = [
    'MODULE_TITLE' => 'Subcategorías',
    'MODULE_DESCRIPTION' => 'Gestión de subcategorías del sistema Inventixor',
    'MODULE_ICON' => 'fas fa-tag',
    'MODULE_SUBTITLE' => 'Administrar subcategorías de productos',
    'SUBCATEGORIAS_ACTIVE' => 'bg-primary text-white', // Bootstrap
    'ADDITIONAL_STYLES' => ResponsivePageHelper::getModuleStyles('subcategorias'),
    'MODULE_CONTENT' => ''
];

// Capturar el contenido del módulo
ob_start();

?>
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="mb-1"><i class="fas fa-tag me-2"></i>Subcategorías</h2>
            <p class="text-muted">Gestión de subcategorías del sistema Inventixor</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalSubcategoria">
                <i class="fas fa-plus me-1"></i> Nueva subcategoría
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form class="row g-2" id="form-filtros">
                <div class="col-md-4">
                    <select class="form-select" id="filtro-categoria">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" id="buscar-subcategoria" placeholder="Buscar subcategoría...">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="button" class="btn btn-primary" onclick="aplicarFiltros()">
                        <i class="fas fa-search me-1"></i> Buscar
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="limpiarFiltros()">
                        <i class="fas fa-times me-1"></i> Limpiar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover" id="tabla-subcategorias">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th>Productos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subcategorias as $i => $subcat): ?>
                        <tr data-categoria-id="<?= $subcat['id_categoria'] ?>">
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($subcat['nombre']) ?></td>
                            <td><?= htmlspecialchars($subcat['categoria_nombre']) ?></td>
                            <td><?= htmlspecialchars($subcat['descripcion']) ?></td>
                            <td><?= $subcat['productos_count'] ?></td>
                            <td>
                                <button class="btn btn-sm btn-info me-1" onclick="verSubcategoria(<?= $subcat['id'] ?>)"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-sm btn-warning me-1" onclick="editarSubcategoria(<?= $subcat['id'] ?>)"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger" onclick="confirmarEliminacion(<?= $subcat['id'] ?>, '<?= htmlspecialchars($subcat['nombre']) ?>', <?= $subcat['productos_count'] ?>)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Subcategoría -->
<div class="modal fade" id="modalSubcategoria" tabindex="-1" aria-labelledby="modalSubcategoriaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-subcategoria">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSubcategoriaLabel">Nueva subcategoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="subcategoria-id" name="id">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="id_categoria" class="form-label">Categoría</label>
                        <select class="form-select" id="id_categoria" name="id_categoria" required>
                            <option value="">Seleccione una categoría</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php
$config['MODULE_CONTENT'] = ob_get_clean();
echo ResponsivePageHelper::generatePage($config);
?>
<script>
function confirmarEliminacion(id, nombre, productosCount) {
    let mensaje = `¿Estás seguro de que deseas eliminar la subcategoría "${nombre}"?`;
    if (productosCount > 0) {
        NotificationSystem.show(`No se puede eliminar la subcategoría "${nombre}" porque tiene ${productosCount} producto(s) asociado(s).`, 'warning');
        return;
    }
    if (confirm(mensaje)) {
        procesarEliminacion(id);
    }
}

async function procesarEliminacion(id) {
    try {
        const response = await fetch('subcategorias.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&id=${id}`
        });
        const result = await response.json();
        if (result.success) {
            NotificationSystem.show(result.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            NotificationSystem.show(result.error || 'Error al eliminar', 'error');
        }
    } catch (error) {
        NotificationSystem.show('Error al procesar la solicitud: ' + error.message, 'error');
    }
}

document.getElementById('form-subcategoria').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const id = document.getElementById('subcategoria-id').value;
    const action = id ? 'update' : 'create';
    formData.append('action', action);
    if (id) {
        formData.append('id', id);
    }
    try {
        const response = await fetch('subcategorias.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            NotificationSystem.show(result.message, 'success');
            cerrarModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            NotificationSystem.show(result.error || 'Error al guardar', 'error');
        }
    } catch (error) {
        NotificationSystem.show('Error al procesar la solicitud: ' + error.message, 'error');
    }
});

function aplicarFiltros() {
    const categoriaFiltro = document.getElementById('filtro-categoria').value;
    const busqueda = document.getElementById('buscar-subcategoria').value.toLowerCase();
    const filas = document.querySelectorAll('#tabla-subcategorias tr');
    filas.forEach(fila => {
        if (fila.querySelector('td')) {
            const categoriaId = fila.getAttribute('data-categoria-id');
            const nombre = fila.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const coincideCategoria = !categoriaFiltro || categoriaId === categoriaFiltro;
            const coincideBusqueda = !busqueda || nombre.includes(busqueda);
            if (coincideCategoria && coincideBusqueda) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        }
    });
}

function limpiarFiltros() {
    document.getElementById('filtro-categoria').value = '';
    document.getElementById('buscar-subcategoria').value = '';
    aplicarFiltros();
}

document.getElementById('filtro-categoria').addEventListener('change', aplicarFiltros);
document.getElementById('buscar-subcategoria').addEventListener('input', aplicarFiltros);

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModal();
        cerrarModalVer();
    }
});
</script>

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
                
                echo json_encode(['success' => true, 'message' => 'Subcategoría eliminada exitosamente']);
                exit;
                
            case 'get':
                $id = intval($_POST['id'] ?? 0);
                
                if ($id <= 0) {
                    throw new Exception('ID inválido');
                }
                
                $stmt = $pdo->prepare("
                    SELECT s.*, c.nombre as categoria_nombre
                    FROM subcategorias s
                    LEFT JOIN categorias c ON s.id_categoria = c.id
                    WHERE s.id = ?
                ");
                $stmt->execute([$id]);
                $subcategoria = $stmt->fetch();
                
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