<?php
// Verificar datos actuales en la base de datos
require_once 'app/helpers/Database.php';

$db = new Database();
$conn = $db->conn;

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Verificar Datos Actuales - Inventixor</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-4'>
    <h2>Datos Actuales en la Base de Datos</h2>";

// Verificar categorías
echo "<h3>Categorías Actuales:</h3>";
$result = $conn->query("SELECT * FROM Categoria ORDER BY id_categ");
echo "<div class='row'>";
while ($row = $result->fetch_assoc()) {
    echo "<div class='col-md-4 mb-2'>
            <div class='alert alert-info'>
                ID: {$row['id_categ']} - {$row['nombre']}
            </div>
          </div>";
}
echo "</div>";

// Verificar subcategorías
echo "<h3>Subcategorías Actuales:</h3>";
$result = $conn->query("SELECT s.*, c.nombre as categoria_nombre FROM Subcategoria s LEFT JOIN Categoria c ON s.id_categ = c.id_categ ORDER BY s.id_categ, s.id_subcg");
echo "<div class='row'>";
while ($row = $result->fetch_assoc()) {
    echo "<div class='col-md-6 mb-2'>
            <div class='alert alert-warning'>
                ID: {$row['id_subcg']} - {$row['nombre']} (Categoría: {$row['categoria_nombre']})
            </div>
          </div>";
}
echo "</div>";

echo "</div></body></html>";
?>