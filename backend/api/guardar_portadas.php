<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/portadas.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderNoAutorizado('Método no permitido.', 405);
exigirAdministrador();
validarCsrf();
set_time_limit(600);
asegurarColumnaPortadaBuscada($conexion);
$datos = json_decode(file_get_contents('php://input'), true);
$portadas = $datos['portadas'] ?? null;
$libros = $datos['libros'] ?? null;
if ((!is_array($portadas) && !is_array($libros)) || (is_array($portadas) && count($portadas) > 250) || (is_array($libros) && count($libros) > 250)) {
    responderNoAutorizado('La lista de portadas no es válida.', 422);
}

// La consulta se realiza aquí para que Google Books, Open Library y Wikimedia
// funcionen también cuando la importación se hace desde un navegador con CORS.
if (is_array($libros)) {
    $buscarLibro = $conexion->prepare('SELECT id, titulo, autor, isbn FROM libros WHERE id = ?');
    if (!$buscarLibro) responderNoAutorizado('No se pudo preparar la búsqueda de portadas.', 500);
    $guardadas = 0;
    foreach ($libros as $libro) {
        $id = filter_var(is_array($libro) ? ($libro['id'] ?? null) : $libro, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) continue;
        $buscarLibro->bind_param('i', $id);
        $buscarLibro->execute();
        $registro = $buscarLibro->get_result()->fetch_assoc();
        if (!$registro) continue;
        $portada = guardarPortadaExterna($conexion, (int) $registro['id'], $registro['titulo'], $registro['autor'], (string) ($registro['isbn'] ?? ''));
        if ($portada !== IMAGEN_PORTADA_PREDETERMINADA) $guardadas++;
    }
    $buscarLibro->close();
    $conexion->close();
    echo json_encode(['status' => 'success', 'guardadas' => $guardadas], JSON_UNESCAPED_UNICODE);
    exit;
}

$actualizar = $conexion->prepare('UPDATE libros SET imagen = ? WHERE id = ?');
$guardadas = 0;
foreach ($portadas as $portada) {
    $id = filter_var($portada['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $imagen = (string) ($portada['imagen'] ?? '');
    $partes = parse_url($imagen);
    if (!$id || ($partes['scheme'] ?? '') !== 'https' || ($partes['host'] ?? '') !== 'covers.openlibrary.org'
        || !preg_match('#^/b/id/\d+-M\.jpg$#', $partes['path'] ?? '')) {
        continue;
    }
    $actualizar->bind_param('si', $imagen, $id);
    if ($actualizar->execute() && $actualizar->affected_rows > 0) $guardadas++;
}
$conexion->close();
echo json_encode(['status' => 'success', 'guardadas' => $guardadas], JSON_UNESCAPED_UNICODE);
