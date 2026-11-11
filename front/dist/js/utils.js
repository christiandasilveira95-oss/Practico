// front/dist/js/utils.js
// Funciones chicas que usan varias páginas.

// Imagen que se muestra si un libro no tiene portada (o si la imagen no carga)
const SIN_PORTADA = 'data:image/svg+xml;utf8,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="450" viewBox="0 0 300 450">' +
    '<rect width="300" height="450" fill="#ddd5a8"/>' +
    '<rect x="70" y="120" width="160" height="210" rx="6" fill="none" stroke="#8a4300" stroke-width="8"/>' +
    '<line x1="110" y1="120" x2="110" y2="330" stroke="#8a4300" stroke-width="8"/>' +
    '<text x="150" y="385" font-family="Arial" font-size="22" text-anchor="middle" fill="#8a4300">Sin portada</text>' +
    '</svg>'
);

// IMPORTANTE: todo texto que viene de la base de datos se pasa por acá antes de meterlo en innerHTML.
// Si alguien guardara "<script>..." como título, se muestra como texto y no se ejecuta (evita XSS).
function escaparHtml(texto) {
    return String(texto === null || texto === undefined ? '' : texto)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// En la base la ruta se guarda así: "assets/img/mi portada.png" (con espacios y tildes).
// encodeURI la convierte en una URL válida y "/front/" la hace relativa a la carpeta front del sitio (donde están assets/ y uploads/).
function urlImagen(ruta) {
    if (!ruta) return SIN_PORTADA;
    return '/front/' + encodeURI(ruta);
}

function formatearPrecio(precio) {
    if (precio === null || precio === undefined) return 'Consultar precio';
    return '$ ' + Number(precio).toLocaleString('es-UY', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

// Si una portada no carga, se reemplaza por la imagen "Sin portada".
// El evento "error" de las imágenes no "burbujea", por eso se escucha en la fase de captura (true).
function imagenesConFallback(contenedor) {
    contenedor.addEventListener('error', function (evento) {
        if (evento.target.tagName === 'IMG' && evento.target.src !== SIN_PORTADA) {
            evento.target.src = SIN_PORTADA;
        }
    }, true);
}
