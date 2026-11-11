// front/dist/js/admin.js - Dashboard de administración
// Práctico 2: sesión, menú y cierre de sesión.
// Práctico 3: alta, modificación y baja de libros (ABML) conectada a la API.

// Datos que vienen de la API
var datos = { libros: [], categorias: [], autores: [] };
var libroAEliminar = null;

// Elementos de la página
var tablaLibros = document.getElementById("tablaLibros");
var formLibro = document.getElementById("formLibro");
var errorForm = document.getElementById("errorForm");
var btnGuardar = document.getElementById("btnGuardar");
var inputId = document.getElementById("libroId");
var inputImagenActual = document.getElementById("libroImagenActual");
var inputTitulo = document.getElementById("libroTitulo");
var inputAutor = document.getElementById("libroAutor");
var inputCategoria = document.getElementById("libroCategoria");
var inputPrecio = document.getElementById("libroPrecio");
var inputEstado = document.getElementById("libroEstado");
var inputDescripcion = document.getElementById("libroDescripcion");
var inputArchivo = document.getElementById("libroArchivo");
var previewPortada = document.getElementById("previewPortada");
var ayudaPortada = document.getElementById("ayudaPortada");
var elModalForm = document.getElementById("modalForm");
var elModalEliminar = document.getElementById("modalEliminar");
var modalForm = new bootstrap.Modal(elModalForm);
var modalEliminar = new bootstrap.Modal(elModalEliminar);

var MAX_BYTES = 2 * 1024 * 1024; // 2 MB, el mismo límite que valida la API
var TIPOS_IMAGEN = ["image/jpeg", "image/png", "image/webp"];

// ============================================================
// API con control de sesión
// Si la sesión se venció (401) o el usuario no es admin (403), se lo saca del panel.
// ============================================================
function controlarSesion(respuesta) {
    if (respuesta.codigo === 401) {
        window.location.href = "/front/dist/pages/login.html?motivo=expirada";
    } else if (respuesta.codigo === 403) {
        window.location.href = "/front/dist/pages/usuario.html";
    }
    return respuesta;
}

async function llamar(endpoint, metodo, cuerpo) {
    return controlarSesion(await API.request(endpoint, metodo, cuerpo));
}

// ============================================================
// AVISOS
// ============================================================
function mostrarAviso(texto, tipo) {
    var aviso = document.createElement("div");
    aviso.className = "alert alert-" + tipo + " alert-dismissible shadow-sm";
    aviso.setAttribute("role", tipo === "danger" ? "alert" : "status");
    aviso.textContent = texto; // textContent: el texto nunca se interpreta como HTML

    var cerrar = document.createElement("button");
    cerrar.type = "button";
    cerrar.className = "btn-close";
    cerrar.setAttribute("data-bs-dismiss", "alert");
    cerrar.setAttribute("aria-label", "Cerrar");
    aviso.appendChild(cerrar);

    document.getElementById("avisos").appendChild(aviso);
    setTimeout(function () { aviso.remove(); }, 5000);
}

function mostrarErrorForm(texto) {
    errorForm.textContent = texto;
    errorForm.hidden = false;
}

// ============================================================
// MENÚ: Resumen / Libros
// ============================================================
var titulosVistas = { inicio: "Resumen", libros: "Libros" };

function mostrarVista(nombre) {
    if (!titulosVistas[nombre]) nombre = "inicio";

    document.getElementById("vista-inicio").hidden = nombre !== "inicio";
    document.getElementById("vista-libros").hidden = nombre !== "libros";
    document.getElementById("tituloVista").textContent = titulosVistas[nombre];
    document.title = titulosVistas[nombre] + " | Administración | Lofi-Library";

    document.querySelectorAll(".admin-menu .nav-link[data-vista]").forEach(function (enlace) {
        enlace.classList.toggle("active", enlace.dataset.vista === nombre);
    });
}

window.addEventListener("hashchange", function () {
    mostrarVista(window.location.hash.slice(1));
});

// En el celular el menú es un panel desplegable: se cierra al elegir una sección
document.querySelectorAll(".admin-menu .nav-link[data-vista]").forEach(function (enlace) {
    enlace.addEventListener("click", function () {
        var panel = bootstrap.Offcanvas.getInstance(document.getElementById("menuAdmin"));
        if (panel) panel.hide();
    });
});

// ============================================================
// CERRAR SESIÓN
// ============================================================
document.getElementById("btnSalir").addEventListener("click", async function () {
    await API.request("/logout", "POST");
    window.location.href = "/front/index.html";
});

