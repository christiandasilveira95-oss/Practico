<?php
// api/routes.php
// Acá se anota: "cuando pidan ESTA url, ejecutá ESTE controlador@método"
// El 3er parámetro 'admin' protege la ruta: solo la puede usar un administrador con sesión iniciada.

// --- Estado de la API ---
$router->get('/estado', 'EstadoController@ver');

// --- Autenticación (Práctico 2) ---
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');
$router->get('/me', 'AuthController@me');

// --- Catálogo público: lo lee la landing page ---
$router->get('/libros', 'LibroController@listar');
$router->get('/libros/:id', 'LibroController@ver');
$router->get('/categorias', 'CatalogoController@categorias');
$router->get('/autores', 'CatalogoController@autores');

// --- Administración de libros (Práctico 3): solo administradores ---
$router->post('/libros', 'LibroController@crear', 'admin');
$router->put('/libros/:id', 'LibroController@actualizar', 'admin');
$router->delete('/libros/:id', 'LibroController@eliminar', 'admin');
$router->post('/upload/portada', 'UploadController@portada', 'admin');
