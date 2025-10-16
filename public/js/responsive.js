/**
 * INVENTIXOR - Sistema de Interacciones Responsivas
 * Maneja la funcionalidad del sidebar móvil y otras interacciones
 */

class InventixorResponsive {
    constructor() {
        this.sidebar = null;
        this.overlay = null;
        this.toggleBtn = null;
        this.isMobile = window.innerWidth <= 768;
        
        this.init();
        this.bindEvents();
    }
    
    init() {
        this.createMobileElements();
        this.handleResize();
    }
    
    createMobileElements() {
        // Crear botón hamburguesa para móvil
        if (!document.querySelector('.sidebar-toggle')) {
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'sidebar-toggle d-md-none';
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            toggleBtn.setAttribute('aria-label', 'Abrir menú de navegación');
            document.body.appendChild(toggleBtn);
            this.toggleBtn = toggleBtn;
        }
        
        // Crear overlay para cerrar sidebar en móvil
        if (!document.querySelector('.sidebar-overlay')) {
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
            this.overlay = overlay;
        }
        
        this.sidebar = document.querySelector('.sidebar');
    }
    
    bindEvents() {
        // Toggle sidebar en móvil
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', () => this.toggleSidebar());
        }
        
        // Cerrar sidebar al hacer clic en overlay
        if (this.overlay) {
            this.overlay.addEventListener('click', () => this.closeSidebar());
        }
        
        // Cerrar sidebar con tecla Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isSidebarOpen()) {
                this.closeSidebar();
            }
        });
        
        // Manejar redimensionamiento de ventana
        window.addEventListener('resize', () => this.handleResize());
        
        // Cerrar sidebar al hacer clic en un enlace (móvil)
        const menuLinks = document.querySelectorAll('.sidebar .menu-link');
        menuLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (this.isMobile) {
                    this.closeSidebar();
                }
            });
        });
        
        // Mejorar accesibilidad de tablas en móvil
        this.enhanceTableAccessibility();
        
        // Inicializar tooltips para botones de acción
        this.initializeTooltips();
        
        // Manejar modales responsivos
        this.handleModalResize();
    }
    
    toggleSidebar() {
        if (this.isSidebarOpen()) {
            this.closeSidebar();
        } else {
            this.openSidebar();
        }
    }
    
    openSidebar() {
        if (this.sidebar) {
            this.sidebar.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        if (this.overlay) {
            this.overlay.classList.add('show');
        }
        if (this.toggleBtn) {
            this.toggleBtn.innerHTML = '<i class="fas fa-times"></i>';
            this.toggleBtn.setAttribute('aria-label', 'Cerrar menú de navegación');
        }
    }
    
    closeSidebar() {
        if (this.sidebar) {
            this.sidebar.classList.remove('show');
            document.body.style.overflow = '';
        }
        if (this.overlay) {
            this.overlay.classList.remove('show');
        }
        if (this.toggleBtn) {
            this.toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            this.toggleBtn.setAttribute('aria-label', 'Abrir menú de navegación');
        }
    }
    
    isSidebarOpen() {
        return this.sidebar && this.sidebar.classList.contains('show');
    }
    
    handleResize() {
        const wasMobile = this.isMobile;
        this.isMobile = window.innerWidth <= 768;
        
        // Si cambiamos de móvil a desktop, cerrar sidebar
        if (wasMobile && !this.isMobile) {
            this.closeSidebar();
        }
        
        // Mostrar/ocultar elementos según el tamaño de pantalla
        this.updateVisibility();
        
        // Ajustar tablas
        this.adjustTables();
    }
    
    updateVisibility() {
        // Mostrar/ocultar botón hamburguesa
        if (this.toggleBtn) {
            this.toggleBtn.style.display = this.isMobile ? 'flex' : 'none';
        }
    }
    
    enhanceTableAccessibility() {
        const tables = document.querySelectorAll('.table-responsive table');
        
        tables.forEach(table => {
            // Agregar scroll horizontal suave en móvil
            const container = table.closest('.table-responsive');
            if (container) {
                container.style.scrollBehavior = 'smooth';
                
                // Indicador visual de scroll horizontal
                if (this.isMobile && container.scrollWidth > container.clientWidth) {
                    this.addScrollIndicator(container);
                }
            }
            
            // Mejorar navegación por teclado
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                row.setAttribute('tabindex', '0');
                row.setAttribute('role', 'button');
                
                row.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        // Simular clic en el primer botón de acción si existe
                        const actionBtn = row.querySelector('.btn-action');
                        if (actionBtn) {
                            e.preventDefault();
                            actionBtn.click();
                        }
                    }
                });
            });
        });
    }
    
    addScrollIndicator(container) {
        if (container.querySelector('.scroll-indicator')) return;
        
        const indicator = document.createElement('div');
        indicator.className = 'scroll-indicator';
        indicator.innerHTML = '<i class="fas fa-arrows-alt-h"></i> Desliza para ver más';
        indicator.style.cssText = `
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            z-index: 10;
            animation: fadeInOut 2s ease-in-out infinite alternate;
        `;
        
        container.style.position = 'relative';
        container.appendChild(indicator);
        
        // Ocultar indicador después de interacción
        container.addEventListener('scroll', () => {
            indicator.style.display = 'none';
        }, { once: true });
    }
    
    initializeTooltips() {
        const tooltipElements = document.querySelectorAll('[title]');
        
        tooltipElements.forEach(element => {
            // En móvil, convertir tooltips a labels accesibles
            if (this.isMobile) {
                const title = element.getAttribute('title');
                element.setAttribute('aria-label', title);
                element.removeAttribute('title');
            }
        });
    }
    
    adjustTables() {
        const tables = document.querySelectorAll('.table');
        
        tables.forEach(table => {
            if (this.isMobile) {
                // Ocultar columnas menos importantes en móvil
                this.hideNonEssentialColumns(table);
                
                // Hacer filas más compactas
                table.classList.add('table-mobile');
            } else {
                // Mostrar todas las columnas en desktop
                this.showAllColumns(table);
                table.classList.remove('table-mobile');
            }
        });
    }
    
    hideNonEssentialColumns(table) {
        const headers = table.querySelectorAll('th');
        const essentialColumns = ['nombre', 'stock', 'acciones', 'id'];
        
        headers.forEach((header, index) => {
            const headerText = header.textContent.toLowerCase();
            const isEssential = essentialColumns.some(col => 
                headerText.includes(col) || 
                header.classList.contains('d-mobile-block')
            );
            
            if (!isEssential && !header.classList.contains('d-mobile-block')) {
                header.classList.add('d-mobile-none');
                
                // Ocultar celdas correspondientes
                const cells = table.querySelectorAll(`td:nth-child(${index + 1})`);
                cells.forEach(cell => cell.classList.add('d-mobile-none'));
            }
        });
    }
    
    showAllColumns(table) {
        const hiddenElements = table.querySelectorAll('.d-mobile-none');
        hiddenElements.forEach(element => element.classList.remove('d-mobile-none'));
    }
    
    handleModalResize() {
        const modals = document.querySelectorAll('.modal');
        
        modals.forEach(modal => {
            modal.addEventListener('shown.bs.modal', () => {
                if (this.isMobile) {
                    // Ajustar modal para móvil
                    const modalDialog = modal.querySelector('.modal-dialog');
                    if (modalDialog) {
                        modalDialog.style.margin = '10px';
                        modalDialog.style.maxWidth = 'calc(100% - 20px)';
                    }
                    
                    // Scroll al principio del modal
                    modal.scrollTop = 0;
                }
            });
        });
    }
    
    // Método para agregar animaciones suaves
    addSmoothAnimations() {
        // Animación de aparición para elementos
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '50px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);
        
        // Observar cards y elementos principales
        const animatedElements = document.querySelectorAll(
            '.stats-card, .filter-card, .table-card, .alert'
        );
        
        animatedElements.forEach(element => {
            observer.observe(element);
        });
    }
    
    // Método para mejorar la experiencia táctil en móvil
    enhanceTouchExperience() {
        if (!('ontouchstart' in window)) return;
        
        // Mejorar feedback táctil para botones
        const interactiveElements = document.querySelectorAll(
            '.btn, .menu-link, .table tbody tr, .card'
        );
        
        interactiveElements.forEach(element => {
            element.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
                this.style.opacity = '0.8';
            });
            
            element.addEventListener('touchend', function() {
                setTimeout(() => {
                    this.style.transform = '';
                    this.style.opacity = '';
                }, 100);
            });
        });
    }
    
    // Método para optimizar rendimiento en dispositivos lentos
    optimizePerformance() {
        // Lazy loading para elementos pesados
        const lazyElements = document.querySelectorAll('[data-lazy]');
        
        if ('IntersectionObserver' in window) {
            const lazyObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const element = entry.target;
                        const src = element.getAttribute('data-lazy');
                        if (src) {
                            element.src = src;
                            element.removeAttribute('data-lazy');
                        }
                        lazyObserver.unobserve(element);
                    }
                });
            });
            
            lazyElements.forEach(element => lazyObserver.observe(element));
        }
        
        // Debounce para eventos de resize
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => this.handleResize(), 150);
        });
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    const responsiveSystem = new InventixorResponsive();
    
    // Agregar animaciones después de un pequeño delay
    setTimeout(() => {
        responsiveSystem.addSmoothAnimations();
        responsiveSystem.enhanceTouchExperience();
        responsiveSystem.optimizePerformance();
    }, 100);
    
    // Exponer globalmente para uso en otros scripts
    window.InventixorResponsive = responsiveSystem;
});

