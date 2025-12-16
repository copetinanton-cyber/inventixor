<?php
// Front controller para MVC
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/app/helpers/Database.php';

// Autocarga simple para App\ namespaces
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Router;
// Nota: Controladores existentes no están totalmente namespaced aún.

$router = new Router();

// Rutas básicas (reutilizando páginas existentes mientras migramos)
$router->get('/', function() { require __DIR__ . '/index.php'; }); // login
$router->get('/dashboard', function() { require __DIR__ . '/dashboard.php'; });
$router->get('/productos', [\App\Controllers\ProductosMvcController::class, 'index']);
$router->get('/salidas', [\App\Controllers\SalidasMvcController::class, 'index']);
$router->post('/salidas', [\App\Controllers\SalidasMvcController::class, 'index']);

// Despachar
$router->dispatch();
