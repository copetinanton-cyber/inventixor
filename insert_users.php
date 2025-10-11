<?php
/**
 * Script de inserción de usuarios para Inventixor
 * Este script inserta usuarios de ejemplo en la base de datos
 * Para futuras instalaciones del sistema
 * 
 * Ejecutar este script después de crear la base de datos
 * Navegue a: http://localhost/inventixor/insert_users.php
 */

require_once 'config/db.php';

// Función para hashear contraseñas de forma segura
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

echo "<h1>Script de Inserción de Usuarios - Inventixor</h1>";
echo "<h2>Insertando usuarios predeterminados...</h2>";

// Array de usuarios predeterminados
$usuarios = [
    [
        'num_doc' => 1000000001,
        'tipo_documento' => 1, // 1 = Cédula de Ciudadanía
        'apellidos' => 'Administrador',
        'nombres' => 'Sistema',
        'telefono' => 3001234567,
        'correo' => 'admin@inventixor.com',
        'cargo' => 'Administrador General',
        'rol' => 'admin',
        'contrasena' => 'admin123' // Se hasheará automáticamente
    ],
    [
        'num_doc' => 1000000002,
        'tipo_documento' => 1,
        'apellidos' => 'Coordinador',
        'nombres' => 'Principal',
        'telefono' => 3002345678,
        'correo' => 'coordinador@inventixor.com',
        'cargo' => 'Coordinador de Inventario',
        'rol' => 'coordinador',
        'contrasena' => 'coord123'
    ],
    [
        'num_doc' => 1000000003,
        'tipo_documento' => 1,
        'apellidos' => 'Auxiliar',
        'nombres' => 'Inventario',
        'telefono' => 3003456789,
        'correo' => 'auxiliar@inventixor.com',
        'cargo' => 'Auxiliar de Inventario',
        'rol' => 'auxiliar',
        'contrasena' => 'aux123'
    ],
    [
        'num_doc' => 1000000004,
        'tipo_documento' => 1,
        'apellidos' => 'García',
        'nombres' => 'María Elena',
        'telefono' => 3004567890,
        'correo' => 'maria.garcia@inventixor.com',
        'cargo' => 'Coordinadora de Almacén',
        'rol' => 'coordinador',
        'contrasena' => 'maria2024'
    ],
    [
        'num_doc' => 1000000005,
        'tipo_documento' => 1,
        'apellidos' => 'López',
        'nombres' => 'Carlos Alberto',
        'telefono' => 3005678901,
        'correo' => 'carlos.lopez@inventixor.com',
        'cargo' => 'Auxiliar de Bodega',
        'rol' => 'auxiliar',
        'contrasena' => 'carlos2024'
    ],
    [
        'num_doc' => 1000000006,
        'tipo_documento' => 1,
        'apellidos' => 'Rodríguez',
        'nombres' => 'Ana Isabel',
        'telefono' => 3006789012,
        'correo' => 'ana.rodriguez@inventixor.com',
        'cargo' => 'Supervisora de Inventario',
        'rol' => 'coordinador',
        'contrasena' => 'ana2024'
    ]
];

// Preparar la consulta SQL
$sql = "INSERT INTO Users (num_doc, tipo_documento, apellidos, nombres, telefono, correo, cargo, rol, contrasena) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conn->error);
}

$usuarios_insertados = 0;
$errores = 0;

// Insertar cada usuario
foreach ($usuarios as $usuario) {
    // Hashear la contraseña
    $password_hash = hashPassword($usuario['contrasena']);
    
    $stmt->bind_param(
        'iississss',
        $usuario['num_doc'],
        $usuario['tipo_documento'],
        $usuario['apellidos'],
        $usuario['nombres'],
        $usuario['telefono'],
        $usuario['correo'],
        $usuario['cargo'],
        $usuario['rol'],
        $password_hash
    );
    
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Usuario insertado: {$usuario['nombres']} {$usuario['apellidos']} - Rol: {$usuario['rol']}</p>";
        $usuarios_insertados++;
    } else {
        // Verificar si el error es por duplicado
        if ($conn->errno == 1062) {
            echo "<p style='color: orange;'>⚠ Usuario ya existe: {$usuario['nombres']} {$usuario['apellidos']} (Documento: {$usuario['num_doc']})</p>";
        } else {
            echo "<p style='color: red;'>✗ Error al insertar usuario {$usuario['nombres']} {$usuario['apellidos']}: " . $stmt->error . "</p>";
            $errores++;
        }
    }
}

$stmt->close();

echo "<hr>";
echo "<h3>Resumen de la inserción:</h3>";
echo "<p><strong>Usuarios insertados exitosamente:</strong> $usuarios_insertados</p>";
echo "<p><strong>Errores encontrados:</strong> $errores</p>";

