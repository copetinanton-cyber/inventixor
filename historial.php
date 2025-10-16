<?php
session_start();
if (!isset($_SESSION['user']) || !in_array($_SESSION['rol'], ['admin', 'coordinador'])) {
    header('Location: index.php');
    exit;
}
require_once 'app/helpers/Database.php';
$db = new Database();

// Filtros
$filtro_entidad = isset($_GET['entidad']) ? $_GET['entidad'] : '';
$filtro_usuario = isset($_GET['usuario']) ? $_GET['usuario'] : '';
$filtro_rol = isset($_GET['rol']) ? $_GET['rol'] : '';

$sql = "SELECT * FROM HistorialCRUD WHERE 1=1";
$params = [];
if ($filtro_entidad) {
    $sql .= " AND entidad = ?";
    $params[] = $filtro_entidad;
}
if ($filtro_usuario) {
    $sql .= " AND usuario LIKE ?";
    $params[] = "%$filtro_usuario%";
}
if ($filtro_rol) {
    $sql .= " AND rol = ?";
    $params[] = $filtro_rol;
}
$sql .= " ORDER BY fecha DESC LIMIT 200";
$stmt = $db->conn->prepare($sql);
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$entidades = ['Producto','Categoria','Subcategoria','Usuario','Proveedor','Alerta','Reporte'];
$roles = ['admin','coordinador','auxiliar','usuario'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Movimientos y CRUD</title>
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="stylesheet" href="public/css/responsive-sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
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
                <a href="historial.php" class="menu-link active">
                    <i class="fas fa-history me-2"></i> Historial
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
                <a href="reportes_inteligentes.php" class="menu-link">
                    <i class="fas fa-brain me-2"></i> Reportes Inteligentes
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
                <a href="logout.php" class="menu-link">
                    <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
<div class="container mt-4">
    <h2 class="mb-3"><i class="fas fa-history me-2"></i>Historial de Movimientos y CRUD</h2>
    <form class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="entidad" class="form-select" onchange="this.form.submit()">
                <option value="">Todas las entidades</option>
                <?php foreach($entidades as $e): ?>
                <option value="<?= $e ?>" <?= $filtro_entidad==$e?'selected':'' ?>><?= $e ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" name="usuario" class="form-control" placeholder="Usuario" value="<?= htmlspecialchars($filtro_usuario) ?>">
        </div>
        <div class="col-md-3">
            <select name="rol" class="form-select" onchange="this.form.submit()">
                <option value="">Todos los roles</option>
                <?php foreach($roles as $r): ?>
                <option value="<?= $r ?>" <?= $filtro_rol==$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i>Filtrar</button>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Fecha</th>
                    <th>Entidad</th>
                    <th>ID</th>
                    <th>Acción</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($row['fecha'])) ?></td>
                    <td><?= htmlspecialchars($row['entidad']) ?></td>
                    <td><?= htmlspecialchars($row['id_entidad']) ?></td>
                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['accion']) ?></span></td>
                    <td><?= htmlspecialchars($row['usuario']) ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($row['rol']) ?></span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-dark" onclick="mostrarDetalles(this)" data-detalles='<?= htmlspecialchars($row['detalles']) ?>'>Ver</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
</div>
<script>
function mostrarDetalles(btn) {
    let detalles = btn.getAttribute('data-detalles');
    try {
        detalles = JSON.stringify(JSON.parse(detalles), null, 2);
    } catch(e) {}
    let modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `<div class='modal-dialog'><div class='modal-content'><div class='modal-header'><h5 class='modal-title'>Detalles</h5><button type='button' class='btn-close' data-bs-dismiss='modal'></button></div><div class='modal-body'><pre style='white-space:pre-wrap;'>${detalles}</pre></div></div></div>`;
    document.body.appendChild(modal);
    let bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    modal.addEventListener('hidden.bs.modal',()=>modal.remove());
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sistema Responsive -->
<script src="public/js/responsive-sidebar.js"></script>
<script>
    // Marcar como activo el menú de historial
    setActiveMenuItem('historial.php');
</script>
</body>
</html>
