<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderNoAutorizado('Método no permitido.', 405);
exigirAdministrador();
validarCsrf();

function normalizarEncabezadoCsv(string $valor): string {
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
$contenido = str_replace(["\r\n", "\r"], "\n", $contenido);
$primeraLinea = strtok($contenido, "\r\n") ?: '';
$delimitador = substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',') ? ';' : ',';
$handle = fopen('php://temp', 'r+');
fwrite($handle, $contenido);
rewind($handle);
$primeraFila = fgetcsv($handle, 0, $delimitador);
$encabezados = array_map(static fn($valor): string => normalizarEncabezadoCsv((string) $valor), $primeraFila ?: []);
$indice = ['documento' => 0, 'nombre' => 1, 'rol' => 2, 'contrasena' => 3];
$aliases = [
    'documento' => ['documento', 'doc', 'cedula', 'identificacion', 'usuario_documento', 'numero_documento'],
    'nombre' => ['nombre', 'estudiante', 'usuario', 'nombre_completo'],
    'rol' => ['rol', 'tipo_usuario'],
    'contrasena' => ['contrasena', 'contrasena_inicial', 'contrasea', 'contrasea_inicial', 'password', 'clave'],
];
$esEncabezado = (bool) array_intersect($encabezados, array_merge(...array_values($aliases)));
if ($esEncabezado) {
    foreach ($aliases as $campo => $nombres) {
        foreach ($encabezados as $posicion => $encabezado) {
            $esColumnaContrasena = $campo === 'contrasena' && str_starts_with($encabezado, 'contrase');
            if (in_array($encabezado, $nombres, true) || $esColumnaContrasena) { $indice[$campo] = $posicion; break; }
        }
    }
} else {
    rewind($handle);
}

$rolForzado = trim((string) ($_POST['rol'] ?? ''));
if ($rolForzado !== '' && !in_array($rolForzado, ['Estudiante', 'Profesor'], true)) {
    responderNoAutorizado('El rol de importación no es válido.', 422);
}
$insertados = 0;
$omitidos = 0;
$errores = [];
$fila = $esEncabezado ? 1 : 0;
$buscar = $conexion->prepare('SELECT id FROM estudiantes WHERE doc = ? LIMIT 1');
$insertar = $conexion->prepare('INSERT INTO estudiantes (doc, nombre, contrasena, rol) VALUES (?, ?, ?, ?)');
while (($datos = fgetcsv($handle, 0, $delimitador)) !== false) {
    $fila++;
    if (count($datos) === 1 && trim((string) $datos[0]) === '') continue;
    $documento = trim((string) ($datos[$indice['documento']] ?? ''));
    $nombre = trim((string) ($datos[$indice['nombre']] ?? ''));
    $contrasena = trim((string) ($datos[$indice['contrasena']] ?? ''));
    $rol = $rolForzado ?: trim((string) ($datos[$indice['rol']] ?? 'Estudiante'));
    if ($rol === '') $rol = 'Estudiante';
    if ($documento === '' || $nombre === '' || $contrasena === '' || !in_array($rol, ['Estudiante', 'Profesor'], true)) {
        $errores[] = "Fila $fila: faltan documento, nombre o contraseña, o el rol no es válido.";
        continue;
    }
    $buscar->bind_param('s', $documento);
    $buscar->execute();
    if ($buscar->get_result()->fetch_assoc()) { $omitidos++; continue; }
    $hash = password_hash($contrasena, PASSWORD_DEFAULT);
    $insertar->bind_param('ssss', $documento, $nombre, $hash, $rol);
    if ($insertar->execute()) $insertados++;
    else $errores[] = "Fila $fila: no se pudo registrar el usuario.";
}
fclose($handle);
$conexion->close();
echo json_encode([
    'status' => 'success',
    'message' => "Usuarios registrados: $insertados. Duplicados omitidos: $omitidos. Errores: " . count($errores) . '.',
    'insertados' => $insertados,
    'omitidos' => $omitidos,
    'errores' => array_slice($errores, 0, 30),
], JSON_UNESCAPED_UNICODE);
?>
