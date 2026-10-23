<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/prestamos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderNoAutorizado('Método no permitido.', 405);
$usuario = usuarioAutenticado();
if ($usuario['rol'] === 'Administrador') responderNoAutorizado('Los administradores no pueden renovar préstamos.', 403);
validarCsrf();

$datos = json_decode(file_get_contents('php://input'), true) ?: [];
$prestamoId = filter_var($datos['prestamo_id'] ?? null, FILTER_VALIDATE_INT);
if (!$prestamoId) responderNoAutorizado('Datos incompletos.', 400);

$conexion->begin_transaction();
$consulta = $conexion->prepare("SELECT p.fecha_prestamo, p.fecha_devolucion_esperada, p.renovaciones, l.categoria_prestamo FROM prestamos p INNER JOIN libros l ON l.id = p.libro_id WHERE p.id = ? AND p.estudiante_id = ? AND p.estado = 'Activo' FOR UPDATE");
$consulta->bind_param('ii', $prestamoId, $usuario['id']);
$consulta->execute();
$prestamo = $consulta->get_result()->fetch_assoc();
if (!$prestamo) { $conexion->rollback(); responderNoAutorizado('El préstamo no está activo o no te pertenece.', 404); }
if ((int) $prestamo['renovaciones'] >= 1) { $conexion->rollback(); responderNoAutorizado('Este préstamo ya fue renovado una vez.', 409); }
try {
    $dias = diasPoliticaPrestamo($conexion, $prestamo['categoria_prestamo'], $usuario['rol']);
    $base = new DateTimeImmutable(max(date('Y-m-d'), substr($prestamo['fecha_devolucion_esperada'], 0, 10)));
    $fechaActual = new DateTimeImmutable(substr($prestamo['fecha_devolucion_esperada'], 0, 10));
    $fechaMaxima = fechaDevolucionPrestamo(new DateTimeImmutable($prestamo['fecha_prestamo']), 30);
    $nuevaFechaDate = fechaDevolucionPrestamo($base, $dias);
    if ($nuevaFechaDate > $fechaMaxima) $nuevaFechaDate = $fechaMaxima;
    if ($nuevaFechaDate <= $fechaActual) {
        $conexion->rollback();
        responderNoAutorizado('El préstamo ya alcanzó su fecha máxima de 30 días y no se puede extender más.', 422);
    }
    $nuevaFecha = $nuevaFechaDate->format('Y-m-d 23:59:59');
    $actualizar = $conexion->prepare('UPDATE prestamos SET fecha_devolucion_esperada = ?, fecha_devolucion = DATE(?), renovaciones = renovaciones + 1, ultima_renovacion = NOW() WHERE id = ? AND renovaciones < 1');
    $actualizar->bind_param('ssi', $nuevaFecha, $nuevaFecha, $prestamoId);
    $actualizar->execute();
    if ($actualizar->affected_rows !== 1) throw new RuntimeException('El préstamo ya fue renovado.');
    $conexion->commit();
    echo json_encode(['status' => 'success', 'fecha_devolucion' => $nuevaFecha, 'message' => 'Préstamo renovado correctamente.'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    $conexion->rollback();
    responderNoAutorizado('No fue posible renovar el préstamo.', 500);
}
$conexion->close();