# 📚 SISTEMA DE PORTADAS AUTOMÁTICAS - CÓMO FUNCIONA

## 🎯 OBJETIVO
Cargar portadas de libros automáticamente **SIN necesidad de guardar miles de imágenes** en el servidor.

---

## 🔧 CÓMO FUNCIONA

### **Método 1: API de Open Library (Automático)**
El sistema busca portadas en la base de datos gratuita de Open Library.

```
https://covers.openlibrary.org/b/isbn/[ISBN]-M.jpg
```

**Ventajas:**
- ✅ Miles de portadas disponibles
- ✅ No ocupa espacio en tu servidor
- ✅ Se actualiza automáticamente
- ✅ Gratis y sin límites

**Cómo se usa:**
1. El sistema toma el ISBN del libro de tu base de datos
2. Construye la URL de Open Library
3. La imagen se carga directamente desde su servidor
4. Se cachea la URL en `imagen_url` para no buscar cada vez

---

### **Método 2: Imágenes Locales (Manual)**
Para libros específicos que quieras personalizar.

**Ubicación:**
```
frontend/assets/IMAGES-LIBROS/[nombre-archivo].jpg
```

**Cómo se usa:**
1. Coloca la imagen en la carpeta `IMAGES-LIBROS`
2. En la base de datos, campo `imagen`: `IMAGES-LIBROS/nombre-archivo.jpg`
3. El sistema carga primero las imágenes locales

---

### **Método 3: Portada por Defecto**
Si no encuentra ninguna portada.

```
frontend/assets/portada-no-disponible.svg
```

---

## 📊 ORDEN DE PRIORIDAD

El sistema busca las portadas en este orden:

1. **Cache en base de datos** (`imagen_url`)
   - Si ya se buscó antes, usa la URL guardada
   
2. **Imagen local** (`imagen`)
   - Si existe en `IMAGES-LIBROS/`, usa esa

3. **Open Library por ISBN**
   - Busca en Open Library usando el ISBN

4. **Portada por defecto**
   - Muestra la imagen genérica

---

## 🗂️ ESTRUCTURA EN LA BASE DE DATOS

```sql
-- Tabla: libros
isbn VARCHAR(20)           -- ISBN del libro (10 o 13 dígitos)
imagen VARCHAR(255)        -- Ruta local si existe
imagen_url VARCHAR(500)    -- URL cacheada de Open Library
```

**Ejemplos:**

```sql
-- Libro con ISBN (se busca automáticamente)
INSERT INTO libros (titulo, autor, isbn) 
VALUES ('Cien años de soledad', 'García Márquez', '9780307474728');

-- Libro con imagen local
INSERT INTO libros (titulo, autor, imagen) 
VALUES ('Mi libro personalizado', 'Autor Local', 'IMAGES-LIBROS/mi-libro.jpg');

-- Libro sin portada (usará la por defecto)
INSERT INTO libros (titulo, autor) 
VALUES ('Libro sin portada', 'Autor Desconocido');
```

---

## 🚀 CONFIGURACIÓN INICIAL

### **Paso 1: Agregar ISBNs a tus libros**

```sql
-- Actualizar libros existentes con ISBNs
UPDATE libros SET isbn = '9780307474728' WHERE titulo = 'Cien años de soledad';
UPDATE libros SET isbn = '9780141439518' WHERE titulo = 'Orgullo y prejuicio';
UPDATE libros SET isbn = '9780451524935' WHERE titulo = '1984';
```

### **Paso 2: Limpiar cache anterior**

```sql
-- Forzar búsqueda de portadas nuevamente
UPDATE libros SET imagen_url = NULL WHERE imagen IS NULL OR imagen = '';
```

### **Paso 3: Recargar el catálogo**

Accede al catálogo y las portadas se cargarán automáticamente:
```
http://localhost/biblioteca/frontend/index.html
```

---

## 🔍 BÚSQUEDA AUTOMÁTICA DE ISBNs

