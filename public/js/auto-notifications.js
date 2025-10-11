/**
 * Sistema de Notificaciones Automáticas en Tiempo Real - InventiXor
 * 
 * Este sistema consulta periódicamente el servidor para obtener nuevas
 * notificaciones y las muestra como popups automáticos a todos los usuarios.
 * 
 * Características:
 * - Polling automático cada X segundos
 * - Notificaciones emergentes con prioridades
 * - Sonidos de alerta configurables
 * - Persistencia en localStorage
 * - Control de frecuencia inteligente
 * - Modo debug/desarrollo
 * 
 * @version 2.0
 * @author Sistema InventiXor
 */

class SistemaNotificacionesAutomaticas {
    constructor(config = {}) {
        // Configuración predeterminada
        this.config = {
            // Intervalo de polling en milisegundos
            pollingInterval: config.pollingInterval || 15000, // 15 segundos
            
            // Intervalo cuando hay actividad alta
            fastPollingInterval: config.fastPollingInterval || 5000, // 5 segundos
            
            // Intervalo cuando no hay actividad
            slowPollingInterval: config.slowPollingInterval || 30000, // 30 segundos
            
            // API endpoint
            apiUrl: config.apiUrl || 'notifications_api.php',
            
            // Usuario actual (se obtiene automáticamente)
            userDoc: config.userDoc || null,
            
            // Máximo de notificaciones a mostrar simultáneamente
            maxConcurrentNotifications: config.maxConcurrentNotifications || 3,
            
            // Duración de las notificaciones en milisegundos
            notificationDuration: {
                'baja': 5000,
                'media': 8000,
                'alta': 12000,
                'critica': 20000
            },
            
            // Sonidos de alerta
            sounds: {
                'baja': config.sounds?.baja || null,
                'media': config.sounds?.media || 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmUdBzuH0fPTgjMGJHfH8N2QQAoUXrTp66hVFApGn+DyvmUdBzuH0fPTgjMGJHfH8N2QQAoUXrTp66hVFApGn+DyvmUdBzuH0fPTgjMGJHfH8N2QQAoUXrTp66hVFApGn+DyvmUdBzuH0fPTgjMGJHfH8N2QQAoUXrTp66hVFApGn+DyvmUdBzuH0fPTgjMG',
                'alta': config.sounds?.alta || 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmUdBzuH0fPTgjMGJHfH8N2QQAoUXrTp66hVFApGn+DyvmUdBzuH0fPTgjMGJHfH8N2QQAoUXrTp66hVFApGn+DyvmUdBzuH0fPTgjMGJHfH8N2QQAoUXrTp66hVFApGn+DyvmUdBzuH0fPTgjMGJHfH8N2QQAoUXrTp66hVFApGn+DyvmUdBzuH0fPTgjMG',
                'critica': config.sounds?.critica || 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmUdBzuH0fPTgjMGJHfH8N2QQAoUXrTp66hVFApGn+DyvmUdBzuH0fPTgjMGJHfH8N2QQAoUXrTp66hVFApGn+DyvmUdBzuH0fPTgjMGJHfH8N2QQAoUXrTp66hVFApGn+DyvmUdBzuH0fPTgjMGJHfH8N2QQAoUXrTp66hVFApGn+DyvmUdBzuH0fPTgjMG'
            },
            
            // Modo debug
            debug: config.debug || false,
            
            // Posición de las notificaciones
            position: config.position || 'top-right', // top-right, top-left, bottom-right, bottom-left
            
            // Tema
            theme: config.theme || 'modern', // modern, classic, minimal
            
            // Auto-iniciar
            autoStart: config.autoStart !== false
        };
        
        // Estado interno
        this.state = {
            isPolling: false,
            pollingTimer: null,
            currentInterval: this.config.pollingInterval,
            lastCheck: null,
            consecutiveErrors: 0,
            activeNotifications: [],
            seenNotifications: new Set(),
            isPageVisible: true,
            hasActivity: false,
            userDoc: null,
            totalNotifications: 0,
            lastNotificationTime: null
        };
        
        // Contenedor para notificaciones
        this.container = null;
        
        // Audio objects para sonidos
        this.audioElements = {};
        
        // Inicializar
        this.init();
    }
    
