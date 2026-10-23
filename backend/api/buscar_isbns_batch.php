<?php
/**
 * API para buscar ISBNs en lote usando Open Library
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';

exigirAdministrador();

header('Content-Type: text/plain; charset=UTF-8');
header('X-Accel-Buffering: no'); // Desactivar buffering
ob_implicit_flush(true);
set_time_limit(600); // 10 minutos

// Función para buscar ISBN en Open Library
function buscarISBN($titulo, $autor) {
    $query = $autor ? "$titulo $autor" : $titulo;
    $url = "https://openlibrary.org/search.json?q=" . urlencode($query) . "&limit=1";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Biblioteca-Bibliocusiana/1.0');

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);

        if (isset($data['docs'][0])) {
            $doc = $data['docs'][0];

            // Intentar obtener ISBN-13 primero, luego ISBN-10
            if (isset($doc['isbn']) && count($doc['isbn']) > 0) {
                // Preferir ISBN-13 (13 dígitos)
                foreach ($doc['isbn'] as $isbn) {
                    if (strlen($isbn) === 13) {
                        return $isbn;
                    }
                }
                // Si no hay ISBN-13, usar el primero disponible
                return $doc['isbn'][0];
            }
        }
    }

    return null;
}

// Obtener libros sin ISBN
$result = $conexion->query("
    SELECT id, titulo, autor
    FROM libros
    WHERE isbn IS NULL OR isbn = ''
    ORDER BY id ASC
");

if (!$result) {
    echo json_encode(['error' => 'Error al obtener libros']) . "\n";
    exit;
}

$total = $result->num_rows;
$procesados = 0;
$encontrados = 0;
$noEncontrados = 0;

while ($libro = $result->fetch_assoc()) {
    $procesados++;
    $progreso = round(($procesados / $total) * 100);

    // Buscar ISBN
    $isbn = buscarISBN($libro['titulo'], $libro['autor']);

    if ($isbn) {
        // Actualizar en la base de datos
        $stmt = $conexion->prepare("UPDATE libros SET isbn = ? WHERE id = ?");
        $stmt->bind_param('si', $isbn, $libro['id']);
        $stmt->execute();

        $encontrados++;
        $mensaje = "✅ {$libro['titulo']} → ISBN: $isbn";
        $isbn_encontrado = true;
    } else {
        $noEncontrados++;
        $mensaje = "⚠️ {$libro['titulo']} → No se encontró ISBN";
        $isbn_encontrado = false;
    }

    // Enviar progreso
    echo json_encode([
        'progress' => $progreso,
        'mensaje' => $mensaje,
        'isbn_encontrado' => $isbn_encontrado,
        'libro_id' => $libro['id']
    ]) . "\n";

    flush();

    // Pausa para no saturar la API
    usleep(250000); // 0.25 segundos
}

// Finalizar
echo json_encode([
    'finalizado' => true,
    'encontrados' => $encontrados,
    'no_encontrados' => $noEncontrados,
    'total' => $total
]) . "\n";

$conexion->close();
?>
