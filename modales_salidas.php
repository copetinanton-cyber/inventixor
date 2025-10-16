<!-- Modal para Ver Seguimiento -->
<div class="modal fade" id="modalSeguimiento" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-route me-2"></i>Seguimiento de Salida
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="seguimiento-content">
                    <!-- Contenido dinámico -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Actualizar Estado -->
<div class="modal fade" id="modalActualizarEstado" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Actualizar Estado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-actualizar-estado">
                <div class="modal-body">
                    <input type="hidden" name="id_salida" id="actualizar_id_salida">
                    
                    <div class="mb-3">
                        <label class="form-label">Nuevo Estado</label>
                        <select name="nuevo_estado" class="form-select" required>
                            <option value="preparando">Preparando</option>
                            <option value="enviado">Enviado</option>
                            <option value="en_transito">En Tránsito</option>
                            <option value="entregado">Entregado</option>
                            <option value="perdido">Perdido</option>
                            <option value="dañado">Dañado</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3" 
                                  placeholder="Detalles sobre el cambio de estado..."></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>El sistema registrará automáticamente la fecha y usuario del cambio.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Actualizar Estado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Devolución -->
<div class="modal fade" id="modalDevolucion" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-undo me-2"></i>Procesar Devolución
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-devolucion">
                <div class="modal-body">
                    <input type="hidden" name="id_salida" id="devolucion_id_salida">
                    <input type="hidden" name="id_prod" id="devolucion_id_prod">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Cantidad a Devolver</label>
                                <input type="number" name="cantidad_devuelta" class="form-control" min="1" required>
                                <div class="form-text">Cantidad original: <span id="cantidad-original"></span></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Motivo de Devolución</label>
                                <select name="motivo" id="motivo_devolucion" class="form-select" required onchange="toggleMotivoOtro()">
                                    <option value="">Seleccionar motivo...</option>
                                    <optgroup label="🏭 Problemas de Calidad">
                                        <option value="defecto_fabrica">Defecto de Fábrica</option>
                                        <option value="material_defectuoso">Material Defectuoso</option>
                                        <option value="costura_defectuosa">Costura Defectuosa</option>
                                        <option value="suela_despegada">Suela Despegada</option>
                                    </optgroup>
                                    <optgroup label="📦 Problemas de Pedido">
                                        <option value="no_conforme">No Conforme a lo Pedido</option>
                                        <option value="talla_incorrecta">Talla Incorrecta</option>
                                        <option value="color_incorrecto">Color Incorrecto</option>
                                        <option value="modelo_incorrecto">Modelo Incorrecto</option>
                                    </optgroup>
                                    <optgroup label="👤 Decisión del Cliente">
                                        <option value="cambio_talla">Cambio de Talla</option>
                                        <option value="no_le_queda">No le Queda Bien</option>
                                        <option value="no_le_gusta">No le Gusta</option>
                                        <option value="arrepentimiento">Arrepentimiento de Compra</option>
                                        <option value="encontro_mejor_precio">Encontró Mejor Precio</option>
                                    </optgroup>
                                    <optgroup label="🚚 Problemas de Entrega">
                                        <option value="dañado_transporte">Dañado en Transporte</option>
                                        <option value="entrega_tardia">Entrega Tardía</option>
                                        <option value="empaque_dañado">Empaque Dañado</option>
                                    </optgroup>
                                    <optgroup label="🛡️ Garantía y Otros">
                                        <option value="garantia">Uso de Garantía</option>
                                        <option value="rotura_uso_normal">Rotura por Uso Normal</option>
                                        <option value="producto_incomodo">Producto Incómodo</option>
                                        <option value="otro">Otro Motivo</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campo adicional para "Otro motivo" -->
                    <div class="row" id="otro-motivo-container" style="display: none;">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Especifique el Motivo</label>
                                <input type="text" name="motivo_otro_detalle" class="form-control" 
                                       placeholder="Describa el motivo específico de la devolución...">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Condición del Producto</label>
                                <select name="condicion_producto" class="form-select" required>
                                    <option value="">Evaluar condición...</option>
                                    <option value="nuevo">Como Nuevo</option>
                                    <option value="usado_bueno">Usado - Buen Estado</option>
                                    <option value="usado_regular">Usado - Estado Regular</option>
                                    <option value="dañado">Dañado</option>
                                    <option value="no_recuperable">No Recuperable</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Acción a Tomar</label>
                                <select name="accion" class="form-select" required>
                                    <option value="">Seleccionar acción...</option>
                                    <option value="reingresar_inventario">Reingresar a Inventario</option>
                                    <option value="devolver_proveedor">Devolver a Proveedor</option>
                                    <option value="reparar">Enviar a Reparación</option>
                                    <option value="descartar">Descartar Producto</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observaciones Detalladas</label>
                        <textarea name="observaciones" class="form-control" rows="3" 
                                  placeholder="Describe el estado del producto, razón específica de la devolución, etc..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Importante:</strong> Si selecciona "Reingresar a Inventario", el stock se actualizará automáticamente.
                        Asegúrese de que la condición del producto justifica su reingreso.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-undo me-2"></i>Procesar Devolución
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Detalles de Producto en Tránsito -->
<div class="modal fade" id="modalDetallesTransito" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-truck me-2"></i>Detalles de Envío
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalles-transito-content">
                    <!-- Contenido dinámico -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="marcarComoEntregado()">
                    <i class="fas fa-check me-2"></i>Marcar como Entregado
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalles de Garantía -->
<div class="modal fade" id="modalDetallesGarantia" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-shield-alt me-2"></i>Detalles de Garantía
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detalles-garantia-content">
                    <!-- Contenido dinámico -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-warning" onclick="utilizarGarantia()">
                    <i class="fas fa-tools me-2"></i>Utilizar Garantía
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast para Notificaciones -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="toast-notification" class="toast" role="alert">
        <div class="toast-header">
            <i class="fas fa-check-circle text-success me-2"></i>
            <strong class="me-auto">Notificación</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toast-message">
            <!-- Mensaje dinámico -->
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="d-none">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>
</div>

<style>
#loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
}

.toast-container {
    z-index: 10000;
}
</style>