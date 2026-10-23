<?php
/**
 * BUSCAR Y AGREGAR ISBNs AUTOMÁTICAMENTE
 * Usa la API de Open Library para buscar ISBNs por título y autor
 * Acceder: http://localhost/biblioteca/buscar_isbns_automatico.php
 */

require_once __DIR__ . '/backend/config/conexion.php';

set_time_limit(300); // 5 minutos
header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Búsqueda Automática de ISBNs</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #2c3e50; }
        .progress { background: #ecf0f1; height: 30px; border-radius: 5px; overflow: hidden; margin: 20px 0; }
        .progress-bar { background: linear-gradient(90deg, #667eea, #764ba2); height: 100%; transition: width 0.3s; color: white; text-align: center; line-height: 30px; font-weight: bold; }
        .libro { padding: 10px; margin: 5px 0; border-left: 4px solid #3498db; background: #ecf0f1; border-radius: 3px; }
        .success { border-left-color: #27ae60; background: #d5f4e6; }
        .error { border-left-color: #e74c3c; background: #fadbd8; }
        .info { background: #e8f4f8; padding: 15px; border-radius: 5px; margin: 20px 0; }
        button { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 15px 30px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin: 10px 5px; }
        button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        button:disabled { background: #95a5a6; cursor: not-allowed; }
        #resultado { max-height: 400px; overflow-y: auto; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { flex: 1; padding: 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 10px; text-align: center; }
        .stat-number { font-size: 36px; font-weight: bold; }
        .stat-label { font-size: 14px; opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Búsqueda Automática de ISBNs</h1>

        <div class="info">
            <strong>📚 Cómo funciona:</strong>
            <ol>
                <li>Lee todos los libros sin ISBN de tu base de datos</li>
                <li>Busca cada libro en Open Library API (gratis, sin límites)</li>
                <li>Si encuentra el libro, agrega el ISBN automáticamente</li>
                <li>Las portadas se cargarán automáticamente después</li>
            </ol>
            <p><strong>⏱️ Tiempo estimado:</strong> 5-10 minutos para 613 libros</p>
        </div>

        <?php
        // Obtener estadísticas
        $stats = $conexion->query("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN isbn IS NOT NULL AND isbn <> '' THEN 1 ELSE 0 END) as con_isbn,
                SUM(CASE WHEN isbn IS NULL OR isbn = '' THEN 1 ELSE 0 END) as sin_isbn
            FROM libros
        ")->fetch_assoc();
        ?>

        <div class="stats">
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total de Libros</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $stats['con_isbn']; ?></div>
                <div class="stat-label">Con ISBN</div>
            </div>
            <div class="stat-box">
                <div class="stat-number" id="sin-isbn"><?php echo $stats['sin_isbn']; ?></div>
                <div class="stat-label">Sin ISBN</div>
            </div>
        </div>

        <div>
            <button id="btnBuscar" onclick="iniciarBusqueda()">🚀 Buscar ISBNs Automáticamente</button>
            <button onclick="window.location.href='frontend/index.html'" style="background: #27ae60;">✅ Ir al Sistema</button>
        </div>

        <div class="progress" id="progress-container" style="display: none;">
            <div class="progress-bar" id="progress-bar">0%</div>
        </div>

        <div id="resultado"></div>
    </div>

    <script>
        let procesando = false;

        async function iniciarBusqueda() {
            if (procesando) return;

            const btn = document.getElementById('btnBuscar');
            const progressContainer = document.getElementById('progress-container');
            const progressBar = document.getElementById('progress-bar');
            const resultado = document.getElementById('resultado');

            btn.disabled = true;
            procesando = true;
            progressContainer.style.display = 'block';
            resultado.innerHTML = '<h3>🔍 Buscando ISBNs...</h3>';

            try {
                const response = await fetch('backend/api/buscar_isbns_batch.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });

                const reader = response.body.getReader();
                const decoder = new TextDecoder();

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    const chunk = decoder.decode(value);
                    const lines = chunk.split('\n');

                    for (const line of lines) {
                        if (!line.trim()) continue;

                        try {
                            const data = JSON.parse(line);

                            if (data.progress) {
                                progressBar.style.width = data.progress + '%';
                                progressBar.textContent = data.progress + '%';
                            }

                            if (data.mensaje) {
                                const clase = data.isbn_encontrado ? 'success' : 'error';
                                resultado.innerHTML += `<div class="libro ${clase}">${data.mensaje}</div>`;
                                resultado.scrollTop = resultado.scrollHeight;
                            }

                            if (data.finalizado) {
                                resultado.innerHTML += `
                                    <div class="libro success" style="margin-top: 20px; font-size: 18px;">
                                        <strong>✅ ¡Búsqueda Completada!</strong><br>
                                        ISBNs encontrados: ${data.encontrados}<br>
                                        No encontrados: ${data.no_encontrados}<br>
                                        <br>
                                        <a href="frontend/index.html"><button>Ver Catálogo con Portadas</button></a>
                                    </div>
                                `;
                                btn.disabled = false;
                                procesando = false;
                            }
                        } catch (e) {
                            // Ignorar líneas que no sean JSON
                        }
                    }
                }
            } catch (error) {
                resultado.innerHTML += `<div class="libro error">❌ Error: ${error.message}</div>`;
                btn.disabled = false;
                procesando = false;
            }
        }
    </script>
</body>
</html>
<?php $conexion->close(); ?>
