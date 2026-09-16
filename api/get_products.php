<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    $category = trim($_GET['category'] ?? '');
    $search = trim($_GET['q'] ?? '');

    $sql = "SELECT id, nombre, descripcion, precio, categoria, imagen_url, stock, destacado, requiere_receta
            FROM productos
            WHERE 1=1";
    $types = '';
    $params = [];

    if ($category !== '' && $category !== 'todos') {
        $sql .= " AND categoria = ?";
        $types .= 's';
        $params[] = $category;
    }

    if ($search !== '') {
        $sql .= " AND (nombre LIKE ? OR descripcion LIKE ?)";
        $types .= 'ss';
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= " ORDER BY destacado DESC, id DESC";

    $stmt = $db->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($products as &$product) {
        $product['id'] = (int)$product['id'];
        $product['precio'] = (float)$product['precio'];
        $product['stock'] = (int)$product['stock'];
        $product['destacado'] = (bool)$product['destacado'];
        $product['requiere_receta'] = (bool)$product['requiere_receta'];
    }

    echo json_encode([
        'status' => 'success',
        'count' => count($products),
        'data' => $products
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al consultar los productos.'
    ], JSON_UNESCAPED_UNICODE);
}
