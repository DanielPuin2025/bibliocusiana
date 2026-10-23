<?php
/**
 * Script para agregar ISBNs automáticamente a los libros
 * Ejecutar desde: http://localhost/biblioteca/agregar_isbns.php
 */

require_once __DIR__ . '/backend/config/conexion.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar ISBNs Automáticamente</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #2c3e50; }
        .libro { background: #ecf0f1; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        button { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        button:hover { background: #2980b9; }
        .imagen-test { max-width: 100px; max-height: 150px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Agregar ISBNs Automáticamente a los Libros</h1>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

            if ($_POST['accion'] === 'agregar_isbns') {
                // Agregar ISBNs comunes
                $isbns = [
                    ['titulo' => '%Cien años%', 'isbn' => '9780307474728'],
                    ['titulo' => '%Principito%', 'isbn' => '9780156012195'],
                    ['titulo' => '%1984%', 'isbn' => '9780451524935'],
                    ['titulo' => '%Don Quijote%', 'isbn' => '9780060934347'],
                    ['titulo' => '%Orgullo%', 'isbn' => '9780141439518'],
                    ['titulo' => '%Alquimista%', 'isbn' => '9788420412146'],
                    ['titulo' => '%Harry Potter%', 'isbn' => '9780439708180'],
                    ['titulo' => '%Álgebra recreativa%', 'isbn' => '9785030009841'],
                    ['titulo' => '%Física%divertida%', 'isbn' => '9785030009858'],
                    ['titulo' => '%20000 leguas%', 'isbn' => '9780141321004'],
                    ['titulo' => '%Viaje%centro%tierra%', 'isbn' => '9780141321073'],
                    ['titulo' => '%Vuelta%mundo%', 'isbn' => '9780141321080'],
                ];

                echo "<h2>✅ Agregando ISBNs...</h2>";

                foreach ($isbns as $libro) {
                    $stmt = $conexion->prepare("UPDATE libros SET isbn = ? WHERE titulo LIKE ? AND (isbn IS NULL OR isbn = '')");
                    $stmt->bind_param('ss', $libro['isbn'], $libro['titulo']);
                    $stmt->execute();

                    if ($stmt->affected_rows > 0) {
                        echo "<div class='libro success'>✅ ISBN agregado: {$libro['titulo']} → {$libro['isbn']}</div>";
                    }
                }

                echo "<p class='success'><strong>¡ISBNs agregados exitosamente!</strong></p>";
            }

            if ($_POST['accion'] === 'corregir_rutas') {
                // Corregir rutas de imágenes
                $sql = "UPDATE libros SET imagen = CONCAT('IMAGES-LIBROS/', imagen)
                        WHERE imagen IS NOT NULL
                        AND imagen <> ''
                        AND imagen NOT LIKE 'IMAGES-LIBROS/%'
                        AND imagen NOT LIKE 'https://%'";

                $conexion->query($sql);

                echo "<h2>✅ Rutas de imágenes corregidas</h2>";
                echo "<p class='success'>Se actualizaron las rutas de las imágenes locales</p>";
            }
        }
        ?>

        <h2>📋 Estado Actual de los Libros</h2>

        <?php
        $result = $conexion->query("
            SELECT id, titulo, autor, isbn, imagen
            FROM libros
            ORDER BY id ASC
            LIMIT 20
        ");

        if ($result && $result->num_rows > 0) {
            echo "<table style='width: 100%; border-collapse: collapse;'>";
            echo "<tr style='background: #34495e; color: white;'>";
            echo "<th style='padding: 10px; border: 1px solid #ddd;'>ID</th>";
            echo "<th style='padding: 10px; border: 1px solid #ddd;'>Título</th>";
            echo "<th style='padding: 10px; border: 1px solid #ddd;'>ISBN</th>";
            echo "<th style='padding: 10px; border: 1px solid #ddd;'>Portada</th>";
            echo "</tr>";

            while ($libro = $result->fetch_assoc()) {
                $isbn = $libro['isbn'] ?: 'Sin ISBN';
                $tieneIsbn = $libro['isbn'] ? 'success' : 'error';

                // Construir URL de portada
                $urlPortada = '';
                if ($libro['isbn']) {
                    $urlPortada = "https://covers.openlibrary.org/b/isbn/{$libro['isbn']}-M.jpg";
                }

                echo "<tr>";
                echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$libro['id']}</td>";
                echo "<td style='padding: 10px; border: 1px solid #ddd;'>{$libro['titulo']}</td>";
                echo "<td style='padding: 10px; border: 1px solid #ddd;' class='$tieneIsbn'>$isbn</td>";
                echo "<td style='padding: 10px; border: 1px solid #ddd;'>";

                if ($urlPortada) {
                    echo "<img src='$urlPortada' class='imagen-test' onerror=\"this.src='frontend/assets/portada-no-disponible.svg'\">";
                    echo "<br><small>$urlPortada</small>";
                } else {
                    echo "❌ Sin ISBN";
                }

                echo "</td>";
                echo "</tr>";
            }

            echo "</table>";
        }

        // Resumen
        $stats = $conexion->query("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN isbn IS NOT NULL AND isbn <> '' THEN 1 ELSE 0 END) as con_isbn,
                SUM(CASE WHEN isbn IS NULL OR isbn = '' THEN 1 ELSE 0 END) as sin_isbn
            FROM libros
        ")->fetch_assoc();

        echo "<h2>📊 Resumen</h2>";
        echo "<p><strong>Total de libros:</strong> {$stats['total']}</p>";
        echo "<p class='success'><strong>Con ISBN (portadas automáticas):</strong> {$stats['con_isbn']}</p>";
        echo "<p class='error'><strong>Sin ISBN:</strong> {$stats['sin_isbn']}</p>";
        ?>

        <h2>🔧 Acciones</h2>

        <form method="POST" style="margin: 20px 0;">
            <input type="hidden" name="accion" value="agregar_isbns">
            <button type="submit">📚 Agregar ISBNs Automáticamente</button>
        </form>

        <form method="POST" style="margin: 20px 0;">
            <input type="hidden" name="accion" value="corregir_rutas">
            <button type="submit" style="background: #e74c3c;">🖼️ Corregir Rutas de Imágenes</button>
        </form>

        <hr>
        <p><a href="frontend/index.html"><button style="background: #27ae60;">✅ Ir al Sistema</button></a></p>
    </div>
</body>
</html>
<?php
$conexion->close();
?>
