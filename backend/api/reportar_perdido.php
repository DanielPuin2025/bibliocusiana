<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

exigirAdministrador();
validarCsrf();
$datos = json_decode(file_get_contents('php://input'), true);
$prestamoId = filter_var($datos['prestamo_id'] ?? null, FILTER_VALIDATE_INT);
$tipo = $datos['tipo_reposicion'] ?? '';
$detalle = trim((string) ($datos['detalle_reposicion'] ?? ''));
if (!$prestamoId || !in_array($tipo, ['Mismo título y edición', 'Libro equivalente'], true)) responderNoAutorizado('Datos de reposición inválidos.', 400);

$consulta = $conexion->prepare('SELECT libro_id, estado FROM prestamos WHERE id = ?');
$consulta->bind_param('i', $prestamoId);
$consulta->execute();
$prestamo = $consulta->get_result()->fetch_assoc();
if (!$prestamo || $prestamo['estado'] !== 'Activo') responderNoAutorizado('Solo se pueden reportar como perdidos préstamos activos.', 400);

$actualizar = $conexion->prepare("UPDATE prestamos SET estado = 'Perdido', tipo_reposicion = ?, detalle_reposicion = ?, fecha_reporte_perdida = NOW() WHERE id = ?");
$actualizar->bind_param('ssi', $tipo, $detalle, $prestamoId);
if ($actualizar->execute()) {
    $libro = $conexion->prepare("UPDATE libros SET estado = IF(ejemplares_disponibles > 0, 'Disponible', 'Prestado'), disponible = ejemplares_disponibles > 0 WHERE id = ?");
    $libro->bind_param('i', $prestamo['libro_id']);
    $libro->execute();
    echo json_encode(['status' => 'success', 'message' => 'Préstamo marcado como perdido. Reposición: ' . $tipo . '.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'No fue posible registrar la pérdida.']);
}
$conexion->close();
