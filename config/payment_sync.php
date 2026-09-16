<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../env.php';

/**
 * Consulta un pago en Mercado Pago y sincroniza el estado local de la orden.
 * Es idempotente: el stock solo se descuenta la primera vez que pasa a approved.
 */
function syncPaymentFromMercadoPago(string $paymentId): array
{
    if ($paymentId === '') {
        throw new InvalidArgumentException('ID de pago vacío.');
    }

    if (MP_ACCESS_TOKEN === '' || str_contains(MP_ACCESS_TOKEN, 'PEGAR_AQUI')) {
        throw new RuntimeException('Falta configurar MP_ACCESS_TOKEN en el archivo .env.');
    }

    $ch = curl_init('https://api.mercadopago.com/v1/payments/' . rawurlencode($paymentId));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . MP_ACCESS_TOKEN
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new RuntimeException('Error al consultar Mercado Pago: ' . $curlError);
    }

    $payment = json_decode((string)$response, true);

    if ($httpCode !== 200 || !is_array($payment)) {
        throw new RuntimeException('Mercado Pago no devolvió un pago válido.');
    }

    $externalReference = (string)($payment['external_reference'] ?? '');
    if ($externalReference === '') {
        throw new RuntimeException('El pago no contiene external_reference.');
    }

    $mpStatus = (string)($payment['status'] ?? 'pending');
    $dbStatus = 'pending';
    if ($mpStatus === 'approved') {
        $dbStatus = 'approved';
    } elseif (in_array($mpStatus, ['rejected', 'cancelled', 'refunded', 'charged_back'], true)) {
        $dbStatus = 'rejected';
    }

    $merchantOrderId = (string)($payment['order']['id'] ?? '');
    $db = Database::getConnection();
    $db->begin_transaction();

    try {
        $stmt = $db->prepare('SELECT id, estado FROM ordenes WHERE external_reference = ? FOR UPDATE');
        $stmt->bind_param('s', $externalReference);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            throw new RuntimeException('No se encontró la orden asociada al pago.');
        }

        $previousStatus = (string)$order['estado'];
        $orderId = (int)$order['id'];

        $stmtUpdate = $db->prepare(
            'UPDATE ordenes
             SET estado = ?, mp_payment_id = ?, mp_merchant_order_id = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $stmtUpdate->bind_param('sssi', $dbStatus, $paymentId, $merchantOrderId, $orderId);
        $stmtUpdate->execute();

        if ($dbStatus === 'approved' && $previousStatus !== 'approved') {
            $stmtItems = $db->prepare('SELECT producto_id, cantidad FROM orden_items WHERE orden_id = ?');
            $stmtItems->bind_param('i', $orderId);
            $stmtItems->execute();
            $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

            $stmtStock = $db->prepare('UPDATE productos SET stock = GREATEST(0, stock - ?) WHERE id = ?');
            foreach ($items as $item) {
                $quantity = (int)$item['cantidad'];
                $productId = (int)$item['producto_id'];
                $stmtStock->bind_param('ii', $quantity, $productId);
                $stmtStock->execute();
            }
        }

        $db->commit();

        return [
            'status' => $dbStatus,
            'external_reference' => $externalReference,
            'order_id' => $orderId,
            'payment' => $payment
        ];
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}
