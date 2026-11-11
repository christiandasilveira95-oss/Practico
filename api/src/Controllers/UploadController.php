<?php
namespace App\Controllers;

class UploadController {
    private $db;

    // Tipos de imagen permitidos (se comprueba el contenido real del archivo, no solo la extensión)
    private $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private $maxBytes = 2 * 1024 * 1024; // 2 MB

    public function __construct($db) {
        $this->db = $db;
    }

    // POST /api/upload/portada   (multipart/form-data, campo "imagen") - solo administrador
    public function portada() {
        // Si el archivo supera post_max_size, PHP deja $_FILES vacío
        if (empty($_FILES['imagen'])) {
            return $this->error(400, 'No se recibió ninguna imagen (o supera el tamaño máximo de 2 MB)');
        }
        $archivo = $_FILES['imagen'];

        if ($archivo['error'] === UPLOAD_ERR_INI_SIZE || $archivo['error'] === UPLOAD_ERR_FORM_SIZE || $archivo['size'] > $this->maxBytes) {
            return $this->error(422, 'La imagen supera el tamaño máximo de 2 MB');
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return $this->error(400, 'No se pudo subir la imagen');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        if (!isset($this->permitidos[$mime])) {
            return $this->error(422, 'Formato no permitido: usá JPG, PNG o WEBP');
        }

        $carpeta = dirname(__DIR__, 3) . '/front/uploads/portadas';
        if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true)) {
            return $this->error(500, 'No se pudo crear la carpeta uploads/portadas');
        }

        // Nombre aleatorio: evita pisar archivos y nombres con caracteres raros
        $nombre = bin2hex(random_bytes(8)) . '.' . $this->permitidos[$mime];
        if (!move_uploaded_file($archivo['tmp_name'], $carpeta . '/' . $nombre)) {
            return $this->error(500, 'No se pudo guardar la imagen (revisá los permisos de uploads/)');
        }

        http_response_code(201);
        echo json_encode(['status' => 'ok', 'ruta' => 'uploads/portadas/' . $nombre], JSON_UNESCAPED_UNICODE);
    }

    private function error($codigo, $mensaje) {
        http_response_code($codigo);
        echo json_encode(['status' => 'error', 'message' => $mensaje], JSON_UNESCAPED_UNICODE);
    }
}
