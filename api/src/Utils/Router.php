<?php
namespace App\Utils;

class Router {
    private $routes = [];
    private $basePath;
    private $db;

    /** Si es true, captura la salida del controlador y envía Server-Timing (duración ms). Ver CHECKLIST-OPTIMIZACION-TABLAS-CARGAS §5 Fase 1. */
    private function serverTimingEnabled(): bool {
        $v = getenv('API_SERVER_TIMING');
        if ($v === false || $v === '') {
            return false;
        }
        $v = strtolower(trim((string) $v));
        return in_array($v, ['1', 'true', 'yes', 'on'], true);
    }

    public function __construct($basePath, $db) {
        $this->basePath = $basePath;
        $this->db = $db;
    }

    // El 3er parámetro es opcional: 'admin' protege la ruta (solo administradores logueados)
    public function get($path, $handler, $permiso = null) { $this->addRoute('GET', $path, $handler, $permiso); }
    public function post($path, $handler, $permiso = null) { $this->addRoute('POST', $path, $handler, $permiso); }
    public function put($path, $handler, $permiso = null) { $this->addRoute('PUT', $path, $handler, $permiso); }
    public function delete($path, $handler, $permiso = null) { $this->addRoute('DELETE', $path, $handler, $permiso); }

    private function addRoute($method, $path, $handler, $permiso = null) {
        // Convertimos los parámetros como :slug en expresiones regulares ([a-zA-Z0-9-]+)
        $pattern = preg_replace('/:[a-zA-Z0-9]+/', '([a-zA-Z0-9_-]+)', $path);
        
        // Hacemos que la barra final sea opcional en la regex agregando /?
        // Esto permite que /api/users y /api/users/ funcionen igual
        $this->routes[] = [
            'method' => $method,
            'pattern' => "#^" . $this->basePath . $pattern . "/?$#",
            'handler' => $handler,
            'permiso' => $permiso
        ];
    }

    public function run() {
        // Capturamos la URI pura (sin ?variables=get)
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // ============================================================
        // BLINDAJE NGINX: Limpieza de artefactos en la URI
        // ============================================================
        // NGINX suele inyectar /index.php en el REQUEST_URI tras un try_files.
        // Si no lo limpiamos, la regex falla.
        $uri = str_replace('/index.php', '', $uri);

        // Si después de limpiar quedó vacío (raro, pero posible en la raíz de la api), forzamos barra
        if (empty($uri)) {
            $uri = '/';
        }

        // Iteramos las rutas buscando coincidencias
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches); // Eliminar el match completo (dejando solo los parámetros capturados)

                // Protección de rutas: si la ruta exige 'admin' y no hay un administrador logueado,
                // Auth responde 401/403 y corta acá, sin llegar al controlador.
                if ($route['permiso'] === 'admin') {
                    \App\Utils\Auth::requerirAdmin();
                }
                if ($this->serverTimingEnabled()) {
                    ob_start();
                    $t0 = microtime(true);
                    try {
                        $this->resolve($route['handler'], $matches);
                    } finally {
                        $durMs = (microtime(true) - $t0) * 1000.0;
                        header('Server-Timing: app;dur=' . round($durMs, 2));
                        echo ob_get_clean();
                    }
                    return;
                }
                return $this->resolve($route['handler'], $matches);
            }
        }

        // Si el bucle termina sin hacer return, es un 404 real.
        http_response_code(404);
        echo json_encode([
            "status" => "error", 
            "message" => "Ruta no encontrada en la API",
            "debug_uri" => $uri,        // Útil para ver qué está leyendo exactamente PHP
            "debug_base" => $this->basePath
        ]);
    }

    private function resolve($handler, $params) {
        // Separamos el Nombre del Controlador y el Método
        list($controllerName, $method) = explode('@', $handler);
        $controllerClass = "App\\Controllers\\" . $controllerName;

        if (class_exists($controllerClass)) {
            // Instanciamos inyectando PDO para cumplir con el estándar
            $controller = new $controllerClass($this->db);

            if (method_exists($controller, $method)) {
                // Ejecutamos pasándole los parámetros extraídos de la URL
                return call_user_func_array([$controller, $method], $params);
            }
        }

        // Error interno de código (500)
        http_response_code(500);
        echo json_encode([
            "status" => "error", 
            "message" => "Error interno: Controlador ($controllerName) o método ($method) no válido"
        ]);
    }
}