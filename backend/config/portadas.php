<?php
declare(strict_types=1);

const IMAGEN_PORTADA_PREDETERMINADA = 'assets/portada-no-disponible.svg';
// Si una búsqueda no encuentra portada, no se repite hasta pasado este tiempo.
const DIAS_REINTENTO_PORTADA = 7;

/**
 * Busca la portada de un libro en internet (Open Library y Google Books).
 * Devuelve ['url' => ?string, 'isbn' => ?string]. No guarda nada en la base de datos.
 */
function buscarPortadaCompleta(string $titulo, string $autor = '', string $isbn = ''): array {
    $resultado = ['url' => null, 'isbn' => null];
    if (!function_exists('curl_init')) return $resultado;

    $tituloLimpio = limpiarTituloParaBusqueda($titulo);
    $autorLimpio = limpiarAutorParaBusqueda($autor);
    $isbnLimpio = normalizarIsbn($isbn);

    // 1. Por ISBN: es la coincidencia más fiable.
    if ($isbnLimpio !== '') {
        $url = "https://covers.openlibrary.org/b/isbn/{$isbnLimpio}-M.jpg?default=false";
        if (existeImagenRemota($url)) return ['url' => $url, 'isbn' => $isbnLimpio];
    }

    // 2. Open Library por título y autor, y después solo por título.
    $consultas = [];
    if ($autorLimpio !== '') $consultas[] = ['title' => $tituloLimpio, 'author' => $autorLimpio];
    $consultas[] = ['title' => $tituloLimpio];
    foreach ($consultas as $consulta) {
        $datos = solicitarJsonPortada('https://openlibrary.org/search.json?' . http_build_query($consulta + [
            'fields' => 'cover_i,isbn,title',
            'limit' => 5,
        ]));
        foreach ($datos['docs'] ?? [] as $doc) {
            if (!empty($doc['cover_i']) && ctype_digit((string) $doc['cover_i']) && titulosSimilares($tituloLimpio, (string) ($doc['title'] ?? ''))) {
                return ['url' => 'https://covers.openlibrary.org/b/id/' . (int) $doc['cover_i'] . '-M.jpg', 'isbn' => primerIsbn($doc['isbn'] ?? [])];
            }
        }
    }

    // 3. Google Books (puede agotar su cuota diaria sin clave; en ese caso se omite).
    $consultasGoogle = [];
    if ($isbnLimpio !== '') $consultasGoogle[] = 'isbn:' . $isbnLimpio;
    $consultasGoogle[] = 'intitle:"' . $tituloLimpio . '"' . ($autorLimpio !== '' ? ' inauthor:"' . $autorLimpio . '"' : '');
    $consultasGoogle[] = 'intitle:"' . $tituloLimpio . '"';
    foreach (array_unique($consultasGoogle) as $q) {
        $parametros = ['q' => $q, 'maxResults' => 5, 'printType' => 'books'];
        $clave = getenv('GOOGLE_BOOKS_API_KEY') ?: '';
        if ($clave !== '') $parametros['key'] = $clave;
        $datos = solicitarJsonPortada('https://www.googleapis.com/books/v1/volumes?' . http_build_query($parametros));
        foreach ($datos['items'] ?? [] as $item) {
            $info = $item['volumeInfo'] ?? [];
            $miniatura = $info['imageLinks']['thumbnail'] ?? $info['imageLinks']['smallThumbnail'] ?? null;
            if (!is_string($miniatura) || !filter_var($miniatura, FILTER_VALIDATE_URL)) continue;
            if (!str_starts_with($q, 'isbn:') && !titulosSimilares($tituloLimpio, (string) ($info['title'] ?? ''))) continue;
            $miniatura = preg_replace('#^http://#', 'https://', $miniatura);
            $isbnGoogle = null;
            foreach ($info['industryIdentifiers'] ?? [] as $identificador) {
                if (($identificador['type'] ?? '') === 'ISBN_13') $isbnGoogle = $identificador['identifier'];
            }
            return ['url' => mb_substr($miniatura, 0, 500, 'UTF-8'), 'isbn' => $isbnGoogle];
        }
    }
    return $resultado;
}

