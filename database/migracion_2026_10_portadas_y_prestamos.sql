-- Migración no destructiva para instalaciones existentes de biblioteca_db.
-- No elimina datos ni reemplaza portadas registradas.
USE biblioteca_db;

CREATE TABLE IF NOT EXISTS politicas_prestamo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_prestamo VARCHAR(50) NOT NULL DEFAULT 'General',
    rol ENUM('Estudiante', 'Profesor') NOT NULL,
    dias_prestamo TINYINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_politica_categoria_rol (categoria_prestamo, rol)
);

INSERT INTO politicas_prestamo (categoria_prestamo, rol, dias_prestamo) VALUES
('General', 'Estudiante', 7), ('General', 'Profesor', 14),
('Referencia', 'Estudiante', 3), ('Referencia', 'Profesor', 7),
('Literatura', 'Estudiante', 10), ('Literatura', 'Profesor', 14)
ON DUPLICATE KEY UPDATE dias_prestamo = VALUES(dias_prestamo);

CREATE TABLE IF NOT EXISTS notificaciones_prestamo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prestamo_id INT NOT NULL,
    dias_antes TINYINT UNSIGNED NOT NULL,
    enviado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY un_recordatorio_por_prestamo (prestamo_id, dias_antes),
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS resenas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    libro_id INT NOT NULL,
    estudiante_id INT NOT NULL,
    prestamo_id INT NOT NULL,
    calificacion TINYINT UNSIGNED NOT NULL,
    comentario VARCHAR(1000) NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_resena_calificacion CHECK (calificacion BETWEEN 1 AND 5),
    FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE CASCADE,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE,
    UNIQUE KEY uq_resena_prestamo (prestamo_id)
);