// Utilidades adicionales
const ResponsiveUtils = {
    // Detectar tipo de dispositivo
    isMobile: () => window.innerWidth <= 768,
    isTablet: () => window.innerWidth > 768 && window.innerWidth <= 1024,
    isDesktop: () => window.innerWidth > 1024,
    
    // Obtener breakpoint actual
    getCurrentBreakpoint: () => {
        if (window.innerWidth <= 480) return 'xs';
        if (window.innerWidth <= 768) return 'sm';
        if (window.innerWidth <= 1024) return 'md';
        if (window.innerWidth <= 1200) return 'lg';
        return 'xl';
    },
    
    // Scroll suave a elemento
    scrollToElement: (selector, offset = 0) => {
        const element = document.querySelector(selector);
        if (element) {
            const top = element.offsetTop - offset;
            window.scrollTo({
                top: top,
                behavior: 'smooth'
            });
        }
    },
    
    // Mostrar notificación responsiva
    showNotification: (message, type = 'info', duration = 3000) => {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        `;
        
        // Ajustar para móvil
        if (ResponsiveUtils.isMobile()) {
            toast.style.cssText += `
                left: 10px;
                right: 10px;
                min-width: auto;
                top: 10px;
            `;
        }
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        if (window.bootstrap) {
            const bsToast = new bootstrap.Toast(toast, { delay: duration });
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', () => {
                document.body.removeChild(toast);
            });
        } else {
            // Fallback sin Bootstrap
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, duration);
        }
    }
};

// Exponer utilidades globalmente
window.ResponsiveUtils = ResponsiveUtils;