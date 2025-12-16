<?php
/**
 * Helper para generar páginas responsivas consistentes
 * Sistema Inventixor - Responsive Helper
 */

class ResponsivePageHelper {
    private static $baseTemplate = null;
    
    /**
     * Carga el template base responsivo con Tailwind CSS
     */
    private static function loadBaseTemplate() {
        if (self::$baseTemplate === null) {
            $templatePath = realpath(__DIR__ . '/../templates/responsive-base.html');
            if (file_exists($templatePath)) {
                self::$baseTemplate = file_get_contents($templatePath);
            } else {
                throw new Exception("Template base Bootstrap no encontrado: $templatePath");
            }
        }
        return self::$baseTemplate;
    }
    
    /**
     * Genera una página responsiva completa
     * 
     * @param array $config Configuración de la página
     * @return string HTML de la página completa
     */
    public static function generatePage($config) {
        $template = self::loadBaseTemplate();
        
        // Configuración por defecto
        $defaults = [
            'MODULE_TITLE' => 'Módulo',
            'MODULE_DESCRIPTION' => 'Módulo del sistema Inventixor',
            'MODULE_ICON' => 'fas fa-cog',
            'MODULE_SUBTITLE' => 'Gestión del sistema',
            'MODULE_CONTENT' => '<div class="container"><p>Contenido del módulo</p></div>',
            'ADDITIONAL_STYLES' => '',
            'ADDITIONAL_SCRIPTS' => '',
            'NOTIFICATION_SCRIPT' => '',
            'USER_MENU' => '',
            // Clases activas para navegación
            'DASHBOARD_ACTIVE' => '',
            'PRODUCTOS_ACTIVE' => '',
            'CATEGORIAS_ACTIVE' => '',
            'SUBCATEGORIAS_ACTIVE' => '',
            'PROVEEDORES_ACTIVE' => '',
            'SALIDAS_ACTIVE' => '',
            'REPORTES_ACTIVE' => '',
            'ALERTAS_ACTIVE' => ''
        ];
        
        $config = array_merge($defaults, $config);
        
        // Reemplazar placeholders
        foreach ($config as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        
        return $template;
    }
    
    /**
     * Genera el menú de usuarios si está permitido
     */
    public static function getUserMenu($userRole = null) {
        if ($userRole === 'admin' || $userRole === 'gerente') {
            return '
            <li class="menu-item">
                <a href="usuarios.php" class="menu-link">
                    <i class="fas fa-users"></i> Usuarios
                </a>
            </li>';
        }
        return '';
    }
    
    /**
     * Genera el script de notificaciones común
     */
    public static function getNotificationScript() {
        return "
        // Configurar notificaciones responsivas
        ResponsiveUtils.initNotifications({
            position: window.innerWidth < 768 ? 'top-center' : 'top-right',
            duration: 4000,
            maxWidth: window.innerWidth < 768 ? '90%' : '400px'
        });
        
        // Mostrar notificaciones desde PHP si existen
        if (typeof showNotification === 'function') {
            // Implementar según el sistema de notificaciones existente
            setTimeout(() => {
                ResponsiveUtils.adjustForKeyboard();
            }, 100);
        }";
    }
    
    /**
     * Genera scripts específicos para tablas
     */
    public static function getTableScripts($tableId = 'dataTable') {
        return "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tabla responsiva
            if (typeof ResponsiveTable !== 'undefined') {
                const table = document.getElementById('$tableId');
                if (table) {
                    new ResponsiveTable(table, {
                        breakpoint: 768,
                        showSearch: true,
                        showPagination: true,
                        itemsPerPage: window.innerWidth < 768 ? 5 : 10
                    });
                }
            }
            
            // Optimizaciones específicas para móvil
            ResponsiveUtils.optimizeForTouch();
        });
        </script>";
    }
    
    /**
     * Genera scripts para formularios
     */
    public static function getFormScripts($formId = null) {
        $selector = $formId ? "#$formId" : 'form';
        return "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mejorar formularios en móvil
            const forms = document.querySelectorAll('$selector');
            forms.forEach(form => {
                ResponsiveUtils.enhanceForm(form);
            });
            
            // Validación responsiva
            ResponsiveUtils.initFormValidation();
        });
        </script>";
    }
    
    /**
     * Marca un módulo como activo en la navegación
     */
    public static function setActiveModule($moduleName) {
        $key = strtoupper($moduleName) . '_ACTIVE';
        return [$key => 'active'];
    }
    
    /**
     * Genera estilos adicionales para módulos específicos
     */
    public static function getModuleStyles($moduleName) {
        $styles = [
            'productos' => '',
            'usuarios' => '',
            'proveedores' => '',
            'reportes' => '',
            'subcategorias' => ''
        ];
        return $styles[$moduleName] ?? '';
    }
}

/**
 * Función helper global para generar páginas rápidamente
 */
function renderResponsivePage($config, $content = null) {
    if ($content !== null) {
        $config['MODULE_CONTENT'] = $content;
    }
    
    echo ResponsivePageHelper::generatePage($config);
}

/**
 * Función helper para incluir el header responsivo
 */
function includeResponsiveHeader($config) {
    // Extraer solo la parte del header
    $fullPage = ResponsivePageHelper::generatePage($config);
    $headerEnd = strpos($fullPage, '{{MODULE_CONTENT}}');
    
    if ($headerEnd !== false) {
        echo substr($fullPage, 0, $headerEnd);
    }
}

/**
 * Función helper para incluir el footer responsivo
 */
function includeResponsiveFooter($scripts = '') {
    $config = ['ADDITIONAL_SCRIPTS' => $scripts];
    $fullPage = ResponsivePageHelper::generatePage($config);
    $footerStart = strpos($fullPage, '{{MODULE_CONTENT}}') + strlen('{{MODULE_CONTENT}}');
    
    if ($footerStart !== false) {
        echo substr($fullPage, $footerStart);
    }
}
?>