function buscarPortadaExterna(string $titulo, string $autor, string $isbn = ''): ?string {
    return buscarPortadaCompleta($titulo, $autor, $isbn)['url'];
}

function normalizarIsbn(string $isbn): string {
    $limpio = strtoupper(preg_replace('/[^0-9Xx]/', '', $isbn) ?: '');
    return in_array(strlen($limpio), [10, 13], true) ? $limpio : '';
}

function primerIsbn(array $isbns): ?string {
    foreach ($isbns as $isbn) if (strlen((string) $isbn) === 13) return (string) $isbn;
    return isset($isbns[0]) ? mb_substr((string) $isbns[0], 0, 20) : null;
}

function limpiarTituloParaBusqueda(string $titulo): string {
    $titulo = preg_replace('/\([^)]*\)/u', ' ', $titulo) ?? $titulo;
    $titulo = preg_replace('/\s+\d{1,2}$/u', '', trim($titulo)) ?? $titulo;
    return trim(preg_replace('/\s+/u', ' ', mb_strtolower($titulo, 'UTF-8')) ?? $titulo);
}

function limpiarAutorParaBusqueda(string $autor): string {
    $autor = trim(mb_strtolower($autor, 'UTF-8'));
    if ($autor === '' || preg_match('/^(an[oó]nimo|varios|varios autores|n\/a|sin autor|s\/a)$/u', $autor)) return '';
    return $autor;
}

function aSinAcentos(string $texto): string {
    $texto = mb_strtolower($texto, 'UTF-8');
    $sin = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
    return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $sin !== false ? $sin : $texto));
}

// Evita aceptar portadas de libros distintos: debe coincidir al menos la mitad de las palabras relevantes.
function titulosSimilares(string $buscado, string $encontrado): bool {
    $palabras = static fn(string $t): array => array_values(array_filter(explode(' ', aSinAcentos($t)), static fn($p) => strlen($p) > 2));
    $a = $palabras($buscado);
    $b = $palabras($encontrado);
    if (!$a || !$b) return false;
    $comunes = count(array_intersect($a, $b));
    return $comunes / count($a) >= 0.5;
}

function existeImagenRemota(string $url): bool {
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_NOBODY => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_USERAGENT => 'BibliotecaEscolar/1.0 (catalogo escolar)',
    ]);
    curl_exec($curl);
    $estado = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return $estado === 200;
}

function solicitarJsonPortada(string $url): array {
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'BibliotecaEscolar/1.0 (catalogo escolar)',
    ]);
    $respuesta = curl_exec($curl);
    $estado = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if (!is_string($respuesta) || $estado < 200 || $estado >= 300) return [];
    $datos = json_decode($respuesta, true);
    return is_array($datos) ? $datos : [];
}

/** Crea la columna de control de reintentos en bases creadas antes de este cambio. */
function asegurarColumnaPortadaBuscada(mysqli $conexion): void {
    static $verificada = false;
    if ($verificada) return;
    $existe = $conexion->query("SHOW COLUMNS FROM libros LIKE 'portada_buscada_en'");
    if ($existe && $existe->num_rows === 0) $conexion->query('ALTER TABLE libros ADD COLUMN portada_buscada_en DATETIME NULL');
    $verificada = true;
}

/**
 * Busca y guarda la portada. Si no se encuentra, imagen_url queda en NULL y se anota la fecha
 * del intento para no repetir la búsqueda en cada visita. Devuelve la URL o la imagen por defecto.
 */
function guardarPortadaExterna(mysqli $conexion, int $libroId, string $titulo, string $autor, string $isbn = ''): string {
    asegurarColumnaPortadaBuscada($conexion);
    $encontrada = buscarPortadaCompleta($titulo, $autor, $isbn);
    $url = $encontrada['url'];
    $isbnNuevo = normalizarIsbn((string) $encontrada['isbn']) ?: null;
    $stmt = $conexion->prepare('UPDATE libros SET imagen_url = ?, isbn = IF(isbn IS NULL OR isbn = \'\', ?, isbn), portada_buscada_en = NOW() WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('ssi', $url, $isbnNuevo, $libroId);
        $stmt->execute();
    }
    return $url ?? IMAGEN_PORTADA_PREDETERMINADA;
}
