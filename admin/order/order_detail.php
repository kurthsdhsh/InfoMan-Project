<?php

require_once '../auth.php';
require_once '../../includes/config.php';
require_once '../../includes/order_helpers.php';

$orderId = (int) ($_GET['id'] ?? 0);

$res = mysqli_execute_query(
    $conn,
    "SELECT o.order_id, o.order_date, o.order_status, o.fulfillment_method, o.scheduled_at,
            o.recipient_name, o.recipient_phone, o.customer_note, o.cancel_reason,
            c.first_name, c.last_name, c.email, c.contact_number,
            d.delivery_address, d.delivery_fee, a.area_name,
            p.payment_method, p.payment_status, p.amount_paid, p.payment_date, ad.admin_name AS confirmed_name
     FROM tbl_orders o
     INNER JOIN tbl_customers c ON c.customer_id = o.customer_id
     LEFT JOIN tbl_deliveries d ON d.order_id = o.order_id
     LEFT JOIN tbl_delivery_areas a ON a.area_id = d.area_id
     LEFT JOIN tbl_payments p ON p.order_id = o.order_id
     LEFT JOIN tbl_admins ad ON ad.admin_id = p.confirmed_by
     WHERE o.order_id = ? LIMIT 1",
    [$orderId]
);
$order = mysqli_fetch_assoc($res);
if (!$order) {
    $_SESSION['error'] = 'Order not found.';
    header('Location: orders.php');
    exit;
}

$res = mysqli_execute_query(
    $conn,
    "SELECT pr.product_name, i.quantity, i.unit_price
     FROM tbl_order_items i INNER JOIN tbl_products pr ON pr.product_id = i.product_id
     WHERE i.order_id = ? ORDER BY pr.product_name",
    [$orderId]
);
$rows = mysqli_fetch_all($res, MYSQLI_ASSOC);

$ordSubtotal = 0.0;
foreach ($rows as $r) {
    $ordSubtotal += $r['quantity'] * $r['unit_price'];
}
$ordFee      = (float) ($order['delivery_fee'] ?? 0);
$ordTotal    = $ordSubtotal + $ordFee;
$ordDelivery = $order['fulfillment_method'] === 'Delivery';
$ordStatus   = $order['order_status'];

$ordNext      = nextOrderStep($ordStatus, $order['fulfillment_method']);   // [new status, button words] or null
$ordCanPay    = $ordStatus === 'Ready' || $ordStatus === 'Out for Delivery'; // last step: cash received
$ordCanCancel = storeCanCancel($ordStatus);
$ordAsking    = $ordCanCancel && isset($_GET['cancel']);                    // the "cancel with a reason" step

$_SESSION['ao_token'] = bin2hex(random_bytes(16));

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
$ordOld = $_SESSION['ao_form'] ?? [];

unset($_SESSION['success'], $_SESSION['error'], $_SESSION['ao_form']);
?>

<?php require_once '../../includes/adminHeader.php'; ?>

