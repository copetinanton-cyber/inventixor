# Migración progresiva a MVC (Inventixor)

Este proyecto está en transición hacia una arquitectura Modelo-Vista-Controlador (MVC) sin romper las páginas existentes. Este documento explica la estructura y cómo ir migrando módulos.

## Estructura actual

- app/
  - core/
    - Router.php (enrutador simple con soporte para `?r=/ruta`)
    - Controller.php (base para controladores)
  - controllers/ (controladores existentes y futuros)
  - models/ (modelos)
  - views/ (vistas)
- router.php (front controller)
- index.php, dashboard.php, productos.php, salidas.php, ... (páginas existentes)

## Cómo usar el router

Puedes acceder a rutas usando cualquiera de estas formas (útil en XAMPP sin reescritura de URLs):

- http://localhost/inventixor/router.php?r=/ (login)
- http://localhost/inventixor/router.php?r=/dashboard
- http://localhost/inventixor/router.php?r=/productos
- http://localhost/inventixor/router.php?r=/salidas

Si configuras reescritura de URLs en Apache/Nginx, podrás mapear `router.php` como front controller y usar rutas limpias.

## Plan de migración por módulos

1. Productos
   - Paso 1 (hecho): Agregar Router y base MVC sin romper `productos.php`.
   - Paso 2: Crear `app/models/Producto.php` con métodos de acceso a datos usados en `productos.php`.
   - Paso 3: Crear `app/views/productos/index.php` y mover el HTML de lista.
   - Paso 4: Crear `ProductosController` con acciones index/crear/editar/eliminar y usar `Controller->view()`.

2. Salidas
   - Paso 1 (hecho): Ruta que reusa `salidas.php`.
   - Paso 2: Extraer consultas a `app/models/Salida.php` y `app/models/Producto.php`.
   - Paso 3: Crear vistas en `app/views/salidas/`.
   - Paso 4: Controlador `SalidasController` para manejar GET/POST.

3. Otros módulos (usuarios, reportes, proveedores, categorías)
   - Repetir el patrón: Modelo -> Vista -> Controlador.

## Buenas prácticas

- Usar prepared statements (PDO/mysqli) en modelos.
- Escapar salida HTML con `htmlspecialchars` en vistas.
- En controladores, validar permisos (RBAC) y entradas.
- Centralizar notificaciones/toasts en `public/js/notifications.js`.

## Próximos pasos sugeridos

- Agregar `Producto.php` y `Salida.php` en models con métodos usados actualmente.
- Crear las primeras vistas bajo `app/views` y probar rutas a través de `router.php`.
- (Opcional) Configurar `.htaccess` para rutas limpias.
