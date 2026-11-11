<?php
// ============================================================
// DASHBOARD DE ADMINISTRACIÓN  (Práctico 2 - Fase 3 y 4)
// ============================================================
// PROTECCIÓN: este bloque se ejecuta en el SERVIDOR antes de enviar una sola línea de HTML.
// Quien no sea administrador nunca recibe la página: se lo redirige.
// (La API además valida el rol en cada operación: ver api/routes.php)
require_once __DIR__ . '/../../../api/autoload.php';

use App\Utils\Auth;

$usuario = Auth::usuario();

if ($usuario === null) {
    // Sin sesión -> a iniciar sesión
    header('Location: ' . Auth::RUTA_LOGIN . '?motivo=admin');
    exit;
}
if ($usuario['rol'] !== Auth::ROL_ADMIN) {
    // Con sesión pero sin ser administrador -> a la sección de usuario
    header('Location: ' . Auth::RUTA_USUARIO);
    exit;
}

// Evita que el botón "Atrás" del navegador muestre el dashboard desde la caché después de cerrar sesión
header('Cache-Control: no-store');

$nombre = htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administración | Lofi-Library</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS propio del panel (distinto al de la landing) -->
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin">

    <!-- Barra superior: solo en pantallas chicas, donde el menú pasa a ser un panel lateral desplegable -->
    <header class="admin-topbar d-lg-none">
        <button class="btn btn-menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuAdmin"
                aria-controls="menuAdmin" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <span class="admin-marca">Lofi-Library</span>
    </header>

    <!-- ===== MENÚ DEL ADMINISTRADOR ===== -->
    <aside class="admin-menu offcanvas-lg offcanvas-start" tabindex="-1" id="menuAdmin" aria-label="Menú de administración">
        <div class="offcanvas-header d-lg-none">
            <span class="admin-marca">Lofi-Library</span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#menuAdmin" aria-label="Cerrar menú"></button>
        </div>
        <div class="offcanvas-body flex-column">
            <div class="admin-marca d-none d-lg-block">Lofi-Library</div>
            <div class="admin-submarca d-none d-lg-block">Panel de administración</div>

            <nav class="nav flex-column mt-lg-4" aria-label="Secciones">
                <a class="nav-link active" href="#inicio" data-vista="inicio">Resumen</a>
                <a class="nav-link" href="#libros" data-vista="libros">Libros</a>
                <a class="nav-link" href="../../index.html#catalogo" target="_blank" rel="noopener">Ver catálogo público</a>
            </nav>
        </div>
    </aside>

    <!-- ===== CONTENIDO ===== -->
    <main class="admin-contenido">

        <!-- Usuario conectado + cerrar sesión -->
        <div class="admin-encabezado">
            <h1 id="tituloVista">Resumen</h1>
            <div class="admin-sesion">
                <div class="admin-usuario">
                    <span class="admin-usuario-nombre" id="adminNombre"><?= $nombre ?></span>
                    <span class="admin-usuario-detalle">Administrador · <?= $email ?></span>
                </div>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="btnSalir">Cerrar sesión</button>
            </div>
        </div>

        <!-- Avisos ("Libro agregado", errores, etc.) -->
        <div id="avisos" aria-live="polite"></div>

        <!-- ----- Vista: Resumen ----- -->
        <section id="vista-inicio">
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3"><div class="dato"><span class="dato-numero" id="totalLibros">-</span><span class="dato-texto">Libros en el catálogo</span></div></div>
                <div class="col-6 col-xl-3"><div class="dato"><span class="dato-numero" id="totalDisponibles">-</span><span class="dato-texto">Disponibles</span></div></div>
                <div class="col-6 col-xl-3"><div class="dato"><span class="dato-numero" id="totalCategorias">-</span><span class="dato-texto">Categorías</span></div></div>
                <div class="col-6 col-xl-3"><div class="dato"><span class="dato-numero" id="totalAutores">-</span><span class="dato-texto">Autores</span></div></div>
            </div>

            <div class="panel">
                <h2 class="panel-titulo">Gestión de libros</h2>
                <p class="mb-3">Agregá, modificá o eliminá los libros. Los cambios se ven enseguida en el catálogo de la página pública.</p>
                <a class="btn btn-primary" href="#libros" data-vista="libros">Administrar libros</a>
            </div>
        </section>

        <!-- ----- Vista: Libros (ABML) ----- -->
        <section id="vista-libros" hidden>
            <div class="panel">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="panel-titulo mb-0">Libros del catálogo</h2>
                    <button class="btn btn-primary" type="button" id="btnNuevo">Agregar libro</button>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle tabla-libros">
                        <thead>
                            <tr>
                                <th scope="col">Portada</th>
                                <th scope="col">Título</th>
                                <th scope="col">Autor</th>
                                <th scope="col">Categoría</th>
                                <th scope="col" class="text-end">Precio</th>
                                <th scope="col">Estado</th>
                                <th scope="col" class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaLibros">
                            <tr><td colspan="7" class="text-center py-4">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <!-- ===== MODAL: agregar / modificar libro ===== -->
    <div class="modal fade" id="modalForm" tabindex="-1" aria-labelledby="modalFormTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="formLibro" novalidate>
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="modalFormTitulo">Agregar libro</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger py-2" id="errorForm" role="alert" hidden></div>

                        <input type="hidden" id="libroId">
                        <input type="hidden" id="libroImagenActual">

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="libroTitulo" class="form-label">Título</label>
                                <input type="text" class="form-control" id="libroTitulo" maxlength="150" required>
                                <div class="invalid-feedback">Escribí el título del libro.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="libroPrecio" class="form-label">Precio ($)</label>
                                <input type="number" class="form-control" id="libroPrecio" min="0" step="0.01">
                                <div class="invalid-feedback">El precio no puede ser negativo.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="libroAutor" class="form-label">Autor</label>
                                <input type="text" class="form-control" id="libroAutor" list="listaAutores" maxlength="100" autocomplete="off" required>
                                <datalist id="listaAutores"></datalist>
                                <div class="form-text">Si el autor no existe, se crea al guardar.</div>
                                <div class="invalid-feedback">Escribí el nombre del autor.</div>
                            </div>
                            <div class="col-md-3">
                                <label for="libroCategoria" class="form-label">Categoría</label>
                                <select class="form-select" id="libroCategoria" required></select>
                                <div class="invalid-feedback">Elegí una categoría.</div>
                            </div>
                            <div class="col-md-3">
                                <label for="libroEstado" class="form-label">Estado</label>
                                <select class="form-select" id="libroEstado">
                                    <option value="1">Disponible</option>
                                    <option value="0">No disponible</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="libroDescripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="libroDescripcion" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <label for="libroArchivo" class="form-label">Portada</label>
                                <div class="d-flex gap-3 align-items-start">
                                    <img id="previewPortada" class="preview-portada" alt="Vista previa de la portada">
                                    <div class="flex-grow-1">
                                        <input type="file" class="form-control" id="libroArchivo" accept="image/jpeg,image/png,image/webp">
                                        <div class="form-text" id="ayudaPortada">JPG, PNG o WEBP de hasta 2 MB.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardar">Guardar libro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== MODAL: confirmar eliminación ===== -->
    <div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarTitulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="modalEliminarTitulo">Eliminar libro</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Vas a eliminar <strong id="eliminarTitulo"></strong>.</p>
                    <p class="mb-0">El libro desaparece también del catálogo público y no se puede recuperar.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminar">Eliminar libro</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/api.js"></script>
    <script src="../js/utils.js"></script>
    <script src="../js/admin.js"></script>
</body>
</html>
