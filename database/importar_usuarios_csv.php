<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../backend/config/conexion.php';

$archivos = [
    'USUARIO.ESTUDIANTE.csv' => ['nombre' => 0, 'documento' => 2, 'contrasena' => 3, 'rol' => 'Estudiante'],
    'USUARIO.DOCENTE.csv' => ['nombre' => 0, 'documento' => 1, 'contrasena' => 2, 'rol' => 'Profesor'],
    'estudiantes.csv' => ['nombre' => 0, 'documento' => 1, 'contrasena' => 2, 'rol' => 'Estudiante'],
];
$soloRevisar = in_array('--dry-run', $argv, true);
$documentosVistos = [];
$plan = [];
$resumen = [];
$errores = [];

foreach ($archivos as $nombreArchivo => $columnas) {
    $ruta = __DIR__ . DIRECTORY_SEPARATOR . $nombreArchivo;
    if (!is_file($ruta)) {
        fwrite(STDERR, "No se encontró {$nombreArchivo}.\n");
        exit(1);
    }

    $contenido = file_get_contents($ruta);
    if ($contenido === false) {
        fwrite(STDERR, "No se pudo leer {$nombreArchivo}.\n");
        exit(1);
    }
    if (!mb_check_encoding($contenido, 'UTF-8')) {
        $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
    }
    $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
    $contenido = str_replace(["\r\n", "\r"], "\n", $contenido);
    $flujo = fopen('php://temp', 'r+');
    fwrite($flujo, $contenido);
    rewind($flujo);
    $encabezado = fgetcsv($flujo, 0, ';', '"', '\\');
    if (!$encabezado) {
        $errores[] = "{$nombreArchivo}: no tiene encabezados.";
        fclose($flujo);
        continue;
    }

    $resumen[$nombreArchivo] = ['filas' => 0, 'nuevos' => 0, 'existentes_o_duplicados' => 0, 'invalidos' => 0];
    while (($fila = fgetcsv($flujo, 0, ';', '"', '\\')) !== false) {
        $resumen[$nombreArchivo]['filas']++;
        if (count($fila) === 1 && trim((string) $fila[0]) === '') continue;

        $documento = trim((string) ($fila[$columnas['documento']] ?? ''));
        $nombre = trim((string) ($fila[$columnas['nombre']] ?? ''));
        $contrasena = trim((string) ($fila[$columnas['contrasena']] ?? ''));
        if (!preg_match('/^\d{5,20}$/', $documento) || $nombre === '' || mb_strlen($nombre, 'UTF-8') > 100 || $contrasena === '') {
            $resumen[$nombreArchivo]['invalidos']++;
            $errores[] = "{$nombreArchivo}, fila {$resumen[$nombreArchivo]['filas']}: documento, nombre o contraseña inválidos.";
            continue;
        }
        if (isset($documentosVistos[$documento])) {
            $resumen[$nombreArchivo]['existentes_o_duplicados']++;
            continue;
        }
        $documentosVistos[$documento] = true;
        $plan[] = [
            'archivo' => $nombreArchivo,
            'doc' => $documento,
            'nombre' => $nombre,
            'contrasena' => $contrasena,
            'rol' => $columnas['rol'],
        ];
    }
    fclose($flujo);
}

$buscar = $conexion->prepare('SELECT id FROM estudiantes WHERE doc = ? LIMIT 1');
$insertar = $conexion->prepare('INSERT INTO estudiantes (doc, nombre, contrasena, rol) VALUES (?, ?, ?, ?)');
$conexion->begin_transaction();
try {
    foreach ($plan as $usuario) {
        $buscar->bind_param('s', $usuario['doc']);
        $buscar->execute();
        if ($buscar->get_result()->fetch_assoc()) {
            $resumen[$usuario['archivo']]['existentes_o_duplicados']++;
            continue;
        }
        $resumen[$usuario['archivo']]['nuevos']++;
        if ($soloRevisar) continue;

        $hash = password_hash($usuario['contrasena'], PASSWORD_DEFAULT);
        $insertar->bind_param('ssss', $usuario['doc'], $usuario['nombre'], $hash, $usuario['rol']);
        if (!$insertar->execute()) throw new RuntimeException('No se pudo registrar el documento ' . $usuario['doc'] . '.');
    }

    if ($soloRevisar) $conexion->rollback();
    else $conexion->commit();
} catch (Throwable $error) {
    $conexion->rollback();
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}

$totalesRol = $conexion->query('SELECT rol, COUNT(*) AS total FROM estudiantes GROUP BY rol ORDER BY rol')->fetch_all(MYSQLI_ASSOC);
echo json_encode([
    'modo' => $soloRevisar ? 'dry-run, no se guardaron cambios' : 'importación completada',
    'resumen_archivos' => $resumen,
    'errores' => $errores,
    'usuarios_por_rol' => $totalesRol,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
$conexion->close();