    /**
     * Inicializar el sistema
     */
    async init() {
        try {
            this.log('Inicializando sistema de notificaciones automáticas...');
            
            // Obtener información del usuario actual
            await this.obtenerUsuarioActual();
            
            // Crear contenedor de notificaciones
            this.crearContenedor();
            
            // Configurar event listeners
            this.configurarEventListeners();
            
            // Preparar sonidos
            this.prepararSonidos();
            
            // Cargar notificaciones vistas desde localStorage
            this.cargarNotificacionesVistas();
            
            // Iniciar polling si está configurado
            if (this.config.autoStart) {
                this.iniciarPolling();
            }
            
            this.log('Sistema de notificaciones inicializado correctamente');
            
        } catch (error) {
            console.error('[NotificacionesAutomaticas] Error en inicialización:', error);
        }
    }
    
    /**
     * Obtener información del usuario actual
     */
    async obtenerUsuarioActual() {
        try {
            const response = await fetch(`${this.config.apiUrl}?action=heartbeat`);
            const data = await response.json();
            
            if (data.success && data.user) {
                this.state.userDoc = data.user.doc;
                this.config.userDoc = data.user.doc;
                this.log(`Usuario identificado: ${data.user.name} (${data.user.doc})`);
            } else {
                throw new Error('No se pudo obtener información del usuario');
            }
        } catch (error) {
            console.error('[NotificacionesAutomaticas] Error obteniendo usuario:', error);
            // Intentar obtener del DOM o variables globales
            this.intentarObtenerUsuarioAlternativo();
        }
    }
    
    /**
     * Intentar obtener usuario de formas alternativas
     */
    intentarObtenerUsuarioAlternativo() {
        // Buscar en elementos del DOM
        const userElements = [
            document.querySelector('[data-user-doc]'),
            document.querySelector('.user-doc'),
            document.querySelector('#user-doc')
        ];
        
        for (const element of userElements) {
            if (element && element.dataset.userDoc) {
                this.state.userDoc = element.dataset.userDoc;
                this.config.userDoc = element.dataset.userDoc;
                this.log(`Usuario obtenido del DOM: ${this.state.userDoc}`);
                return;
            }
        }
        
        // Buscar en variables globales comunes
        if (window.currentUser && window.currentUser.num_doc) {
            this.state.userDoc = window.currentUser.num_doc;
            this.config.userDoc = window.currentUser.num_doc;
            this.log(`Usuario obtenido de variable global: ${this.state.userDoc}`);
            return;
        }
        
        console.warn('[NotificacionesAutomaticas] No se pudo determinar el usuario actual');
    }
    
    /**
     * Crear contenedor para las notificaciones
     */
    crearContenedor() {
        // Eliminar contenedor existente si existe
        const existing = document.getElementById('notification-container-auto');
        if (existing) {
            existing.remove();
        }
        
        this.container = document.createElement('div');
        this.container.id = 'notification-container-auto';
        this.container.className = `notification-container-auto position-${this.config.position} theme-${this.config.theme}`;
        
        // Agregar estilos si no existen
        if (!document.getElementById('auto-notifications-styles')) {
            this.agregarEstilos();
        }
        
        document.body.appendChild(this.container);
    }
    
