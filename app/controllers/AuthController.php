<?php
// app/controllers/AuthController.php
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $pdo;
    
    public function __construct($pdo = null) {
        $this->pdo = $pdo;
    }
    
    public function verificarSesion() {
        if (!isset($_SESSION['user'])) {
            header('Location: login.php');
            exit();
        }
        return $_SESSION['user'];
    }
    
    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = new User();
            $login = $user->login($_POST['username'], $_POST['password']);
            if ($login) {
                // Guardar toda la información del usuario en sesión
                $_SESSION['user'] = $login;
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        }
        include 'app/views/login.php';
    }
}
?>