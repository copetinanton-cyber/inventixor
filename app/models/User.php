<?php
// app/models/User.php
require_once __DIR__ . '/../../config/db.php';

class User {
    public function login($username, $password) {
        global $conn;
        
        // Intentar login por número de documento, rol o correo electrónico
        $sql = "SELECT num_doc, nombres, apellidos, rol, contrasena FROM users 
                WHERE num_doc = ? OR LOWER(rol) = ? OR LOWER(correo) = ?";
        $stmt = $conn->prepare($sql);
        $username_lower = strtolower(trim($username));
        
        // Si es numérico, intentar como número de documento
        if (is_numeric($username)) {
            $num_doc = intval($username);
            $stmt->bind_param('iss', $num_doc, $username_lower, $username_lower);
        } else {
            // Si no es numérico, usar 0 para num_doc (no coincidirá)
            $num_doc = 0;
            $stmt->bind_param('iss', $num_doc, $username_lower, $username_lower);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['contrasena']) || $password === $row['contrasena']) {
                // Devolver información completa del usuario
                return [
                    'num_doc' => $row['num_doc'],
                    'nombres' => $row['nombres'],
                    'apellidos' => $row['apellidos'],
                    'rol' => $row['rol']
                ];
            }
        }
        return false;
    }
    
    /**
     * Obtener información completa del usuario por número de documento
     */
    public function getUserByDocument($num_doc) {
        global $conn;
        
        $sql = "SELECT num_doc, tipo_documento, apellidos, nombres, telefono, correo, cargo, rol 
                FROM users WHERE num_doc = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $num_doc);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Obtener todos los usuarios
     */
    public function getAllUsers() {
        global $conn;
        
        $sql = "SELECT num_doc, tipo_documento, apellidos, nombres, telefono, correo, cargo, rol 
                FROM users ORDER BY apellidos, nombres";
        $result = $conn->query($sql);
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        return $users;
    }
}
?>