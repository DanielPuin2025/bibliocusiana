<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/prestamos.php';

$admin = exigirAdministrador();
validarCsrf();
$datos = json_decode(file_get_contents('php://input'), true);
$prestamoId = filter_var($datos['prestamo_id'] ?? null, FILTER_VALIDATE_INT);
if (!$prestamoId) responderNoAutorizado('Datos incompletos.', 400);

$conexion->begin_transaction();
try {
    $consulta = $conexion->prepare("SELECT p.libro_id, p.estado, p.fecha_prestamo, p.fecha_devolucion_esperada, e.rol, l.categoria_prestamo, l.ejemplares_disponibles FROM prestamos p INNER JOIN estudiantes e ON e.id = p.estudiante_id INNER JOIN libros l ON l.id = p.libro_id WHERE p.id = ? FOR UPDATE");
    $consulta->bind_param('i', $prestamoId);
    $consulta->execute();
    $prestamo = $consulta->get_result()->fetch_assoc();
    if (!$prestamo) throw new RuntimeException('La solicitud no existe.');
    if ($prestamo['estado'] !== 'Pendiente') throw new RuntimeException('Esta solicitud ya fue procesada.');
    if ((int) $prestamo['ejemplares_disponibles'] < 1) throw new RuntimeException('El libro ya no tiene ejemplares disponibles.');

    if (empty($prestamo['fecha_prestamo']) || empty($prestamo['fecha_devolucion_esperada'])) {
        $fechaInicio = new DateTimeImmutable('today');
        $diasPrestamo = diasPoliticaPrestamo($conexion, $prestamo['categoria_prestamo'], $prestamo['rol']);
        $fechaFin = fechaDevolucionPrestamo($fechaInicio, $diasPrestamo);
    } else {
        [$fechaInicio, $fechaFin] = validarRangoFechasPrestamo(substr($prestamo['fecha_prestamo'], 0, 10), substr($prestamo['fecha_devolucion_esperada'], 0, 10));
    }
    $fechaInicioSql = $fechaInicio->format('Y-m-d 00:00:00');
    $fechaLimite = $fechaFin->format('Y-m-d 23:59:59');
    $actualizar = $conexion->prepare("UPDATE prestamos SET estado = 'Activo', aprobado_por = ?, fecha_aprobacion = NOW(), fecha_prestamo = ?, fecha_devolucion_esperada = ? WHERE id = ?");
    $actualizar->bind_param('issi', $admin['id'], $fechaInicioSql, $fechaLimite, $prestamoId);
    $actualizar->execute();
    $libro = $conexion->prepare("UPDATE libros SET ejemplares_disponibles = ejemplares_disponibles - 1, disponible = ejemplares_disponibles > 0, estado = IF(ejemplares_disponibles > 0, 'Disponible', 'Prestado') WHERE id = ? AND ejemplares_disponibles > 0");
    $libro->bind_param('i', $prestamo['libro_id']);
    $libro->execute();
    if ($libro->affected_rows !== 1) throw new RuntimeException('El libro ya no tiene ejemplares disponibles.');
    $conexion->commit();
    echo json_encode(['status' => 'success', 'message' => 'Préstamo aprobado. Fecha de inicio: ' . $fechaInicio->format('d/m/Y') . '. Fecha de devolución: ' . $fechaFin->format('d/m/Y') . '.']);
} catch (InvalidArgumentException $error) {
    $conexion->rollback();
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => $error->getMessage()]);
} catch (RuntimeException $error) {
    $conexion->rollback();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    $conexion->rollback();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'No fue posible aprobar el prestamo.']);
}
$conexion->close();
