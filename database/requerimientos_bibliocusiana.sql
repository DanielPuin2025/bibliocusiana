USE biblioteca_db;

-- Metadatos pedagógicos usados por la búsqueda avanzada.
ALTER TABLE libros
    ADD COLUMN materia VARCHAR(80) NULL AFTER sinopsis,
    ADD COLUMN genero_literario VARCHAR(80) NULL AFTER materia,
    ADD COLUMN grado_escolar VARCHAR(30) NULL AFTER genero_literario,
    ADD COLUMN edad_minima TINYINT UNSIGNED NULL AFTER grado_escolar,
    ADD COLUMN edad_maxima TINYINT UNSIGNED NULL AFTER edad_minima,
    ADD COLUMN categoria_prestamo VARCHAR(50) NOT NULL DEFAULT 'General' AFTER edad_maxima;

CREATE INDEX idx_libros_filtros ON libros (materia, genero_literario, grado_escolar);

-- La combinación más específica (categoría + rol) tiene prioridad.
CREATE TABLE IF NOT EXISTS politicas_prestamo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_prestamo VARCHAR(50) NOT NULL DEFAULT 'General',
    rol ENUM('Estudiante', 'Profesor') NOT NULL,
    dias_prestamo TINYINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_politica_categoria_rol (categoria_prestamo, rol)
);

INSERT INTO politicas_prestamo (categoria_prestamo, rol, dias_prestamo) VALUES
('General', 'Estudiante', 7),
('General', 'Profesor', 14),
('Referencia', 'Estudiante', 3),
('Referencia', 'Profesor', 7),
('Literatura', 'Estudiante', 10),
('Literatura', 'Profesor', 14)
ON DUPLICATE KEY UPDATE dias_prestamo = VALUES(dias_prestamo);

CREATE TABLE IF NOT EXISTS resenas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    libro_id INT NOT NULL,
    estudiante_id INT NOT NULL,
    prestamo_id INT NOT NULL,
    calificacion TINYINT UNSIGNED NOT NULL,
    comentario VARCHAR(1000) NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_resena_calificacion CHECK (calificacion BETWEEN 1 AND 5),
    CONSTRAINT fk_resena_libro FOREIGN KEY (libro_id) REFERENCES libros(id) ON DELETE CASCADE,
    CONSTRAINT fk_resena_estudiante FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    CONSTRAINT fk_resena_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE,
    UNIQUE KEY uq_resena_prestamo (prestamo_id),
    INDEX idx_resenas_fecha (fecha_creacion)
);

-- Ejemplo de clasificación inicial (completa los demás libros desde el panel o SQL).
UPDATE libros SET materia = 'Lenguaje', genero_literario = 'Novela', grado_escolar = '9°', edad_minima = 14, edad_maxima = 18, categoria_prestamo = 'Literatura' WHERE titulo = 'Cien años de soledad';
