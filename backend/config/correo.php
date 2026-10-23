<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/** Envía un correo por SMTP. Devuelve el resultado sin exponer credenciales. */
function enviarCorreoPrestamo(string $destinatario, string $asunto, string $cuerpo): array {
    $archivoConfiguracion = __DIR__ . '/mail.php';
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (!is_file($archivoConfiguracion) || !is_file($autoload)) {
        return ['enviado' => false, 'mensaje' => 'Correo no configurado: copie backend/config/mail.php.example como mail.php e instale PHPMailer.'];
    }

    $configuracion = require $archivoConfiguracion;
    try {
        require_once $autoload;
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $configuracion['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $configuracion['username'];
        $mail->Password = $configuracion['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) $configuracion['port'];
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($configuracion['from_email'], $configuracion['from_name']);
        $mail->addAddress($destinatario);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = nl2br(htmlspecialchars($cuerpo, ENT_QUOTES, 'UTF-8'));
        $mail->AltBody = $cuerpo;
        $mail->send();
        return ['enviado' => true, 'mensaje' => 'Correo enviado a ' . $destinatario . '.'];
    } catch (Exception $error) {
        error_log('Error SMTP Biblioteca: ' . $error->getMessage());
        return ['enviado' => false, 'mensaje' => 'No se pudo enviar el correo. Revise la configuración SMTP.'];
    }
}
