# 📸 SISTEMA DE PORTADAS AUTOMÁTICAS - EXPLICACIÓN COMPLETA

## 🎯 ¿CÓMO FUNCIONA?

El sistema busca portadas de libros automáticamente usando **Open Library API**, la biblioteca digital gratuita más grande del mundo.

---

## 🔄 PROCESO AUTOMÁTICO

### 1️⃣ El sistema verifica si el libro tiene ISBN
```javascript
// En main.js, línea 131-132
const isbn = libro.isbn || isbnPorTitulo[libro.titulo];
if (isbn) return `https://covers.openlibrary.org/b/isbn/${isbn}-M.jpg`;
```

### 2️⃣ Construye la URL de Open Library
```
https://covers.openlibrary.org/b/isbn/[ISBN]-M.jpg
```

### 3️⃣ La imagen se carga automáticamente
- No ocupa espacio en tu servidor
- Se cachea en el navegador
- Si no encuentra, muestra portada por defecto

---

## 📋 PARA QUE FUNCIONE NECESITAS:

### **Opción A: Agregar ISBNs a los libros (RECOMENDADO)**

Ejecuta en phpMyAdmin:

```sql
-- Ejemplos de ISBNs comunes
UPDATE libros SET isbn = '9780307474728' WHERE titulo LIKE '%Cien años de soledad%';
UPDATE libros SET isbn = '9780156012195' WHERE titulo LIKE '%Principito%';
UPDATE libros SET isbn = '9788420412146' WHERE titulo LIKE '%Alquimista%';
UPDATE libros SET isbn = '9780451524935' WHERE titulo LIKE '%1984%';
UPDATE libros SET isbn = '9780060934347' WHERE titulo LIKE '%Don Quijote%';
UPDATE libros SET isbn = '9780141439518' WHERE titulo LIKE '%Orgullo y prejuicio%';

-- Ver libros sin ISBN
SELECT id, titulo, autor, isbn FROM libros WHERE isbn IS NULL OR isbn = '';
```

### **Opción B: Usar imágenes locales**

Coloca las imágenes en:
```
frontend/assets/IMAGES-LIBROS/nombre-del-libro.jpg
```

Actualiza la base de datos:
```sql
UPDATE libros 
SET imagen = 'IMAGES-LIBROS/nombre-del-libro.jpg' 
WHERE id = 1;
```

---

## 🔍 CÓMO BUSCAR ISBNs

### Método 1: Google Books
1. Ve a https://books.google.com/
2. Busca el título del libro
3. Copia el ISBN-13 (13 dígitos)

### Método 2: Open Library
1. Ve a https://openlibrary.org/
2. Busca el libro
3. Copia el ISBN

### Método 3: ISBN Search
1. Ve a https://isbnsearch.org/
2. Busca por título o autor
3. Copia el ISBN-13

---

## 💾 SCRIPT PARA ACTUALIZAR MASIVAMENTE

Crea un archivo CSV con tus libros e ISBNs:

```csv
titulo,isbn
"Cien años de soledad","9780307474728"
"El Principito","9780156012195"
"1984","9780451524935"
```

O actualiza manualmente:

```sql
UPDATE libros SET isbn = '9780307474728' WHERE id = 1;
UPDATE libros SET isbn = '9780156012195' WHERE id = 2;
UPDATE libros SET isbn = '9780451524935' WHERE id = 3;
```

---

## 🎨 CÓMO SE VE

**Con ISBN:** 
- Portada real del libro desde Open Library
- Alta calidad
- Automático

**Sin ISBN:**
- Imagen local si existe en IMAGES-LIBROS/
- O portada genérica (portada-no-disponible.svg)

---

## 🔧 VERIFICAR QUE FUNCIONA

1. Agrega un ISBN a un libro:
```sql
UPDATE libros SET isbn = '9780307474728' WHERE id = 1;
```

2. Prueba la URL directamente en el navegador:
```
https://covers.openlibrary.org/b/isbn/9780307474728-M.jpg
```

3. Si ves la portada, ¡funciona! Recarga el catálogo.

---

## 📊 ESTADO ACTUAL DE TUS LIBROS

Para ver qué libros tienen/no tienen ISBN:

```sql
-- Libros CON ISBN (cargarán portada automática)
SELECT id, titulo, autor, isbn FROM libros WHERE isbn IS NOT NULL AND isbn <> '';

-- Libros SIN ISBN (necesitan agregar)
SELECT id, titulo, autor FROM libros WHERE isbn IS NULL OR isbn = '';

-- Total
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN isbn IS NOT NULL AND isbn <> '' THEN 1 ELSE 0 END) as con_isbn,
    SUM(CASE WHEN isbn IS NULL OR isbn = '' THEN 1 ELSE 0 END) as sin_isbn
FROM libros;
```

---

## ✅ RESUMEN RÁPIDO

1. **Agrega ISBNs** a tus libros en la base de datos
2. Las portadas se **cargan automáticamente** de Open Library
3. **No necesitas descargar** miles de imágenes
4. El sistema **cachea** las portadas para velocidad
5. Si no encuentra, muestra **portada por defecto**

**¡Biblioteca digital moderna sin ocupar espacio en tu servidor!** 🚀

---

## 🆘 SOLUCIÓN DE PROBLEMAS

**Las portadas no aparecen:**
- ✅ Verifica que el libro tenga ISBN
- ✅ Prueba la URL manualmente en navegador
- ✅ Limpia cache del navegador (Ctrl + F5)

**Open Library no tiene esa portada:**
- Coloca la imagen manualmente en IMAGES-LIBROS/
- Actualiza el campo `imagen` en la base de datos

**Quiero usar solo imágenes locales:**
- No agregues ISBNs
- Coloca todas las imágenes en IMAGES-LIBROS/
- El sistema usará las locales primero
