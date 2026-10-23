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

$usuario = usuarioAutenticado();
validarCsrf();

$datos = json_decode(file_get_contents('php://input'), true);
$actual = (string) ($datos['actual'] ?? '');
$nueva = (string) ($datos['nueva'] ?? '');

if ($actual === '' || strlen($nueva) < 8) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Escribe tu contraseña actual y una nueva de al menos 8 caracteres.']);
    exit;
}
if (hash_equals($actual, $nueva)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'La nueva contraseña debe ser distinta a la actual.']);
    exit;
}

$id = (int) $usuario['id'];
$consulta = $conexion->prepare('SELECT contrasena FROM estudiantes WHERE id = ? LIMIT 1');
$consulta->bind_param('i', $id);
$consulta->execute();
$fila = $consulta->get_result()->fetch_assoc();

$valida = $fila && (password_verify($actual, $fila['contrasena']) || hash_equals($fila['contrasena'], $actual));
if (!$valida) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'La contraseña actual es incorrecta.']);
    exit;
}

$hash = password_hash($nueva, PASSWORD_DEFAULT);
$actualizar = $conexion->prepare('UPDATE estudiantes SET contrasena = ? WHERE id = ?');
$actualizar->bind_param('si', $hash, $id);
if (!$actualizar->execute()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'No fue posible cambiar la contraseña.']);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'Contraseña actualizada correctamente.']);
$conexion->close();
