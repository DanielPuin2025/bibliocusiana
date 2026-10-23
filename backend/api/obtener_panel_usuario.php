<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/prestamos.php';

$usuario = usuarioAutenticado();
if ($usuario['rol'] === 'Administrador') responderNoAutorizado('Este panel es solo para estudiantes y profesores.', 403);
$sql = 'SELECT p.id, p.estado, p.fecha_solicitud, p.fecha_devolucion_esperada, p.fecha_solicitud_devolucion, p.renovaciones, p.dias_retraso, p.multa, p.tipo_reposicion, p.detalle_reposicion, l.titulo, l.autor FROM prestamos p INNER JOIN libros l ON l.id = p.libro_id WHERE p.estudiante_id = ? ORDER BY p.fecha_solicitud DESC';
$stmt = $conexion->prepare($sql);
$stmt->bind_param('i', $usuario['id']);
$stmt->execute();
$prestamos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$resumen = ['pendientes' => 0, 'activos' => 0, 'vencidos' => 0, 'perdidos' => 0, 'multa_total' => 0, 'recordatorios' => []];
foreach ($prestamos as &$prestamo) {
    $prestamo['dias_retraso'] = $prestamo['estado'] === 'Activo' ? diasHabilesRetraso($prestamo['fecha_devolucion_esperada']) : (int) $prestamo['dias_retraso'];
    $prestamo['multa_actual'] = $prestamo['estado'] === 'Activo' && $prestamo['dias_retraso'] > 0 ? VALOR_MULTA_LIBRO_VENCIDO : (float) $prestamo['multa'];
    $prestamo['estado_visible'] = estadoPrestamoVisible($prestamo['estado'], $prestamo['fecha_devolucion_esperada']);
    if ($prestamo['estado'] === 'Pendiente') $resumen['pendientes']++;
    if ($prestamo['estado'] === 'Activo') $resumen['activos']++;
    if ($prestamo['estado_visible'] === 'Vencido') $resumen['vencidos']++;
    if ($prestamo['estado'] === 'Perdido') $resumen['perdidos']++;
    if (in_array($prestamo['estado_visible'], ['Próximo a vencer', 'Vencido'], true)) $resumen['recordatorios'][] = ['titulo' => $prestamo['titulo'], 'fecha' => $prestamo['fecha_devolucion_esperada'], 'estado' => $prestamo['estado_visible']];
    $resumen['multa_total'] += (float) $prestamo['multa_actual'];
}
unset($prestamo);
$resumen['en_curso'] = $resumen['pendientes'] + $resumen['activos'] + $resumen['perdidos'];
$resumen['solicitados_total'] = count($prestamos);
$resumen['limite'] = LIMITE_MAXIMO_PRESTAMOS;
echo json_encode(['status' => 'success', 'resumen' => $resumen, 'prestamos' => $prestamos]);
$conexion->close();
