<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

exigirAdministrador();

// Obtener todos los préstamos activos con información del estudiante y libro
$sql = "SELECT
            p.id,
            p.fecha_prestamo,
            p.estado,
            e.nombre as estudiante_nombre,
            e.doc as estudiante_doc,
            l.titulo as libro_titulo,
            l.autor as libro_autor,
            l.id as libro_id
        FROM prestamos p
        INNER JOIN estudiantes e ON p.estudiante_id = e.id
        INNER JOIN libros l ON p.libro_id = l.id
        WHERE p.estado = 'Activo'
        ORDER BY p.fecha_prestamo DESC";

$resultado = $conexion->query($sql);

$prestamos = [];
if ($resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $prestamos[] = $fila;
    }
}

echo json_encode([
    "status" => "success",
    "prestamos" => $prestamos
]);

$conexion->close();
?>
