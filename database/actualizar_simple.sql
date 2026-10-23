-- =====================================================
-- Script Simple de Actualización
-- Ejecutar línea por línea en phpMyAdmin
-- =====================================================

USE biblioteca_db;

-- 1. Actualizar políticas a 15 días
UPDATE politicas_prestamo SET dias_prestamo = 15 WHERE categoria_prestamo = 'General' AND rol = 'Estudiante';

UPDATE politicas_prestamo SET dias_prestamo = 15 WHERE categoria_prestamo = 'General' AND rol = 'Profesor';

-- 2. Corregir rutas de imágenes
UPDATE libros SET imagen = CONCAT('IMAGES-LIBROS/', imagen) WHERE imagen IS NOT NULL AND imagen NOT LIKE 'IMAGES-LIBROS/%' AND imagen NOT LIKE 'https://%' AND imagen NOT LIKE 'assets/%' AND imagen <> '';

-- 3. Sincronizar disponibilidad
UPDATE libros SET disponible = (ejemplares_disponibles > 0), estado = IF(ejemplares_disponibles > 0, 'Disponible', 'Prestado');

-- 4. Verificar cambios
SELECT * FROM politicas_prestamo;

SELECT id, titulo, imagen FROM libros LIMIT 10;
