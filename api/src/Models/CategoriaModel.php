<?php
namespace App\Models;

class CategoriaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar() {
        return $this->db->query('SELECT id_categoria, nombre_categoria, descripcion FROM CATEGORIAS ORDER BY nombre_categoria')
                        ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function existe($id) {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM CATEGORIAS WHERE id_categoria = :id');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
