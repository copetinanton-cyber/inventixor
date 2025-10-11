<?php
// Script para revisar la estructura de la tabla Productos
require_once 'config/db.php';

echo "=== ESTRUCTURA DE TABLA PRODUCTOS ===\n\n";

// Mostrar estructura de la tabla
$result = $conn->query("DESCRIBE Productos");
if ($result) {
    echo "Columnas disponibles en tabla Productos:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']} ({$row['Type']}) {$row['Null']} {$row['Key']}\n";
    }
} else {
    echo "Error al obtener estructura: " . $conn->error . "\n";
}

echo "\n=== MUESTRA DE DATOS ===\n";

// Mostrar algunos registros de ejemplo
$result = $conn->query("SELECT * FROM Productos LIMIT 3");
if ($result) {
    echo "Primeros registros:\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id_prod'] . "\n";
        foreach ($row as $key => $value) {
            echo "  $key: $value\n";
        }
        echo "---\n";
    }
} else {
    echo "Error al obtener datos: " . $conn->error . "\n";
}

$conn->close();
?>