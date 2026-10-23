-- Clasificación aproximada del catálogo por palabras del título.
-- Solo completa libros sin materia, con género "Consulta" o categoría "General".
-- Revisar y corregir luego desde el panel de administrador.

UPDATE libros SET materia = CASE
    WHEN titulo LIKE '%matem%' OR titulo LIKE '%aritm%' OR titulo LIKE '%geometr%' OR titulo LIKE '%algebra%' OR titulo LIKE '%calculo%' OR titulo LIKE '%numeros%' THEN 'Matemáticas'
    WHEN titulo LIKE '%english%' OR titulo LIKE '%ingles%' OR titulo LIKE '%vocabulary%' OR titulo LIKE '%dictionary%' THEN 'Inglés'
    WHEN titulo LIKE '%computa%' OR titulo LIKE '%internet%' OR titulo LIKE '%informatic%' OR titulo LIKE '%tecnolog%' OR titulo LIKE '%robot%' THEN 'Tecnología e Informática'
    WHEN titulo LIKE '%quimic%' OR titulo LIKE '%fisica%' OR titulo LIKE '%biolog%' OR titulo LIKE '%experiment%' OR titulo LIKE '%ciencia%' OR titulo LIKE '%cientific%' OR titulo LIKE '%dinosaur%' OR titulo LIKE '%animal%' OR titulo LIKE '%planeta%' OR titulo LIKE '%la tierra%' OR titulo LIKE '%cuerpo%' OR titulo LIKE '%salud%' OR titulo LIKE '% sano%' OR titulo LIKE '%naturale%' OR titulo LIKE '%astronom%' OR titulo LIKE '%ecolog%' OR titulo LIKE '%ambiente%' THEN 'Ciencias Naturales'
    WHEN titulo LIKE '%agricultur%' OR titulo LIKE '%ganader%' OR titulo LIKE '%agropecuari%' OR titulo LIKE '%contabilidad%' OR titulo LIKE '%costos%' OR titulo LIKE '%emprend%' OR titulo LIKE '%comprar%' OR titulo LIKE '%empresa%' OR titulo LIKE '%economia%' THEN 'Emprendimiento y Economía'
    WHEN titulo LIKE '%valores%' OR titulo LIKE '%etica%' OR titulo LIKE '%respeto%' OR titulo LIKE '%respeto%' OR titulo LIKE '%habito%' OR titulo LIKE '%autoestima%' OR titulo LIKE '%quierete%' OR titulo LIKE '%biblic%' OR titulo LIKE '%dios%' OR titulo LIKE '%religi%' OR titulo LIKE '%filosofia%' OR titulo LIKE '%convivir%' OR titulo LIKE '%adolescent%' THEN 'Ética y Valores'
    WHEN titulo LIKE '%colombia%' OR titulo LIKE '%historia%' OR titulo LIKE '%geograf%' OR titulo LIKE '%politic%' OR titulo LIKE '%ciudadan%' OR titulo LIKE '%democracia%' OR titulo LIKE '%paz%' OR titulo LIKE '%sociales%' OR titulo LIKE '%humanidad%' OR titulo LIKE '%guerra%' OR titulo LIKE '%mundo%' OR titulo LIKE '%infancia%' OR titulo LIKE '%educacion%' OR titulo LIKE '%chibcha%' THEN 'Ciencias Sociales'
    WHEN titulo LIKE '%arte%' OR titulo LIKE '%pintur%' OR titulo LIKE '%dibujo%' OR titulo LIKE '%colorear%' OR titulo LIKE '%musica%' OR titulo LIKE '%obregon%' OR titulo LIKE '%artistic%' THEN 'Arte'
    WHEN titulo LIKE '%cuento%' OR titulo LIKE '%leyenda%' OR titulo LIKE '%fabula%' OR titulo LIKE '%poesia%' OR titulo LIKE '%poema%' OR titulo LIKE '%verso%' OR titulo LIKE '%rima%' OR titulo LIKE '%copla%' OR titulo LIKE '%novela%' OR titulo LIKE '%teatro%' OR titulo LIKE '%gramat%' OR titulo LIKE '%espanol%' OR titulo LIKE '%ortograf%' OR titulo LIKE '%lengua%' OR titulo LIKE '%escrit%' OR titulo LIKE '%escribir%' OR titulo LIKE '%lectura%' OR titulo LIKE '%letras%' OR titulo LIKE '%redaccion%' OR titulo LIKE '%refran%' OR titulo LIKE '%comunicacion%' OR titulo LIKE '%quijote%' OR titulo LIKE '%historias%' THEN 'Lenguaje'
    ELSE NULL END
