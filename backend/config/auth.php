<?php
function iniciarSesionSegura(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $esHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('biblioteca_sesion');
    session_set_cookie_params(['lifetime' => 604800, 'path' => '/', 'secure' => $esHttps, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function responderNoAutorizado(string $mensaje, int $codigo = 401): never {
    http_response_code($codigo);
    echo json_encode(['status' => 'error', 'message' => $mensaje]);
    exit;
}

function usuarioAutenticado(): array {
    iniciarSesionSegura();
    if (empty($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) responderNoAutorizado('Debes iniciar sesion para continuar.');
    return $_SESSION['usuario'];
}

function exigirAdministrador(): array {
    $usuario = usuarioAutenticado();
    if (($usuario['rol'] ?? '') !== 'Administrador') responderNoAutorizado('No tienes permisos de administrador.', 403);
    return $usuario;
}

function validarCsrf(): void {
    iniciarSesionSegura();
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) responderNoAutorizado('Solicitud no valida.', 403);
}

const LIMITE_MAXIMO_PRESTAMOS = 5;
const VALOR_MULTA_LIBRO_VENCIDO = 3000;
