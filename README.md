# 📚 Sistema de Gestión de Biblioteca

Sistema web completo para la gestión de préstamos de libros en una biblioteca escolar con roles diferenciados para estudiantes, profesores y administradores.

## ✨ Funcionalidades Principales

### 👥 Sistema de Roles

#### 🎓 Estudiante / 👨‍🏫 Profesor
- Ver catálogo completo de libros
- Conocer disponibilidad en tiempo real
- Solicitar préstamos de libros disponibles
- Ver fecha estimada de devolución para libros prestados
- Consultar su propio historial de préstamos
- Renovar una vez un préstamo activo desde su perfil
- Activar lectura interactiva y publicar una valoración después de la devolución

#### ⚙️ Administrador
- Ver todas las solicitudes de préstamo pendientes
- Aprobar o rechazar solicitudes con notas
- Gestionar devoluciones de libros
- Registrar devoluciones sin multas ni sanciones para el usuario
- Ver estadísticas del sistema:
  - Total de libros y disponibilidad
  - Préstamos activos y vencidos
  - Solicitudes pendientes
  - Préstamos por rol de usuario
- Visualizar fechas de préstamo y devolución esperada

### 📖 Gestión de Libros
- Catálogo completo con título, autor y estado
- Género literario obligatorio y filtros por género
- Estados: Disponible / Prestado
- Información de cuándo estará disponible un libro prestado
- Indicador visual de préstamos vencidos

### 🔄 Flujo de Préstamo
1. **Estudiante/Profesor** solicita un libro
2. Solicitud queda en estado "Pendiente"
3. **Administrador** revisa y aprueba/rechaza
4. Si se aprueba: el libro se marca como prestado y se aplica la política de duración de su género y rol
5. El usuario puede renovar una vez desde su perfil
6. **Administrador** registra la devolución cuando el usuario regresa el libro, sin multas
7. La lectura interactiva se habilita después de la devolución

## 🚀 Instalación

### Requisitos
- XAMPP (Apache + MySQL + PHP)
- Navegador web moderno

### Pasos de Instalación

1. **Clonar o copiar el proyecto** en la carpeta `htdocs` de XAMPP:
   ```
   C:\xampp\htdocs\biblioteca\
   ```

2. **Iniciar XAMPP**:
   - Abrir XAMPP Control Panel
   - Iniciar **Apache** y **MySQL**

3. **Crear el esquema de la base de datos**:
   - Crear la base configurada en `DB_NAME` (localmente, `biblioteca_db`).
   - Importar `database/biblioteca.sql`. El script crea las tablas que falten y no elimina tablas ni registros existentes; no carga usuarios ni libros.

4. **Configurar la conexión** (si es necesario):
   - Configurar `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASSWORD` como variables de entorno de PHP.
   - En XAMPP se mantienen los valores locales predeterminados (`localhost`, `biblioteca_db`, `root` sin contraseña).

5. **Acceder al sistema**:
   - Abrir navegador en: `http://localhost/biblioteca/frontend/index.html`

## 🚀 Despliegue en EasyPanel

1. Crear un servicio de aplicación conectado al repositorio `DanielPuin2025/bibliocusiana` y usar el `Dockerfile` incluido. El contenedor escucha en el puerto `80`; Apache sirve la aplicación desde la raíz y también admite `frontend/`, sin exponer listados de directorios.
2. Crear o seleccionar el servicio MySQL y configurar `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASSWORD` en las variables privadas del servicio web. `DB_HOST` debe ser el nombre de host interno del servicio MySQL que proporciona EasyPanel; no uses `localhost`, porque PHP y MySQL corren en contenedores distintos. El script SQL usa la base `biblioteca_db`, así que `DB_NAME` debe tener ese valor.
3. En el servicio web, asociar el dominio `bibliocusiana.programacionsena.online` y activar HTTPS. Configurar el DNS del subdominio según el destino que indique EasyPanel; la dirección `/projects/sustentaciones2026` es la interfaz de administración, no la URL pública de la aplicación.
4. Inicializar las tablas importando `database/biblioteca.sql` en la base indicada por `DB_NAME`; luego, si se deben conservar datos existentes, restaurar el respaldo por un canal privado y seguro. Los respaldos con usuarios, CSV y hojas Excel no deben subirse al repositorio ni incluirse en imágenes Docker.

