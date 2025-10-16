<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Campo Motivo Sistema InventiXor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .diagnostic-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .status-ok { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
        .file-check {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="diagnostic-container">
        <div class="text-center mb-4">
            <h2 class="text-primary">
                <i class="fas fa-stethoscope me-2"></i>Diagnóstico del Sistema
            </h2>
            <p class="text-muted">Verificación del campo motivo en lista desplegable</p>
        </div>

        <!-- Verificación de archivos -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5><i class="fas fa-file-check me-2"></i>Verificación de Archivos</h5>
            </div>
            <div class="card-body">
                <?php
                $archivos = [
                    'modales_salidas.php' => 'Modales de Salidas',
                    'public/js/salidas-mejorado.js' => 'JavaScript Principal',
                    'salidas_mejorado.php' => 'Página Principal'
                ];
                
                foreach ($archivos as $archivo => $descripcion) {
                    $existe = file_exists($archivo);
                    $icono = $existe ? 'fa-check status-ok' : 'fa-times status-error';
                    $estado = $existe ? 'Encontrado' : 'No encontrado';
                    
                    echo "<div class='file-check'>";
                    echo "<i class='fas $icono me-2'></i>";
                    echo "<strong>$descripcion:</strong> $estado";
                    if ($existe) {
                        $tamano = filesize($archivo);
                        $modificado = date('d/m/Y H:i:s', filemtime($archivo));
                        echo "<br><small class='text-muted'>Tamaño: " . number_format($tamano) . " bytes | Modificado: $modificado</small>";
                    }
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- Verificación del contenido del modal -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5><i class="fas fa-search me-2"></i>Contenido del Modal</h5>
            </div>
            <div class="card-body">
                <?php
                if (file_exists('modales_salidas.php')) {
                    $contenido = file_get_contents('modales_salidas.php');
                    
                    // Verificar elementos clave
                    $verificaciones = [
                        'id="motivo_devolucion"' => 'ID del select motivo',
                        'onchange="toggleMotivoOtro()"' => 'Evento onchange',
                        'id="otro-motivo-container"' => 'Contenedor campo otro',
                        'name="motivo_otro_detalle"' => 'Campo detalle otro',
                        '<optgroup label=' => 'Grupos de opciones'
                    ];
                    
                    foreach ($verificaciones as $buscar => $descripcion) {
                        $encontrado = strpos($contenido, $buscar) !== false;
                        $icono = $encontrado ? 'fa-check status-ok' : 'fa-times status-error';
                        $estado = $encontrado ? 'Presente' : 'Faltante';
                        
                        echo "<div class='mb-2'>";
                        echo "<i class='fas $icono me-2'></i>";
                        echo "<strong>$descripcion:</strong> $estado";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger'>";
                    echo "<i class='fas fa-exclamation-triangle me-2'></i>";
                    echo "No se pudo leer el archivo modales_salidas.php";
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- Verificación del JavaScript -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5><i class="fas fa-code me-2"></i>Funciones JavaScript</h5>
            </div>
            <div class="card-body">
                <?php
                if (file_exists('public/js/salidas-mejorado.js')) {
                    $js_contenido = file_get_contents('public/js/salidas-mejorado.js');
                    
                    $funciones_js = [
                        'toggleMotivoOtro' => 'Función toggle motivo otro',
                        'toggleOtroMotivo' => 'Método de clase toggle',
                        'window.toggleMotivoOtro' => 'Función global',
                        'procesarDevolucion' => 'Función abrir modal'
                    ];
                    
                    foreach ($funciones_js as $buscar => $descripcion) {
                        $encontrado = strpos($js_contenido, $buscar) !== false;
                        $icono = $encontrado ? 'fa-check status-ok' : 'fa-times status-error';
                        $estado = $encontrado ? 'Definida' : 'Faltante';
                        
                        echo "<div class='mb-2'>";
                        echo "<i class='fas $icono me-2'></i>";
                        echo "<strong>$descripcion:</strong> $estado";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger'>";
                    echo "<i class='fas fa-exclamation-triangle me-2'></i>";
                    echo "No se pudo leer el archivo JavaScript";
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- Test directo del modal -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-white">
                <h5><i class="fas fa-vial me-2"></i>Test Directo del Modal</h5>
            </div>
            <div class="card-body">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDevolucion">
                    <i class="fas fa-test me-2"></i>Abrir Modal de Devolución
                </button>
                <div class="mt-3">
                    <small class="text-muted">
                        Al abrir el modal, verifica que:
                        <ul class="mt-2">
                            <li>El campo "Motivo de Devolución" sea una lista desplegable</li>
                            <li>Contenga grupos organizados (🏭, 📦, 👤, 🚚, 🛡️)</li>
                            <li>Al seleccionar "Otro Motivo" aparezca el campo adicional</li>
                        </ul>
                    </small>
                </div>
            </div>
        </div>

        <!-- Acciones recomendadas -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5><i class="fas fa-tools me-2"></i>Acciones Recomendadas</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Si el campo no aparece como lista desplegable:</h6>
                    <ol class="mb-0">
                        <li>Verificar que todos los archivos estén presentes ✅</li>
                        <li>Limpiar caché del navegador (Ctrl+F5)</li>
                        <li>Verificar la consola del navegador (F12) en busca de errores</li>
                        <li>Asegurarse de que Bootstrap 5 se esté cargando correctamente</li>
                    </ol>
                </div>
                
                <div class="text-center">
                    <a href="salidas_mejorado.php" class="btn btn-success me-2">
                        <i class="fas fa-arrow-right me-2"></i>Ir al Sistema Principal
                    </a>
                    <a href="test_motivo_dropdown.html" class="btn btn-info">
                        <i class="fas fa-vial me-2"></i>Página de Prueba
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir el modal para prueba -->
    <?php include 'modales_salidas.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/salidas-mejorado.js"></script>
    <script>
        // Función adicional para diagnóstico
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Página de diagnóstico cargada');
            console.log('✅ Bootstrap cargado:', typeof bootstrap !== 'undefined');
            console.log('✅ Función toggleMotivoOtro disponible:', typeof toggleMotivoOtro !== 'undefined');
            
            // Verificar que el modal existe
            const modal = document.getElementById('modalDevolucion');
            console.log('✅ Modal encontrado:', modal !== null);
            
            // Verificar select
            const select = document.getElementById('motivo_devolucion');
            console.log('✅ Select motivo encontrado:', select !== null);
            
            if (select) {
                console.log('✅ Opciones en el select:', select.options.length);
            }
        });
    </script>
</body>
</html>