<?php
namespace App\Core;

class Controller {
    protected function view(string $view, array $data = []): void {
        extract($data);
        $viewFile = __DIR__ . '/../../app/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "Vista no encontrada: $view";
            return;
        }
        include $viewFile;
    }
}
