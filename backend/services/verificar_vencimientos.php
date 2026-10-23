<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/prestamos.php';
require_once __DIR__ . '/../config/notificaciones.php';
require_once __DIR__ . '/../config/correo.php';
require_once __DIR__ . '/../config/whatsapp.php';

/**
 * Revisa préstamos activos y envía recordatorios exactamente a 3 días.
 * La tabla notificaciones_prestamo evita reenvíos si el cron se ejecuta más de
 * una vez el mismo día. Se puede ejecutar desde CLI o desde el endpoint admin.
 */
function verificarVencimientos(mysqli $conexion): array {
    $resultado = ['revisados' => 0, 'enviados' => 0, 'whatsapp_enviados' => 0, 'whatsapp_omitidos' => 0, 'omitidos' => 0, 'errores' => []];
    $avisoConfiguracionWhatsapp = false;
    $sql = "SELECT p.id, p.fecha_devolucion_esperada, e.nombre, e.correo, e.telefono_whatsapp, e.whatsapp_consentimiento, l.titulo
            FROM prestamos p
            INNER JOIN estudiantes e ON e.id = p.estudiante_id
            INNER JOIN libros l ON l.id = p.libro_id
            WHERE p.estado = 'Activo' AND p.fecha_devolucion_esperada IS NOT NULL";
    $prestamos = $conexion->query($sql);
    while ($prestamo = $prestamos->fetch_assoc()) {
        $resultado['revisados']++;
        $dias = diasCalendarioRestantes($prestamo['fecha_devolucion_esperada']);
        if (!in_array($dias, DIAS_RECORDATORIO, true)) {
            $resultado['omitidos']++;
            continue;
        }
        $fecha = (new DateTimeImmutable($prestamo['fecha_devolucion_esperada']))->format('d/m/Y');
        $canalEnviado = false;
        $existeCorreo = $conexion->prepare('SELECT id FROM notificaciones_prestamo WHERE prestamo_id = ? AND dias_antes = ? LIMIT 1');
        $existeCorreo->bind_param('ii', $prestamo['id'], $dias);
        $existeCorreo->execute();
        if (!$existeCorreo->get_result()->num_rows && filter_var($prestamo['correo'], FILTER_VALIDATE_EMAIL)) {
            $asunto = 'Tu préstamo del libro ' . $prestamo['titulo'] . ' vence en ' . $dias . ' días';
            $cuerpo = "Hola {$prestamo['nombre']},\n\nLibro prestado: {$prestamo['titulo']}\nFecha de devolución: {$fecha}\nDías restantes: {$dias}\n\nPor favor devuelve el libro en la fecha indicada.\nBiblioteca Escolar";
            $envioCorreo = enviarCorreoPrestamo($prestamo['correo'], $asunto, $cuerpo);
            if ($envioCorreo['enviado']) {
                $registrarCorreo = $conexion->prepare('INSERT INTO notificaciones_prestamo (prestamo_id, dias_antes, enviado_en) VALUES (?, ?, NOW())');
                $registrarCorreo->bind_param('ii', $prestamo['id'], $dias);
                $registrarCorreo->execute();
                $resultado['enviados']++;
                $canalEnviado = true;
            } else $resultado['errores'][] = "Correo préstamo {$prestamo['id']}: {$envioCorreo['mensaje']}";
        }

        $existeWhatsapp = $conexion->prepare('SELECT id FROM notificaciones_whatsapp WHERE prestamo_id = ? AND dias_antes = ? LIMIT 1');
        $existeWhatsapp->bind_param('ii', $prestamo['id'], $dias);
        $existeWhatsapp->execute();
        $yaEnviadoWhatsapp = (bool) $existeWhatsapp->get_result()->num_rows;
        if (!$yaEnviadoWhatsapp && !empty($prestamo['telefono_whatsapp']) && (bool) $prestamo['whatsapp_consentimiento']) {
            $envioWhatsapp = enviarRecordatorioWhatsApp($prestamo['telefono_whatsapp'], $prestamo['titulo'], $fecha, $dias);
            if ($envioWhatsapp['enviado']) {
                $registrarWhatsapp = $conexion->prepare('INSERT INTO notificaciones_whatsapp (prestamo_id, dias_antes, enviado_en) VALUES (?, ?, NOW())');
                $registrarWhatsapp->bind_param('ii', $prestamo['id'], $dias);
                $registrarWhatsapp->execute();
                $resultado['whatsapp_enviados']++;
                $canalEnviado = true;
            } elseif ($envioWhatsapp['configurado']) $resultado['errores'][] = "WhatsApp préstamo {$prestamo['id']}: {$envioWhatsapp['mensaje']}";
        }
        if (!$yaEnviadoWhatsapp && !empty($prestamo['telefono_whatsapp']) && (bool) $prestamo['whatsapp_consentimiento'] && isset($envioWhatsapp) && !$envioWhatsapp['configurado'] && !$avisoConfiguracionWhatsapp) {
            $resultado['errores'][] = 'WhatsApp no configurado: defina WHATSAPP_ACCESS_TOKEN, WHATSAPP_PHONE_NUMBER_ID, WHATSAPP_TEMPLATE_NAME y WHATSAPP_API_VERSION en el entorno de PHP/Apache.';
            $avisoConfiguracionWhatsapp = true;
        }
        if (!$yaEnviadoWhatsapp && (empty($prestamo['telefono_whatsapp']) || !(bool) $prestamo['whatsapp_consentimiento'])) {
            $resultado['whatsapp_omitidos']++;
            $motivoWhatsapp = empty($prestamo['telefono_whatsapp']) ? 'no tiene teléfono registrado' : 'no autorizó recibir recordatorios';
            $resultado['errores'][] = "WhatsApp préstamo {$prestamo['id']} omitido: el usuario {$motivoWhatsapp}.";
        }
        if (!$canalEnviado) $resultado['omitidos']++;
    }
    return $resultado;
}
