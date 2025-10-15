<?php
// Script de debug para verificar el problema de notificaciones
echo "Debug de Notificaciones - " . date('Y-m-d H:i:s') . "\n";
echo "=================================================\n\n";

// Mostrar todas las variables GET
echo "Variables GET:\n";
if (!empty($_GET)) {
    foreach ($_GET as $key => $value) {
        echo "  $key = '$value'\n";
    }
} else {
    echo "  No hay variables GET\n";
}

echo "\n";

// Mostrar variables de sesión
session_start();
echo "Variables de Sesión:\n";
if (!empty($_SESSION)) {
    foreach ($_SESSION as $key => $value) {
        if (is_array($value) || is_object($value)) {
            echo "  $key = " . print_r($value, true) . "\n";
        } else {
            echo "  $key = '$value'\n";
        }
    }
} else {
    echo "  No hay variables de sesión\n";
}

echo "\n";
echo "URL actual: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'No referer') . "\n";

?>