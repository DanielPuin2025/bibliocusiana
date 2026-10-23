-- =====================================================
-- Script de Actualización: Sistema de 15 Días
-- =====================================================
-- Este script corrige todos los problemas identificados
-- Ejecutar en phpMyAdmin o línea de comandos MySQL
-- =====================================================

USE biblioteca_db;

-- 1. ACTUALIZAR POLÍTICAS DE PRÉSTAMO A 15 DÍAS
-- =====================================================
UPDATE politicas_prestamo
SET dias_prestamo = 15
WHERE categoria_prestamo = 'General' AND rol = 'Estudiante';

UPDATE politicas_prestamo
SET dias_prestamo = 15
WHERE categoria_prestamo = 'General' AND rol = 'Profesor';

-- Verificar cambios
SELECT * FROM politicas_prestamo;

-- 2. CORREGIR RUTAS DE IMÁGENES
-- =====================================================
-- Actualizar rutas de imágenes que no tienen el prefijo correcto
UPDATE libros
SET imagen = CONCAT('IMAGES-LIBROS/', imagen)
WHERE imagen IS NOT NULL
  AND imagen NOT LIKE 'IMAGES-LIBROS/%'
  AND imagen NOT LIKE 'imagenes/%'
  AND imagen NOT LIKE 'https://%'
  AND imagen NOT LIKE 'assets/%'
  AND imagen <> '';

-- Asignar imagen por defecto a libros sin imagen
UPDATE libros
SET imagen_url = 'assets/portada-no-disponible.svg'
WHERE (imagen IS NULL OR imagen = '')
  AND (imagen_url IS NULL OR imagen_url = '');

-- Verificar rutas de imágenes
SELECT id, titulo, imagen, imagen_url FROM libros LIMIT 10;

-- 3. SINCRONIZAR DISPONIBILIDAD DE LIBROS
-- =====================================================
UPDATE libros
SET disponible = (ejemplares_disponibles > 0),
    estado = IF(ejemplares_disponibles > 0, 'Disponible', 'Prestado')
WHERE 1=1;

-- Verificar sincronización
SELECT id, titulo, ejemplares_total, ejemplares_disponibles, disponible, estado
FROM libros
LIMIT 10;

-- 4. VERIFICAR ESTRUCTURA DE TABLAS
-- =====================================================
-- Verificar que existan todas las tablas necesarias
SHOW TABLES;

-- Verificar estructura de notificaciones
DESC notificaciones_prestamo;
DESC notificaciones_whatsapp;

-- 5. LIMPIAR NOTIFICACIONES ANTIGUAS (OPCIONAL)
-- =====================================================
-- Si quieres resetear las notificaciones para probar:
-- DELETE FROM notificaciones_prestamo;
-- DELETE FROM notificaciones_whatsapp;

-- 6. VERIFICAR PRÉSTAMOS ACTIVOS
-- =====================================================
SELECT
    p.id,
    e.nombre AS usuario,
    l.titulo AS libro,
    p.fecha_prestamo,
    p.fecha_devolucion_esperada,
    DATEDIFF(p.fecha_devolucion_esperada, NOW()) AS dias_restantes,
    p.estado
FROM prestamos p
INNER JOIN estudiantes e ON e.id = p.estudiante_id
INNER JOIN libros l ON l.id = p.libro_id
WHERE p.estado = 'Activo'
ORDER BY p.fecha_devolucion_esperada ASC;

-- 7. VERIFICAR USUARIOS CON WHATSAPP
-- =====================================================
SELECT
    id,
    nombre,
    telefono_whatsapp,
    whatsapp_consentimiento
FROM estudiantes
WHERE telefono_whatsapp IS NOT NULL
  AND telefono_whatsapp <> '';

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
-- Ejecutar: SELECT 'Script ejecutado correctamente' AS mensaje;
SELECT 'Script ejecutado correctamente. Revisa los resultados arriba.' AS mensaje;
