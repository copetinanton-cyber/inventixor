<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

require_once 'app/controllers/SalidaControllerMejorado.php';
require_once 'app/helpers/Database.php';

$controller = new SalidaControllerMejorado();
$db = new Database();

// Procesar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'registrar_salida':
            $datos = [
                'id_prod' => intval($_POST['id_prod']),
                'cantidad' => intval($_POST['cantidad']),
                'tipo_salida' => $_POST['tipo_salida'],
                'observacion' => $_POST['observacion'] ?? '',
                'destino' => $_POST['destino'] ?? '',
                'cliente_info' => [
                    'nombre' => $_POST['cliente_nombre'] ?? '',
                    'telefono' => $_POST['cliente_telefono'] ?? '',
                    'direccion' => $_POST['cliente_direccion'] ?? ''
                ]
            ];
            
            if (!empty($_POST['con_garantia'])) {
                $datos['garantia'] = [
                    'tipo' => $_POST['garantia_tipo'] ?? 'tienda',
                    'duracion_meses' => intval($_POST['garantia_meses'] ?? 12),
                    'terminos' => $_POST['garantia_terminos'] ?? ''
                ];
            }
            
            echo json_encode($controller->registrarSalida($datos));
            exit;
            
        case 'actualizar_seguimiento':
            $datos = [
                'nuevo_estado' => $_POST['nuevo_estado'],
                'observaciones' => $_POST['observaciones'] ?? '',
                'usuario' => $_SESSION['user']['nombres'] ?? 'Usuario'
            ];
            echo json_encode($controller->actualizarSeguimiento($_POST['id_salida'], $datos));
            exit;
            
        case 'registrar_devolucion':
            // Procesar motivo - si es "otro", usar el detalle especificado
            $motivo = $_POST['motivo'];
            if ($motivo === 'otro' && !empty($_POST['motivo_otro_detalle'])) {
                $motivo = 'otro: ' . $_POST['motivo_otro_detalle'];
            }
            
            $datos = [
                'id_salida' => intval($_POST['id_salida']),
                'id_prod' => intval($_POST['id_prod']),
                'cantidad_devuelta' => intval($_POST['cantidad_devuelta']),
                'motivo' => $motivo,
                'condicion_producto' => $_POST['condicion_producto'],
                'accion' => $_POST['accion'],
                'observaciones' => $_POST['observaciones'] ?? '',
                'usuario_recibe' => $_SESSION['user']['nombres'] ?? 'Usuario'
            ];
            echo json_encode($controller->procesarDevolucion($datos));
            exit;
            
        case 'get_dashboard':
            echo json_encode($controller->getDashboardSalidas());
            exit;
    }
}

// Obtener datos para la vista
$filtros = [
    'producto' => $_GET['producto'] ?? '',
    'tipo_salida' => $_GET['tipo_salida'] ?? '',
    'estado' => $_GET['estado'] ?? '',
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? ''
];

$salidas_response = $controller->getSalidasFiltradas($filtros);
$salidas = $salidas_response['success'] ? $salidas_response['salidas'] : [];

