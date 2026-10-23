<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/prestamos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderNoAutorizado('Método no permitido.', 405);
$usuario = usuarioAutenticado();
if ($usuario['rol'] === 'Administrador') responderNoAutorizado('El administrador no puede registrar préstamos.', 403);
validarCsrf();

$datos = json_decode(file_get_contents('php://input'), true) ?: [];
$nombre = trim((string) ($datos['nombre'] ?? ''));
$rolFormulario = trim((string) ($datos['rol'] ?? ''));
$titulo = trim((string) ($datos['libro'] ?? ''));
$telefonoWhatsapp = preg_replace('/\D+/', '', (string) ($datos['telefono_whatsapp'] ?? ''));
$consentimientoWhatsapp = !empty($datos['whatsapp_consentimiento']);
$fechaInicioTexto = trim((string) ($datos['fecha_inicio'] ?? ''));
$fechaFinTexto = trim((string) ($datos['fecha_fin'] ?? ''));
$rolNormalizado = $rolFormulario === 'Docente' ? 'Profesor' : $rolFormulario;
$rolEsperado = $usuario['rol'] === 'Profesor' ? 'Profesor' : 'Estudiante';
if ($nombre === '' || $titulo === '' || $rolNormalizado !== $rolEsperado) {
    responderNoAutorizado('Completa todos los campos con datos válidos.', 422);
}
if (!preg_match('/^[1-9][0-9]{7,14}$/', $telefonoWhatsapp)) {
    responderNoAutorizado('Ingresa un número de WhatsApp internacional válido con código de país, entre 8 y 15 dígitos.', 422);
}
if (!$consentimientoWhatsapp) {
    responderNoAutorizado('Debes autorizar los recordatorios por WhatsApp para registrar el préstamo.', 422);
}
try {
    [$fechaInicio, $fechaFin] = validarRangoFechasPrestamo($fechaInicioTexto, $fechaFinTexto);
} catch (InvalidArgumentException $error) {
    responderNoAutorizado($error->getMessage(), 422);
}

$conexion->begin_transaction();
try {
    $limite = $conexion->prepare("SELECT COUNT(*) AS total FROM prestamos WHERE estudiante_id = ? AND estado = 'Activo' FOR UPDATE");
    $limite->bind_param('i', $usuario['id']);
    $limite->execute();
    if ((int) $limite->get_result()->fetch_assoc()['total'] >= LIMITE_MAXIMO_PRESTAMOS) throw new RuntimeException('Ya alcanzaste el máximo de ' . LIMITE_MAXIMO_PRESTAMOS . ' préstamos activos.');

    $datosUsuario = $conexion->prepare('SELECT correo, grado, area FROM estudiantes WHERE id = ? FOR UPDATE');
    $datosUsuario->bind_param('i', $usuario['id']);
    $datosUsuario->execute();
    $registroUsuario = $datosUsuario->get_result()->fetch_assoc();
    if (!$registroUsuario) throw new RuntimeException('No se encontró el usuario autenticado.');
    $correo = $registroUsuario['correo'];
    $gradoArea = $usuario['rol'] === 'Profesor' ? $registroUsuario['area'] : $registroUsuario['grado'];

    // El formulario expone solo el nombre del libro; el identificador interno permanece oculto.
    $consultaLibro = $conexion->prepare("SELECT id FROM libros WHERE titulo = ? AND ejemplares_disponibles > 0 ORDER BY id ASC LIMIT 1 FOR UPDATE");
    $consultaLibro->bind_param('s', $titulo);
    $consultaLibro->execute();
    $libro = $consultaLibro->get_result()->fetch_assoc();
    if (!$libro) throw new RuntimeException('El libro seleccionado ya no está disponible.');

    $actualizarUsuario = $conexion->prepare('UPDATE estudiantes SET nombre = ?, telefono_whatsapp = ?, whatsapp_consentimiento = 1 WHERE id = ?');
    $actualizarUsuario->bind_param('ssi', $nombre, $telefonoWhatsapp, $usuario['id']);
    $actualizarUsuario->execute();

    $inicioSql = $fechaInicio->format('Y-m-d 00:00:00');
    $finSql = $fechaFin->format('Y-m-d 23:59:59');
    $registrar = $conexion->prepare("INSERT INTO prestamos (nombre, rol, curso_area, estudiante_id, libro_id, libro_titulo, email, fecha_prestamo, fecha_devolucion_esperada, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendiente')");
    $registrar->bind_param('sssiissss', $nombre, $rolEsperado, $gradoArea, $usuario['id'], $libro['id'], $titulo, $correo, $inicioSql, $finSql);
    $registrar->execute();
    $conexion->commit();
    $_SESSION['usuario']['telefono_whatsapp'] = $telefonoWhatsapp;
    $_SESSION['usuario']['whatsapp_consentimiento'] = true;
    echo json_encode(['status' => 'success', 'message' => 'Solicitud registrada y enviada a aprobación.'], JSON_UNESCAPED_UNICODE);
} catch (RuntimeException $error) {
    $conexion->rollback(); responderNoAutorizado($error->getMessage(), 422);
} catch (Throwable $error) {
    $conexion->rollback(); error_log($error->getMessage()); responderNoAutorizado('No fue posible registrar el préstamo.', 500);
}
$conexion->close();
