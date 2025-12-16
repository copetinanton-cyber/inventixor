<?php
namespace App\Controllers;

use App\Core\Controller;

class ProductosMvcController extends Controller {
    public function index(): void {
        // Fase 1: reutilizar la página existente mientras migramos a vistas
        require_once __DIR__ . '/../../productos.php';
    }
}
