<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../env.php';

ensureAppSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (MP_ACCESS_TOKEN === '' || str_contains(MP_ACCESS_TOKEN, 'PEGAR_AQUI')) {
        throw new RuntimeException('Antes de pagar tenés que configurar MP_ACCESS_TOKEN en el archivo .env.');
    }

    if (!function_exists('curl_init')) {
        throw new RuntimeException('La extensión cURL de PHP no está habilitada.');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data) || empty($data['items']) || !is_array($data['items'])) {
        throw new InvalidArgumentException('El carrito está vacío o el formato es inválido.');
    }

    $prescriptionToken = trim((string)($data['prescription_token'] ?? ''));

    $db = Database::getConnection();
    $db->begin_transaction();

    $orderItems = [];
    $mpItems = [];
    $montoTotal = 0.0;
    $needsPrescription = false;

    $stmtProduct = $db->prepare(
        'SELECT id, nombre, descripcion, precio, stock, requiere_receta
         FROM productos
         WHERE id = ?
         FOR UPDATE'
    );

    foreach ($data['items'] as $item) {
        $productId = (int)($item['id'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);

        if ($productId <= 0 || $quantity <= 0) {
            continue;
        }

        $stmtProduct->bind_param('i', $productId);
        $stmtProduct->execute();
        $product = $stmtProduct->get_result()->fetch_assoc();

        if (!$product) {
            throw new RuntimeException("No se encontró el producto ID {$productId}.");
        }

        if ((int)$product['stock'] < $quantity) {
            throw new RuntimeException(
                "Stock insuficiente para '{$product['nombre']}'. Disponible: {$product['stock']}."
            );
        }

        if ((int)$product['requiere_receta'] === 1) {
            $needsPrescription = true;
        }

        $price = (float)$product['precio'];
        $montoTotal += $price * $quantity;

        $orderItems[] = [
            'producto_id' => (int)$product['id'],
            'cantidad' => $quantity,
            'precio_unitario' => $price
        ];

        $mpItems[] = [
            'id' => (string)$product['id'],
            'title' => (string)$product['nombre'],
            'description' => mb_substr((string)($product['descripcion'] ?: $product['nombre']), 0, 255),
            'quantity' => $quantity,
            'currency_id' => 'ARS',
            'unit_price' => $price
        ];
    }

    if (empty($mpItems)) {
        throw new RuntimeException('No hay productos válidos en el carrito.');
    }

    $prescriptionPath = null;

    if ($needsPrescription) {
        if ($prescriptionToken === '') {
            throw new RuntimeException('El pedido contiene medicamentos bajo receta. Debés cargar la receta antes de pagar.');
        }

        $entry = $_SESSION['prescriptions'][$prescriptionToken] ?? null;
        if (!is_array($entry) || empty($entry['filename'])) {
            throw new RuntimeException('La receta cargada ya no es válida. Volvé a cargarla antes de pagar.');
        }

        $filename = basename((string)$entry['filename']);
        $allowedDir = getPrescriptionUploadDir();
        $absolutePath = realpath($allowedDir . DIRECTORY_SEPARATOR . $filename);

        if (
            $absolutePath === false ||
            !str_starts_with($absolutePath, rtrim($allowedDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) ||
            !is_file($absolutePath)
        ) {
            throw new RuntimeException('No se encontró la imagen de la receta. Volvé a cargarla.');
        }

        // En DB guardamos solo el nombre aleatorio, nunca una ruta pública.
        $prescriptionPath = $filename;
    }

    $externalReference = 'FARMA-' . time() . '-' . strtoupper(bin2hex(random_bytes(3)));

    $stmtOrder = $db->prepare(
        "INSERT INTO ordenes (external_reference, monto_total, estado, receta_archivo, created_at)
         VALUES (?, ?, 'pending', ?, NOW())"
    );
    $stmtOrder->bind_param('sds', $externalReference, $montoTotal, $prescriptionPath);
    $stmtOrder->execute();
    $orderId = (int)$db->insert_id;

    $stmtItem = $db->prepare(
        'INSERT INTO orden_items (orden_id, producto_id, cantidad, precio_unitario)
         VALUES (?, ?, ?, ?)'
    );

    foreach ($orderItems as $orderItem) {
        $stmtItem->bind_param(
            'iiid',
            $orderId,
            $orderItem['producto_id'],
            $orderItem['cantidad'],
            $orderItem['precio_unitario']
        );
        $stmtItem->execute();
    }

    $baseUrl = rtrim(BASE_URL, '/');
    $host = parse_url($baseUrl, PHP_URL_HOST) ?: '';
    $scheme = strtolower((string)(parse_url($baseUrl, PHP_URL_SCHEME) ?: 'http'));

    $payload = [
        'items' => $mpItems,
        'back_urls' => [
            'success' => $baseUrl . '/public/success.php',
            'pending' => $baseUrl . '/public/pending.php',
            'failure' => $baseUrl . '/public/failure.php'
        ],
        'external_reference' => $externalReference,
        'statement_descriptor' => 'FARMACIA ONLINE'
    ];

    if ($scheme === 'https' && !preg_match('/(localhost|127\\.0\\.0\\.1)/i', $host)) {
        $payload['auto_return'] = 'approved';
    }

    if (!preg_match('/(localhost|127\\.0\\.0\\.1)/i', $host)) {
        $payload['notification_url'] = $baseUrl . '/api/webhook.php';
    }

    $ch = curl_init('https://api.mercadopago.com/checkout/preferences');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . MP_ACCESS_TOKEN
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new RuntimeException('Error al comunicarse con Mercado Pago: ' . $curlError);
    }

    $responseData = json_decode((string)$response, true);

    if (!in_array($httpCode, [200, 201], true) || !is_array($responseData)) {
        $message = is_array($responseData)
            ? (string)($responseData['message'] ?? 'No se pudo crear la preferencia de pago.')
            : 'No se pudo crear la preferencia de pago.';
        throw new RuntimeException("Mercado Pago respondió con error {$httpCode}: {$message}");
    }

    $db->commit();

    echo json_encode([
        'status' => 'success',
        'order_id' => $orderId,
        'external_reference' => $externalReference,
        'preference_id' => $responseData['id'] ?? '',
        'init_point' => $responseData['init_point'] ?? '',
        'sandbox_init_point' => $responseData['sandbox_init_point'] ?? ($responseData['init_point'] ?? '')
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (isset($db) && $db instanceof mysqli) {
        try {
            $db->rollback();
        } catch (Throwable $ignored) {
        }
    }

    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