    /**
     * Agregar estilos CSS para las notificaciones automáticas
     */
    agregarEstilos() {
        const style = document.createElement('style');
        style.id = 'auto-notifications-styles';
        style.textContent = `
            .notification-container-auto {
                position: fixed;
                z-index: 10000;
                max-width: 400px;
                pointer-events: none;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            .notification-container-auto.position-top-right {
                top: 20px;
                right: 20px;
            }
            
            .notification-container-auto.position-top-left {
                top: 20px;
                left: 20px;
            }
            
            .notification-container-auto.position-bottom-right {
                bottom: 20px;
                right: 20px;
            }
            
            .notification-container-auto.position-bottom-left {
                bottom: 20px;
                left: 20px;
            }
            
            .auto-notification {
                pointer-events: auto;
                margin-bottom: 10px;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                overflow: hidden;
                position: relative;
                transform: translateX(100%);
                transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                max-width: 380px;
                min-width: 320px;
            }
            
            .auto-notification.show {
                transform: translateX(0);
            }
            
            .auto-notification.hide {
                transform: translateX(100%);
                opacity: 0;
            }
            
            .auto-notification.priority-critica {
                border-left: 6px solid #dc3545;
                animation: pulse-critical 2s infinite;
            }
            
            .auto-notification.priority-alta {
                border-left: 6px solid #fd7e14;
            }
            
            .auto-notification.priority-media {
                border-left: 6px solid #ffc107;
            }
            
            .auto-notification.priority-baja {
                border-left: 6px solid #28a745;
            }
            
            .theme-modern .auto-notification {
                background: rgba(255, 255, 255, 0.95);
                color: #333;
            }
            
            .theme-classic .auto-notification {
                background: #ffffff;
                color: #333;
                border-radius: 8px;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            }
            
            .theme-minimal .auto-notification {
                background: rgba(0, 0, 0, 0.85);
                color: #fff;
                border-radius: 6px;
            }
            
            .auto-notification-header {
                padding: 16px 20px 12px;
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
            }
            
            .auto-notification-title {
                font-weight: 600;
                font-size: 15px;
                margin: 0;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .auto-notification-title i {
                font-size: 16px;
                opacity: 0.8;
            }
            
            .auto-notification-close {
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
                opacity: 0.5;
                transition: opacity 0.2s;
                color: inherit;
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
            }
            
            .auto-notification-close:hover {
                opacity: 1;
                background: rgba(0, 0, 0, 0.1);
            }
            
            .auto-notification-body {
                padding: 0 20px 16px;
            }
            
            .auto-notification-message {
                font-size: 14px;
                line-height: 1.4;
                margin: 0;
                opacity: 0.9;
            }
            
            .auto-notification-time {
                font-size: 12px;
                opacity: 0.6;
                margin-top: 8px;
                font-style: italic;
            }
            
            .auto-notification-progress {
                position: absolute;
                bottom: 0;
                left: 0;
                height: 3px;
                background: rgba(0, 0, 0, 0.1);
                transition: width 0.1s linear;
            }
            
            .auto-notification-actions {
                padding: 12px 20px;
                border-top: 1px solid rgba(0, 0, 0, 0.1);
                display: flex;
                gap: 8px;
                justify-content: flex-end;
            }
            
            .auto-notification-btn {
                padding: 6px 12px;
                border: none;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
                background: rgba(0, 0, 0, 0.1);
                color: inherit;
            }
            
            .auto-notification-btn:hover {
                background: rgba(0, 0, 0, 0.2);
                transform: translateY(-1px);
            }
            
            .auto-notification-btn.primary {
                background: #007bff;
                color: white;
            }
            
            .auto-notification-btn.primary:hover {
                background: #0056b3;
            }
            
            @keyframes pulse-critical {
                0%, 100% { 
                    border-left-color: #dc3545; 
                    box-shadow: 0 8px 32px rgba(220, 53, 69, 0.15);
                }
                50% { 
                    border-left-color: #ff6b6b; 
                    box-shadow: 0 8px 32px rgba(220, 53, 69, 0.25);
                }
            }
            
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
            
            /* Responsividad */
            @media (max-width: 480px) {
                .notification-container-auto {
                    left: 10px !important;
                    right: 10px !important;
                    max-width: none;
                }
                
                .auto-notification {
                    min-width: auto;
                    max-width: none;
                }
            }
            
            /* Modo oscuro automático */
            @media (prefers-color-scheme: dark) {
                .theme-modern .auto-notification {
                    background: rgba(33, 37, 41, 0.95);
                    color: #f8f9fa;
                }
            }
        `;
        
        document.head.appendChild(style);
    }
    
