USE biblioteca_db;

ALTER TABLE libros
    ADD COLUMN imagen_url VARCHAR(500) NULL AFTER imagen;