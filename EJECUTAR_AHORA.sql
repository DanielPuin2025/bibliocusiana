-- =====================================================
-- SCRIPT FINAL DE ACTUALIZACIÓN
-- Ejecutar en phpMyAdmin AHORA
-- =====================================================

USE biblioteca_db;

-- 1. Actualizar políticas a 15 días
UPDATE politicas_prestamo SET dias_prestamo = 15 WHERE categoria_prestamo = 'General' AND rol = 'Estudiante';
UPDATE politicas_prestamo SET dias_prestamo = 15 WHERE categoria_prestamo = 'General' AND rol = 'Profesor';

-- 2. Corregir rutas de imágenes
UPDATE libros
SET imagen = CONCAT('IMAGES-LIBROS/', imagen)
WHERE imagen IS NOT NULL
  AND imagen <> ''
  AND imagen NOT LIKE 'IMAGES-LIBROS/%'
  AND imagen NOT LIKE 'https://%'
  AND imagen NOT LIKE 'imagenes/%'
  AND imagen NOT LIKE 'assets/%';

-- 3. Sincronizar disponibilidad
UPDATE libros
SET disponible = (ejemplares_disponibles > 0),
    estado = IF(ejemplares_disponibles > 0, 'Disponible', 'Prestado');

-- 4. Verificar resultados
SELECT 'Políticas actualizadas:' as Mensaje;
SELECT * FROM politicas_prestamo;

SELECT 'Primeros 5 libros con rutas corregidas:' as Mensaje;
SELECT id, titulo, imagen, disponible FROM libros LIMIT 5;

SELECT '✅ SCRIPT EJECUTADO CORRECTAMENTE' as Resultado;