    /**
     * Configurar event listeners
     */
    configurarEventListeners() {
        // Detectar visibilidad de la página
        document.addEventListener('visibilitychange', () => {
            this.state.isPageVisible = !document.hidden;
            this.ajustarIntervaloPolling();
            
            if (this.state.isPageVisible) {
                // Comprobar inmediatamente al volver a la página
                this.verificarNotificaciones();
            }
        });
        
        // Detectar actividad del usuario
        const activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'];
        const handleActivity = () => {
            this.state.hasActivity = true;
            this.ajustarIntervaloPolling();
            
            // Reset actividad después de 30 segundos
            clearTimeout(this.activityTimeout);
            this.activityTimeout = setTimeout(() => {
                this.state.hasActivity = false;
                this.ajustarIntervaloPolling();
            }, 30000);
        };
        
        activityEvents.forEach(event => {
            document.addEventListener(event, handleActivity, { passive: true });
        });
        
        // Cleanup al cerrar la página
        window.addEventListener('beforeunload', () => {
            this.detenerPolling();
            this.guardarNotificacionesVistas();
        });
    }
    
    /**
     * Preparar elementos de audio para sonidos
     */
    prepararSonidos() {
        Object.entries(this.config.sounds).forEach(([priority, soundData]) => {
            if (soundData) {
                try {
                    const audio = new Audio(soundData);
                    audio.volume = 0.3;
                    this.audioElements[priority] = audio;
                } catch (error) {
                    this.log(`Error cargando sonido para prioridad ${priority}:`, error);
                }
            }
        });
    }
    
    /**
     * Iniciar polling automático
     */
    iniciarPolling() {
        if (this.state.isPolling) {
            return;
        }
        
        if (!this.state.userDoc) {
            this.log('No se puede iniciar polling: usuario no identificado');
            return;
        }
        
        this.state.isPolling = true;
        this.log('Iniciando polling automático...');
        
        // Primera verificación inmediata
        this.verificarNotificaciones();
        
        // Programar verificaciones periódicas
        this.programarSiguienteVerificacion();
    }
    
    /**
     * Detener polling automático
     */
    detenerPolling() {
        this.state.isPolling = false;
        
        if (this.state.pollingTimer) {
            clearTimeout(this.state.pollingTimer);
            this.state.pollingTimer = null;
        }
        
        this.log('Polling automático detenido');
    }
    
    /**
     * Programar siguiente verificación
     */
    programarSiguienteVerificacion() {
        if (!this.state.isPolling) {
            return;
        }
        
        if (this.state.pollingTimer) {
            clearTimeout(this.state.pollingTimer);
        }
        
        this.state.pollingTimer = setTimeout(() => {
            this.verificarNotificaciones();
            this.programarSiguienteVerificacion();
        }, this.state.currentInterval);
    }
    
    /**
     * Ajustar intervalo de polling según actividad y visibilidad
     */
    ajustarIntervaloPolling() {
        let nuevoIntervalo;
        
        if (!this.state.isPageVisible) {
            // Página no visible - polling lento
            nuevoIntervalo = this.config.slowPollingInterval;
        } else if (this.state.hasActivity) {
            // Usuario activo - polling rápido
            nuevoIntervalo = this.config.fastPollingInterval;
        } else {
            // Polling normal
            nuevoIntervalo = this.config.pollingInterval;
        }
        
        if (nuevoIntervalo !== this.state.currentInterval) {
            this.state.currentInterval = nuevoIntervalo;
            this.log(`Intervalo de polling ajustado a ${nuevoIntervalo}ms`);
        }
    }
    
