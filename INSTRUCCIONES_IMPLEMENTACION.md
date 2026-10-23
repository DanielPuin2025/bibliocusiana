# 🚀 INSTRUCCIONES DE IMPLEMENTACIÓN

## ✅ CAMBIOS REALIZADOS

He corregido todos los problemas identificados en el sistema. Aquí está el resumen:

---

## 📋 CAMBIOS EN EL CÓDIGO

### 1. **Límite de préstamo cambiado de 30 a 15 días**

**Archivos modificados:**
- ✅ `backend/config/prestamos.php` - Constante y validaciones
- ✅ `frontend/js/main.js` - Cálculo de fecha máxima
- ✅ `frontend/index.html` - Texto informativo

### 2. **Recordatorios cambiados de 3 a 1 día antes**

**Archivos modificados:**
- ✅ `backend/config/notificaciones.php` - Días de recordatorio

### 3. **Scripts SQL creados**

**Archivos creados:**
- ✅ `database/actualizar_sistema_15_dias.sql` - Script completo de actualización

### 4. **Configuración de WhatsApp**

**Archivos creados:**
- ✅ `.env.example` - Plantilla de variables de entorno
- ✅ `CONFIGURAR_WHATSAPP.md` - Guía completa paso a paso
- ✅ `test_whatsapp.php` - Script de verificación
- ✅ `ejecutar_recordatorios.bat` - Script para tarea programada

### 5. **Documentación**

**Archivos creados:**
- ✅ `ANALISIS_PROBLEMAS_Y_SOLUCIONES.md` - Análisis completo

---

## 🔧 PASOS PARA IMPLEMENTAR

### PASO 1: Actualizar la Base de Datos

1. Abre phpMyAdmin: `http://localhost/phpmyadmin`
2. Selecciona la base de datos `biblioteca_db`
3. Ve a la pestaña **SQL**
4. Abre el archivo: `database/actualizar_sistema_15_dias.sql`
5. Copia todo el contenido y pégalo en phpMyAdmin
6. Haz clic en **Continuar**
7. Verifica que todos los comandos se ejecuten correctamente

### PASO 2: Verificar las Imágenes

1. Ejecuta esta consulta SQL para verificar las rutas:
```sql
SELECT id, titulo, imagen, imagen_url FROM libros LIMIT 10;
```

2. Las rutas deben verse así:
   - `IMAGES-LIBROS/ALGEBRA RECREATIVA.jpeg`
   - NO como: `ALGEBRA RECREATIVA.jpeg`

3. Si las rutas no se actualizaron automáticamente, ejecuta:
```sql
UPDATE libros
SET imagen = CONCAT('IMAGES-LIBROS/', imagen)
WHERE imagen IS NOT NULL
  AND imagen NOT LIKE 'IMAGES-LIBROS/%'
  AND imagen NOT LIKE 'https://%';
```

### PASO 3: Verificar Logo de Portada

Revisa si existe este archivo:
```
c:\xampp\htdocs\biblioteca\frontend\assets\logo.1.png
```

Si no existe, busca el logo correcto y renómbralo a `logo.1.png` o actualiza la línea 26 de `index.html`.

### PASO 4: Configurar WhatsApp (Opcional pero Recomendado)

1. Lee la guía completa: `CONFIGURAR_WHATSAPP.md`
2. Sigue los pasos para obtener credenciales de Meta
3. Configura las variables de entorno en Apache o crea archivo `.env`
4. Verifica la configuración accediendo a: `http://localhost/biblioteca/test_whatsapp.php`

### PASO 5: Configurar Tarea Programada (Windows)

1. Abre el **Programador de tareas** (Win + R → `taskschd.msc`)
2. Crea una nueva tarea básica:
   - **Nombre:** Recordatorios Biblioteca
   - **Desencadenador:** Diariamente a las 9:00 AM
   - **Acción:** Iniciar programa
   - **Programa:** `c:\xampp\htdocs\biblioteca\ejecutar_recordatorios.bat`
3. En opciones avanzadas, marca:
   - "Ejecutar tanto si el usuario inició sesión como si no"
   - "Ejecutar con los privilegios más altos"

### PASO 6: Probar el Sistema

1. **Probar carga de imágenes:**
   - Accede a: `http://localhost/biblioteca/frontend/index.html`
   - Inicia sesión y verifica que las portadas de los libros se carguen
   - Si aparece imagen por defecto, revisa que existan los archivos en `frontend/assets/IMAGES-LIBROS/`

