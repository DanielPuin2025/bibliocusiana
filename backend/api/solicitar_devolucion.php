<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderNoAutorizado('Método no permitido.', 405);
$usuario = usuarioAutenticado();
if ($usuario['rol'] === 'Administrador') responderNoAutorizado('Esta acción es solo para estudiantes y docentes.', 403);
validarCsrf();

$datos = json_decode(file_get_contents('php://input'), true) ?: [];
$prestamoId = filter_var($datos['prestamo_id'] ?? null, FILTER_VALIDATE_INT);
if (!$prestamoId) responderNoAutorizado('Préstamo inválido.', 400);

$consulta = $conexion->prepare("SELECT estado, fecha_solicitud_devolucion FROM prestamos WHERE id = ? AND estudiante_id = ? LIMIT 1");
$consulta->bind_param('ii', $prestamoId, $usuario['id']);
$consulta->execute();
$prestamo = $consulta->get_result()->fetch_assoc();
if (!$prestamo || $prestamo['estado'] !== 'Activo') responderNoAutorizado('Solo puedes solicitar la devolución de un préstamo activo.', 404);
if ($prestamo['fecha_solicitud_devolucion'] !== null) responderNoAutorizado('Ya solicitaste la devolución de este libro.', 409);

$actualizar = $conexion->prepare("UPDATE prestamos SET fecha_solicitud_devolucion = NOW() WHERE id = ? AND estudiante_id = ? AND estado = 'Activo' AND fecha_solicitud_devolucion IS NULL");
$actualizar->bind_param('ii', $prestamoId, $usuario['id']);
if (!$actualizar->execute() || $actualizar->affected_rows !== 1) responderNoAutorizado('No se pudo registrar la solicitud de devolución.', 409);

echo json_encode(['status' => 'success', 'message' => 'Solicitud de devolución enviada. Entrega el libro en la biblioteca para que administración confirme la recepción.'], JSON_UNESCAPED_UNICODE);
$conexion->close();