<?php
/**
 * Script de depuración para verificar el login
 * Este script ayuda a diagnosticar problemas con el sistema de login
 */

require_once 'config/db.php';

echo "<h1>Debug del Sistema de Login - Inventixor</h1>";

// 1. Verificar conexión a la base de datos
echo "<h2>1. Verificación de Base de Datos</h2>";
if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ Error de conexión: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>✅ Conexión exitosa a la base de datos</p>";
}

// 2. Verificar si la tabla Users existe
echo "<h2>2. Verificación de Tabla Users</h2>";
$tables_result = $conn->query("SHOW TABLES LIKE 'Users'");
if ($tables_result->num_rows > 0) {
    echo "<p style='color: green;'>✅ Tabla 'Users' existe</p>";
} else {
    echo "<p style='color: red;'>❌ Tabla 'Users' NO existe</p>";
    
    // Verificar si existe con minúsculas
    $tables_result_lower = $conn->query("SHOW TABLES LIKE 'users'");
    if ($tables_result_lower->num_rows > 0) {
        echo "<p style='color: orange;'>⚠ Tabla 'users' (minúsculas) encontrada</p>";
    }
}

// 3. Mostrar estructura de la tabla Users
echo "<h2>3. Estructura de la Tabla</h2>";
$structure = $conn->query("DESCRIBE Users");
if ($structure) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No se pudo obtener la estructura de la tabla</p>";
}

// 4. Contar usuarios en la tabla
echo "<h2>4. Usuarios en la Tabla</h2>";
$count_result = $conn->query("SELECT COUNT(*) as total FROM Users");
if ($count_result) {
    $count = $count_result->fetch_assoc()['total'];
    echo "<p>Total de usuarios: <strong>$count</strong></p>";
} else {
    echo "<p style='color: red;'>❌ Error al contar usuarios: " . $conn->error . "</p>";
}

// 5. Mostrar todos los usuarios
echo "<h2>5. Lista de Usuarios</h2>";
$users_result = $conn->query("SELECT num_doc, nombres, apellidos, rol, correo FROM Users");
if ($users_result) {
    if ($users_result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Número Doc</th><th>Nombres</th><th>Apellidos</th><th>Rol</th><th>Correo</th></tr>";
        while ($user = $users_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $user['num_doc'] . "</td>";
            echo "<td>" . $user['nombres'] . "</td>";
            echo "<td>" . $user['apellidos'] . "</td>";
            echo "<td>" . $user['rol'] . "</td>";
            echo "<td>" . $user['correo'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No hay usuarios en la tabla</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Error al consultar usuarios: " . $conn->error . "</p>";
}

// 6. Buscar específicamente el usuario 1000000001
echo "<h2>6. Búsqueda del Usuario 1000000001</h2>";
$search_result = $conn->query("SELECT * FROM Users WHERE num_doc = 1000000001");
if ($search_result) {
    if ($search_result->num_rows > 0) {
        $user = $search_result->fetch_assoc();
        echo "<p style='color: green;'>✅ Usuario 1000000001 encontrado:</p>";
        echo "<ul>";
        echo "<li><strong>Nombres:</strong> " . $user['nombres'] . "</li>";
        echo "<li><strong>Apellidos:</strong> " . $user['apellidos'] . "</li>";
        echo "<li><strong>Rol:</strong> " . $user['rol'] . "</li>";
        echo "<li><strong>Correo:</strong> " . $user['correo'] . "</li>";
        echo "<li><strong>Contraseña (hash):</strong> " . substr($user['contrasena'], 0, 30) . "...</li>";
        echo "</ul>";
        
        // Verificar si la contraseña es correcta
        if (password_verify('admin123', $user['contrasena'])) {
            echo "<p style='color: green;'>✅ La contraseña 'admin123' es correcta para este usuario</p>";
        } else {
            echo "<p style='color: red;'>❌ La contraseña 'admin123' NO es correcta para este usuario</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Usuario 1000000001 NO encontrado</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Error en la búsqueda: " . $conn->error . "</p>";
}

// 7. Probar la lógica de login
echo "<h2>7. Prueba de Lógica de Login</h2>";
$username = '1000000001';
$password = 'admin123';

echo "<p>Probando login con usuario: <strong>$username</strong> y contraseña: <strong>$password</strong></p>";

$sql = "SELECT * FROM Users WHERE LOWER(rol) = ? OR LOWER(correo) = ? OR num_doc = ?";
$stmt = $conn->prepare($sql);
$username_lower = strtolower(trim($username));
$num_doc = is_numeric($username) ? intval($username) : 0;
$stmt->bind_param("ssi", $username_lower, $username_lower, $num_doc);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    echo "<p style='color: red;'>❌ Usuario no encontrado en la consulta de login</p>";
    echo "<p>Parámetros de búsqueda:</p>";
    echo "<ul>";
    echo "<li>rol (lowercase): '$username_lower'</li>";
    echo "<li>correo (lowercase): '$username_lower'</li>";
    echo "<li>num_doc: $num_doc</li>";
    echo "</ul>";
} else {
    echo "<p style='color: green;'>✅ Usuario encontrado en la consulta de login</p>";
    if (password_verify($password, $usuario['contrasena'])) {
        echo "<p style='color: green;'>✅ Login exitoso</p>";
    } else {
        echo "<p style='color: red;'>❌ Contraseña incorrecta</p>";
    }
}

echo "<hr>";
echo "<p><a href='login.php'>Ir al Login</a> | <a href='insert_users.php'>Insertar Usuarios</a></p>";

$conn->close();
?>