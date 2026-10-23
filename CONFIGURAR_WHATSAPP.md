# 📱 Guía de Configuración de WhatsApp Business API

## Requisitos Previos

1. Cuenta de Facebook/Meta for Developers
2. Número de teléfono exclusivo para WhatsApp Business
3. Documento de identidad y verificación empresarial (para producción)

---

## Paso 1: Crear Aplicación en Meta for Developers

1. Ve a [https://developers.facebook.com/](https://developers.facebook.com/)
2. Inicia sesión con tu cuenta de Facebook
3. Haz clic en **"Mis aplicaciones"** → **"Crear aplicación"**
4. Selecciona **"Empresa"** como tipo de aplicación
5. Completa los datos:
   - Nombre de la aplicación: `Biblioteca Cusiana`
   - Correo de contacto: tu correo
   - Cuenta empresarial (opcional para pruebas)
6. Haz clic en **"Crear aplicación"**

---

## Paso 2: Configurar WhatsApp Business

1. En el panel de tu aplicación, busca **"WhatsApp"** en el menú lateral
2. Haz clic en **"Comenzar"**
3. Selecciona o crea una **Cuenta empresarial de WhatsApp**
4. Agrega un número de teléfono:
   - **Opción A (Pruebas):** Usa el número de prueba que Meta proporciona
   - **Opción B (Producción):** Registra tu propio número

---

## Paso 3: Obtener Credenciales

### Token de Acceso (WHATSAPP_ACCESS_TOKEN)

1. Ve a **WhatsApp** → **Introducción** en el panel
2. En la sección **"Enviar y recibir mensajes"**, busca el campo **"Token de acceso temporal"**
3. Copia el token (válido por 24 horas para pruebas)
4. Para token permanente:
   - Ve a **Configuración** → **Básico**
   - Crea un **Token de acceso del sistema** con permisos de WhatsApp
   - Guárdalo de forma segura (solo se muestra una vez)

### ID del Número de Teléfono (WHATSAPP_PHONE_NUMBER_ID)

1. Ve a **WhatsApp** → **Introducción**
2. En **"Número de teléfono"**, copia el **Phone number ID**
3. Ejemplo: `123456789012345`

---

## Paso 4: Crear Plantilla de Mensaje

Las plantillas deben ser aprobadas por Meta antes de usarse.

1. Ve a **WhatsApp** → **Plantillas de mensajes**
2. Haz clic en **"Crear plantilla"**
3. Configura la plantilla:
   - **Nombre:** `recordatorio_biblioteca`
   - **Categoría:** Utilidad
   - **Idioma:** Español
4. **Contenido del mensaje:**

```
Hola, te recordamos que el libro *{{1}}* debe ser devuelto el *{{2}}*.

Faltan *{{3}} días*.

¡Gracias por usar la Biblioteca El Cusiana! 📚
```

5. **Parámetros:**
   - {{1}} = Título del libro
   - {{2}} = Fecha de devolución
   - {{3}} = Días restantes

6. Haz clic en **"Enviar"** y espera aprobación (puede tardar hasta 24 horas)

---

## Paso 5: Configurar Variables de Entorno

### Opción A: Archivo .env (Recomendado para desarrollo)

1. Crea el archivo `.env` en la raíz del proyecto:
```bash
cd c:\xampp\htdocs\biblioteca
copy .env.example .env
```

2. Edita el archivo `.env` con tus credenciales:
```env
WHATSAPP_ACCESS_TOKEN=EAAxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
WHATSAPP_PHONE_NUMBER_ID=123456789012345
WHATSAPP_TEMPLATE_NAME=recordatorio_biblioteca
WHATSAPP_API_VERSION=v17.0
```

3. Asegúrate de que PHP pueda leer variables de entorno

### Opción B: Configuración en Apache (Recomendado para producción)

1. Abre el archivo `httpd.conf` de Apache:
```
c:\xampp\apache\conf\httpd.conf
```

2. O crea/edita `.htaccess` en la raíz del proyecto:
```
c:\xampp\htdocs\biblioteca\.htaccess
```

3. Agrega las variables:
```apache
SetEnv WHATSAPP_ACCESS_TOKEN "EAAxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
SetEnv WHATSAPP_PHONE_NUMBER_ID "123456789012345"
SetEnv WHATSAPP_TEMPLATE_NAME "recordatorio_biblioteca"
SetEnv WHATSAPP_API_VERSION "v17.0"
```

4. Reinicia Apache:
   - Abre el Panel de Control de XAMPP
   - Detén y vuelve a iniciar Apache

---

## Paso 6: Verificar Configuración

1. Crea un archivo de prueba: `c:\xampp\htdocs\biblioteca\test_whatsapp.php`

```php
<?php
echo "<h2>Verificación de Variables de Entorno</h2>";
echo "<pre>";
echo "TOKEN: " . (getenv('WHATSAPP_ACCESS_TOKEN') ? "✅ Configurado" : "❌ No configurado") . "\n";
echo "PHONE_ID: " . (getenv('WHATSAPP_PHONE_NUMBER_ID') ? "✅ Configurado" : "❌ No configurado") . "\n";
echo "TEMPLATE: " . (getenv('WHATSAPP_TEMPLATE_NAME') ? "✅ Configurado" : "❌ No configurado") . "\n";
echo "VERSION: " . (getenv('WHATSAPP_API_VERSION') ? "✅ Configurado" : "❌ No configurado") . "\n";
echo "</pre>";

// Verificar cURL
if (function_exists('curl_init')) {
    echo "<p>✅ cURL está habilitado</p>";
} else {
    echo "<p>❌ cURL NO está habilitado. Actívalo en php.ini</p>";
}
?>
```

2. Accede a: `http://localhost/biblioteca/test_whatsapp.php`
3. Verifica que todas las variables estén configuradas

---

## Paso 7: Probar Envío de Mensaje

1. Agrega tu número de prueba en Meta:
   - Ve a **WhatsApp** → **Introducción**
   - En **"Para"**, agrega tu número con código de país (ej: 573001234567)
   - Recibirás un código de verificación por WhatsApp

2. Desde el panel administrativo del sistema:
   - Crea un préstamo de prueba
   - Configura la fecha de devolución para mañana
   - Ejecuta manualmente: `http://localhost/biblioteca/backend/api/verificar_vencimientos.php`
   - Deberías recibir el mensaje de WhatsApp

---

## Paso 8: Configurar Tarea Programada (CRON)

### En Windows con XAMPP:

1. Crea el archivo batch: `c:\xampp\htdocs\biblioteca\ejecutar_recordatorios.bat`

```batch
@echo off
"C:\xampp\php\php.exe" "C:\xampp\htdocs\biblioteca\backend\cron\verificar_vencimientos.php"
```

2. Abre el **Programador de tareas** de Windows:
   - Presiona `Win + R` y escribe `taskschd.msc`
   - Haz clic en **"Crear tarea básica"**

3. Configura la tarea:
   - **Nombre:** Recordatorios Biblioteca
   - **Descripción:** Envía recordatorios automáticos de devolución de libros
   - **Desencadenador:** Diariamente a las 9:00 AM
   - **Acción:** Iniciar programa
   - **Programa:** `c:\xampp\htdocs\biblioteca\ejecutar_recordatorios.bat`

4. Configura opciones avanzadas:
   - Marcar: "Ejecutar tanto si el usuario inició sesión como si no"
   - Marcar: "Ejecutar con los privilegios más altos"

---

## Solución de Problemas Comunes

### ❌ Error: "WhatsApp no está configurado"
- Verifica que las variables de entorno estén correctamente configuradas
- Reinicia Apache después de editar httpd.conf o .htaccess

### ❌ Error: "Meta devolvió HTTP 401"
- Tu token de acceso expiró o es inválido
- Genera un nuevo token permanente

### ❌ Error: "Meta devolvió HTTP 404"
- El PHONE_NUMBER_ID es incorrecto
- Verifica el ID en el panel de Meta

### ❌ Error: "Template not found"
- La plantilla no existe o no fue aprobada
- Verifica el nombre exacto en Meta
- Espera la aprobación de la plantilla

### ❌ Error: "Recipient phone number not valid"
- El formato del número debe ser: código de país + número (sin +, espacios ni guiones)
- Ejemplo correcto: `573001234567`

### ❌ No recibo mensajes
- Verifica que el número esté registrado en "Números de prueba" (modo desarrollo)
- En producción, el número debe haber aceptado recibir mensajes de tu negocio

---

## Limitaciones del Modo de Prueba

- Solo puedes enviar mensajes a 5 números registrados
- Token temporal válido por 24 horas
- Máximo 1,000 mensajes gratuitos al mes

## Pasar a Producción

Para uso completo sin limitaciones:

1. Verifica tu cuenta empresarial en Meta
2. Solicita revisión de tu aplicación
3. Agrega un método de pago
4. Los mensajes tienen costo después del límite gratuito

---

## Recursos Adicionales

- [Documentación oficial de WhatsApp Business API](https://developers.facebook.com/docs/whatsapp)
- [Crear plantillas de mensajes](https://developers.facebook.com/docs/whatsapp/message-templates)
- [Precios de WhatsApp Business](https://developers.facebook.com/docs/whatsapp/pricing)

---

## Soporte

Si tienes problemas con la configuración, revisa:
1. Los logs de Apache: `c:\xampp\apache\logs\error.log`
2. Los logs de PHP: `c:\xampp\php\logs\php_error_log`
3. La consola del panel administrativo del sistema

**¡Listo! Tu sistema de notificaciones por WhatsApp está configurado.**
