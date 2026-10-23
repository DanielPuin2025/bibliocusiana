<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/prestamos.php';

exigirAdministrador();
$sql = 'SELECT p.id, p.estado, p.fecha_solicitud, p.fecha_devolucion_esperada, p.fecha_solicitud_devolucion, p.dias_retraso, p.multa, p.tipo_reposicion, p.detalle_reposicion, p.fecha_reporte_perdida, e.nombre AS usuario_nombre, e.rol AS usuario_rol, l.titulo, l.autor FROM prestamos p INNER JOIN estudiantes e ON e.id = p.estudiante_id INNER JOIN libros l ON l.id = p.libro_id ORDER BY p.fecha_devolucion_esperada ASC, p.fecha_solicitud ASC';
$prestamos = $conexion->query($sql)->fetch_all(MYSQLI_ASSOC);
$resumen = ['pendientes' => 0, 'activos' => 0, 'vencidos' => 0, 'perdidos' => 0, 'multa_total' => 0];
foreach ($prestamos as &$prestamo) {
    $prestamo['dias_retraso'] = $prestamo['estado'] === 'Activo' ? diasHabilesRetraso($prestamo['fecha_devolucion_esperada']) : (int) $prestamo['dias_retraso'];
    $prestamo['multa_actual'] = $prestamo['estado'] === 'Activo' && $prestamo['dias_retraso'] > 0 ? VALOR_MULTA_LIBRO_VENCIDO : (float) $prestamo['multa'];
    $prestamo['estado_visible'] = $prestamo['estado'] === 'Activo' && $prestamo['fecha_solicitud_devolucion'] ? 'Devolución solicitada' : estadoPrestamoVisible($prestamo['estado'], $prestamo['fecha_devolucion_esperada']);
    if ($prestamo['estado'] === 'Pendiente') $resumen['pendientes']++;
    if ($prestamo['estado'] === 'Activo') $resumen['activos']++;
    if ($prestamo['estado_visible'] === 'Vencido') $resumen['vencidos']++;
    if ($prestamo['estado'] === 'Perdido') $resumen['perdidos']++;
    $resumen['multa_total'] += (float) $prestamo['multa_actual'];
}
unset($prestamo);
echo json_encode(['status' => 'success', 'resumen' => $resumen, 'prestamos' => $prestamos, 'multa_por_libro' => VALOR_MULTA_LIBRO_VENCIDO]);
$conexion->close();
