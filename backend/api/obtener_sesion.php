<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';

iniciarSesionSegura();
if (empty($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
    echo json_encode(['status' => 'guest']);
    exit;
}

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
echo json_encode([
    'status' => 'success',
    'usuario' => $_SESSION['usuario'],
    'csrf_token' => $_SESSION['csrf_token'],
], JSON_UNESCAPED_UNICODE);