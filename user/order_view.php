<?php

session_start();
include('../includes/config.php');
require_once __DIR__ . '/../includes/order_helpers.php';

requireCustomer();
$orderId = (int) ($_GET['id'] ?? 0);

$res = mysqli_execute_query(
    $conn,
    "SELECT * FROM v_order_summary WHERE order_id = ? AND customer_id = ? LIMIT 1",
    [$orderId, (int) $_SESSION['customer_id']]
);
$order = mysqli_fetch_assoc($res);
if (!$order) {
    header('Location: /InfoMan-Project/user/myorders.php');
    exit();
}

$items = mysqli_execute_query(
    $conn,
    "SELECT product_name, quantity, unit_price, line_total
     FROM v_order_details WHERE order_id = ? ORDER BY product_name",
    [$orderId]
);
$rows = mysqli_fetch_all($items, MYSQLI_ASSOC);

// the view already calculated these
$ovSubtotal = (float) $order['items_total'];
$ovFee      = (float) $order['delivery_fee'];
$ovTotal    = (float) $order['grand_total'];
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

<div id="ovWrap">
    <a id="ovBack" href="myorders.php">&larr; Back to My Orders</a>

    <?php if ($ovMessage !== ''): ?><div id="ovOk"><?= h($ovMessage) ?></div><?php endif; ?>
    <?php if ($ovError !== ''): ?><div id="ovErr"><?= h($ovError) ?></div><?php endif; ?>

    <div id="ovCard">
        <div id="ovNumber"><?= h(orderNumber((int) $order['order_id'])) ?></div>
        <div id="ovStatus"><span class="ovBadge ovSt-<?= h(str_replace(' ', '', $order['order_status'])) ?>"><?= h($order['order_status']) ?></span></div>
        <div id="ovMeaning"><?= h($ovMeaning[$order['order_status']] ?? '') ?></div>
        
        <?php if ($order['order_status'] === 'Cancelled' && !empty($order['cancel_reason'])): ?>
        <div id="ovReason">Reason from the store: <?= h($order['cancel_reason']) ?></div>
        <?php endif; ?>


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
