<?php
declare(strict_types=1);

function enviarRecordatorioWhatsApp(string $telefono, string $titulo, string $fecha, int $dias): array {
    $token = getenv('WHATSAPP_ACCESS_TOKEN') ?: '';
    $numeroId = getenv('WHATSAPP_PHONE_NUMBER_ID') ?: '';
    $plantilla = getenv('WHATSAPP_TEMPLATE_NAME') ?: '';
    $version = getenv('WHATSAPP_API_VERSION') ?: '';
    if ($token === '' || $numeroId === '' || $plantilla === '' || $version === '') return ['configurado' => false, 'enviado' => false, 'mensaje' => 'WhatsApp no está configurado.'];
    if (!preg_match('/^\d{8,15}$/', $telefono)) return ['configurado' => true, 'enviado' => false, 'mensaje' => 'El usuario no tiene un número WhatsApp válido.'];

    if (!function_exists('curl_init')) return ['configurado' => true, 'enviado' => false, 'mensaje' => 'La extensión cURL de PHP no está habilitada.'];
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $telefono,
        'type' => 'template',
        'template' => [
            'name' => $plantilla,
            'language' => ['code' => 'es'],
            'components' => [[
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $titulo],
                    ['type' => 'text', 'text' => $fecha],
                    ['type' => 'text', 'text' => (string) $dias],
                ],
            ]],
        ],
    ];
    $curl = curl_init("https://graph.facebook.com/{$version}/{$numeroId}/messages");
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 15,
    ]);
    $respuesta = curl_exec($curl);
    $codigo = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($respuesta === false || $codigo < 200 || $codigo >= 300) {
        return ['configurado' => true, 'enviado' => false, 'mensaje' => $error ?: "Meta devolvió HTTP {$codigo}."];
    }
    return ['configurado' => true, 'enviado' => true, 'mensaje' => 'Enviado.'];
}
