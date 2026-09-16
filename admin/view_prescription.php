<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
requireAdminAuth();

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    http_response_code(400);
    exit('Orden inválida.');
}

$db = Database::getConnection();
$stmt = $db->prepare('SELECT receta_archivo FROM ordenes WHERE id = ?');
$stmt->bind_param('i', $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order || empty($order['receta_archivo'])) {
    http_response_code(404);
    exit('La orden no tiene receta adjunta.');
}

$allowedDir = getPrescriptionUploadDir();
$filename = basename((string)$order['receta_archivo']);
$filePath = realpath($allowedDir . DIRECTORY_SEPARATOR . $filename);

if (
    $filePath === false ||
    !str_starts_with($filePath, rtrim($allowedDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) ||
    !is_file($filePath)
) {
    http_response_code(404);
    exit('No se encontró el archivo.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($filePath) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: inline; filename="receta-orden-' . $orderId . '.' . pathinfo($filePath, PATHINFO_EXTENSION) . '"');
header('X-Content-Type-Options: nosniff');
readfile($filePath);
exit;
