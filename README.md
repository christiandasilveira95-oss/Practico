# Lofi-Library - Biblioteca Virtual

Proyecto continuo de los Prácticos 1, 2 y 3: la landing page del Práctico 1 ahora tiene base de datos, login con roles, dashboard de administración y un catálogo que se administra desde el panel.

**Stack:** HTML5 + CSS3 + Bootstrap 5 + JavaScript (front) · PHP 8.2 con API REST propia (router + controladores + modelos) · MySQL 8 · Docker.

## Cómo ejecutarlo

Requiere Docker Desktop. En la carpeta del proyecto:

```bash
docker compose up --build
```

| Qué | Dónde |
|---|---|
| Sitio público (landing) | http://localhost:8080/ (redirige a `/front/index.html`) |
| Iniciar sesión | http://localhost:8080/front/dist/pages/login.html |
| Dashboard de administración | http://localhost:8080/front/dist/pages/dashboard.php |
| Estado de la API | http://localhost:8080/api/estado |
| phpMyAdmin | http://localhost:8081 (servidor `db`, usuario `root`, contraseña `root_password`) |

La base `lofi_library` se crea sola la primera vez con `api/config/init.sql`. Si cambiás ese archivo y querés volver a crearla: `docker compose down -v` y de nuevo `docker compose up --build`.

> Al arrancar, Docker deja lista la carpeta `front/uploads` para guardar portadas (ver `command` en `docker-compose.yml`), así que no hace falta ningún `chmod` a mano.

### Usuarios de prueba

| Rol | Email | Contraseña |
|---|---|---|
| Administrador | admin@lofilibrary.com | Admin123! |
| Usuario | usuario@lofilibrary.com | Usuario123! |

Las contraseñas se guardan cifradas (`password_hash`, bcrypt), nunca en texto plano.

## Práctico 2 - dónde está cada cosa

| Ítem a verificar | Dónde |
|---|---|
| 1. MER | `docs/MER.png` (y `docs/MER.svg`) |
| 2. Pasaje a tablas | `docs/pasaje-a-tablas.md` |
| 3. Glosario de datos | `docs/glosario-de-datos.docx` (y `docs/glosario-de-datos.pdf`) |
| 4. Base de datos implementada | `api/config/init.sql` (tablas `ROLES`, `USUARIOS`, `AUTORES`, `CATEGORIAS`, `LIBROS`) |
| 5. Login funcionando | `front/dist/pages/login.html` + `front/dist/js/login.js` + `api/src/Controllers/AuthController.php` |
| 6. Usuario vs administrador | tabla `ROLES` / `USUARIOS.id_rol` · `api/src/Utils/Auth.php` |
| 7. Dashboard del administrador | `front/dist/pages/dashboard.php` + `front/dist/css/admin.css` + `front/dist/js/admin.js` |
| 8. Redirección según el rol | La API responde `redirect`: administrador → `/front/dist/pages/dashboard.php`, usuario → `/front/dist/pages/usuario.html` |
| 9. Protección del acceso administrativo | Ver abajo |
| 10. Proyecto completo | Este repositorio |

### Protección de las rutas administrativas

1. **Dashboard:** `front/dist/pages/dashboard.php` comprueba la sesión en el servidor antes de enviar HTML. Sin sesión → redirige a `login.html`. Con sesión de usuario común → redirige a `usuario.html` (ambas en `front/dist/pages/`).
2. **API:** las rutas de escritura llevan `'admin'` en `api/routes.php`; el router (`Router.php`) llama a `Auth::requerirAdmin()` y responde **401** (sin sesión) o **403** (sin permisos) antes de llegar al controlador.
3. **Sesión:** cookie `HttpOnly` + `SameSite=Lax`, `session_regenerate_id` al iniciar sesión, mensaje de error genérico ("Email o contraseña incorrectos").
4. **Archivos sensibles:** `.htaccess` impide abrir por URL `api/.env`, `api/config/` y `api/src/`, y evita ejecutar PHP dentro de `front/uploads/`.

## Práctico 3 - gestión de artículos

Flujo: administrador inicia sesión → dashboard → **Libros** → agrega, modifica o elimina → vuelve a la landing y el catálogo ya refleja el cambio.

- El catálogo de `front/index.html` ya no tiene libros escritos a mano: `front/dist/js/script.js` los pide a `GET /api/libros` y dibuja las tarjetas (misma estructura y estilos del Práctico 1).
- "Ver más" abre un modal con el detalle (autor, categoría, descripción, precio, disponibilidad).
- Los 10 libros originales están cargados en `init.sql`. Los precios son valores de ejemplo, editables desde el dashboard.
- Las portadas nuevas se guardan en `front/uploads/portadas/` (JPG, PNG o WEBP, hasta 2 MB; se valida el contenido real del archivo).

## API

Base: `/api`. Todas las respuestas son JSON (`status: "ok" | "error"`).

| Método | Ruta | Acceso | Qué hace |
|---|---|---|---|
| POST | `/login` | público | Inicia sesión; devuelve el usuario y la ruta (`redirect`) según su rol |
| POST | `/logout` | público | Cierra la sesión |
| GET | `/me` | sesión | Usuario de la sesión actual (401 si no hay) |
| GET | `/libros` · `/libros/:id` | público | Catálogo |
| GET | `/categorias` · `/autores` | público | Listados para el formulario |
| POST | `/libros` | **admin** | Agrega un libro |
| PUT | `/libros/:id` | **admin** | Modifica un libro |
| DELETE | `/libros/:id` | **admin** | Elimina un libro |
| POST | `/upload/portada` | **admin** | Sube una imagen de portada |
| GET | `/estado` | público | Comprueba API y base de datos |

## Estructura

El front sigue la plantilla de la cátedra (`front/index.html` + `front/dist/`); la API está en `api/`.

```
index.html                           redirige a /front/index.html
front/
├─ index.html                        landing (catálogo dinámico)
├─ assets/img/                       portadas originales del Práctico 1 + imagen del login
├─ uploads/portadas/                 portadas subidas desde el dashboard
└─ dist/
   ├─ css/   style.css (sitio) · mobile.css · login.css · admin.css
   ├─ js/    script.js (landing) · api.js · utils.js · login.js · usuario.js · admin.js
   └─ pages/ login.html · usuario.html · dashboard.php (con guard de sesión en el servidor)
api/                                 API PHP (routes.php, Controllers, Models, Utils)
api/config/init.sql                  script de la base de datos
docs/                                MER, pasaje a tablas, glosario de datos
docker-compose.yml · Dockerfile · .env
```
