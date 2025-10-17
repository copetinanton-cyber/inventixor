<?php
namespace App\Core;

class Router {
    private array $routes = [];

    public function get(string $path, callable|array $handler): void {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, callable|array $handler): void {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        // Permitir routing por query param ?r=/ruta para entornos sin reescritura de URLs
        $requested = isset($_GET['r']) ? (string)$_GET['r'] : $uri;
        $path = $this->normalize($requested);

        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            http_response_code(404);
            echo "<h1>404 - Ruta no encontrada</h1>";
            return;
        }

        if (is_array($handler)) {
            [$class, $action] = $handler;
            $controller = new $class();
            $controller->$action();
            return;
        }

        call_user_func($handler);
    }

    private function normalize(string $path): string {
        if ($path === '') return '/';
        // Quitar base path si existe (/inventixor)
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        if ($base && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        $path = '/' . trim($path, '/');
        return $path === '' ? '/' : $path;
    }
}
