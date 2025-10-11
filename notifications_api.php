<?php
/**
 * API de Notificaciones Automáticas en Tiempo Real - InventiXor
 * 
 * Este endpoint maneja las solicitudes AJAX para:
 * - Obtener notificaciones pendientes para el usuario
 * - Marcar notificaciones como leídas
 * - Obtener estadísticas de notificaciones
 * - Configurar preferencias de notificación
 * 
 * Uso: notifications_api.php?action=get_pending&user_doc=123456789
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar sesión
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado. Sesión requerida.', 'code' => 'UNAUTHORIZED']);
    exit();
}

require_once __DIR__ . '/app/helpers/Database.php';
require_once __DIR__ . '/app/helpers/SistemaNotificaciones.php';

try {
    $db = new Database();
    $usuario_actual = $_SESSION['user'];
    $sistema_notificaciones = new SistemaNotificaciones($db, $usuario_actual);
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_pending':
            // Obtener notificaciones pendientes para el usuario
            $user_doc = $_GET['user_doc'] ?? $usuario_actual['num_doc'] ?? '';
            $limit = intval($_GET['limit'] ?? 10);
            
            if (empty($user_doc)) {
                throw new Exception('Número de documento de usuario requerido');
            }
            
            $notificaciones = $sistema_notificaciones->obtenerNotificacionesPendientes($user_doc, $limit);
            
            echo json_encode([
                'success' => true,
                'data' => $notificaciones,
                'count' => count($notificaciones),
                'timestamp' => time()
            ]);
            break;
            
        case 'mark_as_read':
            // Marcar una notificación como leída
            $notification_id = intval($_POST['notification_id'] ?? 0);
            $user_doc = $_POST['user_doc'] ?? $usuario_actual['num_doc'] ?? '';
            
            if ($notification_id <= 0 || empty($user_doc)) {
                throw new Exception('ID de notificación y documento de usuario requeridos');
            }
            
            $ip_usuario = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
            $resultado = $sistema_notificaciones->marcarComoVista($notification_id, $user_doc, $ip_usuario);
            
            echo json_encode([
                'success' => $resultado,
                'message' => $resultado ? 'Notificación marcada como leída' : 'Error al marcar notificación',
                'timestamp' => time()
            ]);
            break;
            
        case 'mark_all_as_read':
            // Marcar todas las notificaciones pendientes como leídas
            $user_doc = $_POST['user_doc'] ?? $usuario_actual['num_doc'] ?? '';
            
            if (empty($user_doc)) {
                throw new Exception('Documento de usuario requerido');
            }
            
            // Obtener todas las pendientes y marcarlas
            $notificaciones_pendientes = $sistema_notificaciones->obtenerNotificacionesPendientes($user_doc, 100);
            $marcadas = 0;
            
            foreach ($notificaciones_pendientes as $notif) {
                if ($sistema_notificaciones->marcarComoVista($notif['id_notificacion'], $user_doc)) {
                    $marcadas++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => "$marcadas notificaciones marcadas como leídas",
                'marked_count' => $marcadas,
                'timestamp' => time()
            ]);
            break;
            
        case 'get_stats':
            // Obtener estadísticas de notificaciones
            $stats = $sistema_notificaciones->obtenerEstadisticas();
            
            echo json_encode([
                'success' => true,
                'data' => $stats,
                'timestamp' => time()
            ]);
            break;
            
        case 'get_config':
            // Obtener configuración de tipos de notificaciones
            $sql = "SELECT tipo_evento, habilitado, nivel_prioridad, icono, color, duracion_horas, umbral_stock_bajo
                    FROM ConfigNotificaciones 
                    ORDER BY tipo_evento";
            
            $result = $db->conn->query($sql);
            $config = [];
            
            while ($row = $result->fetch_assoc()) {
                $config[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $config,
                'timestamp' => time()
            ]);
            break;
            
        case 'update_config':
            // Actualizar configuración (solo para administradores)
            if (!isset($usuario_actual['rol']) || $usuario_actual['rol'] !== 'admin') {
                throw new Exception('Solo los administradores pueden modificar la configuración');
            }
            
            $tipo_evento = $_POST['tipo_evento'] ?? '';
            $habilitado = isset($_POST['habilitado']) ? (bool)$_POST['habilitado'] : true;
            $umbral_stock_bajo = intval($_POST['umbral_stock_bajo'] ?? 10);
            
            if (empty($tipo_evento)) {
                throw new Exception('Tipo de evento requerido');
            }
            
            $sql = "UPDATE ConfigNotificaciones 
                    SET habilitado = ?, umbral_stock_bajo = ? 
                    WHERE tipo_evento = ?";
            
            $stmt = $db->conn->prepare($sql);
            $stmt->bind_param('iis', $habilitado, $umbral_stock_bajo, $tipo_evento);
            $resultado = $stmt->execute();
            $stmt->close();
            
            echo json_encode([
                'success' => $resultado,
                'message' => $resultado ? 'Configuración actualizada' : 'Error al actualizar configuración',
                'timestamp' => time()
            ]);
            break;
            
        case 'test_notification':
            // Crear notificación de prueba (solo para desarrollo/administradores)
            if (!isset($usuario_actual['rol']) || !in_array($usuario_actual['rol'], ['admin', 'coordinador'])) {
                throw new Exception('Permisos insuficientes para crear notificaciones de prueba');
            }
            
            $tipo = $_POST['tipo'] ?? 'info';
            $titulo = $_POST['titulo'] ?? 'Notificación de Prueba';
            $mensaje = $_POST['mensaje'] ?? 'Esta es una notificación de prueba del sistema InventiXor';
            
            // Crear notificación manual
            $sql = "INSERT INTO NotificacionesSistema (tipo_evento, titulo, mensaje, nivel_prioridad, icono, color, creado_por) 
                    VALUES ('test_notification', ?, ?, 'baja', 'fas fa-vial', ?, ?)";
            
            $stmt = $db->conn->prepare($sql);
            $color = $tipo === 'error' ? 'error' : ($tipo === 'warning' ? 'warning' : 'info');
            $creador = $usuario_actual['nombres'] ?? 'Test';
            
            $stmt->bind_param('ssss', $titulo, $mensaje, $color, $creador);
            $resultado = $stmt->execute();
            $id_notificacion = $db->conn->insert_id;
            $stmt->close();
            
            echo json_encode([
                'success' => $resultado,
                'message' => 'Notificación de prueba creada',
                'notification_id' => $id_notificacion,
                'timestamp' => time()
            ]);
            break;
            
        case 'check_stock':
            // Verificar stock bajo manualmente
            if (!isset($usuario_actual['rol']) || !in_array($usuario_actual['rol'], ['admin', 'coordinador'])) {
                throw new Exception('Permisos insuficientes para verificar stock');
            }
            
            $productos_notificados = $sistema_notificaciones->verificarStockBajo();
            
            echo json_encode([
                'success' => true,
                'message' => "Verificación completada. $productos_notificados productos con stock bajo detectados",
                'products_notified' => $productos_notificados,
                'timestamp' => time()
            ]);
            break;
            
        case 'clean_old':
            // Limpiar notificaciones antiguas
            if (!isset($usuario_actual['rol']) || $usuario_actual['rol'] !== 'admin') {
                throw new Exception('Solo los administradores pueden limpiar notificaciones');
            }
            
            $resultado = $sistema_notificaciones->limpiarNotificacionesAntiguas();
            
            echo json_encode([
                'success' => $resultado,
                'message' => $resultado ? 'Notificaciones antiguas eliminadas' : 'Error al limpiar notificaciones',
                'timestamp' => time()
            ]);
            break;
            
        case 'get_recent':
            // Obtener notificaciones recientes (últimas 24 horas)
            $sql = "SELECT * FROM NotificacionesActivas 
                    WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    ORDER BY fecha_creacion DESC 
                    LIMIT 20";
            
            $result = $db->conn->query($sql);
            $notificaciones = [];
            
            while ($row = $result->fetch_assoc()) {
                if ($row['datos_evento']) {
                    $row['datos_evento'] = json_decode($row['datos_evento'], true);
                }
                $notificaciones[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $notificaciones,
                'count' => count($notificaciones),
                'timestamp' => time()
            ]);
            break;
            
        case 'heartbeat':
            // Verificar conexión y sesión
            echo json_encode([
                'success' => true,
                'message' => 'Conexión activa',
                'user' => [
                    'doc' => $usuario_actual['num_doc'] ?? '',
                    'name' => $usuario_actual['nombres'] ?? '',
                    'role' => $usuario_actual['rol'] ?? ''
                ],
                'timestamp' => time(),
                'server_time' => date('Y-m-d H:i:s')
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => time()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'debug' => $e->getMessage(),
        'timestamp' => time()
    ]);
}
?>

<?php
/**
 * Documentación de la API
 * 
 * GET  /notifications_api.php?action=get_pending&user_doc=123456&limit=10
 *      - Obtiene notificaciones pendientes para el usuario
 *      - Parámetros: user_doc (requerido), limit (opcional, default 10)
 * 
 * POST /notifications_api.php
 *      action=mark_as_read&notification_id=123&user_doc=456789
 *      - Marca una notificación como leída
 * 
 * POST /notifications_api.php
 *      action=mark_all_as_read&user_doc=456789
 *      - Marca todas las notificaciones pendientes como leídas
 * 
 * GET  /notifications_api.php?action=get_stats
 *      - Obtiene estadísticas de notificaciones
 * 
 * GET  /notifications_api.php?action=get_config
 *      - Obtiene configuración de tipos de notificaciones
 * 
 * POST /notifications_api.php (Solo admin)
 *      action=update_config&tipo_evento=stock_bajo&habilitado=1&umbral_stock_bajo=15
 *      - Actualiza configuración de notificaciones
 * 
 * POST /notifications_api.php (Admin/Coordinador)
 *      action=test_notification&tipo=info&titulo=Test&mensaje=Prueba
 *      - Crea notificación de prueba
 * 
 * POST /notifications_api.php (Admin/Coordinador)
 *      action=check_stock
 *      - Verifica stock bajo manualmente
 * 
 * POST /notifications_api.php (Solo admin)
 *      action=clean_old
 *      - Limpia notificaciones antiguas
 * 
 * GET  /notifications_api.php?action=get_recent
 *      - Obtiene notificaciones recientes (24h)
 * 
 * GET  /notifications_api.php?action=heartbeat
 *      - Verifica conexión y estado de sesión
 * 
 * Todas las respuestas incluyen:
 * - success: boolean
 * - timestamp: Unix timestamp
 * - data/message/error según corresponda
 */
?>