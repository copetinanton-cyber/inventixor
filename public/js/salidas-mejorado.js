// Gestión Avanzada de Salidas - JavaScript
class GestionSalidas {
    constructor() {
        this.initializeEvents();
        this.loadDashboard();
    }

    initializeEvents() {
        // Form nueva salida
        document.getElementById('form-nueva-salida').addEventListener('submit', this.handleNuevaSalida.bind(this));
        
        // Form actualizar estado
        document.getElementById('form-actualizar-estado').addEventListener('submit', this.handleActualizarEstado.bind(this));
        
        // Form devolución
        document.getElementById('form-devolucion').addEventListener('submit', this.handleDevolucion.bind(this));
        
        // Producto selector change
        const selectProducto = document.querySelector('select[name="id_prod"]');
        if (selectProducto) {
            selectProducto.addEventListener('change', this.updateStockDisplay.bind(this));
        }
        
        // Tipo salida change
        const selectTipoSalida = document.querySelector('select[name="tipo_salida"]');
        if (selectTipoSalida) {
            selectTipoSalida.addEventListener('change', this.toggleClienteInfo.bind(this));
        }
        
        // Garantía checkbox
        const checkboxGarantia = document.getElementById('con_garantia');
        if (checkboxGarantia) {
            checkboxGarantia.addEventListener('change', this.toggleGarantiaInfo.bind(this));
        }
        
        // Tab changes
        document.querySelectorAll('#salidaTabs button').forEach(tab => {
            tab.addEventListener('shown.bs.tab', this.handleTabChange.bind(this));
        });
        
        // Motivo devolución change - usar delegación de eventos
        document.addEventListener('change', (e) => {
            if (e.target && e.target.id === 'motivo_devolucion') {
                this.toggleOtroMotivo();
            }
        });
    }

