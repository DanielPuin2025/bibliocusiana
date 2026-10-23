<?php
$host = getenv('DB_HOST') ?: 'localhost';
$usuario = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD');
$base_datos = getenv('DB_NAME') ?: 'biblioteca_db';

if ($password === false) {
    $password = '';
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexion = new mysqli($host, $usuario, $password, $base_datos);
    $conexion->set_charset('utf8mb4');
} catch (mysqli_sql_exception $error) {
    error_log('Error de conexión a la base de datos: ' . $error->getMessage());
    http_response_code(500);
    die('No fue posible conectar con la base de datos.');
}
?>