Si no tienes los ISBNs, puedes buscarlos en:

1. **Google Books:** https://books.google.com/
2. **Open Library:** https://openlibrary.org/
3. **ISBN Search:** https://isbnsearch.org/

**Cómo buscar:**
- Busca el título del libro
- Copia el ISBN-13 (13 dígitos)
- Actualiza tu base de datos

---

## 💾 SCRIPT PARA AGREGAR ISBNs MASIVAMENTE

```sql
-- Lista de ISBNs comunes para libros populares
UPDATE libros SET isbn = '9780307474728' WHERE titulo LIKE '%Cien años%';
UPDATE libros SET isbn = '9780156012195' WHERE titulo LIKE '%Principito%';
UPDATE libros SET isbn = '9780060934347' WHERE titulo LIKE '%Don Quijote%';
UPDATE libros SET isbn = '9780451524935' WHERE titulo LIKE '%1984%';
UPDATE libros SET isbn = '9780141439518' WHERE titulo LIKE '%Orgullo%';
UPDATE libros SET isbn = '9788420412146' WHERE titulo LIKE '%Alquimista%';
UPDATE libros SET isbn = '9788466331470' WHERE titulo LIKE '%Código Da Vinci%';
UPDATE libros SET isbn = '9780439708180' WHERE titulo LIKE '%Harry Potter%';
```

---

## 🎨 PERSONALIZACIÓN

### **Cambiar la portada por defecto:**

1. Reemplaza el archivo:
```
frontend/assets/portada-no-disponible.svg
```

2. O cambia en el código (`main.js`, línea 133):
```javascript
return 'assets/MI-PORTADA-PERSONALIZADA.png';
```

### **Agregar más fuentes de portadas:**

Edita `main.js` línea 125-134, función `obtenerPortadaLibro()`:

```javascript
// Agregar Google Books como fuente alternativa
if (libro.google_books_id) {
    return `https://books.google.com/books/content?id=${libro.google_books_id}&printsec=frontcover&img=1&zoom=1`;
}
```

---

## 📈 VENTAJAS DEL SISTEMA

✅ **Sin almacenamiento:** No ocupas espacio en tu servidor  
✅ **Automático:** Se cargan solas con el ISBN  
✅ **Cache inteligente:** No repite búsquedas innecesarias  
✅ **Fallback:** Siempre muestra algo (portada por defecto)  
✅ **Lazy loading:** Solo carga imágenes visibles (ahorra datos)  
✅ **Escalable:** Soporta miles de libros sin problemas  

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### **Las portadas no se cargan:**

1. Verifica que el libro tenga ISBN:
```sql
SELECT id, titulo, isbn FROM libros WHERE id = 1;
```

2. Prueba la URL manualmente:
```
https://covers.openlibrary.org/b/isbn/9780307474728-M.jpg
```

3. Limpia el cache:
```sql
UPDATE libros SET imagen_url = NULL WHERE id = 1;
```

### **Aparece "Portada no disponible":**

- El libro no tiene ISBN
- El ISBN es incorrecto
- Open Library no tiene esa portada
- **Solución:** Agrega la imagen manualmente en `IMAGES-LIBROS/`

---

## 📝 RESUMEN RÁPIDO

1. **Agrega ISBNs** a tus libros en la base de datos
2. Las portadas se **cargan automáticamente** de Open Library
3. Para libros sin ISBN, coloca la imagen en **IMAGES-LIBROS/**
4. El sistema **cachea** las portadas para velocidad
5. Si no encuentra nada, muestra **portada por defecto**

**¡Listo! Tu biblioteca tiene portadas automáticas sin guardar miles de imágenes.** 🎉

---

## 🔗 RECURSOS

- **Open Library API:** https://openlibrary.org/dev/docs/api/covers
- **Buscar ISBNs:** https://isbnsearch.org/
- **Google Books:** https://books.google.com/
- **Documentación del proyecto:** Ver `README.md`
