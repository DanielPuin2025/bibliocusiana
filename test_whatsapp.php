<?php
/**
 * Script de prueba para verificar configuración de WhatsApp
 * Acceder desde: http://localhost/biblioteca/test_whatsapp.php
 */

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Verificación WhatsApp - Biblioteca</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #2c3e50; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .info { background: #ecf0f1; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        td:first-child { font-weight: bold; width: 250px; }
    </style>
</head>
<body>
    <h1>🔧 Verificación de Configuración - WhatsApp Business API</h1>";

echo "<h2>1. Variables de Entorno</h2>";
echo "<table>";

$token = getenv('WHATSAPP_ACCESS_TOKEN');
$phoneId = getenv('WHATSAPP_PHONE_NUMBER_ID');
$template = getenv('WHATSAPP_TEMPLATE_NAME');
$version = getenv('WHATSAPP_API_VERSION');

echo "<tr><td>WHATSAPP_ACCESS_TOKEN</td><td>";
if ($token && $token !== '') {
    echo "<span class='success'>✅ Configurado</span> (Longitud: " . strlen($token) . " caracteres)";
} else {
    echo "<span class='error'>❌ No configurado</span>";
}
echo "</td></tr>";

echo "<tr><td>WHATSAPP_PHONE_NUMBER_ID</td><td>";
if ($phoneId && $phoneId !== '') {
    echo "<span class='success'>✅ Configurado</span> (Valor: " . $phoneId . ")";
} else {
    echo "<span class='error'>❌ No configurado</span>";
}
echo "</td></tr>";

echo "<tr><td>WHATSAPP_TEMPLATE_NAME</td><td>";
if ($template && $template !== '') {
    echo "<span class='success'>✅ Configurado</span> (Plantilla: " . $template . ")";
} else {
    echo "<span class='error'>❌ No configurado</span>";
}
echo "</td></tr>";

echo "<tr><td>WHATSAPP_API_VERSION</td><td>";
if ($version && $version !== '') {
    echo "<span class='success'>✅ Configurado</span> (Versión: " . $version . ")";
} else {
    echo "<span class='error'>❌ No configurado</span>";
}
echo "</td></tr>";

echo "</table>";

echo "<h2>2. Extensiones de PHP</h2>";
echo "<table>";

echo "<tr><td>cURL</td><td>";
if (function_exists('curl_init')) {
    echo "<span class='success'>✅ Habilitado</span>";
    $curl_version = curl_version();
    echo " (Versión: " . $curl_version['version'] . ")";
} else {
    echo "<span class='error'>❌ NO habilitado</span>";
    echo "<br><small>Edita c:\\xampp\\php\\php.ini y descomenta: extension=curl</small>";
}
echo "</td></tr>";

echo "<tr><td>JSON</td><td>";
if (function_exists('json_encode')) {
    echo "<span class='success'>✅ Habilitado</span>";
} else {
    echo "<span class='error'>❌ NO habilitado</span>";
}
echo "</td></tr>";

echo "<tr><td>OpenSSL</td><td>";
if (extension_loaded('openssl')) {
    echo "<span class='success'>✅ Habilitado</span>";
} else {
    echo "<span class='error'>❌ NO habilitado</span>";
}
echo "</td></tr>";

echo "</table>";

echo "<h2>3. Archivos del Sistema</h2>";
echo "<table>";

$archivos = [
    'backend/config/whatsapp.php' => 'Configuración WhatsApp',
    'backend/services/verificar_vencimientos.php' => 'Servicio de verificación',
    'backend/cron/verificar_vencimientos.php' => 'CRON de verificación',
    'backend/api/verificar_vencimientos.php' => 'API de verificación',
];

foreach ($archivos as $ruta => $descripcion) {
    echo "<tr><td>$descripcion</td><td>";
    if (file_exists(__DIR__ . '/' . $ruta)) {
        echo "<span class='success'>✅ Existe</span> ($ruta)";
    } else {
        echo "<span class='error'>❌ No encontrado</span> ($ruta)";
    }
    echo "</td></tr>";
}

echo "</table>";

echo "<h2>4. Base de Datos</h2>";
require_once __DIR__ . '/backend/config/conexion.php';

echo "<table>";

