<?php
require_once __DIR__ . '/../env.php';
require_once __DIR__ . '/../config/payment_sync.php';

$paymentId = (string)($_GET['payment_id'] ?? $_GET['collection_id'] ?? '');
$reference = (string)($_GET['external_reference'] ?? '');
$syncMessage = '';
$status = 'approved';

if ($paymentId !== '') {
    try {
        $result = syncPaymentFromMercadoPago($paymentId);
        $reference = $result['external_reference'] ?? $reference;
        $status = $result['status'] ?? 'approved';
        $syncMessage = $status === 'approved' ? 'El pago quedó registrado como aprobado.' : 'Mercado Pago devolvió el estado: ' . $status . '.';
    } catch (Throwable $e) {
        $syncMessage = 'El pago volvió correctamente, pero no se pudo sincronizar el estado local automáticamente.';
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pago recibido | Farmacia Salud+</title><link rel="stylesheet" href="css/styles.css"></head>
<body class="result-page"><main class="result-card"><div class="result-icon">✓</div><p class="eyebrow">MERCADO PAGO</p><h1>¡Pago recibido!</h1><p>Gracias por tu compra en Farmacia Salud+.</p><?php if($syncMessage): ?><p><?= htmlspecialchars($syncMessage) ?></p><?php endif; ?><div class="result-meta"><strong>Referencia:</strong> <?= htmlspecialchars($reference ?: 'No informada') ?><br><strong>ID de pago:</strong> <?= htmlspecialchars($paymentId ?: 'No informado') ?></div><div class="result-actions"><a class="btn-primary" href="../index.php">Volver a la tienda</a><a class="btn-secondary" href="../admin/index.php">Ver administración</a></div></main>
<script>localStorage.removeItem('farmacia_online_cart_v1');localStorage.removeItem('farmacia_online_prescription_v1');</script></body></html>
