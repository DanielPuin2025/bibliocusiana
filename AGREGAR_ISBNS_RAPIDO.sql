-- =====================================================
-- SCRIPT RÁPIDO: Agregar ISBNs a libros comunes
-- Ejecutar en phpMyAdmin
-- =====================================================

USE biblioteca_db;

-- Clásicos universales más buscados
UPDATE libros SET isbn = '9780307474728' WHERE titulo LIKE '%Cien años%' OR titulo LIKE '%cien años%' OR titulo LIKE '%100 años%';
UPDATE libros SET isbn = '9780156012195' WHERE titulo LIKE '%Principito%' OR titulo LIKE '%principito%' OR titulo LIKE '%petit prince%';
UPDATE libros SET isbn = '9780451524935' WHERE titulo LIKE '%1984%' OR titulo LIKE '%mil novecientos%';
UPDATE libros SET isbn = '9780060934347' WHERE titulo LIKE '%Don Quijote%' OR titulo LIKE '%Quijote%' OR titulo LIKE '%Quixote%';
UPDATE libros SET isbn = '9780141439518' WHERE titulo LIKE '%Orgullo%' OR titulo LIKE '%prejuicio%' OR titulo LIKE '%Pride%';
UPDATE libros SET isbn = '9788420412146' WHERE titulo LIKE '%Alquimista%' OR titulo LIKE '%alquimista%';

-- Harry Potter
UPDATE libros SET isbn = '9780439708180' WHERE titulo LIKE '%Harry Potter%' AND (titulo LIKE '%piedra%' OR titulo LIKE '%filosofal%' OR titulo LIKE '%sorcerer%');
UPDATE libros SET isbn = '9780439064873' WHERE titulo LIKE '%Harry Potter%' AND (titulo LIKE '%cámara%' OR titulo LIKE '%secretos%' OR titulo LIKE '%chamber%');
UPDATE libros SET isbn = '9780439136365' WHERE titulo LIKE '%Harry Potter%' AND (titulo LIKE '%prisionero%' OR titulo LIKE '%Azkaban%');

-- Aventuras clásicas
UPDATE libros SET isbn = '9780141321004' WHERE titulo LIKE '%20000%' OR titulo LIKE '%veinte mil%' OR titulo LIKE '%20.000%' OR titulo LIKE '%leguas%';
UPDATE libros SET isbn = '9780141321073' WHERE titulo LIKE '%Viaje%' AND (titulo LIKE '%centro%' OR titulo LIKE '%tierra%');
UPDATE libros SET isbn = '9780141321080' WHERE titulo LIKE '%Vuelta%' AND (titulo LIKE '%mundo%' OR titulo LIKE '%80%');
UPDATE libros SET isbn = '9780143039082' WHERE titulo LIKE '%Robinson%' OR titulo LIKE '%Crusoe%';
UPDATE libros SET isbn = '9780141321462' WHERE titulo LIKE '%Isla%' AND titulo LIKE '%tesoro%';

-- Julio Verne
UPDATE libros SET isbn = '9780553213119' WHERE titulo LIKE '%Viaje submarino%' OR (titulo LIKE '%20%' AND titulo LIKE '%leguas%');
UPDATE libros SET isbn = '9780486440880' WHERE titulo LIKE '%Miguel Strogoff%';

-- Literatura juvenil
UPDATE libros SET isbn = '9780545162074' WHERE titulo LIKE '%Hambre%' OR titulo LIKE '%Hunger%' OR titulo LIKE '%juegos%';
UPDATE libros SET isbn = '9780062024039' WHERE titulo LIKE '%Divergente%' OR titulo LIKE '%Divergent%';
UPDATE libros SET isbn = '9780062315007' WHERE titulo LIKE '%culpa%' AND titulo LIKE '%estrellas%';

-- Ciencia recreativa (Yakov Perelman)
UPDATE libros SET isbn = '9785030009841' WHERE titulo LIKE '%Álgebra%' AND (titulo LIKE '%recreativa%' OR titulo LIKE '%recreacional%');
UPDATE libros SET isbn = '9785030009858' WHERE titulo LIKE '%Física%' AND (titulo LIKE '%divertida%' OR titulo LIKE '%recreativa%');

-- Otros clásicos
UPDATE libros SET isbn = '9788466331470' WHERE titulo LIKE '%Código%' AND titulo LIKE '%Vinci%';
UPDATE libros SET isbn = '9780486284736' WHERE titulo LIKE '%Frankenstein%';
UPDATE libros SET isbn = '9780486406510' WHERE titulo LIKE '%Drácula%' OR titulo LIKE '%Dracula%';

-- Verificar resultados
SELECT 'Libros con ISBN agregado:' as Resultado;
SELECT id, titulo, isbn FROM libros WHERE isbn IS NOT NULL LIMIT 20;

SELECT 'Libros sin ISBN:' as Resultado;
SELECT id, titulo FROM libros WHERE isbn IS NULL OR isbn = '' LIMIT 10;

SELECT '✅ ISBNs agregados correctamente' as Estado;
