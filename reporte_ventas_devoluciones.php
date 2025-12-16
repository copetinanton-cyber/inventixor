<?php
require_once 'app/helpers/Database.php';
$db = new Database();
$conn = $db->conn;

function getResumen($conn, $tipo) {
    $tipo_salida = $tipo === 'venta' ? 'venta' : ($tipo === 'devolucion' ? 'devolucion' : 'cambio');
    $sql = "SELECT c.nombre AS categoria, sc.nombre AS marca, COUNT(*) AS total, SUM(s.cantidad) AS cantidad, SUM(s.cantidad * s.precio_venta) AS total_ventas
            FROM Salidas s
            LEFT JOIN Productos p ON s.id_prod = p.id_prod
            LEFT JOIN Subcategoria sc ON p.id_subcg = sc.id_subcg
            LEFT JOIN Categoria c ON sc.id_categ = c.id_categ
            WHERE s.tipo_salida = '$tipo_salida'
            GROUP BY c.id_categ, sc.id_subcg
            ORDER BY c.id_categ, sc.nombre";
    return $conn->query($sql);
}

$ventas = getResumen($conn, 'venta');
$devoluciones = getResumen($conn, 'devolucion');
$cambios = getResumen($conn, 'cambio');
?>
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Reporte de Ventas y Devoluciones - Inventixor</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-5'>
    <div class='row'>
        <div class='col-md-12'>
            <div class='card mb-4'>
                <div class='card-header bg-primary text-white'>
                    <h3 class='mb-0'><i class='fas fa-chart-bar me-2'></i>Reporte de Ventas por Categoría y Marca</h3>
                </div>
                <div class='card-body'>
                    <div class='table-responsive'>
                        <table class='table table-striped'>
                            <thead class='table-dark'>
                                <tr>
                                    <th>Categoría</th>
                                    <th>Marca</th>
                                    <th># Movimientos</th>
                                    <th>Cantidad Vendida</th>
                                    <th>Total Ventas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $ventas->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['categoria']) ?></td>
                                    <td><?= htmlspecialchars($row['marca']) ?></td>
                                    <td><?= $row['total'] ?></td>
                                    <td><?= $row['cantidad'] ?></td>
                                    <td>$<?= number_format($row['total_ventas'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class='card mb-4'>
                <div class='card-header bg-danger text-white'>
                    <h3 class='mb-0'><i class='fas fa-undo-alt me-2'></i>Reporte de Devoluciones por Categoría y Marca</h3>
                </div>
                <div class='card-body'>
                    <div class='table-responsive'>
                        <table class='table table-striped'>
                            <thead class='table-dark'>
                                <tr>
                                    <th>Categoría</th>
                                    <th>Marca</th>
                                    <th># Movimientos</th>
                                    <th>Cantidad Devuelta</th>
                                    <th>Total Devoluciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $devoluciones->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['categoria']) ?></td>
                                    <td><?= htmlspecialchars($row['marca']) ?></td>
                                    <td><?= $row['total'] ?></td>
                                    <td><?= $row['cantidad'] ?></td>
                                    <td>$<?= number_format($row['total_ventas'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class='card mb-4'>
                <div class='card-header bg-warning text-dark'>
                    <h3 class='mb-0'><i class='fas fa-exchange-alt me-2'></i>Reporte de Cambios por Categoría y Marca</h3>
                </div>
                <div class='card-body'>
                    <div class='table-responsive'>
                        <table class='table table-striped'>
                            <thead class='table-dark'>
                                <tr>
                                    <th>Categoría</th>
                                    <th>Marca</th>
                                    <th># Movimientos</th>
                                    <th>Cantidad Cambiada</th>
                                    <th>Total Cambios</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $cambios->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['categoria']) ?></td>
                                    <td><?= htmlspecialchars($row['marca']) ?></td>
                                    <td><?= $row['total'] ?></td>
                                    <td><?= $row['cantidad'] ?></td>
                                    <td>$<?= number_format($row['total_ventas'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class='text-center mb-4'>
                <a href='diagnostico_bd.php' class='btn btn-info me-2'>Ver Diagnóstico</a>
                <a href='productos.php' class='btn btn-success me-2'>Ver Productos</a>
                <a href='categorias.php' class='btn btn-primary'>Ver Categorías</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
