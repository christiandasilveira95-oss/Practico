<?php
namespace App\Controllers;

use App\Models\LibroModel;
use App\Models\UsuarioModel;

// GET /api/estado: comprueba que la API y la base de datos responden
class EstadoController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function ver() {
        $respuesta = [
            "status" => "ok",
            "message" => "La API responde",
            "base_datos" => "conectada",
        ];

        // Si las tablas no existen (init.sql sin ejecutar) PDO lanza una excepción
        try {
            $respuesta["cantidad_libros"] = (new LibroModel($this->db))->contar();
            $respuesta["cantidad_usuarios"] = (new UsuarioModel($this->db))->contar();
        } catch (\Exception $e) {
            $respuesta["tablas"] = "Faltan las tablas: ejecutá api/config/init.sql (phpMyAdmin o docker compose down -v)";
        }

        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    }
}