WHERE materia IS NULL OR materia = '';

UPDATE libros SET genero_literario = CASE
    WHEN titulo LIKE '%poesia%' OR titulo LIKE '%poema%' OR titulo LIKE '%verso%' OR titulo LIKE '%rima%' OR titulo LIKE '%copla%' OR titulo LIKE '%poeta%' THEN 'Poesía'
    WHEN titulo LIKE '%teatr%' OR titulo LIKE '%dramatic%' OR titulo LIKE '%vivencia teatral%' THEN 'Teatro'
    WHEN titulo LIKE '%marciano%' OR titulo LIKE '%ciencia ficcion%' OR titulo LIKE '%cronona%' OR titulo LIKE '%espacial%' THEN 'Ciencia Ficción'
    WHEN titulo LIKE '%fantas%' OR titulo LIKE '%anillos%' OR titulo LIKE '%harry potter%' OR titulo LIKE '%magic%' OR titulo LIKE '%dragon%' OR titulo LIKE '%hada%' THEN 'Fantasía'
    WHEN titulo LIKE '%cuento%' OR titulo LIKE '%leyenda%' OR titulo LIKE '%fabula%' OR titulo LIKE '%mitos%' OR titulo LIKE '%pinocho%' OR titulo LIKE '%bella durmiente%' OR titulo LIKE '%caperucita%' OR titulo LIKE '%grimm%' OR titulo LIKE '%llorona%' OR titulo LIKE '%historias de%' OR titulo LIKE '%historias para%' OR titulo LIKE '%espanto%' OR titulo LIKE '%fantasma%' OR titulo LIKE '%travesuras%' OR titulo LIKE '%aventuras%' OR titulo LIKE '%tonina%' THEN 'Cuento'
    WHEN titulo LIKE '%novela%' OR titulo LIKE '%vorag%' OR titulo LIKE '%crónica de una muerte%' OR titulo LIKE '%cronica de una muerte%' OR titulo LIKE '%patriarca%' OR titulo LIKE '%la tregua%' OR titulo LIKE '%jardin secreto%' OR titulo LIKE '%platero%' OR titulo LIKE '%trilog%' OR titulo LIKE '%jack y jill%' THEN 'Novela'
    WHEN titulo LIKE '%ensayo%' OR titulo LIKE '%el principe%' OR titulo LIKE '%opinion%' OR titulo LIKE '%filosofia%' OR titulo LIKE '%reimaginando%' THEN 'Ensayo'
    WHEN titulo LIKE '%biograf%' OR titulo LIKE '%extraordinarios%' OR titulo LIKE '%memorias%' OR titulo LIKE '%que hicieron historia%' OR titulo LIKE '%que cambiaron%' THEN 'Biografía'
    WHEN titulo LIKE '%enciclopedia%' OR titulo LIKE '%diccionario%' OR titulo LIKE '%manual%' OR titulo LIKE '%consultor%' OR titulo LIKE '%atlas%' OR titulo LIKE '%curso%' OR titulo LIKE '%guia%' OR titulo LIKE '%experimentos%' OR titulo LIKE '%matem%' OR titulo LIKE '%quimica%' THEN 'Consulta'
    ELSE genero_literario END
WHERE genero_literario IS NULL OR genero_literario = '' OR genero_literario = 'Consulta';

-- La categoría define los días de préstamo (tabla politicas_prestamo).
UPDATE libros SET categoria_prestamo = CASE
    WHEN titulo LIKE '%enciclopedia%' OR titulo LIKE '%diccionario%' OR titulo LIKE '%manual%' OR titulo LIKE '%consultor%' OR titulo LIKE '%atlas%' OR titulo LIKE '%la biblia de la%' THEN 'Referencia'
    WHEN genero_literario IN ('Narrativa','Poesía','Teatro','Cuento','Novela','Fantasía','Ciencia Ficción','Drama') THEN 'Literatura'
    ELSE categoria_prestamo END
WHERE categoria_prestamo = 'General';
