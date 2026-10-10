<?php

session_start();
include('../includes/config.php');
require_once __DIR__ . '/../includes/order_helpers.php';

requireCustomer();
$orderId = (int) ($_GET['id'] ?? 0);

$res = mysqli_execute_query(
    $conn,
    "SELECT o.order_id, o.order_date, o.order_status, o.fulfillment_method, o.scheduled_at,
            o.recipient_name, o.recipient_phone, o.customer_note,
            d.delivery_address, d.delivery_fee, a.area_name,
            p.payment_method, p.payment_status
     FROM tbl_orders o
     LEFT JOIN tbl_deliveries d ON d.order_id = o.order_id
     LEFT JOIN tbl_delivery_areas a ON a.area_id = d.area_id
     LEFT JOIN tbl_payments p ON p.order_id = o.order_id
     WHERE o.order_id = ? AND o.customer_id = ? LIMIT 1",
    [$orderId, (int) $_SESSION['customer_id']]
);
$order = mysqli_fetch_assoc($res);
if (!$order) {
    header('Location: /InfoMan-Project/user/myorders.php');
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

$ovSubtotal = 0.0;
foreach ($rows as $r) {
    $ovSubtotal += $r['quantity'] * $r['unit_price'];
}
$ovFee      = (float) ($order['delivery_fee'] ?? 0);
$ovTotal    = $ovSubtotal + $ovFee;
$ovDelivery = $order['fulfillment_method'] === 'Delivery';

$ovMeaning = [
    'Pending'          => 'Waiting for the store to confirm your order.',
    'Confirmed'        => 'The store accepted your order.',
    'Preparing'        => 'The store is preparing your order.',
    'Ready'            => $ovDelivery ? 'Your order is packed and waiting for the rider.' : 'Your order is ready for pick-up. Please bring the exact cash.',
    'Out for Delivery' => 'Your order is on its way. Please prepare the exact cash.',
    'Completed'        => 'Thank you for your order!',
    'Cancelled'        => 'This order was cancelled.',
];

$ovCanCancel = $order['order_status'] === 'Pending';
$ovAsking    = $ovCanCancel && isset($_GET['cancel']);          
if ($ovAsking) {
    $_SESSION['cancel_token'] = bin2hex(random_bytes(16));     
}
$ovMessage = $_SESSION['mo_message'] ?? '';                     
$ovError   = $_SESSION['mo_error'] ?? '';
unset($_SESSION['mo_message'], $_SESSION['mo_error']);

include('../includes/header.php');
?>

<style>
#ovWrap { max-width: 680px; margin: 0 auto 40px; padding: 0 12px; color: #4a3b30; }
#ovBack { display: inline-block; margin-bottom: 8px; color: #B77466; font-weight: 600; }
#ovCard { border: 3px solid #4a3b30; border-radius: 14px; padding: 24px; background: #fff; }
#ovNumber { font-size: 30px; font-weight: 800; color: #B77466; text-align: center; }
#ovStatus { text-align: center; margin: 6px 0 2px; }
#ovMeaning { text-align: center; color: #957C62; margin-bottom: 16px; }
.ovBadge { display: inline-block; padding: 3px 14px; border-radius: 999px; font-weight: 700; background: #FFE3B0; }
.ovBadge.ovSt-Confirmed, .ovBadge.ovSt-Preparing { background: #DCE9F7; }
.ovBadge.ovSt-Ready, .ovBadge.ovSt-OutforDelivery { background: #D8EFD3; }
.ovBadge.ovSt-Completed { background: #B9E2B1; }
.ovBadge.ovSt-Cancelled { background: #EBD6D6; color: #8A3B3B; }
#ovBox { background: #FFF3DC; border: 1px solid #E2B59A; border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; }
#ovItems { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
#ovItems th { text-align: left; font-size: 13px; color: #957C62; padding: 6px 4px; border-bottom: 3px solid #E2B59A; }
#ovItems td { padding: 6px 4px; border-bottom: 1px solid #FFE3B0; }
#ovItems .ovR { text-align: right; }
#ovTotals div { display: flex; justify-content: space-between; padding: 3px 0; }
#ovTotals .ovGrand { font-size: 20px; font-weight: 800; border-top: 3px solid #E2B59A; margin-top: 6px; padding-top: 8px; }
#ovOk { background: #E6F4E2; border: 1px solid #A9D4A0; border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; text-align: center; }
#ovErr { background: #FBE9E7; border: 1px solid #E0A8A0; color: #8A3B3B; border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; text-align: center; }
#ovCancelArea { margin-top: 18px; text-align: center; }
#ovCancelArea .ovHint { color: #957C62; font-size: 14px; margin-top: 8px; }
#ovCancelStart, #ovCancelYes, #ovKeep { display: inline-block; min-width: 0; font-size: 16px; font-family: inherit; line-height: 1.4; padding: 10px 22px; border-radius: 999px; font-weight: 700; text-decoration: none; cursor: pointer; text-shadow: none; }
#ovCancelStart { background: #fff; color: #8A3B3B; border: 2px solid #8A3B3B; }
#ovCancelYes { background: #8A3B3B; color: #fff; border: 2px solid #8A3B3B; }
#ovKeep { background: #fff; color: #4a3b30; border: 2px solid #E2B59A; }
#ovConfirm { background: #FBE9E7; border: 2px solid #E0A8A0; border-radius: 10px; padding: 16px; }
#ovConfirm form { display: inline; }
</style>

<div id="ovWrap">
    <a id="ovBack" href="myorders.php">&larr; Back to My Orders</a>

    <?php if ($ovMessage !== ''): ?><div id="ovOk"><?= h($ovMessage) ?></div><?php endif; ?>
    <?php if ($ovError !== ''): ?><div id="ovErr"><?= h($ovError) ?></div><?php endif; ?>

    <div id="ovCard">
        <div id="ovNumber"><?= h(orderNumber((int) $order['order_id'])) ?></div>
        <div id="ovStatus"><span class="ovBadge ovSt-<?= h(str_replace(' ', '', $order['order_status'])) ?>"><?= h($order['order_status']) ?></span></div>
        <div id="ovMeaning"><?= h($ovMeaning[$order['order_status']] ?? '') ?></div>

        <div id="ovBox">
            Placed on: <?= h(date('M j, Y g:i A', strtotime($order['order_date']))) ?><br>
            <?php if ($ovDelivery): ?>
                <strong>Delivery</strong> - <?= h(date('D, M j \a\t g:i A', strtotime($order['scheduled_at']))) ?> (target time)<br>
                To: <?= h($order['recipient_name']) ?> (<?= h($order['recipient_phone']) ?>)<br>
                Address: <?= h($order['delivery_address']) ?>, <?= h($order['area_name']) ?>
            <?php else: ?>
                <strong>Pick-up</strong> - <?= h(date('D, M j \a\t g:i A', strtotime($order['scheduled_at']))) ?> (target time)<br>
                Picked up by: <?= h($order['recipient_name']) ?> (<?= h($order['recipient_phone']) ?>)
            <?php endif; ?>
            <?php if (!empty($order['customer_note'])): ?><br>Your note: <?= h($order['customer_note']) ?><?php endif; ?>
        </div>

        <table id="ovItems">
            <thead><tr><th>Product</th><th class="ovR">Price</th><th class="ovR">Qty</th><th class="ovR">Total</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= h($r['product_name']) ?></td>
                    <td class="ovR"><?= peso($r['unit_price']) ?></td>
                    <td class="ovR"><?= (int) $r['quantity'] ?></td>
                    <td class="ovR"><?= peso($r['quantity'] * $r['unit_price']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div id="ovTotals">
            <div><span>Subtotal</span><span><?= peso($ovSubtotal) ?></span></div>
            <?php if ($ovDelivery): ?><div><span>Delivery fee</span><span><?= peso($ovFee) ?></span></div><?php endif; ?>
            <div class="ovGrand"><span>Total</span><span><?= peso($ovTotal) ?></span></div>
            <div><span>Payment</span><span><?= h($order['payment_method'] ?? '') ?> - <?= h($order['payment_status'] ?? 'Unpaid') ?></span></div>
        </div>

        <div id="ovCancelArea">
            <?php if ($ovAsking): ?>
                <div id="ovConfirm">
                    <strong>Cancel this order?</strong><br>
                    The items will go back to the store's stock. This cannot be undone.<br><br>
                    <form method="post" action="/InfoMan-Project/transaction/cancel_order.php">
                        <input type="hidden" name="order_id" value="<?= (int) $order['order_id'] ?>">
                        <input type="hidden" name="token" value="<?= h($_SESSION['cancel_token']) ?>">
                        <button type="submit" id="ovCancelYes">Yes, cancel my order</button>
                    </form>
                    <a id="ovKeep" href="order_view.php?id=<?= (int) $order['order_id'] ?>">No, keep it</a>
                </div>
            <?php elseif ($ovCanCancel): ?>
                <a id="ovCancelStart" href="order_view.php?id=<?= (int) $order['order_id'] ?>&cancel=1">Cancel order</a>
                <div class="ovHint">You can cancel while the store has not confirmed the order yet.</div>
            <?php elseif ($order['order_status'] !== 'Cancelled' && $order['order_status'] !== 'Completed'): ?>
                <div class="ovHint">The store is already working on this order, so it can no longer be cancelled here. Please contact the store.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
