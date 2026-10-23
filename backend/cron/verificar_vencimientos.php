<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../services/verificar_vencimientos.php';
$resultado = verificarVencimientos($conexion);
$conexion->close();
echo json_encode($resultado, JSON_UNESCAPED_UNICODE) . PHP_EOL;