// ============================================================
// CARGA Y DIBUJO DE DATOS
// ============================================================
async function cargarDatos() {
    var respuestas = await Promise.all([
        API.request("/libros"),
        API.request("/categorias"),
        API.request("/autores")
    ]);

    var fallo = respuestas.some(function (r) { return r.status !== "ok"; });
    if (fallo) {
        tablaLibros.innerHTML = '<tr><td colspan="7" class="text-center py-4">No se pudieron cargar los datos. Recargá la página.</td></tr>';
        mostrarAviso("No se pudieron cargar los datos desde la API.", "danger");
        return;
    }

    datos.libros = respuestas[0].data;
    datos.categorias = respuestas[1].data;
    datos.autores = respuestas[2].data;

    dibujarResumen();
    dibujarTabla();
    dibujarOpcionesFormulario();
}

function dibujarResumen() {
    var disponibles = datos.libros.filter(function (l) { return l.disponible === 1; }).length;
    document.getElementById("totalLibros").textContent = datos.libros.length;
    document.getElementById("totalDisponibles").textContent = disponibles;
    document.getElementById("totalCategorias").textContent = datos.categorias.length;
    document.getElementById("totalAutores").textContent = datos.autores.length;
}

function filaLibro(libro) {
    var titulo = escaparHtml(libro.titulo);
    var disponible = libro.disponible === 1;

    return '<tr>' +
        '<td><img class="miniatura" src="' + urlImagen(libro.imagen) + '" alt=""></td>' +
        '<td class="col-titulo">' + titulo + '</td>' +
        '<td>' + escaparHtml(libro.autor) + '</td>' +
        '<td>' + escaparHtml(libro.categoria) + '</td>' +
        '<td class="text-end col-precio">' + escaparHtml(formatearPrecio(libro.precio)) + '</td>' +
        '<td><span class="estado ' + (disponible ? 'estado-disponible' : 'estado-agotado') + '">' +
            (disponible ? 'Disponible' : 'No disponible') + '</span></td>' +
        '<td class="text-end col-acciones">' +
            '<button type="button" class="btn btn-sm btn-outline-secondary me-1" data-accion="editar" data-id="' + libro.id_libro + '" aria-label="Editar ' + titulo + '">Editar</button>' +
            '<button type="button" class="btn btn-sm btn-outline-danger" data-accion="eliminar" data-id="' + libro.id_libro + '" aria-label="Eliminar ' + titulo + '">Eliminar</button>' +
        '</td>' +
    '</tr>';
}

function dibujarTabla() {
    if (datos.libros.length === 0) {
        tablaLibros.innerHTML = '<tr><td colspan="7" class="text-center py-4">Todavía no hay libros. Usá "Agregar libro" para cargar el primero.</td></tr>';
        return;
    }
    tablaLibros.innerHTML = datos.libros.map(filaLibro).join("");
}

// Categorías (select) y autores existentes (sugerencias del campo Autor)
function dibujarOpcionesFormulario() {
    inputCategoria.innerHTML = '<option value="">Elegí una categoría</option>' +
        datos.categorias.map(function (c) {
            return '<option value="' + c.id_categoria + '">' + escaparHtml(c.nombre_categoria) + '</option>';
        }).join("");

    document.getElementById("listaAutores").innerHTML = datos.autores.map(function (a) {
        return '<option value="' + escaparHtml(a.nombre_autor) + '"></option>';
    }).join("");
}

imagenesConFallback(tablaLibros);

// ============================================================
// FORMULARIO: AGREGAR / MODIFICAR
// ============================================================
function abrirFormulario(libro) {
    formLibro.reset();
    formLibro.classList.remove("was-validated");
    errorForm.hidden = true;

    if (libro) {
        document.getElementById("modalFormTitulo").textContent = "Modificar libro";
        inputId.value = libro.id_libro;
        inputImagenActual.value = libro.imagen || "";
        inputTitulo.value = libro.titulo;
        inputAutor.value = libro.autor;
        inputCategoria.value = libro.id_categoria;
        inputPrecio.value = libro.precio === null ? "" : libro.precio;
        inputEstado.value = libro.disponible;
        inputDescripcion.value = libro.descripcion || "";
        previewPortada.src = urlImagen(libro.imagen);
        ayudaPortada.textContent = "Si no elegís otra imagen, se mantiene la portada actual. JPG, PNG o WEBP de hasta 2 MB.";
    } else {
        document.getElementById("modalFormTitulo").textContent = "Agregar libro";
        inputId.value = "";
        inputImagenActual.value = "";
        previewPortada.src = urlImagen(null);
        ayudaPortada.textContent = "JPG, PNG o WEBP de hasta 2 MB.";
    }
    modalForm.show();
}

