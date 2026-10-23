<?php
/**
 * DIAGNÓSTICO COMPLETO DE PORTADAS
 * Verifica cuántos libros tienen portada disponible
 * Acceder: http://localhost/biblioteca/diagnostico_portadas.php
 */

require_once __DIR__ . '/backend/config/conexion.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico de Portadas</title>
    <style>
        body { font-family: Arial; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        h1 { color: #2c3e50; margin-bottom: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0; }
        .stat-card { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .stat-card.success { background: linear-gradient(135deg, #11998e, #38ef7d); }
        .stat-card.warning { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .stat-number { font-size: 48px; font-weight: bold; margin: 10px 0; }
        .stat-label { font-size: 16px; opacity: 0.9; }
        .chart { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        tr:hover { background: #f5f5f5; }
        .img-preview { max-width: 80px; max-height: 120px; border-radius: 5px; }
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge.success { background: #27ae60; color: white; }
        .badge.error { background: #e74c3c; color: white; }
        button { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; margin: 5px; }
        button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        .section { margin: 40px 0; }
        h2 { color: #2c3e50; border-bottom: 3px solid #667eea; padding-bottom: 10px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Diagnóstico Completo de Portadas</h1>

        <?php
        // Estadísticas generales
        $stats = $conexion->query("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN isbn IS NOT NULL AND isbn <> '' THEN 1 ELSE 0 END) as con_isbn,
                SUM(CASE WHEN isbn IS NULL OR isbn = '' THEN 1 ELSE 0 END) as sin_isbn,
                SUM(CASE WHEN imagen IS NOT NULL AND imagen <> '' AND imagen NOT LIKE '%no-disponible%' THEN 1 ELSE 0 END) as con_imagen_local
            FROM libros
        ")->fetch_assoc();

        $total = $stats['total'];
        $conIsbn = $stats['con_isbn'];
        $sinIsbn = $stats['sin_isbn'];
        $conImagenLocal = $stats['con_imagen_local'];

        // Calcular libros con portada disponible
        $conPortada = $conIsbn + $conImagenLocal;
        $sinPortada = $total - $conPortada;

        $porcentajeConPortada = $total > 0 ? round(($conPortada / $total) * 100, 1) : 0;
        $porcentajeSinPortada = $total > 0 ? round(($sinPortada / $total) * 100, 1) : 0;
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total de Libros</div>
                <div class="stat-number"><?php echo $total; ?></div>
            </div>

            <div class="stat-card success">
                <div class="stat-label">✅ Con Portada</div>
                <div class="stat-number"><?php echo $conPortada; ?></div>
                <div class="stat-label"><?php echo $porcentajeConPortada; ?>% del total</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-label">❌ Sin Portada</div>
                <div class="stat-number"><?php echo $sinPortada; ?></div>
                <div class="stat-label"><?php echo $porcentajeSinPortada; ?>% del total</div>
            </div>
        </div>

        <div class="chart">
            <h3>📈 Desglose Detallado</h3>
            <table style="width: 100%; border: none;">
                <tr>
                    <td><strong>Con ISBN (portada automática):</strong></td>
                    <td style="text-align: right;"><span class="badge success"><?php echo $conIsbn; ?> libros</span></td>
                </tr>
                <tr>
                    <td><strong>Con imagen local:</strong></td>
                    <td style="text-align: right;"><span class="badge success"><?php echo $conImagenLocal; ?> libros</span></td>
                </tr>
                <tr>
                    <td><strong>Sin ISBN ni imagen:</strong></td>
                    <td style="text-align: right;"><span class="badge error"><?php echo $sinPortada; ?> libros</span></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h2>📚 Libros CON Portada (Primeros 20)</h2>
            <?php
            $librosConPortada = $conexion->query("
                SELECT id, titulo, autor, isbn, imagen
                FROM libros
                WHERE (isbn IS NOT NULL AND isbn <> '')
                   OR (imagen IS NOT NULL AND imagen <> '' AND imagen NOT LIKE '%no-disponible%')
                ORDER BY id ASC
                LIMIT 20
            ");

            if ($librosConPortada && $librosConPortada->num_rows > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Título</th><th>Autor</th><th>ISBN</th><th>Portada</th></tr>";

                while ($libro = $librosConPortada->fetch_assoc()) {
                    $urlPortada = '';

                    if ($libro['isbn']) {
                        $urlPortada = "https://covers.openlibrary.org/b/isbn/{$libro['isbn']}-M.jpg";
                    } else if ($libro['imagen']) {
                        $urlPortada = "frontend/assets/{$libro['imagen']}";
                    }

                    echo "<tr>";
                    echo "<td>{$libro['id']}</td>";
                    echo "<td>{$libro['titulo']}</td>";
                    echo "<td>{$libro['autor']}</td>";
                    echo "<td>" . ($libro['isbn'] ?: '<em>-</em>') . "</td>";
                    echo "<td>";
                    if ($urlPortada) {
                        echo "<img src='$urlPortada' class='img-preview' onerror=\"this.src='frontend/assets/portada-no-disponible.svg'\">";
                    } else {
                        echo "Sin portada";
                    }
                    echo "</td>";
                    echo "</tr>";
                }

                echo "</table>";
            }
            ?>
        </div>

        <div class="section">
            <h2>⚠️ Libros SIN Portada (Primeros 20)</h2>
            <?php
            $librosSinPortada = $conexion->query("
                SELECT id, titulo, autor
                FROM libros
                WHERE (isbn IS NULL OR isbn = '')
                  AND (imagen IS NULL OR imagen = '' OR imagen LIKE '%no-disponible%')
                ORDER BY id ASC
                LIMIT 20
            ");

            if ($librosSinPortada && $librosSinPortada->num_rows > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Título</th><th>Autor</th></tr>";

                while ($libro = $librosSinPortada->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$libro['id']}</td>";
                    echo "<td>{$libro['titulo']}</td>";
                    echo "<td>{$libro['autor']}</td>";
                    echo "</tr>";
                }

                echo "</table>";
            }
            ?>
        </div>

        <div class="section" style="text-align: center;">
            <h2>🔧 Acciones Disponibles</h2>

            <?php if ($sinPortada > 0): ?>
                <p style="color: #e74c3c; font-weight: bold; margin: 20px 0;">
                    ⚠️ Tienes <?php echo $sinPortada; ?> libros sin portada
                </p>

                <a href="buscar_isbns_automatico.php">
                    <button style="background: linear-gradient(135deg, #f093fb, #f5576c); font-size: 16px; padding: 15px 30px;">
                        🔍 Buscar ISBNs Automáticamente
                    </button>
                </a>

                <p style="margin: 20px 0; color: #7f8c8d;">
                    <small>Esta herramienta buscará ISBNs automáticamente para todos los libros sin portada</small>
                </p>
            <?php else: ?>
                <p style="color: #27ae60; font-weight: bold; font-size: 18px;">
                    ✅ ¡Todos tus libros tienen portada!
                </p>
            <?php endif; ?>

            <a href="frontend/index.html">
                <button style="background: #27ae60;">
                    📚 Ver Catálogo
                </button>
            </a>

            <a href="diagnostico_completo.php">
                <button style="background: #3498db;">
                    📊 Diagnóstico Completo
                </button>
            </a>
        </div>

        <hr style="margin: 40px 0;">
        <p style="text-align: center; color: #7f8c8d;">
            <small>Última actualización: <?php echo date('d/m/Y H:i:s'); ?></small>
        </p>
    </div>
</body>
</html>
<?php $conexion->close(); ?>
