<?php
namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Utils\Auth;

class AuthController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // POST /api/login   body: { "email": "...", "password": "..." }
    public function login() {
        $datos = json_decode(file_get_contents('php://input'), true) ?: [];
        $email = trim($datos['email'] ?? '');
        $password = $datos['password'] ?? '';

        if ($email === '' || $password === '') {
            return $this->error(400, 'Ingresá tu email y tu contraseña');
        }

        $model = new UsuarioModel($this->db);
        $usuario = $model->buscarPorEmail($email);

        // Mismo mensaje si el email no existe o la contraseña es incorrecta
        // (así no se le dice a un atacante qué emails están registrados)
        if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
            return $this->error(401, 'Email o contraseña incorrectos');
        }
        if ((int) $usuario['activo'] !== 1) {
            return $this->error(403, 'Tu cuenta está desactivada');
        }

        Auth::login($usuario);
        $model->registrarAcceso($usuario['id_usuario']);

        // El servidor decide a dónde va cada rol; el front solo obedece.
        echo json_encode([
            'status' => 'ok',
            'usuario' => Auth::usuario(),
            'redirect' => Auth::rutaSegunRol($usuario['nombre_rol']),
        ], JSON_UNESCAPED_UNICODE);
    }

    // POST /api/logout
    public function logout() {
        Auth::logout();
        echo json_encode(['status' => 'ok', 'message' => 'Sesión cerrada'], JSON_UNESCAPED_UNICODE);
    }

    // GET /api/me  -> usuario de la sesión actual (o 401 si no hay sesión)
    public function me() {
        $usuario = Auth::usuario();
        if ($usuario === null) {
            return $this->error(401, 'No hay una sesión iniciada');
        }
        echo json_encode([
            'status' => 'ok',
            'usuario' => $usuario,
            'redirect' => Auth::rutaSegunRol($usuario['rol']),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function error($codigo, $mensaje) {
        http_response_code($codigo);
        echo json_encode(['status' => 'error', 'message' => $mensaje], JSON_UNESCAPED_UNICODE);
    }
}