El esquema debe importarse también en el servicio MySQL de EasyPanel antes de probar el inicio de sesión o el catálogo. El archivo no contiene usuarios ni libros; restaura esos datos desde un respaldo privado si necesitas conservar el inventario local.

## 🛠️ Estructura del Proyecto

```
biblioteca/
├── frontend/                       # Cliente (HTML, CSS, JS, imágenes)
│   ├── index.html                  # Página principal
│   ├── css/
│   │   └── styles.css              # Estilos del sistema
│   ├── js/
│   │   └── main.js                 # Lógica del frontend
│   └── assets/
│       ├── logo.1.png
│       ├── IMAGES-LIBROS/
│       └── logo-institucion-cusiana.png
├── backend/                        # Servidor (PHP)
│   ├── config/
│   │   ├── conexion.php            # Conexión a base de datos
│   │   └── auth.php                # Sesión, CSRF y permisos
│   ├── api/
│   │   ├── guardar_usuario.php     # Validación de login
│   │   ├── cerrar_sesion.php       # Cierre de sesión
│   │   ├── obtener_libros.php      # Consulta de catálogo
│   │   ├── buscar_libros.php       # Búsqueda de libros
│   │   ├── realizar_prestamo.php   # Crear solicitud de préstamo
│   │   ├── obtener_solicitudes.php # Solicitudes pendientes (admin)
│   │   ├── aprobar_prestamo.php    # Aprobar solicitud (admin)
│   │   ├── rechazar_prestamo.php   # Rechazar solicitud (admin)
│   │   ├── devolver_libro.php      # Registrar devolución (admin)
│   │   ├── obtener_prestamos.php   # Préstamos activos
│   │   ├── obtener_historial.php   # Historial por usuario
│   │   ├── obtener_estadisticas.php # Estadísticas del sistema
│   │   ├── obtener_panel_usuario.php # Panel de estudiante/profesor
│   │   └── obtener_panel_admin.php   # Panel de administrador
│   └── test_conexion.php           # Diagnóstico de conexión/BD
└── database/
   ├── biblioteca.sql              # Script de base de datos
   ├── cargar_primeros_libros.sql  # Carga los libros de PRIMEROS LIBROS.csv
    ├── actualizar_db.sql            # Migración: tabla prestamos
    └── migrar_multas.sql           # Migración: multas
```

## 📊 Base de Datos

### Tablas Principales

- **estudiantes**: Usuarios del sistema (estudiantes, profesores, administradores)
- **libros**: Catálogo de libros con estado
- **prestamos**: Registro de solicitudes y préstamos con fechas y estados
- **politicas_prestamo**: Días permitidos por género y rol
- **resenas**: Valoraciones posteriores a la devolución

### Estados de Préstamo
- **Pendiente**: Solicitud esperando aprobación
- **Aprobado**: Aprobado pero aún no activo
- **Activo**: Préstamo en curso
- **Devuelto**: Libro devuelto
- **Rechazado**: Solicitud rechazada por administrador
- **Perdido**: Préstamo reportado como perdido

## 🎯 Funcionalidades Adicionales Recomendadas

### Implementadas
- ✅ Sistema de solicitudes con aprobación
- ✅ Fechas de devolución esperada
- ✅ Información de disponibilidad
- ✅ Panel de administración completo
- ✅ Historial de préstamos por usuario
- ✅ Búsqueda de libros
- ✅ Estadísticas del sistema

