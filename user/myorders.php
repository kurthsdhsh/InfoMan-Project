<?php

session_start();
include('../includes/config.php');
require_once __DIR__ . '/../includes/order_helpers.php';

requireCustomer();
$customerId = (int) $_SESSION['customer_id'];

$show = $_GET['show'] ?? 'all';
$filters = [
    'all'       => ['All',       ''],
    'active'    => ['Active',    " AND o.order_status IN ('Pending','Confirmed','Preparing','Ready','Out for Delivery')"],
    'done'      => ['Completed', " AND o.order_status = 'Completed'"],
    'cancelled' => ['Cancelled', " AND o.order_status = 'Cancelled'"],
];
if (!isset($filters[$show])) {
    $show = 'all';
}

$res = mysqli_execute_query(
    $conn,
        "SELECT order_id, order_date, order_status, fulfillment_method, scheduled_at, payment_status, grand_total
     FROM v_order_summary o
     WHERE o.customer_id = ?" . $filters[$show][1] . "
     ORDER BY o.order_id DESC",

    [$customerId]
);
$orders = mysqli_fetch_all($res, MYSQLI_ASSOC);

include('../includes/header.php');
?>

<div id="moWrap">
    <h1 class="text-center my-4">My Orders</h1>

    <div id="moTabs">
        <?php foreach ($filters as $key => $f): ?>
            <a href="myorders.php?show=<?= $key ?>" class="<?= $key === $show ? 'moOn' : '' ?>"><?= h($f[0]) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (!$orders): ?>
        <div id="moEmpty">
            No orders here yet.<br>
            <a href="/InfoMan-Project/index.php">Start shopping</a>
        </div>
    <?php else: ?>
        <table id="moTable">
            <thead>
                <tr>
                    <th>Order no.</th>
                    <th>Placed on</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th class="moR">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><strong><?= h(orderNumber((int) $o['order_id'])) ?></strong></td>
                    <td><?= h(date('M j, Y g:i A', strtotime($o['order_date']))) ?></td>
                    <td><?= h($o['fulfillment_method'] === 'Delivery' ? 'Delivery' : 'Pick-up') ?></td>
                    <td><span class="moBadge moSt-<?= h(str_replace(' ', '', $o['order_status'])) ?>"><?= h($o['order_status']) ?></span></td>
                    <td class="moR"><?= peso((float) $o['grand_total']) ?></td>
                    <td class="moR"><a class="moView" href="order_view.php?id=<?= (int) $o['order_id'] ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>