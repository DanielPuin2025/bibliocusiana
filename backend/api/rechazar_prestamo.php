<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

$admin = exigirAdministrador();
validarCsrf();

$datos = json_decode(file_get_contents("php://input"), true);

if (!$datos || !isset($datos['prestamo_id'])) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos."]);
    exit;
}

$prestamo_id = intval($datos['prestamo_id']);
$admin_id = (int) $admin['id'];
$notas = isset($datos['notas']) ? $datos['notas'] : '';

// Verificar que el usuario sea administrador
$sql_admin = "SELECT rol FROM estudiantes WHERE id = ?";
$stmt_admin = $conexion->prepare($sql_admin);
$stmt_admin->bind_param("i", $admin_id);
$stmt_admin->execute();
$resultado_admin = $stmt_admin->get_result();
$usuario = $resultado_admin->fetch_assoc();

if (!$usuario || $usuario['rol'] !== 'Administrador') {
    echo json_encode(["status" => "error", "message" => "No tienes permisos de administrador."]);
    exit;
}

// Rechazar el préstamo
$sql_rechazar = "UPDATE prestamos
                 SET estado = 'Rechazado',
                     aprobado_por = ?,
                     fecha_aprobacion = NOW(),
                     notas = ?
                 WHERE id = ? AND estado = 'Pendiente'";
$stmt_rechazar = $conexion->prepare($sql_rechazar);
$stmt_rechazar->bind_param("isi", $admin_id, $notas, $prestamo_id);

if ($stmt_rechazar->execute()) {
    if ($stmt_rechazar->affected_rows > 0) {
        echo json_encode(["status" => "success", "message" => "Préstamo rechazado."]);
    } else {
        echo json_encode(["status" => "error", "message" => "La solicitud no existe o ya fue procesada."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Error al rechazar el préstamo."]);
}

$conexion->close();
?>
