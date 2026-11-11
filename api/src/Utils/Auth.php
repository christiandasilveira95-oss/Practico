<?php
namespace App\Utils;

/**
 * Manejo de sesión y roles.
 * Guarda en $_SESSION['usuario'] los datos del usuario logueado (sin la contraseña).
 * Se usa desde la API (controladores/router) y desde admin/dashboard.php.
 */
class Auth {
    const ROL_ADMIN = 'Administrador';
    const ROL_USUARIO = 'Usuario';

    // Rutas a las que se redirige según el rol (Fase 3 del Práctico 2)
    const RUTA_ADMIN = '/front/dist/pages/dashboard.php';
    const RUTA_USUARIO = '/front/dist/pages/usuario.html';
    const RUTA_LOGIN = '/front/dist/pages/login.html';

    public static function iniciarSesion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'path' => '/',
                'httponly' => true,   // JavaScript no puede leer la cookie de sesión
                'samesite' => 'Lax',  // no se envía en peticiones desde otros sitios
            ]);
            session_start();
        }
    }

    // Guarda al usuario en la sesión (después de validar su contraseña)
    public static function login(array $usuario) {
        self::iniciarSesion();
        session_regenerate_id(true); // evita "session fixation"
        $_SESSION['usuario'] = [
            'id_usuario' => (int) $usuario['id_usuario'],
            'nombre' => $usuario['nombre'],
            'email' => $usuario['email'],
            'rol' => $usuario['nombre_rol'],
        ];
    }

    public static function logout() {
        self::iniciarSesion();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // Devuelve el usuario logueado o null
    public static function usuario() {
        self::iniciarSesion();
        return $_SESSION['usuario'] ?? null;
    }

    public static function esAdmin() {
        $u = self::usuario();
        return $u !== null && $u['rol'] === self::ROL_ADMIN;
    }

    // A qué página debe ir cada rol
    public static function rutaSegunRol($rol) {
        return $rol === self::ROL_ADMIN ? self::RUTA_ADMIN : self::RUTA_USUARIO;
    }

    // Protege una ruta de la API: corta la ejecución con 401 (sin sesión) o 403 (sin permisos)
    public static function requerirAdmin() {
        $u = self::usuario();
        if ($u === null) {
            self::cortar(401, 'Tenés que iniciar sesión');
        }
        if ($u['rol'] !== self::ROL_ADMIN) {
            self::cortar(403, 'No tenés permisos de administrador');
        }
    }

    private static function cortar($codigo, $mensaje) {
        http_response_code($codigo);
        echo json_encode(['status' => 'error', 'message' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
