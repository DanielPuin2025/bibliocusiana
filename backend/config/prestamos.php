<?php
declare(strict_types=1);

/** Respaldo para instalaciones que todavía no tienen la tabla de políticas. */
const DIAS_PRESTAMO = 15;

/** La fecha de devolución nunca llega del cliente: siempre se calcula en el servidor. */
function fechaDevolucionPrestamo(DateTimeInterface $fechaPrestamo, int $dias = DIAS_PRESTAMO): DateTimeImmutable {
    return (new DateTimeImmutable($fechaPrestamo->format('Y-m-d')))->modify('+' . max(1, $dias) . ' days');
}

function validarRangoFechasPrestamo(string $inicioTexto, string $finTexto): array {
    $inicio = DateTimeImmutable::createFromFormat('!Y-m-d', $inicioTexto);
    $fin = DateTimeImmutable::createFromFormat('!Y-m-d', $finTexto);
    $erroresInicio = DateTimeImmutable::getLastErrors();
    $erroresFin = DateTimeImmutable::getLastErrors();
    if (!$inicio || !$fin || $inicio->format('Y-m-d') !== $inicioTexto || $fin->format('Y-m-d') !== $finTexto || ($erroresInicio && ($erroresInicio['warning_count'] || $erroresInicio['error_count'])) || ($erroresFin && ($erroresFin['warning_count'] || $erroresFin['error_count']))) {
        throw new InvalidArgumentException('Selecciona fechas válidas para el inicio y el fin del préstamo.');
    }
    if ($inicio < new DateTimeImmutable('today')) {
        throw new InvalidArgumentException('La fecha de inicio no puede ser anterior a hoy.');
    }
    if ($fin <= $inicio) {
        throw new InvalidArgumentException('La fecha de fin debe ser posterior a la fecha de inicio.');
    }
    if ($inicio->diff($fin)->days > 15) {
        throw new InvalidArgumentException('El préstamo no puede superar los 15 días.');
    }
    return [$inicio, $fin];
}

function diasPoliticaPrestamo(mysqli $conexion, string $categoria, string $rol): int {
    try {
        $stmt = $conexion->prepare('SELECT dias_prestamo FROM politicas_prestamo WHERE categoria_prestamo = ? AND rol = ? LIMIT 1');
        if (!$stmt) return DIAS_PRESTAMO;
        $stmt->bind_param('ss', $categoria, $rol);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        if ($fila) return min(15, max(1, (int) $fila['dias_prestamo']));

        $stmt = $conexion->prepare("SELECT dias_prestamo FROM politicas_prestamo WHERE categoria_prestamo = 'General' AND rol = ? LIMIT 1");
        if (!$stmt) return DIAS_PRESTAMO;
        $stmt->bind_param('s', $rol);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        return $fila ? min(15, max(1, (int) $fila['dias_prestamo'])) : DIAS_PRESTAMO;
    } catch (mysqli_sql_exception $error) {
        return DIAS_PRESTAMO;
    }
}

function diasCalendarioRestantes(?string $fechaVencimiento): int {
    if (!$fechaVencimiento) return 0;
    $vencimiento = new DateTimeImmutable(substr($fechaVencimiento, 0, 10));
    return (int) (new DateTimeImmutable('today'))->diff($vencimiento)->format('%r%a');
}

function diasHabilesRetraso(?string $fechaVencimiento): int {
    return max(0, -diasCalendarioRestantes($fechaVencimiento));
}

function estadoPrestamoVisible(string $estado, ?string $fechaVencimiento): string {
    if ($estado !== 'Activo') return $estado === 'Devuelto' ? 'Devuelto' : ($estado === 'Perdido' ? 'Perdido' : $estado);
    $diasRestantes = diasCalendarioRestantes($fechaVencimiento);
    if ($diasRestantes < 0) return 'Vencido';
    return $diasRestantes <= 7 ? 'Próximo a vencer' : 'Prestado';
}
