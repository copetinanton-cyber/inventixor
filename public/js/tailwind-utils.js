/**
 * Utilidades Responsive con Tailwind CSS
 * Sistema Inventixor - JavaScript Framework
 */

class TailwindUtils {
    /**
     * Inicializar utilidades generales
     */
    static init() {
        this.setupMobileOptimizations();
        this.setupNotificationSystem();
        this.setupFormEnhancements();
        this.setupTableResponsive();
        this.setupModalSystem();
        console.log('TailwindUtils initialized');
    }

    /**
     * Optimizaciones para dispositivos móviles
     */
    static setupMobileOptimizations() {
        // Detectar si es dispositivo móvil
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            // Mejorar experiencia táctil
            document.body.classList.add('touch-device');
            
            // Optimizar scroll en iOS
            document.body.style.webkitOverflowScrolling = 'touch';
            
            // Prevenir zoom en inputs en iOS
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.style.fontSize === '' || parseFloat(input.style.fontSize) < 16) {
                    input.style.fontSize = '16px';
                }
            });
        }
        
        // Listener para cambios de orientación
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                this.adjustForOrientation();
            }, 100);
        });
    }

    /**
     * Ajustar interfaz para cambios de orientación
     */
    static adjustForOrientation() {
        // Recalcular alturas de modales
        const modales = document.querySelectorAll('.modal-container');
        modales.forEach(modal => {
            modal.style.maxHeight = window.innerHeight - 40 + 'px';
        });
        
        // Ajustar tablas responsivas
        this.refreshResponsiveTables();
    }

    /**
     * Sistema de notificaciones mejorado para Tailwind
     */
    static setupNotificationSystem() {
        // Crear contenedor si no existe
        if (!document.getElementById('notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'fixed top-4 right-4 z-50 space-y-2 max-w-sm';
            document.body.appendChild(container);
        }
    }

    /**
     * Mostrar notificación con animaciones Tailwind
     */
    static showNotification(message, type = 'info', duration = 5000) {
        const container = document.getElementById('notification-container');
        if (!container) return;

        const notification = document.createElement('div');
        
        const typeStyles = {
            success: 'bg-green-50 border-green-200 text-green-800',
            error: 'bg-red-50 border-red-200 text-red-800',
            warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
            info: 'bg-blue-50 border-blue-200 text-blue-800'
        };

        const typeIcons = {
            success: 'fas fa-check-circle text-green-500',
            error: 'fas fa-exclamation-circle text-red-500',
            warning: 'fas fa-exclamation-triangle text-yellow-500',
            info: 'fas fa-info-circle text-blue-500'
        };

        notification.className = `w-full bg-white border rounded-lg shadow-lg p-4 transform transition-all duration-300 ease-in-out translate-x-full opacity-0 ${typeStyles[type] || typeStyles.info}`;
        
        notification.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="${typeIcons[type] || typeIcons.info}"></i>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium">${message}</p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button onclick="this.closest('.w-full').remove()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>
        `;

        container.appendChild(notification);

        // Animar entrada
        setTimeout(() => {
            notification.classList.remove('translate-x-full', 'opacity-0');
            notification.classList.add('translate-x-0', 'opacity-100');
        }, 100);

        // Auto eliminar
        if (duration > 0) {
            setTimeout(() => {
                notification.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }, duration);
        }

        return notification;
    }

    /**
     * Mejoras de formularios para Tailwind
     */
    static setupFormEnhancements() {
        document.addEventListener('DOMContentLoaded', () => {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => this.enhanceForm(form));
        });
    }

    /**
     * Mejorar formulario específico
     */
    static enhanceForm(form) {
        if (!form) return;

        // Mejorar validación visual
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });

        // Prevenir doble submit
        form.addEventListener('submit', function(e) {
            if (this.dataset.submitting === 'true') {
                e.preventDefault();
                return false;
            }
            this.dataset.submitting = 'true';
            
            // Re-habilitar después de 3 segundos por seguridad
            setTimeout(() => {
                this.dataset.submitting = 'false';
            }, 3000);
        });
    }

    /**
     * Validar campo individual
     */
    static validateField(field) {
        const isValid = field.checkValidity();
        
        // Remover clases anteriores
        field.classList.remove('border-red-300', 'border-green-300', 'focus:ring-red-500', 'focus:ring-green-500');
        
        if (field.value.trim() !== '') {
            if (isValid) {
                field.classList.add('border-green-300', 'focus:ring-green-500');
            } else {
                field.classList.add('border-red-300', 'focus:ring-red-500');
            }
        }
    }

    /**
     * Limpiar error de campo
     */
    static clearFieldError(field) {
        field.classList.remove('border-red-300', 'focus:ring-red-500');
    }

    /**
     * Configurar tablas responsivas con Tailwind
     */
    static setupTableResponsive() {
        const tables = document.querySelectorAll('table');
        tables.forEach(table => this.makeTableResponsive(table));
    }

    /**
     * Hacer tabla responsiva
     */
    static makeTableResponsive(table) {
        if (!table) return;

        // Agregar scroll horizontal en móvil
        if (!table.closest('.overflow-x-auto')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'overflow-x-auto';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }

        // Optimizar para pantallas pequeñas
        if (window.innerWidth <= 768) {
            this.convertTableToCards(table);
        }

        // Listener para cambios de tamaño
        window.addEventListener('resize', () => {
            if (window.innerWidth <= 768) {
                this.convertTableToCards(table);
            } else {
                this.restoreTableFormat(table);
            }
        });
    }

    /**
     * Convertir tabla a cards en móvil
     */
    static convertTableToCards(table) {
        if (table.dataset.converted === 'true') return;

        const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        const rows = Array.from(table.querySelectorAll('tbody tr'));

        rows.forEach(row => {
            const cells = Array.from(row.querySelectorAll('td'));
            row.classList.add('block', 'bg-white', 'border', 'border-gray-200', 'rounded-lg', 'p-4', 'mb-4', 'shadow-sm');
            
            cells.forEach((cell, index) => {
                if (headers[index]) {
                    cell.innerHTML = `
                        <div class="flex justify-between items-start mb-2 last:mb-0">
                            <span class="font-medium text-gray-600 text-sm">${headers[index]}:</span>
                            <span class="text-gray-900 text-sm text-right">${cell.innerHTML}</span>
                        </div>
                    `;
                    cell.classList.add('block', 'border-0', 'p-0');
                } else {
                    cell.style.display = 'none';
                }
            });
        });

        // Ocultar header en móvil
        const thead = table.querySelector('thead');
        if (thead) {
            thead.style.display = 'none';
        }

        table.dataset.converted = 'true';
    }

    /**
     * Restaurar formato de tabla
     */
    static restoreTableFormat(table) {
        if (table.dataset.converted !== 'true') return;

        const rows = Array.from(table.querySelectorAll('tbody tr'));
        rows.forEach(row => {
            row.className = 'hover:bg-gray-50 transition-colors';
            const cells = Array.from(row.querySelectorAll('td'));
            cells.forEach(cell => {
                // Restaurar contenido original si es necesario
                cell.classList.remove('block', 'border-0', 'p-0');
                cell.style.display = '';
            });
        });

        // Mostrar header
        const thead = table.querySelector('thead');
        if (thead) {
            thead.style.display = '';
        }

        table.dataset.converted = 'false';
    }

    /**
     * Refrescar tablas responsivas
     */
    static refreshResponsiveTables() {
        const tables = document.querySelectorAll('table');
        tables.forEach(table => {
            table.dataset.converted = 'false';
            this.makeTableResponsive(table);
        });
    }

    /**
     * Sistema de modales mejorado
     */
    static setupModalSystem() {
        // Cerrar modales con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });

        // Cerrar modal al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-backdrop')) {
                this.closeModal(e.target.closest('.modal'));
            }
        });
    }

    /**
     * Abrir modal
     */
    static openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Animar entrada
        setTimeout(() => {
            const content = modal.querySelector('.modal-content, > div > div');
            if (content) {
                content.classList.add('animate-fade-in');
            }
        }, 10);

        // Enfocar primer input
        const firstInput = modal.querySelector('input, select, textarea, button');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }

    /**
     * Cerrar modal
     */
    static closeModal(modal) {
        if (!modal) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        
        // Limpiar formularios
        const forms = modal.querySelectorAll('form');
        forms.forEach(form => form.reset());
    }

    /**
     * Cerrar todos los modales
     */
    static closeAllModals() {
        const modals = document.querySelectorAll('.modal, [id*="modal"]');
        modals.forEach(modal => {
            if (!modal.classList.contains('hidden')) {
                this.closeModal(modal);
            }
        });
    }

    /**
     * Utilidades de loading
     */
    static showLoading(element) {
        if (!element) return;

        const originalContent = element.innerHTML;
        element.dataset.originalContent = originalContent;
        element.innerHTML = `
            <i class="fas fa-spinner fa-spin mr-2"></i>
            Cargando...
        `;
        element.disabled = true;
    }

    static hideLoading(element) {
        if (!element) return;

        element.innerHTML = element.dataset.originalContent || 'Aceptar';
        element.disabled = false;
    }

    /**
     * Utilidades de scroll
     */
    static scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    static scrollToElement(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }

    /**
     * Utilidades de almacenamiento local
     */
    static saveToLocal(key, data) {
        try {
            localStorage.setItem(key, JSON.stringify(data));
            return true;
        } catch (e) {
            console.error('Error saving to localStorage:', e);
            return false;
        }
    }

    static getFromLocal(key) {
        try {
            const data = localStorage.getItem(key);
            return data ? JSON.parse(data) : null;
        } catch (e) {
            console.error('Error reading from localStorage:', e);
            return null;
        }
    }

    /**
     * Debounce para optimizar rendimiento
     */
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Inicializar automáticamente
document.addEventListener('DOMContentLoaded', () => {
    TailwindUtils.init();
});

// Hacer disponible globalmente
window.TailwindUtils = TailwindUtils;

// Mantener compatibilidad con sistema anterior
window.ResponsiveUtils = TailwindUtils;
window.showNotification = TailwindUtils.showNotification;

// Sistema de notificaciones global mejorado
window.NotificationSystem = {
    show: (message, type = 'info', duration = 5000) => {
        return TailwindUtils.showNotification(message, type, duration);
    }
};

// Inicialización responsiva de notificaciones
function initResponsiveNotifications() {
    const position = window.innerWidth < 768 ? 'top-center' : 'top-right';
    const maxWidth = window.innerWidth < 768 ? '90%' : '400px';
    const container = document.getElementById('notification-container');
    if (container) {
        container.style.maxWidth = maxWidth;
        container.style.right = position === 'top-right' ? '1rem' : '50%';
        container.style.left = position === 'top-center' ? '50%' : 'auto';
        container.style.transform = position === 'top-center' ? 'translateX(-50%)' : 'none';
    }
    setTimeout(() => {
        if (typeof ResponsiveUtils.adjustForKeyboard === 'function') {
            ResponsiveUtils.adjustForKeyboard();
        }
    }, 100);
}

document.addEventListener('DOMContentLoaded', () => {
    initResponsiveNotifications();
});