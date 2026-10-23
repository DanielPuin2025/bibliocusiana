<?php
// Uso (consola): php database\buscar_portadas_masivo.php [--todos]
// Busca en internet la portada de los libros que no tienen imagen y guarda solo la URL.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo desde consola.'); }
require __DIR__ . '/../backend/config/conexion.php';
require __DIR__ . '/../backend/config/portadas.php';
$conexion->set_charset('utf8mb4');
set_time_limit(0);
asegurarColumnaPortadaBuscada($conexion);

$reintentar = in_array('--todos', $argv, true) ? '' : ' AND portada_buscada_en IS NULL';
$lista = $conexion->query("SELECT id, titulo, autor, isbn FROM libros WHERE (imagen IS NULL OR imagen = '') AND (imagen_url IS NULL OR imagen_url NOT LIKE 'http%')$reintentar ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$total = count($lista);
$ok = 0;
foreach ($lista as $i => $libro) {
    $url = guardarPortadaExterna($conexion, (int) $libro['id'], $libro['titulo'], $libro['autor'], (string) ($libro['isbn'] ?? ''));
    if ($url !== IMAGEN_PORTADA_PREDETERMINADA) $ok++;
    if (($i + 1) % 25 === 0 || $i + 1 === $total) echo ($i + 1) . "/$total procesados, con portada: $ok\n";
    usleep(200000);
}
echo "Listo. Portadas encontradas: $ok de $total.\n";
