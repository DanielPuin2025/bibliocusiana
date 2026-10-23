<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

$usuario = usuarioAutenticado();
if ($usuario['rol'] === 'Administrador') {
    echo json_encode(['status' => 'success', 'libros' => []]);
    exit;
}

// Un libro aparece una vez por cada préstamo devuelto que aún no tiene reseña.
$stmt = $conexion->prepare("SELECT DISTINCT l.id, l.titulo FROM prestamos p INNER JOIN libros l ON l.id = p.libro_id LEFT JOIN resenas r ON r.prestamo_id = p.id WHERE p.estudiante_id = ? AND p.estado = 'Devuelto' AND r.id IS NULL ORDER BY l.titulo ASC");
$stmt->bind_param('i', $usuario['id']);
$stmt->execute();
echo json_encode(['status' => 'success', 'libros' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)], JSON_UNESCAPED_UNICODE);
$conexion->close();
