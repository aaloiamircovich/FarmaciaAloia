<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

requireAdminAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $id = (int)($data['id'] ?? 0);

    if ($id <= 0) {
        throw new InvalidArgumentException('ID de producto inválido.');
    }

    $db = Database::getConnection();
    $stmt = $db->prepare('DELETE FROM productos WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->affected_rows < 1) {
        throw new RuntimeException('No se encontró el producto.');
    }

    echo json_encode(['status' => 'success', 'message' => 'Producto eliminado.'], JSON_UNESCAPED_UNICODE);
} catch (mysqli_sql_exception $e) {
    http_response_code(409);
    echo json_encode([
        'status' => 'error',
        'message' => 'Ese producto ya aparece en una orden y no puede eliminarse. Podés dejar su stock en 0.'
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
