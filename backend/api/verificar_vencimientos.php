<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../services/verificar_vencimientos.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') responderNoAutorizado('Método no permitido.', 405);
exigirAdministrador();
validarCsrf();
echo json_encode(['status' => 'success', 'resultado' => verificarVencimientos($conexion)], JSON_UNESCAPED_UNICODE);
$conexion->close();