### Sugerencias Futuras
- 🔔 Notificaciones automáticas por correo
- 📧 Recordatorio tres días antes del vencimiento
- 🔄 Renovación única de préstamos
- 📱 Versión móvil optimizada
- 📈 Reportes exportables (PDF/Excel)
- 🏷️ Sistema de categorías de libros
- ⭐ Sistema de calificación de libros
- 📚 Reservas de libros prestados
- 💰 Sistema de multas por retraso

## 🔒 Seguridad

**IMPORTANTE**: Para producción se recomienda:
- Mantener `password_hash` con Argon2id o bcrypt y retirar contraseñas heredadas en texto plano
- Agregar validación CSRF
- Sanitizar todas las entradas
- Usar prepared statements (ya implementado)
- Agregar HTTPS
- Implementar rate limiting
- Sesiones seguras con tokens

## 📝 Notas Técnicas

- El sistema usa **mysqli** con prepared statements para prevenir SQL injection
- Las nuevas contraseñas se almacenan con `password_hash`; los usuarios heredados se migran al iniciar sesión
- El período de préstamo se define por política de género y rol
- Los libros prestados muestran cuántos días faltan para su devolución

## 🤝 Contribuir

Para mejorar el sistema:
1. Revisa las sugerencias futuras
2. Implementa nuevas funcionalidades
3. Mejora la seguridad
4. Optimiza el rendimiento

## 📄 Licencia

Sistema educativo de código abierto.

---

**Desarrollado para gestión de biblioteca escolar** 📚✨

## Recordatorios, devoluciones y renovación

Los préstamos activos reciben un único recordatorio por correo **tres días antes** del vencimiento. El formulario es exclusivo para estudiantes y docentes; ambos eligen la fecha de inicio y la fecha de fin al solicitar el préstamo. El servidor rechaza inicios pasados, fechas finales anteriores al inicio o intervalos mayores a 30 días. La renovación se permite una sola vez sin superar 30 días totales desde el inicio.

El catálogo inicial contiene ocho libros. Las portadas importadas por el formulario se almacenan en `imagenes/` y las portadas del catálogo en `frontend/assets/IMAGES-LIBROS/`.

### Instalación

1. Importe `database/biblioteca.sql` para una instalación nueva. Si la base ya existe, ejecute una vez `database/migrar_integridad_biblioteca.sql` en phpMyAdmin.
2. Instale PHPMailer desde la raíz del proyecto: `composer install`.
3. Copie `backend/config/mail.php.example` a `backend/config/mail.php` y complete los datos SMTP.

Sin `backend/config/mail.php` y PHPMailer instalado, los correos no se envían. El panel de administración muestra el motivo cuando falla un envío. Los recordatorios se procesan una vez, exactamente tres días antes del vencimiento, para préstamos activos con un correo válido.

### Configurar Gmail SMTP

1. Active la verificación en dos pasos en la cuenta de Google que enviará los avisos.
2. En la cuenta de Google cree una **contraseña de aplicación** para “Correo”. No use la contraseña normal de Gmail.
3. En `backend/config/mail.php` deje `smtp.gmail.com`, puerto `587`, y pegue esa contraseña de aplicación en `password`.