if ($usuarios_insertados > 0) {
    echo "<hr>";
    echo "<h3 style='margin-bottom: 10px;'>Credenciales de acceso</h3>";
    echo "<div style='background: #f8f9fa; border-radius: 10px; border: 1px solid #e3e3e3; padding: 20px; margin-bottom: 20px;'>";
    echo "<table style='width:100%; border-collapse:collapse;'>";
    echo "<thead><tr style='background:#e7f3ff;'><th style='padding:8px;'>Usuario</th><th>Rol</th><th>Contraseña</th><th>Nombre</th></tr></thead>";
    echo "<tbody>";
    echo "<tr><td><strong>1000000001</strong></td><td>admin</td><td>admin123</td><td>Sistema Administrador</td></tr>";
    echo "<tr><td><strong>1000000002</strong></td><td>coordinador</td><td>coord123</td><td>Principal Coordinador</td></tr>";
    echo "<tr><td><strong>1000000003</strong></td><td>auxiliar</td><td>aux123</td><td>Inventario Auxiliar</td></tr>";
    echo "<tr><td><strong>1000000004</strong></td><td>coordinador</td><td>maria2024</td><td>María Elena García</td></tr>";
    echo "<tr><td><strong>1000000005</strong></td><td>auxiliar</td><td>carlos2024</td><td>Carlos Alberto López</td></tr>";
    echo "<tr><td><strong>1000000006</strong></td><td>coordinador</td><td>ana2024</td><td>Ana Isabel Rodríguez</td></tr>";
    echo "</tbody></table>";
    echo "<p style='color: #d63333; font-weight: bold; margin-top: 15px;'>⚠ IMPORTANTE: Cambie estas contraseñas por defecto antes de usar el sistema en producción.</p>";
    echo "</div>";
    echo "<div style='background: #fff3cd; border-radius: 8px; border: 1px solid #ffeaa7; padding: 15px; margin-bottom: 20px;'>";
    echo "<h4 style='margin-top:0;'>Formas de acceso</h4>";
    echo "<ul style='margin-bottom:0;'>";
    echo "<li><strong>Método principal:</strong> Número de documento + contraseña</li>";
    echo "<li><strong>Métodos alternativos:</strong> Rol o correo electrónico + contraseña</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<hr>";
echo "<div style='background-color: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
echo "<h4>✅ Sistema configurado correctamente:</h4>";
echo "<p>El modelo <code>app/models/User.php</code> ha sido actualizado para soportar login por:</p>";
echo "<ul>";
echo "<li><strong>Número de documento</strong> (método principal)</li>";
echo "<li><strong>Rol</strong> (admin, coordinador, auxiliar)</li>";
echo "<li><strong>Correo electrónico</strong></li>";
echo "</ul>";
echo "<p style='color: #155724;'><strong>¡Todo listo para usar!</strong> Puede ingresar con cualquiera de estos métodos.</p>";
echo "</div>";

echo "<p><a href='index.php' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Sistema</a></p>";
echo "<p><a href='login.php' style='background-color: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Login</a></p>";

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inserción de Usuarios - Inventixor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        h2, h3 {
            color: #555;
        }
        .info-box {
            background-color: #e7f3ff;
            border: 1px solid #b3d7ff;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="info-box">
        <h4>Información sobre los tipos de documento:</h4>
        <ul>
            <li><strong>1:</strong> Cédula de Ciudadanía</li>
            <li><strong>2:</strong> Cédula de Extranjería</li>
            <li><strong>3:</strong> Pasaporte</li>
            <li><strong>4:</strong> Tarjeta de Identidad</li>
        </ul>
    </div>
    
    <div class="info-box">
        <h4>Roles del sistema:</h4>
        <ul>
            <li><strong>admin:</strong> Acceso completo al sistema</li>
            <li><strong>coordinador:</strong> Gestión de inventarios y supervisión</li>
            <li><strong>auxiliar:</strong> Operaciones básicas de inventario</li>
        </ul>
    </div>
    
    <div class="info-box warning">
        <h4>⚠ Notas importantes:</h4>
        <ul>
            <li>Este script debe ejecutarse solo una vez durante la instalación inicial</li>
            <li>Las contraseñas están hasheadas usando PASSWORD_DEFAULT de PHP</li>
            <li>Cambie las contraseñas por defecto antes de usar en producción</li>
            <li>El modelo User.php ha sido actualizado para soportar login por número de documento</li>
        </ul>
    </div>
    
    <div class="info-box success">
        <h4>✅ Sistema de Login Implementado:</h4>
        <p><strong>Métodos de acceso disponibles:</strong></p>
        <ul>
            <li><strong>Por Número de Documento:</strong> 1000000001 + contraseña</li>
            <li><strong>Por Rol:</strong> admin, coordinador, auxiliar + contraseña</li>
            <li><strong>Por Correo:</strong> admin@inventixor.com + contraseña</li>
        </ul>
        <p style='color: #155724; font-weight: bold;'>✅ ¡Todo configurado y listo para usar!</p>
    </div>
</body>
</html>