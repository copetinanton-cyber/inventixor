<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Modal - Campo Motivo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .alert-success {
            border-left: 4px solid #28a745;
        }
        .alert-warning {
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="text-center mb-4">
            <h2 class="text-primary">
                <i class="fas fa-bug me-2"></i>Prueba del Modal de Devolución
            </h2>
            <p class="text-muted">Verificación directa del campo motivo como lista desplegable</p>
        </div>

        <!-- Status del problema -->
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Problema Reportado:</h5>
            <p class="mb-0">El campo "motivo" no aparece como lista desplegable en el modal de devolución del sistema principal.</p>
        </div>

        <!-- Botón para abrir el modal -->
        <div class="text-center mb-4">
            <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalDevolucion">
                <i class="fas fa-undo me-2"></i>Probar Modal de Devolución
            </button>
            <div class="mt-2">
                <small class="text-muted">Este modal usa exactamente el mismo código que el sistema principal</small>
            </div>
        </div>

        <!-- Información de verificación -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h6><i class="fas fa-check me-2"></i>Verificaciones Completadas</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li>✅ modales_salidas.php - Lista desplegable implementada</li>
                            <li>✅ JavaScript - Función toggleMotivoOtro() presente</li>
                            <li>✅ Inclusión - Modal incluido en salidas_mejorado.php</li>
                            <li>✅ Bootstrap - Framework cargado correctamente</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6><i class="fas fa-lightbulb me-2"></i>Posibles Causas</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li>⚠️ Caché del navegador</li>
                            <li>⚠️ Conflicto de versiones Bootstrap</li>
                            <li>⚠️ Error JavaScript no visible</li>
                            <li>⚠️ Modal no inicializado correctamente</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resultado de la prueba -->
        <div class="mt-4">
            <div class="alert alert-success" id="test-result" style="display: none;">
                <h6><i class="fas fa-check-circle me-2"></i>¡Modal Funcionando Correctamente!</h6>
                <p class="mb-0">Si puedes ver la lista desplegable categorizada en el modal, entonces el problema está en el sistema principal.</p>
            </div>
        </div>

        <!-- Instrucciones -->
        <div class="card mt-4">
            <div class="card-header bg-secondary text-white">
                <h6><i class="fas fa-instructions me-2"></i>Instrucciones de Prueba</h6>
            </div>
            <div class="card-body">
                <ol>
                    <li><strong>Abre el modal</strong> haciendo clic en el botón "Probar Modal de Devolución"</li>
                    <li><strong>Verifica el campo "Motivo de Devolución"</strong> - debe ser una lista desplegable</li>
                    <li><strong>Prueba seleccionar "Otro Motivo"</strong> - debe aparecer un campo adicional</li>
                    <li><strong>Si funciona aquí pero no en el sistema principal:</strong>
                        <ul class="mt-2">
                            <li>Limpia el caché del navegador (Ctrl+F5)</li>
                            <li>Verifica la consola del navegador (F12) en busca de errores</li>
                            <li>Asegúrate de tener salidas registradas para probar</li>
                        </ul>
                    </li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Incluir el modal exacto del sistema -->
    <?php include 'modales_salidas.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/salidas-mejorado.js"></script>
    
    <script>
        // Función para mostrar resultado cuando se abra el modal
        document.getElementById('modalDevolucion').addEventListener('shown.bs.modal', function () {
            document.getElementById('test-result').style.display = 'block';
            console.log('✅ Modal abierto - Verificar campo motivo');
        });
        
        // Log de verificación
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== VERIFICACIÓN DEL MODAL ===');
            console.log('Modal existe:', document.getElementById('modalDevolucion') !== null);
            console.log('Select motivo existe:', document.getElementById('motivo_devolucion') !== null);
            console.log('Función toggleMotivoOtro disponible:', typeof window.toggleMotivoOtro !== 'undefined');
            console.log('Bootstrap cargado:', typeof bootstrap !== 'undefined');
            
            const select = document.getElementById('motivo_devolucion');
            if (select) {
                console.log('Opciones en select:', select.options.length);
                console.log('Tipo de elemento:', select.tagName);
            }
        });
    </script>
</body>
</html>