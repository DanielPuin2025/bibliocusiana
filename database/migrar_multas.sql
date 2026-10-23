-- Migración no destructiva para bases de datos ya creadas.
ALTER TABLE prestamos
    ADD COLUMN IF NOT EXISTS multa DECIMAL(10,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS dias_retraso INT NOT NULL DEFAULT 0;
