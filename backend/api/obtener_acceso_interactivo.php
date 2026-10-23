<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

$usuario = usuarioAutenticado();
$libroId = filter_input(INPUT_GET, 'libro_id', FILTER_VALIDATE_INT);
if (!$libroId || $libroId < 1) responderNoAutorizado('Selecciona un libro válido.', 400);

if ($usuario['rol'] === 'Administrador') {
    echo json_encode(['status' => 'success', 'permitido' => false, 'mensaje' => 'El módulo interactivo es para estudiantes y docentes.']);
    exit;
}

$activo = $conexion->prepare("SELECT id FROM prestamos WHERE estudiante_id = ? AND libro_id = ? AND estado = 'Activo' LIMIT 1");
$activo->bind_param('ii', $usuario['id'], $libroId);
$activo->execute();
if ($activo->get_result()->fetch_assoc()) {
    echo json_encode(['status' => 'success', 'permitido' => false, 'mensaje' => 'Este libro continúa en préstamo. Registra la devolución para desbloquear la reseña y la actividad.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$devuelto = $conexion->prepare("SELECT id FROM prestamos WHERE estudiante_id = ? AND libro_id = ? AND estado = 'Devuelto' LIMIT 1");
$devuelto->bind_param('ii', $usuario['id'], $libroId);
$devuelto->execute();
$permitido = (bool) $devuelto->get_result()->fetch_assoc();
echo json_encode([
    'status' => 'success',
    'permitido' => $permitido,
    'mensaje' => $permitido ? 'Acceso desbloqueado: ya registramos la devolución.' : 'Podrás acceder después de devolver este libro.'
], JSON_UNESCAPED_UNICODE);
$conexion->close();
