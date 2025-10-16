// Responsive Sidebar JavaScript - Inventixor

// Función para toggle del sidebar en móviles
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (sidebar && overlay) {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }
}

// Inicializar funcionalidad responsive
document.addEventListener('DOMContentLoaded', function() {
    const menuLinks = document.querySelectorAll('.menu-link');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    // Asegurar que el sidebar esté oculto en móviles al cargar
    if (window.innerWidth <= 768 && sidebar) {
        sidebar.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
    }
    
    // Cerrar sidebar al hacer click en un enlace (solo en móviles)
    menuLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768 && sidebar && overlay) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        });
    });
    
    // Manejar cambios de tamaño de ventana
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar && overlay) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }
    });
    
    // Animaciones al cargar (solo para dashboard)
    const statsCards = document.querySelectorAll('.stats-card');
    if (statsCards.length > 0) {
        statsCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }
});

// Función para marcar el enlace activo en el sidebar
function setActiveMenuItem(currentPage) {
    document.addEventListener('DOMContentLoaded', function() {
        const menuLinks = document.querySelectorAll('.menu-link');
        menuLinks.forEach(link => {
            link.classList.remove('active');
            const href = link.getAttribute('href');
            if (href && href.includes(currentPage)) {
                link.classList.add('active');
            }
        });
    });
}