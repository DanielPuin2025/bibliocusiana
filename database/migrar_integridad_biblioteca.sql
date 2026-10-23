USE biblioteca_db;

ALTER TABLE estudiantes
    ADD COLUMN IF NOT EXISTS correo VARCHAR(150) NULL UNIQUE AFTER nombre,
    ADD COLUMN IF NOT EXISTS grado VARCHAR(50) NULL AFTER correo,
    ADD COLUMN IF NOT EXISTS area VARCHAR(80) NULL AFTER grado;

ALTER TABLE libros
    ADD COLUMN IF NOT EXISTS codigo VARCHAR(50) NULL UNIQUE AFTER titulo,
    ADD COLUMN IF NOT EXISTS ubicacion VARCHAR(120) NULL AFTER isbn;

UPDATE libros SET genero_literario = 'Consulta' WHERE genero_literario IS NULL OR TRIM(genero_literario) = '';
ALTER TABLE libros
    MODIFY COLUMN genero_literario VARCHAR(80) NOT NULL DEFAULT 'Consulta';

-- Completa instalaciones existentes sin destruir prestamos ni inventario.
ALTER TABLE prestamos
    MODIFY COLUMN estado ENUM('Pendiente', 'Aprobado', 'Activo', 'Devuelto', 'Rechazado', 'Perdido') DEFAULT 'Pendiente',
    ADD COLUMN IF NOT EXISTS nombre VARCHAR(100) NULL AFTER id,
    ADD COLUMN IF NOT EXISTS rol VARCHAR(20) NULL AFTER nombre,
    ADD COLUMN IF NOT EXISTS curso_area VARCHAR(80) NULL AFTER rol,
    ADD COLUMN IF NOT EXISTS libro_titulo VARCHAR(200) NULL AFTER libro_id,
    ADD COLUMN IF NOT EXISTS email VARCHAR(150) NULL AFTER libro_titulo,
    ADD COLUMN IF NOT EXISTS tipo_reposicion VARCHAR(50) NULL AFTER notas,
    ADD COLUMN IF NOT EXISTS detalle_reposicion TEXT NULL AFTER tipo_reposicion,
    ADD COLUMN IF NOT EXISTS fecha_reporte_perdida TIMESTAMP NULL AFTER detalle_reposicion;
ALTER TABLE prestamos
    ADD COLUMN IF NOT EXISTS renovaciones TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS ultima_renovacion TIMESTAMP NULL;

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

UPDATE libros
SET ejemplares_total = GREATEST(1, ejemplares_total),
    ejemplares_disponibles = LEAST(GREATEST(0, ejemplares_disponibles), ejemplares_total),
    disponible = ejemplares_disponibles > 0,
    estado = IF(ejemplares_disponibles > 0, 'Disponible', 'Prestado');
