<?php
// Diagnóstico rápido de libros
require_once __DIR__ . '/backend/config/conexion.php';
header('Content-Type: application/json');

$resultado = [
    'total_libros' => 0,
    'libros_disponibles' => 0,
    'ejemplo_libros' => [],
    'error' => null
];

try {
    // Contar total de libros
    $count = $conexion->query("SELECT COUNT(*) as total FROM libros");
    $resultado['total_libros'] = $count->fetch_assoc()['total'];

    // Contar libros disponibles
    $disponibles = $conexion->query("SELECT COUNT(*) as total FROM libros WHERE disponible = 1");
    $resultado['libros_disponibles'] = $disponibles->fetch_assoc()['total'];

    // Obtener ejemplos
    $ejemplos = $conexion->query("SELECT id, titulo, autor, disponible, estado FROM libros LIMIT 5");
    while ($libro = $ejemplos->fetch_assoc()) {
        $resultado['ejemplo_libros'][] = $libro;
    }

} catch (Exception $e) {
    $resultado['error'] = $e->getMessage();
}

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$conexion->close();
?>
