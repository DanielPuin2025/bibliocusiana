<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';

$q = trim((string) ($_GET['q'] ?? ''));
$categoria = trim((string) ($_GET['categoria'] ?? ''));
$materia = trim((string) ($_GET['materia'] ?? ''));
$genero = trim((string) ($_GET['genero'] ?? ''));

if ($conexion->query("SHOW TABLES LIKE 'libros'")->num_rows === 0) {
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'message' => 'La base de datos todavía no tiene el esquema de biblioteca. El administrador debe importar database/biblioteca.sql.'
    ], JSON_UNESCAPED_UNICODE);
    $conexion->close();
    exit;
}

// Compatibility repair for databases imported from the original schema.
$columnas = [];
$resultadoColumnas = $conexion->query('SHOW COLUMNS FROM libros');
if ($resultadoColumnas) {
    while ($columna = $resultadoColumnas->fetch_assoc()) $columnas[$columna['Field']] = true;
}
$columnasFaltantes = [
    // Estas dos columnas permiten que las bases creadas antes del catálogo de
    // portadas sigan funcionando sin tener que ejecutar una migración manual.
    'imagen' => "VARCHAR(255) NULL",
    'imagen_url' => "VARCHAR(500) NULL",
    'disponible' => "BOOLEAN NOT NULL DEFAULT TRUE",
    'materia' => "VARCHAR(80) NULL",
    'genero_literario' => "VARCHAR(80) NULL",
    'grado_escolar' => "VARCHAR(30) NULL",
    'edad_minima' => "TINYINT UNSIGNED NULL",
    'edad_maxima' => "TINYINT UNSIGNED NULL",
    'categoria_prestamo' => "VARCHAR(50) NOT NULL DEFAULT 'General'",
];
foreach ($columnasFaltantes as $nombre => $definicion) {
    if (!isset($columnas[$nombre])) $conexion->query("ALTER TABLE libros ADD COLUMN {$nombre} {$definicion}");
}
// Al actualizar una base previa, la disponibilidad inicial conserva el estado
// que ya tenía cada ejemplar.
if (!isset($columnas['disponible'])) $conexion->query("UPDATE libros SET disponible = (estado = 'Disponible')");

// Obtener todos los libros con información de préstamos activos
$sql = "SELECT
            l.*,
            CASE WHEN l.ejemplares_disponibles > 0 THEN 'Disponible' ELSE 'Prestado' END AS estado,
            (SELECT MIN(p.fecha_devolucion_esperada) FROM prestamos p WHERE p.libro_id = l.id AND p.estado = 'Activo') AS fecha_devolucion_esperada,
            NULL as prestado_a
        FROM libros l
        WHERE {WHERE}
        ORDER BY l.titulo ASC";

$filtros = ['categoria_prestamo' => $categoria, 'materia' => $materia, 'genero_literario' => $genero];

// Construye el WHERE con la búsqueda y todos los filtros, salvo el indicado en $omitir.
$construirFiltro = function (?string $omitir) use ($q, $filtros) {
    $condiciones = ['1=1'];
    $tipos = '';
    $valores = [];
    if ($q !== '') {
        $condiciones[] = '(l.titulo LIKE ? OR l.autor LIKE ? OR l.isbn LIKE ? OR l.editorial LIKE ? OR l.codigo LIKE ?)';
        $tipos .= 'sssss';
        array_push($valores, ...array_fill(0, 5, "%{$q}%"));
    }
    foreach ($filtros as $columna => $valor) {
        if ($columna === $omitir || $valor === '') continue;
        $condiciones[] = "l.{$columna} = ?";
        $tipos .= 's';
        $valores[] = $valor;
    }
    return [implode(' AND ', $condiciones), $tipos, $valores];
};

[$where, $tipos, $valores] = $construirFiltro(null);
$stmt = $conexion->prepare(str_replace('{WHERE}', $where, $sql));
if ($tipos !== '') $stmt->bind_param($tipos, ...$valores);
$stmt->execute();
$libros = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Cada lista solo ofrece valores que existen con los demás filtros ya elegidos.
$opciones = function (string $columna) use ($conexion, $construirFiltro) {
    [$where, $tipos, $valores] = $construirFiltro($columna);
    $stmt = $conexion->prepare("SELECT l.{$columna} AS valor, COUNT(*) AS total FROM libros l WHERE {$where} AND l.{$columna} IS NOT NULL AND l.{$columna} <> '' GROUP BY l.{$columna} ORDER BY l.{$columna} ASC");
    if ($tipos !== '') $stmt->bind_param($tipos, ...$valores);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
};
$categorias = $opciones('categoria_prestamo');
$materias = $opciones('materia');
$generos = $opciones('genero_literario');
// Las reseñas se devuelven junto al catálogo para no añadir otra solicitud HTTP.
$resenas = [];
$consultaResenas = $conexion->query("SELECT r.comentario, r.calificacion, r.fecha_creacion, e.nombre AS usuario_nombre, l.titulo FROM resenas r JOIN estudiantes e ON e.id = r.estudiante_id JOIN libros l ON l.id = r.libro_id ORDER BY r.fecha_creacion DESC LIMIT 12");
if ($consultaResenas) $resenas = $consultaResenas->fetch_all(MYSQLI_ASSOC);
echo json_encode([
    "status" => "success",
    "libros" => $libros,
    "categorias" => $categorias,
    "materias" => $materias,
    "generos" => $generos,
    "resenas" => $resenas
], JSON_UNESCAPED_UNICODE);

$conexion->close();
?>
