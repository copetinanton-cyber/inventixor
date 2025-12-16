/**
 * INVENTIXOR - Componente de Tabla Responsiva
 * Mejora la experiencia en dispositivos móviles
 */

class ResponsiveTable {
    constructor(tableSelector, options = {}) {
        this.table = document.querySelector(tableSelector);
        this.options = {
            mobileBreakpoint: 768,
            hideColumns: [], // Columnas a ocultar en móvil por índice
            stackCards: true, // Convertir filas en cards en móvil
            scrollIndicator: true,
            ...options
        };
        
        if (this.table) {
            this.init();
        }
    }
    
    init() {
        this.originalTable = this.table.cloneNode(true);
        this.createMobileView();
        this.addScrollIndicator();
        this.bindEvents();
        this.handleResize();
    }
    
    createMobileView() {
        // Crear contenedor para vista móvil
        this.mobileContainer = document.createElement('div');
        this.mobileContainer.className = 'mobile-table-container d-none';
        this.table.parentNode.insertBefore(this.mobileContainer, this.table.nextSibling);
        
        this.generateMobileCards();
    }
    
    generateMobileCards() {
        const tbody = this.table.querySelector('tbody');
        const headers = Array.from(this.table.querySelectorAll('thead th')).map(th => th.textContent.trim());
        
        if (!tbody) return;
        
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        rows.forEach(row => {
            const cells = Array.from(row.querySelectorAll('td'));
            const card = this.createMobileCard(headers, cells, row);
            this.mobileContainer.appendChild(card);
        });
    }
    
    createMobileCard(headers, cells, originalRow) {
        const card = document.createElement('div');
        card.className = 'mobile-table-card card mb-3 hover-lift';
        
        let cardContent = '<div class="card-body">';
        
        cells.forEach((cell, index) => {
            if (this.options.hideColumns.includes(index)) return;
            
            const header = headers[index] || `Campo ${index + 1}`;
            const content = cell.innerHTML;
            
            // Estilos especiales para ciertas columnas
            let valueClass = '';
            if (header.toLowerCase().includes('stock')) {
                valueClass = 'fw-bold';
            } else if (header.toLowerCase().includes('acciones')) {
                valueClass = 'd-flex gap-1 justify-content-end';
            }
            
            cardContent += `
                <div class="row mb-2">
                    <div class="col-5 fw-medium text-muted">${header}:</div>
                    <div class="col-7 ${valueClass}">${content}</div>
                </div>
            `;
        });
        
        cardContent += '</div>';
        card.innerHTML = cardContent;
        
        // Copiar eventos de la fila original
        this.copyRowEvents(originalRow, card);
        
        return card;
    }
    
    copyRowEvents(originalRow, card) {
        // Copiar event listeners básicos
        const clickableElements = originalRow.querySelectorAll('a, button');
        const cardClickableElements = card.querySelectorAll('a, button');
        
        clickableElements.forEach((el, index) => {
            const cardEl = cardClickableElements[index];
            if (cardEl) {
                // Copiar atributos importantes
                ['href', 'onclick', 'data-bs-toggle', 'data-bs-target'].forEach(attr => {
                    if (el.hasAttribute(attr)) {
                        cardEl.setAttribute(attr, el.getAttribute(attr));
                    }
                });
            }
        });
    }
    
    addScrollIndicator() {
        if (!this.options.scrollIndicator) return;
        
        const container = this.table.closest('.table-responsive');
        if (!container) return;
        
        // Verificar si necesita scroll horizontal
        const checkScroll = () => {
            if (container.scrollWidth > container.clientWidth) {
                this.showScrollIndicator(container);
            } else {
                this.hideScrollIndicator(container);
            }
        };
        
        // Observer para cambios de tamaño
        if (window.ResizeObserver) {
            const resizeObserver = new ResizeObserver(checkScroll);
            resizeObserver.observe(container);
        }
        
        checkScroll();
    }
    
    showScrollIndicator(container) {
        let indicator = container.querySelector('.scroll-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'scroll-indicator alert alert-info d-flex align-items-center';
            indicator.innerHTML = `
                <i class="fas fa-info-circle me-2"></i>
                <span>Desliza horizontalmente para ver más columnas</span>
            `;
            indicator.style.cssText = `
                position: sticky;
                left: 0;
                margin: 0;
                border-radius: 0;
                font-size: 0.875rem;
                padding: 0.5rem 1rem;
                background: linear-gradient(90deg, #d1ecf1 0%, rgba(209, 236, 241, 0) 100%);
            `;
            container.insertBefore(indicator, container.firstChild);
        }
        
        // Auto-hide después de scroll
        container.addEventListener('scroll', () => {
            indicator.style.opacity = '0.5';
            setTimeout(() => {
                if (indicator.parentNode) {
                    indicator.style.opacity = '1';
                }
            }, 2000);
        }, { once: true });
    }
    
