USE biblioteca_db;

ALTER TABLE prestamos
    MODIFY COLUMN estado ENUM('Pendiente', 'Aprobado', 'Activo', 'Devuelto', 'Rechazado', 'Perdido') DEFAULT 'Pendiente',
    ADD COLUMN IF NOT EXISTS tipo_reposicion ENUM('Mismo título y edición', 'Libro equivalente') NULL AFTER notas,
    ADD COLUMN IF NOT EXISTS detalle_reposicion TEXT NULL AFTER tipo_reposicion,
    ADD COLUMN IF NOT EXISTS fecha_reporte_perdida TIMESTAMP NULL AFTER detalle_reposicion;