elModalForm.addEventListener("shown.bs.modal", function () {
    inputTitulo.focus();
});

document.getElementById("btnNuevo").addEventListener("click", function () {
    abrirFormulario(null);
});

// Vista previa al elegir una imagen (se comprueba tipo y tamaño antes de subirla)
inputArchivo.addEventListener("change", function () {
    var archivo = inputArchivo.files[0];
    errorForm.hidden = true;

    if (!archivo) {
        previewPortada.src = urlImagen(inputImagenActual.value);
        return;
    }
    if (TIPOS_IMAGEN.indexOf(archivo.type) === -1) {
        mostrarErrorForm("Formato no permitido: usá JPG, PNG o WEBP.");
        inputArchivo.value = "";
    } else if (archivo.size > MAX_BYTES) {
        mostrarErrorForm("La imagen supera el tamaño máximo de 2 MB.");
        inputArchivo.value = "";
    } else {
        previewPortada.src = URL.createObjectURL(archivo);
        return;
    }
    previewPortada.src = urlImagen(inputImagenActual.value);
});

formLibro.addEventListener("submit", async function (evento) {
    evento.preventDefault();
    errorForm.hidden = true;

    if (!formLibro.checkValidity()) {
        formLibro.classList.add("was-validated");
        return;
    }

    btnGuardar.disabled = true;
    btnGuardar.textContent = "Guardando...";

    // 1) Si se eligió una imagen nueva, primero se sube y la API devuelve la ruta donde quedó
    var imagen = inputImagenActual.value;
    if (inputArchivo.files.length > 0) {
        var formData = new FormData();
        formData.append("imagen", inputArchivo.files[0]);
        var subida = controlarSesion(await API.subirArchivo("/upload/portada", formData));

        if (subida.status !== "ok") {
            mostrarErrorForm(subida.message);
            restaurarBotonGuardar();
            return;
        }
        imagen = subida.ruta;
    }

    // 2) Se guarda el libro: POST si es nuevo, PUT si ya existe
    var libro = {
        titulo: inputTitulo.value.trim(),
        autor: inputAutor.value.trim(),
        id_categoria: inputCategoria.value,
        precio: inputPrecio.value === "" ? null : inputPrecio.value,
        disponible: inputEstado.value === "1",
        descripcion: inputDescripcion.value.trim(),
        imagen: imagen
    };

    var id = inputId.value;
    var respuesta = id
        ? await llamar("/libros/" + id, "PUT", libro)
        : await llamar("/libros", "POST", libro);

    if (respuesta.status !== "ok") {
        mostrarErrorForm(respuesta.message);
        restaurarBotonGuardar();
        return;
    }

    modalForm.hide();
    restaurarBotonGuardar();
    mostrarAviso(respuesta.message + ": " + respuesta.data.titulo, "success");
    cargarDatos();
});

function restaurarBotonGuardar() {
    btnGuardar.disabled = false;
    btnGuardar.textContent = "Guardar libro";
}

// ============================================================
// TABLA: botones Editar y Eliminar
// ============================================================
function buscarLibro(id) {
    return datos.libros.find(function (l) { return l.id_libro === Number(id); });
}

tablaLibros.addEventListener("click", function (evento) {
    var boton = evento.target.closest("button[data-accion]");
    if (!boton) return;

    var libro = buscarLibro(boton.dataset.id);
    if (!libro) return;

    if (boton.dataset.accion === "editar") {
        abrirFormulario(libro);
    } else {
        libroAEliminar = libro;
        document.getElementById("eliminarTitulo").textContent = libro.titulo;
        modalEliminar.show();
    }
});

// ============================================================
// ELIMINAR
// ============================================================
document.getElementById("btnConfirmarEliminar").addEventListener("click", async function () {
    if (!libroAEliminar) return;

    var boton = this;
    boton.disabled = true;

    var respuesta = await llamar("/libros/" + libroAEliminar.id_libro, "DELETE");

    boton.disabled = false;
    modalEliminar.hide();

    if (respuesta.status === "ok") {
        mostrarAviso("Libro eliminado: " + libroAEliminar.titulo, "success");
        cargarDatos();
    } else {
        mostrarAviso(respuesta.message, "danger");
    }
    libroAEliminar = null;
});

// ============================================================
// AL ABRIR EL PANEL
// ============================================================
mostrarVista(window.location.hash.slice(1));
cargarDatos();
