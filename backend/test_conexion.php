<?php
// Archivo de prueba para diagnosticar problemas
header('Content-Type: application/json; charset=utf-8');

// Test 1: PHP funciona
$tests = [
    'php_funciona' => true,
    'version_php' => phpversion(),
    'conexion_db' => false,
    'tabla_estudiantes_existe' => false,
    'tabla_libros_existe' => false,
    'tabla_prestamos_existe' => false
];

// Test 2: Conexión a base de datos
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $host = "localhost";
    $usuario = "root";
    $password = "";
    $base_datos = "biblioteca_db";

    $conexion = new mysqli($host, $usuario, $password, $base_datos);

    if ($conexion->connect_error) {
        $tests['conexion_db'] = false;
        $tests['error_db'] = $conexion->connect_error;
    } else {
        $tests['conexion_db'] = true;

        // Test 3: Verificar si existe la tabla estudiantes
        $resultado = $conexion->query("SHOW TABLES LIKE 'estudiantes'");
        $tests['tabla_estudiantes_existe'] = ($resultado->num_rows > 0);

        // Test 4: Contar registros
        if ($tests['tabla_estudiantes_existe']) {
            $resultado = $conexion->query("SELECT COUNT(*) as total FROM estudiantes");
            $fila = $resultado->fetch_assoc();
            $tests['total_estudiantes'] = $fila['total'];
        }

        // Test 5: Verificar tabla libros
        $resultado = $conexion->query("SHOW TABLES LIKE 'libros'");
        $tests['tabla_libros_existe'] = ($resultado->num_rows > 0);

        if ($tests['tabla_libros_existe']) {
            $resultado = $conexion->query("SELECT COUNT(*) as total FROM libros");
            $fila = $resultado->fetch_assoc();
            $tests['total_libros'] = $fila['total'];
        }

        // Test 6: Verificar tabla prestamos
        $resultado = $conexion->query("SHOW TABLES LIKE 'prestamos'");
        $tests['tabla_prestamos_existe'] = ($resultado->num_rows > 0);

        $conexion->close();
    }
} catch (Throwable $error) {
    $tests['error_general'] = $error->getMessage();
}

$correcto = $tests['conexion_db']
    && $tests['tabla_estudiantes_existe']
    && $tests['tabla_libros_existe']
    && $tests['tabla_prestamos_existe'];
http_response_code($correcto ? 200 : 503);

echo json_encode([
    'status' => $correcto ? 'success' : 'error',
    'tests' => $tests,
    'mensaje' => $correcto ? 'PHP y la base de datos funcionan correctamente' : 'PHP funciona, pero la conexión o el esquema de la base de datos no está listo.'
]);
?>