<div class="admin-container">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>
                Order <?= htmlspecialchars(orderNumber((int) $order['order_id'])) ?>
                <span class="badge <?= orderBadgeClass($ordStatus) ?> fs-6"><?= htmlspecialchars($ordStatus) ?></span>
            </h2>
        </div>

        <a href="orders.php" class="btn btn-secondary">
            Back to Orders
        </a>
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-4">

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Customer</h6>
                    <p class="mb-1"><strong><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></strong></p>
                    <p class="mb-1"><?= htmlspecialchars($order['contact_number']) ?></p>
                    <p class="mb-1"><?= htmlspecialchars($order['email']) ?></p>
                    <small class="text-muted">
                        Placed on <?= htmlspecialchars(date('M j, Y g:i A', strtotime($order['order_date']))) ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-3"><?= $ordDelivery ? 'Delivery' : 'Pick-up' ?></h6>
                    <p class="mb-1">
                        <strong><?= htmlspecialchars(date('D, M j \a\t g:i A', strtotime($order['scheduled_at']))) ?></strong>
                        (target time)
                    </p>
                    <p class="mb-1">
                        <?= $ordDelivery ? 'Deliver to' : 'Picked up by' ?>:
                        <?= htmlspecialchars($order['recipient_name']) ?> (<?= htmlspecialchars($order['recipient_phone']) ?>)
                    </p>
                    <?php if ($ordDelivery): ?>
                        <p class="mb-1">Address: <?= htmlspecialchars($order['delivery_address']) ?>, <?= htmlspecialchars($order['area_name']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($order['customer_note'])): ?>
                        <p class="mb-1">Customer note: <?= htmlspecialchars($order['customer_note']) ?></p>
                    <?php endif; ?>
                    <?php if ($ordStatus === 'Cancelled' && !empty($order['cancel_reason'])): ?>
                        <p class="mb-0 text-danger">Cancelled by the store. Reason: <?= htmlspecialchars($order['cancel_reason']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <div class="card mb-4">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['product_name']) ?></td>
                            <td><?= peso($r['unit_price']) ?></td>
                            <td><?= (int) $r['quantity'] ?></td>
                            <td><?= peso($r['quantity'] * $r['unit_price']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= peso($ordSubtotal) ?></span></div>
            <?php if ($ordDelivery): ?>
                <div class="d-flex justify-content-between"><span>Delivery fee</span><span><?= peso($ordFee) ?></span></div>
            <?php endif; ?>
            <div class="d-flex justify-content-between fs-5 fw-bold border-top mt-2 pt-2">
                <span>Total to collect in cash</span><span><?= peso($ordTotal) ?></span>
            </div>
            <div class="d-flex justify-content-between mt-2">
                <span>Payment</span>
                <span><?= htmlspecialchars($order['payment_method'] ?? '') ?> - <?= htmlspecialchars($order['payment_status'] ?? 'Unpaid') ?></span>
            </div>
            <?php if (($order['payment_status'] ?? '') === 'Paid'): ?>
                <div class="d-flex justify-content-between">
                    <span>Cash received</span>
                    <span>
                        <?= peso($order['amount_paid']) ?> on <?= htmlspecialchars(date('M j, g:i A', strtotime($order['payment_date']))) ?>
                        <?= $order['confirmed_name'] ? ' by ' . htmlspecialchars($order['confirmed_name']) : '' ?>
                    </span>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <?php if (!$ordAsking): ?>

                <div class="d-flex flex-wrap gap-2 align-items-center">

                    <?php if ($ordNext): ?>
                        <form method="POST" action="order_action.php">
                            <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['ao_token']) ?>">
                            <input type="hidden" name="from" value="<?= htmlspecialchars($ordStatus) ?>">
                            <button type="submit" name="do" value="next" class="btn btn-primary">
                                <?= htmlspecialchars($ordNext[1]) ?>
                            </button>
                        </form>

                    <?php elseif ($ordCanPay): ?>
                        <form method="POST" action="order_action.php">
                            <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['ao_token']) ?>">
                            <input type="hidden" name="from" value="<?= htmlspecialchars($ordStatus) ?>">
                            <button type="submit" name="do" value="complete" class="btn btn-primary">
                                Cash received - complete order
                            </button>
                        </form>

                    <?php elseif ($ordStatus === 'Completed'): ?>
                        <span class="text-muted">This order is completed and paid.</span>

                    <?php else: ?>
                        <span class="text-muted">This order was cancelled.</span>
                    <?php endif; ?>

                    <?php if ($ordCanCancel): ?>
                        <a href="order_detail.php?id=<?= (int) $orderId ?>&cancel=1" class="btn btn-outline-danger ms-auto">
                            Cancel Order
                        </a>
                    <?php endif; ?>

                </div>

            <?php else: ?>

                <h5>Cancel this order?</h5>
                <p class="text-muted">The customer will see your reason.</p>

                <form method="POST" action="order_action.php">
                    <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['ao_token']) ?>">
                    <input type="hidden" name="from" value="<?= htmlspecialchars($ordStatus) ?>">

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason</label>
                        <textarea
                            class="form-control"
                            id="reason"
                            name="reason"
                            rows="3"
                            maxlength="255"
                            placeholder="Example: Out of stock, or the customer could not be reached"
                            required><?= htmlspecialchars($ordOld['reason'] ?? '') ?></textarea>
                    </div>

                    <div class="form-check mb-3">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="restock"
                            name="restock"
                            value="1"
                            <?= !isset($ordOld['reason']) || isset($ordOld['restock']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="restock">
                            Put the items back in stock (untick if the food can no longer be sold)
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" name="do" value="cancel" class="btn btn-danger">
                            Yes, Cancel This Order
                        </button>

                        <a href="order_detail.php?id=<?= (int) $orderId ?>" class="btn btn-secondary">
                            No, Keep It
                        </a>
                    </div>
                </form>

            <?php endif; ?>

        </div>
    </div>

</div>

</main>
</body>
</html>
