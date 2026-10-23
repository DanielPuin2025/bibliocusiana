<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

$usuario = usuarioAutenticado();

$datos = json_decode(file_get_contents("php://input"), true);

$estudiante_id = (int) $usuario['id'];

// Obtener historial de préstamos del usuario
$sql = "SELECT
            p.id,
            p.fecha_solicitud,
            p.fecha_prestamo,
            p.fecha_devolucion_esperada,
            p.fecha_devolucion_real,
            p.estado,
            l.titulo as libro_titulo,
            l.autor as libro_autor
        FROM prestamos p
        INNER JOIN libros l ON p.libro_id = l.id
        WHERE p.estudiante_id = ?
        ORDER BY p.fecha_solicitud DESC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $estudiante_id);
$stmt->execute();
$resultado = $stmt->get_result();

$historial = [];
if ($resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        $historial[] = $fila;
    }
}

echo json_encode([
    "status" => "success",
    "historial" => $historial
]);

$conexion->close();
?>
