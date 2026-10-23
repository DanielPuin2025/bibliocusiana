-- Ejecutar una vez en phpMyAdmin sobre la base biblioteca_db.
ALTER TABLE estudiantes ADD COLUMN correo VARCHAR(150) NULL UNIQUE AFTER nombre;
ALTER TABLE estudiantes ADD COLUMN grado VARCHAR(50) NULL AFTER correo;
ALTER TABLE estudiantes ADD COLUMN area VARCHAR(80) NULL AFTER grado;
ALTER TABLE libros ADD COLUMN codigo VARCHAR(50) NULL UNIQUE AFTER titulo;
ALTER TABLE libros ADD COLUMN disponible BOOLEAN NOT NULL DEFAULT TRUE AFTER imagen;
ALTER TABLE prestamos ADD COLUMN nombre VARCHAR(100) NULL AFTER id;
ALTER TABLE prestamos ADD COLUMN rol VARCHAR(20) NULL AFTER nombre;
ALTER TABLE prestamos ADD COLUMN curso_area VARCHAR(80) NULL AFTER rol;
ALTER TABLE prestamos ADD COLUMN libro_titulo VARCHAR(200) NULL AFTER libro_id;
ALTER TABLE prestamos ADD COLUMN email VARCHAR(150) NULL AFTER libro_titulo;
ALTER TABLE prestamos ADD COLUMN fecha_devolucion DATE NULL AFTER fecha_devolucion_esperada;

-- Asigna códigos operativos a ejemplares ya existentes para poder prestarlos.
UPDATE libros SET codigo = CONCAT('LIB-', LPAD(id, 6, '0')) WHERE codigo IS NULL;
UPDATE libros SET disponible = (estado = 'Disponible');

CREATE TABLE IF NOT EXISTS notificaciones_prestamo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prestamo_id INT NOT NULL,
    dias_antes TINYINT UNSIGNED NOT NULL,
    enviado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY un_recordatorio_por_prestamo (prestamo_id, dias_antes),
    CONSTRAINT fk_notificacion_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE
);

-- Corrige préstamos activos antiguos para aplicar el nuevo límite institucional.
UPDATE prestamos
SET fecha_devolucion_esperada = DATE_ADD(DATE(fecha_prestamo), INTERVAL 60 DAY)
WHERE estado = 'Activo' AND fecha_prestamo IS NOT NULL;
