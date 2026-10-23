document.addEventListener('DOMContentLoaded', () => {
    const pasoRol = document.getElementById('paso-rol');
    const pasoDatos = document.getElementById('paso-datos');
    const pasoBiblioteca = document.getElementById('paso-biblioteca');

    const tituloRol = document.getElementById('titulo-rol');
    const formRegistro = document.getElementById('form-registro');
    const mensajeBienvenida = document.getElementById('mensaje-bienvenida');
    const tablaLibros = document.getElementById('catalogo-libros');
    const contrasenaInput = document.getElementById('contrasena');

    const btnsRol = document.querySelectorAll('.btn-rol');
    const btnAtras1 = document.getElementById('btn-atras-1');
    const btnCerrar = document.getElementById('btn-cerrar');

    let rolSeleccionado = '';
    let usuarioActual = null;
    let csrfToken = '';
    let todosLosLibros = [];
    let vistaActiva = '';
    // La API se resuelve en relación con la carpeta frontend. Así funciona
    // aunque el proyecto se instale con un nombre de carpeta distinto.
    const API_BASE = window.location.protocol === 'file:'
        ? 'http://localhost/biblioteca/backend/api/'
        : new URL('../backend/api/', window.location.href).href;
    const ASSET_BASE = new URL('../frontend/assets/', window.location.href);
    const assetUrl = path => new URL(path, ASSET_BASE).href;

    function guardarFiltros() {
        const filtros = ['buscar-libros', 'filtro-categoria', 'filtro-materia', 'filtro-genero'];
        const estado = Object.fromEntries(filtros.map(id => [id, document.getElementById(id)?.value || '']));
        localStorage.setItem('biblioteca_filtros', JSON.stringify(estado));
    }

    // Los filtros no se conservan al recargar: un filtro olvidado ocultaría opciones de los demás.
    function restaurarFiltros() {
        localStorage.removeItem('biblioteca_filtros');
    }

    async function buscarYGuardarPortadas(libros) {
        if (!libros.length) return 0;
        const respuesta = await fetch(API_BASE + 'guardar_portadas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify({ libros: libros.map(libro => ({ id: libro.id })) })
        });
        const resultado = await respuesta.json();
        if (resultado.status !== 'success') throw new Error(resultado.message || 'No se pudieron guardar las portadas.');
        return resultado.guardadas;
    }

    function conectarCargaCsv(id, endpoint, rol = '') {
        const input = document.getElementById(id);
        if (!input) return;
        input.addEventListener('change', async evento => {
            const archivo = evento.target.files[0];
            if (!archivo) return;
            const formData = new FormData();
            formData.append('archivo', archivo);
            if (rol) formData.append('rol', rol);
            try {
                const respuesta = await fetch(API_BASE + endpoint, {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    body: formData
                });
                const resultado = await respuesta.json();
                if (resultado.status !== 'success') throw new Error(resultado.message || 'No se pudo importar el CSV.');
                let mensaje = resultado.message;
                if (endpoint === 'cargar_libros.php' && resultado.libros?.length) {
                    // Las portadas se buscan en internet y se guardan poco a poco al mostrar cada libro.
                    alert(`${resultado.message}\nLas portadas se buscarán automáticamente al mostrarse en el catálogo.`);
                    cargarLibros();
                } else {
                    if (resultado.errores?.length) mensaje += `\n\nErrores:\n${resultado.errores.slice(0, 5).join('\n')}`;
                    alert(mensaje);
                    if (endpoint === 'cargar_libros.php') cargarLibros();
                }
            } catch (error) {
                console.error('Error al importar CSV:', error);
                alert(error.message || 'Hubo un problema al cargar el archivo.');
            } finally {
                evento.target.value = '';
            }
        });
    }

    conectarCargaCsv('upload-libros', 'cargar_libros.php');
    conectarCargaCsv('upload-estudiantes', 'cargar_estudiantes.php', 'Estudiante');
    conectarCargaCsv('upload-docentes', 'cargar_estudiantes.php', 'Profesor');

    const isbnPorTitulo = {
        'Cien años de soledad': '9780307474728',
        'El principito': '9780156012195',
        'Don Quijote de la Mancha': '9780060934347',
        '1984': '9780451524935',
        'Orgullo y prejuicio': '9780141439518',
        'Crónica de una muerte anunciada': '9781400034956',
        'El amor en los tiempos del cólera': '9780307389732',
        'La metamorfosis': '9780553213690',
        'El código Da Vinci': '9780307474278',
        'Harry Potter y la piedra filosofal': '9780439708180'
    };

    function obtenerPortadaLocal(libro) {
        if (libro.imagen) {
            if (libro.imagen.startsWith('https://covers.openlibrary.org/')) return libro.imagen;
            const rutaImagen = libro.imagen.split('/').map(parte => encodeURIComponent(parte)).join('/');
            return libro.imagen.startsWith('imagenes/')
                ? new URL(`../${rutaImagen}`, window.location.href).href
                : assetUrl(libro.imagen.startsWith('IMAGES-LIBROS/') ? rutaImagen : `IMAGES-LIBROS/${rutaImagen}`);
        }
        return null;
    }

    function obtenerPortadaLibro(libro) {
        // 1. Imagen subida localmente (no consume internet)
        const portadaLocal = obtenerPortadaLocal(libro);
        if (portadaLocal) return portadaLocal;

        // 2. Portada encontrada en internet y guardada en la BD (solo la URL, no el archivo)
        if (libro.imagen_url && libro.imagen_url !== 'assets/portada-no-disponible.svg') {
            return libro.imagen_url.startsWith('assets/')
                ? assetUrl(libro.imagen_url.slice('assets/'.length))
                : libro.imagen_url;
        }

        // 3. Con ISBN se pide directamente a Open Library; si no existe, onerror muestra la imagen por defecto
        const isbn = libro.isbn || isbnPorTitulo[libro.titulo];
        if (isbn) return `https://covers.openlibrary.org/b/isbn/${encodeURIComponent(isbn)}-M.jpg?default=false`;

        return assetUrl('portada-no-disponible.svg');
    }

    function portadaBuscadaRecientemente(libro) {
        if (!libro.portada_buscada_en) return false;
        const fecha = new Date(libro.portada_buscada_en.replace(' ', 'T'));
        return Date.now() - fecha.getTime() < 7 * 24 * 60 * 60 * 1000;
    }

    function usarPortadaRespaldo(imagen, respaldoLocal = null) {
        if (respaldoLocal) imagen.dataset.respaldoLocal = respaldoLocal;
        imagen.onerror = () => {
            const respaldo = imagen.dataset.respaldoLocal;
            if (respaldo && imagen.src !== new URL(respaldo, document.baseURI).href) {
                delete imagen.dataset.respaldoLocal;
                imagen.src = respaldo;
                return;
            }
            imagen.onerror = null;
            imagen.src = assetUrl('portada-no-disponible.svg');
        };
    }

    const colaPortadas = [];
    let procesandoColaPortadas = false;

    async function procesarColaPortadas() {
        if (procesandoColaPortadas) return;
        procesandoColaPortadas = true;
        while (colaPortadas.length) {
            const grupo = colaPortadas.splice(0, 3);
            try {
                const respuesta = await fetch(API_BASE + 'buscar_portadas_catalogo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                    body: JSON.stringify({ ids: grupo.map(item => item.libro.id) })
                });
                const resultado = await respuesta.json();
                if (resultado.status !== 'success') continue;
                resultado.portadas.forEach(portada => {
                    const elemento = grupo.find(item => Number(item.libro.id) === Number(portada.id));
                    if (!elemento) return;
                    elemento.libro.imagen_url = portada.imagen_url;
                    elemento.libro.portada_buscada_en = new Date().toISOString();
                    elemento.imagen.dataset.respaldoLocal = obtenerPortadaLocal(elemento.libro) || '';
                    elemento.imagen.src = obtenerPortadaLibro(elemento.libro);
                });
            } catch (error) {
                console.warn('No se pudieron buscar algunas portadas:', error);
            }
        }
        procesandoColaPortadas = false;
    }

    const observadorPortadas = 'IntersectionObserver' in window ? new IntersectionObserver(entradas => {
        entradas.forEach(entrada => {
            if (!entrada.isIntersecting) return;
            const imagen = entrada.target;
            observadorPortadas.unobserve(imagen);
            const libro = todosLosLibros.find(item => Number(item.id) === Number(imagen.dataset.libroId));
            if (libro && !libro.imagen && (!libro.imagen_url || libro.imagen_url === 'assets/portada-no-disponible.svg') && !portadaBuscadaRecientemente(libro)) {
                colaPortadas.push({ libro, imagen });
            }
        });
        procesarColaPortadas();
    }, { rootMargin: '300px 0px' }) : null;

    function cargarPortadasDeLibros() {
        if (!observadorPortadas) return;
        tablaLibros.querySelectorAll('.libro-tarjeta .portada-libro').forEach(imagen => observadorPortadas.observe(imagen));
    }

    // Paso 1: Al hacer clic en un rol, oculta Pantalla 1 y muestra Pantalla 2
    btnsRol.forEach(btn => {
        btn.addEventListener('click', () => {
            rolSeleccionado = btn.getAttribute('data-rol');
            tituloRol.innerText = `Datos del ${rolSeleccionado === 'Profesor' ? 'Docente' : rolSeleccionado}`;
            formRegistro.querySelector('button[type="submit"]').textContent = 'Ingresar al Catálogo';
            pasoRol.classList.add('hidden');
            pasoDatos.classList.remove('hidden');
            document.body.classList.remove('home-page');
        });
    });

    // Función para cargar libros desde la base de datos
    async function cargarLibros(tipoVista = 'catalogo') {
        try {
            const params = new URLSearchParams({
                q: document.getElementById('buscar-libros')?.value.trim() || '',
                categoria: document.getElementById('filtro-categoria')?.value || '',
                materia: document.getElementById('filtro-materia')?.value || '',
                genero: document.getElementById('filtro-genero')?.value || ''
            });
            const respuesta = await fetch(API_BASE + 'obtener_libros.php?' + params.toString());
            const resultado = await respuesta.json();

            if (resultado.status === 'success') {
                todosLosLibros = resultado.libros;
                if (tipoVista === 'catalogo') {
                    actualizarSelectorPrestamo(resultado.libros);
                    actualizarSelectorCategorias(resultado.categorias || [], resultado.materias || [], resultado.generos || []);
                    mostrarLibros(resultado.libros);
                    cargarPortadasDeLibros();
                } else if (tipoVista === 'resenas') {
                    await actualizarSelectorResenas();
                    mostrarResenas(resultado.resenas || []);
                }
            } else {
                alert(resultado.message || 'Error al cargar libros.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Hubo un problema al cargar los libros.');
        }
    }

    // Función para mostrar libros en la tabla
    function mostrarLibros(libros) {
        tablaLibros.innerHTML = '';

        if (!libros.length) {
            tablaLibros.innerHTML = '<p>No hay libros disponibles para los filtros seleccionados.</p>';
            return;
        }

        libros.forEach((libro, indice) => {
            const fila = document.createElement('article');
            fila.className = 'libro-tarjeta';
            fila.dataset.libroId = libro.id;

            const estadoClase = libro.estado === 'Disponible' ? 'disponible' : 'prestado';

            // Información de disponibilidad
            let infoDisponibilidad = '';
            if (libro.estado === 'Prestado' && libro.fecha_devolucion_esperada) {
                const fechaDevolucion = new Date(libro.fecha_devolucion_esperada);
                const hoy = new Date();
                const diasRestantes = Math.ceil((fechaDevolucion - hoy) / (1000 * 60 * 60 * 24));

                if (diasRestantes > 0) {
                    infoDisponibilidad = `<br><small style="color: #666;">Disponible en ${diasRestantes} día(s)</small>`;
                } else {
                    infoDisponibilidad = `<br><small style="color: #d32f2f;">Préstamo vencido</small>`;
                }
            }

            // Mostrar diferentes botones según el rol
            let botones = '';
            if (usuarioActual.rol === 'Administrador') {
                if (libro.estado === 'Prestado') {
                    botones = `
                        <button class="btn-accion btn-devolver"
                                data-libro-id="${libro.id}"
                                data-prestado-a="${libro.prestado_a || 'N/A'}">
                            Registrar Devolución
                        </button>
                    `;
                } else {
                    botones = `<span style="color: #666;">Disponible</span>`;
                }
            } else {
                // Estudiante o Profesor
                if (libro.estado === 'Disponible') {
                    botones = `
                        <button class="btn-accion btn-prestar"
                                data-libro-id="${libro.id}">
                            Solicitar
                        </button>
                    `;
                } else {
                    botones = `<span class="badge prestado">No Disponible</span>`;
                }
            }

            fila.innerHTML = `
                <button class="btn-detalle-libro portada-contenedor" data-libro-id="${libro.id}" aria-label="Ver detalle de ${libro.titulo}"><img class="portada-libro" src="${obtenerPortadaLibro(libro)}" alt="Portada de ${libro.titulo}" loading="${indice < 12 ? 'eager' : 'lazy'}"${indice < 4 ? ' fetchpriority="high"' : ''}></button>
                <h4>${libro.titulo}</h4><p>${libro.autor}</p>
                <span class="badge ${estadoClase}">${libro.estado}</span>${infoDisponibilidad}
                <div class="libro-accion">${botones}</div>
            `;

            const imagenPortada = fila.querySelector('.portada-libro');
            imagenPortada.dataset.libroId = libro.id;
            usarPortadaRespaldo(imagenPortada, obtenerPortadaLocal(libro));

            tablaLibros.appendChild(fila);
        });
    }

    function limpiarContenidoVista(vista) {
        if (vista === 'catalogo' || vista === 'inventario-admin') {
            tablaLibros.innerHTML = '';
        } else if (vista === 'prestamos-usuario') {
            document.getElementById('tabla-mis-prestamos').innerHTML = '';
            document.getElementById('resumen-usuario').innerHTML = '';
            document.getElementById('notificaciones-prestamo').innerHTML = '';
        } else if (vista === 'prestamos-admin') {
            document.getElementById('resumen-admin').innerHTML = '';
            document.getElementById('tabla-control-prestamos').innerHTML = '';
            document.getElementById('tabla-solicitudes').innerHTML = '<tr><td colspan="8">No hay solicitudes pendientes.</td></tr>';
        } else if (vista === 'pendientes-admin') {
            document.getElementById('tabla-solicitudes').innerHTML = '<tr><td colspan="8">No hay solicitudes pendientes.</td></tr>';
        } else if (vista === 'resenas') {
            document.getElementById('lista-resenas').innerHTML = '';
            document.getElementById('resena-libro').innerHTML = '<option value="">Selecciona un libro</option>';
            establecerAccesoInteractivo(false, 'Cargando tus libros devueltos…');
        }
    }

    async function mostrarVista(vista, libroId = '') {
        if (vistaActiva && vistaActiva !== vista) limpiarContenidoVista(vistaActiva);
        vistaActiva = vista;
        const vistas = [
            'panel-usuario', 'usuario-solicitud', 'usuario-prestamos', 'panel-admin',
            'vista-prestamos-admin', 'vista-inventario-admin', 'vista-pendientes-admin',
            'vista-usuarios-admin', 'vista-catalogo', 'modulo-interactivo'
        ];
        vistas.forEach(id => document.getElementById(id)?.classList.add('hidden'));
        document.querySelectorAll('[data-vista]').forEach(boton => {
            boton.classList.toggle('is-active', boton.dataset.vista === vista);
        });

        if (vista === 'catalogo') {
            document.getElementById('vista-catalogo').classList.remove('hidden');
            await cargarLibros('catalogo');
        } else if (vista === 'prestamos-usuario') {
            document.getElementById('panel-usuario').classList.remove('hidden');
            document.getElementById('usuario-prestamos').classList.remove('hidden');
            await cargarPanelUsuario(vista);
        } else if (vista === 'prestamos-admin' || vista === 'pendientes-admin') {
            document.getElementById('panel-admin').classList.remove('hidden');
            document.getElementById('vista-prestamos-admin').classList.remove('hidden');
            document.getElementById('vista-pendientes-admin').classList.remove('hidden');
            await Promise.all([cargarPanelAdmin(), cargarSolicitudesPendientes()]);
        } else if (vista === 'inventario-admin') {
            document.getElementById('panel-admin').classList.remove('hidden');
            document.getElementById('vista-inventario-admin').classList.remove('hidden');
        } else if (vista === 'usuarios-admin') {
            document.getElementById('panel-admin').classList.remove('hidden');
            document.getElementById('vista-usuarios-admin').classList.remove('hidden');
        } else if (vista === 'resenas') {
            document.getElementById('modulo-interactivo').classList.remove('hidden');
            await cargarLibros('resenas');
            if (libroId && [...document.getElementById('resena-libro').options].some(o => o.value === String(libroId))) {
                document.getElementById('resena-libro').value = String(libroId);
                document.getElementById('contenido-resena').disabled = false;
            }
        }
    }

    function entrarAlSistema(usuario, token) {
        if (vistaActiva) limpiarContenidoVista(vistaActiva);
        usuarioActual = usuario;
        csrfToken = token;
        document.getElementById('panel-usuario').style.removeProperty('display');
        [
            'panel-usuario', 'usuario-solicitud', 'usuario-prestamos', 'panel-admin',
            'vista-prestamos-admin', 'vista-inventario-admin', 'vista-pendientes-admin',
            'vista-usuarios-admin', 'vista-catalogo', 'modulo-interactivo'
        ].forEach(id => document.getElementById(id)?.classList.add('hidden'));
        vistaActiva = '';
        document.querySelectorAll('[data-vista]').forEach(boton => boton.classList.remove('is-active'));
        mensajeBienvenida.innerText = `¡Hola, ${usuario.nombre} (${usuario.rol})! Aquí están los libros disponibles.`;
        pasoRol.classList.add('hidden');
        pasoDatos.classList.add('hidden');
        pasoBiblioteca.classList.remove('hidden');
        document.body.classList.remove('home-page');
        restaurarFiltros();
        if (usuario.rol === 'Administrador') {
            document.getElementById('navegacion-acciones').style.display = 'block';
            document.getElementById('acciones-admin').style.display = 'flex';
            document.getElementById('acciones-usuario').style.display = 'none';
        } else {
            document.getElementById('navegacion-acciones').style.display = 'block';
            document.getElementById('acciones-admin').style.display = 'none';
            document.getElementById('acciones-usuario').style.display = 'flex';
            actualizarFormularioPrestamo();
        }
    }

    document.getElementById('navegacion-acciones').addEventListener('click', evento => {
        const boton = evento.target.closest('[data-vista]');
        if (boton) mostrarVista(boton.dataset.vista);
    });

    // Paso 2: Al llenar los datos y dar enviar, validación con PHP
    formRegistro.addEventListener('submit', async (e) => {
        e.preventDefault();

        const doc = document.getElementById('doc').value.trim();
        const contrasena = document.getElementById('contrasena').value;
        const correo = document.getElementById('correo').value.trim();

        const datosUsuario = { doc, contrasena, rol: rolSeleccionado || 'Estudiante' };

        try {
            const respuesta = await fetch(API_BASE + 'guardar_usuario.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(datosUsuario)
            });

            const resultado = await respuesta.json();

            if (resultado.status === 'success') {
                entrarAlSistema(resultado.usuario, resultado.csrf_token);
            } else {
                alert('Error: ' + resultado.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Hubo un problema al conectar con el servidor PHP.');
        }
    });

    let temporizadorBusqueda;
    document.getElementById('buscar-libros').addEventListener('input', () => {
        guardarFiltros();
        clearTimeout(temporizadorBusqueda);
        temporizadorBusqueda = setTimeout(cargarLibros, 250);
    });
    ['filtro-categoria', 'filtro-materia', 'filtro-genero'].forEach(id => document.getElementById(id).addEventListener('change', () => {
        guardarFiltros();
        cargarLibros();
    }));
    document.getElementById('limpiar-filtros').addEventListener('click', () => {
        document.getElementById('buscar-libros').value = '';
        ['filtro-categoria', 'filtro-materia', 'filtro-genero'].forEach(id => document.getElementById(id).value = '');
        guardarFiltros();
        cargarLibros();
    });

    function llenarFiltro(id, valores, textoTodos) {
        const selector = document.getElementById(id);
        if (!selector) return;
        const seleccionada = selector.value;
        if (seleccionada && !valores.some(item => item.valor === seleccionada)) valores = [...valores, { valor: seleccionada, total: 0 }];
        selector.innerHTML = `<option value="">${textoTodos}</option>` + valores
            .map(({ valor, total }) => `<option value="${escaparHtml(valor)}">${escaparHtml(valor)} (${total})</option>`)
            .join('');
        selector.value = valores.some(item => item.valor === seleccionada) ? seleccionada : '';
        // Un filtro sin opciones solo confunde: se oculta hasta que existan datos.
        selector.classList.toggle('hidden', valores.length === 0 && !selector.value);
    }

    function actualizarSelectorCategorias(categorias, materias = [], generos = []) {
        llenarFiltro('filtro-categoria', categorias, 'Todas las categorías');
        llenarFiltro('filtro-materia', materias, 'Todas las materias');
        llenarFiltro('filtro-genero', generos, 'Todos los géneros');
    }

    function actualizarSelectorPrestamo(libros) {
        const selector = document.getElementById('solicitud-libro');
        if (!selector) return;
        const actual = selector.value;
        const disponibles = libros.filter(libro => libro.estado === 'Disponible');
        selector.innerHTML = '<option value="">Selecciona un libro disponible</option>' + disponibles.map(libro => `<option value="${libro.titulo}">${libro.titulo}</option>`).join('');
        if ([...selector.options].some(opcion => opcion.value === actual)) selector.value = actual;
    }

    function mostrarDetalleLibro(libro) {
        const disponibles = libro.ejemplares_disponibles ?? (libro.estado === 'Disponible' ? 1 : 0);
        const esAdmin = usuarioActual?.rol === 'Administrador';
        const botones = esAdmin
            ? `<div class="acciones-modal"><button type="button" class="btn-accion btn-editar-libro" data-libro-id="${libro.id}">Editar</button><button type="button" class="btn-accion btn-eliminar-libro" data-libro-id="${libro.id}">Eliminar</button></div>`
            : `<div class="acciones-modal"><button type="button" class="btn-accion btn-resena-libro" data-libro-id="${libro.id}">Dejar Reseña</button></div>`;
        const e = escaparHtml;
        document.getElementById('detalle-libro').innerHTML = `<img class="portada-detalle" src="${obtenerPortadaLibro(libro)}" alt="Portada de ${e(libro.titulo)}"><div><p class="eyebrow">Detalle del libro</p><h2 id="detalle-libro-titulo">${e(libro.titulo)}</h2><p class="autor-libro">${e(libro.autor)}</p><p><strong>Disponibles:</strong> ${disponibles} de ${libro.ejemplares_total || 1}</p><p><strong>Editorial:</strong> ${e(libro.editorial) || 'No registrada'}</p><p><strong>Ubicación:</strong> ${e(libro.ubicacion) || 'No registrada'}</p><p><strong>Año:</strong> ${libro.anio_publicacion || 'No registrado'}</p><p><strong>ISBN:</strong> ${e(libro.isbn) || 'No registrado'}</p><p class="sinopsis-libro">${e(libro.sinopsis) || 'Sinopsis pendiente de registrar.'}</p>${botones}</div>`;
        usarPortadaRespaldo(document.querySelector('#detalle-libro .portada-detalle'), obtenerPortadaLocal(libro));
        document.getElementById('modal-libro').classList.remove('hidden');
    }

    document.getElementById('cerrar-modal-libro').addEventListener('click', () => document.getElementById('modal-libro').classList.add('hidden'));

    const modalesCerrables = {
        'cerrar-modal-solicitud': 'usuario-solicitud',
        'cerrar-modal-editar': 'modal-editar-libro'
    };
    Object.entries(modalesCerrables).forEach(([boton, modal]) => document.getElementById(boton).addEventListener('click', () => document.getElementById(modal).classList.add('hidden')));
    ['modal-libro', 'usuario-solicitud', 'modal-editar-libro'].forEach(id => document.getElementById(id).addEventListener('mousedown', evento => {
        if (evento.target.id === id) document.getElementById(id).classList.add('hidden');
    }));
    document.addEventListener('keydown', evento => {
        if (evento.key === 'Escape') ['modal-libro', 'usuario-solicitud', 'modal-editar-libro'].forEach(id => document.getElementById(id).classList.add('hidden'));
    });

    const formEditarLibro = document.getElementById('form-editar-libro');

    function abrirEdicionLibro(libro) {
        const campos = formEditarLibro.elements;
        ['id', 'titulo', 'autor', 'codigo', 'editorial', 'ubicacion', 'isbn', 'anio_publicacion', 'ejemplares_total', 'sinopsis'].forEach(nombre => {
            campos[nombre].value = libro[nombre] ?? '';
        });
        const selectorGenero = campos['genero_literario'];
        const genero = libro.genero_literario || 'Consulta';
        if (![...selectorGenero.options].some(opcion => opcion.value === genero)) selectorGenero.add(new Option(genero, genero));
        selectorGenero.value = genero;
        campos['imagen_url'].value = libro.imagen_url && libro.imagen_url.startsWith('https://') ? libro.imagen_url : '';
        document.getElementById('mensaje-editar-libro').textContent = '';
        document.getElementById('modal-libro').classList.add('hidden');
        document.getElementById('modal-editar-libro').classList.remove('hidden');
    }

    formEditarLibro.addEventListener('submit', async evento => {
        evento.preventDefault();
        const mensaje = document.getElementById('mensaje-editar-libro');
        const boton = formEditarLibro.querySelector('button[type="submit"]');
        boton.disabled = true; mensaje.className = 'mensaje-formulario'; mensaje.textContent = 'Guardando…';
        try {
            const respuesta = await fetch(API_BASE + 'editar_libro.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify(Object.fromEntries(new FormData(formEditarLibro))) });
            const resultado = await respuesta.json();
            if (resultado.status !== 'success') throw new Error(resultado.message || 'No fue posible guardar.');
            document.getElementById('modal-editar-libro').classList.add('hidden');
            await cargarLibros('catalogo');
        } catch (error) { mensaje.textContent = error.message; mensaje.classList.add('error'); }
        finally { boton.disabled = false; }
    });

    async function eliminarLibro(id) {
        const libro = todosLosLibros.find(item => String(item.id) === String(id));
        if (!libro || !confirm(`¿Eliminar definitivamente "${libro.titulo}"? Esta acción no se puede deshacer.`)) return;
        try {
            const respuesta = await fetch(API_BASE + 'eliminar_libro.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify({ id: Number(id) }) });
            const resultado = await respuesta.json();
            if (resultado.status !== 'success') throw new Error(resultado.message || 'No fue posible eliminar el libro.');
            document.getElementById('modal-libro').classList.add('hidden');
            await cargarLibros('catalogo');
        } catch (error) { alert(error.message); }
    }

    async function actualizarSelectorResenas() {
        const selector = document.getElementById('resena-libro');
        const seleccionado = selector.value;
        try {
            const respuesta = await fetch(API_BASE + 'obtener_libros_resenables.php');
            const resultado = await respuesta.json();
            const libros = resultado.status === 'success' ? resultado.libros : [];
            selector.innerHTML = '<option value="">Selecciona un libro</option>' + libros.map(libro => `<option value="${libro.id}">${escaparHtml(libro.titulo)}</option>`).join('');
            selector.value = libros.some(libro => String(libro.id) === seleccionado) ? seleccionado : '';
            establecerAccesoInteractivo(Boolean(selector.value), libros.length
                ? 'Elige un libro que ya devolviste, marca las estrellas y publica tu reseña.'
                : 'Aún no tienes libros devueltos pendientes de reseñar. Cuando devuelvas un libro podrás calificarlo aquí.');
        } catch (error) {
            establecerAccesoInteractivo(false, 'No fue posible cargar tus libros devueltos.');
        }
    }

    function escaparHtml(texto) {
        return String(texto ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function establecerAccesoInteractivo(permitido, mensaje) {
        document.getElementById('contenido-resena').disabled = !permitido;
        document.getElementById('contenido-actividad').disabled = !permitido;
        const estado = document.getElementById('estado-interactivo');
        estado.textContent = mensaje;
        estado.classList.toggle('desbloqueado', permitido);
    }

    document.getElementById('resena-libro').addEventListener('change', evento => {
        document.getElementById('contenido-resena').disabled = !evento.target.value;
    });

    function mostrarResenas(resenas) {
        const lista = document.getElementById('lista-resenas');
        lista.innerHTML = resenas.length ? resenas.map(resena => `<article class="resena"><strong>${escaparHtml(resena.usuario_nombre)}</strong> · <span style="color:#f5b301">${'★'.repeat(Number(resena.calificacion))}</span>${resena.comentario ? `<p>${escaparHtml(resena.comentario)}</p>` : ''}<small>${escaparHtml(resena.titulo)}</small></article>`).join('') : '<p>Aún no hay reseñas. ¡Sé el primero en compartir tu lectura!</p>';
    }

    document.getElementById('form-resena').addEventListener('submit', async (evento) => {
        evento.preventDefault();
        if (!usuarioActual) return alert('Inicia sesión para publicar una reseña.');
        const calificacion = document.querySelector('input[name="calificacion"]:checked')?.value;
        if (!calificacion) return alert('Selecciona las estrellas para calificar el libro.');
        try {
            const respuesta = await fetch(API_BASE + 'guardar_reseña.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify({ libro_id: document.getElementById('resena-libro').value, calificacion, comentario: document.getElementById('resena-comentario').value }) });
            const resultado = await respuesta.json();
            if (resultado.status === 'success') {
                evento.target.reset();
                await cargarLibros('resenas');
            } else alert('Error: ' + resultado.message);
        } catch (error) { alert('No fue posible guardar la reseña.'); }
    });

    document.getElementById('validar-reto').addEventListener('click', () => {
        const texto = document.getElementById('respuesta-reto').value.toLocaleUpperCase('es');
        const aciertos = ['LIBRO', 'AUTOR', 'CUENTO', 'LECTURA'].filter(palabra => texto.includes(palabra)).length;
        document.getElementById('resultado-reto').textContent = aciertos === 4 ? '¡Excelente! Encontraste todas las palabras.' : `Llevas ${aciertos} de 4. ¡Sigue buscando!`;
    });

    // Acción para Prestar Libro
    document.addEventListener('click', async (e) => {
        const destinoAccion = e.target.closest('[data-scroll-target]');
        if (destinoAccion) {
            document.getElementById(destinoAccion.dataset.scrollTarget)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        const botonResena = e.target.closest('.btn-resena-libro');
        if (botonResena) {
            document.getElementById('modal-libro').classList.add('hidden');
            mostrarVista('resenas', botonResena.dataset.libroId);
            return;
        }
        const botonEditar = e.target.closest('.btn-editar-libro');
        if (botonEditar) {
            const libro = todosLosLibros.find(item => String(item.id) === botonEditar.dataset.libroId);
            if (libro) abrirEdicionLibro(libro);
            return;
        }
        const botonEliminar = e.target.closest('.btn-eliminar-libro');
        if (botonEliminar) {
            eliminarLibro(botonEliminar.dataset.libroId);
            return;
        }
        if (e.target.closest('.btn-detalle-libro')) {
            const id = e.target.closest('.btn-detalle-libro').getAttribute('data-libro-id');
            const libro = todosLosLibros.find(item => String(item.id) === id);
            if (libro) mostrarDetalleLibro(libro);
            return;
        }

        if (e.target && e.target.classList.contains('btn-prestar')) {
            const libro = todosLosLibros.find(item => String(item.id) === e.target.getAttribute('data-libro-id'));
            const selector = document.getElementById('solicitud-libro');
            if (libro && selector) selector.value = libro.titulo;
            document.getElementById('mensaje-prestamo-usuario').textContent = '';
            document.getElementById('usuario-solicitud').classList.remove('hidden');
        }

        if (e.target && e.target.classList.contains('btn-renovar')) {
            renovarPrestamo(e.target.getAttribute('data-prestamo-id'));
            return;
        }

        if (e.target && e.target.classList.contains('btn-solicitar-devolucion')) {
            solicitarDevolucion(e.target.getAttribute('data-prestamo-id'));
            return;
        }

        // Acción para Devolver Libro (Solo Administrador)
        if (e.target && e.target.classList.contains('btn-devolver')) {
            const boton = e.target;
            const prestadoA = boton.getAttribute('data-prestado-a');

            if (!confirm(`¿Confirmar devolución del libro prestado a ${prestadoA}?`)) {
                return;
            }

            const prestamoDirecto = boton.getAttribute('data-prestamo-id');
            if (prestamoDirecto) {
                devolverLibro(prestamoDirecto);
                return;
            }
            // Buscar el préstamo activo para este libro desde el catálogo.
            const libroId = boton.getAttribute('data-libro-id');

            try {
                const respuesta = await fetch(API_BASE + 'obtener_prestamos.php');
                const resultado = await respuesta.json();

                if (resultado.status === 'success') {
                    const prestamo = resultado.prestamos.find(p => p.libro_id == libroId);

                    if (prestamo) {
                        devolverLibro(prestamo.id);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Hubo un problema al procesar la devolución.');
            }
        }

        // Aprobar solicitud
        if (e.target && e.target.classList.contains('btn-aprobar')) {
            const prestamoId = e.target.getAttribute('data-prestamo-id');
            aprobarPrestamo(prestamoId);
        }

        // Rechazar solicitud
        if (e.target && e.target.classList.contains('btn-rechazar')) {
            const prestamoId = e.target.getAttribute('data-prestamo-id');
            const notas = prompt('Motivo del rechazo (opcional):');
            rechazarPrestamo(prestamoId, notas || '');
        }

        if (e.target && e.target.classList.contains('btn-perdido')) {
            const prestamoId = e.target.getAttribute('data-prestamo-id');
            const esMismo = confirm('¿El estudiante repondrá el mismo título y la misma edición?\nAceptar: mismo título y edición. Cancelar: libro equivalente aprobado.');
            const tipo = esMismo ? 'Mismo título y edición' : 'Libro equivalente';
            const detalle = prompt(esMismo ? 'Indica la edición o cualquier observación:' : 'Indica el libro equivalente aprobado por administración:') || '';
            reportarPerdido(prestamoId, tipo, detalle);
        }
    });

    // Navegación de regreso
    btnAtras1.addEventListener('click', () => {
        formRegistro.reset();
        formRegistro.querySelector('button[type="submit"]').textContent = 'Ingresar al Catálogo';
        pasoDatos.classList.add('hidden');
        pasoRol.classList.remove('hidden');
        document.body.classList.add('home-page');
    });

    btnCerrar.addEventListener('click', () => {
        if (csrfToken) {
            fetch(API_BASE + 'cerrar_sesion.php', {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrfToken }
            }).catch(error => console.error('Error al cerrar sesión:', error));
        }
        pasoBiblioteca.classList.add('hidden');
        pasoRol.classList.remove('hidden');
        document.body.classList.add('home-page');
        formRegistro.reset();
        usuarioActual = null;
        csrfToken = '';
        document.getElementById('panel-usuario').classList.add('hidden');
    });

    const modalClave = document.getElementById('modal-cambiar-clave');
    const formClave = document.getElementById('form-cambiar-clave');
    const mensajeClave = document.getElementById('mensaje-cambiar-clave');
    document.getElementById('btn-cambiar-clave').addEventListener('click', () => {
        formClave.reset();
        mensajeClave.textContent = '';
        modalClave.classList.remove('hidden');
    });
    document.getElementById('cerrar-modal-clave').addEventListener('click', () => modalClave.classList.add('hidden'));
    formClave.addEventListener('submit', async evento => {
        evento.preventDefault();
        mensajeClave.className = 'mensaje-formulario';
        if (formClave.nueva.value !== document.getElementById('clave-confirmar').value) {
            mensajeClave.textContent = 'Las contraseñas nuevas no coinciden.';
            mensajeClave.classList.add('error');
            return;
        }
        const boton = formClave.querySelector('button[type="submit"]');
        boton.disabled = true;
        try {
            const respuesta = await fetch(API_BASE + 'cambiar_contrasena.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ actual: formClave.actual.value, nueva: formClave.nueva.value })
            });
            const resultado = await respuesta.json();
            if (resultado.status !== 'success') throw new Error(resultado.message || 'No se pudo cambiar la contraseña.');
            formClave.reset();
            mensajeClave.textContent = resultado.message;
            mensajeClave.classList.add('exito');
        } catch (error) {
            mensajeClave.textContent = error.message;
            mensajeClave.classList.add('error');
        } finally { boton.disabled = false; }
    });

    // Funciones para el panel de administrador
    function mostrarPanelAdmin() {
        const panelAdmin = document.getElementById('panel-admin');
        if (panelAdmin) {
            panelAdmin.style.display = 'block';
        }
    }

    function ocultarPanelAdmin() {
        const panelAdmin = document.getElementById('panel-admin');
        if (panelAdmin) {
            panelAdmin.style.display = 'none';
        }
    }

    const formNuevoLibro = document.getElementById('form-nuevo-libro');
    async function descargarInventario() {
        const respuesta = await fetch(API_BASE + 'exportar_inventario.php', { headers: { 'X-CSRF-Token': csrfToken } });
        if (!respuesta.ok) {
            const resultado = await respuesta.json().catch(() => ({}));
            throw new Error(resultado.message || 'No fue posible descargar el inventario.');
        }
        const archivo = URL.createObjectURL(await respuesta.blob());
        const enlace = document.createElement('a');
        enlace.href = archivo;
        enlace.download = `inventario_biblioteca_${new Date().toISOString().slice(0, 10)}.csv`;
        enlace.click();
        URL.revokeObjectURL(archivo);
    }

    document.getElementById('btn-exportar-inventario-panel').addEventListener('click', async () => {
        try { await descargarInventario(); }
        catch (error) { alert(error.message); }
    });

    const formNuevoUsuario = document.getElementById('form-nuevo-usuario');
    formNuevoUsuario.addEventListener('submit', async evento => {
        evento.preventDefault();
        const mensaje = document.getElementById('mensaje-nuevo-usuario');
        const boton = formNuevoUsuario.querySelector('button[type="submit"]');
        boton.disabled = true;
        mensaje.className = 'mensaje-formulario';
        mensaje.textContent = 'Creando usuario…';
        try {
            const datos = Object.fromEntries(new FormData(formNuevoUsuario));
            const respuesta = await fetch(API_BASE + 'registrar_usuario.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify(datos)
            });
            const resultado = await respuesta.json();
            if (resultado.status !== 'success') throw new Error(resultado.message || 'No se pudo crear el usuario.');
            formNuevoUsuario.reset();
            mensaje.textContent = resultado.message;
            mensaje.classList.add('exito');
        } catch (error) {
            mensaje.textContent = error.message || 'No fue posible crear el usuario.';
            mensaje.classList.add('error');
        } finally { boton.disabled = false; }
    });

    if (formNuevoLibro) {
        formNuevoLibro.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            const mensaje = document.getElementById('mensaje-nuevo-libro');
            const boton = formNuevoLibro.querySelector('button[type="submit"]');
            mensaje.textContent = 'Guardando libro…';
            mensaje.className = 'mensaje-formulario';
            boton.disabled = true;
            try {
                const respuesta = await fetch(API_BASE + 'registrar_libro.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    body: new FormData(formNuevoLibro)
                });
                const resultado = await respuesta.json();
                if (resultado.status !== 'success') throw new Error(resultado.message || 'No se pudo registrar el libro.');
                formNuevoLibro.reset();
                document.getElementById('libro-cantidad').value = 1;
                mensaje.textContent = resultado.message;
                mensaje.classList.add('exito');
                cargarLibros();
            } catch (error) {
                mensaje.textContent = error.message || 'No fue posible registrar el libro.';
                mensaje.classList.add('error');
            } finally {
                boton.disabled = false;
            }
        });
    }

    const formPrestamoUsuario = document.getElementById('form-prestamo-usuario');
    const fechaInicioPrestamo = document.getElementById('solicitud-fecha');
    const fechaFinPrestamo = document.getElementById('solicitud-devolucion');

    function fechaLocalISO(fecha) {
        const año = fecha.getFullYear();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const dia = String(fecha.getDate()).padStart(2, '0');
        return `${año}-${mes}-${dia}`;
    }

    function actualizarLimitesFechaFin() {
        if (!fechaInicioPrestamo.value) return;
        const [año, mes, dia] = fechaInicioPrestamo.value.split('-').map(Number);
        const minimo = new Date(año, mes - 1, dia + 1);
        const maximo = new Date(año, mes - 1, dia + 15);
        fechaFinPrestamo.min = fechaLocalISO(minimo);
        fechaFinPrestamo.max = fechaLocalISO(maximo);
        if (!fechaFinPrestamo.value || fechaFinPrestamo.value <= fechaInicioPrestamo.value || fechaFinPrestamo.value > fechaFinPrestamo.max) {
            fechaFinPrestamo.value = fechaLocalISO(maximo);
        }
    }

    fechaInicioPrestamo.addEventListener('change', actualizarLimitesFechaFin);

    function actualizarFormularioPrestamo() {
        if (!usuarioActual || usuarioActual.rol === 'Administrador') return;
        const esDocente = usuarioActual.rol === 'Profesor';
        document.getElementById('solicitud-nombre').value = usuarioActual.nombre || '';
        document.getElementById('solicitud-rol').value = esDocente ? 'Profesor' : 'Estudiante';
        document.getElementById('solicitud-whatsapp').value = usuarioActual.telefono_whatsapp || '';
        document.getElementById('solicitud-consentimiento-whatsapp').checked = Boolean(Number(usuarioActual.whatsapp_consentimiento));
        const hoy = fechaLocalISO(new Date());
        fechaInicioPrestamo.min = hoy;
        if (!fechaInicioPrestamo.value || fechaInicioPrestamo.value < hoy) fechaInicioPrestamo.value = hoy;
        actualizarLimitesFechaFin();
    }
    if (formPrestamoUsuario) formPrestamoUsuario.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        const mensaje = document.getElementById('mensaje-prestamo-usuario');
        const boton = formPrestamoUsuario.querySelector('button[type="submit"]');

        boton.disabled = true; mensaje.className = 'mensaje-formulario'; mensaje.textContent = 'Registrando préstamo…';
        try {
            const respuesta = await fetch(API_BASE + 'realizar_prestamo.php', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken }, body: JSON.stringify(Object.fromEntries(new FormData(formPrestamoUsuario))) });
            const resultado = await respuesta.json();
            if (resultado.status !== 'success') throw new Error(resultado.message || 'No fue posible registrar el préstamo.');
            const datosSolicitud = Object.fromEntries(new FormData(formPrestamoUsuario));
            usuarioActual.telefono_whatsapp = datosSolicitud.telefono_whatsapp;
            usuarioActual.whatsapp_consentimiento = true;
            mensaje.textContent = resultado.message; mensaje.classList.add('exito');
            actualizarFormularioPrestamo();
            await mostrarVista('prestamos-usuario');
        } catch (error) { mensaje.textContent = error.message; mensaje.classList.add('error'); }
        finally { boton.disabled = false; }
    });
    const botonVencimientos = document.getElementById('btn-verificar-vencimientos');
    if (botonVencimientos) botonVencimientos.addEventListener('click', async () => {
        const mensaje = document.getElementById('mensaje-vencimientos');
        botonVencimientos.disabled = true; mensaje.className = 'mensaje-formulario'; mensaje.textContent = 'Verificando vencimientos…';
        try {
            const respuesta = await fetch(API_BASE + 'verificar_vencimientos.php', { method: 'POST', headers: { 'X-CSRF-Token': csrfToken } });
            const resultado = await respuesta.json();
            if (resultado.status !== 'success') throw new Error(resultado.message || 'No fue posible verificar los vencimientos.');
            const datos = resultado.resultado;
            mensaje.textContent = `Revisados: ${datos.revisados}. Correos enviados: ${datos.enviados}. WhatsApp enviados: ${datos.whatsapp_enviados}. WhatsApp omitidos: ${datos.whatsapp_omitidos || 0}. Omitidos: ${datos.omitidos}. ${datos.errores.join(' ')}`;
            mensaje.classList.add(datos.errores.length ? 'error' : 'exito');
        } catch (error) { mensaje.textContent = error.message; mensaje.classList.add('error'); }
        finally { botonVencimientos.disabled = false; }
    });

    function formatoMoneda(valor) {
        return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(valor || 0);
    }

    function formatoFecha(fecha) {
        if (!fecha) return 'Pendiente de aprobación';
        return new Date(fecha).toLocaleDateString('es-CO', { dateStyle: 'medium' });
    }

    function crearTarjetas(contenedor, tarjetas) {
        contenedor.innerHTML = tarjetas.map(tarjeta => `<div class="tarjeta-resumen"><span>${tarjeta.etiqueta}</span><strong>${tarjeta.valor}</strong></div>`).join('');
    }

    async function cargarPanelUsuario(vista = 'prestamos-usuario') {
        const panel = document.getElementById('panel-usuario');
        try {
            const respuesta = await fetch(API_BASE + 'obtener_panel_usuario.php');
            const resultado = await respuesta.json();
            if (resultado.status !== 'success') return;
            const { resumen, prestamos } = resultado;
            document.getElementById('mis-prestamos').textContent = 'Mis Préstamos y Pendientes';
            document.getElementById('mis-pendientes').textContent = 'Solicitudes, préstamos activos e historial';
            document.getElementById('resumen-usuario').classList.remove('hidden');
            document.getElementById('notificaciones-prestamo').classList.remove('hidden');
            crearTarjetas(document.getElementById('resumen-usuario'), [
                { etiqueta: 'En curso', valor: `${resumen.en_curso} de ${resumen.limite}` },
                { etiqueta: 'Solicitudes totales', valor: resumen.solicitados_total },
                { etiqueta: 'Pendientes', valor: resumen.pendientes },
                { etiqueta: 'Por devolver', valor: resumen.activos },
                { etiqueta: 'Vencidos', valor: resumen.vencidos },
                { etiqueta: 'Multa actual', valor: formatoMoneda(resumen.multa_total) }
            ]);
            const notificaciones = document.getElementById('notificaciones-prestamo');
            notificaciones.innerHTML = resumen.recordatorios.length ? resumen.recordatorios.map(recordatorio => {
                const vencido = recordatorio.estado === 'Vencido';
                return `<div class="notificacion-prestamo ${vencido ? 'vencido' : ''}"><strong>${vencido ? 'Libro vencido:' : 'Recordatorio:'}</strong> ${recordatorio.titulo}. Fecha límite: ${formatoFecha(recordatorio.fecha)}.${vencido ? ' Devuélvelo para evitar o regularizar la multa de $3.000.' : ' Devuélvelo a tiempo para evitar una multa de $3.000.'}</div>`;
            }).join('') : '';
            const tabla = document.getElementById('tabla-mis-prestamos');
            tabla.innerHTML = prestamos.length ? prestamos.map(prestamo => {
                const alerta = prestamo.estado === 'Pendiente' ? 'Esperando aprobación' :
                    prestamo.estado === 'Devuelto' ? `Devuelto${Number(prestamo.multa_actual) > 0 ? `. Multa: ${formatoMoneda(prestamo.multa_actual)}` : ''}` :
                    prestamo.estado === 'Rechazado' ? 'Solicitud rechazada' :
                    prestamo.estado === 'Perdido' ? `Perdido. Reposición: ${prestamo.tipo_reposicion || 'pendiente de definir'}` :
                    Number(prestamo.dias_retraso) > 0 ? `<span class="alerta-vencido">Vencido: ${prestamo.dias_retraso} día(s) hábil(es). Multa: ${formatoMoneda(prestamo.multa_actual)}</span>` :
                    `<span class="alerta-entrega">Entregar el ${formatoFecha(prestamo.fecha_devolucion_esperada)}</span>`;
                const devolucionSolicitada = Boolean(prestamo.fecha_solicitud_devolucion);
                const renovar = prestamo.estado === 'Activo' && !devolucionSolicitada && Number(prestamo.renovaciones || 0) < 1 ? `<button class="btn-accion btn-renovar" data-prestamo-id="${prestamo.id}">Renovar</button>` : '';
                const devolver = prestamo.estado === 'Activo' ? devolucionSolicitada
                    ? `<span>Devolución solicitada el ${formatoFecha(prestamo.fecha_solicitud_devolucion)}</span>`
                    : `<button class="btn-accion btn-solicitar-devolucion" data-prestamo-id="${prestamo.id}">Solicitar devolución</button>` : '';
                return `<tr><td>${prestamo.titulo}<br><small>${prestamo.autor}</small></td><td>${devolucionSolicitada ? 'Devolución solicitada' : prestamo.estado_visible}</td><td>${formatoFecha(prestamo.fecha_devolucion_esperada)}</td><td>${alerta}<br>${renovar}<br>${devolver}</td></tr>`;
            }).join('') : '<tr><td colspan="4">No tienes préstamos ni solicitudes.</td></tr>';
        } catch (error) {
            console.error('Error al cargar tu panel:', error);
        }
    }

    async function renovarPrestamo(prestamoId) {
        if (!confirm('¿Solicitar la renovación de este préstamo? Solo se permite una renovación por préstamo.')) return;
        try {
            const respuesta = await fetch(API_BASE + 'renovar_prestamo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ prestamo_id: prestamoId })
            });
            const resultado = await respuesta.json();
            if (resultado.status !== 'success') throw new Error(resultado.message);
            alert(resultado.message);
            await cargarPanelUsuario('prestamos-usuario');
        } catch (error) {
            alert(error.message || 'No fue posible renovar el préstamo.');
        }
    }

    async function solicitarDevolucion(prestamoId) {
        if (!confirm('¿Solicitar la devolución de este libro? Debes entregarlo en la biblioteca para que administración confirme la recepción.')) return;
        try {
            const respuesta = await fetch(API_BASE + 'solicitar_devolucion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ prestamo_id: prestamoId })
            });
            const resultado = await respuesta.json();
            if (resultado.status !== 'success') throw new Error(resultado.message || 'No fue posible solicitar la devolución.');
            alert(resultado.message);
            await cargarPanelUsuario('prestamos-usuario');
        } catch (error) {
            alert(error.message || 'No fue posible solicitar la devolución.');
        }
    }

    async function cargarPanelAdmin() {
        try {
            const respuesta = await fetch(API_BASE + 'obtener_panel_admin.php');
            const resultado = await respuesta.json();
            if (resultado.status !== 'success') return;
            const { resumen, prestamos } = resultado;
            crearTarjetas(document.getElementById('resumen-admin'), [
                { etiqueta: 'Préstamos activos', valor: resumen.activos },
                { etiqueta: 'Préstamos vencidos', valor: resumen.vencidos },
                { etiqueta: 'Libros reportados perdidos', valor: resumen.perdidos },
                { etiqueta: 'Multas acumuladas', valor: formatoMoneda(resumen.multa_total) }
            ]);
            const tabla = document.getElementById('tabla-control-prestamos');
            tabla.innerHTML = prestamos.length ? prestamos.map(prestamo => {
                const multa = Number(prestamo.dias_retraso) > 0 ? `<span class="alerta-vencido">${prestamo.dias_retraso} día(s) hábil(es): ${formatoMoneda(prestamo.multa_actual)}</span>` : '$0';
                const claseEstado = prestamo.estado_visible === 'Vencido' ? 'estado-rojo' : (prestamo.estado_visible === 'Próximo a vencer' ? 'estado-amarillo' : 'estado-verde');
                const devolucion = prestamo.estado === 'Activo' ? `<button class="btn-accion btn-devolver" data-prestamo-id="${prestamo.id}" data-prestado-a="${prestamo.usuario_nombre}">${prestamo.fecha_solicitud_devolucion ? 'Confirmar devolución solicitada' : 'Registrar devolución'}</button>${prestamo.fecha_solicitud_devolucion ? `<br><small>Solicitada: ${formatoFecha(prestamo.fecha_solicitud_devolucion)}</small>` : ''}` : '—';
                return `<tr class="${claseEstado}"><td>${prestamo.usuario_nombre}<br><small>${prestamo.usuario_rol}</small></td><td>${prestamo.titulo}</td><td>${prestamo.estado_visible}</td><td>${formatoFecha(prestamo.fecha_devolucion_esperada)}</td><td>${multa}</td><td>${devolucion}</td></tr>`;
            }).join('') : '<tr><td colspan="6">No hay préstamos en curso.</td></tr>';
        } catch (error) {
            console.error('Error al cargar panel administrativo:', error);
        }
    }

    async function cargarSolicitudesPendientes() {
        try {
            const respuesta = await fetch(API_BASE + 'obtener_solicitudes.php');
            const resultado = await respuesta.json();

            if (resultado.status === 'success') {
                mostrarSolicitudes(resultado.solicitudes);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    function mostrarSolicitudes(solicitudes) {
        const tablaSolicitudes = document.getElementById('tabla-solicitudes');
        if (!tablaSolicitudes) return;

        tablaSolicitudes.innerHTML = '';

        if (solicitudes.length === 0) {
            tablaSolicitudes.innerHTML = '<tr><td colspan="8" style="text-align: center;">No hay solicitudes pendientes</td></tr>';
            return;
        }

        solicitudes.forEach(solicitud => {
            const fila = document.createElement('tr');
            const fecha = new Date(solicitud.fecha_solicitud).toLocaleString('es-ES');

            fila.innerHTML = `
                <td>${solicitud.estudiante_nombre}</td>
                <td>${solicitud.estudiante_rol}</td>
                <td>${solicitud.libro_titulo}</td>
                <td>${solicitud.libro_autor}</td>
                <td>${formatoFecha(solicitud.fecha_prestamo)}</td>
                <td>${formatoFecha(solicitud.fecha_devolucion_esperada)}</td>
                <td>${fecha}</td>
                <td>
                    <button class="btn-accion btn-aprobar" data-prestamo-id="${solicitud.id}">
                        Aprobar
                    </button>
                    <button class="btn-accion btn-rechazar" data-prestamo-id="${solicitud.id}" style="background-color: #d32f2f;">
                        Rechazar
                    </button>
                </td>
            `;

            tablaSolicitudes.appendChild(fila);
        });
    }

    async function aprobarPrestamo(prestamoId) {
        if (!confirm('¿Aprobar el préstamo con las fechas de inicio y fin elegidas por el usuario? El servidor verificará que no supere 30 días.')) {
            return;
        }

        try {
            const respuesta = await fetch(API_BASE + 'aprobar_prestamo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    prestamo_id: prestamoId
                })
            });

            const resultado = await respuesta.json();

            if (resultado.status === 'success') {
                alert('Préstamo aprobado exitosamente');
                await mostrarVista('prestamos-admin');
            } else {
                alert('Error: ' + resultado.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Hubo un problema al aprobar el préstamo.');
        }
    }

    async function rechazarPrestamo(prestamoId, notas) {
        try {
            const respuesta = await fetch(API_BASE + 'rechazar_prestamo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    prestamo_id: prestamoId,
                    admin_id: usuarioActual.id,
                    notas: notas
                })
            });

            const resultado = await respuesta.json();

            if (resultado.status === 'success') {
                alert('Solicitud rechazada');
                await mostrarVista('prestamos-admin');
            } else {
                alert('Error: ' + resultado.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Hubo un problema al rechazar la solicitud.');
        }
    }

    async function devolverLibro(prestamoId) {
        try {
            const respuesta = await fetch(API_BASE + 'devolver_libro.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    prestamo_id: prestamoId
                })
            });

            const resultado = await respuesta.json();

            if (resultado.status === 'success') {
                const multa = Number(resultado.multa || 0);
                alert(multa > 0 ? `Libro devuelto. Multa generada: ${formatoMoneda(multa)}.` : 'Libro devuelto exitosamente');
                await mostrarVista('prestamos-admin');
            } else {
                alert('Error: ' + resultado.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Hubo un problema al devolver el libro.');
        }
    }

    async function reportarPerdido(prestamoId, tipoReposicion, detalleReposicion) {
        try {
            const respuesta = await fetch(API_BASE + 'reportar_perdido.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
                body: JSON.stringify({ prestamo_id: prestamoId, tipo_reposicion: tipoReposicion, detalle_reposicion: detalleReposicion })
            });
            const resultado = await respuesta.json();
            if (resultado.status === 'success') {
                alert(resultado.message);
                await mostrarVista('prestamos-admin');
            } else alert('Error: ' + resultado.message);
        } catch (error) {
            console.error('Error al reportar pérdida:', error);
            alert('No fue posible registrar el libro perdido.');
        }
    }

    fetch(API_BASE + 'obtener_sesion.php')
        .then(respuesta => respuesta.ok ? respuesta.json() : Promise.reject(new Error('No fue posible comprobar la sesión.')))
        .then(resultado => {
            if (resultado.status === 'success') entrarAlSistema(resultado.usuario, resultado.csrf_token);
            else pasoRol.classList.remove('hidden');
        })
        .catch(() => pasoRol.classList.remove('hidden'));
});
