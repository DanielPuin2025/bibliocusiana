<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderNoAutorizado('Método no permitido.', 405);
exigirAdministrador();
validarCsrf();

$datos = json_decode(file_get_contents('php://input'), true);
$id = filter_var($datos['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) responderNoAutorizado('Libro inválido.', 400);

$consulta = $conexion->prepare('SELECT imagen, (SELECT COUNT(*) FROM prestamos WHERE libro_id = libros.id) AS prestamos FROM libros WHERE id = ?');
$consulta->bind_param('i', $id);
$consulta->execute();
$libro = $consulta->get_result()->fetch_assoc();
if (!$libro) responderNoAutorizado('El libro no existe.', 404);
// El historial de préstamos referencia al libro; borrarlo dejaría registros huérfanos.
if ((int) $libro['prestamos'] > 0) {
    responderNoAutorizado('Este libro tiene préstamos registrados y no puede eliminarse. Edítalo y deja los ejemplares según corresponda.', 409);
}

try {
    $borrar = $conexion->prepare('DELETE FROM libros WHERE id = ?');
    $borrar->bind_param('i', $id);
    $borrar->execute();
} catch (mysqli_sql_exception $error) {
    responderNoAutorizado('No fue posible eliminar el libro.', 500);
}
if (is_string($libro['imagen']) && str_starts_with($libro['imagen'], 'imagenes/')) {
    @unlink(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'imagenes' . DIRECTORY_SEPARATOR . basename($libro['imagen']));
}
echo json_encode(['status' => 'success', 'message' => 'Libro eliminado.'], JSON_UNESCAPED_UNICODE);
$conexion->close();