echo "<tr><td>Conexión MySQL</td><td>";
if ($conexion->connect_error) {
    echo "<span class='error'>❌ Error: " . $conexion->connect_error . "</span>";
} else {
    echo "<span class='success'>✅ Conectado</span>";
}
echo "</td></tr>";

if (!$conexion->connect_error) {
    // Verificar tabla notificaciones_whatsapp
    $result = $conexion->query("SHOW TABLES LIKE 'notificaciones_whatsapp'");
    echo "<tr><td>Tabla notificaciones_whatsapp</td><td>";
    if ($result && $result->num_rows > 0) {
        echo "<span class='success'>✅ Existe</span>";
    } else {
        echo "<span class='error'>❌ No existe</span>";
    }
    echo "</td></tr>";

    // Verificar tabla prestamos
    $result = $conexion->query("SHOW TABLES LIKE 'prestamos'");
    echo "<tr><td>Tabla prestamos</td><td>";
    if ($result && $result->num_rows > 0) {
        echo "<span class='success'>✅ Existe</span>";
    } else {
        echo "<span class='error'>❌ No existe</span>";
    }
    echo "</td></tr>";

    // Verificar usuarios con WhatsApp configurado
    $result = $conexion->query("SELECT COUNT(*) as total FROM estudiantes WHERE telefono_whatsapp IS NOT NULL AND telefono_whatsapp <> ''");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<tr><td>Usuarios con WhatsApp</td><td>";
        echo "<span class='info'>" . $row['total'] . " usuario(s)</span>";
        echo "</td></tr>";
    }

    // Verificar préstamos activos
    $result = $conexion->query("SELECT COUNT(*) as total FROM prestamos WHERE estado = 'Activo'");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<tr><td>Préstamos activos</td><td>";
        echo "<span class='info'>" . $row['total'] . " préstamo(s)</span>";
        echo "</td></tr>";
    }
}

echo "</table>";

echo "<h2>5. Configuración de Notificaciones</h2>";
require_once __DIR__ . '/backend/config/notificaciones.php';

echo "<div class='info'>";
echo "<strong>Días de recordatorio configurados:</strong> ";
echo implode(', ', DIAS_RECORDATORIO);
echo " día(s) antes del vencimiento";
echo "</div>";

echo "<h2>6. Resumen</h2>";

$todoOk = $token && $phoneId && $template && $version && function_exists('curl_init') && !$conexion->connect_error;

if ($todoOk) {
    echo "<div class='info' style='background: #d4edda; border: 1px solid #c3e6cb;'>";
    echo "<strong style='color: #155724;'>✅ Sistema listo para enviar notificaciones por WhatsApp</strong>";
    echo "</div>";

    echo "<h3>Próximos pasos:</h3>";
    echo "<ol>";
    echo "<li>Configura la tarea programada en Windows para ejecutar automáticamente los recordatorios</li>";
    echo "<li>Prueba enviando un mensaje desde el panel administrativo</li>";
    echo "<li>Verifica que la plantilla de mensaje esté aprobada en Meta</li>";
    echo "</ol>";
} else {
    echo "<div class='info' style='background: #f8d7da; border: 1px solid #f5c6cb;'>";
    echo "<strong style='color: #721c24;'>⚠️ Hay problemas de configuración que debes resolver</strong>";
    echo "</div>";

    echo "<h3>Tareas pendientes:</h3>";
    echo "<ul>";

    if (!$token || !$phoneId || !$template || !$version) {
        echo "<li>Configurar las variables de entorno de WhatsApp en Apache o archivo .env</li>";
    }

    if (!function_exists('curl_init')) {
        echo "<li>Habilitar la extensión cURL en php.ini</li>";
    }

    if ($conexion->connect_error) {
        echo "<li>Verificar la conexión a la base de datos</li>";
    }

    echo "<li>Consulta la guía completa en: <strong>CONFIGURAR_WHATSAPP.md</strong></li>";
    echo "</ul>";
}

echo "<hr style='margin: 30px 0;'>";
echo "<p><small>Para más ayuda, consulta: <a href='CONFIGURAR_WHATSAPP.md'>CONFIGURAR_WHATSAPP.md</a></small></p>";

echo "</body></html>";

$conexion->close();
?>
