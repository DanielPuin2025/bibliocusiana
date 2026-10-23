-- Eliminar la tabla prestamos antigua
DROP TABLE IF EXISTS prestamos;

-- Crear tabla prestamos con la nueva estructura
CREATE TABLE IF NOT EXISTS prestamos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    libro_id INT NOT NULL,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_aprobacion TIMESTAMP NULL,
    fecha_prestamo TIMESTAMP NULL,
    fecha_devolucion_esperada TIMESTAMP NULL,
    fecha_devolucion_real TIMESTAMP NULL,
    estado ENUM('Pendiente', 'Aprobado', 'Activo', 'Devuelto', 'Rechazado') DEFAULT 'Pendiente',
    aprobado_por INT NULL,
    notas TEXT NULL,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id),
    FOREIGN KEY (libro_id) REFERENCES libros(id),
    FOREIGN KEY (aprobado_por) REFERENCES estudiantes(id)
);

-- Actualizar todos los libros a estado Disponible para empezar limpio
UPDATE libros SET estado = 'Disponible';
