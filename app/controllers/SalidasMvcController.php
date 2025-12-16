<?php
namespace App\Controllers;

use App\Core\Controller;

class SalidasMvcController extends Controller {
    public function index(): void {
        // Fase de transición: reutilizamos la página existente mientras se migra a vistas
        require_once __DIR__ . '/../../salidas.php';
    }
}
