<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

$datos = json_decode(file_get_contents('php://input'), true);
if (!is_array($datos)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Datos de inicio de sesión inválidos.']);
    exit;
}

$documento = trim((string) ($datos['doc'] ?? ''));
$contrasena = (string) ($datos['contrasena'] ?? '');
$rol = (string) ($datos['rol'] ?? '');
$rolesPermitidos = ['Estudiante', 'Profesor', 'Administrador'];

if ($documento === '' || $contrasena === '' || !in_array($rol, $rolesPermitidos, true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Completa el documento, la contraseña y el rol.']);
    exit;
}

$sentencia = $conexion->prepare(
    'SELECT id, doc, nombre, correo, telefono_whatsapp, whatsapp_consentimiento, contrasena, rol FROM estudiantes WHERE doc = ? LIMIT 1'
);
$sentencia->bind_param('s', $documento);
$sentencia->execute();
$usuario = $sentencia->get_result()->fetch_assoc();

// La comparación directa mantiene compatibilidad con los usuarios de prueba
// actuales; password_verify permite migrar contraseñas a hash gradualmente.
$contrasenaValida = $usuario && (
    password_verify($contrasena, $usuario['contrasena']) ||
    hash_equals($usuario['contrasena'], $contrasena)
);

if (!$contrasenaValida || $usuario['rol'] !== $rol) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Documento, contraseña o rol incorrectos.']);
    $conexion->close();
    exit;
}

if (!str_starts_with($usuario['contrasena'], '$2y$') && !str_starts_with($usuario['contrasena'], '$argon2')) {
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);
    $migrar = $conexion->prepare('UPDATE estudiantes SET contrasena = ? WHERE id = ?');
    $migrar->bind_param('si', $hash, $usuario['id']);
    $migrar->execute();
}

iniciarSesionSegura();
session_regenerate_id(true);

$_SESSION['usuario'] = [
    'id' => (int) $usuario['id'],
    'doc' => $usuario['doc'],
    'nombre' => $usuario['nombre'],
    'correo' => $usuario['correo'],
    'telefono_whatsapp' => $usuario['telefono_whatsapp'],
    'whatsapp_consentimiento' => (bool) $usuario['whatsapp_consentimiento'],
    'rol' => $usuario['rol'],
];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo json_encode([
    'status' => 'success',
    'usuario' => $_SESSION['usuario'],
    'csrf_token' => $_SESSION['csrf_token'],
]);

$conexion->close();
