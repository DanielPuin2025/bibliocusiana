# Análisis Completo de Problemas del Sistema de Biblioteca

## PROBLEMAS IDENTIFICADOS

### 1. ❌ IMÁGENES NO CARGAN

#### Problema de las Portadas de Libros
**Causa Principal:** Las imágenes en la base de datos tienen rutas incorrectas
- En la tabla `libros`, el campo `imagen` tiene valores como `"ALGEBRA RECREATIVA.jpeg"` 
- El código busca en `assets/IMAGES-LIBROS/ALGEBRA RECREATIVA.jpeg`
- Pero la carpeta correcta existe y tiene los archivos

**Solución:**
```sql
-- Actualizar las rutas de imagen en la base de datos
UPDATE libros 
SET imagen = CONCAT('IMAGES-LIBROS/', imagen) 
WHERE imagen IS NOT NULL 
AND imagen NOT LIKE 'IMAGES-LIBROS/%' 
AND imagen NOT LIKE 'https://%';
```

#### Problema de la Portada de la Página Principal
**Ubicación:** `frontend/index.html` línea 26
```html
<img src="assets/logo.1.png" alt="Logo Biblioteca Colegio" >
```

**Problema:** El archivo se llama `logo.1.png` pero debería verificarse si existe
**Verificar:** ¿Existe el archivo `c:\xampp\htdocs\biblioteca\frontend\assets\logo.1.png`?

---

### 2. ❌ LÍMITE DE PRÉSTAMO NO ES 15 DÍAS

#### Problema Actual
- El sistema está configurado para **30 días máximo**
- La tabla `politicas_prestamo` define:
  - Estudiantes General: **7 días**
  - Profesores General: **14 días**
  - Máximo permitido: **30 días**

**Archivos Afectados:**
1. `backend/config/prestamos.php` - línea 5: `const DIAS_PRESTAMO = 30;`
2. `backend/config/prestamos.php` - líneas 26, 39, 46: Validación de máximo 30 días
3. `frontend/index.html` - línea 88: Texto que dice "hasta 30 días"
4. `frontend/js/main.js` - línea 775: Cálculo de fecha máxima con 30 días
5. Base de datos: Tabla `politicas_prestamo`

**Soluciones Requeridas:**

```php
// backend/config/prestamos.php
const DIAS_PRESTAMO = 15; // Cambiar de 30 a 15

// Línea 26 - Cambiar validación
if ($inicio->diff($fin)->days > 15) {
    throw new InvalidArgumentException('El préstamo no puede superar los 15 días.');
}

// Líneas 39 y 46 - Cambiar límite
return min(15, max(1, (int) $fila['dias_prestamo']));
```

```sql
-- Actualizar políticas en base de datos
UPDATE politicas_prestamo SET dias_prestamo = 15 WHERE categoria_prestamo = 'General' AND rol = 'Estudiante';
UPDATE politicas_prestamo SET dias_prestamo = 15 WHERE categoria_prestamo = 'General' AND rol = 'Profesor';
```

```html
<!-- frontend/index.html línea 88 -->
<p class="registro-ayuda">Elige las fechas de inicio y fin. El préstamo puede durar hasta 15 días.</p>
```

```javascript
// frontend/js/main.js línea 775
const maximo = new Date(año, mes - 1, dia + 15); // Cambiar de 30 a 15
```

---

### 3. ❌ RECORDATORIOS AUTOMÁTICOS NO FUNCIONAN CORRECTAMENTE

#### Problema 1: Recordatorio debe ser 1 día antes (no 3 días)
**Archivo:** `backend/config/notificaciones.php`
```php
// ACTUAL (línea 4):
const DIAS_RECORDATORIO = [3];

// DEBE SER:
const DIAS_RECORDATORIO = [1]; // Enviar recordatorio 1 día antes
```

#### Problema 2: WhatsApp no está configurado
El sistema necesita variables de entorno que no existen:

**Archivo faltante:** `.env` en la raíz del proyecto

**Variables requeridas:**
```env
WHATSAPP_ACCESS_TOKEN=tu_token_de_meta
WHATSAPP_PHONE_NUMBER_ID=tu_id_de_numero
WHATSAPP_TEMPLATE_NAME=recordatorio_biblioteca
WHATSAPP_API_VERSION=v17.0
```

