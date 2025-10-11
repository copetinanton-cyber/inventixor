<?php
require_once 'app/helpers/Database.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

// Inicializar $_SESSION['rol'] si no existe
if (!isset($_SESSION['rol'])) {
    if (isset($_SESSION['user']['rol'])) {
        $_SESSION['rol'] = $_SESSION['user']['rol'];
    } else {
        $_SESSION['rol'] = '';
    }
}

$db = new Database();

// Obtener estadísticas del inventario para respuestas contextuales
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM Productos) as total_productos,
                (SELECT COUNT(*) FROM Categoria) as total_categorias,
                (SELECT COUNT(*) FROM Subcategoria) as total_subcategorias,
                (SELECT COUNT(*) FROM Proveedores) as total_proveedores,
                (SELECT COUNT(*) FROM Salidas) as total_salidas,
                (SELECT COUNT(*) FROM Alertas WHERE estado = 'Activa') as alertas_activas,
                (SELECT COUNT(*) FROM users) as total_usuarios,
                (SELECT SUM(stock) FROM Productos) as stock_total";
$stats_result = $db->conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Manejar consulta AJAX del chat
if (isset($_POST['action']) && $_POST['action'] === 'chat') {
    header('Content-Type: application/json');
    $pregunta = strtolower(trim($_POST['pregunta']));
    
    // Sistema de respuestas inteligentes basado en datos reales
    $respuesta = generarRespuesta($pregunta, $stats, $db, $_SESSION);
    
    echo json_encode(['respuesta' => $respuesta]);
    exit;
}

