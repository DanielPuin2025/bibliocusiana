-- =====================================================
-- SCRIPT PARA ACTUALIZAR RUTAS DE IMÁGENES
-- Ejecutar en phpMyAdmin
-- =====================================================

USE biblioteca_db;

-- 1. Ver el estado actual de las imágenes
SELECT id, titulo, imagen, imagen_url FROM libros LIMIT 10;

-- 2. Actualizar rutas de imágenes para que incluyan la carpeta correcta
UPDATE libros
SET imagen = CONCAT('IMAGES-LIBROS/', imagen)
WHERE imagen IS NOT NULL
  AND imagen <> ''
  AND imagen NOT LIKE 'IMAGES-LIBROS/%'
  AND imagen NOT LIKE 'https://%'
  AND imagen NOT LIKE 'imagenes/%'
  AND imagen NOT LIKE 'assets/%';

-- 3. Asignar portada por defecto a libros sin imagen
UPDATE libros
SET imagen_url = 'assets/portada-no-disponible.svg'
WHERE (imagen IS NULL OR imagen = '')
  AND (imagen_url IS NULL OR imagen_url = '');

-- 4. Limpiar el campo imagen_url para forzar búsqueda automática
UPDATE libros
SET imagen_url = NULL
WHERE imagen IS NOT NULL
  AND imagen <> ''
  AND imagen_url = 'assets/portada-no-disponible.svg';

-- 5. Verificar los cambios
SELECT id, titulo, imagen, imagen_url FROM libros LIMIT 10;

-- 6. IMPORTANTE: Actualizar políticas a 15 días
UPDATE politicas_prestamo SET dias_prestamo = 15 WHERE categoria_prestamo = 'General';

-- 7. Verificar políticas
SELECT * FROM politicas_prestamo;
