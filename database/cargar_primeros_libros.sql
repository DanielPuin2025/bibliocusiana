USE biblioteca_db;
SET NAMES utf8mb4;

-- Libros iniciales de PRIMEROS LIBROS.csv. La carga es idempotente por título.
INSERT INTO libros (titulo, codigo, autor, editorial, ubicacion, imagen, disponible, sinopsis, genero_literario, categoria_prestamo, ejemplares_total, ejemplares_disponibles, estado)
SELECT 'PEQUEÑAS CRIATURAS', 'PRIMEROS-001', 'ALBERTO SÁNCHEZ FIGUEROA', NULL, 'MESA 3', 'PEQUEÑAS CRIATURAS.jpeg', TRUE, NULL, 'Consulta', 'General', 10, 10, 'Disponible'
WHERE NOT EXISTS (SELECT 1 FROM libros WHERE titulo = 'PEQUEÑAS CRIATURAS');

INSERT INTO libros (titulo, codigo, autor, editorial, ubicacion, imagen, disponible, sinopsis, genero_literario, categoria_prestamo, ejemplares_total, ejemplares_disponibles, estado)
SELECT 'CLASICOS JUVENILES DE LA LITERATURA UNIVERSAL', 'PRIMEROS-002', 'ANÓNIMO', NULL, 'MESA 3', 'CLASICOS JUVENILES DE LA LITERATURA UNIVERSAL.jpeg', TRUE, NULL, 'Narrativa', 'Literatura', 10, 10, 'Disponible'
WHERE NOT EXISTS (SELECT 1 FROM libros WHERE titulo = 'CLASICOS JUVENILES DE LA LITERATURA UNIVERSAL');

INSERT INTO libros (titulo, codigo, autor, editorial, ubicacion, imagen, disponible, sinopsis, genero_literario, categoria_prestamo, ejemplares_total, ejemplares_disponibles, estado)
SELECT 'FISICA MAS QUE DIVERTIDA', 'PRIMEROS-003', 'EDUARDO CAMPOS VALADARES', NULL, 'MESA 3', 'FÌSICA MAS QUE DIVERTIDA.jpeg', TRUE, NULL, 'Ciencia Ficción', 'General', 10, 10, 'Disponible'
WHERE NOT EXISTS (SELECT 1 FROM libros WHERE titulo = 'FISICA MAS QUE DIVERTIDA');

INSERT INTO libros (titulo, codigo, autor, editorial, ubicacion, imagen, disponible, sinopsis, genero_literario, categoria_prestamo, ejemplares_total, ejemplares_disponibles, estado)
SELECT 'CIENCIA MAGICA 1', 'PRIMEROS-004', 'INDALECIO VILLARRAGA DÍAZ', NULL, 'MESA 3', 'CIENCIA MAGICA 1.jpeg', TRUE, NULL, 'Ciencia Ficción', 'General', 10, 10, 'Disponible'
WHERE NOT EXISTS (SELECT 1 FROM libros WHERE titulo = 'CIENCIA MAGICA 1');

INSERT INTO libros (titulo, codigo, autor, editorial, ubicacion, imagen, disponible, sinopsis, genero_literario, categoria_prestamo, ejemplares_total, ejemplares_disponibles, estado)
SELECT 'PONGAME UN PROBLEMA', 'PRIMEROS-005', 'BERNARDO RECAMÁN SANTOS', NULL, 'MESA 3', 'PONGAME UN PROBLEMA.jpeg', TRUE, NULL, 'Ensayo', 'General', 10, 10, 'Disponible'
WHERE NOT EXISTS (SELECT 1 FROM libros WHERE titulo = 'PONGAME UN PROBLEMA');

INSERT INTO libros (titulo, codigo, autor, editorial, ubicacion, imagen, disponible, sinopsis, genero_literario, categoria_prestamo, ejemplares_total, ejemplares_disponibles, estado)
SELECT 'QUIMICA RECREATIVA', 'PRIMEROS-006', 'LUIS MIGUEL MORA', NULL, 'MESA 3', 'QUÌMICA RECREATIVA.jpeg', TRUE, NULL, 'Ciencia Ficción', 'General', 10, 10, 'Disponible'
WHERE NOT EXISTS (SELECT 1 FROM libros WHERE titulo = 'QUIMICA RECREATIVA');

-- Corrige datos si el título ya existía en una carga anterior.
UPDATE libros SET autor = 'ALBERTO SÁNCHEZ FIGUEROA', ubicacion = 'MESA 3', imagen = 'PEQUEÑAS CRIATURAS.jpeg', ejemplares_total = 10, ejemplares_disponibles = 10, disponible = TRUE, estado = 'Disponible' WHERE titulo = 'PEQUEÑAS CRIATURAS';
UPDATE libros SET autor = 'ANÓNIMO', ubicacion = 'MESA 3', imagen = 'CLASICOS JUVENILES DE LA LITERATURA UNIVERSAL.jpeg', genero_literario = 'Narrativa', categoria_prestamo = 'Literatura', ejemplares_total = 10, ejemplares_disponibles = 10, disponible = TRUE, estado = 'Disponible' WHERE titulo = 'CLASICOS JUVENILES DE LA LITERATURA UNIVERSAL';
UPDATE libros SET autor = 'EDUARDO CAMPOS VALADARES', ubicacion = 'MESA 3', imagen = 'FÌSICA MAS QUE DIVERTIDA.jpeg', genero_literario = 'Ciencia Ficción', ejemplares_total = 10, ejemplares_disponibles = 10, disponible = TRUE, estado = 'Disponible' WHERE titulo = 'FISICA MAS QUE DIVERTIDA';
UPDATE libros SET autor = 'INDALECIO VILLARRAGA DÍAZ', ubicacion = 'MESA 3', imagen = 'CIENCIA MAGICA 1.jpeg', genero_literario = 'Ciencia Ficción', ejemplares_total = 10, ejemplares_disponibles = 10, disponible = TRUE, estado = 'Disponible' WHERE titulo = 'CIENCIA MAGICA 1';
UPDATE libros SET autor = 'BERNARDO RECAMÁN SANTOS', ubicacion = 'MESA 3', imagen = 'PONGAME UN PROBLEMA.jpeg', genero_literario = 'Ensayo', ejemplares_total = 10, ejemplares_disponibles = 10, disponible = TRUE, estado = 'Disponible' WHERE titulo = 'PONGAME UN PROBLEMA';
UPDATE libros SET autor = 'LUIS MIGUEL MORA', ubicacion = 'MESA 3', imagen = 'QUÌMICA RECREATIVA.jpeg', genero_literario = 'Ciencia Ficción', ejemplares_total = 10, ejemplares_disponibles = 10, disponible = TRUE, estado = 'Disponible' WHERE titulo = 'QUIMICA RECREATIVA';
