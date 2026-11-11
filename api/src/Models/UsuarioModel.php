<?php
namespace App\Models;

class UsuarioModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Busca un usuario por email junto con el nombre de su rol
    public function buscarPorEmail($email) {
        $sql = 'SELECT u.id_usuario, u.nombre, u.email, u.password_hash, u.activo, r.nombre_rol
                FROM USUARIOS u
                INNER JOIN ROLES r ON r.id_rol = u.id_rol
                WHERE u.email = :email';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function registrarAcceso($idUsuario) {
        $stmt = $this->db->prepare('UPDATE USUARIOS SET ultimo_acceso = NOW() WHERE id_usuario = :id');
        $stmt->execute([':id' => $idUsuario]);
    }

    public function contar() {
        return (int) $this->db->query('SELECT COUNT(*) FROM USUARIOS')->fetchColumn();
    }
}
