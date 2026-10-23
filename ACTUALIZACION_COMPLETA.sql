-- =====================================================
-- ACTUALIZACIÓN COMPLETA DEL SISTEMA
-- Incluye: Políticas, ISBNs, Imágenes
-- =====================================================

USE biblioteca_db;

-- ==========================================
-- 1. POLÍTICAS DE PRÉSTAMO (15 DÍAS)
-- ==========================================
UPDATE politicas_prestamo SET dias_prestamo = 15 WHERE categoria_prestamo = 'General' AND rol = 'Estudiante';
UPDATE politicas_prestamo SET dias_prestamo = 15 WHERE categoria_prestamo = 'General' AND rol = 'Profesor';

-- ==========================================
-- 2. CORREGIR RUTAS DE IMÁGENES LOCALES
-- ==========================================
UPDATE libros
SET imagen = CONCAT('IMAGES-LIBROS/', imagen)
WHERE imagen IS NOT NULL
  AND imagen <> ''
  AND imagen NOT LIKE 'IMAGES-LIBROS/%'
  AND imagen NOT LIKE 'https://%'
  AND imagen NOT LIKE 'imagenes/%'
  AND imagen NOT LIKE 'assets/%';

-- ==========================================
-- 3. AGREGAR ISBNs PARA PORTADAS AUTOMÁTICAS
-- ==========================================

-- Libros clásicos universales
UPDATE libros SET isbn = '9780307474728' WHERE titulo LIKE '%Cien años%' OR titulo LIKE '%cien años%';
UPDATE libros SET isbn = '9780156012195' WHERE titulo LIKE '%Principito%' OR titulo LIKE '%principito%';
UPDATE libros SET isbn = '9780451524935' WHERE titulo LIKE '%1984%';
UPDATE libros SET isbn = '9780060934347' WHERE titulo LIKE '%Don Quijote%' OR titulo LIKE '%Quijote%';
UPDATE libros SET isbn = '9780141439518' WHERE titulo LIKE '%Orgullo%' OR titulo LIKE '%prejuicio%';
UPDATE libros SET isbn = '9788420412146' WHERE titulo LIKE '%Alquimista%';
UPDATE libros SET isbn = '9780743273565' WHERE titulo LIKE '%Gran Gatsby%';
UPDATE libros SET isbn = '9780061120084' WHERE titulo LIKE '%Matar%' OR titulo LIKE '%ruiseñor%';
UPDATE libros SET isbn = '9780142437247' WHERE titulo LIKE '%Catcher%' OR titulo LIKE '%Guardián%';
UPDATE libros SET isbn = '9780544003415' WHERE titulo LIKE '%Señor%' OR titulo LIKE '%anillos%';

-- Libros juveniles populares
UPDATE libros SET isbn = '9780439708180' WHERE titulo LIKE '%Harry Potter%' AND titulo LIKE '%piedra%';
UPDATE libros SET isbn = '9780439064873' WHERE titulo LIKE '%Harry Potter%' AND titulo LIKE '%cámara%';
UPDATE libros SET isbn = '9780439136365' WHERE titulo LIKE '%Harry Potter%' AND titulo LIKE '%prisionero%';
UPDATE libros SET isbn = '9780545162074' WHERE titulo LIKE '%Hambre%' OR titulo LIKE '%Hunger%';
UPDATE libros SET isbn = '9780062024039' WHERE titulo LIKE '%Divergente%';
UPDATE libros SET isbn = '9780316769488' WHERE titulo LIKE '%Crepúsculo%' OR titulo LIKE '%Twilight%';
UPDATE libros SET isbn = '9780062315007' WHERE titulo LIKE '%culpa%' OR titulo LIKE '%estrellas%';

-- Literatura latinoamericana
UPDATE libros SET isbn = '9788437604947' WHERE titulo LIKE '%Rayuela%';
UPDATE libros SET isbn = '9788420633473' WHERE titulo LIKE '%casa%' OR titulo LIKE '%espíritus%';
UPDATE libros SET isbn = '9788466331470' WHERE titulo LIKE '%Código%' OR titulo LIKE '%Vinci%';
UPDATE libros SET isbn = '9788466318648' WHERE titulo LIKE '%Cometa%';
UPDATE libros SET isbn = '9788408163374' WHERE titulo LIKE '%sombra%' OR titulo LIKE '%viento%';

