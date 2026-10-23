<?php
/**
 * DIAGNÓSTICO COMPLETO DEL SISTEMA DE BIBLIOTECA
 * Acceder desde: http://localhost/biblioteca/diagnostico_completo.php
 */

require_once __DIR__ . '/backend/config/conexion.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico Completo - Sistema Biblioteca</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-bottom: 30px; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #34495e; margin: 30px 0 15px; background: #ecf0f1; padding: 10px; border-left: 4px solid #3498db; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .info-box { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin: 15px 0; }
        .problem-box { background: #fee; border-left: 4px solid #e74c3c; padding: 15px; margin: 15px 0; }
        .solution-box { background: #efe; border-left: 4px solid #27ae60; padding: 15px; margin: 15px 0; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .img-preview { max-width: 100px; max-height: 100px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Diagnóstico Completo del Sistema de Biblioteca</h1>

    <?php
    if ($conexion->connect_error) {
        echo "<div class='problem-box'><strong>❌ ERROR DE CONEXIÓN:</strong> " . $conexion->connect_error . "</div>";
        exit;
    }
    echo "<div class='info-box'><strong>✅ Conexión a base de datos:</strong> Exitosa</div>";
    ?>

    <!-- ============================================ -->
    <h2>1. 📊 Estado de la Tabla LIBROS</h2>
    <?php
    $result = $conexion->query("SELECT id, titulo, autor, imagen, imagen_url, ejemplares_total, ejemplares_disponibles, disponible, estado FROM libros LIMIT 10");

    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Título</th><th>Imagen</th><th>imagen_url</th><th>Ejemplares</th><th>Disponible</th><th>Estado</th></tr>";

        while ($libro = $result->fetch_assoc()) {
            $colorDisponible = $libro['disponible'] ? 'success' : 'error';
            $rutaImagen = $libro['imagen'] ?: 'Sin ruta';
            $rutaImagenUrl = $libro['imagen_url'] ?: 'Sin URL';

            echo "<tr>";
            echo "<td>{$libro['id']}</td>";
            echo "<td>{$libro['titulo']}</td>";
            echo "<td style='font-size:11px;'>{$rutaImagen}</td>";
            echo "<td style='font-size:11px;'>{$rutaImagenUrl}</td>";
            echo "<td>{$libro['ejemplares_disponibles']}/{$libro['ejemplares_total']}</td>";
            echo "<td class='$colorDisponible'>" . ($libro['disponible'] ? 'SÍ' : 'NO') . "</td>";
            echo "<td>{$libro['estado']}</td>";
            echo "</tr>";
        }
        echo "</table>";

        // Verificar problemas con rutas de imágenes
        $sinPrefijo = $conexion->query("SELECT COUNT(*) as total FROM libros WHERE imagen IS NOT NULL AND imagen NOT LIKE 'IMAGES-LIBROS/%' AND imagen NOT LIKE 'https://%' AND imagen <> ''");
        $countSinPrefijo = $sinPrefijo->fetch_assoc()['total'];

        if ($countSinPrefijo > 0) {
            echo "<div class='problem-box'>";
            echo "<strong>⚠️ PROBLEMA DETECTADO:</strong> Hay <strong>$countSinPrefijo libros</strong> con rutas de imagen SIN el prefijo 'IMAGES-LIBROS/'<br><br>";
            echo "<strong>Solución:</strong> Ejecuta este comando en phpMyAdmin:";
            echo "<pre>UPDATE libros SET imagen = CONCAT('IMAGES-LIBROS/', imagen) WHERE imagen IS NOT NULL AND imagen NOT LIKE 'IMAGES-LIBROS/%' AND imagen NOT LIKE 'https://%' AND imagen <> '';</pre>";
            echo "</div>";
        } else {
            echo "<div class='solution-box'>✅ Las rutas de imágenes están correctas</div>";
        }
    } else {
        echo "<div class='problem-box'>❌ No hay libros en la base de datos</div>";
    }
    ?>

    <!-- ============================================ -->
    <h2>2. 📅 Políticas de Préstamo</h2>
    <?php
    $result = $conexion->query("SELECT * FROM politicas_prestamo ORDER BY categoria_prestamo, rol");

    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Categoría</th><th>Rol</th><th>Días de Préstamo</th><th>Estado</th></tr>";

        $problema = false;
        while ($politica = $result->fetch_assoc()) {
            $dias = (int)$politica['dias_prestamo'];
            $esIncorrecto = ($politica['categoria_prestamo'] === 'General' && $dias != 15);
            if ($esIncorrecto) $problema = true;

            $clase = $esIncorrecto ? 'error' : 'success';
            $estado = $esIncorrecto ? "❌ Debe ser 15" : "✅ Correcto";

            echo "<tr>";
            echo "<td>{$politica['id']}</td>";
            echo "<td>{$politica['categoria_prestamo']}</td>";
            echo "<td>{$politica['rol']}</td>";
            echo "<td class='$clase'>{$dias} días</td>";
            echo "<td class='$clase'>$estado</td>";
            echo "</tr>";
        }
        echo "</table>";

        if ($problema) {
            echo "<div class='problem-box'>";
            echo "<strong>⚠️ PROBLEMA DETECTADO:</strong> Las políticas de préstamo NO están en 15 días<br><br>";
            echo "<strong>Solución:</strong> Ejecuta estos comandos en phpMyAdmin:";
            echo "<pre>UPDATE politicas_prestamo SET dias_prestamo = 15 WHERE categoria_prestamo = 'General' AND rol = 'Estudiante';\n\n";
            echo "UPDATE politicas_prestamo SET dias_prestamo = 15 WHERE categoria_prestamo = 'General' AND rol = 'Profesor';</pre>";
            echo "</div>";
        } else {
            echo "<div class='solution-box'>✅ Las políticas están configuradas correctamente en 15 días</div>";
        }
    } else {
        echo "<div class='problem-box'>❌ No hay políticas de préstamo configuradas</div>";
    }
    ?>

    <!-- ============================================ -->
    <h2>3. 📧 Configuración de Notificaciones</h2>
    <?php
    require_once __DIR__ . '/backend/config/notificaciones.php';

    $diasRecordatorio = implode(', ', DIAS_RECORDATORIO);
    $esIncorrecto = !in_array(1, DIAS_RECORDATORIO, true);

    if ($esIncorrecto) {
        echo "<div class='problem-box'>";
        echo "<strong>⚠️ PROBLEMA:</strong> Los recordatorios están configurados para: <strong>$diasRecordatorio día(s)</strong> antes<br>";
        echo "Debe ser <strong>1 día</strong> antes del vencimiento<br><br>";
        echo "<strong>Archivo:</strong> backend/config/notificaciones.php<br>";
        echo "<strong>Debe decir:</strong> <code>const DIAS_RECORDATORIO = [1];</code>";
        echo "</div>";
    } else {
        echo "<div class='solution-box'>✅ Recordatorios configurados correctamente para 1 día antes</div>";
    }
    ?>

    <!-- ============================================ -->
    <h2>4. 📱 Configuración de WhatsApp</h2>
    <?php
    $token = getenv('WHATSAPP_ACCESS_TOKEN') ?: '';
    $phoneId = getenv('WHATSAPP_PHONE_NUMBER_ID') ?: '';
    $template = getenv('WHATSAPP_TEMPLATE_NAME') ?: '';
    $version = getenv('WHATSAPP_API_VERSION') ?: '';

    echo "<table>";
    echo "<tr><th>Variable</th><th>Estado</th></tr>";
    echo "<tr><td>WHATSAPP_ACCESS_TOKEN</td><td class='" . ($token ? 'success' : 'error') . "'>" . ($token ? '✅ Configurado' : '❌ No configurado') . "</td></tr>";
    echo "<tr><td>WHATSAPP_PHONE_NUMBER_ID</td><td class='" . ($phoneId ? 'success' : 'error') . "'>" . ($phoneId ? '✅ Configurado' : '❌ No configurado') . "</td></tr>";
    echo "<tr><td>WHATSAPP_TEMPLATE_NAME</td><td class='" . ($template ? 'success' : 'error') . "'>" . ($template ? '✅ Configurado' : '❌ No configurado') . "</td></tr>";
    echo "<tr><td>WHATSAPP_API_VERSION</td><td class='" . ($version ? 'success' : 'error') . "'>" . ($version ? '✅ Configurado' : '❌ No configurado') . "</td></tr>";
    echo "</table>";

    if (!$token || !$phoneId || !$template || !$version) {
        echo "<div class='problem-box'>";
        echo "<strong>⚠️ WhatsApp NO está configurado</strong><br>";
        echo "Los recordatorios se enviarán solo por correo electrónico hasta que configures WhatsApp.<br><br>";
        echo "<strong>Para configurar:</strong> Lee el archivo <code>CONFIGURAR_WHATSAPP.md</code>";
        echo "</div>";
    } else {
        echo "<div class='solution-box'>✅ WhatsApp está configurado</div>";
    }
    ?>

    <!-- ============================================ -->
    <h2>5. 👥 Usuarios con WhatsApp</h2>
    <?php
    $result = $conexion->query("SELECT nombre, doc, rol, telefono_whatsapp, whatsapp_consentimiento FROM estudiantes WHERE telefono_whatsapp IS NOT NULL AND telefono_whatsapp <> '' LIMIT 10");

    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>Nombre</th><th>Documento</th><th>Rol</th><th>WhatsApp</th><th>Consentimiento</th></tr>";

        while ($usuario = $result->fetch_assoc()) {
            $consentimiento = $usuario['whatsapp_consentimiento'] ? '✅ SÍ' : '❌ NO';
            $claseConsent = $usuario['whatsapp_consentimiento'] ? 'success' : 'warning';

            echo "<tr>";
            echo "<td>{$usuario['nombre']}</td>";
            echo "<td>{$usuario['doc']}</td>";
            echo "<td>{$usuario['rol']}</td>";
            echo "<td>{$usuario['telefono_whatsapp']}</td>";
            echo "<td class='$claseConsent'>$consentimiento</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info-box'>ℹ️ No hay usuarios con WhatsApp registrado</div>";
    }
    ?>

    <!-- ============================================ -->
    <h2>6. 📖 Préstamos Activos</h2>
    <?php
    $result = $conexion->query("
        SELECT p.id, e.nombre, l.titulo, p.fecha_prestamo, p.fecha_devolucion_esperada,
               DATEDIFF(p.fecha_devolucion_esperada, NOW()) as dias_restantes, p.estado
        FROM prestamos p
        INNER JOIN estudiantes e ON e.id = p.estudiante_id
        INNER JOIN libros l ON l.id = p.libro_id
        WHERE p.estado = 'Activo'
        ORDER BY p.fecha_devolucion_esperada ASC
        LIMIT 10
    ");

    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Usuario</th><th>Libro</th><th>Fecha Préstamo</th><th>Fecha Devolución</th><th>Días Restantes</th></tr>";

        while ($prestamo = $result->fetch_assoc()) {
            $dias = (int)$prestamo['dias_restantes'];
            $clase = $dias < 0 ? 'error' : ($dias <= 1 ? 'warning' : 'success');
            $estadoDias = $dias < 0 ? "❌ VENCIDO" : ($dias == 1 ? "⚠️ Vence mañana" : "✅ $dias días");

            echo "<tr>";
            echo "<td>{$prestamo['id']}</td>";
            echo "<td>{$prestamo['nombre']}</td>";
            echo "<td>{$prestamo['titulo']}</td>";
            echo "<td>" . date('d/m/Y', strtotime($prestamo['fecha_prestamo'])) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])) . "</td>";
            echo "<td class='$clase'>$estadoDias</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info-box'>ℹ️ No hay préstamos activos en este momento</div>";
    }
    ?>

    <!-- ============================================ -->
    <h2>7. 🔧 Verificación de Archivos</h2>
    <?php
    $archivos = [
        'frontend/assets/logo.1.png' => 'Logo principal',
        'frontend/assets/logo-institucion-cusiana.png' => 'Escudo institucional',
        'frontend/assets/portada-no-disponible.svg' => 'Portada por defecto',
        'frontend/assets/IMAGES-LIBROS' => 'Carpeta de imágenes de libros',
    ];

    echo "<table>";
    echo "<tr><th>Archivo/Carpeta</th><th>Descripción</th><th>Estado</th></tr>";

    foreach ($archivos as $ruta => $desc) {
        $rutaCompleta = __DIR__ . '/' . $ruta;
        $existe = file_exists($rutaCompleta);
        $clase = $existe ? 'success' : 'error';
        $estado = $existe ? '✅ Existe' : '❌ No encontrado';

        echo "<tr>";
        echo "<td><code>$ruta</code></td>";
        echo "<td>$desc</td>";
        echo "<td class='$clase'>$estado</td>";
        echo "</tr>";
    }
    echo "</table>";
    ?>

    <!-- ============================================ -->
    <h2>8. 📋 Resumen y Acciones Requeridas</h2>
    <?php
    $problemas = [];

    // Verificar problemas
    $sinPrefijo = $conexion->query("SELECT COUNT(*) as total FROM libros WHERE imagen IS NOT NULL AND imagen NOT LIKE 'IMAGES-LIBROS/%' AND imagen NOT LIKE 'https://%' AND imagen <> ''");
    if ($sinPrefijo && $sinPrefijo->fetch_assoc()['total'] > 0) {
        $problemas[] = "Las rutas de imágenes de libros necesitan corrección";
    }

    $politicasIncorrectas = $conexion->query("SELECT COUNT(*) as total FROM politicas_prestamo WHERE categoria_prestamo = 'General' AND dias_prestamo != 15");
    if ($politicasIncorrectas && $politicasIncorrectas->fetch_assoc()['total'] > 0) {
        $problemas[] = "Las políticas de préstamo deben actualizarse a 15 días";
    }

    if (!in_array(1, DIAS_RECORDATORIO, true)) {
        $problemas[] = "Los días de recordatorio deben configurarse a 1 día antes";
    }

    if (!$token || !$phoneId || !$template || !$version) {
        $problemas[] = "WhatsApp no está configurado (opcional, pero recomendado)";
    }

    if (count($problemas) > 0) {
        echo "<div class='problem-box'>";
        echo "<h3>⚠️ Problemas Detectados (" . count($problemas) . "):</h3>";
        echo "<ol>";
        foreach ($problemas as $problema) {
            echo "<li>$problema</li>";
        }
        echo "</ol>";
        echo "<br><strong>Solución:</strong> Ejecuta el archivo <code>database/actualizar_simple.sql</code> en phpMyAdmin";
        echo "</div>";
    } else {
        echo "<div class='solution-box'>";
        echo "<h3>🎉 ¡Sistema Correctamente Configurado!</h3>";
        echo "<p>No se detectaron problemas críticos. El sistema está listo para usar.</p>";
        echo "</div>";
    }
    ?>

    <hr style="margin: 30px 0;">
    <p><strong>Fecha del diagnóstico:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
    <p><strong>Acceso al sistema:</strong> <a href="frontend/index.html">http://localhost/biblioteca/frontend/index.html</a></p>

</div>
</body>
</html>
<?php
$conexion->close();
?>
