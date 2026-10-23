<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/portadas.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderNoAutorizado('Método no permitido.', 405);
exigirAdministrador();
validarCsrf();
asegurarColumnaPortadaBuscada($conexion);

$datos = json_decode(file_get_contents('php://input'), true);
if (!is_array($datos)) responderNoAutorizado('Datos inválidos.', 400);
$id = filter_var($datos['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$titulo = trim((string) ($datos['titulo'] ?? ''));
$autor = trim((string) ($datos['autor'] ?? ''));
$codigo = trim((string) ($datos['codigo'] ?? ''));
$editorial = trim((string) ($datos['editorial'] ?? ''));
$ubicacion = trim((string) ($datos['ubicacion'] ?? ''));
$isbn = trim((string) ($datos['isbn'] ?? ''));
$sinopsis = trim((string) ($datos['sinopsis'] ?? ''));
$genero = trim((string) ($datos['genero_literario'] ?? '')) ?: 'Consulta';
$imagenUrl = trim((string) ($datos['imagen_url'] ?? ''));
$anioTexto = trim((string) ($datos['anio_publicacion'] ?? ''));
$anio = $anioTexto === '' ? null : filter_var($anioTexto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1000, 'max_range' => 2100]]);
$total = filter_var($datos['ejemplares_total'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9999]]);

if (!$id || $titulo === '' || $autor === '' || !$total || $anio === false
    || mb_strlen($titulo) > 200 || mb_strlen($autor) > 100 || mb_strlen($codigo) > 50 || mb_strlen($editorial) > 120
    || mb_strlen($ubicacion) > 120 || mb_strlen($isbn) > 20 || mb_strlen($genero) > 80) {
    responderNoAutorizado('Revisa los campos: título, autor y ejemplares son obligatorios.', 422);
}
if ($imagenUrl !== '' && (!preg_match('#^https://#i', $imagenUrl) || !filter_var($imagenUrl, FILTER_VALIDATE_URL) || mb_strlen($imagenUrl) > 500)) {
    responderNoAutorizado('La URL de la portada debe empezar con https://', 422);
}

$conexion->begin_transaction();
try {
    $consulta = $conexion->prepare('SELECT titulo, autor, isbn, imagen, imagen_url FROM libros WHERE id = ? FOR UPDATE');
    $consulta->bind_param('i', $id);
    $consulta->execute();
    $anterior = $consulta->get_result()->fetch_assoc();
    if (!$anterior) throw new RuntimeException('El libro no existe.');

    $activos = $conexion->prepare("SELECT COUNT(*) AS total FROM prestamos WHERE libro_id = ? AND estado = 'Activo'");
    $activos->bind_param('i', $id);
    $activos->execute();
    $prestados = (int) $activos->get_result()->fetch_assoc()['total'];
    if ($total < $prestados) throw new RuntimeException("No puedes dejar menos de $prestados ejemplares: hay esa cantidad en préstamo.");
    $disponibles = $total - $prestados;
    $estado = $disponibles > 0 ? 'Disponible' : 'Prestado';
    $disponible = $disponibles > 0 ? 1 : 0;

    // Si cambian los datos de búsqueda y no hay portada manual, se vuelve a buscar la portada.
    $cambioBusqueda = $anterior['titulo'] !== $titulo || $anterior['autor'] !== $autor || (string) $anterior['isbn'] !== $isbn;
    $nuevaPortada = $imagenUrl !== '' ? $imagenUrl : $anterior['imagen_url'];
    $reiniciarBusqueda = $imagenUrl === '' && $cambioBusqueda;
    if ($reiniciarBusqueda) $nuevaPortada = null;

    $actualizar = $conexion->prepare("UPDATE libros SET titulo = ?, autor = ?, codigo = NULLIF(?, ''), editorial = NULLIF(?, ''), ubicacion = NULLIF(?, ''), isbn = NULLIF(?, ''), anio_publicacion = ?, sinopsis = NULLIF(?, ''), genero_literario = ?, ejemplares_total = ?, ejemplares_disponibles = ?, disponible = ?, estado = ?, imagen_url = ?, portada_buscada_en = IF(?, NULL, portada_buscada_en) WHERE id = ?");
    $reiniciar = $reiniciarBusqueda ? 1 : 0;
    $actualizar->bind_param('ssssssissiiissii', $titulo, $autor, $codigo, $editorial, $ubicacion, $isbn, $anio, $sinopsis, $genero, $total, $disponibles, $disponible, $estado, $nuevaPortada, $reiniciar, $id);
    $actualizar->execute();
    $conexion->commit();
    echo json_encode(['status' => 'success', 'message' => 'Libro actualizado correctamente.'], JSON_UNESCAPED_UNICODE);
} catch (RuntimeException $error) {
    $conexion->rollback();
    responderNoAutorizado($error->getMessage(), 422);
} catch (mysqli_sql_exception $error) {
    $conexion->rollback();
    responderNoAutorizado($error->getCode() === 1062 ? 'Ya existe otro libro con ese código.' : 'No fue posible actualizar el libro.', $error->getCode() === 1062 ? 409 : 500);
}
$conexion->close();
