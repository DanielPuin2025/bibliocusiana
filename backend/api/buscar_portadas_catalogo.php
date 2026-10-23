<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/portadas.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderNoAutorizado('Método no permitido.', 405);
usuarioAutenticado();
validarCsrf();

$datos = json_decode(file_get_contents('php://input'), true);
$ids = $datos['ids'] ?? null;
if (!is_array($ids) || count($ids) > 12) responderNoAutorizado('La lista de libros no es válida.', 422);

set_time_limit(90);
asegurarColumnaPortadaBuscada($conexion);
$buscar = $conexion->prepare('SELECT id, titulo, autor, isbn, imagen, imagen_url, (portada_buscada_en IS NOT NULL AND portada_buscada_en > DATE_SUB(NOW(), INTERVAL ' . DIAS_REINTENTO_PORTADA . ' DAY)) AS reciente FROM libros WHERE id = ? LIMIT 1');
if (!$buscar) responderNoAutorizado('No se pudo preparar la búsqueda de portadas.', 500);

$portadas = [];
foreach ($ids as $valorId) {
    $id = filter_var($valorId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) continue;

    $buscar->bind_param('i', $id);
    $buscar->execute();
    $libro = $buscar->get_result()->fetch_assoc();
    if (!$libro) continue;

    $imagenUrl = (string) ($libro['imagen_url'] ?? '');
    $sinPortada = $imagenUrl === '' || $imagenUrl === IMAGEN_PORTADA_PREDETERMINADA;
    if ($sinPortada && !(int) $libro['reciente'] && empty($libro['imagen'])) {
        $imagenUrl = guardarPortadaExterna($conexion, (int) $id, $libro['titulo'], $libro['autor'], (string) ($libro['isbn'] ?? ''));
    } elseif ($sinPortada) {
        $imagenUrl = IMAGEN_PORTADA_PREDETERMINADA;
    }

    $portadas[] = ['id' => (int) $id, 'imagen' => $libro['imagen'], 'imagen_url' => $imagenUrl];
}

$buscar->close();
$conexion->close();
echo json_encode(['status' => 'success', 'portadas' => $portadas], JSON_UNESCAPED_UNICODE);
