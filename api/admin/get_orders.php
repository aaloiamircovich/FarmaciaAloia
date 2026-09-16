<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';

requireAdminAuth();

try {
    $db = Database::getConnection();

    $result = $db->query(
        'SELECT id, external_reference, monto_total, estado, mp_payment_id, mp_merchant_order_id,
                receta_archivo, created_at, updated_at
         FROM ordenes
         ORDER BY id DESC
         LIMIT 100'
    );

    $orders = $result->fetch_all(MYSQLI_ASSOC);

    $stmtItems = $db->prepare(
        'SELECT oi.producto_id, oi.cantidad, oi.precio_unitario, p.nombre AS producto_nombre
         FROM orden_items oi
         LEFT JOIN productos p ON p.id = oi.producto_id
         WHERE oi.orden_id = ?'
    );

    $totalRevenue = 0.0;
    $approvedCount = 0;
    $pendingCount = 0;
    $rejectedCount = 0;

    foreach ($orders as &$order) {
        $order['id'] = (int)$order['id'];
        $order['monto_total'] = (float)$order['monto_total'];
        $order['tiene_receta'] = !empty($order['receta_archivo']);
        unset($order['receta_archivo']);

        if ($order['estado'] === 'approved') {
            $totalRevenue += $order['monto_total'];
            $approvedCount++;
        } elseif ($order['estado'] === 'pending') {
            $pendingCount++;
        } else {
            $rejectedCount++;
        }

        $stmtItems->bind_param('i', $order['id']);
        $stmtItems->execute();
        $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($items as &$item) {
            $item['producto_id'] = (int)$item['producto_id'];
            $item['cantidad'] = (int)$item['cantidad'];
            $item['precio_unitario'] = (float)$item['precio_unitario'];
        }

        $order['items'] = $items;
    }

    echo json_encode([
        'status' => 'success',
        'stats' => [
            'total_orders' => count($orders),
            'approved_count' => $approvedCount,
            'pending_count' => $pendingCount,
            'rejected_count' => $rejectedCount,
            'total_revenue' => $totalRevenue
        ],
        'data' => $orders
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'No se pudieron cargar las órdenes.'], JSON_UNESCAPED_UNICODE);
}
