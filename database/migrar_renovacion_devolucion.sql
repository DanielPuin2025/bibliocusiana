USE biblioteca_db;

ALTER TABLE prestamos
    ADD COLUMN fecha_solicitud_devolucion TIMESTAMP NULL AFTER fecha_devolucion_real,
    ADD COLUMN renovaciones TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER fecha_reporte_perdida,
    ADD COLUMN ultima_renovacion TIMESTAMP NULL AFTER renovaciones;