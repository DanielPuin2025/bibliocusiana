-- Ejecuta este archivo solo si ya habías importado biblioteca.sql antes.
USE biblioteca_db;

ALTER TABLE libros ADD COLUMN IF NOT EXISTS ubicacion VARCHAR(120) NULL AFTER isbn;
ALTER TABLE libros ADD COLUMN IF NOT EXISTS imagen VARCHAR(255) NULL AFTER ubicacion;
