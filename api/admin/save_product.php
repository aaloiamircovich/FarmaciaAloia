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
    $nombre = trim((string)($data['nombre'] ?? ''));
    $descripcion = trim((string)($data['descripcion'] ?? ''));
    $precio = (float)($data['precio'] ?? 0);
    $categoria = strtolower(trim((string)($data['categoria'] ?? 'medicamentos')));
    $imagenUrl = trim((string)($data['imagen_url'] ?? ''));
    $stock = max(0, (int)($data['stock'] ?? 0));
    $destacado = !empty($data['destacado']) ? 1 : 0;
    $requiereReceta = !empty($data['requiere_receta']) ? 1 : 0;

    $allowedCategories = ['medicamentos', 'perfumeria', 'higiene', 'cuidado_personal', 'bebe'];

    if ($nombre === '') {
        throw new InvalidArgumentException('El nombre del producto es obligatorio.');
    }

    if ($precio <= 0) {
        throw new InvalidArgumentException('El precio debe ser mayor a $0.');
    }

    if (!in_array($categoria, $allowedCategories, true)) {
        throw new InvalidArgumentException('La categoría seleccionada no es válida.');
    }

    if ($imagenUrl === '') {
        $imagenUrl = $requiereReceta ? 'public/img/receta.svg' : 'public/img/medicamento.svg';
    }

    // Solo los medicamentos pueden marcarse bajo receta.
    if ($categoria !== 'medicamentos') {
        $requiereReceta = 0;
    }

    $db = Database::getConnection();

    if ($id > 0) {
        $stmt = $db->prepare(
            'UPDATE productos
             SET nombre = ?, descripcion = ?, precio = ?, categoria = ?, imagen_url = ?, stock = ?, destacado = ?, requiere_receta = ?
             WHERE id = ?'
        );
        $stmt->bind_param(
            'ssdssiiii',
            $nombre,
            $descripcion,
            $precio,
            $categoria,
            $imagenUrl,
            $stock,
            $destacado,
            $requiereReceta,
            $id
        );
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'Producto actualizado correctamente.',
            'product_id' => $id
        ], JSON_UNESCAPED_UNICODE);
    } else {
        $stmt = $db->prepare(
            'INSERT INTO productos (nombre, descripcion, precio, categoria, imagen_url, stock, destacado, requiere_receta)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'ssdssiii',
            $nombre,
            $descripcion,
            $precio,
            $categoria,
            $imagenUrl,
            $stock,
            $destacado,
            $requiereReceta
        );
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'Producto creado correctamente.',
            'product_id' => (int)$db->insert_id
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (mysqli_sql_exception $e) {
    http_response_code(400);
    $message = str_contains($e->getMessage(), 'Duplicate entry')
        ? 'Ya existe un producto con ese nombre.'
        : 'No se pudo guardar el producto.';
    echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
