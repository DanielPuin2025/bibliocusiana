<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/auth.php';
iniciarSesionSegura();
validarCsrf();
$_SESSION = [];
session_destroy();
echo json_encode(['status' => 'success']);
