<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderNoAutorizado('Método no permitido.', 405);
exigirAdministrador();
validarCsrf();

function normalizarEncabezadoLibroCsv(string $valor): string {
    $valor = mb_strtolower(trim($valor), 'UTF-8');
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);
    return trim((string) preg_replace('/[^a-z0-9]+/', '_', $ascii), '_');
}

if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    responderNoAutorizado('No se recibió un archivo CSV válido.', 422);
}
if (strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION)) !== 'csv') {
    responderNoAutorizado('Solo se permiten archivos CSV.', 422);
}

$contenido = file_get_contents($_FILES['archivo']['tmp_name']);
if ($contenido === false) responderNoAutorizado('No se pudo leer el archivo.', 422);
if (!mb_check_encoding($contenido, 'UTF-8')) $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
$contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
$primeraLinea = strtok($contenido, "\r\n") ?: '';
$delimitador = substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',') ? ';' : ',';
$handle = fopen('php://temp', 'r+');
fwrite($handle, $contenido);
rewind($handle);
$primeraFila = fgetcsv($handle, 0, $delimitador);
$encabezados = array_map(static fn($valor): string => normalizarEncabezadoLibroCsv((string) $valor), $primeraFila ?: []);
$campos = [
    'titulo' => ['titulo', 'libro', 'articulo', 'title'],
    'autor' => ['autor', 'author'],
    'codigo' => ['codigo', 'codigo_inventario', 'item', 'referencia'],
    'cantidad' => ['cantidad', 'ejemplares', 'stock'],
    'editorial' => ['editorial', 'publisher'],
    'isbn' => ['isbn'],
    'anio' => ['ano', 'anio', 'ano_publicacion', 'year'],
    'ubicacion' => ['ubicacion', 'localizacion'],
    'sinopsis' => ['sinopsis', 'descripcion', 'description'],
];
$indices = [];
foreach ($campos as $campo => $nombres) {
    foreach ($encabezados as $posicion => $encabezado) {
        if (in_array($encabezado, $nombres, true)) { $indices[$campo] = $posicion; break; }
    }
}
$esEncabezado = isset($indices['titulo']);
if (!$esEncabezado) {
    $indices = ['titulo' => 0, 'autor' => 1, 'isbn' => 2, 'editorial' => 3, 'anio' => 4, 'sinopsis' => 5];
    rewind($handle);
}
if (!isset($indices['autor']) && $esEncabezado) {
    responderNoAutorizado('El CSV debe incluir columnas para el título/libro y el autor.', 422);
}

$buscar = $conexion->prepare('SELECT id FROM libros WHERE (codigo = ? AND ? <> "") OR (titulo = ? AND autor = ?) LIMIT 1');
$insertar = $conexion->prepare("INSERT INTO libros (titulo, codigo, autor, isbn, editorial, anio_publicacion, ubicacion, sinopsis, estado, disponible, ejemplares_total, ejemplares_disponibles) VALUES (?, NULLIF(?, ''), ?, NULLIF(?, ''), NULLIF(?, ''), ?, NULLIF(?, ''), NULLIF(?, ''), 'Disponible', 1, ?, ?)");
$insertados = 0;
$omitidos = 0;
$errores = [];
$importados = [];
$fila = $esEncabezado ? 1 : 0;
while (($datos = fgetcsv($handle, 0, $delimitador)) !== false) {
    $fila++;
    if (count($datos) === 1 && trim((string) $datos[0]) === '') continue;
    $valor = static fn(string $campo): string => trim((string) ($datos[$indices[$campo] ?? -1] ?? ''));
    $titulo = $valor('titulo');
    $autor = $valor('autor');
    $codigo = $valor('codigo');
    $cantidadTexto = $valor('cantidad');
    $cantidad = $cantidadTexto === '' ? 1 : filter_var($cantidadTexto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9999]]);
    if ($titulo === '' || $autor === '' || $cantidad === false) {
        $errores[] = "Fila $fila: título, autor y cantidad válida son obligatorios.";
        continue;
    }
    $isbn = $valor('isbn');
    $editorial = $valor('editorial');
    $anioTexto = $valor('anio');
    $anio = preg_match('/^\d{4}$/', $anioTexto) ? (int) $anioTexto : null;
    $ubicacion = $valor('ubicacion');
    $sinopsis = $valor('sinopsis');
    $buscar->bind_param('ssss', $codigo, $codigo, $titulo, $autor);
    $buscar->execute();
    if ($buscar->get_result()->fetch_assoc()) { $omitidos++; continue; }
    $insertar->bind_param('sssssissii', $titulo, $codigo, $autor, $isbn, $editorial, $anio, $ubicacion, $sinopsis, $cantidad, $cantidad);
    if (!$insertar->execute()) {
        $errores[] = "Fila $fila: no se pudo registrar el libro.";
        continue;
    }
    $insertados++;
    $importados[] = ['id' => $insertar->insert_id, 'titulo' => $titulo, 'autor' => $autor];
}
fclose($handle);
$conexion->close();
echo json_encode([
    'status' => 'success',
    'message' => "Libros registrados: $insertados. Duplicados omitidos: $omitidos. Errores: " . count($errores) . '.',
    'insertados' => $insertados,
    'omitidos' => $omitidos,
    'libros' => $importados,
    'errores' => array_slice($errores, 0, 30),
], JSON_UNESCAPED_UNICODE);
?>