    hideScrollIndicator(container) {
        const indicator = container.querySelector('.scroll-indicator');
        if (indicator) {
            indicator.remove();
        }
    }
    
    bindEvents() {
        window.addEventListener('resize', () => this.handleResize());
        
        // Mejorar navegación por teclado
        if (this.options.stackCards) {
            this.enhanceKeyboardNavigation();
        }
    }
    
    enhanceKeyboardNavigation() {
        const cards = this.mobileContainer.querySelectorAll('.mobile-table-card');
        
        cards.forEach((card, index) => {
            card.setAttribute('tabindex', '0');
            card.addEventListener('keydown', (e) => {
                switch(e.key) {
                    case 'ArrowDown':
                        e.preventDefault();
                        const nextCard = cards[index + 1];
                        if (nextCard) nextCard.focus();
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        const prevCard = cards[index - 1];
                        if (prevCard) prevCard.focus();
                        break;
                    case 'Enter':
                    case ' ':
                        e.preventDefault();
                        const firstButton = card.querySelector('button, a');
                        if (firstButton) firstButton.click();
                        break;
                }
            });
        });
    }
    
    handleResize() {
        const isMobile = window.innerWidth <= this.options.mobileBreakpoint;
        
        if (isMobile && this.options.stackCards) {
            // Mostrar vista móvil
            this.table.closest('.table-responsive').style.display = 'none';
            this.mobileContainer.classList.remove('d-none');
        } else {
            // Mostrar tabla normal
            this.table.closest('.table-responsive').style.display = 'block';
            this.mobileContainer.classList.add('d-none');
        }
        
        // Aplicar ocultación de columnas si no es stack cards
        if (!this.options.stackCards && isMobile) {
            this.hideColumnsOnMobile();
        } else {
            this.showAllColumns();
        }
    }
    
    hideColumnsOnMobile() {
        this.options.hideColumns.forEach(columnIndex => {
            const header = this.table.querySelector(`thead th:nth-child(${columnIndex + 1})`);
            const cells = this.table.querySelectorAll(`tbody td:nth-child(${columnIndex + 1})`);
            
            if (header) header.classList.add('d-none');
            cells.forEach(cell => cell.classList.add('d-none'));
        });
    }
    
    showAllColumns() {
        const hiddenHeaders = this.table.querySelectorAll('thead th.d-none');
        const hiddenCells = this.table.querySelectorAll('tbody td.d-none');
        
        hiddenHeaders.forEach(header => header.classList.remove('d-none'));
        hiddenCells.forEach(cell => cell.classList.remove('d-none'));
    }
    
    // Método para actualizar los datos (útil para tablas dinámicas)
    updateData() {
        this.mobileContainer.innerHTML = '';
        this.generateMobileCards();
        this.handleResize();
    }
    
    // Método para agregar nueva fila
    addRow(rowData) {
        // Implementar según necesidades específicas
    }
    
    // Método para eliminar fila
    removeRow(rowIndex) {
        const mobileCards = this.mobileContainer.querySelectorAll('.mobile-table-card');
        if (mobileCards[rowIndex]) {
            mobileCards[rowIndex].remove();
        }
    }
}

// Inicialización automática para tablas con clase específica
document.addEventListener('DOMContentLoaded', function() {
    // Configuraciones específicas por módulo
    const tableConfigs = {
        '.table': {
            hideColumns: [], // Por defecto no ocultar nada
            stackCards: true
        },
        '.productos-table': {
            hideColumns: [2, 3, 4, 6], // Ocultar modelo, talla, color, material en móvil
            stackCards: true
        },
        '.usuarios-table': {
            hideColumns: [1, 4, 5], // Ocultar tipo doc, teléfono, cargo en móvil
            stackCards: true
        },
        '.categorias-table': {
            hideColumns: [], // Mostrar todo en cards
            stackCards: true
        }
    };
    
    // Inicializar tablas automáticamente
    Object.entries(tableConfigs).forEach(([selector, config]) => {
        document.querySelectorAll(selector).forEach(table => {
            new ResponsiveTable(table, config);
        });
    });
});

// Exportar para uso global
window.ResponsiveTable = ResponsiveTable;