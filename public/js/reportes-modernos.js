/**
 * Módulo JavaScript para Reportes Modernos
 * Manejo avanzado de reportes interactivos con visualizaciones dinámicas
 */

class ReportesModernos {
    constructor() {
        this.charts = new Map();
        this.currentData = null;
        this.filters = {};
        this.isLoading = false;
        
        this.initializeEventListeners();
        this.loadInitialData();
    }
    
    /**
     * Inicializar event listeners
     */
    initializeEventListeners() {
        // Tabs de navegación
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                this.handleTabChange(e.target.getAttribute('data-bs-target'));
            });
        });
        
        // Filtros avanzados
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('filter-input')) {
                this.handleFilterChange(e.target);
            }
        });
        
        // Botones de exportación
        document.querySelectorAll('[data-export]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                this.exportReport(btn.dataset.export, btn.dataset.reportType);
            });
        });
        
        // Actualización automática
        document.querySelectorAll('[data-auto-refresh]').forEach(element => {
            const interval = parseInt(element.dataset.autoRefresh) * 1000;
            setInterval(() => {
                this.refreshData(element.dataset.reportType);
            }, interval);
        });
    }
    
    /**
     * Cargar datos iniciales
     */
    async loadInitialData() {
        try {
            await this.loadDashboardMetrics();
            await this.loadInventoryChart();
            await this.loadTrendsChart();
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
            this.showError('Error al cargar los datos iniciales');
        }
    }
    
    /**
     * Manejar cambio de tab
     */
    handleTabChange(targetTab) {
        switch (targetTab) {
            case '#dashboard':
                this.loadDashboardMetrics();
                break;
            case '#predefinidos':
                this.loadPredefinedReports();
                break;
            case '#personalizado':
                this.initializeCustomReportBuilder();
                break;
            case '#analisis':
                this.loadAdvancedAnalysis();
                break;
        }
    }
    
    /**
     * Cargar métricas del dashboard
     */
    async loadDashboardMetrics() {
        try {
            this.showLoading('metricsContainer');
            
            const response = await fetch('reportes_modernos.php?action=dashboard_data');
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text();
            let result;
            
            try {
                result = JSON.parse(text);
            } catch (e) {
                console.error('Response text:', text);
                throw new Error('La respuesta del servidor no es un JSON válido');
            }
            
            if (result.success) {
                this.renderMetricsGrid(result.data);
                this.renderInventoryStatus(result.data.inventario);
                this.renderTopProducts(result.data.top_productos);
                this.renderMovementsChart(result.data.movimientos);
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            this.showError('Error al cargar métricas: ' + error.message);
        } finally {
            this.hideLoading('metricsContainer');
        }
    }
    
    /**
     * Renderizar grid de métricas
     */
    renderMetricsGrid(data) {
        const container = document.getElementById('metricsContainer');
        if (!container) return;
        
        const inventario = data.inventario;
        const movimientos = data.movimientos || [];
        const totalMovimientosHoy = movimientos.reduce((sum, mov) => sum + parseInt(mov.total_salidas || 0), 0);
        
        container.innerHTML = `
            <div class="metric-card-modern scale-in">
                <div class="metric-value-modern">${this.formatNumber(inventario.total_productos)}</div>
                <div class="metric-label-modern">Productos Totales</div>
                <div class="metric-change positive">
                    <i class="fas fa-box"></i>
                    <span>Total en catálogo</span>
                </div>
            </div>
            
            <div class="metric-card-modern success scale-in" style="animation-delay: 0.1s">
                <div class="metric-value-modern">${this.formatNumber(inventario.total_stock)}</div>
                <div class="metric-label-modern">Stock Total</div>
                <div class="metric-change positive">
                    <i class="fas fa-warehouse"></i>
                    <span>Unidades en inventario</span>
                </div>
            </div>
            
            <div class="metric-card-modern warning scale-in" style="animation-delay: 0.2s">
                <div class="metric-value-modern">${this.formatNumber(inventario.productos_stock_bajo)}</div>
                <div class="metric-label-modern">Stock Bajo</div>
                <div class="metric-change ${inventario.productos_stock_bajo > 0 ? 'negative' : 'positive'}">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>≤ 10 unidades</span>
                </div>
            </div>
            
            <div class="metric-card-modern danger scale-in" style="animation-delay: 0.3s">
                <div class="metric-value-modern">${this.formatNumber(inventario.productos_stock_critico)}</div>
                <div class="metric-label-modern">Stock Crítico</div>
                <div class="metric-change negative">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>≤ 5 unidades</span>
                </div>
            </div>
        `;
    }
    
    /**
     * Renderizar estado de inventario
     */
    renderInventoryStatus(inventario) {
        // Crear gráfico de estado de inventario si existe el canvas
        const ctx = document.getElementById('inventarioChart');
        if (!ctx) return;
        
        if (this.charts.has('inventarioStatus')) {
            this.charts.get('inventarioStatus').destroy();
        }
        
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Stock Normal', 'Stock Bajo', 'Stock Crítico'],
                datasets: [{
                    data: [
                        inventario.total_productos - inventario.productos_stock_bajo,
                        inventario.productos_stock_bajo - inventario.productos_stock_critico,
                        inventario.productos_stock_critico
                    ],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107', 
                        '#dc3545'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} productos (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
        
        this.charts.set('inventarioStatus', chart);
    }
    
    /**
     * Renderizar top productos
     */
    renderTopProducts(topProductos) {
        const container = document.getElementById('topProductosContainer');
        if (!container || !topProductos || topProductos.length === 0) return;
        
        let html = '<div class="list-group list-group-flush">';
        
        topProductos.slice(0, 5).forEach((producto, index) => {
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${producto.nombre}</strong>
                        <br><small class="text-muted">${producto.num_movimientos || 0} movimientos</small>
                    </div>
                    <span class="badge bg-primary rounded-pill">${producto.total_movido || 0}</span>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    /**
     * Renderizar gráfico de movimientos
     */
    renderMovementsChart(movimientos) {
        const ctx = document.getElementById('movimientosChart');
        if (!ctx || !movimientos || movimientos.length === 0) return;
        
        if (this.charts.has('movimientos')) {
            this.charts.get('movimientos').destroy();
        }
        
        // Preparar datos ordenados por fecha
        const sortedMovimientos = movimientos.sort((a, b) => new Date(a.fecha) - new Date(b.fecha));
        const fechas = sortedMovimientos.map(m => m.fecha);
        const cantidades = sortedMovimientos.map(m => parseInt(m.total_salidas || 0));
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: fechas,
                datasets: [{
                    label: 'Salidas Diarias',
                    data: cantidades,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        ticks: {
                            maxTicksLimit: 10
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
        
        this.charts.set('movimientos', chart);
    }
    
    /**
     * Cargar gráfico de inventario
     */
    async loadInventoryChart() {
        try {
            const response = await fetch('api/reportes.php?action=inventario_avanzado');
            const result = await response.json();
            
            if (result.success) {
                this.renderInventoryChart(result.datos);
            }
        } catch (error) {
            console.error('Error cargando gráfico de inventario:', error);
        }
    }
    
    /**
     * Renderizar gráfico de inventario
     */
    renderInventoryChart(data) {
        const ctx = document.getElementById('inventoryChart');
        if (!ctx) return;
        
        // Procesar datos para el gráfico
        const stockLevels = {
            'CRÍTICO': data.filter(p => p.nivel_stock === 'CRÍTICO').length,
            'BAJO': data.filter(p => p.nivel_stock === 'BAJO').length,
            'NORMAL': data.filter(p => p.nivel_stock === 'NORMAL').length,
            'ALTO': data.filter(p => p.nivel_stock === 'ALTO').length
        };
        
        if (this.charts.has('inventory')) {
            this.charts.get('inventory').destroy();
        }
        
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(stockLevels),
                datasets: [{
                    data: Object.values(stockLevels),
                    backgroundColor: [
                        '#ea4335', // Crítico - Rojo
                        '#fbbc04', // Bajo - Amarillo
                        '#34a853', // Normal - Verde
                        '#4285f4'  // Alto - Azul
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} productos (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
        
        this.charts.set('inventory', chart);
    }
    
    /**
     * Cargar gráfico de tendencias
     */
    async loadTrendsChart() {
        try {
            const response = await fetch('api/reportes.php?action=analisis_tendencias&meses=6');
            const result = await response.json();
            
            if (result.success) {
                this.renderTrendsChart(result.datos);
            }
        } catch (error) {
            console.error('Error cargando gráfico de tendencias:', error);
        }
    }
    
    /**
     * Renderizar gráfico de tendencias
     */
    renderTrendsChart(data) {
        const ctx = document.getElementById('trendsChart');
        if (!ctx) return;
        
        // Agrupar datos por mes
        const monthlyData = {};
        data.forEach(item => {
            if (!monthlyData[item.mes]) {
                monthlyData[item.mes] = {
                    operaciones: 0,
                    unidades: 0
                };
            }
            monthlyData[item.mes].operaciones += parseInt(item.total_operaciones);
            monthlyData[item.mes].unidades += parseInt(item.total_unidades);
        });
        
        const months = Object.keys(monthlyData).sort().slice(-6);
        
        if (this.charts.has('trends')) {
            this.charts.get('trends').destroy();
        }
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: months.map(m => {
                    const [year, month] = m.split('-');
                    return new Date(year, month - 1).toLocaleDateString('es-ES', {
                        month: 'short',
                        year: '2-digit'
                    });
                }),
                datasets: [{
                    label: 'Operaciones',
                    data: months.map(m => monthlyData[m].operaciones),
                    borderColor: '#4285f4',
                    backgroundColor: 'rgba(66, 133, 244, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                }, {
                    label: 'Unidades Movidas',
                    data: months.map(m => monthlyData[m].unidades),
                    borderColor: '#34a853',
                    backgroundColor: 'rgba(52, 168, 83, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Mes'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Operaciones'
                        },
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Unidades'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
        
        this.charts.set('trends', chart);
    }
    
    /**
     * Generar reporte personalizado
     */
    async generateCustomReport() {
        const form = document.getElementById('customReportForm');
        if (!form) return;
        
        const formData = new FormData(form);
        const reportData = {
            tabla: formData.get('tabla'),
            columnas: Array.from(document.querySelectorAll('input[name="columnas[]"]:checked')).map(cb => cb.value),
            filtros: this.collectFilters(),
            orden: formData.get('orden'),
            limite: parseInt(formData.get('limite'))
        };
        
        if (!reportData.tabla || reportData.columnas.length === 0) {
            this.showError('Selecciona una tabla y al menos una columna');
            return;
        }
        
        try {
            this.showLoading('customReportResult');
            
            const response = await fetch('api/reportes.php?action=reporte_personalizado', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(reportData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.renderCustomReportResult(result.datos, reportData.columnas);
                document.getElementById('customReportResult').style.display = 'block';
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            this.showError('Error generando reporte: ' + error.message);
        } finally {
            this.hideLoading('customReportResult');
        }
    }
    
    /**
     * Renderizar resultado de reporte personalizado
     */
    renderCustomReportResult(data, columns) {
        const container = document.getElementById('customReportContent');
        if (!container || !data.length) return;
        
        let html = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6><i class="fas fa-table me-2"></i>Resultados: ${data.length} registros</h6>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-modern-success" onclick="reportesModernos.exportReport('excel', 'personalizado')">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                    <button class="btn btn-modern-danger" onclick="reportesModernos.exportReport('pdf', 'personalizado')">
                        <i class="fas fa-file-pdf"></i> PDF
                    </button>
                    <button class="btn btn-modern-primary" onclick="reportesModernos.exportReport('csv', 'personalizado')">
                        <i class="fas fa-file-csv"></i> CSV
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table-modern">
                    <thead>
                        <tr>
        `;
        
        // Cabeceras
        columns.forEach(col => {
            html += `<th>${this.formatColumnName(col)}</th>`;
        });
        
        html += '</tr></thead><tbody>';
        
        // Filas de datos
        data.forEach(row => {
            html += '<tr>';
            columns.forEach(col => {
                const value = row[col] || '';
                html += `<td>${this.formatCellValue(value, col)}</td>`;
            });
            html += '</tr>';
        });
        
        html += '</tbody></table></div>';
        
        container.innerHTML = html;
        
        // Scroll al resultado
        container.closest('.report-container').scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    }
    
    /**
     * Exportar reporte
     */
    async exportReport(format, reportType) {
        try {
            const url = `api/reportes.php?action=exportar&formato=${format}&tipo=${reportType}`;
            const link = document.createElement('a');
            link.href = url;
            link.download = `reporte_${reportType}_${new Date().toISOString().split('T')[0]}.${format}`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.showSuccess(`Reporte exportado en formato ${format.toUpperCase()}`);
        } catch (error) {
            this.showError('Error exportando reporte: ' + error.message);
        }
    }
    
    /**
     * Utilidades de formato
     */
    formatNumber(num) {
        return parseInt(num).toLocaleString('es-ES');
    }
    
    formatColumnName(col) {
        return col.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    formatCellValue(value, column) {
        // Formatear valores específicos según el tipo de columna
        if (column.includes('fecha')) {
            return value ? new Date(value).toLocaleDateString('es-ES') : '';
        }
        
        if (column.includes('stock') || column.includes('cantidad')) {
            return this.formatNumber(value);
        }
        
        if (column === 'nivel_stock') {
            const badges = {
                'CRÍTICO': '<span class="badge-modern badge-modern-danger">Crítico</span>',
                'BAJO': '<span class="badge-modern badge-modern-warning">Bajo</span>',
                'NORMAL': '<span class="badge-modern badge-modern-success">Normal</span>',
                'ALTO': '<span class="badge-modern badge-modern-primary">Alto</span>'
            };
            return badges[value] || value;
        }
        
        return value;
    }
    
    /**
     * Recopilar filtros del formulario
     */
    collectFilters() {
        const filters = [];
        document.querySelectorAll('.filter-row').forEach(row => {
            const campo = row.querySelector('select[name*="[campo]"]')?.value;
            const operador = row.querySelector('select[name*="[operador]"]')?.value;
            const valor = row.querySelector('input[name*="[valor]"]')?.value;
            
            if (campo && operador && valor) {
                filters.push({ campo, operador, valor });
            }
        });
        return filters;
    }
    
    /**
     * Mostrar indicador de carga
     */
    showLoading(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = '<div class="loading-spinner-modern"></div>';
        
        container.style.position = 'relative';
        container.appendChild(overlay);
        
        this.isLoading = true;
    }
    
    /**
     * Ocultar indicador de carga
     */
    hideLoading(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const overlay = container.querySelector('.loading-overlay');
        if (overlay) {
            overlay.remove();
        }
        
        this.isLoading = false;
    }
    
    /**
     * Mostrar mensaje de error
     */
    showError(message) {
        this.showNotification(message, 'danger');
    }
    
    /**
     * Mostrar mensaje de éxito
     */
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    
    /**
     * Mostrar notificación
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert-modern alert-modern-${type} position-fixed`;
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease-out;
        `;
        notification.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <span>${message}</span>
                <button type="button" class="btn-close" onclick="this.closest('.alert-modern').remove()"></button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove después de 5 segundos
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }
    
    /**
     * Actualizar datos específicos
     */
    async refreshData(reportType) {
        if (this.isLoading) return;
        
        switch (reportType) {
            case 'dashboard':
                await this.loadDashboardMetrics();
                break;
            case 'inventory':
                await this.loadInventoryChart();
                break;
            case 'trends':
                await this.loadTrendsChart();
                break;
        }
    }
}

// Inicializar cuando se carga el DOM
document.addEventListener('DOMContentLoaded', function() {
    window.reportesModernos = new ReportesModernos();
});

// Funciones globales para compatibilidad
function actualizarDashboard() {
    if (window.reportesModernos) {
        window.reportesModernos.loadDashboardMetrics();
    }
}

function ejecutarReportePredefinido(reporteId) {
    if (window.reportesModernos) {
        console.log('Ejecutando reporte predefinido:', reporteId);
        // Implementar lógica específica para cada reporte predefinido
    }
}

function generarReportePersonalizado() {
    if (window.reportesModernos) {
        window.reportesModernos.generateCustomReport();
    }
}

// CSS animations para notificaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);