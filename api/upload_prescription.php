<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../env.php';
require_once __DIR__ . '/../config/session.php';

ensureAppSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (!isset($_FILES['receta'])) {
        throw new RuntimeException('Seleccioná una imagen de la receta médica.');
    }

    $file = $_FILES['receta'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No se pudo subir la imagen. Intentá nuevamente.');
    }

    if ((int)$file['size'] <= 0 || (int)$file['size'] > PRESCRIPTION_MAX_BYTES) {
        throw new RuntimeException('La imagen debe pesar como máximo 5 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Formato no permitido. Usá JPG, PNG o WEBP.');
    }

    $uploadDir = getPrescriptionUploadDir();
    if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
        throw new RuntimeException('El directorio de recetas no está disponible para escritura.');
    }

    $token = bin2hex(random_bytes(24));
    $filename = 'receta_' . date('Ymd_His') . '_' . bin2hex(random_bytes(5)) . '.' . $allowed[$mime];
    $destination = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('No se pudo guardar la receta en el servidor.');
    }

    if (!isset($_SESSION['prescriptions']) || !is_array($_SESSION['prescriptions'])) {
        $_SESSION['prescriptions'] = [];
    }

    // Limpiar tokens antiguos de más de 24 horas.
    $now = time();
    foreach ($_SESSION['prescriptions'] as $oldToken => $entry) {
        if (($entry['created_at'] ?? 0) < ($now - 86400)) {
            unset($_SESSION['prescriptions'][$oldToken]);
        }
    }

    $_SESSION['prescriptions'][$token] = [
        'filename' => $filename,
        'original_name' => basename((string)$file['name']),
        'created_at' => $now
    ];

    echo json_encode([
        'status' => 'success',
        'message' => 'Receta cargada correctamente.',
        'token' => $token,
        'filename' => basename((string)$file['name'])
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
