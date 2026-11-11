<?php
namespace App\Controllers;

use App\Models\AutorModel;
use App\Models\CategoriaModel;

// Listados simples que usa el formulario del dashboard (categorías y autores)
class CatalogoController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // GET /api/categorias
    public function categorias() {
        echo json_encode(['status' => 'ok', 'data' => (new CategoriaModel($this->db))->listar()], JSON_UNESCAPED_UNICODE);
    }

    // GET /api/autores
    public function autores() {
        echo json_encode(['status' => 'ok', 'data' => (new AutorModel($this->db))->listar()], JSON_UNESCAPED_UNICODE);
    }
}