function generarRespuesta($pregunta, $stats, $db, $session) {
    $rol = $session['rol'];
    $usuario = $session['user']['nombres'] ?? 'Usuario';
    
    // CONSULTAS ESPECÍFICAS DE CALZADO Y TIENDA DE ZAPATOS
    
    // Productos y marcas
    if (strpos($pregunta, 'nike') !== false || strpos($pregunta, 'adidas') !== false || strpos($pregunta, 'puma') !== false) {
        $marca = '';
        if (strpos($pregunta, 'nike') !== false) $marca = 'Nike';
        if (strpos($pregunta, 'adidas') !== false) $marca = 'Adidas';  
        if (strpos($pregunta, 'puma') !== false) $marca = 'Puma';
        
        $productos = $db->conn->query("SELECT COUNT(*) as total, SUM(stock) as stock FROM productos p 
            JOIN subcategoria s ON p.id_subcg = s.id_subcg WHERE s.nombre LIKE '%$marca%'");
        $datos = $productos->fetch_assoc();
        
        if ($datos['total'] > 0) {
            return "Tenemos <strong>{$datos['total']} productos de $marca</strong> con un stock total de <strong>{$datos['stock']} unidades</strong>. $marca es una de nuestras marcas deportivas más populares. Consulta el módulo <em>Productos</em> para ver modelos específicos.";
        } else {
            return "Actualmente no tenemos productos de $marca en stock. Considera contactar proveedores para reabastecer esta marca popular.";
        }
    }
    
    // Consultas por tallas
    if (strpos($pregunta, 'talla') !== false || strpos($pregunta, 'tallas') !== false || strpos($pregunta, 'número') !== false || strpos($pregunta, 'números') !== false) {
        $tallas = $db->conn->query("SELECT talla, COUNT(*) as cantidad, SUM(stock) as stock_total 
            FROM productos WHERE talla IS NOT NULL AND talla != '' 
            GROUP BY talla ORDER BY talla");
        
        $respuesta = "📏 <strong>Distribución de tallas en inventario:</strong><br><br>";
        while($talla = $tallas->fetch_assoc()) {
            $respuesta .= "• Talla <strong>{$talla['talla']}</strong>: {$talla['cantidad']} modelos ({$talla['stock_total']} unidades)<br>";
        }
        $respuesta .= "<br>💡 <em>Tip:</em> Las tallas más vendidas suelen ser 38-42. Mantén stock adecuado en estos números.";
        return $respuesta;
    }
    
    // Consultas por colores
    if (strpos($pregunta, 'color') !== false || strpos($pregunta, 'colores') !== false) {
        $colores = $db->conn->query("SELECT color, COUNT(*) as cantidad, SUM(stock) as stock_total 
            FROM productos WHERE color IS NOT NULL AND color != '' 
            GROUP BY color ORDER BY cantidad DESC LIMIT 8");
        
        $respuesta = "🎨 <strong>Colores disponibles en inventario:</strong><br><br>";
        while($color = $colores->fetch_assoc()) {
            $respuesta .= "• <strong>{$color['color']}</strong>: {$color['cantidad']} modelos ({$color['stock_total']} unidades)<br>";
        }
        $respuesta .= "<br>💡 <em>Tip:</em> Negro y blanco son colores clásicos de alta rotación. Café y beige son populares en calzado formal.";
        return $respuesta;
    }
    
    // Consultas por categorías específicas de calzado
    if (strpos($pregunta, 'deportivo') !== false || strpos($pregunta, 'deportivos') !== false || strpos($pregunta, 'tenis') !== false) {
        $deportivos = $db->conn->query("SELECT COUNT(*) as total, SUM(stock) as stock FROM productos p 
            JOIN subcategoria s ON p.id_subcg = s.id_subcg 
            JOIN categoria c ON s.id_categ = c.id_categ 
            WHERE c.nombre LIKE '%Deportivo%' OR p.nombre LIKE '%tenis%'");
        $datos = $deportivos->fetch_assoc();
        
        return "👟 Tenemos <strong>{$datos['total']} productos deportivos</strong> con <strong>{$datos['stock']} unidades</strong> en stock. Incluye marcas como Nike, Adidas, Puma, etc. El calzado deportivo tiene alta rotación, especialmente en temporadas escolares y deportivas.";
    }
    
    if (strpos($pregunta, 'formal') !== false || strpos($pregunta, 'elegante') !== false || strpos($pregunta, 'oficina') !== false) {
        $formales = $db->conn->query("SELECT COUNT(*) as total, SUM(stock) as stock FROM productos p 
            JOIN subcategoria s ON p.id_subcg = s.id_subcg 
            JOIN categoria c ON s.id_categ = c.id_categ 
            WHERE c.nombre LIKE '%Formal%'");
        $datos = $formales->fetch_assoc();
        
        return "👔 Tenemos <strong>{$datos['total']} productos formales</strong> con <strong>{$datos['stock']} unidades</strong>. El calzado formal es ideal para ejecutivos y ocasiones especiales. Colores populares: negro, café, vino tinto.";
    }
    
    if (strpos($pregunta, 'infantil') !== false || strpos($pregunta, 'niños') !== false || strpos($pregunta, 'niñas') !== false) {
        $infantiles = $db->conn->query("SELECT COUNT(*) as total, SUM(stock) as stock FROM productos p 
            JOIN subcategoria s ON p.id_subcg = s.id_subcg 
            JOIN categoria c ON s.id_categ = c.id_categ 
            WHERE c.nombre LIKE '%Infantil%'");
        $datos = $infantiles->fetch_assoc();
        
        return "👶 Tenemos <strong>{$datos['total']} productos infantiles</strong> con <strong>{$datos['stock']} unidades</strong>. El calzado infantil requiere reposición frecuente por crecimiento. Tallas más vendidas: 22-35.";
    }
    
    if (strpos($pregunta, 'escolar') !== false || strpos($pregunta, 'colegio') !== false || strpos($pregunta, 'estudiante') !== false) {
        $escolares = $db->conn->query("SELECT COUNT(*) as total, SUM(stock) as stock FROM productos p 
            JOIN subcategoria s ON p.id_subcg = s.id_subcg 
            JOIN categoria c ON s.id_categ = c.id_categ 
            WHERE c.nombre LIKE '%Escolar%'");
        $datos = $escolares->fetch_assoc();
        
        return "🎒 Tenemos <strong>{$datos['total']} productos escolares</strong> con <strong>{$datos['stock']} unidades</strong>. Mayor demanda en enero-febrero y julio-agosto. Colores requeridos: negro principalmente, algunos colegios permiten café.";
    }
    
    // Stock bajo y alertas críticas
    if (strpos($pregunta, 'stock bajo') !== false || strpos($pregunta, 'crítico') !== false || strpos($pregunta, 'agotar') !== false) {
        $criticos = $db->conn->query("SELECT p.nombre, p.stock, s.nombre as marca 
            FROM productos p 
            JOIN subcategoria s ON p.id_subcg = s.id_subcg 
            WHERE p.stock <= 5 ORDER BY p.stock ASC LIMIT 5");
        
        $respuesta = "⚠️ <strong>Productos con stock crítico (≤5 unidades):</strong><br><br>";
        while($producto = $criticos->fetch_assoc()) {
            $respuesta .= "• <strong>{$producto['marca']} - {$producto['nombre']}</strong>: {$producto['stock']} unidades<br>";
        }
        $respuesta .= "<br>🚨 <strong>Acción requerida:</strong> Contacta proveedores para reabastecer estos productos críticos.";
        return $respuesta;
    }
    
    // Análisis de ventas y tendencias
    if (strpos($pregunta, 'ventas') !== false || strpos($pregunta, 'vendido') !== false || strpos($pregunta, 'popular') !== false) {
        $populares = $db->conn->query("SELECT p.nombre, s.nombre as marca, SUM(sa.cantidad) as total_vendido 
            FROM salidas sa 
            JOIN productos p ON sa.id_prod = p.id_prod 
            JOIN subcategoria s ON p.id_subcg = s.id_subcg 
            WHERE sa.tipo_salida = 'Venta' 
            GROUP BY p.id_prod 
            ORDER BY total_vendido DESC LIMIT 5");
        
        $respuesta = "📊 <strong>Productos más vendidos:</strong><br><br>";
        while($producto = $populares->fetch_assoc()) {
            $respuesta .= "• <strong>{$producto['marca']} - {$producto['nombre']}</strong>: {$producto['total_vendido']} unidades vendidas<br>";
        }
        $respuesta .= "<br>💡 <em>Tip:</em> Mantén stock extra de estos productos populares.";
        return $respuesta;
    }
    
    // Proveedores especializados
    if (strpos($pregunta, 'proveedor') !== false || strpos($pregunta, 'distribuidor') !== false) {
        $proveedores_activos = $db->conn->query("SELECT pr.razon_social, COUNT(p.id_prod) as productos 
            FROM proveedores pr 
            LEFT JOIN productos p ON pr.id_nit = p.id_nit 
            WHERE pr.estado = 'Activo' 
            GROUP BY pr.id_nit 
            ORDER BY productos DESC LIMIT 5");
        
        $respuesta = "🏢 <strong>Proveedores activos con más productos:</strong><br><br>";
        while($proveedor = $proveedores_activos->fetch_assoc()) {
            $respuesta .= "• <strong>{$proveedor['razon_social']}</strong>: {$proveedor['productos']} productos<br>";
        }
        $respuesta .= "<br>Gestiona relaciones con proveedores desde el módulo <em>Proveedores</em>.";
        return $respuesta;
    }
    
    // Consultas estacionales y recomendaciones
    if (strpos($pregunta, 'temporada') !== false || strpos($pregunta, 'estación') !== false || strpos($pregunta, 'época') !== false) {
        $mes_actual = date('n');
        $temporada = '';
        $recomendacion = '';
        
        if ($mes_actual >= 12 || $mes_actual <= 2) {
            $temporada = 'Fin de año y inicio escolar';
            $recomendacion = 'Focalízate en calzado escolar y deportivo. Alta demanda de zapatos negros para colegios.';
        } elseif ($mes_actual >= 3 && $mes_actual <= 5) {
            $temporada = 'Primer semestre';
            $recomendacion = 'Temporada ideal para calzado casual y deportivo. Prepárate para el Día de la Madre.';
        } elseif ($mes_actual >= 6 && $mes_actual <= 8) {
            $temporada = 'Mitad de año y vacaciones';
            $recomendacion = 'Enfócate en calzado escolar para segundo semestre y sandalias para vacaciones.';
        } else {
            $temporada = 'Final de año';
            $recomendacion = 'Temporada alta para calzado formal (fiestas navideñas) y deportivo (regalos).';
        }
        
        return "📅 <strong>Temporada actual:</strong> $temporada<br><br>💡 <strong>Recomendación:</strong> $recomendacion<br><br>Ajusta tu inventario según las tendencias estacionales para maximizar ventas.";
    }
    
    // RESPUESTAS ORIGINALES MEJORADAS
    if (strpos($pregunta, 'productos') !== false || strpos($pregunta, 'inventario') !== false) {
        $stock_promedio = round($stats['stock_total'] / $stats['total_productos'], 1);
        return "📦 Tienes <strong>{$stats['total_productos']} productos</strong> con <strong>{$stats['stock_total']} unidades</strong> totales (promedio: $stock_promedio unidades/producto). Usa filtros avanzados en el módulo <em>Productos</em> para consultas específicas por marca, talla, color o categoría.";
    }
    
    if (strpos($pregunta, 'categorias') !== false || strpos($pregunta, 'categorías') !== false) {
        return "📂 Tienes <strong>{$stats['total_categorias']} categorías</strong> y <strong>{$stats['total_subcategorias']} marcas</strong> organizando tu inventario de calzado. Cada categoría representa un tipo de calzado (deportivo, formal, infantil, etc.) y cada subcategoría una marca específica.";
    }
    
    if (strpos($pregunta, 'alertas') !== false || strpos($pregunta, 'notificaciones') !== false) {
        $nivel_alerta = '';
        if ($stats['alertas_activas'] == 0) {
            $nivel_alerta = '✅ Excelente, no hay alertas críticas.';
        } elseif ($stats['alertas_activas'] <= 3) {
            $nivel_alerta = '⚠️ Nivel moderado de alertas.';
        } else {
            $nivel_alerta = '🚨 Nivel alto de alertas - Requiere atención inmediata.';
        }
        
        return "🔔 Tienes <strong>{$stats['alertas_activas']} alertas activas</strong>. $nivel_alerta Las alertas automáticas te ayudan a mantener stock óptimo y evitar desabastecimientos.";
    }
    
    if (strpos($pregunta, 'hola') !== false || strpos($pregunta, 'ayuda') !== false) {
        return "👋 ¡Hola <strong>$usuario</strong>! Soy tu asistente especializado en gestión de inventario para tienda de zapatos. Puedo ayudarte con: <em>marcas, tallas, colores, stock, ventas, temporadas, proveedores, categorías y mucho más</em>. ¿Qué necesitas consultar?";
    }
    
    // Respuesta por defecto más completa
    return "🤔 No encontré información específica sobre esa consulta. Puedo ayudarte con:<br><br>
    👟 <strong>Productos:</strong> marcas, tallas, colores, stock<br>
    📊 <strong>Análisis:</strong> ventas, tendencias, productos populares<br>
    📅 <strong>Temporadas:</strong> recomendaciones estacionales<br>
    🏢 <strong>Gestión:</strong> proveedores, categorías, alertas<br>
    📈 <strong>Reportes:</strong> estadísticas y gráficos<br><br>
    ¿Sobre qué aspecto de tu tienda de zapatos te gustaría saber más?";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistente Virtual Inventixor - IA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: #bdc3c7 !important;
            transition: all 0.3s ease;
            margin: 2px 0;
            border-radius: 8px;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(52, 152, 219, 0.2);
            color: #3498db !important;
            transform: translateX(5px);
        }
        
        .main-content {
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin: 20px;
            padding: 30px;
            min-height: calc(100vh - 40px);
        }
        
        .chat-container {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 20px;
            height: 500px;
            overflow-y: auto;
            margin-bottom: 20px;
            border: 2px solid rgba(102, 126, 234, 0.1);
        }
        
        .chat-message {
            margin-bottom: 20px;
            animation: fadeInUp 0.5s ease-out;
        }
        
        .chat-bubble {
            border-radius: 18px;
            padding: 15px 20px;
            max-width: 80%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: relative;
            word-wrap: break-word;
        }
        
        .chat-bubble.user {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        
        .chat-bubble.bot {
            background: white;
            color: #2c3e50;
            margin-right: auto;
            border: 2px solid #e9ecef;
            border-bottom-left-radius: 5px;
        }
        
        .bot-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 10px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            margin-bottom: 10px;
            margin-left: auto;
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .typing-indicator {
            display: none;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .typing-dots {
            display: flex;
            gap: 4px;
        }
        
        .typing-dot {
            width: 8px;
            height: 8px;
            background: #667eea;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }
        
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        
        .chat-input-container {
            background: white;
            border-radius: 25px;
            padding: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border: 2px solid #e9ecef;
        }
        
        .chat-input {
            border: none;
            outline: none;
            background: transparent;
            padding: 12px 20px;
            font-size: 1rem;
            width: 100%;
        }
        
        .send-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 20px;
            padding: 12px 20px;
            color: white;
            transition: all 0.3s ease;
            min-width: 80px;
        }
        
        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .suggestion-chip {
            background: rgba(102, 126, 234, 0.1);
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 20px;
            padding: 8px 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            color: #667eea;
        }
        
        .suggestion-chip:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 20px;
            color: white;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .page-title {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 30px;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }
        
        .voice-indicator {
            display: none;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            margin-top: 15px;
            animation: voicePulse 1.2s infinite;
            border: 2px solid rgba(231, 76, 60, 0.3);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
        }
        
        @keyframes voicePulse {
            0%, 100% { 
                opacity: 1; 
                transform: scale(1);
                box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
            }
            50% { 
                opacity: 0.8; 
                transform: scale(1.05);
                box-shadow: 0 6px 20px rgba(231, 76, 60, 0.6);
            }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        /* Estilos para notificaciones */
        #notificationContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        }

        .chat-input-container:focus-within {
            border-color: #667eea !important;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25) !important;
        }

        .send-btn:disabled,
        .send-btn.opacity-50 {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Mejoras visuales para botones */
        .send-btn:active {
            transform: scale(0.95);
        }

        .suggestion-chip:active {
            transform: scale(0.92) !important;
        }
        
        .welcome-message {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 20px;
            margin-bottom: 20px;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .feature-item {
            background: white;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .feature-item:hover {
            transform: translateY(-5px);
        }
        
        /* Estilos mejorados para sugerencias agrupadas */
        .suggestions {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .suggestions h6 {
            color: #2c3e50;
            font-weight: 600;
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }
        
        .suggestions h6 i {
            color: #667eea;
        }
        
        .suggestion-chip {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 25px;
            padding: 10px 18px;
            cursor: pointer;
            transition: all 0.4s ease;
            font-size: 0.9rem;
            color: #667eea;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            margin: 4px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
        }
        
        .suggestion-chip:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
            border-color: transparent;
        }
        
        .suggestion-chip i {
            transition: transform 0.3s ease;
        }
        
        .suggestion-chip:hover i {
            transform: scale(1.2) rotate(5deg);
        }
        
        /* Colores específicos para diferentes tipos de consultas */
        .suggestions:nth-child(1) .suggestion-chip {
            border-color: rgba(52, 152, 219, 0.3);
            color: #3498db;
        }
        
        .suggestions:nth-child(1) .suggestion-chip:hover {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        }
        
        .suggestions:nth-child(2) .suggestion-chip {
            border-color: rgba(39, 174, 96, 0.3);
            color: #27ae60;
        }
        
        .suggestions:nth-child(2) .suggestion-chip:hover {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        }
        
        .suggestions:nth-child(3) .suggestion-chip {
            border-color: rgba(230, 126, 34, 0.3);
            color: #e67e22;
        }
        
        .suggestions:nth-child(3) .suggestion-chip:hover {
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
        }
    </style>
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Sidebar Navigation -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar position-fixed h-100">
                <div class="position-sticky pt-4">
                    <div class="text-center mb-4">
                        <h3 class="text-white fw-bold">
                            <i class="fas fa-cube me-2"></i>Inventixor
                        </h3>
                        <p class="text-light opacity-75 small">Sistema de Inventario</p>
                    </div>
                    
                    <ul class="nav flex-column px-2">
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-3"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="productos.php">
                                <i class="fas fa-box me-3"></i>
                                <span>Productos</span>
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="categorias.php">
                                <i class="fas fa-tags me-3"></i>
                                <span>Categorías</span>
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="subcategorias.php">
                                <i class="fas fa-list me-3"></i>
                                <span>Subcategorías</span>
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="proveedores.php">
                                <i class="fas fa-truck me-3"></i>
                                <span>Proveedores</span>
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="salidas.php">
                                <i class="fas fa-sign-out-alt me-3"></i>
                                <span>Salidas</span>
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="reportes.php">
                                <i class="fas fa-chart-bar me-3"></i>
                                <span>Reportes</span>
                            </a>
                        </li>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="alertas.php">
                                <i class="fas fa-bell me-3"></i>
                                <span>Alertas</span>
                            </a>
                        </li>
                        <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3" href="usuarios.php">
                                <i class="fas fa-users me-3"></i>
                                <span>Usuarios</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item mb-1">
                            <a class="nav-link d-flex align-items-center py-2 px-3 active" href="ia_ayuda.php">
                                <i class="fas fa-robot me-3"></i>
                                <span>Asistente IA</span>
                            </a>
                        </li>
                    </ul>
                    
                    <div class="mt-auto pt-4 px-2">
                        <div class="bg-light bg-opacity-10 rounded p-3 mb-3">
                            <div class="d-flex align-items-center text-light">
                                <i class="fas fa-user-circle fs-4 me-2"></i>
                                <div>
                                    <div class="fw-semibold"><?php echo $_SESSION['user']['nombres'] ?? 'Usuario'; ?></div>
                                    <small class="opacity-75"><?php echo ucfirst($_SESSION['rol']); ?></small>
                                </div>
                            </div>
                        </div>
                        <a class="nav-link d-flex align-items-center py-2 px-3 text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt me-3"></i>
                            <span>Cerrar Sesión</span>
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 ms-md-auto">
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="page-title">
                            <i class="fas fa-robot me-3"></i>Asistente Virtual IA
                        </h1>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary rounded-pill" onclick="limpiarChat()">
                                <i class="fas fa-refresh me-2"></i>Limpiar Chat
                            </button>
                        </div>
                    </div>

                    <!-- Stats Overview -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <i class="fas fa-box fs-2 mb-2"></i>
                                <h4><?php echo $stats['total_productos']; ?></h4>
                                <p class="mb-0">Productos</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <i class="fas fa-tags fs-2 mb-2"></i>
                                <h4><?php echo $stats['total_categorias']; ?></h4>
                                <p class="mb-0">Categorías</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <i class="fas fa-bell fs-2 mb-2"></i>
                                <h4><?php echo $stats['alertas_activas']; ?></h4>
                                <p class="mb-0">Alertas Activas</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <i class="fas fa-cubes fs-2 mb-2"></i>
                                <h4><?php echo number_format($stats['stock_total']); ?></h4>
                                <p class="mb-0">Stock Total</p>
                            </div>
                        </div>
                    </div>

                    <!-- Welcome Message -->
                    <div class="welcome-message">
                        <h2><i class="fas fa-robot me-2"></i>¡Hola <?php echo $_SESSION['user']['nombres'] ?? 'Usuario'; ?>!</h2>
                        <p class="lead">Soy tu asistente virtual de Inventixor. Puedo ayudarte con información sobre el sistema y responder tus preguntas.</p>
                        
                        <div class="feature-grid">
                            <div class="feature-item">
                                <i class="fas fa-box text-primary fs-3 mb-2"></i>
                                <h6>Productos</h6>
                                <small>Consulta información sobre tu inventario</small>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-chart-line text-success fs-3 mb-2"></i>
                                <h6>Estadísticas</h6>
                                <small>Obtén datos en tiempo real</small>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-bell text-warning fs-3 mb-2"></i>
                                <h6>Alertas</h6>
                                <small>Revisa notificaciones importantes</small>
                            </div>
                            <div class="feature-item">
                                <i class="fas fa-question-circle text-info fs-3 mb-2"></i>
                                <h6>Ayuda</h6>
                                <small>Soporte y orientación</small>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Interface -->
                    <div class="row">
                        <div class="col-12">
                            <!-- Chat Container -->
                            <div class="chat-container" id="chatContainer">
                                <div class="chat-message">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="bot-avatar me-3">
                                            <i class="fas fa-robot"></i>
                                        </div>
                                        <div class="chat-bubble bot">
                                            <strong>¡Hola <?php echo $_SESSION['user']['nombres'] ?? 'Usuario'; ?>!</strong><br>
                                            Soy tu asistente virtual de Inventixor. Puedo ayudarte con:
                                            <ul class="mt-2 mb-0">
                                                <li>Información sobre productos y stock</li>
                                                <li>Estado de categorías y proveedores</li>
                                                <li>Alertas y notificaciones</li>
                                                <li>Explicación de funciones del sistema</li>
                                                <li>Estadísticas y reportes</li>
                                            </ul>
                                            ¿En qué puedo asistirte hoy?
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Typing Indicator -->
                            <div class="typing-indicator" id="typingIndicator">
                                <div class="bot-avatar me-3">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div class="typing-dots">
                                    <div class="typing-dot"></div>
                                    <div class="typing-dot"></div>
                                    <div class="typing-dot"></div>
                                </div>
                                <span class="ms-2 text-muted">El asistente está escribiendo...</span>
                            </div>

                            <!-- Voice Indicator -->
                            <div class="voice-indicator text-center" id="voiceIndicator">
                                <i class="fas fa-microphone me-2"></i>Escuchando...
                            </div>

                            <!-- Quick Suggestions - Tienda de Zapatos -->
                            <div class="suggestions">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-2"><i class="fas fa-bolt me-2"></i>Consultas Rápidas - Inventario</h6>
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('¿Cuántos productos tengo?')">
                                    <i class="fas fa-box me-2"></i>Total Productos
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('Stock bajo')">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Stock Crítico
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('¿Qué alertas están activas?')">
                                    <i class="fas fa-bell me-2"></i>Alertas Activas
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('Productos más vendidos')">
                                    <i class="fas fa-trophy me-2"></i>Más Vendidos
                                </div>
                            </div>

                            <!-- Consultas Específicas de Calzado -->
                            <div class="suggestions mt-3">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-2"><i class="fas fa-shoe-prints me-2"></i>Consultas Específicas - Calzado</h6>
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('Nike')">
                                    <i class="fab fa-nike me-2"></i>Nike
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('Adidas')">
                                    <i class="fas fa-running me-2"></i>Adidas
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('Deportivo')">
                                    <i class="fas fa-dumbbell me-2"></i>Deportivo
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('Formal')">
                                    <i class="fas fa-user-tie me-2"></i>Formal
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('Infantil')">
                                    <i class="fas fa-child me-2"></i>Infantil
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('Tallas')">
                                    <i class="fas fa-ruler me-2"></i>Tallas
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('Colores')">
                                    <i class="fas fa-palette me-2"></i>Colores
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('Temporada')">
                                    <i class="fas fa-calendar-alt me-2"></i>Temporada
                                </div>
                            </div>

                            <!-- Consultas de Gestión -->
                            <div class="suggestions mt-3">
                                <div class="mb-3">
                                    <h6 class="text-muted mb-2"><i class="fas fa-cogs me-2"></i>Gestión y Reportes</h6>
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('Proveedores')">
                                    <i class="fas fa-truck me-2"></i>Proveedores
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('¿Qué reportes puedo generar?')">
                                    <i class="fas fa-chart-bar me-2"></i>Reportes
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('¿Cuál es mi rol en el sistema?')">
                                    <i class="fas fa-user me-2"></i>Mi Rol
                                </div>
                                <div class="suggestion-chip" onclick="enviarSugerencia('¿Cómo funcionan las salidas FIFO?')">
                                    <i class="fas fa-sign-out-alt me-2"></i>Salidas FIFO
                                </div>
                            </div>

                            <!-- Chat Input -->
                            <div class="chat-input-container d-flex align-items-center">
                                <input type="text" class="chat-input flex-grow-1" id="chatInput" placeholder="Escribe tu pregunta aquí...">
                                <button class="btn send-btn me-2" onclick="iniciarReconocimientoVoz()" title="Reconocimiento de voz">
                                    <i class="fas fa-microphone"></i>
                                </button>
                                <button class="btn send-btn" onclick="enviarMensaje()" title="Enviar mensaje">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap & jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        let recognition;
        let isListening = false;

        // Función para limpiar el chat
        function limpiarChat() {
            const chatContainer = document.getElementById('chatContainer');
            chatContainer.innerHTML = `
                <div class="chat-message">
                    <div class="d-flex align-items-start mb-3">
                        <div class="bot-avatar me-3">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="chat-bubble bot">
                            <strong>¡Hola <?php echo $_SESSION['user']['nombres'] ?? 'Usuario'; ?>!</strong><br>
                            Chat reiniciado. ¿En qué puedo ayudarte?
                        </div>
                    </div>
                </div>
            `;
        }

        // Función para enviar sugerencias
        function enviarSugerencia(pregunta) {
            document.getElementById('chatInput').value = pregunta;
            enviarMensaje();
        }

        // Función principal para enviar mensajes
        function enviarMensaje() {
            const input = document.getElementById('chatInput');
            const pregunta = input.value.trim();
            
            if (!pregunta) return;

            // Mostrar mensaje del usuario
            mostrarMensajeUsuario(pregunta);
            
            // Limpiar input
            input.value = '';

            // Mostrar indicador de escritura
            mostrarIndicadorEscritura();

            // Simular delay para respuesta más realista
            setTimeout(() => {
                ocultarIndicadorEscritura();
                enviarPreguntaIA(pregunta);
            }, 1000 + Math.random() * 1000);
        }

        // Mostrar mensaje del usuario
        function mostrarMensajeUsuario(pregunta) {
            const chatContainer = document.getElementById('chatContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message';
            messageDiv.innerHTML = `
                <div class="d-flex align-items-end mb-3 justify-content-end">
                    <div class="chat-bubble user me-3">
                        ${pregunta}
                    </div>
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
            `;
            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        // Mostrar respuesta del bot
        function mostrarMensajeBot(respuesta) {
            const chatContainer = document.getElementById('chatContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message';
            messageDiv.innerHTML = `
                <div class="d-flex align-items-start mb-3">
                    <div class="bot-avatar me-3">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="chat-bubble bot">
                        ${respuesta}
                    </div>
                </div>
            `;
            chatContainer.appendChild(messageDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;

            // Síntesis de voz opcional
            if ('speechSynthesis' in window) {
                try {
                    const utterance = new SpeechSynthesisUtterance(respuesta.replace(/<[^>]*>/g, ''));
                    utterance.lang = 'es-ES';
                    utterance.rate = 0.9;
                    utterance.pitch = 1.1;
                    speechSynthesis.speak(utterance);
                } catch (error) {
                    console.log('Síntesis de voz no disponible');
                }
            }
        }

        // Mostrar indicador de escritura
        function mostrarIndicadorEscritura() {
            const indicator = document.getElementById('typingIndicator');
            indicator.style.display = 'flex';
        }

        // Ocultar indicador de escritura
        function ocultarIndicadorEscritura() {
            const indicator = document.getElementById('typingIndicator');
            indicator.style.display = 'none';
        }

        // Enviar pregunta a la IA usando AJAX
        function enviarPreguntaIA(pregunta) {
            $.ajax({
                url: 'ia_ayuda.php',
                method: 'POST',
                data: {
                    action: 'chat',
                    pregunta: pregunta
                },
                dataType: 'json',
                success: function(response) {
                    mostrarMensajeBot(response.respuesta);
                },
                error: function() {
                    mostrarMensajeBot('Lo siento, hubo un error al procesar tu pregunta. Por favor, intenta de nuevo.');
                }
            });
        }

        // Reconocimiento de voz mejorado
        function iniciarReconocimientoVoz() {
            if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                mostrarNotificacion('Lo siento, tu navegador no soporta reconocimiento de voz.', 'warning');
                return;
            }

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            
            // Si ya está escuchando, detener
            if (isListening && recognition) {
                recognition.stop();
                isListening = false;
                document.getElementById('voiceIndicator').style.display = 'none';
                return;
            }

            recognition = new SpeechRecognition();
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'es-ES';
            recognition.maxAlternatives = 3;

            // Iniciar reconocimiento
            try {
                recognition.start();
                isListening = true;
                document.getElementById('voiceIndicator').style.display = 'block';
                
                // Auto-detener después de 10 segundos
                setTimeout(() => {
                    if (isListening) {
                        recognition.stop();
                    }
                }, 10000);
                
            } catch (error) {
                console.error('Error al iniciar reconocimiento:', error);
                mostrarNotificacion('Error al iniciar el reconocimiento de voz', 'error');
                isListening = false;
                document.getElementById('voiceIndicator').style.display = 'none';
            }

            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript;
                const confidence = event.results[0][0].confidence;
                
                if (confidence > 0.5) {
                    document.getElementById('chatInput').value = transcript;
                    document.getElementById('voiceIndicator').style.display = 'none';
                    isListening = false;
                    
                    // Enviar automáticamente si la confianza es alta
                    if (confidence > 0.8) {
                        setTimeout(() => enviarMensaje(), 500);
                    }
                } else {
                    mostrarNotificacion('No se pudo entender claramente. Intenta de nuevo.', 'warning');
                }
            };

            recognition.onerror = function(event) {
                console.error('Error en reconocimiento de voz:', event.error);
                let mensajeError = 'Error en el reconocimiento de voz';
                
                switch(event.error) {
                    case 'no-speech':
                        mensajeError = 'No se detectó voz. Intenta hablar más cerca del micrófono.';
                        break;
                    case 'audio-capture':
                        mensajeError = 'No se puede acceder al micrófono.';
                        break;
                    case 'not-allowed':
                        mensajeError = 'Permiso de micrófono denegado.';
                        break;
                    case 'network':
                        mensajeError = 'Error de conexión. Verifica tu internet.';
                        break;
                }
                
                mostrarNotificacion(mensajeError, 'error');
                isListening = false;
                document.getElementById('voiceIndicator').style.display = 'none';
            };

            recognition.onend = function() {
                isListening = false;
                document.getElementById('voiceIndicator').style.display = 'none';
            };
        }

        // Función para mostrar notificaciones
        function mostrarNotificacion(mensaje, tipo = 'info') {
            // Crear elemento de notificación si no existe
            let container = document.getElementById('notificationContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'notificationContainer';
                container.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    max-width: 350px;
                `;
                document.body.appendChild(container);
            }

            const notification = document.createElement('div');
            const iconClass = tipo === 'error' ? 'fa-exclamation-circle' : 
                            tipo === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
            const bgClass = tipo === 'error' ? 'danger' : 
                           tipo === 'warning' ? 'warning' : 'primary';

            notification.className = `alert alert-${bgClass} alert-dismissible fade show`;
            notification.style.cssText = 'margin-bottom: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
            notification.innerHTML = `
                <i class="fas ${iconClass} me-2"></i>
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            container.appendChild(notification);

            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }

        // Event listeners consolidados
        document.addEventListener('DOMContentLoaded', function() {
            // Enter key para enviar mensaje
            document.getElementById('chatInput').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    enviarMensaje();
                }
            });

            // Auto-focus en el input al cargar la página
            document.getElementById('chatInput').focus();

            // Animaciones suaves para las sugerencias
            const suggestions = document.querySelectorAll('.suggestion-chip');
            suggestions.forEach((suggestion, index) => {
                suggestion.style.animationDelay = `${index * 0.1}s`;
                suggestion.classList.add('animate__animated', 'animate__fadeInUp');
                
                // Efecto de click en sugerencias
                suggestion.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });

            // Configurar tooltips para botones
            const voiceBtn = document.querySelector('button[onclick="iniciarReconocimientoVoz()"]');
            const sendBtn = document.querySelector('button[onclick="enviarMensaje()"]');
            
            if (voiceBtn) {
                voiceBtn.addEventListener('click', function() {
                    this.blur(); // Quitar focus después del click
                });
            }

            if (sendBtn) {
                sendBtn.addEventListener('click', function() {
                    this.blur(); // Quitar focus después del click
                });
            }

            // Mejorar UX del input de chat
            const chatInput = document.getElementById('chatInput');
            if (chatInput) {
                chatInput.addEventListener('focus', function() {
                    this.parentElement.style.borderColor = '#667eea';
                    this.parentElement.style.boxShadow = '0 0 0 0.25rem rgba(102, 126, 234, 0.25)';
                });

                chatInput.addEventListener('blur', function() {
                    this.parentElement.style.borderColor = '#e9ecef';
                    this.parentElement.style.boxShadow = 'none';
                });
            }

            // Verificar soporte de reconocimiento de voz
            if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
                const voiceButton = document.querySelector('button[onclick="iniciarReconocimientoVoz()"]');
                if (voiceButton) {
                    voiceButton.disabled = true;
                    voiceButton.title = 'Reconocimiento de voz no disponible en este navegador';
                    voiceButton.classList.add('opacity-50');
                }
            }
        });
    </script>

    <!-- Animate.css para animaciones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</body>
</html>
    </script>

    <!-- Animate.css para animaciones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</body>
</html>