    async loadDashboard() {
        try {
            const response = await this.fetchData('get_dashboard');
            if (response.success) {
                this.renderDashboard(response.stats);
            }
        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    }

    renderDashboard(stats) {
        const container = document.getElementById('dashboard-stats');
        
        container.innerHTML = `
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <i class="fas fa-arrow-up fa-2x mb-2 opacity-75"></i>
                    <h4>${stats.hoy.total}</h4>
                    <p class="mb-0">Salidas Hoy</p>
                    <small>${stats.hoy.cantidad_total} productos</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <i class="fas fa-truck fa-2x mb-2 opacity-75"></i>
                    <h4>${stats.en_transito}</h4>
                    <p class="mb-0">En Tránsito</p>
                    <small>Productos enviados</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <i class="fas fa-shield-alt fa-2x mb-2 opacity-75"></i>
                    <h4>${stats.garantias_activas}</h4>
                    <p class="mb-0">Garantías Activas</p>
                    <small>Con cobertura</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <i class="fas fa-undo fa-2x mb-2 opacity-75"></i>
                    <h4>${stats.devoluciones_mes}</h4>
                    <p class="mb-0">Devoluciones</p>
                    <small>Este mes</small>
                </div>
            </div>
        `;
    }

    async handleNuevaSalida(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        formData.append('action', 'registrar_salida');
        
        try {
            this.showLoading(true);
            const response = await this.fetchData('registrar_salida', formData);
            
            if (response.success) {
                this.showToast('Salida registrada exitosamente', 'success');
                event.target.reset();
                this.updateStockDisplay();
                // Cambiar a la pestaña de lista
                document.getElementById('lista-tab').click();
                // Recargar la página para mostrar la nueva salida
                setTimeout(() => location.reload(), 1500);
            } else {
                this.showToast(response.error || 'Error al registrar salida', 'error');
            }
        } catch (error) {
            this.showToast('Error de conexión', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async handleActualizarEstado(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        formData.append('action', 'actualizar_seguimiento');
        
        try {
            const response = await this.fetchData('actualizar_seguimiento', formData);
            
            if (response.success) {
                this.showToast('Estado actualizado correctamente', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalActualizarEstado')).hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                this.showToast(response.error || 'Error al actualizar estado', 'error');
            }
        } catch (error) {
            this.showToast('Error de conexión', 'error');
        }
    }

    async handleDevolucion(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        formData.append('action', 'registrar_devolucion');
        
        try {
            const response = await this.fetchData('registrar_devolucion', formData);
            
            if (response.success) {
                this.showToast(response.message || 'Devolución procesada correctamente', 'success');
                bootstrap.Modal.getInstance(document.getElementById('modalDevolucion')).hide();
                setTimeout(() => location.reload(), 1500);
            } else {
                this.showToast(response.error || 'Error al procesar devolución', 'error');
            }
        } catch (error) {
            this.showToast('Error de conexión', 'error');
        }
    }

    updateStockDisplay() {
        const selectProducto = document.querySelector('select[name="id_prod"]');
        const stockDisplay = document.getElementById('stock-disponible');
        const cantidadInput = document.querySelector('input[name="cantidad"]');
        
        if (selectProducto && stockDisplay) {
            const selectedOption = selectProducto.options[selectProducto.selectedIndex];
            const stock = selectedOption ? selectedOption.dataset.stock : 0;
            
            stockDisplay.textContent = stock;
            
            if (cantidadInput) {
                cantidadInput.max = stock;
                cantidadInput.value = '';
            }
        }
    }

    toggleClienteInfo() {
        const selectTipoSalida = document.querySelector('select[name="tipo_salida"]');
        const infoCliente = document.getElementById('info-cliente');
        
        if (selectTipoSalida && infoCliente) {
            const tipoSalida = selectTipoSalida.value;
            const requiereCliente = ['venta', 'venta_mayoreo', 'transferencia'].includes(tipoSalida);
            
            if (requiereCliente) {
                infoCliente.classList.remove('d-none');
            } else {
                infoCliente.classList.add('d-none');
            }
        }
    }

    toggleGarantiaInfo() {
        const checkbox = document.getElementById('con_garantia');
        const infoGarantia = document.getElementById('info-garantia');
        
        if (checkbox && infoGarantia) {
            if (checkbox.checked) {
                infoGarantia.classList.remove('d-none');
            } else {
                infoGarantia.classList.add('d-none');
            }
        }
    }

    toggleOtroMotivo() {
        const selectMotivo = document.getElementById('motivo_devolucion');
        const otroMotivoContainer = document.getElementById('otro-motivo-container');
        const otroMotivoInput = document.querySelector('input[name="motivo_otro_detalle"]');
        
        if (selectMotivo && otroMotivoContainer) {
            if (selectMotivo.value === 'otro') {
                otroMotivoContainer.style.display = 'block';
                if (otroMotivoInput) {
                    otroMotivoInput.setAttribute('required', 'required');
                }
            } else {
                otroMotivoContainer.style.display = 'none';
                if (otroMotivoInput) {
                    otroMotivoInput.removeAttribute('required');
                    otroMotivoInput.value = '';
                }
            }
        }
    }

    async handleTabChange(event) {
        const targetTab = event.target.getAttribute('data-bs-target');
        
        switch (targetTab) {
            case '#transito':
                await this.loadProductosTransito();
                break;
            case '#garantias':
                await this.loadGarantiasActivas();
                break;
        }
    }

    async loadProductosTransito() {
        const container = document.getElementById('productos-transito');
        container.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
        
        try {
            // Simular carga - en producción sería una llamada AJAX
            setTimeout(() => {
                container.innerHTML = `
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-truck me-2"></i>Productos en Tránsito</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Función en desarrollo. Aquí se mostrarán todos los productos que están en proceso de entrega.
                            </div>
                        </div>
                    </div>
                `;
            }, 500);
        } catch (error) {
            container.innerHTML = '<div class="alert alert-danger">Error al cargar productos en tránsito</div>';
        }
    }

    async loadGarantiasActivas() {
        const container = document.getElementById('garantias-activas');
        container.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
        
        try {
            // Simular carga - en producción sería una llamada AJAX
            setTimeout(() => {
                container.innerHTML = `
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-shield-alt me-2"></i>Garantías Activas</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Función en desarrollo. Aquí se mostrarán todas las garantías activas y por vencer.
                            </div>
                        </div>
                    </div>
                `;
            }, 500);
        } catch (error) {
            container.innerHTML = '<div class="alert alert-danger">Error al cargar garantías activas</div>';
        }
    }

    async fetchData(action, formData = null) {
        const options = {
            method: 'POST',
            headers: formData ? {} : { 'Content-Type': 'application/json' }
        };
        
        if (formData) {
            options.body = formData;
        } else {
            const data = new FormData();
            data.append('action', action);
            options.body = data;
        }
        
        const response = await fetch('salidas_mejorado.php', options);
        return await response.json();
    }

    showLoading(show) {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.classList.toggle('d-none', !show);
        }
    }

    showToast(message, type = 'success') {
        const toast = document.getElementById('toast-notification');
        const toastMessage = document.getElementById('toast-message');
        const icon = toast.querySelector('.toast-header i');
        
        // Actualizar contenido
        toastMessage.textContent = message;
        
        // Actualizar icono y color según el tipo
        icon.className = type === 'success' ? 'fas fa-check-circle text-success me-2' : 'fas fa-exclamation-circle text-danger me-2';
        
        // Mostrar toast
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }
}

// Funciones globales para los botones de las tarjetas
function verSeguimiento(idSalida) {
    const modal = new bootstrap.Modal(document.getElementById('modalSeguimiento'));
    
    // Cargar contenido del seguimiento
    document.getElementById('seguimiento-content').innerHTML = `
        <div class="text-center">
            <div class="spinner-border"></div>
            <p>Cargando historial de seguimiento...</p>
        </div>
    `;
    
    modal.show();
    
    // Simular carga de datos
    setTimeout(() => {
        document.getElementById('seguimiento-content').innerHTML = `
            <div class="seguimiento-timeline">
                <div class="timeline-item">
                    <div class="d-flex justify-content-between">
                        <strong>Salida registrada</strong>
                        <small class="text-muted">Hace 2 días</small>
                    </div>
                    <p class="mb-1">Producto registrado para salida tipo: Venta</p>
                    <small class="text-muted">Usuario: Sistema</small>
                </div>
                <div class="timeline-item">
                    <div class="d-flex justify-content-between">
                        <strong>En preparación</strong>
                        <small class="text-muted">Hace 2 días</small>
                    </div>
                    <p class="mb-1">Producto preparado para envío</p>
                    <small class="text-muted">Usuario: Sistema</small>
                </div>
                <div class="timeline-item">
                    <div class="d-flex justify-content-between">
                        <strong>Enviado</strong>
                        <small class="text-muted">Hace 1 día</small>
                    </div>
                    <p class="mb-1">Producto despachado para entrega</p>
                    <small class="text-muted">Usuario: Sistema</small>
                </div>
            </div>
        `;
    }, 1000);
}

function actualizarEstado(idSalida) {
    document.getElementById('actualizar_id_salida').value = idSalida;
    const modal = new bootstrap.Modal(document.getElementById('modalActualizarEstado'));
    modal.show();
}

function procesarDevolucion(idSalida, idProd) {
    document.getElementById('devolucion_id_salida').value = idSalida;
    document.getElementById('devolucion_id_prod').value = idProd;
    
    // Resetear el campo de motivo y ocultar el campo "otro"
    const motivoSelect = document.getElementById('motivo_devolucion');
    const otroContainer = document.getElementById('otro-motivo-container');
    if (motivoSelect) {
        motivoSelect.value = '';
    }
    if (otroContainer) {
        otroContainer.style.display = 'none';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('modalDevolucion'));
    modal.show();
}

function marcarComoEntregado() {
    // Función para marcar producto como entregado desde el modal de tránsito
    app.showToast('Funcionalidad en desarrollo', 'info');
}

function utilizarGarantia() {
    // Función para procesar uso de garantía
    app.showToast('Funcionalidad en desarrollo', 'info');
}

// Función global para manejar el campo motivo
window.toggleMotivoOtro = function() {
    const selectMotivo = document.getElementById('motivo_devolucion');
    const otroContainer = document.getElementById('otro-motivo-container');
    const otroInput = document.querySelector('input[name="motivo_otro_detalle"]');
    
    if (selectMotivo && otroContainer) {
        if (selectMotivo.value === 'otro') {
            otroContainer.style.display = 'block';
            if (otroInput) {
                otroInput.setAttribute('required', 'required');
            }
        } else {
            otroContainer.style.display = 'none';
            if (otroInput) {
                otroInput.removeAttribute('required');
                otroInput.value = '';
            }
        }
    }
};

// Inicializar la aplicación cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.app = new GestionSalidas();
});