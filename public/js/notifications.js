/**
 * Sistema de Notificaciones Emergentes para Inventixor
 * Maneja las notificaciones de cambios en productos, categorías y subcategorías
 * @version 2.0
 */

class NotificationSystem {
    constructor() {
        this.initializeStyles();
        this.notifications = [];
        this.maxNotifications = 5;
    }

    /**
     * Inicializa los estilos CSS para las notificaciones
     */
    initializeStyles() {
        if (document.getElementById('notification-styles')) {
            return; // Ya existe
        }

        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            /* Contenedor principal de notificaciones */
            .notification-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
                pointer-events: none;
            }

            /* Estilos de notificación individual */
            .notification {
                background: white;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
                margin-bottom: 12px;
                padding: 16px 20px;
                border-left: 4px solid #007bff;
                transform: translateX(450px);
                opacity: 0;
                pointer-events: auto;
                transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                position: relative;
                overflow: hidden;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
            }

            .notification.show {
                transform: translateX(0);
                opacity: 1;
            }

            .notification.hiding {
                transform: translateX(450px);
                opacity: 0;
            }

            /* Tipos de notificación */
            .notification.success {
                border-left-color: #28a745;
                background: linear-gradient(135deg, #f8fff9 0%, #ffffff 100%);
            }

            .notification.error {
                border-left-color: #dc3545;
                background: linear-gradient(135deg, #fff8f8 0%, #ffffff 100%);
            }

            .notification.warning {
                border-left-color: #ffc107;
                background: linear-gradient(135deg, #fffdf7 0%, #ffffff 100%);
            }

            .notification.info {
                border-left-color: #17a2b8;
                background: linear-gradient(135deg, #f7fdff 0%, #ffffff 100%);
            }

            /* Header de la notificación */
            .notification-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 8px;
            }

            .notification-title {
                display: flex;
                align-items: center;
                font-weight: 600;
                font-size: 14px;
                color: #2c3e50;
                margin: 0;
            }

            .notification-icon {
                margin-right: 8px;
                font-size: 16px;
            }

            .notification.success .notification-icon {
                color: #28a745;
            }

            .notification.error .notification-icon {
                color: #dc3545;
            }

            .notification.warning .notification-icon {
                color: #ffc107;
            }

            .notification.info .notification-icon {
                color: #17a2b8;
            }

            /* Botón de cerrar */
            .notification-close {
                background: none;
                border: none;
                font-size: 18px;
                color: #6c757d;
                cursor: pointer;
                padding: 0;
                margin-left: 10px;
                transition: color 0.2s;
                line-height: 1;
            }

            .notification-close:hover {
                color: #495057;
            }

            /* Mensaje de la notificación */
            .notification-message {
                font-size: 13px;
                color: #6c757d;
                margin: 0;
                line-height: 1.4;
            }

            /* Metadatos adicionales */
            .notification-meta {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px solid rgba(0, 0, 0, 0.1);
                font-size: 11px;
                color: #9ca3af;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            /* Barra de progreso */
            .notification-progress {
                position: absolute;
                bottom: 0;
                left: 0;
                height: 3px;
                background: rgba(0, 123, 255, 0.3);
                transition: width 0.1s linear;
            }

            .notification.success .notification-progress {
                background: rgba(40, 167, 69, 0.3);
            }

            .notification.error .notification-progress {
                background: rgba(220, 53, 69, 0.3);
            }

            .notification.warning .notification-progress {
                background: rgba(255, 193, 7, 0.3);
            }

            /* Efectos hover */
            .notification:hover {
                box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
                transform: translateY(-2px) translateX(0);
            }

            /* Responsividad */
            @media (max-width: 768px) {
                .notification-container {
                    left: 20px;
                    right: 20px;
                    max-width: none;
                }

                .notification {
                    transform: translateY(-100px);
                }

                .notification.show {
                    transform: translateY(0);
                }

                .notification.hiding {
                    transform: translateY(-100px);
                }
            }
        `;

        document.head.appendChild(style);
    }

    /**
     * Crea el contenedor de notificaciones si no existe
     */
    getContainer() {
        let container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
        return container;
    }

    /**
     * Muestra una notificación genérica
     */
    show(title, message, type = 'info', duration = 5000, metadata = null) {
        const container = this.getContainer();
        
        // Limitar número de notificaciones
        if (this.notifications.length >= this.maxNotifications) {
            this.notifications[0].remove();
        }

        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        
        const iconMap = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };

        const currentTime = new Date().toLocaleTimeString('es-ES', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });

        notification.innerHTML = `
            <div class="notification-header">
                <h6 class="notification-title">
                    <i class="notification-icon ${iconMap[type]}"></i>
                    ${title}
                </h6>
                <button class="notification-close" type="button">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="notification-message">${message}</p>
            ${metadata ? `
            <div class="notification-meta">
                <span>${metadata}</span>
                <span>${currentTime}</span>
            </div>
            ` : `
            <div class="notification-meta">
                <span></span>
                <span>${currentTime}</span>
            </div>
            `}
            <div class="notification-progress" style="width: 100%"></div>
        `;

        // Agregar event listeners
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => this.hide(notification));

        // Hover para pausar auto-close
        let autoCloseTimer;
        let progressTimer;
        let startTime;
        let remainingTime = duration;
        let isPaused = false;

        const startAutoClose = () => {
            if (duration > 0) {
                startTime = Date.now();
                const progressBar = notification.querySelector('.notification-progress');
                
                autoCloseTimer = setTimeout(() => {
                    this.hide(notification);
                }, remainingTime);

                // Animar barra de progreso
                const animateProgress = () => {
                    if (!isPaused) {
                        const elapsed = Date.now() - startTime;
                        const remaining = Math.max(0, remainingTime - elapsed);
                        const progress = (remaining / duration) * 100;
                        progressBar.style.width = progress + '%';
                        
                        if (remaining > 0) {
                            progressTimer = requestAnimationFrame(animateProgress);
                        }
                    }
                };
                progressTimer = requestAnimationFrame(animateProgress);
            }
        };

        const pauseAutoClose = () => {
            if (autoCloseTimer && !isPaused) {
                clearTimeout(autoCloseTimer);
                cancelAnimationFrame(progressTimer);
                remainingTime = remainingTime - (Date.now() - startTime);
                isPaused = true;
            }
        };

        const resumeAutoClose = () => {
            if (isPaused) {
                isPaused = false;
                startAutoClose();
            }
        };

        notification.addEventListener('mouseenter', pauseAutoClose);
        notification.addEventListener('mouseleave', resumeAutoClose);

        // Agregar al DOM
        container.appendChild(notification);
        this.notifications.push(notification);

        // Mostrar con animación
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        // Iniciar auto-close
        startAutoClose();

        return notification;
    }

    /**
     * Oculta una notificación específica
     */
    hide(notification) {
        notification.classList.add('hiding');
        notification.classList.remove('show');
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
            
            const index = this.notifications.indexOf(notification);
            if (index > -1) {
                this.notifications.splice(index, 1);
            }
        }, 400);
    }

    /**
     * Notificación específica para cambios en productos
     */
    showProductChange(action, message, type = 'success', productData = null) {
        const actionMap = {
            create: { title: 'Producto Creado', icon: 'fas fa-plus-circle' },
            update: { title: 'Producto Actualizado', icon: 'fas fa-edit' },
            delete: { title: 'Producto Eliminado', icon: 'fas fa-trash' }
        };

        const config = actionMap[action] || actionMap.create;
        let metadata = null;
        
        if (productData) {
            if (productData.id && productData.nombre) {
                metadata = `ID: ${productData.id} - ${productData.nombre}`;
            } else if (productData.nombre) {
                metadata = `Producto: ${productData.nombre}`;
            } else if (typeof productData === 'string') {
                metadata = productData;
            }
        }

        return this.show(config.title, message, type, 6000, metadata);
    }

    /**
     * Notificación específica para cambios en categorías
     */
    showCategoryChange(action, message, type = 'success', categoryData = null) {
        const actionMap = {
            create: { title: 'Categoría Creada', icon: 'fas fa-plus-circle' },
            update: { title: 'Categoría Actualizada', icon: 'fas fa-edit' },
            delete: { title: 'Categoría Eliminada', icon: 'fas fa-trash' }
        };

        const config = actionMap[action] || actionMap.create;
        const metadata = categoryData ? `Categoría: ${categoryData.nombre || categoryData}` : null;

        return this.show(config.title, message, type, 6000, metadata);
    }

    /**
     * Notificación específica para cambios en subcategorías
     */
    showSubcategoryChange(action, message, type = 'success', subcategoryData = null) {
        const actionMap = {
            create: { title: 'Subcategoría Creada', icon: 'fas fa-plus-circle' },
            update: { title: 'Subcategoría Actualizada', icon: 'fas fa-edit' },
            delete: { title: 'Subcategoría Eliminada', icon: 'fas fa-trash' }
        };

        const config = actionMap[action] || actionMap.create;
        const metadata = subcategoryData ? `Subcategoría: ${subcategoryData.nombre || subcategoryData}` : null;

        return this.show(config.title, message, type, 6000, metadata);
    }

    /**
     * Notificación específica para cambios en usuarios
     */
    showUserChange(action, message, type = 'success', userData = null) {
        const actionMap = {
            create: { title: 'Usuario Creado', icon: 'fas fa-user-plus' },
            update: { title: 'Usuario Actualizado', icon: 'fas fa-user-edit' },
            delete: { title: 'Usuario Eliminado', icon: 'fas fa-user-minus' }
        };

        const config = actionMap[action] || actionMap.create;
        let metadata = null;
        
        if (userData) {
            if (userData.num_doc && userData.nombres) {
                metadata = `Doc: ${userData.num_doc} - ${userData.nombres}`;
            } else if (userData.nombres) {
                metadata = `Usuario: ${userData.nombres}`;
            } else if (typeof userData === 'string') {
                metadata = userData;
            }
        }

        return this.show(config.title, message, type, 6000, metadata);
    }

    /**
     * Muestra notificación específica para cambios en proveedores
     */
    showProviderChange(action, message, type = 'success', providerData = null) {
        const actionMap = {
            create: { title: 'Proveedor Creado', icon: 'fas fa-building-plus' },
            update: { title: 'Proveedor Actualizado', icon: 'fas fa-building-edit' },
            delete: { title: 'Proveedor Eliminado', icon: 'fas fa-building-minus' }
        };

        const config = actionMap[action] || actionMap.create;
        let metadata = null;
        
        if (providerData) {
            if (providerData.id_nit && providerData.razon_social) {
                metadata = `NIT: ${providerData.id_nit} - ${providerData.razon_social}`;
            } else if (providerData.razon_social) {
                metadata = `Proveedor: ${providerData.razon_social}`;
            } else if (typeof providerData === 'string') {
                metadata = providerData;
            }
        }

        return this.show(config.title, message, type, 6000, metadata);
    }

    /**
     * Muestra notificación específica para cambios en salidas
     */
    showSalidaChange(action, message, type = 'success', salidaData = null) {
        const actionMap = {
            create: { title: 'Salida Registrada', icon: 'fas fa-box-open' },
            update: { title: 'Salida Actualizada', icon: 'fas fa-edit' },
            delete: { title: 'Salida Eliminada', icon: 'fas fa-undo' }
        };

        const config = actionMap[action] || actionMap.create;
        let metadata = null;
        
        if (salidaData) {
            if (salidaData.id_salida && salidaData.producto) {
                metadata = `Salida #${salidaData.id_salida} - ${salidaData.producto}`;
            } else if (salidaData.producto) {
                metadata = `Producto: ${salidaData.producto}`;
            } else if (typeof salidaData === 'string') {
                metadata = salidaData;
            }
        }

        return this.show(config.title, message, type, 6000, metadata);
    }

    /**
     * Muestra notificación específica para cambios en reportes
     */
    showReporteChange(action, message, type = 'success', reporteData = null) {
        const actionMap = {
            create: { title: 'Reporte Generado', icon: 'fas fa-chart-line' },
            update: { title: 'Reporte Actualizado', icon: 'fas fa-edit' },
            delete: { title: 'Reporte Eliminado', icon: 'fas fa-trash' }
        };

        const config = actionMap[action] || actionMap.create;
        let metadata = null;
        
        if (reporteData) {
            if (reporteData.id_repor && reporteData.titulo) {
                metadata = `Reporte #${reporteData.id_repor} - ${reporteData.titulo}`;
            } else if (reporteData.titulo) {
                metadata = `Reporte: ${reporteData.titulo}`;
            } else if (typeof reporteData === 'string') {
                metadata = reporteData;
            }
        }

        return this.show(config.title, message, type, 6000, metadata);
    }

    /**
     * Muestra notificación específica para cambios en alertas del sistema
     */
    showAlertaChange(action, message, type = 'success', alertaData = null) {
        const actionMap = {
            create: { title: 'Alerta Creada', icon: 'fas fa-exclamation-triangle' },
            update: { title: 'Alerta Actualizada', icon: 'fas fa-edit' },
            delete: { title: 'Alerta Eliminada', icon: 'fas fa-trash-alt' },
            resolve: { title: 'Alerta Resuelta', icon: 'fas fa-check-circle' }
        };

        const config = actionMap[action] || actionMap.create;
        let metadata = null;
        
        if (alertaData) {
            if (alertaData.id_alerta && alertaData.tipo) {
                metadata = `Alerta #${alertaData.id_alerta} - ${alertaData.tipo}`;
            } else if (alertaData.tipo) {
                metadata = `Tipo: ${alertaData.tipo}`;
            } else if (typeof alertaData === 'string') {
                metadata = alertaData;
            }
        }

        return this.show(config.title, message, type, 6000, metadata);
    }

    /**
     * Limpia todas las notificaciones
     */
    clearAll() {
        this.notifications.forEach(notification => {
            this.hide(notification);
        });
    }

    /**
     * Notificación para errores de validación
     */
    showValidationError(field, message) {
        return this.show(
            'Error de Validación',
            `${field}: ${message}`,
            'error',
            8000
        );
    }

    /**
     * Notificación para confirmaciones de acción
     */
    showConfirmation(message, onConfirm, onCancel = null) {
        const notification = this.show(
            'Confirmar Acción',
            message,
            'warning',
            0 // No auto-close
        );

        // Agregar botones de confirmación
        const buttonsHtml = `
            <div style="margin-top: 12px; display: flex; gap: 8px; justify-content: flex-end;">
                <button class="btn btn-sm btn-outline-secondary confirm-cancel">Cancelar</button>
                <button class="btn btn-sm btn-warning confirm-ok">Confirmar</button>
            </div>
        `;

        notification.querySelector('.notification-message').insertAdjacentHTML('afterend', buttonsHtml);

        // Event listeners para botones
        notification.querySelector('.confirm-ok').addEventListener('click', () => {
            this.hide(notification);
            if (onConfirm) onConfirm();
        });

        notification.querySelector('.confirm-cancel').addEventListener('click', () => {
            this.hide(notification);
            if (onCancel) onCancel();
        });

        return notification;
    }
}

// Crear instancia global
window.NotificationSystem = NotificationSystem;

// Auto-inicialización para compatibilidad
document.addEventListener('DOMContentLoaded', function() {
    if (!window.notificationSystem) {
        window.notificationSystem = new NotificationSystem();
    }
});