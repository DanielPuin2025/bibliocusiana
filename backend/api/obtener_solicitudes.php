<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

exigirAdministrador();

// Obtener todas las solicitudes pendientes
$sql = "SELECT
            p.id,
            p.fecha_solicitud,
            p.fecha_prestamo,
            p.fecha_devolucion_esperada,
            p.estado,
            e.nombre as estudiante_nombre,
            e.doc as estudiante_doc,
            e.rol as estudiante_rol,
            l.titulo as libro_titulo,
            l.autor as libro_autor,
            l.id as libro_id
        FROM prestamos p
        INNER JOIN estudiantes e ON p.estudiante_id = e.id
        INNER JOIN libros l ON p.libro_id = l.id
        WHERE p.estado = 'Pendiente'
        ORDER BY p.fecha_solicitud ASC";

$resultado = $conexion->query($sql);

$solicitudes = [];
if ($resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $solicitudes[] = $fila;
    }
}

echo json_encode([
    "status" => "success",
    "solicitudes" => $solicitudes
]);

$conexion->close();
?>
