<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderNoAutorizado('Método no permitido.', 405);
exigirAdministrador();
validarCsrf();

function fallar(string $mensaje, int $codigo = 422): void {
    http_response_code($codigo);
    echo json_encode(['status' => 'error', 'message' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

$d = json_decode(file_get_contents('php://input'), true) ?: [];
$id = (int) ($d['id'] ?? 0);
$nombre = trim((string) ($d['nombre'] ?? ''));
$correo = trim((string) ($d['correo'] ?? ''));
$telefono = preg_replace('/\D+/', '', (string) ($d['telefono_whatsapp'] ?? ''));
$grado = trim((string) ($d['grado'] ?? ''));
$area = trim((string) ($d['area'] ?? ''));
$rol = trim((string) ($d['rol'] ?? ''));
$contrasena = (string) ($d['contrasena'] ?? '');

if ($id <= 0 || $nombre === '' || !in_array($rol, ['Estudiante', 'Profesor', 'Administrador'], true)) fallar('Datos incompletos.');
if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) fallar('Correo no válido.');
if ($telefono !== '' && (strlen($telefono) < 8 || strlen($telefono) > 15)) fallar('El teléfono debe tener entre 8 y 15 dígitos (con indicativo, ej. 573001234567).');
if ($contrasena !== '' && strlen($contrasena) < 8) fallar('La nueva contraseña debe tener al menos 8 caracteres.');

$consentimiento = !empty($d['whatsapp_consentimiento']) && $telefono !== '' ? 1 : 0;
$correoDb = $correo === '' ? null : $correo;

if ($correoDb !== null) {
    $dup = $conexion->prepare('SELECT id FROM estudiantes WHERE correo = ? AND id <> ? LIMIT 1');
    $dup->bind_param('si', $correoDb, $id);
    $dup->execute();
    if ($dup->get_result()->fetch_assoc()) fallar('Ese correo ya pertenece a otro usuario.', 409);
}

$actual = $conexion->prepare('SELECT rol FROM estudiantes WHERE id = ?');
$actual->bind_param('i', $id);
$actual->execute();
$fila = $actual->get_result()->fetch_assoc();
if (!$fila) fallar('Usuario no encontrado.', 404);
if ($fila['rol'] === 'Administrador' && $rol !== 'Administrador') {
    $n = (int) $conexion->query("SELECT COUNT(*) FROM estudiantes WHERE rol = 'Administrador'")->fetch_row()[0];
    if ($n <= 1) fallar('Debe existir al menos un administrador.');
}

$ok = $conexion->prepare('UPDATE estudiantes SET nombre = ?, correo = ?, telefono_whatsapp = ?, whatsapp_consentimiento = ?, grado = ?, area = ?, rol = ? WHERE id = ?');
$ok->bind_param('sssisssi', $nombre, $correoDb, $telefono, $consentimiento, $grado, $area, $rol, $id);
if (!$ok->execute()) fallar('No fue posible guardar los cambios.', 500);

if ($contrasena !== '') {
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);
    $c = $conexion->prepare('UPDATE estudiantes SET contrasena = ? WHERE id = ?');
    $c->bind_param('si', $hash, $id);
    $c->execute();
}

echo json_encode(['status' => 'success', 'message' => 'Usuario actualizado.'], JSON_UNESCAPED_UNICODE);
$conexion->close();
