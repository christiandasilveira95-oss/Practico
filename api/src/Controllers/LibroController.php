<?php
namespace App\Controllers;

use App\Models\LibroModel;
use App\Models\AutorModel;
use App\Models\CategoriaModel;
use App\Utils\Auth;

class LibroController {
    private $db;
    private $libros;

    public function __construct($db) {
        $this->db = $db;
        $this->libros = new LibroModel($db);
    }

    // GET /api/libros  (público: lo usa el catálogo de la landing)
    public function listar() {
        $this->responder(200, ['status' => 'ok', 'data' => $this->libros->listar()]);
    }

    // GET /api/libros/:id  (público)
    public function ver($id) {
        $libro = ctype_digit($id) ? $this->libros->obtener((int) $id) : null;
        if (!$libro) {
            return $this->error(404, 'El libro no existe');
        }
        $this->responder(200, ['status' => 'ok', 'data' => $libro]);
    }

    // POST /api/libros  (solo administrador, ver routes.php)
    public function crear() {
        $datos = $this->leerYValidar();
        if ($datos === null) return;

        $idAutor = (new AutorModel($this->db))->buscarOCrear($datos['autor']);
        $idUsuario = Auth::usuario()['id_usuario'];
        $id = $this->libros->crear($datos, $idAutor, $idUsuario);

        $this->responder(201, ['status' => 'ok', 'message' => 'Libro agregado', 'data' => $this->libros->obtener($id)]);
    }

    // PUT /api/libros/:id  (solo administrador)
    public function actualizar($id) {
        $actual = ctype_digit($id) ? $this->libros->obtener((int) $id) : null;
        if (!$actual) {
            return $this->error(404, 'El libro no existe');
        }
        $datos = $this->leerYValidar();
        if ($datos === null) return;

        $idAutor = (new AutorModel($this->db))->buscarOCrear($datos['autor']);
        $this->libros->actualizar((int) $id, $datos, $idAutor);

        // Si se cambió la portada, se borra la anterior (solo si la subió el admin)
        if ($actual['imagen'] !== $datos['imagen']) {
            $this->borrarImagenSubida($actual['imagen']);
        }
        $this->responder(200, ['status' => 'ok', 'message' => 'Libro actualizado', 'data' => $this->libros->obtener((int) $id)]);
    }

    // DELETE /api/libros/:id  (solo administrador)
    public function eliminar($id) {
        $actual = ctype_digit($id) ? $this->libros->obtener((int) $id) : null;
        if (!$actual) {
            return $this->error(404, 'El libro no existe');
        }
        $this->libros->eliminar((int) $id);
        $this->borrarImagenSubida($actual['imagen']);
        $this->responder(200, ['status' => 'ok', 'message' => 'Libro eliminado']);
    }

    // ------------------------------------------------------------------

    // Lee el JSON del body, lo limpia y lo valida. Devuelve null (y ya respondió) si hay un error.
    private function leerYValidar() {
        $d = json_decode(file_get_contents('php://input'), true);
        if (!is_array($d)) {
            $this->error(400, 'El cuerpo de la petición no es un JSON válido');
            return null;
        }

        $titulo = trim($d['titulo'] ?? '');
        $autor = trim($d['autor'] ?? '');
        $descripcion = trim($d['descripcion'] ?? '');
        $idCategoria = $d['id_categoria'] ?? '';
        $imagen = trim($d['imagen'] ?? '');
        $precio = $d['precio'] ?? null;

        if ($titulo === '' || mb_strlen($titulo) > 150) {
            $this->error(422, 'El título es obligatorio (máximo 150 caracteres)');
            return null;
        }
        if ($autor === '' || mb_strlen($autor) > 100) {
            $this->error(422, 'El autor es obligatorio (máximo 100 caracteres)');
            return null;
        }
        if (!ctype_digit((string) $idCategoria) || !(new CategoriaModel($this->db))->existe((int) $idCategoria)) {
            $this->error(422, 'Elegí una categoría válida');
            return null;
        }
        if ($precio === '' || $precio === null) {
            $precio = null;
        } elseif (!is_numeric($precio) || $precio < 0 || $precio > 99999999) {
            $this->error(422, 'El precio debe ser un número mayor o igual a 0');
            return null;
        }
        // Solo se aceptan portadas del proyecto (assets/img/...) o subidas por el admin (uploads/portadas/...)
        if ($imagen !== '' && !preg_match('#^(assets/img|uploads/portadas)/[^/\\\\]+$#', $imagen)) {
            $this->error(422, 'La ruta de la imagen no es válida');
            return null;
        }

        return [
            'titulo' => $titulo,
            'autor' => $autor,
            'descripcion' => $descripcion === '' ? null : $descripcion,
            'id_categoria' => (int) $idCategoria,
            'imagen' => $imagen === '' ? null : $imagen,
            'precio' => $precio === null ? null : round((float) $precio, 2),
            'disponible' => empty($d['disponible']) ? 0 : 1,
        ];
    }

    // Borra el archivo de la portada solo si fue subido desde el dashboard (no toca assets/img)
    private function borrarImagenSubida($ruta) {
        if ($ruta && strpos($ruta, 'uploads/portadas/') === 0) {
            $archivo = dirname(__DIR__, 3) . '/front/' . $ruta;
            if (is_file($archivo)) {
                @unlink($archivo);
            }
        }
    }

    private function responder($codigo, $cuerpo) {
        http_response_code($codigo);
        echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
    }

    private function error($codigo, $mensaje) {
        $this->responder($codigo, ['status' => 'error', 'message' => $mensaje]);
    }
}