    /**
     * Verificar nuevas notificaciones desde el servidor
     */
    async verificarNotificaciones() {
        if (!this.state.userDoc) {
            this.log('Verificación omitida: usuario no identificado');
            return;
        }
        
        try {
            const url = `${this.config.apiUrl}?action=get_pending&user_doc=${encodeURIComponent(this.state.userDoc)}&limit=${this.config.maxConcurrentNotifications}`;
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Error desconocido del servidor');
            }
            
            // Reset contador de errores consecutivos
            this.state.consecutiveErrors = 0;
            
            // Procesar notificaciones
            if (data.data && data.data.length > 0) {
                this.log(`${data.data.length} notificaciones pendientes encontradas`);
                this.procesarNotificaciones(data.data);
            }
            
            this.state.lastCheck = new Date();
            
        } catch (error) {
            this.state.consecutiveErrors++;
            console.error('[NotificacionesAutomaticas] Error verificando notificaciones:', error);
            
            // Si hay muchos errores consecutivos, reducir frecuencia
            if (this.state.consecutiveErrors >= 3) {
                this.state.currentInterval = Math.min(
                    this.state.currentInterval * 2, 
                    this.config.slowPollingInterval
                );
                this.log(`Demasiados errores, intervalo aumentado a ${this.state.currentInterval}ms`);
            }
        }
    }
    
    /**
     * Procesar y mostrar notificaciones nuevas
     */
    procesarNotificaciones(notificaciones) {
        notificaciones.forEach(notif => {
            // Verificar si ya fue mostrada
            if (this.state.seenNotifications.has(notif.id_notificacion)) {
                return;
            }
            
            // Verificar límite de notificaciones concurrentes
            if (this.state.activeNotifications.length >= this.config.maxConcurrentNotifications) {
                // Cerrar la más antigua
                this.cerrarNotificacionMasAntigua();
            }
            
            // Mostrar notificación
            this.mostrarNotificacion(notif);
            
            // Marcar como vista
            this.state.seenNotifications.add(notif.id_notificacion);
        });
    }
    
    /**
     * Mostrar una notificación individual
     */
    mostrarNotificacion(notificacion) {
        try {
            // Crear elemento de notificación
            const element = this.crearElementoNotificacion(notificacion);
            
            // Agregar al contenedor
            this.container.appendChild(element);
            
            // Agregar a lista activa
            const notifData = {
                id: notificacion.id_notificacion,
                element: element,
                startTime: Date.now(),
                duration: this.config.notificationDuration[notificacion.nivel_prioridad] || 8000,
                priority: notificacion.nivel_prioridad
            };
            
            this.state.activeNotifications.push(notifData);
            
            // Mostrar con animación
            setTimeout(() => {
                element.classList.add('show');
            }, 100);
            
            // Reproducir sonido si está disponible
            this.reproducirSonido(notificacion.nivel_prioridad);
            
            // Programar auto-cierre
            this.programarAutoCierre(notifData);
            
            // Actualizar contadores
            this.state.totalNotifications++;
            this.state.lastNotificationTime = Date.now();
            
            this.log(`Notificación mostrada: ${notificacion.titulo} (Prioridad: ${notificacion.nivel_prioridad})`);
            
        } catch (error) {
            console.error('[NotificacionesAutomaticas] Error mostrando notificación:', error);
        }
    }
    
    /**
     * Crear elemento DOM para una notificación
     */
    crearElementoNotificacion(notificacion) {
        const element = document.createElement('div');
        element.className = `auto-notification priority-${notificacion.nivel_prioridad}`;
        element.dataset.notificationId = notificacion.id_notificacion;
        
        // Formatear tiempo
        const timeAgo = this.formatearTiempoRelativo(new Date(notificacion.fecha_creacion));
        
        element.innerHTML = `
            <div class="auto-notification-header">
                <h4 class="auto-notification-title">
                    <i class="${notificacion.icono || 'fas fa-bell'}"></i>
                    ${this.escapeHtml(notificacion.titulo)}
                </h4>
                <button class="auto-notification-close" type="button" aria-label="Cerrar">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="auto-notification-body">
                <p class="auto-notification-message">
                    ${this.escapeHtml(notificacion.mensaje)}
                </p>
                <div class="auto-notification-time">
                    ${timeAgo}
                </div>
            </div>
            <div class="auto-notification-progress"></div>
        `;
        
        // Event listener para cerrar
        const closeBtn = element.querySelector('.auto-notification-close');
        closeBtn.addEventListener('click', () => {
            this.cerrarNotificacion(notificacion.id_notificacion);
        });
        
        // Event listener para hacer clic en la notificación
        element.addEventListener('click', (e) => {
            if (e.target !== closeBtn && !closeBtn.contains(e.target)) {
                this.onNotificacionClick(notificacion);
            }
        });
        
        return element;
    }
    
    /**
     * Programar auto-cierre de una notificación
     */
    programarAutoCierre(notifData) {
        const progressBar = notifData.element.querySelector('.auto-notification-progress');
        const duration = notifData.duration;
        const startTime = Date.now();
        
        // Actualizar barra de progreso
        const updateProgress = () => {
            const elapsed = Date.now() - startTime;
            const progress = Math.min((elapsed / duration) * 100, 100);
            
            if (progressBar) {
                progressBar.style.width = `${progress}%`;
            }
            
            if (progress >= 100) {
                this.cerrarNotificacion(notifData.id);
            } else {
                requestAnimationFrame(updateProgress);
            }
        };
        
        // Pausar en hover
        notifData.element.addEventListener('mouseenter', () => {
            if (progressBar) {
                progressBar.style.animationPlayState = 'paused';
            }
        });
        
        notifData.element.addEventListener('mouseleave', () => {
            if (progressBar) {
                progressBar.style.animationPlayState = 'running';
            }
        });
        
        requestAnimationFrame(updateProgress);
    }
    
    /**
     * Cerrar una notificación específica
     */
    async cerrarNotificacion(notificationId) {
        try {
            const index = this.state.activeNotifications.findIndex(n => n.id === notificationId);
            if (index === -1) return;
            
            const notifData = this.state.activeNotifications[index];
            
            // Animación de salida
            notifData.element.classList.add('hide');
            
            // Remover del DOM después de la animación
            setTimeout(() => {
                if (notifData.element.parentNode) {
                    notifData.element.parentNode.removeChild(notifData.element);
                }
            }, 400);
            
            // Remover de lista activa
            this.state.activeNotifications.splice(index, 1);
            
            // Marcar como vista en el servidor
            await this.marcarComoVista(notificationId);
            
        } catch (error) {
            console.error('[NotificacionesAutomaticas] Error cerrando notificación:', error);
        }
    }
    
    /**
     * Cerrar la notificación más antigua
     */
    cerrarNotificacionMasAntigua() {
        if (this.state.activeNotifications.length === 0) return;
        
        // Encontrar la notificación más antigua
        let masAntigua = this.state.activeNotifications[0];
        
        for (let i = 1; i < this.state.activeNotifications.length; i++) {
            if (this.state.activeNotifications[i].startTime < masAntigua.startTime) {
                masAntigua = this.state.activeNotifications[i];
            }
        }
        
        this.cerrarNotificacion(masAntigua.id);
    }
    
    /**
     * Marcar notificación como vista en el servidor
     */
    async marcarComoVista(notificationId) {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_as_read');
            formData.append('notification_id', notificationId);
            formData.append('user_doc', this.state.userDoc);
            
            const response = await fetch(this.config.apiUrl, {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                console.warn('No se pudo marcar notificación como vista:', data.message);
            }
            
        } catch (error) {
            console.error('[NotificacionesAutomaticas] Error marcando como vista:', error);
        }
    }
    
    /**
     * Reproducir sonido para una prioridad
     */
    reproducirSonido(priority) {
        try {
            const audio = this.audioElements[priority];
            if (audio && this.state.isPageVisible) {
                // Reset y reproducir
                audio.currentTime = 0;
                const playPromise = audio.play();
                
                if (playPromise) {
                    playPromise.catch(error => {
                        this.log('No se pudo reproducir sonido (probablemente requiere interacción del usuario):', error);
                    });
                }
            }
        } catch (error) {
            this.log('Error reproduciendo sonido:', error);
        }
    }
    
    /**
     * Manejar click en notificación
     */
    onNotificacionClick(notificacion) {
        this.log('Click en notificación:', notificacion.titulo);
        
        // Cerrar la notificación
        this.cerrarNotificacion(notificacion.id_notificacion);
        
        // Aquí se pueden agregar acciones específicas según el tipo de notificación
        if (notificacion.datos_evento) {
            const datos = typeof notificacion.datos_evento === 'string' ? 
                JSON.parse(notificacion.datos_evento) : notificacion.datos_evento;
                
            // Ejemplo: redirigir a producto con stock bajo
            if (notificacion.tipo_evento === 'stock_bajo' && datos.producto_id) {
                window.open(`productos.php?id=${datos.producto_id}`, '_blank');
            }
        }
    }
    
    /**
     * Cargar notificaciones vistas desde localStorage
     */
    cargarNotificacionesVistas() {
        try {
            const saved = localStorage.getItem('inventixor_seen_notifications');
            if (saved) {
                const seenArray = JSON.parse(saved);
                this.state.seenNotifications = new Set(seenArray);
                this.log(`${seenArray.length} notificaciones vistas cargadas desde localStorage`);
            }
        } catch (error) {
            this.log('Error cargando notificaciones vistas:', error);
        }
    }
    
    /**
     * Guardar notificaciones vistas en localStorage
     */
    guardarNotificacionesVistas() {
        try {
            const seenArray = Array.from(this.state.seenNotifications);
            // Mantener solo las últimas 1000 para evitar crecimiento excesivo
            const limited = seenArray.slice(-1000);
            localStorage.setItem('inventixor_seen_notifications', JSON.stringify(limited));
            this.log(`${limited.length} notificaciones vistas guardadas en localStorage`);
        } catch (error) {
            this.log('Error guardando notificaciones vistas:', error);
        }
    }
    
    /**
     * Utilidades
     */
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    formatearTiempoRelativo(fecha) {
        const now = new Date();
        const diff = now - fecha;
        const minutes = Math.floor(diff / 60000);
        
        if (minutes < 1) return 'Hace un momento';
        if (minutes < 60) return `Hace ${minutes} minuto${minutes > 1 ? 's' : ''}`;
        
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `Hace ${hours} hora${hours > 1 ? 's' : ''}`;
        
        const days = Math.floor(hours / 24);
        return `Hace ${days} día${days > 1 ? 's' : ''}`;
    }
    
    log(...args) {
        if (this.config.debug) {
            console.log('[NotificacionesAutomaticas]', ...args);
        }
    }
    
    /**
     * API pública
     */
    
    // Obtener estadísticas del sistema
    getStats() {
        return {
            isPolling: this.state.isPolling,
            currentInterval: this.state.currentInterval,
            activeNotifications: this.state.activeNotifications.length,
            totalNotifications: this.state.totalNotifications,
            lastCheck: this.state.lastCheck,
            consecutiveErrors: this.state.consecutiveErrors,
            userDoc: this.state.userDoc
        };
    }
    
    // Forzar verificación manual
    async checkNow() {
        await this.verificarNotificaciones();
    }
    
    // Cerrar todas las notificaciones activas
    cerrarTodas() {
        const activeIds = this.state.activeNotifications.map(n => n.id);
        activeIds.forEach(id => this.cerrarNotificacion(id));
    }
    
    // Cambiar configuración
    updateConfig(newConfig) {
        Object.assign(this.config, newConfig);
        this.ajustarIntervaloPolling();
    }
    
    // Reiniciar sistema
    restart() {
        this.detenerPolling();
        setTimeout(() => {
            this.iniciarPolling();
        }, 1000);
    }
}

// Instancia global
let sistemaNotificacionesAutomaticas;

// Inicialización automática cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si ya existe una instancia
    if (window.sistemaNotificacionesAutomaticas) {
        return;
    }
    
    // Crear instancia global
    window.sistemaNotificacionesAutomaticas = new SistemaNotificacionesAutomaticas({
        debug: window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1',
        pollingInterval: 15000, // 15 segundos
        fastPollingInterval: 5000, // 5 segundos cuando hay actividad
        slowPollingInterval: 30000, // 30 segundos cuando no hay actividad
        position: 'top-right',
        theme: 'modern',
        autoStart: true
    });
    
    // También crear alias más corto
    window.notificacionesAuto = window.sistemaNotificacionesAutomaticas;
    
    console.log('[InventiXor] Sistema de notificaciones automáticas iniciado');
});

// Exportar para uso en módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SistemaNotificacionesAutomaticas;
}