// Obtener productos para el select
$productos_result = $db->conn->query("
    SELECT p.id_prod, p.nombre, p.modelo, p.talla, p.color, p.stock
    FROM Productos p 
    WHERE p.stock > 0 
    ORDER BY p.nombre ASC
");
$productos = $productos_result->fetch_all(MYSQLI_ASSOC);

// Obtener tipos de salida
$salidaModel = new SalidaMejorada($db);
$tipos_salida = $salidaModel->getTiposSalida();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión Avanzada de Salidas - InventiXor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="public/css/style.css" rel="stylesheet">
    <style>
        .estado-badge {
            font-size: 0.8em;
            padding: 0.4em 0.8em;
        }
        .seguimiento-timeline {
            max-height: 300px;
            overflow-y: auto;
        }
        .timeline-item {
            border-left: 2px solid #dee2e6;
            padding-left: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #007bff;
        }
        .card-salida {
            transition: all 0.3s ease;
            border-left: 4px solid #007bff;
        }
        .card-salida:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-boxes me-2"></i>InventiXor</h4>
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
                <a href="proveedores.php" class="menu-link">
                    <i class="fas fa-truck me-2"></i> Proveedores
                </a>
            </li>
            <li class="menu-item active">
                <a href="salidas_mejorado.php" class="menu-link">
                    <i class="fas fa-arrow-up me-2"></i> Salidas Avanzadas
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
            <?php if ($_SESSION['rol'] === 'admin'): ?>
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
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h2><i class="fas fa-arrow-up me-2"></i>Gestión Avanzada de Salidas</h2>
                    <p class="text-muted">Control completo del ciclo de vida de productos post-salida</p>
                </div>
            </div>

            <!-- Dashboard Cards -->
            <div class="row mb-4" id="dashboard-stats">
                <!-- Se llenarán dinámicamente -->
            </div>

            <!-- Tabs Navigation -->
            <ul class="nav nav-tabs mb-4" id="salidaTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="lista-tab" data-bs-toggle="tab" data-bs-target="#lista" type="button">
                        <i class="fas fa-list me-2"></i>Lista de Salidas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="nueva-tab" data-bs-toggle="tab" data-bs-target="#nueva" type="button">
                        <i class="fas fa-plus me-2"></i>Nueva Salida
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="transito-tab" data-bs-toggle="tab" data-bs-target="#transito" type="button">
                        <i class="fas fa-truck me-2"></i>En Tránsito
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="garantias-tab" data-bs-toggle="tab" data-bs-target="#garantias" type="button">
                        <i class="fas fa-shield-alt me-2"></i>Garantías
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="salidaTabContent">
                <!-- Lista de Salidas -->
                <div class="tab-pane fade show active" id="lista" role="tabpanel">
                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Producto</label>
                                    <input type="text" name="producto" class="form-control" 
                                           placeholder="Buscar producto..." value="<?= htmlspecialchars($filtros['producto']) ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Tipo de Salida</label>
                                    <select name="tipo_salida" class="form-select">
                                        <option value="">Todos</option>
                                        <?php foreach ($tipos_salida as $tipo): ?>
                                        <option value="<?= $tipo['codigo'] ?>" 
                                                <?= $filtros['tipo_salida'] === $tipo['codigo'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tipo['nombre']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Estado</label>
                                    <select name="estado" class="form-select">
                                        <option value="">Todos</option>
                                        <option value="preparando" <?= $filtros['estado'] === 'preparando' ? 'selected' : '' ?>>Preparando</option>
                                        <option value="enviado" <?= $filtros['estado'] === 'enviado' ? 'selected' : '' ?>>Enviado</option>
                                        <option value="en_transito" <?= $filtros['estado'] === 'en_transito' ? 'selected' : '' ?>>En Tránsito</option>
                                        <option value="entregado" <?= $filtros['estado'] === 'entregado' ? 'selected' : '' ?>>Entregado</option>
                                        <option value="devuelto" <?= $filtros['estado'] === 'devuelto' ? 'selected' : '' ?>>Devuelto</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Desde</label>
                                    <input type="date" name="fecha_desde" class="form-control" value="<?= $filtros['fecha_desde'] ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Hasta</label>
                                    <input type="date" name="fecha_hasta" class="form-control" value="<?= $filtros['fecha_hasta'] ?>">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary d-block">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Lista de Salidas -->
                    <div class="row" id="salidas-container">
                        <?php foreach ($salidas as $salida): ?>
                        <div class="col-lg-6 mb-3">
                            <div class="card card-salida">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="fas fa-box me-2"></i>
                                        <?= htmlspecialchars($salida['producto_nombre']) ?>
                                    </h6>
                                    <span class="badge estado-badge bg-<?= $this->getEstadoColor($salida['estado_seguimiento']) ?>">
                                        <?= ucfirst($salida['estado_seguimiento'] ?? 'Sin seguimiento') ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">Información Básica</small>
                                            <p class="mb-1"><strong>Cantidad:</strong> <?= $salida['cantidad'] ?></p>
                                            <p class="mb-1"><strong>Tipo:</strong> <?= ucfirst($salida['tipo_salida']) ?></p>
                                            <p class="mb-1"><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($salida['fecha_salida'])) ?></p>
                                            <?php if ($salida['tiempo_transcurrido']): ?>
                                            <p class="mb-1"><strong>Hace:</strong> <?= $salida['tiempo_transcurrido'] ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Estado y Garantía</small>
                                            <p class="mb-1"><strong>Estado:</strong> <?= ucfirst($salida['estado_salida']) ?></p>
                                            <p class="mb-1"><strong>Garantía:</strong> <?= $salida['estado_garantia'] ?></p>
                                            <?php if ($salida['fecha_entrega']): ?>
                                            <p class="mb-1"><strong>Entregado:</strong> <?= date('d/m/Y', strtotime($salida['fecha_entrega'])) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($salida['observacion'])): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">Observaciones:</small>
                                        <p class="mb-0"><?= htmlspecialchars($salida['observacion']) ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-outline-primary" onclick="verSeguimiento(<?= $salida['id_salida'] ?>)">
                                            <i class="fas fa-eye me-1"></i>Seguimiento
                                        </button>
                                        <button class="btn btn-outline-warning" onclick="actualizarEstado(<?= $salida['id_salida'] ?>)">
                                            <i class="fas fa-edit me-1"></i>Actualizar
                                        </button>
                                        <?php if ($salida['puede_devolver']): ?>
                                        <button class="btn btn-outline-danger" onclick="procesarDevolucion(<?= $salida['id_salida'] ?>, <?= $salida['id_prod'] ?>)">
                                            <i class="fas fa-undo me-1"></i>Devolver
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($salidas)): ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i>
                                No se encontraron salidas con los filtros aplicados.
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Nueva Salida -->
                <div class="tab-pane fade" id="nueva" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-plus me-2"></i>Registrar Nueva Salida</h5>
                        </div>
                        <div class="card-body">
                            <form id="form-nueva-salida">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Producto *</label>
                                            <select name="id_prod" class="form-select" required>
                                                <option value="">Seleccionar producto...</option>
                                                <?php foreach ($productos as $producto): ?>
                                                <option value="<?= $producto['id_prod'] ?>" data-stock="<?= $producto['stock'] ?>">
                                                    <?= htmlspecialchars($producto['nombre']) ?> 
                                                    (<?= $producto['modelo'] ?> - <?= $producto['talla'] ?> - <?= $producto['color'] ?>) 
                                                    - Stock: <?= $producto['stock'] ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Cantidad *</label>
                                            <input type="number" name="cantidad" class="form-control" min="1" required>
                                            <div class="form-text">Stock disponible: <span id="stock-disponible">0</span></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label class="form-label">Tipo de Salida *</label>
                                            <select name="tipo_salida" class="form-select" required>
                                                <?php foreach ($tipos_salida as $tipo): ?>
                                                <option value="<?= $tipo['codigo'] ?>" data-seguimiento="<?= $tipo['requiere_seguimiento'] ? '1' : '0' ?>">
                                                    <?= htmlspecialchars($tipo['nombre']) ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Observaciones</label>
                                            <textarea name="observacion" class="form-control" rows="2" 
                                                      placeholder="Detalles adicionales sobre la salida..."></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información del Cliente (se muestra para ventas) -->
                                <div id="info-cliente" class="d-none">
                                    <h6><i class="fas fa-user me-2"></i>Información del Cliente</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Nombre</label>
                                                <input type="text" name="cliente_nombre" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Teléfono</label>
                                                <input type="tel" name="cliente_telefono" class="form-control">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Destino</label>
                                                <input type="text" name="destino" class="form-control" placeholder="Dirección de entrega">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Garantía -->
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="con_garantia" name="con_garantia">
                                        <label class="form-check-label" for="con_garantia">
                                            Incluir garantía
                                        </label>
                                    </div>
                                </div>

                                <div id="info-garantia" class="d-none">
                                    <h6><i class="fas fa-shield-alt me-2"></i>Información de Garantía</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Tipo</label>
                                                <select name="garantia_tipo" class="form-select">
                                                    <option value="tienda">Garantía de Tienda</option>
                                                    <option value="fabricante">Garantía del Fabricante</option>
                                                    <option value="extendida">Garantía Extendida</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Duración (meses)</label>
                                                <input type="number" name="garantia_meses" class="form-control" value="12" min="1" max="60">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Términos</label>
                                                <textarea name="garantia_terminos" class="form-control" rows="2" 
                                                          placeholder="Condiciones de la garantía..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Registrar Salida
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Productos en Tránsito -->
                <div class="tab-pane fade" id="transito" role="tabpanel">
                    <div id="productos-transito">
                        <!-- Se cargarán dinámicamente -->
                    </div>
                </div>

                <!-- Garantías Activas -->
                <div class="tab-pane fade" id="garantias" role="tabpanel">
                    <div id="garantias-activas">
                        <!-- Se cargarán dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales -->
    <?php include 'modales_salidas.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/salidas-mejorado.js"></script>
</body>
</html>

<?php
function getEstadoColor($estado) {
    $colores = [
        'preparando' => 'warning',
        'enviado' => 'info', 
        'en_transito' => 'primary',
        'entregado' => 'success',
        'devuelto' => 'secondary',
        'perdido' => 'danger',
        'dañado' => 'danger'
    ];
    return $colores[$estado] ?? 'secondary';
}
?>