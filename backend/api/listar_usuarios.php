<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

exigirAdministrador();

$q = '%' . trim((string) ($_GET['q'] ?? '')) . '%';
$rol = trim((string) ($_GET['rol'] ?? ''));
$sinTelefono = !empty($_GET['sin_telefono']);

$sql = "SELECT id, doc, nombre, correo, telefono_whatsapp, whatsapp_consentimiento, grado, area, rol
        FROM estudiantes WHERE (doc LIKE ? OR nombre LIKE ? OR correo LIKE ?)";
$tipos = 'sss';
$params = [$q, $q, $q];
if (in_array($rol, ['Estudiante', 'Profesor', 'Administrador'], true)) {
    $sql .= ' AND rol = ?';
    $tipos .= 's';
    $params[] = $rol;
}
if ($sinTelefono) $sql .= " AND (telefono_whatsapp IS NULL OR telefono_whatsapp = '')";
$sql .= ' ORDER BY nombre LIMIT 100';

$consulta = $conexion->prepare($sql);
$consulta->bind_param($tipos, ...$params);
$consulta->execute();
$usuarios = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
echo json_encode(['status' => 'success', 'usuarios' => $usuarios], JSON_UNESCAPED_UNICODE);
$conexion->close();
