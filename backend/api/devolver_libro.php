<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/prestamos.php';

exigirAdministrador();
validarCsrf();
$datos = json_decode(file_get_contents('php://input'), true);
$prestamoId = filter_var($datos['prestamo_id'] ?? null, FILTER_VALIDATE_INT);
if (!$prestamoId) responderNoAutorizado('Datos incompletos.', 400);

$consulta = $conexion->prepare('SELECT libro_id, estado, fecha_devolucion_esperada FROM prestamos WHERE id = ?');
$consulta->bind_param('i', $prestamoId);
$consulta->execute();
$prestamo = $consulta->get_result()->fetch_assoc();
if (!$prestamo || $prestamo['estado'] !== 'Activo') responderNoAutorizado('Este prestamo no esta activo.', 400);

$diasRetraso = 0;
$multa = 0;
$actualizar = $conexion->prepare("UPDATE prestamos SET estado = 'Devuelto', fecha_devolucion_real = NOW(), dias_retraso = 0, multa = 0 WHERE id = ?");
$actualizar->bind_param('i', $prestamoId);
if ($actualizar->execute()) {
    $libro = $conexion->prepare("UPDATE libros SET ejemplares_disponibles = LEAST(ejemplares_total, ejemplares_disponibles + 1), disponible = TRUE, estado = 'Disponible' WHERE id = ?");
    $libro->bind_param('i', $prestamo['libro_id']);
    $libro->execute();
    echo json_encode(['status' => 'success', 'dias_retraso' => $diasRetraso, 'multa' => $multa]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'No fue posible registrar la devolucion.']);
}
$conexion->close();
