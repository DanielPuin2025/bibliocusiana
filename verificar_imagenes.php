<?php
/**
 * Verificación rápida de imágenes de libros
 * Acceder: http://localhost/biblioteca/verificar_imagenes.php
 */

require_once __DIR__ . '/backend/config/conexion.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de Imágenes - Libros</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #34495e; color: white; }
        .img-test { max-width: 80px; max-height: 100px; border: 1px solid #ccc; }
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #2c3e50; color: white; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .fix-box { background: #fff3cd; border: 2px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Verificación de Imágenes de Libros</h1>

    <?php
    // Obtener todos los libros
    $result = $conexion->query("SELECT id, titulo, autor, imagen, imagen_url FROM libros ORDER BY id ASC LIMIT 20");

    if ($result && $result->num_rows > 0) {
        echo "<h2>📚 Estado actual de las imágenes:</h2>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Título</th><th>Campo 'imagen'</th><th>Campo 'imagen_url'</th><th>Prueba de carga</th><th>Estado</th></tr>";

        $problemasDetectados = 0;
        $rutasCorrectas = 0;

        while ($libro = $result->fetch_assoc()) {
            $imagen = $libro['imagen'];
            $imagenUrl = $libro['imagen_url'];

            // Verificar si la ruta está correcta
            $tieneProblema = false;
            if ($imagen && strpos($imagen, 'IMAGES-LIBROS/') !== 0 && strpos($imagen, 'https://') !== 0) {
                $tieneProblema = true;
                $problemasDetectados++;
            } else if ($imagen) {
                $rutasCorrectas++;
            }

            $claseEstado = $tieneProblema ? 'error' : 'success';
            $textoEstado = $tieneProblema ? '❌ Ruta incorrecta' : '✅ OK';

            // Construir la ruta completa para probar
            $rutaPrueba = '';
            if ($imagen) {
                if (strpos($imagen, 'IMAGES-LIBROS/') === 0) {
                    $rutaPrueba = 'frontend/assets/' . $imagen;
                } else if (strpos($imagen, 'https://') === 0) {
                    $rutaPrueba = $imagen;
                } else {
                    $rutaPrueba = 'frontend/assets/IMAGES-LIBROS/' . $imagen;
                }
            }

            echo "<tr>";
            echo "<td>{$libro['id']}</td>";
            echo "<td style='font-size:12px;'>{$libro['titulo']}</td>";
            echo "<td style='font-size:11px;'>" . ($imagen ?: '<em>NULL</em>') . "</td>";
            echo "<td style='font-size:11px;'>" . ($imagenUrl ?: '<em>NULL</em>') . "</td>";
            echo "<td>";
            if ($rutaPrueba) {
                echo "<img src='$rutaPrueba' class='img-test' onerror=\"this.src='frontend/assets/portada-no-disponible.svg'; this.style.border='2px solid red';\" alt='Prueba'>";
            } else {
                echo "<em>Sin imagen</em>";
            }
            echo "</td>";
            echo "<td class='$claseEstado'>$textoEstado</td>";
            echo "</tr>";
        }

        echo "</table>";

        echo "<h2>📊 Resumen:</h2>";
        echo "<ul>";
        echo "<li><strong>Total de libros revisados:</strong> " . $result->num_rows . "</li>";
        echo "<li><strong>Rutas correctas:</strong> <span class='success'>$rutasCorrectas</span></li>";
        echo "<li><strong>Rutas con problemas:</strong> <span class='error'>$problemasDetectados</span></li>";
        echo "</ul>";

        if ($problemasDetectados > 0) {
            echo "<div class='fix-box'>";
            echo "<h3>🔧 SOLUCIÓN: Ejecuta este SQL en phpMyAdmin</h3>";
            echo "<pre>";
            echo "-- Corregir TODAS las rutas de imágenes\n";
            echo "UPDATE libros\n";
            echo "SET imagen = CONCAT('IMAGES-LIBROS/', imagen)\n";
            echo "WHERE imagen IS NOT NULL\n";
            echo "  AND imagen <> ''\n";
            echo "  AND imagen NOT LIKE 'IMAGES-LIBROS/%'\n";
            echo "  AND imagen NOT LIKE 'https://%'\n";
            echo "  AND imagen NOT LIKE 'imagenes/%';\n\n";
            echo "-- Verificar cambios\n";
            echo "SELECT id, titulo, imagen FROM libros LIMIT 10;";
            echo "</pre>";
            echo "<p><strong>Después de ejecutar el SQL, recarga esta página con Ctrl+F5</strong></p>";
            echo "</div>";
        } else {
            echo "<div style='background:#d4edda; border:2px solid #28a745; padding:15px; margin:20px 0; border-radius:5px;'>";
            echo "<h3 style='color:#155724;'>✅ ¡Todas las rutas están correctas!</h3>";
            echo "<p>Si aún no ves las imágenes en el catálogo:</p>";
            echo "<ol>";
            echo "<li>Verifica que existan los archivos en: <code>frontend/assets/IMAGES-LIBROS/</code></li>";
            echo "<li>Recarga la página del catálogo con <strong>Ctrl + F5</strong></li>";
            echo "<li>Limpia el cache del navegador</li>";
            echo "</ol>";
            echo "</div>";
        }

    } else {
        echo "<p class='error'>No hay libros en la base de datos</p>";
    }

    // Verificar carpeta de imágenes
    echo "<h2>📁 Verificación de archivos físicos:</h2>";
    $carpetaImagenes = __DIR__ . '/frontend/assets/IMAGES-LIBROS/';

    if (is_dir($carpetaImagenes)) {
        $archivos = scandir($carpetaImagenes);
        $archivos = array_diff($archivos, ['.', '..']);

        echo "<p><strong>✅ La carpeta existe:</strong> <code>frontend/assets/IMAGES-LIBROS/</code></p>";
        echo "<p><strong>Total de archivos en la carpeta:</strong> " . count($archivos) . "</p>";

        if (count($archivos) > 0) {
            echo "<p><strong>Primeros 10 archivos:</strong></p>";
            echo "<ul>";
            $contador = 0;
            foreach ($archivos as $archivo) {
                if ($contador >= 10) break;
                echo "<li>$archivo</li>";
                $contador++;
            }
            echo "</ul>";
        }
    } else {
        echo "<p class='error'>❌ La carpeta NO existe: <code>frontend/assets/IMAGES-LIBROS/</code></p>";
        echo "<p>Crea la carpeta y coloca las imágenes allí.</p>";
    }

    $conexion->close();
    ?>

    <hr style="margin: 30px 0;">
    <p><a href="diagnostico_completo.php">← Volver al diagnóstico completo</a></p>
</div>
</body>
</html>
