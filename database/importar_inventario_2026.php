<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/config/conexion.php';

$archivo = __DIR__ . '/INVENTARIO DE BIBLIOTECA 2026.xlsx - inventario nuevo .csv';
if (!is_file($archivo)) {
    fwrite(STDERR, "No se encontró el CSV de inventario.\n");
    exit(1);
}

$normalizar = static function (string $texto): string {
    $texto = trim(mb_strtolower($texto, 'UTF-8'));
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    return preg_replace('/[^a-z0-9]+/', ' ', (string) $ascii) ?? '';
};

$clasificarGenero = static function (string $titulo) use ($normalizar): string {
    $titulo = $normalizar($titulo);
    $reglas = [
        'Ciencia Ficción' => '/\b(1984|ciencia ficcion|magica|universo|espacio|robot|extraterrestre|paranormal)\b/',
        'Fantasía' => '/\b(harry potter|principito|fantasia|dragones?)\b/',
    ];
    foreach ($reglas as $genero => $patron) {
        if (preg_match($patron, $titulo)) return $genero;
    }
    if (preg_match('/\b(poema|poesia|versos|recital|cancion)\b/', $titulo)) return 'Poesía';
    if (preg_match('/\b(teatro|drama|dramatic)\b/', $titulo)) return 'Drama';
    if (preg_match('/\b(ensayo|filosofia|pensamiento|politica|motivacion|educacion para|balance de|critico)\b/', $titulo)) return 'Ensayo';
    if (preg_match('/\b(don quijote|codigo da vinci|metamorfosis|cronica de una muerte|amor en los tiempos|cien anos de soledad|cuento|relato|novela|aventura|leyenda|mito|historia|cronica|fabula)\b/', $titulo)) return 'Narrativa';
    return 'Consulta';
};

$handle = fopen($archivo, 'rb');
if (!$handle) {
    fwrite(STDERR, "No se pudo abrir el CSV.\n");
    exit(1);
}

$encabezados = fgetcsv($handle, 0, ',', '"', '\\');
if (!$encabezados) {
    fwrite(STDERR, "El CSV está vacío.\n");
    exit(1);
}
$encabezados = array_map(static fn($valor) => mb_strtolower(trim((string) $valor), 'UTF-8'), $encabezados);
$indices = [];
foreach (['libro' => 'libro', 'cantidad' => 'cantidad', 'estado' => 'estado bueno o malo (si/no)', 'autor' => 'autor', 'editorial' => 'editorial'] as $campo => $encabezado) {
    $posicion = array_search($encabezado, $encabezados, true);
    if ($posicion === false) {
        fwrite(STDERR, "Falta la columna requerida: {$encabezado}.\n");
        exit(1);
    }
    $indices[$campo] = $posicion;
}

$buscar = $conexion->prepare('SELECT id, titulo, autor, ejemplares_total, ejemplares_disponibles FROM libros WHERE LOWER(titulo) = LOWER(?) ORDER BY id LIMIT 1');
$insertar = $conexion->prepare("INSERT INTO libros (titulo, autor, editorial, genero_literario, categoria_prestamo, ejemplares_total, ejemplares_disponibles, estado, disponible) VALUES (?, ?, NULLIF(?, ''), ?, 'General', ?, ?, 'Disponible', 1)");
$actualizar = $conexion->prepare("UPDATE libros SET autor = ?, editorial = NULLIF(?, ''), genero_literario = ?, ejemplares_total = ?, ejemplares_disponibles = ?, disponible = (? > 0), estado = IF(? > 0, 'Disponible', 'Prestado') WHERE id = ?");

$conexion->begin_transaction();
$insertados = 0;
$actualizados = 0;
$omitidos = 0;
$errores = [];
$cantidadesPorDefecto = 0;
$filaNumero = 1;
try {
    while (($fila = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
        $filaNumero++;
        if (count($fila) < count($encabezados)) $fila = array_pad($fila, count($encabezados), '');
        $titulo = trim((string) ($fila[$indices['libro']] ?? ''));
        if ($titulo === '') { $omitidos++; continue; }
        $cantidadTexto = trim((string) ($fila[$indices['cantidad']] ?? ''));
        $cantidad = $cantidadTexto === '' ? 1 : filter_var($cantidadTexto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9999]]);
        if ($cantidadTexto === '') $cantidadesPorDefecto++;
        if ($cantidad === false) {
            $errores[] = "Fila {$filaNumero}: cantidad inválida.";
            continue;
        }
        $autor = trim((string) ($fila[$indices['autor']] ?? ''));
        if ($autor === '') $autor = 'Anónimo';
        $editorial = trim((string) ($fila[$indices['editorial']] ?? ''));
        $estadoBueno = mb_strtoupper(trim((string) ($fila[$indices['estado']] ?? 'SI')), 'UTF-8');
        $genero = $clasificarGenero($titulo);

        $buscar->bind_param('s', $titulo);
        $buscar->execute();
        $existente = $buscar->get_result()->fetch_assoc();
        if (!$existente) {
            $disponibles = $estadoBueno === 'SI' ? $cantidad : 0;
            $insertar->bind_param('ssssii', $titulo, $autor, $editorial, $genero, $cantidad, $disponibles);
            if (!$insertar->execute()) throw new RuntimeException("Fila {$filaNumero}: no se pudo insertar {$titulo}.");
            $insertados++;
            continue;
        }

        $id = (int) $existente['id'];
        $activos = $conexion->prepare("SELECT COUNT(*) total FROM prestamos WHERE libro_id = ? AND estado = 'Activo'");
        $activos->bind_param('i', $id);
        $activos->execute();
        $prestados = (int) $activos->get_result()->fetch_assoc()['total'];
        $total = max($cantidad, $prestados);
        $disponibles = $estadoBueno === 'SI' ? max(0, $total - $prestados) : 0;
        $actualizar->bind_param('sssiiiii', $autor, $editorial, $genero, $total, $disponibles, $disponibles, $disponibles, $id);
        if (!$actualizar->execute()) throw new RuntimeException("Fila {$filaNumero}: no se pudo actualizar {$titulo}.");
        $actualizados++;
    }
    $conexion->commit();
} catch (Throwable $error) {
    $conexion->rollback();
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
} finally {
    fclose($handle);
}

$generosClasicos = [
    'Don Quijote de la Mancha' => 'Narrativa',
    '1984' => 'Ciencia Ficción',
    'Crónica de una muerte anunciada' => 'Narrativa',
    'El amor en los tiempos del cólera' => 'Narrativa',
    'La metamorfosis' => 'Narrativa',
    'El código Da Vinci' => 'Narrativa',
    'Harry Potter y la piedra filosofal' => 'Fantasía',
];
$actualizarGenero = $conexion->prepare('UPDATE libros SET genero_literario = ? WHERE LOWER(titulo) = LOWER(?) AND genero_literario IS NULL');
foreach ($generosClasicos as $titulo => $genero) {
    $actualizarGenero->bind_param('ss', $genero, $titulo);
    $actualizarGenero->execute();
}
$resumen = $conexion->query('SELECT COALESCE(genero_literario, "Sin clasificar") genero_literario, COUNT(*) libros, SUM(ejemplares_total) ejemplares FROM libros GROUP BY genero_literario ORDER BY genero_literario')->fetch_all(MYSQLI_ASSOC);
echo json_encode(['filas_insertadas' => $insertados, 'libros_actualizados' => $actualizados, 'filas_omitidas' => $omitidos, 'cantidades_asignadas_por_defecto' => $cantidadesPorDefecto, 'errores' => $errores, 'resumen_generos' => $resumen], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
$conexion->close();
