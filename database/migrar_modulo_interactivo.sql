-- Ejecutar una sola vez en instalaciones que ya tenían la tabla resenas.
-- Las reseñas antiguas quedan con prestamo_id NULL porque no existe suficiente
-- historial para asociarlas de forma fiable; las nuevas siempre guardan el préstamo.
ALTER TABLE resenas
    ADD COLUMN IF NOT EXISTS prestamo_id INT NULL AFTER estudiante_id;

ALTER TABLE resenas
    ADD CONSTRAINT fk_resena_prestamo
        FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE,
    ADD UNIQUE KEY uq_resena_prestamo (prestamo_id);

-- Para instalaciones creadas desde cero, usar requerimientos_bibliocusiana.sql:
-- allí prestamo_id se crea como NOT NULL.
