<?php

session_start();
include('../../includes/config.php');
require_once __DIR__ . '/../../includes/order_helpers.php';

requireCustomer();
$orderId = (int) ($_GET['id'] ?? 0);

$res = mysqli_execute_query(
    $conn,
    "SELECT o.order_id, o.fulfillment_method, o.scheduled_at, o.recipient_name, o.recipient_phone, o.customer_note,
            d.delivery_address, d.delivery_fee, a.area_name, p.payment_status
     FROM tbl_orders o
     LEFT JOIN tbl_deliveries d ON d.order_id = o.order_id
     LEFT JOIN tbl_delivery_areas a ON a.area_id = d.area_id
     LEFT JOIN tbl_payments p ON p.order_id = o.order_id
     WHERE o.order_id = ? AND o.customer_id = ? LIMIT 1",
    [$orderId, (int) $_SESSION['customer_id']]
);
$order = mysqli_fetch_assoc($res);
if (!$order) {
    header('Location: /InfoMan-Project/index.php');
    exit();
}

$items = mysqli_execute_query(
    $conn,
    "SELECT pr.product_name, i.quantity, i.unit_price
     FROM tbl_order_items i JOIN tbl_products pr ON pr.product_id = i.product_id
     WHERE i.order_id = ? ORDER BY pr.product_name",
    [$orderId]
);
$rows = mysqli_fetch_all($items, MYSQLI_ASSOC);

$osSubtotal = 0.0;
foreach ($rows as $r) {
    $osSubtotal += $r['quantity'] * $r['unit_price'];   // calculated, never stored
}
$osFee   = (float) ($order['delivery_fee'] ?? 0);
$osTotal = $osSubtotal + $osFee;
$delivery = $order['fulfillment_method'] === 'Delivery';

include('../../includes/header.php');
?>

<style>
#osWrap { max-width: 640px; margin: 0 auto 40px; padding: 0 12px; color: #4a3b30; }
#osCard { border: 3px solid #4a3b30; border-radius: 14px; padding: 24px; background: #fff; }
#osNumber { font-size: 34px; font-weight: 800; letter-spacing: 1px; color: #B77466; text-align: center; margin: 4px 0 2px; }
#osNote { text-align: center; color: #957C62; margin-bottom: 16px; }
#osBox { background: #FFF3DC; border: 1px solid #E2B59A; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; }
#osItems { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
#osItems th { text-align: left; font-size: 13px; color: #957C62; padding: 6px 4px; border-bottom: 3px solid #E2B59A; }
#osItems td { padding: 6px 4px; border-bottom: 1px solid #FFE3B0; }
#osItems .osR { text-align: right; }
#osTotals div { display: flex; justify-content: space-between; padding: 3px 0; }
#osTotals .osGrand { font-size: 20px; font-weight: 800; border-top: 3px solid #E2B59A; margin-top: 6px; padding-top: 8px; }
#osHome { display: block; margin: 18px auto 0; text-align: center; color: #B77466; font-weight: 600; }
</style>

<div id="osWrap">
    <h1 class="text-center my-4">Order placed!</h1>
    <div id="osCard">
        <div class="text-center">Your order number</div>
        <div id="osNumber"><?= h(orderNumber((int) $order['order_id'])) ?></div>
        <div id="osNote">Please keep this number.</div>

        <div id="osBox">
            <?php if ($delivery): ?>
                <strong>Delivery</strong> - <?= h(date('D, M j \a\t g:i A', strtotime($order['scheduled_at']))) ?> (target time)<br>
                To: <?= h($order['recipient_name']) ?> (<?= h($order['recipient_phone']) ?>)<br>
                Address: <?= h($order['delivery_address']) ?>, <?= h($order['area_name']) ?><br>
                <span id="osNext">Our rider will bring your order. Please prepare the exact cash.</span>
            <?php else: ?>
                <strong>Pick-up</strong> - <?= h(date('D, M j \a\t g:i A', strtotime($order['scheduled_at']))) ?> (target time)<br>
                Picked up by: <?= h($order['recipient_name']) ?> (<?= h($order['recipient_phone']) ?>)<br>
                <span id="osNext">Show this order number at the store and pay in cash.</span>
            <?php endif; ?>
            <?php if (!empty($order['customer_note'])): ?><br>Your note: <?= h($order['customer_note']) ?><?php endif; ?>
        </div>

        <table id="osItems">
            <thead><tr><th>Product</th><th class="osR">Qty</th><th class="osR">Total</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= h($r['product_name']) ?></td>
                    <td class="osR"><?= (int) $r['quantity'] ?></td>
                    <td class="osR"><?= peso($r['quantity'] * $r['unit_price']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div id="osTotals">
            <div><span>Subtotal</span><span><?= peso($osSubtotal) ?></span></div>
            <?php if ($delivery): ?><div><span>Delivery fee</span><span><?= peso($osFee) ?></span></div><?php endif; ?>
            <div class="osGrand"><span>Total to pay in cash</span><span><?= peso($osTotal) ?></span></div>
            <div><span>Payment</span><span><?= $delivery ? 'Cash on Delivery' : 'Cash on Pickup' ?> - <?= h($order['payment_status'] ?? 'Unpaid') ?></span></div>
        </div>
    </div>
    <a id="osHome" href="/InfoMan-Project/user/myorders.php">View my orders</a>
    <a id="osHome2" href="/InfoMan-Project/index.php">Continue shopping</a>

</div>

<?php include('../../includes/footer.php'); ?>