**Configuración en Apache (httpd.conf o .htaccess):**
```apache
SetEnv WHATSAPP_ACCESS_TOKEN "tu_token"
SetEnv WHATSAPP_PHONE_NUMBER_ID "tu_id"
SetEnv WHATSAPP_TEMPLATE_NAME "recordatorio_biblioteca"
SetEnv WHATSAPP_API_VERSION "v17.0"
```

#### Problema 3: El CRON no está configurado
El archivo `backend/cron/verificar_vencimientos.php` existe pero no se ejecuta automáticamente.

**Solución para Windows con XAMPP:**

1. Crear archivo BAT: `c:\xampp\htdocs\biblioteca\ejecutar_recordatorios.bat`
```batch
@echo off
"C:\xampp\php\php.exe" "C:\xampp\htdocs\biblioteca\backend\cron\verificar_vencimientos.php"
```

2. Programar tarea en Windows:
   - Abrir "Programador de tareas"
   - Crear tarea básica
   - Nombre: "Recordatorios Biblioteca"
   - Ejecutar diariamente a las 9:00 AM
   - Acción: Iniciar programa
   - Programa: `c:\xampp\htdocs\biblioteca\ejecutar_recordatorios.bat`

#### Problema 4: Los recordatorios se calculan en días calendario (no hábiles)
El código actual usa días calendario. El sistema calcula correctamente pero la documentación debe ser clara.

---

### 4. ❌ ESTRUCTURA DE BASE DE DATOS - PROBLEMAS

#### Problema 1: Campo `imagen` permite NULL pero no tiene valor predeterminado
```sql
-- Verificar registros sin imagen
SELECT id, titulo, imagen, imagen_url FROM libros WHERE imagen IS NULL;

-- Actualizar registros sin ruta de imagen
UPDATE libros 
SET imagen_url = 'assets/portada-no-disponible.svg' 
WHERE imagen IS NULL AND (imagen_url IS NULL OR imagen_url = '');
```

#### Problema 2: Tabla `notificaciones_whatsapp` existe pero no se usa correctamente
La tabla está bien estructurada. El problema es la falta de configuración de WhatsApp.

#### Problema 3: La columna `disponible` puede estar desincronizada
```sql
-- Sincronizar disponibilidad con ejemplares
UPDATE libros 
SET disponible = (ejemplares_disponibles > 0),
    estado = IF(ejemplares_disponibles > 0, 'Disponible', 'Prestado');
```

---

## SOLUCIONES IMPLEMENTADAS

### Archivo de configuración de WhatsApp
Crear: `c:\xampp\htdocs\biblioteca\.env.example`

### Script de actualización de base de datos
Crear: `c:\xampp\htdocs\biblioteca\database\actualizar_sistema_15_dias.sql`

### Documentación para configurar WhatsApp
Crear: `c:\xampp\htdocs\biblioteca\CONFIGURAR_WHATSAPP.md`

---

## RESUMEN DE CAMBIOS NECESARIOS

### Cambios en Base de Datos:
1. ✅ Actualizar rutas de imágenes
2. ✅ Cambiar políticas de préstamo a 15 días
3. ✅ Sincronizar disponibilidad de libros

### Cambios en Backend:
1. ✅ Cambiar límite de 30 a 15 días en `prestamos.php`
2. ✅ Cambiar recordatorio de 3 a 1 día en `notificaciones.php`
3. ✅ Configurar variables de entorno para WhatsApp

### Cambios en Frontend:
1. ✅ Actualizar texto de 30 a 15 días en HTML
2. ✅ Ajustar cálculo de fecha máxima en JavaScript
3. ✅ Verificar carga de imágenes

### Configuración del Sistema:
1. ✅ Configurar CRON/Tarea programada
2. ✅ Configurar API de WhatsApp Business
3. ✅ Verificar archivos de imágenes

---

## PRÓXIMOS PASOS

1. Ejecutar scripts SQL de actualización
2. Modificar archivos PHP y JavaScript
3. Configurar WhatsApp Business API
4. Configurar tarea programada de Windows
5. Probar el sistema completo
