<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/payment_sync.php';

$paymentId = $_GET['data_id'] ?? $_GET['id'] ?? null;
$type = $_GET['type'] ?? $_GET['topic'] ?? null;

if (!$paymentId) {
    $body = json_decode(file_get_contents('php://input'), true);
    if (is_array($body)) {
        $paymentId = $body['data']['id'] ?? null;
        $type = $body['type'] ?? $type;
    }
}

@file_put_contents(
    __DIR__ . '/webhook.log',
    sprintf("[%s] type=%s payment=%s\n", date('Y-m-d H:i:s'), (string)$type, (string)$paymentId),
    FILE_APPEND
);

if ($paymentId && ($type === 'payment' || $type === null)) {
    try {
        $result = syncPaymentFromMercadoPago((string)$paymentId);
        @file_put_contents(
            __DIR__ . '/webhook.log',
            "Sincronizado: {$result['external_reference']} -> {$result['status']}\n",
            FILE_APPEND
        );
    } catch (Throwable $e) {
        @file_put_contents(
            __DIR__ . '/webhook.log',
            'ERROR: ' . $e->getMessage() . "\n",
            FILE_APPEND
        );
    }
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