2. **Probar límite de 15 días:**
   - Intenta crear un préstamo con más de 15 días
   - Debe mostrar error: "El préstamo no puede superar los 15 días"

3. **Probar recordatorios (Manual):**
   - Crea un préstamo de prueba con fecha de devolución = mañana
   - Accede a: `http://localhost/biblioteca/backend/api/verificar_vencimientos.php`
   - Debe enviar recordatorio por correo (y WhatsApp si está configurado)

4. **Verificar desde el panel admin:**
   - Inicia sesión como administrador
   - Ve a "Préstamos y Pendientes"
   - Haz clic en "Enviar recordatorios pendientes ahora"
   - Revisa el resultado

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### Problema: Las imágenes no cargan

**Causa:** Rutas incorrectas en la base de datos

**Solución:**
1. Verifica que exista la carpeta: `frontend/assets/IMAGES-LIBROS/`
2. Ejecuta el script SQL de actualización
3. Verifica las rutas con: `SELECT imagen FROM libros;`

### Problema: Error "El préstamo no puede superar los 30 días"

**Causa:** La base de datos no se actualizó o hay cache

**Solución:**
1. Ejecuta el script SQL completo
2. Limpia el cache del navegador (Ctrl + F5)
3. Reinicia Apache desde XAMPP

### Problema: No se envían recordatorios por WhatsApp

**Causa:** WhatsApp no está configurado

**Solución:**
1. Accede a: `http://localhost/biblioteca/test_whatsapp.php`
2. Revisa qué falta configurar
3. Sigue la guía: `CONFIGURAR_WHATSAPP.md`
4. Por ahora, los recordatorios por **correo sí funcionan**

### Problema: Recordatorios se envían 3 días antes

**Causa:** Cache o configuración antigua

**Solución:**
1. Verifica: `backend/config/notificaciones.php` debe decir `DIAS_RECORDATORIO = [1]`
2. Reinicia Apache
3. Limpia la tabla: `DELETE FROM notificaciones_prestamo;`

---

## 📊 VERIFICAR QUE TODO FUNCIONA

Ejecuta estas consultas SQL para verificar:

```sql
-- 1. Verificar políticas de préstamo
SELECT * FROM politicas_prestamo;
-- Debe mostrar 15 días para Estudiantes y Profesores

-- 2. Verificar rutas de imágenes
SELECT id, titulo, imagen FROM libros WHERE imagen IS NOT NULL LIMIT 5;
-- Las rutas deben incluir "IMAGES-LIBROS/"

-- 3. Verificar préstamos activos
SELECT
    e.nombre,
    l.titulo,
    p.fecha_devolucion_esperada,
    DATEDIFF(p.fecha_devolucion_esperada, NOW()) as dias_restantes
FROM prestamos p
INNER JOIN estudiantes e ON e.id = p.estudiante_id
INNER JOIN libros l ON l.id = p.libro_id
WHERE p.estado = 'Activo';

-- 4. Verificar usuarios con WhatsApp
SELECT nombre, telefono_whatsapp, whatsapp_consentimiento
FROM estudiantes
WHERE telefono_whatsapp IS NOT NULL;
```

---

## 📝 NOTAS IMPORTANTES

1. **Los cambios en el código ya están hechos** - No necesitas editar manualmente
2. **Solo debes ejecutar el script SQL** - Es el paso más importante
3. **WhatsApp es opcional** - El sistema funciona con correo mientras configuras WhatsApp
4. **Backups** - Considera hacer backup de la base de datos antes de ejecutar el script:
   ```
   Exportar desde phpMyAdmin → biblioteca_db → Exportar
   ```

---

## 🎯 RESUMEN RÁPIDO

1. ✅ Código actualizado (ya hecho)
2. ⏳ Ejecutar script SQL (TÚ debes hacerlo)
3. ⏳ Verificar imágenes (TÚ debes hacerlo)
4. ⏳ Configurar WhatsApp (Opcional)
5. ⏳ Configurar tarea programada (TÚ debes hacerlo)

---

## 📞 SOPORTE

Si tienes problemas:
1. Revisa `ANALISIS_PROBLEMAS_Y_SOLUCIONES.md`
2. Ejecuta `test_whatsapp.php` para diagnóstico
3. Revisa logs de Apache: `c:\xampp\apache\logs\error.log`

**¡Todo listo para implementar! 🚀**