El plazo de préstamo no supera 30 días en ninguna política. La renovación única también queda limitada a 30 días totales desde el inicio. El administrador puede usar “Enviar recordatorios pendientes ahora” para comprobar el envío. La tarea diaria real se ejecuta con:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\biblioteca\backend\cron\verificar_vencimientos.php
```

Programe ese comando diariamente en el Programador de tareas de Windows. Envía una sola vez por préstamo el aviso de 3 días antes del vencimiento; el asunto y cuerpo se generan automáticamente con el estudiante, libro y fecha de devolución.

## Importar usuarios, inventario y portadas

Inicie sesión como administrador y use los selectores CSV del panel:

- **Estudiantes:** admite `Estudiante;Tipo;Usuario (Documento);Contraseña Inicial` y formatos con encabezados `documento,nombre,rol,contrasena`. Las contraseñas se guardan con hash y los documentos duplicados se omiten.
- **Docentes:** cargue el archivo separado para docentes; admite `USUARIO;DOC;CONTRASENA`. El sistema asigna el rol `Profesor` automáticamente.
- **Libros:** admite encabezados como `LIBRO,CANTIDAD,ESTADO BUENO O MALO (SI/NO),AUTOR,EDITORIAL` y `titulo,autor,isbn,editorial,anio,sinopsis`. Se omiten duplicados por código o título y autor.

Después de importar libros, el navegador busca portadas en Open Library por título y autor. Si encuentra una, guarda su URL en el campo `libros.imagen` y la muestra en el catálogo; las portadas no disponibles conservan la portada de reemplazo. La búsqueda requiere conexión a Internet y acceso del navegador a `openlibrary.org`.

### Carga del inventario 2026

El inventario `database/INVENTARIO DE BIBLIOTECA 2026.xlsx - inventario nuevo .csv` se cargó en `biblioteca_db.libros` mediante `database/importar_inventario_2026.php`. El CSV no incluye género, por lo que se asigna usando palabras clave del título; cuando el género no se puede determinar con fiabilidad se usa **Consulta**. Las cuatro filas que solo tenían título se cargaron como un ejemplar disponible y autor **Anónimo**. El importador puede ejecutarse de nuevo: actualiza por título y no duplica registros.

### Usuarios de estudiantes y docentes

Los archivos `database/USUARIO.ESTUDIANTE.csv`, `database/USUARIO.DOCENTE.csv` y `database/estudiantes.csv` se importan desde **Inventario → Cargar estudiantes CSV** y **Cargar docentes CSV**. El sistema normaliza los finales de línea de estos archivos, omite documentos ya existentes, asigna el rol según el archivo y guarda las contraseñas con hash. En la carga realizada, los 32 registros de `USUARIO.ESTUDIANTE.csv` ya existían y se omitieron; se agregaron 32 docentes nuevos. La carga posterior de `estudiantes.csv` encontró 230 documentos ya existentes y no modificó esas cuentas. Una línea separadora vacía del CSV docente se reportó como inválida y se ignoró.

Para simular o repetir la carga por consola, use `C:\xampp\php\php.exe database\importar_usuarios_csv.php --dry-run` o `C:\xampp\php\php.exe database\importar_usuarios_csv.php`. La importación es idempotente por documento y no imprime contraseñas.

Para repetir la importación desde la carpeta del proyecto, ejecute `C:\xampp\php\php.exe database\importar_inventario_2026.php`.

## Acciones y permisos

- Los usuarios tienen una sola vista **Préstamos y Pendientes** para sus solicitudes, préstamos activos e historial, además de acciones separadas para **Catálogo** y **Dejar Reseña**. La reseña también está disponible desde el detalle del libro y el servidor exige que el usuario haya devuelto un ejemplar.
- Cada solicitud de préstamo exige un teléfono WhatsApp internacional válido (8 a 15 dígitos con código de país) y consentimiento para recibir recordatorios. El número queda guardado en el perfil y se precarga en futuras solicitudes.
- Para actualizar una base existente con renovación y solicitudes de devolución, ejecute una vez `database/migrar_renovacion_devolucion.sql`. El estudiante puede solicitar la devolución desde **Préstamos y Pendientes**; administración confirma la recepción física antes de marcar el ejemplar como devuelto. La renovación se permite una vez y no puede exceder 30 días desde el inicio.
- Solo el administrador puede crear usuarios desde **Usuarios** en su panel. `registrar_usuario.php` valida sesión, rol administrador y token CSRF; la carga masiva de usuarios también queda protegida por rol.
- El administrador puede descargar el inventario completo como CSV UTF-8 con separador `;` desde el panel. La exportación requiere sesión admin y token CSRF.

## Recordatorios por WhatsApp

Los recordatorios de tres días antes pueden salir por correo y WhatsApp. Ejecute una vez `database/migrar_whatsapp_recordatorios.sql` en instalaciones existentes; las instalaciones nuevas ya incluyen los campos y la bitácora. El alta de usuario permite guardar el teléfono en formato internacional y requiere marcar el consentimiento para enviar WhatsApp.

Configure como variables de entorno de Apache/PHP `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_TEMPLATE_NAME` y `WHATSAPP_API_VERSION` con una versión de Graph API habilitada en su cuenta. El token debe ser un token de sistema de Meta; el número debe pertenecer a una cuenta WhatsApp Business Platform y la plantilla debe estar aprobada en idioma `es`, con tres variables en el cuerpo: título del libro, fecha y días restantes. Nunca guarde el token en el repositorio. La tarea diaria existente envía el mensaje mediante Meta WhatsApp Cloud API y registra cada canal por separado para evitar duplicados. Sin credenciales configuradas, el envío permanece inactivo y el correo funciona como antes.

## Portadas con Google Books

Las portadas externas se guardan en `libros.imagen_url` (`VARCHAR(500)`). En una base existente ejecute `database/migrar_imagen_url_libros.sql`; en instalaciones nuevas la columna está en `database/biblioteca.sql`. El cambio SQL equivalente es:

```sql
ALTER TABLE libros ADD COLUMN imagen_url VARCHAR(500) NULL AFTER imagen;
```

Al registrar un libro, PHP consulta Google Books con título y autor; si Google limita la cuota o no tiene miniatura, consulta Open Library. La carga manual de portada es opcional y queda como respaldo local. Al consultar el catálogo, se procesan hasta tres libros sin URL por petición para rellenar gradualmente los títulos ya existentes; los primeros doce elementos de la vista cargan de inmediato y el resto usa carga diferida. Google Books permite búsquedas públicas sin clave, pero se puede configurar opcionalmente `GOOGLE_BOOKS_API_KEY` en el entorno de Apache/PHP para disponer de la cuota asociada al proyecto.

La petición backend usa Google Books Volumes API y extrae `volumeInfo.imageLinks.thumbnail`:

```php
$consulta = 'intitle:' . $titulo . ' inauthor:' . $autor;
$url = 'https://www.googleapis.com/books/v1/volumes?' . http_build_query([
   'q' => $consulta,
   'maxResults' => 1,
   'printType' => 'books',
]);
$curl = curl_init($url);
curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 4]);
$respuesta = curl_exec($curl);
curl_close($curl);
$datos = json_decode((string) $respuesta, true);
$portada = $datos['items'][0]['volumeInfo']['imageLinks']['thumbnail'] ?? null;
```

El código completo está en `backend/config/portadas.php`. Si ninguna API devuelve portada, se guarda `assets/portada-no-disponible.svg`. La interfaz usa primero `imagen_url`, luego la imagen local subida/importada o la portada heredada por ISBN y finalmente ese marcador:

```html
<img src="<?= htmlspecialchars($libro['imagen_url']) ?>" alt="Portada del libro">
```

En el frontend existente las tarjetas del catálogo y la ficha del libro aplican ese mismo orden y cambian al marcador si la URL remota falla.

## Flujos principales

- **Administrador:** iniciar sesión → abrir **Préstamos y Pendientes** para revisar préstamos y aprobar/rechazar solicitudes → administrar inventario y descargar CSV → crear cuentas asignando rol y consentimiento de WhatsApp → ejecutar recordatorios o dejar activa la tarea diaria.
- **Usuario:** iniciar sesión → solicitar préstamo desde Catálogo → consultar solicitudes, préstamos activos e historial en **Préstamos y Pendientes** → recibir recordatorios autorizados → devolver el libro → abrir su ficha y usar Dejar Reseña.
