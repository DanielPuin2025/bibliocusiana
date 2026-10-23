<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

exigirAdministrador();

// Estadísticas generales del sistema
$estadisticas = [];

// Total de libros
$sql_libros = "SELECT COUNT(*) as total FROM libros";
$resultado = $conexion->query($sql_libros);
$estadisticas['total_libros'] = $resultado->fetch_assoc()['total'];

// Libros disponibles
$sql_disponibles = "SELECT COUNT(*) as total FROM libros WHERE estado = 'Disponible'";
$resultado = $conexion->query($sql_disponibles);
$estadisticas['libros_disponibles'] = $resultado->fetch_assoc()['total'];

// Libros prestados
$sql_prestados = "SELECT COUNT(*) as total FROM libros WHERE estado = 'Prestado'";
$resultado = $conexion->query($sql_prestados);
$estadisticas['libros_prestados'] = $resultado->fetch_assoc()['total'];

// Préstamos activos
$sql_activos = "SELECT COUNT(*) as total FROM prestamos WHERE estado = 'Activo'";
$resultado = $conexion->query($sql_activos);
$estadisticas['prestamos_activos'] = $resultado->fetch_assoc()['total'];

// Solicitudes pendientes
$sql_pendientes = "SELECT COUNT(*) as total FROM prestamos WHERE estado = 'Pendiente'";
$resultado = $conexion->query($sql_pendientes);
$estadisticas['solicitudes_pendientes'] = $resultado->fetch_assoc()['total'];

// Préstamos vencidos
$sql_vencidos = "SELECT COUNT(*) as total FROM prestamos
                 WHERE estado = 'Activo'
                 AND fecha_devolucion_esperada < NOW()";
$resultado = $conexion->query($sql_vencidos);
$estadisticas['prestamos_vencidos'] = $resultado->fetch_assoc()['total'];

// Total de usuarios
$sql_usuarios = "SELECT COUNT(*) as total FROM estudiantes";
$resultado = $conexion->query($sql_usuarios);
$estadisticas['total_usuarios'] = $resultado->fetch_assoc()['total'];

// Préstamos por rol
$sql_por_rol = "SELECT e.rol, COUNT(p.id) as total
                FROM prestamos p
                INNER JOIN estudiantes e ON p.estudiante_id = e.id
                WHERE p.estado IN ('Activo', 'Devuelto')
                GROUP BY e.rol";
$resultado = $conexion->query($sql_por_rol);
$prestamos_por_rol = [];
while ($fila = $resultado->fetch_assoc()) {
    $prestamos_por_rol[] = $fila;
}
$estadisticas['prestamos_por_rol'] = $prestamos_por_rol;

echo json_encode([
    "status" => "success",
    "estadisticas" => $estadisticas
]);

$conexion->close();
?>
