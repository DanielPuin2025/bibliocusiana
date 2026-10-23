<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/portadas.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

exigirAdministrador();
validarCsrf();

$titulo = trim((string) ($_POST['titulo'] ?? ''));
$autor = trim((string) ($_POST['autor'] ?? ''));
$codigo = trim((string) ($_POST['codigo'] ?? ''));
$editorial = trim((string) ($_POST['editorial'] ?? ''));
$ubicacion = trim((string) ($_POST['ubicacion'] ?? ''));
$genero = trim((string) ($_POST['genero_literario'] ?? ''));
$estado = (string) ($_POST['estado'] ?? 'Disponible');
$cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9999]]);

if ($titulo === '' || $autor === '' || $codigo === '' || $editorial === '' || $ubicacion === '' || $genero === '' || $cantidad === false || !in_array($estado, ['Disponible', 'Prestado'], true)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Completa todos los campos con valores válidos.']);
    exit;
}

$rutaImagen = null;
$destino = null;
$archivo = $_FILES['imagen'] ?? null;
if ($archivo && $archivo['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > 5 * 1024 * 1024) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'La imagen es inválida o supera 5 MB.']);
        exit;
    }
    $tipoReal = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
    $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($extensiones[$tipoReal])) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Solo se permiten imágenes JPG o PNG.']);
        exit;
    }
    $directorioImagenes = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'imagenes';
    if (!is_dir($directorioImagenes) && !mkdir($directorioImagenes, 0755, true)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'No se pudo preparar la carpeta de imágenes.']);
        exit;
    }
    $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extensiones[$tipoReal];
    $destino = $directorioImagenes . DIRECTORY_SEPARATOR . $nombreArchivo;
    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar la imagen.']);
        exit;
    }
    $rutaImagen = 'imagenes/' . $nombreArchivo;
}
$imagenUrl = buscarPortadaExterna($titulo, $autor) ?? IMAGEN_PORTADA_PREDETERMINADA;
$disponibles = $estado === 'Disponible' ? $cantidad : 0;
$disponible = $disponibles > 0 ? 1 : 0;
$stmt = $conexion->prepare('INSERT INTO libros (titulo, codigo, autor, editorial, ubicacion, genero_literario, imagen, imagen_url, disponible, estado, ejemplares_total, ejemplares_disponibles) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
if (!$stmt) {
    if ($destino) @unlink($destino);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'No se pudo preparar el registro del libro.']);
    exit;
}
$tipos = 'ssss' . 'ssss' . 'isii';
$stmt->bind_param($tipos, $titulo, $codigo, $autor, $editorial, $ubicacion, $genero, $rutaImagen, $imagenUrl, $disponible, $estado, $cantidad, $disponibles);

if (!$stmt->execute()) {
    if ($destino) @unlink($destino);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar el libro en la base de datos.']);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'Libro registrado correctamente.', 'id' => $stmt->insert_id], JSON_UNESCAPED_UNICODE);
$stmt->close();
$conexion->close();
