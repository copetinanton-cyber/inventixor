<?php
// ========================================
// RESETEAR TIENDA DE CALZADO - INVENTIXOR
// Elimina todos los datos y reinicia los IDs
// ========================================
require_once 'app/helpers/Database.php';
$db = new Database();
$conn = $db->conn;

try {
    // Desactivar claves foráneas
    $conn->query('SET FOREIGN_KEY_CHECKS = 0');
    // Eliminar datos
    $conn->query('DELETE FROM Salidas');
    $conn->query('DELETE FROM Productos');
    $conn->query('DELETE FROM Subcategoria');
    $conn->query('DELETE FROM Categoria');
    // Reiniciar AUTO_INCREMENT
    $conn->query('ALTER TABLE Categoria AUTO_INCREMENT = 1');
    $conn->query('ALTER TABLE Subcategoria AUTO_INCREMENT = 1');
    $conn->query('ALTER TABLE Productos AUTO_INCREMENT = 1');
    $conn->query('ALTER TABLE Salidas AUTO_INCREMENT = 1');
    $conn->query('SET FOREIGN_KEY_CHECKS = 1');

    // Insertar categorías correctas
    $conn->query("INSERT INTO Categoria (nombre) VALUES
        ('Calzado para Caballero'),
        ('Calzado para Dama'),
        ('Calzado Infantil')");

    // Insertar subcategorías (marcas) correctas
    $conn->query("INSERT INTO Subcategoria (nombre, id_categ) VALUES
        ('Nike', 1),('Adidas', 1),('Puma', 1),('Reebok', 1),('Converse', 1),('Vans', 1),('Timberland', 1),('Clarks', 1),
        ('Nike', 2),('Adidas', 2),('Puma', 2),('Reebok', 2),('Converse', 2),('Vans', 2),('Nine West', 2),('Steve Madden', 2),
        ('Nike', 3),('Adidas', 3),('Puma', 3),('Converse', 3),('Vans', 3),('Sketchers', 3),('Crocs', 3),('Disney', 3)");

    $msg = "<div class='alert alert-success'><strong>¡Base de datos reseteada correctamente!</strong><br>Solo quedan las categorías y marcas correctas para tienda de calzado.</div>";
} catch (Exception $e) {
    $msg = "<div class='alert alert-danger'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Interfaz
?>
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Resetear Tienda de Calzado - Inventixor</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-5'>
    <div class='row justify-content-center'>
        <div class='col-md-8'>
            <div class='card shadow'>
                <div class='card-header bg-danger text-white'>
                    <h3 class='mb-0'><i class='fas fa-trash-alt me-2'></i>Resetear Tienda de Calzado</h3>
                </div>
                <div class='card-body'>
                    <?php echo $msg; ?>
                    <div class='mt-4 text-center'>
                        <a href='diagnostico_bd.php' class='btn btn-primary me-2'>Ver Diagnóstico</a>
                        <a href='productos.php' class='btn btn-success me-2'>Ver Productos</a>
                        <a href='categorias.php' class='btn btn-info'>Ver Categorías</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