-- Ciencia y divulgación (Yakov Perelman y otros)
UPDATE libros SET isbn = '9785030009841' WHERE titulo LIKE '%Álgebra recreativa%' OR titulo LIKE '%Algebra recreativa%';
UPDATE libros SET isbn = '9785030009858' WHERE titulo LIKE '%Física%' AND titulo LIKE '%divertida%';
UPDATE libros SET isbn = '9785030009865' WHERE titulo LIKE '%Química recreativa%' OR titulo LIKE '%Quimica recreativa%';
UPDATE libros SET isbn = '9788437611112' WHERE titulo LIKE '%Ciencia mágica%' OR titulo LIKE '%Ciencia magica%';

-- Clásicos infantiles
UPDATE libros SET isbn = '9780064401883' WHERE titulo LIKE '%Telaraña%' OR titulo LIKE '%Charlotte%';
UPDATE libros SET isbn = '9780064404990' WHERE titulo LIKE '%Donde viven%' OR titulo LIKE '%monstruos%';
UPDATE libros SET isbn = '9780064430180' WHERE titulo LIKE '%Oruga%' OR titulo LIKE '%hambrienta%';

-- Literatura clásica adicional
UPDATE libros SET isbn = '9780486284736' WHERE titulo LIKE '%Frankenstein%';
UPDATE libros SET isbn = '9780486406510' WHERE titulo LIKE '%Drácula%' OR titulo LIKE '%Dracula%';
UPDATE libros SET isbn = '9780486415871' WHERE titulo LIKE '%Jekyll%' OR titulo LIKE '%Hyde%';
UPDATE libros SET isbn = '9780141439556' WHERE titulo LIKE '%Madame Bovary%';
UPDATE libros SET isbn = '9780486270586' WHERE titulo LIKE '%Crimen%' OR titulo LIKE '%castigo%';

-- Aventuras clásicas
UPDATE libros SET isbn = '9780141321004' WHERE titulo LIKE '%20.000 leguas%' OR titulo LIKE '%veinte mil leguas%';
UPDATE libros SET isbn = '9780141321073' WHERE titulo LIKE '%Viaje%' OR titulo LIKE '%centro%' OR titulo LIKE '%tierra%';
UPDATE libros SET isbn = '9780141321080' WHERE titulo LIKE '%Vuelta%' OR titulo LIKE '%mundo%' OR titulo LIKE '%80 días%';
UPDATE libros SET isbn = '9780143039082' WHERE titulo LIKE '%Robinson%' OR titulo LIKE '%Crusoe%';
UPDATE libros SET isbn = '9780141321462' WHERE titulo LIKE '%Isla%' OR titulo LIKE '%tesoro%';

-- ==========================================
-- 4. SINCRONIZAR DISPONIBILIDAD
-- ==========================================
UPDATE libros
SET disponible = (ejemplares_disponibles > 0),
    estado = IF(ejemplares_disponibles > 0, 'Disponible', 'Prestado');

-- ==========================================
-- 5. VERIFICACIONES
-- ==========================================

SELECT '===== POLÍTICAS ACTUALIZADAS =====' as ' ';
SELECT * FROM politicas_prestamo;

SELECT '===== LIBROS CON ISBN (Portadas automáticas) =====' as ' ';
SELECT id, titulo, autor, isbn FROM libros WHERE isbn IS NOT NULL AND isbn <> '' LIMIT 10;

SELECT '===== LIBROS SIN ISBN (Necesitan agregar) =====' as ' ';
SELECT id, titulo, autor FROM libros WHERE isbn IS NULL OR isbn = '' LIMIT 10;

SELECT '===== RESUMEN GENERAL =====' as ' ';
SELECT
    COUNT(*) as total_libros,
    SUM(CASE WHEN isbn IS NOT NULL AND isbn <> '' THEN 1 ELSE 0 END) as con_isbn,
    SUM(CASE WHEN isbn IS NULL OR isbn = '' THEN 1 ELSE 0 END) as sin_isbn,
    SUM(CASE WHEN disponible = 1 THEN 1 ELSE 0 END) as disponibles
FROM libros;

SELECT '✅ ACTUALIZACIÓN COMPLETADA EXITOSAMENTE' as RESULTADO;
