<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderNoAutorizado('Método no permitido.', 405);
validarCsrf();
$usuario = usuarioAutenticado();
$datos = json_decode(file_get_contents('php://input'), true);
$libroId = filter_var($datos['libro_id'] ?? null, FILTER_VALIDATE_INT);
$calificacion = filter_var($datos['calificacion'] ?? null, FILTER_VALIDATE_INT);
$comentario = trim((string) ($datos['comentario'] ?? ''));
if (!$libroId || !$calificacion || $calificacion < 1 || $calificacion > 5 || mb_strlen($comentario) > 1000) responderNoAutorizado('Datos de reseña inválidos.', 400);
if ($usuario['rol'] === 'Administrador') responderNoAutorizado('Los administradores no publican reseñas.', 403);
$libro = $conexion->prepare('SELECT id FROM libros WHERE id = ?'); $libro->bind_param('i', $libroId); $libro->execute();
if (!$libro->get_result()->fetch_assoc()) responderNoAutorizado('El libro no existe.', 404);

// Solo se reseñan préstamos devueltos que todavía no tienen reseña.
$prestamoDevuelto = $conexion->prepare("SELECT p.id FROM prestamos p LEFT JOIN resenas r ON r.prestamo_id = p.id WHERE p.estudiante_id = ? AND p.libro_id = ? AND p.estado = 'Devuelto' AND r.id IS NULL ORDER BY p.fecha_devolucion_real DESC LIMIT 1");
$prestamoDevuelto->bind_param('ii', $usuario['id'], $libroId);
$prestamoDevuelto->execute();
$filaPrestamo = $prestamoDevuelto->get_result()->fetch_assoc();
if (!$filaPrestamo) responderNoAutorizado('Solo puedes reseñar libros que hayas devuelto y que aún no hayas calificado.', 403);

$prestamoId = (int) $filaPrestamo['id'];

$stmt = $conexion->prepare('INSERT INTO resenas (libro_id, estudiante_id, prestamo_id, calificacion, comentario) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('iiiis', $libroId, $usuario['id'], $prestamoId, $calificacion, $comentario);
try {
    if (!$stmt->execute()) responderNoAutorizado('No fue posible publicar la reseña.', 500);
} catch (mysqli_sql_exception $error) {
    responderNoAutorizado('Ya publicaste una reseña para este préstamo.', 409);
}
echo json_encode(['status' => 'success', 'message' => 'Reseña publicada.'], JSON_UNESCAPED_UNICODE);
$conexion->close();
