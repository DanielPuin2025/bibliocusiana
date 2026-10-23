<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/portadas.php';

$columnas = [];
$resultadoColumnas = $conexion->query('SHOW COLUMNS FROM libros');
if ($resultadoColumnas) {
    while ($columna = $resultadoColumnas->fetch_assoc()) $columnas[$columna['Field']] = true;
}
if (!isset($columnas['imagen_url'])) $conexion->query('ALTER TABLE libros ADD COLUMN imagen_url VARCHAR(500) NULL');

$datos = json_decode(file_get_contents("php://input"), true);

$busqueda = isset($datos['busqueda']) ? $datos['busqueda'] : '';

// Buscar libros por título o autor
$sql = "SELECT
            l.*,
            p.fecha_devolucion_esperada,
            NULL as prestado_a
        FROM libros l
        LEFT JOIN prestamos p ON l.id = p.libro_id AND p.estado = 'Activo'
        LEFT JOIN estudiantes e ON p.estudiante_id = e.id
        WHERE l.titulo LIKE ? OR l.autor LIKE ?
        ORDER BY l.titulo ASC";

$busqueda_param = "%{$busqueda}%";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ss", $busqueda_param, $busqueda_param);
$stmt->execute();
$resultado = $stmt->get_result();
$libros = $resultado->fetch_all(MYSQLI_ASSOC);
$portadasConsultadas = 0;
foreach ($libros as &$libro) {
    if ($portadasConsultadas >= 3) break;
    if (!empty($libro['imagen_url'])) continue;
    $libro['imagen_url'] = guardarPortadaExterna($conexion, (int) $libro['id'], $libro['titulo'], $libro['autor'], (string) ($libro['isbn'] ?? ''));
    $portadasConsultadas++;
}
unset($libro);

echo json_encode([
    "status" => "success",
    "libros" => $libros,
    "total" => count($libros)
]);

$conexion->close();
?>
