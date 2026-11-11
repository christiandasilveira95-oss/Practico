// front/dist/js/script.js - Landing page (Práctico 1, 2 y 3)

var btnLogin = document.getElementById("btnLogin");
var btnSalir = document.getElementById("btnSalir");
var listaLibros = document.getElementById("listaLibros");

// Acá se guardan los libros que llegan de la API, para poder mostrar el detalle en el modal
var libros = [];

// ============================================================
// SESIÓN (Práctico 2)
// Si ya hay alguien logueado, el botón "Iniciar Sesión" pasa a llevar a su panel.
// ============================================================
async function revisarSesion() {
    var respuesta = await API.request("/me");

    if (respuesta.status !== "ok") {
        return; // sin sesión: el botón sigue llevando a login.html
    }

    btnLogin.textContent = "Mi panel";
    btnLogin.href = respuesta.redirect; // el servidor dice a dónde va cada rol
    btnSalir.hidden = false;
}

btnSalir.addEventListener("click", async function (evento) {
    evento.preventDefault();
    await API.request("/logout", "POST");
    window.location.reload();
});

// ============================================================
// CATÁLOGO DINÁMICO (Práctico 3)
// Los libros ya no están escritos en el HTML: se piden a la API y se dibujan acá.
// ============================================================
function crearTarjeta(libro) {
    var noDisponible = libro.disponible === 0;

    // La estructura es la misma tarjeta que se diseñó en el Práctico 1
    return '' +
        '<div class="col">' +
            '<div class="card h-100 libro' + (noDisponible ? ' no-disponible' : '') + '">' +
                (noDisponible ? '<span class="etiqueta-agotado">No disponible</span>' : '') +
                '<img src="' + urlImagen(libro.imagen) + '" class="card-img-top" alt="' + escaparHtml(libro.titulo) + '">' +
                '<div class="card-body text-center">' +
                    '<h5 class="card-title">' + escaparHtml(libro.titulo) + '</h5>' +
                    '<p class="card-text">' + escaparHtml(libro.autor) + '</p>' +
                    '<button class="btn btn-sm btn-comprar" data-id="' + libro.id_libro + '">Ver más</button>' +
                '</div>' +
            '</div>' +
        '</div>';
}

function mostrarMensajeCatalogo(texto) {
    listaLibros.innerHTML = '<p class="text-center text-white w-100">' + escaparHtml(texto) + '</p>';
}

async function cargarCatalogo() {
    var respuesta = await API.request("/libros");

    if (respuesta.status !== "ok") {
        mostrarMensajeCatalogo("No pudimos cargar el catálogo. Intentá de nuevo en unos minutos.");
        return;
    }

    libros = respuesta.data;

    if (libros.length === 0) {
        mostrarMensajeCatalogo("Todavía no hay libros en el catálogo.");
        return;
    }

    listaLibros.innerHTML = libros.map(crearTarjeta).join("");
}

// ============================================================
// BOTONES "VER MÁS": abren el detalle del libro en un modal
// Un solo listener en el contenedor sirve para todos los botones, incluso los que se crean después.
// ============================================================
listaLibros.addEventListener("click", function (evento) {
    var boton = evento.target.closest(".btn-comprar");
    if (!boton) return;

    var id = Number(boton.dataset.id);
    var libro = libros.find(function (l) { return l.id_libro === id; });
    if (!libro) return;

    document.getElementById("modalLibroTitulo").textContent = libro.titulo;
    document.getElementById("modalLibroImg").src = urlImagen(libro.imagen);
    document.getElementById("modalLibroImg").alt = "Portada de " + libro.titulo;
    document.getElementById("modalLibroAutor").textContent = libro.autor;
    document.getElementById("modalLibroCategoria").textContent = libro.categoria;
    document.getElementById("modalLibroDescripcion").textContent = libro.descripcion || "Este libro todavía no tiene descripción.";
    document.getElementById("modalLibroPrecio").textContent = formatearPrecio(libro.precio);

    var estado = document.getElementById("modalLibroEstado");
    estado.textContent = libro.disponible === 1 ? "Disponible" : "No disponible";
    estado.className = "badge " + (libro.disponible === 1 ? "estado-ok" : "estado-no");

    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalLibro")).show();
});

imagenesConFallback(listaLibros);

// Al abrir la página
revisarSesion();
cargarCatalogo();
