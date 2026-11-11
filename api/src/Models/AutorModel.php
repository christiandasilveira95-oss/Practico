<?php
namespace App\Models;

class AutorModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function listar() {
        return $this->db->query('SELECT id_autor, nombre_autor, nacionalidad FROM AUTORES ORDER BY nombre_autor')
                        ->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Si el autor ya existe devuelve su id; si no, lo crea.
    // Así el administrador puede escribir un autor nuevo directamente en el formulario del libro.
    public function buscarOCrear($nombre) {
        $stmt = $this->db->prepare('SELECT id_autor FROM AUTORES WHERE nombre_autor = :n');
        $stmt->execute([':n' => $nombre]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }
        $stmt = $this->db->prepare('INSERT INTO AUTORES (nombre_autor) VALUES (:n)');
        $stmt->execute([':n' => $nombre]);
        return (int) $this->db->lastInsertId();
    }

    public function contar() {
        return (int) $this->db->query('SELECT COUNT(*) FROM AUTORES')->fetchColumn();
    }
}
