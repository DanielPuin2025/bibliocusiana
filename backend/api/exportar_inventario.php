<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') responderNoAutorizado('Método no permitido.', 405);
exigirAdministrador();
validarCsrf();

$resultado = $conexion->query('SELECT id, codigo, titulo, autor, editorial, anio_publicacion, isbn, ubicacion, imagen_url, genero_literario, materia, grado_escolar, categoria_prestamo, ejemplares_total, ejemplares_disponibles, estado, fecha_registro FROM libros ORDER BY titulo, codigo');
if (!$resultado) responderNoAutorizado('No fue posible generar el inventario.', 500);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="inventario_biblioteca_' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-store, no-cache, must-revalidate');
$salida = fopen('php://output', 'wb');
fwrite($salida, "\xEF\xBB\xBF");
$columnas = ['ID', 'Código', 'Título', 'Autor', 'Editorial', 'Año', 'ISBN', 'Ubicación', 'URL de portada', 'Género', 'Materia', 'Grado', 'Categoría de préstamo', 'Ejemplares totales', 'Ejemplares disponibles', 'Estado', 'Fecha de registro'];
fputcsv($salida, $columnas, ';', '"', '\\');
while ($fila = $resultado->fetch_assoc()) {
    $fila = array_map(static function ($valor) {
        $valor = (string) ($valor ?? '');
        return preg_match('/^[=+@\\-\\t\\r]/u', $valor) ? "'" . $valor : $valor;
    }, array_values($fila));
    fputcsv($salida, $fila, ';', '"', '\\');
}
fclose($salida);
$conexion->close();