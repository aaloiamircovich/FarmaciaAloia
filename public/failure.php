<?php
require_once __DIR__ . '/../config/payment_sync.php';
$paymentId=(string)($_GET['payment_id']??$_GET['collection_id']??'');
$reference=(string)($_GET['external_reference']??'');
if($paymentId!==''){try{$r=syncPaymentFromMercadoPago($paymentId);$reference=$r['external_reference']??$reference;}catch(Throwable $e){}}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pago no completado | Farmacia Salud+</title><link rel="stylesheet" href="css/styles.css"></head><body class="result-page"><main class="result-card"><div class="result-icon">!</div><p class="eyebrow">MERCADO PAGO</p><h1>Pago no completado</h1><p>El pago fue rechazado o cancelado. Podés volver al carrito e intentarlo nuevamente.</p><div class="result-meta"><strong>Referencia:</strong> <?= htmlspecialchars($reference?:'No informada') ?></div><div class="result-actions"><a class="btn-primary" href="../index.php">Volver al carrito</a></div></main></body></html>
