/* ================================================
   SISTEMA MEJORADO DE PORTADAS - MÚLTIPLES FUENTES
   Busca en Google Books, Open Library y fallback
   ================================================ */

(function() {
    'use strict';

    // Cache de portadas encontradas
    const cachePortadas = new Map();
    const ASSET_BASE = new URL('../frontend/assets/', window.location.href);
    const assetUrl = path => new URL(path, ASSET_BASE).href;

    /**
     * Buscar portada usando Google Books API
     */
    async function buscarEnGoogleBooks(titulo, autor) {
        try {
            const query = autor ? `${titulo} ${autor}` : titulo;
            const url = `https://www.googleapis.com/books/v1/volumes?q=${encodeURIComponent(query)}&maxResults=1`;

            const response = await fetch(url);
            const data = await response.json();

            if (data.items && data.items[0]) {
                const imageLinks = data.items[0].volumeInfo.imageLinks;
                if (imageLinks) {
                    // Preferir la imagen más grande disponible
                    return imageLinks.large || imageLinks.medium || imageLinks.thumbnail || imageLinks.smallThumbnail;
                }
            }
        } catch (error) {
            console.log('Google Books falló:', error);
        }
        return null;
    }

    /**
     * Buscar portada usando Open Library
     */
    function buscarEnOpenLibrary(isbn, titulo) {
        if (isbn) {
            return `https://covers.openlibrary.org/b/isbn/${isbn}-L.jpg`;
        }
        if (titulo) {
            return `https://covers.openlibrary.org/b/title/${encodeURIComponent(titulo)}-L.jpg`;
        }
        return null;
    }

    /**
     * Función principal: obtener portada con múltiples intentos
     */
    window.obtenerPortadaInteligente = async function(libro, imagenElement) {
        const cacheKey = `${libro.id}-${libro.titulo}`;

        // 1. Verificar cache
        if (cachePortadas.has(cacheKey)) {
            return cachePortadas.get(cacheKey);
        }

        // 2. Imagen local si existe
        if (libro.imagen && !libro.imagen.includes('portada-no-disponible')) {
            const rutaLocal = assetUrl(libro.imagen.startsWith('IMAGES-LIBROS/')
                ? libro.imagen
                : `IMAGES-LIBROS/${libro.imagen}`);
            cachePortadas.set(cacheKey, rutaLocal);
            return rutaLocal;
        }

        // 3. Intentar con ISBN en Open Library
        if (libro.isbn) {
            const urlOpenLibrary = buscarEnOpenLibrary(libro.isbn, null);

            // Verificar si la imagen existe
            const existe = await verificarImagenExiste(urlOpenLibrary);
            if (existe) {
                cachePortadas.set(cacheKey, urlOpenLibrary);
                return urlOpenLibrary;
            }
        }

        // 4. Buscar en Google Books
        const urlGoogleBooks = await buscarEnGoogleBooks(libro.titulo, libro.autor);
        if (urlGoogleBooks) {
            cachePortadas.set(cacheKey, urlGoogleBooks);
            return urlGoogleBooks;
        }

        // 5. Intentar Open Library por título
        const urlTitulo = buscarEnOpenLibrary(null, libro.titulo);
        const existeTitulo = await verificarImagenExiste(urlTitulo);
        if (existeTitulo) {
            cachePortadas.set(cacheKey, urlTitulo);
            return urlTitulo;
        }

        // 6. Fallback: imagen no disponible
        const fallback = assetUrl('portada-no-disponible.svg');
        cachePortadas.set(cacheKey, fallback);
        return fallback;
    };

    /**
     * Verificar si una imagen existe (sin cargarla completa)
     */
    async function verificarImagenExiste(url) {
        return new Promise((resolve) => {
            const img = new Image();

            const timeout = setTimeout(() => {
                img.src = '';
                resolve(false);
            }, 3000); // 3 segundos timeout

            img.onload = function() {
                clearTimeout(timeout);
                // Verificar que no sea una imagen de error (1x1 pixel)
                resolve(this.width > 1 && this.height > 1);
            };

            img.onerror = function() {
                clearTimeout(timeout);
                resolve(false);
            };

            img.src = url;
        });
    }

    /**
     * Aplicar portada a un elemento de imagen
     */
    window.aplicarPortadaInteligente = async function(libro, imagenElement) {
        // Mostrar loading
        imagenElement.classList.add('loading');

        const urlPortada = await obtenerPortadaInteligente(libro, imagenElement);

        imagenElement.src = urlPortada;
        imagenElement.classList.remove('loading');

        // Si falla la carga, intentar fallback
        imagenElement.onerror = function() {
            if (this.src.includes('portada-no-disponible.svg')) return;
            this.onerror = null;
            this.src = assetUrl('portada-no-disponible.svg');
        };
    };

    console.log('✅ Sistema de portadas inteligente cargado');
})();
