<?php
namespace App\Models;

class LibroModel {
    private $db;

    // Se repite en listar() y obtener(): libro + nombre del autor + nombre de la categoría
    private $consultaBase = 'SELECT l.id_libro, l.titulo, l.descripcion, l.imagen, l.precio, l.disponible, l.fecha_alta,
                                    l.id_autor, a.nombre_autor AS autor,
                                    l.id_categoria, c.nombre_categoria AS categoria
                             FROM LIBROS l
                             INNER JOIN AUTORES a ON a.id_autor = l.id_autor
                             INNER JOIN CATEGORIAS c ON c.id_categoria = l.id_categoria';

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar() {
        $filas = $this->db->query($this->consultaBase . ' ORDER BY l.id_libro')->fetchAll(\PDO::FETCH_ASSOC);
        return array_map([$this, 'formatear'], $filas);
    }

    public function obtener($id) {
        $stmt = $this->db->prepare($this->consultaBase . ' WHERE l.id_libro = :id');
        $stmt->execute([':id' => $id]);
        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $fila ? $this->formatear($fila) : null;
    }

    public function crear($d, $idAutor, $idUsuario) {
        $sql = 'INSERT INTO LIBROS (titulo, descripcion, id_autor, id_categoria, imagen, precio, disponible, id_usuario_alta)
                VALUES (:titulo, :descripcion, :id_autor, :id_categoria, :imagen, :precio, :disponible, :id_usuario)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':titulo' => $d['titulo'],
            ':descripcion' => $d['descripcion'],
            ':id_autor' => $idAutor,
            ':id_categoria' => $d['id_categoria'],
            ':imagen' => $d['imagen'],
            ':precio' => $d['precio'],
            ':disponible' => $d['disponible'],
            ':id_usuario' => $idUsuario,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function actualizar($id, $d, $idAutor) {
        $sql = 'UPDATE LIBROS
                SET titulo = :titulo, descripcion = :descripcion, id_autor = :id_autor,
                    id_categoria = :id_categoria, imagen = :imagen, precio = :precio, disponible = :disponible
                WHERE id_libro = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':titulo' => $d['titulo'],
            ':descripcion' => $d['descripcion'],
            ':id_autor' => $idAutor,
            ':id_categoria' => $d['id_categoria'],
            ':imagen' => $d['imagen'],
            ':precio' => $d['precio'],
            ':disponible' => $d['disponible'],
            ':id' => $id,
        ]);
    }

    public function eliminar($id) {
        $stmt = $this->db->prepare('DELETE FROM LIBROS WHERE id_libro = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    public function contar() {
        return (int) $this->db->query('SELECT COUNT(*) FROM LIBROS')->fetchColumn();
    }

    // MySQL devuelve todo como texto; acá se pasa a los tipos correctos para el JSON
    private function formatear($f) {
        $f['id_libro'] = (int) $f['id_libro'];
        $f['id_autor'] = (int) $f['id_autor'];
        $f['id_categoria'] = (int) $f['id_categoria'];
        $f['precio'] = $f['precio'] !== null ? (float) $f['precio'] : null;
        $f['disponible'] = (int) $f['disponible'];
        return $f;
    }
}
