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

exigirAdministrador();
validarCsrf();

$datos = json_decode(file_get_contents('php://input'), true);
$documento = trim((string) ($datos['doc'] ?? ''));
$correo = trim((string) ($datos['correo'] ?? ''));
$contrasena = (string) ($datos['contrasena'] ?? '');
$nombre = trim((string) ($datos['nombre'] ?? ''));
$rol = trim((string) ($datos['rol'] ?? 'Estudiante'));
$telefonoWhatsapp = preg_replace('/\D+/', '', (string) ($datos['telefono_whatsapp'] ?? ''));
$whatsappConsentimiento = !empty($datos['whatsapp_consentimiento']) && $telefonoWhatsapp !== '' ? 1 : 0;

if ($documento === '' || $nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($contrasena) < 8 || !in_array($rol, ['Estudiante', 'Profesor', 'Administrador'], true)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Completa documento, nombre, correo válido, rol y contraseña de al menos 8 caracteres.']);
    exit;
}

$consulta = $conexion->prepare('SELECT id FROM estudiantes WHERE doc = ? OR correo = ? LIMIT 1');
$consulta->bind_param('ss', $documento, $correo);
$consulta->execute();
if ($consulta->get_result()->fetch_assoc()) {
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => 'El documento o correo ya está registrado.']);
    exit;
}

$hash = password_hash($contrasena, PASSWORD_DEFAULT);
$insertar = $conexion->prepare('INSERT INTO estudiantes (doc, nombre, correo, telefono_whatsapp, whatsapp_consentimiento, contrasena, rol) VALUES (?, ?, ?, ?, ?, ?, ?)');
$insertar->bind_param('ssssiss', $documento, $nombre, $correo, $telefonoWhatsapp, $whatsappConsentimiento, $hash, $rol);
if (!$insertar->execute()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'No fue posible crear el usuario.']);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'Cuenta creada. Ya puedes iniciar sesión.']);
$conexion->close();